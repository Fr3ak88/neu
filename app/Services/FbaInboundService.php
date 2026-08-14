<?php

namespace App\Services;

use App\Models\FbaShipment;
use App\Models\FbaInboundSplit;
use App\Models\FbaShipmentCarton;
use App\Models\FbaShipmentPallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FbaInboundService implements FbaInboundServiceInterface
{
    private string $baseUrl = 'https://sellingpartnerapi-eu.amazon.com';

    public function __construct(
        private readonly SpApiAuthService $auth
    ) {}

    // ── Gesamter Workflow: Plan → Packing → Placement → Transport ──────

    public function createInboundPlan(FbaShipment $shipment): void
    {
        $token   = $this->auth->getAccessToken($shipment->amazonAccount);
        $headers = $this->headers($token);

        $this->executeFullWorkflow($shipment, $token, $headers);
    }

    private function executeFullWorkflow(FbaShipment $shipment, string $token, array $headers): void
    {
        // ── Schritt 1: Plan erstellen ────────────────────────
        $shipment->update(['status' => FbaShipment::STATUS_PLAN_CREATING]);

        $items = $this->buildItems($shipment);

        $payload = [
            'destinationMarketplaces' => [$shipment->marketplace_id],
            'labelType'               => $shipment->packaging_type === 'ltl' ? 'PALLET' : 'ITEM',
            'sourceAddress' => [
                'name'         => $shipment->ship_from_name ?? $shipment->amazonAccount->tenant->name ?? 'FBA Händler',
                'addressLine1' => $shipment->ship_from_address ?? '',
                'city'         => $shipment->ship_from_city ?? '',
                'countryCode'  => $shipment->ship_from_country ?? 'DE',
                'postalCode'   => $shipment->ship_from_zip ?? '',
                'phoneNumber'  => $shipment->ship_from_phone ?? '',
            ],
            'contactInformation' => [
                'name'         => $shipment->ship_from_name ?? $shipment->amazonAccount->tenant->name ?? 'FBA Händler',
                'phoneNumber'  => $shipment->ship_from_phone ?? '',
            ],
            'items' => $items,
        ];

        $resp = Http::withHeaders($headers)
            ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans", $payload);

        Log::info('Amazon inboundPlans Request', [
            'payload' => $payload,
            'status'  => $resp->status(),
            'body'    => $resp->json(),
        ]);

        file_put_contents(storage_path('logs/inbound-plan-request.json'), json_encode([
            'payload' => $payload,
            'status'  => $resp->status(),
            'response' => $resp->json(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // ── Retry ohne Prep-Details bei Fehler ─────────────
        if ($resp->failed()) {
            $errorBody = $resp->json();
            $errorCode = $errorBody['errors'][0]['code'] ?? '';
            $errorMsg  = $errorBody['errors'][0]['message'] ?? '';

            if (str_contains($errorCode, 'prep') || str_contains($errorMsg, 'prep')
                || str_contains($errorCode, 'Prep') || str_contains($errorMsg, 'Prep')) {
                Log::warning('Amazon lehnt Prep-Owner ab, retry mit NONE', ['error' => $errorBody]);

                $items = $this->buildItems($shipment, skipPrep: true);
                $payload['items'] = $items;

                $resp = Http::withHeaders($headers)
                    ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans", $payload);
            }

            // Retry ohne labelOwner bei Label-Fehlern
            if ($resp->failed()) {
                $errorBody = $resp->json();
                $errorCode = $errorBody['errors'][0]['code'] ?? '';
                $errorMsg  = $errorBody['errors'][0]['message'] ?? '';

                if (str_contains($errorCode, 'label') || str_contains($errorMsg, 'label')
                    || str_contains($errorCode, 'Label') || str_contains($errorMsg, 'Label')) {
                    Log::warning('Amazon lehnt Label-Owner ab, retry mit NONE', ['error' => $errorBody]);

                    $items = $this->buildItems($shipment, skipPrep: true, skipLabel: true);
                    $payload['items'] = $items;

                    $resp = Http::withHeaders($headers)
                        ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans", $payload);
                }
            }
        }

        $resp->throw();
        $planId = $resp->json('inboundPlanId');
        $shipment->update(['inbound_plan_id' => $planId]);

        Log::info("FBA Inbound Plan erstellt: {$planId}");

        $this->poll($token, $planId);

        // ── Schritt 2: Carton-Informationen (wenn vorhanden) ──
        if ($shipment->cartons()->count() > 0) {
            $this->submitCartonContent($shipment, $token, $planId, $headers);
        }

        // ── Schritt 3: Packing Options ───────────────────────
        Http::withHeaders($headers)
            ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/packingOptions")
            ->throw();
        $this->poll($token, $planId);

        $packingOptions = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/packingOptions")
            ->throw()
            ->json('packingOptions');

        $bestPacking = collect($packingOptions)->first();
        Log::info("Packing Option bestätigt: {$bestPacking['packingOptionId']}");

        Http::withHeaders($headers)
            ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/packingOptions/confirmation", [
                'packingOptionId' => $bestPacking['packingOptionId'],
            ])->throw();
        $this->poll($token, $planId);

        // ── Schritt 4: Placement Options ─────────────────────
        $shipmentIds = collect($packingOptions)->pluck('shipmentIds')->flatten()->toArray();

        Http::withHeaders($headers)
            ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/placementOptions", [
                'shipmentIds' => $shipmentIds,
            ])->throw();
        $this->poll($token, $planId);

        $placementOptions = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/placementOptions")
            ->throw()
            ->json('placementOptions');

        $bestPlacement = collect($placementOptions)->first();
        Log::info("Placement Option bestätigt: {$bestPlacement['placementOptionId']}");

        Http::withHeaders($headers)
            ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/placementOptions/confirmation", [
                'placementOptionId' => $bestPlacement['placementOptionId'],
            ])->throw();
        $this->poll($token, $planId);

        $shipment->update(['placement_option_id' => $bestPlacement['placementOptionId']]);

        // ── Schritt 5: Transportation Options ────────────────
        $finalShipmentIds = $bestPlacement['shipmentIds'] ?? $shipmentIds;

        Http::withHeaders($headers)
            ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/transportationOptions", [
                'shipmentIds'                  => $finalShipmentIds,
                'transportationOptionCriteria' => [
                    'requiredDeliveryDate' => now()->addDays(7)->toIso8601String(),
                ],
            ])->throw();
        $this->poll($token, $planId);

        $transOptions = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/transportationOptions")
            ->throw()
            ->json('transportationOptions');

        $bestTrans = collect($transOptions)->sortBy('quote.cost.value')->first();

        Http::withHeaders($headers)
            ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/transportationOptions/confirmation", [
                'transportationSelections' => [[
                    'shipmentId'             => $bestTrans['shipmentId'],
                    'transportationOptionId' => $bestTrans['transportationOptionId'],
                ]],
            ])->throw();
        $this->poll($token, $planId);

        $shipment->update([
            'transportation_option_id' => $bestTrans['transportationOptionId'],
            'shipment_ids'             => $finalShipmentIds,
        ]);

        // ── Schritt 6: Delivery Window generieren ────────────
        try {
            $this->generateDeliveryWindows($shipment, $token, $planId, $finalShipmentIds, $headers);
        } catch (\Throwable $e) {
            Log::warning("Delivery Windows konnten nicht generiert werden: " . $e->getMessage());
        }

        // ── Schritt 7: Splits speichern ──────────────────────
        $apiShipments = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/shipments")
            ->throw()
            ->json('shipments');

        foreach ($apiShipments as $s) {
            FbaInboundSplit::updateOrCreate(
                [
                    'fba_shipment_id'    => $shipment->id,
                    'amazon_shipment_id' => $s['shipmentId'],
                ],
                [
                    'fulfillment_center_id' => $s['destination']['warehouseId'] ?? '–',
                    'destination_address'   => $s['destination']['address']['city'] ?? '–',
                    'status'                => $s['status'] ?? 'working',
                ]
            );
        }

        $shipment->update(['status' => FbaShipment::STATUS_PLAN_READY]);

        Log::info("FBA Inbound Plan komplett: {$planId} — Splits: " . count($apiShipments));
    }

    // ── Items aufbauen (mit optionalem Skip für Prep/Label) ──

    private function buildItems(FbaShipment $shipment, bool $skipPrep = false, bool $skipLabel = false): array
    {
        return $shipment->items->map(function ($i) use ($skipPrep, $skipLabel) {
            $item = [
                'msku'     => $i->sku,
                'quantity' => $i->quantity,
            ];

            // prepOwner — PFLICHT bei Amazon, NONE wenn nicht gesetzt
            if (! $skipPrep && $i->prep_owner) {
                $item['prepOwner'] = $i->prep_owner;
            } else {
                $item['prepOwner'] = 'NONE';
            }

            // labelOwner — PFLICHT bei Amazon, NONE wenn nicht gesetzt
            if (! $skipLabel && $i->label_owner) {
                $item['labelOwner'] = $i->label_owner;
            } else {
                $item['labelOwner'] = 'NONE';
            }

            return $item;
        })->toArray();
    }

    // ── Carton-Content an Amazon senden ──────────────────────

    public function submitCartonContent(FbaShipment $shipment, string $token, string $planId, array $headers): void
    {
        $cartons = $shipment->cartons()->get();

        if ($cartons->isEmpty()) {
            return;
        }

        // Carton-Inhalte an Amazon senden
        $cartonData = $cartons->map(function (FbaShipmentCarton $carton) {
            $cartonItem = [
                'cartonId'     => $carton->carton_id,
                'cartonReferenceInformation' => [
                    'sellerHandle' => $carton->carton_id,
                ],
            ];

            if ($carton->weight_value) {
                $cartonItem['weight'] = [
                    'value' => $carton->weight_value,
                    'unit'  => $carton->weight_unit === 'KG' ? 'KILOGRAMS' : 'POUNDS',
                ];
            }

            if ($carton->length && $carton->width && $carton->height) {
                $cartonItem['dimensions'] = [
                    'length' => $carton->length,
                    'width'  => $carton->width,
                    'height' => $carton->height,
                    'unit'   => $carton->dimension_unit === 'CM' ? 'CENTIMETER' : 'INCHES',
                ];
            }

            // SKU-Inhalte zuordnen
            $cartonItem['contents'] = collect($carton->contents ?? [])->map(fn($c) => [
                'msku'     => $c['sku'],
                'quantity' => $c['quantity'],
            ])->toArray();

            return $cartonItem;
        })->toArray();

        Http::withHeaders($headers)
            ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/cartonContentInformation", [
                'cartonContentInformationList' => $cartonData,
            ])->throw();

        $this->poll($token, $planId);
        Log::info("Carton-Content für Plan {$planId} eingereicht: " . count($cartonData) . " Kartons");
    }

    // ── Registrierung (Finalisierung mit Tracking) ───────────

    public function register(FbaShipment $shipment): void
    {
        $token   = $this->auth->getAccessToken($shipment->amazonAccount);
        $headers = $this->headers($token);
        $planId  = $shipment->inbound_plan_id;

        foreach ($shipment->splits as $split) {
            $trackingData = [
                'trackingDetails' => [
                    'spTrackingDetail' => [
                        'trackingItems' => $this->buildTrackingItems($shipment),
                    ],
                ],
            ];

            Http::withHeaders($headers)
                ->put("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/shipments/{$split->amazon_shipment_id}/trackingDetails", $trackingData)
                ->throw();

            $split->update(['status' => 'registered']);
        }

        $shipment->update(['status' => FbaShipment::STATUS_REGISTERED]);
        Log::info("FBA Shipment registriert: {$planId}");
    }

    private function buildTrackingItems(FbaShipment $shipment): array
    {
        $items = [];

        if ($shipment->packaging_type === 'ltl' && $shipment->pallets()->count() > 0) {
            // LTL: Paletten als Tracking-Items
            foreach ($shipment->pallets as $pallet) {
                $items[] = [
                    'packageReferenceInformation' => [
                        'sellerHandle' => $pallet->pallet_id,
                        'amazonHandle' => null,
                    ],
                ];
            }
        } else {
            // SPD: Kartons als Tracking-Items
            foreach ($shipment->cartons as $carton) {
                $items[] = [
                    'packageReferenceInformation' => [
                        'sellerHandle' => $carton->carton_id,
                        'amazonHandle' => null,
                    ],
                ];
            }
        }

        return $items;
    }

    // ── Plan stornieren ──────────────────────────────────────

    public function cancelPlan(FbaShipment $shipment): void
    {
        $token   = $this->auth->getAccessToken($shipment->amazonAccount);
        $headers = $this->headers($token);
        $planId  = $shipment->inbound_plan_id;

        if (!$planId) {
            throw new \RuntimeException('Kein Inbound Plan vorhanden zum Stornieren.');
        }

        $resp = Http::withHeaders($headers)
            ->delete("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}");

        $resp->throw();

        $operationId = $resp->json('operationId');

        if ($operationId) {
            $this->pollOperation($token, $operationId);
        }

        $shipment->update(['status' => 'cancelled']);
        $shipment->splits()->update(['status' => 'cancelled']);

        Log::info("FBA Inbound Plan storniert: {$planId}");
    }

    // ── Inbound Plans auflisten ─────────────────────────────

    public function listInboundPlans(string $marketplaceId, array $filters = []): array
    {
        $account = \App\Models\AmazonAccount::where('marketplace_id', $marketplaceId)->firstOrFail();
        $token   = $this->auth->getAccessToken($account);
        $headers = $this->headers($token);

        $params = array_merge([
            'marketplaceIds' => $marketplaceId,
        ], $filters);

        $resp = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans", $params);

        $resp->throw();

        return $resp->json('inboundPlans', []);
    }

    // ── Einzelnen Plan abrufen ──────────────────────────────

    public function getInboundPlan(string $planId): array
    {
        // Nimm das erste aktive Amazon-Konto
        $account = \App\Models\AmazonAccount::where('active', true)->firstOrFail();
        $token   = $this->auth->getAccessToken($account);
        $headers = $this->headers($token);

        $resp = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}");

        $resp->throw();

        return $resp->json();
    }

    // ── Shipment Items auflisten ────────────────────────────

    public function listShipmentItems(string $planId, string $shipmentId): array
    {
        $account = \App\Models\AmazonAccount::where('active', true)->firstOrFail();
        $token   = $this->auth->getAccessToken($account);
        $headers = $this->headers($token);

        $resp = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/shipments/{$shipmentId}/items");

        $resp->throw();

        return $resp->json('items', []);
    }

    // ── Shipment-Name aktualisieren ─────────────────────────

    public function updateShipmentName(string $planId, string $shipmentId, string $name): void
    {
        $account = \App\Models\AmazonAccount::where('active', true)->firstOrFail();
        $token   = $this->auth->getAccessToken($account);
        $headers = $this->headers($token);

        $resp = Http::withHeaders($headers)
            ->patch("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/shipments/{$shipmentId}", [
                'name' => $name,
            ]);

        $resp->throw();

        $operationId = $resp->json('operationId');
        if ($operationId) {
            $this->pollOperation($token, $operationId);
        }

        Log::info("Shipment-Name aktualisiert: {$shipmentId} -> {$name}");
    }

    // ── PCP Versand kaufen ──────────────────────────────────

    public function purchaseShipment(string $planId, string $shipmentId, string $transportationOptionId): array
    {
        $account = \App\Models\AmazonAccount::where('active', true)->firstOrFail();
        $token   = $this->auth->getAccessToken($account);
        $headers = $this->headers($token);

        $resp = Http::withHeaders($headers)
            ->post("{$this->baseUrl}/inbound/fba/2024-03-20/transportationOptions/shipmentPurchase", [
                'shipmentId'             => $shipmentId,
                'transportationOptionId' => $transportationOptionId,
                'inboundPlanId'          => $planId,
            ]);

        $resp->throw();

        $operationId = $resp->json('operationId');
        if ($operationId) {
            $this->pollOperation($token, $operationId);
        }

        Log::info("PCP Versand gekauft: {$shipmentId}");

        return $resp->json();
    }

    // ── PCP Kauf-Status ─────────────────────────────────────

    public function getShipmentPurchaseStatus(string $planId, string $shipmentId): array
    {
        $account = \App\Models\AmazonAccount::where('active', true)->firstOrFail();
        $token   = $this->auth->getAccessToken($account);
        $headers = $this->headers($token);

        $resp = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/inbound/fba/2024-03-20/transportationOptions/shipmentPurchase/{$shipmentId}");

        $resp->throw();

        return $resp->json();
    }

    // ── Prep Details auflisten ──────────────────────────────

    public function listPrepDetails(string $marketplaceId): array
    {
        $account = \App\Models\AmazonAccount::where('marketplace_id', $marketplaceId)->firstOrFail();
        $token   = $this->auth->getAccessToken($account);
        $headers = $this->headers($token);

        $resp = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/inbound/fba/2024-03-20/prepDetails", [
                'marketplaceIds' => $marketplaceId,
            ]);

        $resp->throw();

        return $resp->json('prepDetails', []);
    }

    // ── Paletten-Informationen (LTL) ────────────────────────

    public function submitPalletInfo(FbaShipment $shipment): void
    {
        $token   = $this->auth->getAccessToken($shipment->amazonAccount);
        $headers = $this->headers($token);
        $planId  = $shipment->inbound_plan_id;

        $pallets = $shipment->pallets()->get();

        if ($pallets->isEmpty()) {
            throw new \RuntimeException('Keine Paletten vorhanden.');
        }

        // Paletten-Inhalte senden
        $palletData = $pallets->map(function (FbaShipmentPallet $pallet) {
            $palletItem = [
                'palletId' => $pallet->pallet_id,
            ];

            if ($pallet->weight_value) {
                $palletItem['weight'] = [
                    'value' => $pallet->weight_value,
                    'unit'  => $pallet->weight_unit === 'KG' ? 'KILOGRAMS' : 'POUNDS',
                ];
            }

            if ($pallet->length && $pallet->width && $pallet->height) {
                $palletItem['dimensions'] = [
                    'length' => $pallet->length,
                    'width'  => $pallet->width,
                    'height' => $pallet->height,
                    'unit'   => $pallet->dimension_unit === 'CM' ? 'CENTIMETER' : 'INCHES',
                ];
            }

            $palletItem['isStackable'] = $pallet->is_stacked;

            // Zugehörige Kartons
            if ($pallet->carton_ids) {
                $palletItem['contents'] = collect($pallet->carton_ids)->map(fn($cartonId) => [
                    'cartonId' => $cartonId,
                ])->toArray();
            }

            return $palletItem;
        })->toArray();

        Http::withHeaders($headers)
            ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/pallets", [
                'palletList' => $palletData,
            ])->throw();

        $this->poll($token, $planId);
        Log::info("Pallet-Info für Plan {$planId} eingereicht: " . count($palletData) . " Paletten");
    }

    // ── Labels generieren ────────────────────────────────────

    public function generateLabels(FbaShipment $shipment): array
    {
        $token   = $this->auth->getAccessToken($shipment->amazonAccount);
        $headers = $this->headers($token);
        $planId  = $shipment->inbound_plan_id;

        $labelType = $shipment->packaging_type === 'ltl' ? 'PALLET' : 'ITEM';

        $results = [];

        foreach ($shipment->splits as $split) {
            $resp = Http::withHeaders($headers)
                ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/shipments/{$split->amazon_shipment_id}/labels", [
                    'labelType' => $labelType,
                ]);

            $resp->throw();

            $operationId = $resp->json('operationId');

            if ($operationId) {
                $this->pollOperation($token, $operationId);
            }

            // Labels abrufen
            $labelResp = Http::withHeaders($headers)
                ->get("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/shipments/{$split->amazon_shipment_id}/labels");

            $labelResp->throw();

            $results[] = [
                'shipment_id'   => $split->amazon_shipment_id,
                'operation_id'  => $operationId,
                'labels'        => $labelResp->json('labels', []),
            ];
        }

        $shipment->update(['status' => FbaShipment::STATUS_LABEL_READY]);
        Log::info("Labels generiert für Plan {$planId}");

        return $results;
    }

    // ── Delivery Window generieren ───────────────────────────

    private function generateDeliveryWindows(
        FbaShipment $shipment,
        string $token,
        string $planId,
        array $shipmentIds,
        array $headers
    ): void {
        Http::withHeaders($headers)
            ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/deliveryWindows", [
                'shipmentIds' => $shipmentIds,
            ])->throw();

        $this->poll($token, $planId);

        $deliveryWindows = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/deliveryWindows")
            ->throw()
            ->json('deliveryWindows');

        if (!empty($deliveryWindows)) {
            $bestWindow = collect($deliveryWindows)->first();
            $windowId = $bestWindow['deliveryWindowId'] ?? null;

            if ($windowId) {
                // Delivery Window bestätigen
                Http::withHeaders($headers)
                    ->post("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/deliveryWindows/confirmation", [
                        'deliveryWindowSelections' => [[
                            'deliveryWindowId' => $windowId,
                            'shipmentIds'      => $shipmentIds,
                        ]],
                    ])->throw();

                $this->poll($token, $planId);
                $shipment->update(['delivery_window_id' => $windowId]);
                Log::info("Delivery Window bestätigt: {$windowId}");
            }
        }
    }

    // ── Prep Details setzen ──────────────────────────────────

    public function setPrepDetails(FbaShipment $shipment): void
    {
        $token   = $this->auth->getAccessToken($shipment->amazonAccount);
        $headers = $this->headers($token);

        $prepData = $shipment->items->filter(fn($i) => $i->prep_category || $i->prep_instruction)->map(function ($item) {
            $detail = [
                'msku' => $item->sku,
            ];

            // prepCategory — PFLICHT
            $detail['prepCategory'] = $item->prep_category ?: 'NONE';

            // prepTypes — PFLICHT (Array)
            if ($item->prep_instruction) {
                $detail['prepTypes'] = [$item->prep_instruction];
            } else {
                $detail['prepTypes'] = ['ITEM_NO_PREP'];
            }

            return $detail;
        })->toArray();

        if (empty($prepData)) {
            return;
        }

        Http::withHeaders($headers)
            ->post("{$this->baseUrl}/inbound/fba/2024-03-20/prepDetails", [
                'marketplaceId' => $shipment->marketplace_id,
                'prepDetails'   => $prepData,
            ])->throw();

        Log::info("Prep Details gesetzt für Shipment {$shipment->internal_ref}");
    }

    // ── Shipment-Status abrufen ──────────────────────────────

    public function getShipmentStatus(FbaShipment $shipment): array
    {
        $token   = $this->auth->getAccessToken($shipment->amazonAccount);
        $headers = $this->headers($token);
        $planId  = $shipment->inbound_plan_id;

        $resp = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}");

        $resp->throw();

        $planData = $resp->json();

        // Splits aktualisieren
        if (!empty($planData['shipments'])) {
            foreach ($planData['shipments'] as $s) {
                FbaInboundSplit::where('fba_shipment_id', $shipment->id)
                    ->where('amazon_shipment_id', $s['shipmentId'])
                    ->update(['status' => $s['status'] ?? 'unknown']);
            }
        }

        // Status des Shipments aktualisieren
        $newStatus = match($planData['status'] ?? '') {
            'VOIDED'    => 'cancelled',
            'SHIPPED'   => FbaShipment::STATUS_SHIPPED,
            default     => null,
        };

        if ($newStatus) {
            $shipment->update(['status' => $newStatus]);
        }

        // Split-Status update
        foreach ($planData['shipments'] ?? [] as $s) {
            $splitStatus = match($s['status'] ?? '') {
                'RECEIVING'  => 'receiving',
                'CLOSED'     => 'closed',
                'SHIPPED'    => 'shipped',
                'WORKING'    => 'working',
                'READY_TO_SHIP' => 'ready_to_ship',
                default      => $s['status'] ?? 'unknown',
            };

            FbaInboundSplit::where('fba_shipment_id', $shipment->id)
                ->where('amazon_shipment_id', $s['shipmentId'])
                ->update(['status' => $splitStatus]);
        }

        return [
            'plan_status' => $planData['status'] ?? 'UNKNOWN',
            'shipments'   => $planData['shipments'] ?? [],
        ];
    }

    // ── FBA Inventory Summaries ──────────────────────────────

    public function getInventorySummaries(string $marketplaceId, string $granularityId): array
    {
        $token   = $this->auth->getAccessToken(
            \App\Models\AmazonAccount::where('marketplace_id', $marketplaceId)->firstOrFail()
        );

        $resp = Http::withHeaders($this->headers($token))
            ->get("{$this->baseUrl}/fba/inventory/v1/summaries", [
                'details'             => true,
                'granularityType'     => 'Marketplace',
                'granularityId'       => $granularityId,
                'marketplaceIds'      => $marketplaceId,
            ]);

        $resp->throw();

        return $resp->json('payload.inventorySummaries', []);
    }

    // ── Polling ──────────────────────────────────────────────

    private function poll(string $token, string $planId, ?int $maxSeconds = null): void
    {
        $maxSeconds = $maxSeconds ?? config('fba.poll_max_seconds', 90);
        $start = now();

        do {
            sleep(config('fba.poll_interval', 3));

            $op = Http::withHeaders($this->headers($token))
                ->get("{$this->baseUrl}/inbound/fba/2024-03-20/inboundPlans/{$planId}/operations/latest")
                ->throw()
                ->json();

            $status = $op['operationStatus'] ?? 'IN_PROGRESS';

            if ($status === 'FAILED') {
                throw new \RuntimeException(
                    'Amazon SP-API Fehler: ' . json_encode($op['operationProblems'] ?? [])
                );
            }

            if (now()->diffInSeconds($start) > $maxSeconds) {
                throw new \RuntimeException("SP-API Timeout nach {$maxSeconds}s — PlanId: {$planId}");
            }

        } while ($status !== 'SUCCESS');
    }

    private function pollOperation(string $token, string $operationId, ?int $maxSeconds = null): void
    {
        $maxSeconds = $maxSeconds ?? config('fba.poll_max_seconds', 60);
        $start = now();

        do {
            sleep(config('fba.poll_interval', 3));

            $op = Http::withHeaders($this->headers($token))
                ->get("{$this->baseUrl}/inbound/fba/2024-03-20/operations/{$operationId}")
                ->throw()
                ->json();

            $status = $op['operationStatus'] ?? 'IN_PROGRESS';

            if ($status === 'FAILED') {
                throw new \RuntimeException(
                    'Operation fehlgeschlagen: ' . json_encode($op['operationProblems'] ?? [])
                );
            }

            if (now()->diffInSeconds($start) > $maxSeconds) {
                throw new \RuntimeException("Operation Timeout nach {$maxSeconds}s — OpId: {$operationId}");
            }

        } while ($status !== 'SUCCESS');
    }

    private function headers(string $token): array
    {
        return [
            'x-amz-access-token' => $token,
            'Content-Type'       => 'application/json',
        ];
    }
}
