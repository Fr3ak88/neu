<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Wms\Order;
use App\Models\Wms\Shipment;
use App\Models\Wms\SyncLog;
use App\Services\JtlWawiApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;

class WmsShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Shipment::with('order');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('storlogix_id', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($q2) use ($search) {
                      $q2->where('order_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $shipments = $query->latest()->paginate(25)->withQueryString();

        return view('wms.shipments.index', compact('shipments'));
    }

    public function show(Shipment $shipment)
    {
        $shipment->load('order.items');
        return view('wms.shipments.show', compact('shipment'));
    }

    public function edit(Shipment $shipment)
    {
        $shipment->load('order');
        return view('wms.shipments.edit', compact('shipment'));
    }

    public function update(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'storlogix_id'    => 'nullable|string|max:100',
            'carrier'         => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:255',
            'package_count'   => 'nullable|integer|min:1',
            'weight'          => 'nullable|numeric|min:0',
            'status'          => 'required|string|in:pending,created,shipped,delivered,error',
            'shipped_at'      => 'nullable|date',
        ]);

        $shipment->update($validated);

        if ($validated['status'] === Shipment::STATUS_SHIPPED) {
            $shipment->order?->update(['status' => Order::STATUS_SHIPPED]);
            $this->pushOrderStatusToJtl($shipment->order, 'shipped');
        }
        if ($validated['status'] === Shipment::STATUS_DELIVERED) {
            $shipment->order?->update(['status' => Order::STATUS_COMPLETED]);
            $this->pushOrderStatusToJtl($shipment->order, 'completed');
        }

        return redirect()->route('wms.shipments.show', $shipment)
            ->with('success', 'Versandauftrag aktualisiert.');
    }

    private function pushOrderStatusToJtl(?Order $order, string $status): void
    {
        if (!$order || !$order->jtl_order_id) {
            return;
        }

        try {
            $tenant = Tenant::first();
            if (!$tenant) {
                return;
            }

            $jtl = new JtlWawiApiService($tenant);
            if (!$jtl->isConfigured() || !$jtl->isAuthenticated()) {
                return;
            }

            $jtl->updateOrderStatus($order->jtl_order_id, $status);

            SyncLog::create([
                'direction' => 'out',
                'type'      => 'order',
                'entity_id' => (string) $order->id,
                'status'    => 'success',
                'message'   => "Bestellstatus '{$status}' an JTL-Wawi gesendet (manuell)",
            ]);
        } catch (\Exception $e) {
            Log::error('JTL order status push failed (manual)', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            SyncLog::create([
                'direction' => 'out',
                'type'      => 'order',
                'entity_id' => (string) $order->id,
                'status'    => 'error',
                'message'   => 'JTL Status-Update fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }
    }

    public function destroy(Shipment $shipment)
    {
        $shipment->delete();
        return redirect()->route('wms.shipments.index')
            ->with('success', 'Versandauftrag gelöscht.');
    }
}
