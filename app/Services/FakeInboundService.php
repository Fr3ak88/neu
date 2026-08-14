<?php

namespace App\Services;

use App\Models\FbaShipment;
use App\Models\FbaInboundSplit;
use Illuminate\Support\Str;

class FakeInboundService implements FbaInboundServiceInterface
{
    public function createInboundPlan(FbaShipment $shipment): void
    {
        $shipment->update(['status' => FbaShipment::STATUS_PLAN_CREATING]);
        sleep(1);

        $planId = 'FAKE-' . strtoupper(Str::random(12));
        $placementId = 'PL-' . strtoupper(Str::random(8));
        $transportId = 'TR-' . strtoupper(Str::random(8));

        // Fake Splits
        $fcs = [
            ['id' => 'DEA4', 'city' => 'Winsen (Luhe)'],
            ['id' => 'DUS2', 'city' => 'Duisburg'],
        ];

        $shipmentIds = [];
        foreach ($fcs as $fc) {
            $splitId = 'FBA' . strtoupper(Str::random(9));
            $shipmentIds[] = $splitId;

            FbaInboundSplit::create([
                'fba_shipment_id'       => $shipment->id,
                'amazon_shipment_id'    => $splitId,
                'fulfillment_center_id' => $fc['id'],
                'destination_address'   => $fc['city'],
                'status'                => 'working',
            ]);
        }

        $shipment->update([
            'inbound_plan_id'           => $planId,
            'placement_option_id'       => $placementId,
            'transportation_option_id'  => $transportId,
            'shipment_ids'              => $shipmentIds,
            'status'                    => FbaShipment::STATUS_PLAN_READY,
        ]);
    }

    public function register(FbaShipment $shipment): void
    {
        sleep(1);

        foreach ($shipment->splits as $split) {
            $split->update(['status' => 'registered']);
        }

        $shipment->update(['status' => FbaShipment::STATUS_REGISTERED]);
    }

    public function cancelPlan(FbaShipment $shipment): void
    {
        sleep(1);
        $shipment->update(['status' => 'cancelled']);
        $shipment->splits()->update(['status' => 'cancelled']);
    }

    public function submitCartonContent(FbaShipment $shipment): void
    {
        sleep(1);
    }

    public function submitPalletInfo(FbaShipment $shipment): void
    {
        sleep(1);
    }

    public function generateLabels(FbaShipment $shipment): array
    {
        sleep(1);
        $shipment->update(['status' => FbaShipment::STATUS_LABEL_READY]);

        return $shipment->splits->map(fn($split) => [
            'shipment_id'  => $split->amazon_shipment_id,
            'operation_id' => 'FAKE-OP-' . strtoupper(Str::random(8)),
            'labels'       => [],
        ])->toArray();
    }

    public function setPrepDetails(FbaShipment $shipment): void
    {
        sleep(1);
    }

    public function listPrepDetails(string $marketplaceId): array
    {
        return [];
    }

    public function listInboundPlans(string $marketplaceId, array $filters = []): array
    {
        return [];
    }

    public function getInboundPlan(string $planId): array
    {
        return [
            'inboundPlanId' => $planId,
            'status'        => 'ACTIVE',
            'shipments'     => [],
        ];
    }

    public function listShipmentItems(string $planId, string $shipmentId): array
    {
        return [];
    }

    public function updateShipmentName(string $planId, string $shipmentId, string $name): void
    {
        sleep(1);
    }

    public function purchaseShipment(string $planId, string $shipmentId, string $transportationOptionId): array
    {
        sleep(1);
        return ['status' => 'confirmed'];
    }

    public function getShipmentPurchaseStatus(string $planId, string $shipmentId): array
    {
        return ['status' => 'confirmed'];
    }

    public function getShipmentStatus(FbaShipment $shipment): array
    {
        return [
            'plan_status' => 'ACTIVE',
            'shipments'   => $shipment->splits->map(fn($s) => [
                'shipmentId' => $s->amazon_shipment_id,
                'status'     => $s->status,
            ])->toArray(),
        ];
    }

    public function getInventorySummaries(string $marketplaceId, string $granularityId): array
    {
        return [
            [
                'asin'          => 'B0EXAMPLE1',
                'fnSku'         => 'X00EXAMPLE1',
                'sellerSku'     => 'SKU-EXAMPLE-1',
                'productName'   => 'Beispielprodukt 1',
                'totalQuantity' => 150,
                'fulfillableQuantity' => 120,
            ],
            [
                'asin'          => 'B0EXAMPLE2',
                'fnSku'         => 'X00EXAMPLE2',
                'sellerSku'     => 'SKU-EXAMPLE-2',
                'productName'   => 'Beispielprodukt 2',
                'totalQuantity' => 75,
                'fulfillableQuantity' => 60,
            ],
        ];
    }
}
