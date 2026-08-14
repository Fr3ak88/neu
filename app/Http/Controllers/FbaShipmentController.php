<?php

namespace App\Http\Controllers;

use App\Jobs\CreateInboundPlanJob;
use App\Jobs\RegisterFbaShipmentJob;
use App\Jobs\PollShipmentStatusJob;
use App\Models\AmazonAccount;
use App\Models\FbaShipment;
use App\Models\FbaShipmentItem;
use App\Models\FbaShipmentCarton;
use App\Models\FbaShipmentPallet;
use App\Services\FbaInboundServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class FbaShipmentController extends Controller
{
    public function __construct(
        private readonly FbaInboundServiceInterface $fbaService
    ) {}

    public function index()
    {
        $shipments = FbaShipment::with('amazonAccount')->latest()->get();

        $user = Auth::user();
        $jtlUmlagerungen = [];
        $jtlStatus = null;

        if ($user->jtl_host) {
            config([
                'database.connections.jtl.driver'                   => 'sqlsrv',
                'database.connections.jtl.host'                     => $user->jtl_host,
                'database.connections.jtl.port'                     => $user->jtl_port,
                'database.connections.jtl.database'                 => $user->jtl_database,
                'database.connections.jtl.username'                 => $user->jtl_username,
                'database.connections.jtl.password'                 => Crypt::decryptString($user->jtl_password),
                'database.connections.jtl.encrypt'                  => 'no',
                'database.connections.jtl.trust_server_certificate' => 'true',
            ]);

            try {
                $jtlUmlagerungen = DB::connection('jtl')->select("
                    SELECT
                        a.dErstellt AS Datum,
                        a.cAuftragsNr AS Umlagerung,
                        CASE
                            WHEN az.nStatus = 1 THEN 'offen'
                            WHEN az.nStatus = 3 THEN 'Abgeschlossen'
                            ELSE CAST(az.nStatus AS VARCHAR(50))
                        END AS Status
                    FROM verkauf.tauftrag AS a
                        JOIN tUmlagerung AS AZ ON AZ.kBestellung = a.kAuftrag
                        WHERE AZ.kZielLager = 2
                    ORDER BY a.dErstellt DESC
                ");
                $jtlStatus = 'connected';
            } catch (\Exception $e) {
                $jtlStatus = 'error: ' . $e->getMessage();
            }
        }

        $alle = collect();

        foreach ($shipments as $s) {
            $alle->push([
                'source'       => 'app',
                'ref'          => $s->internal_ref,
                'date'         => $s->created_at,
                'account'      => $s->amazonAccount->name ?? '—',
                'items'        => $s->items->count(),
                'units'        => $s->items->sum('quantity'),
                'status'       => $s->status,
                'status_label' => match($s->status) {
                    FbaShipment::STATUS_DRAFT         => 'Entwurf',
                    FbaShipment::STATUS_PLAN_CREATING => 'Plan wird erstellt',
                    FbaShipment::STATUS_PLAN_READY    => 'Plan bereit',
                    FbaShipment::STATUS_REGISTERED    => 'Angemeldet',
                    FbaShipment::STATUS_LABEL_READY   => 'Etikett bereit',
                    FbaShipment::STATUS_SHIPPED       => 'Versendet',
                    FbaShipment::STATUS_COMPLETED     => 'Abgeschlossen',
                    FbaShipment::STATUS_ERROR         => 'Fehler',
                    'cancelled'                       => 'Storniert',
                    default => $s->status,
                },
                'id' => $s->id,
            ]);
        }

        $importedJtlRefs = FbaShipment::whereNotNull('jtl_ref')
            ->pluck('jtl_ref')
            ->toArray();

        foreach ($jtlUmlagerungen as $j) {
            if (in_array($j->Umlagerung, $importedJtlRefs)) {
                continue;
            }

            $alle->push([
                'source'       => 'jtl',
                'ref'          => $j->Umlagerung,
                'date'         => \Carbon\Carbon::parse($j->Datum),
                'account'      => 'JTL-Wawi',
                'items'        => null,
                'units'        => null,
                'status'       => $j->Status === 'Abgeschlossen' ? 'completed' : 'open',
                'status_label' => $j->Status,
                'id'           => null,
            ]);
        }

        $alle = $alle->sortByDesc('date')->values();

        return view('fba-shipments.index', compact('alle', 'jtlStatus', 'importedJtlRefs'));
    }

    public function create()
    {
        $accounts = AmazonAccount::where('active', true)->get();
        return view('fba-shipments.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amazon_account_id'  => 'nullable|exists:amazon_accounts,id',
            'source_warehouse'   => 'required|string',
            'marketplace_id'     => 'required|string',
            'ship_from_phone'    => 'required|string',
            'ship_from_name'     => 'nullable|string',
            'ship_from_address'  => 'nullable|string',
            'ship_from_city'     => 'nullable|string',
            'ship_from_zip'      => 'nullable|string',
            'ship_from_country'  => 'nullable|string|max:2',
            'packaging_type'     => 'required|in:small_parcel,ltl',
            'items'              => 'required|array|min:1',
            'items.*.sku'        => 'required|string',
            'items.*.name'       => 'required|string',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.prep_instruction' => 'nullable|string',
            'items.*.prep_category'    => 'nullable|string',
            'items.*.prep_owner'       => 'nullable|string',
            'items.*.label_owner'      => 'nullable|string',
        ]);

        $user = $request->user();

        $shipment = DB::transaction(function () use ($validated, $user) {
            $shipment = FbaShipment::create([
                'amazon_account_id' => $validated['amazon_account_id'],
                'source_warehouse'  => $validated['source_warehouse'],
                'marketplace_id'    => $validated['marketplace_id'],
                'ship_from_phone'   => $validated['ship_from_phone'],
                'ship_from_name'    => $validated['ship_from_name'] ?? $user->name,
                'ship_from_address' => $validated['ship_from_address'] ?? '',
                'ship_from_city'    => $validated['ship_from_city'] ?? '',
                'ship_from_zip'     => $validated['ship_from_zip'] ?? '',
                'ship_from_country' => $validated['ship_from_country'] ?? 'DE',
                'packaging_type'    => $validated['packaging_type'],
                'status'            => FbaShipment::STATUS_DRAFT,
            ]);

            foreach ($validated['items'] as $item) {
                $shipment->items()->create([
                    'sku'              => $item['sku'],
                    'name'             => $item['name'],
                    'quantity'         => $item['quantity'],
                    'prep_instruction' => $item['prep_instruction'] ?? null,
                    'prep_category'    => $item['prep_category'] ?? null,
                    'prep_owner'       => $item['prep_owner'] ?? null,
                    'label_owner'      => $item['label_owner'] ?? null,
                ]);
            }

            return $shipment;
        });

        return redirect()
            ->route('fba-shipments.show', $shipment)
            ->with('success', 'Umlagerung angelegt: ' . $shipment->internal_ref);
    }

    public function show(FbaShipment $fbaShipment)
    {
        $fbaShipment->load('items', 'splits', 'amazonAccount', 'cartons', 'pallets');
        return view('fba-shipments.show', ['shipment' => $fbaShipment]);
    }

    // ── Plan-Actions ─────────────────────────────────────────

    public function update(Request $request, FbaShipment $fbaShipment)
    {
        abort_unless($fbaShipment->isEditable(), 422, 'Umlagerung kann nicht mehr bearbeitet werden.');

        $validated = $request->validate([
            'ship_from_name'     => 'required|string|max:255',
            'ship_from_address'  => 'required|string|max:255',
            'ship_from_city'     => 'required|string|max:255',
            'ship_from_zip'      => 'required|string|max:20',
            'ship_from_country'  => 'required|string|size:2',
            'ship_from_phone'    => 'required|string|max:50',
        ]);

        $fbaShipment->update($validated);

        return redirect()
            ->route('fba-shipments.show', $fbaShipment)
            ->with('success', 'Versandadresse aktualisiert.');
    }

    public function createPlan(FbaShipment $fbaShipment)
    {
        abort_if(! $fbaShipment->isDraft(), 422, 'Plan kann nur im Status Entwurf erstellt werden.');

        CreateInboundPlanJob::dispatch($fbaShipment);

        return redirect()
            ->route('fba-shipments.show', $fbaShipment)
            ->with('success', 'Anlieferungsplan wird erstellt…');
    }

    public function register(FbaShipment $fbaShipment)
    {
        abort_if(! $fbaShipment->isPlanReady(), 422, 'Nur Status "Plan bereit" kann angemeldet werden.');

        RegisterFbaShipmentJob::dispatch($fbaShipment);

        return redirect()
            ->route('fba-shipments.show', $fbaShipment)
            ->with('success', 'Registrierung bei Amazon gestartet…');
    }

    public function cancel(FbaShipment $fbaShipment)
    {
        abort_if(! in_array($fbaShipment->status, [
            FbaShipment::STATUS_PLAN_READY,
            FbaShipment::STATUS_PLAN_CREATING,
        ]), 422, 'Nur aktive Pläne können storniert werden.');

        $service = app(FbaInboundServiceInterface::class);

        try {
            $service->cancelPlan($fbaShipment);
            return redirect()
                ->route('fba-shipments.show', $fbaShipment)
                ->with('success', 'Anlieferungsplan storniert.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('fba-shipments.show', $fbaShipment)
                ->with('error', 'Storno fehlgeschlagen: ' . $e->getMessage());
        }
    }

    // ── Carton-Handling ──────────────────────────────────────

    public function storeCarton(Request $request, FbaShipment $fbaShipment)
    {
        abort_if(! $fbaShipment->isDraft() && ! $fbaShipment->isPlanReady(), 422,
            'Kartons können nur im Status Entwurf oder Plan bereit hinzugefügt werden.');

        $validated = $request->validate([
            'carton_id'    => 'required|string',
            'weight_value' => 'nullable|numeric|min:0',
            'weight_unit'  => 'nullable|in:KG,LB',
            'length'       => 'nullable|numeric|min:0',
            'width'        => 'nullable|numeric|min:0',
            'height'       => 'nullable|numeric|min:0',
            'dimension_unit' => 'nullable|in:CM,INCH',
            'contents_json'  => 'required|string',
        ]);

        $contents = json_decode($validated['contents_json'], true);
        if (!is_array($contents)) {
            return redirect()->route('fba-shipments.show', $fbaShipment)
                ->with('error', 'Inhalte müssen gültiges JSON sein.');
        }

        FbaShipmentCarton::updateOrCreate(
            ['fba_shipment_id' => $fbaShipment->id, 'carton_id' => $validated['carton_id']],
            [
                'weight_value'    => $validated['weight_value'] ?? null,
                'weight_unit'     => $validated['weight_unit'] ?? 'KG',
                'length'          => $validated['length'] ?? null,
                'width'           => $validated['width'] ?? null,
                'height'          => $validated['height'] ?? null,
                'dimension_unit'  => $validated['dimension_unit'] ?? 'CM',
                'contents'        => $contents,
            ]
        );

        return redirect()
            ->route('fba-shipments.show', $fbaShipment)
            ->with('success', 'Karton ' . $validated['carton_id'] . ' gespeichert.');
    }

    public function destroyCarton(FbaShipment $fbaShipment, FbaShipmentCarton $carton)
    {
        abort_if($carton->fba_shipment_id !== $fbaShipment->id, 404);

        $carton->delete();

        return redirect()
            ->route('fba-shipments.show', $fbaShipment)
            ->with('success', 'Karton gelöscht.');
    }

    // ── Pallet-Handling ──────────────────────────────────────

    public function storePallet(Request $request, FbaShipment $fbaShipment)
    {
        abort_if(! $fbaShipment->isDraft() && ! $fbaShipment->isPlanReady(), 422,
            'Paletten können nur im Status Entwurf oder Plan bereit hinzugefügt werden.');

        $validated = $request->validate([
            'pallet_id'    => 'required|string',
            'weight_value' => 'nullable|numeric|min:0',
            'weight_unit'  => 'nullable|in:KG,LB',
            'length'       => 'nullable|numeric|min:0',
            'width'        => 'nullable|numeric|min:0',
            'height'       => 'nullable|numeric|min:0',
            'dimension_unit' => 'nullable|in:CM,INCH',
            'is_stacked'   => 'nullable|boolean',
            'carton_ids'   => 'nullable|string',
        ]);

        $cartonIds = null;
        if (!empty($validated['carton_ids'])) {
            $cartonIds = json_decode($validated['carton_ids'], true);
        }

        FbaShipmentPallet::updateOrCreate(
            ['fba_shipment_id' => $fbaShipment->id, 'pallet_id' => $validated['pallet_id']],
            [
                'weight_value'    => $validated['weight_value'] ?? null,
                'weight_unit'     => $validated['weight_unit'] ?? 'KG',
                'length'          => $validated['length'] ?? null,
                'width'           => $validated['width'] ?? null,
                'height'          => $validated['height'] ?? null,
                'dimension_unit'  => $validated['dimension_unit'] ?? 'CM',
                'is_stacked'      => $validated['is_stacked'] ?? false,
                'carton_ids'      => $cartonIds,
            ]
        );

        return redirect()
            ->route('fba-shipments.show', $fbaShipment)
            ->with('success', 'Palette ' . $validated['pallet_id'] . ' gespeichert.');
    }

    public function destroyPallet(FbaShipment $fbaShipment, FbaShipmentPallet $pallet)
    {
        abort_if($pallet->fba_shipment_id !== $fbaShipment->id, 404);

        $pallet->delete();

        return redirect()
            ->route('fba-shipments.show', $fbaShipment)
            ->with('success', 'Palette gelöscht.');
    }

    // ── Labels ───────────────────────────────────────────────

    public function labels(FbaShipment $fbaShipment)
    {
        abort_if(! in_array($fbaShipment->status, ['plan_ready', 'registered', 'label_ready']), 422,
            'Labels können erst nach Plan-Erstellung generiert werden.');

        $service = app(FbaInboundServiceInterface::class);

        try {
            $results = $service->generateLabels($fbaShipment);

            // Labels als HTML-Übersicht anzeigen
            return view('fba-shipments.labels', [
                'shipment' => $fbaShipment,
                'labels'   => $results,
            ]);
        } catch (\Throwable $e) {
            return redirect()->route('fba-shipments.show', $fbaShipment)
                ->with('error', 'Labels fehlgeschlagen: ' . $e->getMessage());
        }
    }

    // ── Status Polling ───────────────────────────────────────

    public function checkStatus(FbaShipment $fbaShipment)
    {
        PollShipmentStatusJob::dispatch($fbaShipment);

        return redirect()
            ->route('fba-shipments.show', $fbaShipment)
            ->with('success', 'Status-Abfrage gestartet…');
    }

    public function retry(FbaShipment $fbaShipment)
    {
        $fbaShipment->update([
            'error_message' => null,
            'status'        => FbaShipment::STATUS_DRAFT,
        ]);

        CreateInboundPlanJob::dispatch($fbaShipment);

        return redirect()
            ->route('fba-shipments.show', $fbaShipment)
            ->with('success', 'Vorgang wird erneut versucht…');
    }

    public function updateAccount(Request $request, FbaShipment $fbaShipment)
    {
        $request->validate([
            'amazon_account_id' => 'required|exists:amazon_accounts,id',
        ]);

        $fbaShipment->update(['amazon_account_id' => $request->amazon_account_id]);

        return redirect()
            ->route('fba-shipments.show', $fbaShipment)
            ->with('success', 'Amazon-Konto zugewiesen. Du kannst jetzt den Plan erstellen.');
    }

    // ── Plan-Listing & Details ──────────────────────────────

    public function listPlans(Request $request)
    {
        $marketplaceId = $request->query('marketplace_id', 'A1PA6795UKMFR9');
        $filters       = $request->only(['createdAfter', 'createdBefore', 'status']);

        try {
            $plans = $this->fbaService->listInboundPlans($marketplaceId, $filters);
        } catch (\Throwable $e) {
            $plans = [];
        }

        return view('fba-shipments.plans', [
            'plans'        => $plans,
            'marketplaceId' => $marketplaceId,
        ]);
    }

    public function planDetail(string $planId)
    {
        $plan = $this->fbaService->getInboundPlan($planId);

        return view('fba-shipments.plan-detail', [
            'plan' => $plan,
        ]);
    }

    // ── Shipment-Name ändern ────────────────────────────────

    public function updateShipmentName(Request $request, FbaShipment $fbaShipment)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            if ($fbaShipment->inbound_plan_id) {
                $this->fbaService->updateShipmentName(
                    $fbaShipment->inbound_plan_id,
                    $fbaShipment->shipment_ids[0] ?? '',
                    $validated['name']
                );
            }

            return redirect()
                ->route('fba-shipments.show', $fbaShipment)
                ->with('success', 'Shipment-Name aktualisiert: ' . $validated['name']);
        } catch (\Throwable $e) {
            return redirect()
                ->route('fba-shipments.show', $fbaShipment)
                ->with('error', 'Name konnte nicht aktualisiert werden: ' . $e->getMessage());
        }
    }

    // ── Shipment Items von Amazon ────────────────────────────

    public function amazonItems(Request $request, FbaShipment $fbaShipment)
    {
        $shipmentId = $request->query('shipment_id');

        if (!$fbaShipment->inbound_plan_id || !$shipmentId) {
            return redirect()
                ->route('fba-shipments.show', $fbaShipment)
                ->with('error', 'Plan-ID oder Shipment-ID fehlt.');
        }

        $items = $this->fbaService->listShipmentItems($fbaShipment->inbound_plan_id, $shipmentId);

        return view('fba-shipments.amazon-items', [
            'shipment' => $fbaShipment,
            'items'    => $items,
        ]);
    }

    // ── PCP Versand kaufen ──────────────────────────────────

    public function purchaseShipment(Request $request, FbaShipment $fbaShipment)
    {
        $validated = $request->validate([
            'transportation_option_id' => 'required|string',
            'shipment_id'              => 'required|string',
        ]);

        try {
            $result = $this->fbaService->purchaseShipment(
                $fbaShipment->inbound_plan_id,
                $validated['shipment_id'],
                $validated['transportation_option_id']
            );

            return redirect()
                ->route('fba-shipments.show', $fbaShipment)
                ->with('success', 'PCP-Versand erfolgreich gekauft.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('fba-shipments.show', $fbaShipment)
                ->with('error', 'PCP-Versand fehlgeschlagen: ' . $e->getMessage());
        }
    }

    // ── Item-Handling ────────────────────────────────────────

    public function updateItem(Request $request, FbaShipment $fbaShipment, FbaShipmentItem $fbaShipmentItem)
    {
        abort_if($fbaShipmentItem->fba_shipment_id !== $fbaShipment->id, 404);

        if ($fbaShipment->isRegisteredOrLater()) {
            abort(422, 'Artikel können nach der Anmeldung nicht mehr geändert werden.');
        }

        $validated = $request->validate([
            'quantity'         => 'required|integer|min:1',
            'prep_instruction' => 'nullable|string',
            'prep_category'    => 'nullable|string',
            'prep_owner'       => 'nullable|string',
            'label_owner'      => 'nullable|string',
        ]);

        $fbaShipmentItem->update($validated);

        return redirect()
            ->route('fba-shipments.show', $fbaShipment)
            ->with('success', 'Artikel aktualisiert.');
    }

    public function bulkUpdateItems(Request $request, FbaShipment $fbaShipment)
    {
        abort_if($fbaShipment->isRegisteredOrLater(), 422, 'Artikel können nach der Anmeldung nicht mehr geändert werden.');

        $items = json_decode($request->input('items_json'), true);

        if (!is_array($items)) {
            return redirect()
                ->route('fba-shipments.show', $fbaShipment)
                ->with('error', 'Keine Daten erhalten.');
        }

        foreach ($items as $itemData) {
            if (isset($itemData['id'])) {
                FbaShipmentItem::where('id', $itemData['id'])
                    ->where('fba_shipment_id', $fbaShipment->id)
                    ->update(array_filter([
                        'quantity'         => $itemData['quantity'] ?? null,
                        'prep_instruction' => $itemData['prep_instruction'] ?? null,
                        'prep_category'    => $itemData['prep_category'] ?? null,
                        'prep_owner'       => $itemData['prep_owner'] ?? null,
                        'label_owner'      => $itemData['label_owner'] ?? null,
                    ], fn($v) => $v !== null));
            }
        }

        return redirect()
            ->route('fba-shipments.show', $fbaShipment)
            ->with('success', 'Artikel aktualisiert.');
    }

    public function destroy(FbaShipment $fbaShipment)
    {
        $fbaShipment->delete();
        return redirect()->route('fba-shipments.index')->with('success', 'Umlagerung gelöscht.');
    }

    // ── JTL-Import ───────────────────────────────────────────

    public function importJtl(Request $request)
    {
        $request->validate([
            'jtl_ref'   => 'required|string',
            'jtl_datum' => 'required|date',
        ]);

        $user = Auth::user();

        if (!$user->jtl_host) {
            return redirect()->route('fba-shipments.index')
                ->with('error', 'JTL-Wawi nicht verbunden.');
        }

        $exists = FbaShipment::where('jtl_ref', $request->jtl_ref)->exists();
        if ($exists) {
            return redirect()->route('fba-shipments.index')
                ->with('error', 'Umlagerung ' . $request->jtl_ref . ' wurde bereits importiert.');
        }

        $defaultAccount = AmazonAccount::where('active', true)->first();

        config([
            'database.connections.jtl.driver'                   => 'sqlsrv',
            'database.connections.jtl.host'                     => $user->jtl_host,
            'database.connections.jtl.port'                     => $user->jtl_port,
            'database.connections.jtl.database'                 => $user->jtl_database,
            'database.connections.jtl.username'                 => $user->jtl_username,
            'database.connections.jtl.password'                 => Crypt::decryptString($user->jtl_password),
            'database.connections.jtl.encrypt'                  => 'no',
            'database.connections.jtl.trust_server_certificate' => 'true',
        ]);

        try {
            $order = DB::connection('jtl')->select("
                SELECT TOP 1
                    a.kAuftrag,
                    a.cAuftragsNr,
                    a.dErstellt
                FROM verkauf.tauftrag AS a
                WHERE a.cAuftragsNr = ?
            ", [$request->jtl_ref]);

            if (empty($order)) {
                return redirect()->route('fba-shipments.index')
                    ->with('error', 'JTL-Bestellung ' . $request->jtl_ref . ' nicht gefunden.');
            }

            $positions = DB::connection('jtl')->select("
                SELECT
                    AM.csellersku AS sku,
                    AP.cName AS name,
                    CAST(SUM(AP.fAnzahl) AS INT) AS quantity
                FROM Verkauf.tauftrag AS A
                JOIN Verkauf.tAuftragPosition AS AP ON A.kAuftrag = AP.kAuftrag
                JOIN tUmlagerungPos AS AM ON AP.kAuftragPosition = AM.kAuftragPosition
                WHERE A.cAuftragsNr = ?
                GROUP BY AM.csellersku, AP.cName, AP.fAnzahl
                ORDER BY AM.csellersku
            ", [$request->jtl_ref]);

            if (empty($positions)) {
                return redirect()->route('fba-shipments.index')
                    ->with('error', 'Keine Artikelpositionen für Bestellung ' . $request->jtl_ref . ' gefunden.');
            }

            // Adresse aus JTL lesen (Lager-Adresse des Quell-Lagers)
            $address = DB::connection('jtl')->select("
                SELECT TOP 1
                    w.cAnsprechpartnerVorname AS firstname,
                    w.cAnsprechpartnerName    AS lastname,
                    w.cStrasse                AS street,
                    w.cPLZ                    AS zip,
                    w.cOrt                    AS city,
                    w.cAnsprechpartnerTel     AS phone,
                    w.cEmpfaengerFirma        AS company
                FROM Verkauf.tAuftragAdresse a
                JOIN tUmlagerung AS u ON a.kAuftrag = u.kBestellung
                JOIN tWarenLager AS w ON w.kWarenLager = u.kQuellLager
                JOIN Verkauf.tAuftrag AS aa ON aa.kAuftrag = a.kAuftrag
                WHERE aa.cAuftragsNr = ?
            ", [$request->jtl_ref]);

            $addr = $address[0] ?? null;
            $phone = $addr?->phone ?? '';
            $street = $addr?->street ?? '';
            $city = $addr?->city ?? '';
            $zip = $addr?->zip ?? '';
            $country = 'DE';
            $name = trim(($addr?->company ?? '') . ' ' . ($addr?->firstname ?? '') . ' ' . ($addr?->lastname ?? ''));
            $name = $name ?: $user->name;

            $shipment = \DB::transaction(function () use ($request, $positions, $defaultAccount, $name, $street, $city, $zip, $country, $phone) {
                $shipment = FbaShipment::create([
                    'amazon_account_id' => $defaultAccount?->id,
                    'source_warehouse'  => 'JTL-Wawi',
                    'marketplace_id'    => 'A1PA6795UKMFR9',
                    'internal_ref'      => $request->jtl_ref,
                    'jtl_ref'           => $request->jtl_ref,
                    'jtl_datum'         => $request->jtl_datum,
                    'packaging_type'    => 'small_parcel',
                    'status'            => FbaShipment::STATUS_DRAFT,
                    'ship_from_name'    => $name,
                    'ship_from_address' => $street,
                    'ship_from_city'    => $city,
                    'ship_from_zip'     => $zip,
                    'ship_from_country' => $country,
                    'ship_from_phone'   => $phone,
                ]);

                foreach ($positions as $pos) {
                    $shipment->items()->create([
                        'sku'      => $pos->sku,
                        'name'     => $pos->name,
                        'quantity' => $pos->quantity,
                    ]);
                }

                return $shipment;
            });

            // Amazon-InboundPlan nur erstellen wenn Konto vorhanden
            if ($shipment->amazon_account_id) {
                CreateInboundPlanJob::dispatch($shipment);
                $msg = 'JTL-Umlagerung ' . $request->jtl_ref . ' importiert. Amazon-Plan wird erstellt…';
            } else {
                $msg = 'JTL-Umlagerung ' . $request->jtl_ref . ' als Entwurf importiert. Konto zuweisen um Plan zu erstellen.';
            }

            return redirect()
                ->route('fba-shipments.show', $shipment)
                ->with('success', $msg);

        } catch (\Exception $e) {
            return redirect()->route('fba-shipments.index')
                ->with('error', 'JTL-Fehler: ' . $e->getMessage());
        }
    }
}
