<?php

namespace App\Http\Controllers;

use App\Models\Wms\Shipment;
use App\Models\Wms\Order;
use App\Models\Wms\Parcel;
use App\Models\Wms\ReturnRecord;
use App\Models\Wms\StockMovement;
use App\Models\Wms\Product;
use App\Models\Wms\SyncLog;
use App\Models\Tenant;
use App\Services\StorlogixService;
use App\Services\JtlWawiApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function storlogix(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Storlogix-Signature', '');

        $tenantId = $request->header('X-Tenant-Id');
        if (!$tenantId) {
            Log::warning('Storelogix webhook: missing tenant header');
            return response()->json(['error' => 'Missing tenant'], 400);
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            Log::warning('Storelogix webhook: unknown tenant', ['tenant_id' => $tenantId]);
            return response()->json(['error' => 'Unknown tenant'], 400);
        }

        if ($tenant->storlogix_api_secret) {
            try {
                $service = new StorlogixService($tenant);
                if (!$service->verifyWebhookSignature($payload, $signature, decrypt($tenant->storlogix_api_secret))) {
                    Log::warning('Storelogix webhook: invalid signature', ['tenant_id' => $tenantId]);
                    return response()->json(['error' => 'Invalid signature'], 403);
                }
            } catch (\Exception $e) {
                Log::error('Storelogix webhook: signature verification failed', ['error' => $e->getMessage()]);
            }
        }

        $data = $request->json()->all();

        if (empty($data)) {
            Log::warning('Storelogix webhook: empty payload', ['tenant_id' => $tenantId]);
            return response()->json(['error' => 'Empty payload'], 400);
        }

        // Support both wrapped and flat JSON structures
        $payload = $data['deliveryorderresponse']
            ?? $data['deliveryorderclose']
            ?? $data['goodsreceiptresponse']
            ?? $data['returnreceiptresponse']
            ?? $data['inventorresponse']
            ?? $data['purchaseorderresponse']
            ?? $data;

        $eventType = $this->detectEventType($payload);

        SyncLog::create([
            'direction' => 'in',
            'type'      => $eventType,
            'entity_id' => $payload['ordernumber'] ?? $payload['purchasenumber'] ?? $payload['receiptnumber'] ?? $payload['retourenumber'] ?? 'unknown',
            'status'    => 'success',
            'message'   => "Webhook empfangen: {$eventType}",
            'payload'   => $data,
        ]);

        match ($eventType) {
            'order.status'         => $this->handleOrderStatusUpdate($tenant, $payload),
            'order.completed'      => $this->handleOrderCompleted($tenant, $payload),
            'order.cancelled'      => $this->handleOrderCancelled($tenant, $payload),
            'goods.receipt'        => $this->handleGoodsReceipt($tenant, $payload),
            'return.received'      => $this->handleReturnReceived($tenant, $payload),
            'return.status_change' => $this->handleReturnStatusChange($tenant, $payload),
            'stock.change'         => $this->handleStockChange($tenant, $payload),
            default                => null,
        };

        Log::info('Storelogix webhook processed', [
            'tenant_id' => $tenant->id,
            'event'     => $eventType,
        ]);

        return response()->json(['success' => true]);
    }

    private function detectEventType(array $payload): string
    {
        // DeliveryOrderClose has items and parcels → order completed
        if (isset($payload['items']) && isset($payload['parcels'])) {
            return 'order.completed';
        }

        // DeliveryOrderResponse with cancellationreason
        if (isset($payload['cancellationreason']) && !empty($payload['cancellationreason'])) {
            return 'order.cancelled';
        }

        // DeliveryOrderResponse with orderstatus
        if (isset($payload['orderstatus']) && isset($payload['ordernumber'])) {
            return 'order.status';
        }

        // GoodsReceiptResponse
        if (isset($payload['receiptnumber']) || isset($payload['goodsreceiptresponse'])) {
            return 'goods.receipt';
        }

        // ReturnReceiptResponse with changes
        if (isset($payload['changes']) || isset($payload['itemreturnstatus'])) {
            return 'return.status_change';
        }

        // ReturnReceiptResponse
        if (isset($payload['retourenumber']) || isset($payload['returnreceiptresponse'])) {
            return 'return.received';
        }

        // InventorResponse
        if (isset($payload['inventorchanges']) || isset($payload['inventorchange'])) {
            return 'stock.change';
        }

        // PurchaseOrderResponse
        if (isset($payload['purchasenumber']) && isset($payload['purchaseorderstatus'])) {
            return 'purchaseorder.status';
        }

        return 'unknown';
    }

    // ── Order Events ────────────────────────────────────────

    private function handleOrderStatusUpdate(Tenant $tenant, array $data): void
    {
        $orderNumber = $data['ordernumber'] ?? null;
        $status = $data['orderstatus'] ?? null;

        if (!$orderNumber) {
            return;
        }

        $order = Order::where('order_number', $orderNumber)
            ->orWhere('storlogix_order_number', $orderNumber)
            ->first();

        if (!$order) {
            Log::warning('Storelogix webhook: order not found', [
                'tenant_id'    => $tenant->id,
                'order_number' => $orderNumber,
            ]);
            return;
        }

        $statusMap = [
            'received' => 'new',
            'planed'   => 'processing',
            'picking'  => 'processing',
            'packing'  => 'processing',
            'finished' => 'processing',
            'shipped'  => 'shipped',
            'canceled' => 'cancelled',
        ];

        $newStatus = $statusMap[$status] ?? $status;

        $updates = [
            'storlogix_status' => $status,
            'last_synced_at'   => now(),
        ];

        if (in_array($newStatus, Order::STATUSES)) {
            $updates['status'] = $newStatus;
        }

        $order->update($updates);

        if ($status === 'shipped') {
            $this->pushOrderStatusToJtl($tenant, $order, 'shipped');
        }
        if ($status === 'canceled' && !empty($data['cancellationreason'])) {
            Log::info('Storelogix order cancelled', [
                'order_id' => $order->id,
                'reason'   => $data['cancellationreason'],
            ]);
        }
    }

    private function handleOrderCompleted(Tenant $tenant, array $data): void
    {
        $orderNumber = $data['ordernumber'] ?? null;

        if (!$orderNumber) {
            return;
        }

        $order = Order::where('order_number', $orderNumber)
            ->orWhere('storlogix_order_number', $orderNumber)
            ->first();

        if (!$order) {
            Log::warning('Storelogix webhook: order not found for completion', [
                'order_number' => $orderNumber,
            ]);
            return;
        }

        $order->update([
            'status'               => Order::STATUS_COMPLETED,
            'storlogix_status'     => 'shipped',
            'delivery_note_number' => $data['deliverynotenumber'] ?? null,
            'last_synced_at'       => now(),
        ]);

        // Create or update shipment with tracking info
        $shipment = $order->shipments()->firstOrCreate(
            ['wms_order_id' => $order->id],
            [
                'status'            => 'shipped',
                'tracking_number'   => null,
                'carrier'           => null,
                'shipped_date'      => $data['shippingdate'] ?? now()->format('Y-m-d'),
                'last_synced_at'    => now(),
            ]
        );

        // Process parcels
        foreach ($data['parcels'] ?? [] as $parcelData) {
            $shipment->parcels()->create([
                'shipper'           => $parcelData['shipper'] ?? null,
                'shipping_service'  => $parcelData['shippingservice'] ?? null,
                'sscc'              => $parcelData['sscc'] ?? null,
                'tracking_number'   => $parcelData['trackingnumber'] ?? null,
                'tracking_url'      => $parcelData['trackingurl'] ?? null,
                'parcel_weight'     => $parcelData['parcelweight'] ?? null,
                'package_type'      => $parcelData['packagetype'] ?? null,
                'package_sku'       => $parcelData['packagesku'] ?? null,
                'package_length'    => $parcelData['packagesize']['l'] ?? null,
                'package_width'     => $parcelData['packagesize']['w'] ?? null,
                'package_height'    => $parcelData['packagesize']['h'] ?? null,
            ]);
        }

        // Update shipment with first parcel's tracking info
        $firstParcel = $shipment->parcels()->first();
        if ($firstParcel) {
            $shipment->update([
                'tracking_number'  => $firstParcel->tracking_number,
                'tracking_url'     => $firstParcel->tracking_url,
                'carrier'          => $firstParcel->shipper,
                'shipping_service' => $firstParcel->shipping_service,
                'sscc'             => $firstParcel->sscc,
            ]);
        }

        // Process items - update order items with picked quantities
        foreach ($data['items'] ?? [] as $itemData) {
            $order->items()
                ->where('sku', $itemData['itemno'] ?? '')
                ->update([
                    'quantity' => (int) ($itemData['quantity'] ?? 0),
                ]);
        }

        $this->pushOrderStatusToJtl($tenant, $order, 'completed');

        Log::info('Storelogix order completed', [
            'order_id'     => $order->id,
            'order_number' => $orderNumber,
            'parcels'      => count($data['parcels'] ?? []),
        ]);
    }

    private function handleOrderCancelled(Tenant $tenant, array $data): void
    {
        $orderNumber = $data['ordernumber'] ?? null;

        if (!$orderNumber) {
            return;
        }

        $order = Order::where('order_number', $orderNumber)
            ->orWhere('storlogix_order_number', $orderNumber)
            ->first();

        if (!$order) {
            return;
        }

        $order->update([
            'status'           => Order::STATUS_CANCELLED,
            'storlogix_status' => 'canceled',
            'last_synced_at'   => now(),
        ]);

        Log::info('Storelogix order cancelled', [
            'order_id' => $order->id,
            'reason'   => $data['cancellationreason'] ?? null,
        ]);
    }

    // ── Goods Receipt ───────────────────────────────────────

    private function handleGoodsReceipt(Tenant $tenant, array $data): void
    {
        $purchaseNumber = $data['purchasenumber'] ?? null;

        foreach ($data['items'] ?? [] as $item) {
            $sku = $item['customitemno'] ?? $item['itemno'] ?? null;
            if (!$sku) {
                continue;
            }

            $product = Product::where('sku', $sku)->first();
            if ($product) {
                $quantityReceived = (int) ($item['quantityavailable'] ?? 0);
                $quantityLocked = (int) ($item['quantitylocked'] ?? 0);

                $product->update([
                    'quantity'       => $product->quantity + $quantityReceived,
                    'last_synced_at' => now(),
                ]);

                StockMovement::create([
                    'wms_product_id'  => $product->id,
                    'sku'             => $sku,
                    'change_type'     => 'ADD',
                    'quantity_change' => $quantityReceived,
                    'reason'          => "Wareneingang: {$purchaseNumber}",
                    'location'        => $data['location'] ?? null,
                    'warehouse'       => $data['warehouse'] ?? null,
                    'client'          => $data['client'] ?? null,
                    'lot'             => $item['lot'] ?? null,
                    'bbd'             => $item['bbd'] ?? null,
                    'changed_at'      => now(),
                ]);
            }
        }

        Log::info('Storelogix goods receipt processed', [
            'purchase_number' => $purchaseNumber,
            'items'           => count($data['items'] ?? []),
        ]);
    }

    // ── Return Events ───────────────────────────────────────

    private function handleReturnReceived(Tenant $tenant, array $data): void
    {
        $returnNumber = $data['retourenumber'] ?? null;
        $originalOrderNumber = $data['originalordernumber'] ?? null;

        $order = null;
        if ($originalOrderNumber) {
            $order = Order::where('order_number', $originalOrderNumber)->first();
        }

        foreach ($data['items'] ?? [] as $item) {
            ReturnRecord::create([
                'wms_order_id'                => $order?->id,
                'storlogix_return_id'         => $returnNumber,
                'return_number'               => $returnNumber,
                'rma_number'                  => $data['rmanumber'] ?? null,
                'return_advice_number'        => null,
                'reason'                      => $data['returnreason'] ?? null,
                'status'                      => 'received',
                'quantity'                    => (int) ($item['quantityok'] ?? 0) + (int) ($item['quantitylocked'] ?? 0),
                'condition'                   => $item['itemreturncondition'] ?? null,
                'return_quality'              => $item['quality'] ?? null,
                'return_condition_description' => null,
                'item_return_status'          => 'ACCEPT',
                'serial_number'               => $item['serialno'] ?? null,
                'received_at'                 => $data['returndate'] ?? now(),
            ]);
        }

        Log::info('Storelogix return received', [
            'return_number' => $returnNumber,
            'items'         => count($data['items'] ?? []),
        ]);
    }

    private function handleReturnStatusChange(Tenant $tenant, array $data): void
    {
        $returnNumber = $data['retourenumber'] ?? null;

        if (!$returnNumber) {
            return;
        }

        $changes = $data['changes'] ?? [];
        foreach ($changes as $change) {
            $sku = $change['customitemno'] ?? $change['itemno'] ?? null;

            $return = ReturnRecord::where('storlogix_return_id', $returnNumber)
                ->when($sku, fn ($q) => $q->whereHas('product', fn ($q2) => $q2->where('sku', $sku)))
                ->first();

            if ($return) {
                $return->update([
                    'item_return_status'           => $change['itemreturnstatus'] ?? $return->item_return_status,
                    'return_quality'               => $change['itemreturnquality'] ?? $return->return_quality,
                    'return_condition_description' => $change['conditiondescription'] ?? $return->return_condition_description,
                ]);
            }
        }

        Log::info('Storelogix return status changed', [
            'return_number' => $returnNumber,
            'changes'       => count($changes),
        ]);
    }

    // ── Stock Changes ───────────────────────────────────────

    private function handleStockChange(Tenant $tenant, array $data): void
    {
        $changes = $data['inventorchanges'] ?? $data['inventorchange'] ?? [];
        if (!is_array($changes)) {
            $changes = [$changes];
        }

        foreach ($changes as $change) {
            $sku = $change['customitemno'] ?? $change['itemno'] ?? null;
            if (!$sku) {
                continue;
            }

            $product = Product::where('sku', $sku)->first();

            StockMovement::create([
                'wms_product_id'  => $product?->id,
                'sku'             => $sku,
                'change_type'     => $change['changetype'] ?? null,
                'quantity_change' => (int) ($change['quantity'] ?? 0),
                'reason'          => $change['reason'] ?? null,
                'location'        => $change['location'] ?? null,
                'warehouse'       => $change['warehouse'] ?? null,
                'client'          => $change['client'] ?? null,
                'lot'             => $change['lot'] ?? null,
                'bbd'             => $change['bbd'] ?? null,
                'changed_at'      => $change['processtimestamp'] ?? now(),
            ]);

            if ($product) {
                $newQuantity = $product->quantity + (int) ($change['quantity'] ?? 0);
                $product->update([
                    'quantity'       => max(0, $newQuantity),
                    'last_synced_at' => now(),
                ]);
            }
        }

        Log::info('Storelogix stock change processed', [
            'changes' => count($changes),
        ]);
    }

    // ── JTL Push ────────────────────────────────────────────

    private function pushOrderStatusToJtl(Tenant $tenant, ?Order $order, string $status): void
    {
        if (!$order || !$order->jtl_order_id) {
            return;
        }

        try {
            $jtl = new JtlWawiApiService($tenant);
            if (!$jtl->isConfigured() || !$jtl->isAuthenticated()) {
                return;
            }

            $jtlStatus = match($status) {
                'shipped'   => 'shipped',
                'completed' => 'completed',
                default     => $status,
            };

            $jtl->updateOrderStatus($order->jtl_order_id, $jtlStatus);

            SyncLog::create([
                'direction' => 'out',
                'type'      => 'order',
                'entity_id' => (string) $order->id,
                'status'    => 'success',
                'message'   => "Bestellstatus '{$jtlStatus}' an JTL-Wawi gesendet",
            ]);

            Log::info('JTL order status pushed', [
                'order_id'     => $order->id,
                'jtl_order_id' => $order->jtl_order_id,
                'status'       => $jtlStatus,
            ]);
        } catch (\Exception $e) {
            Log::error('JTL order status push failed', [
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
}
