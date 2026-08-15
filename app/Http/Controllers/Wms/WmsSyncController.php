<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Wms\Product;
use App\Models\Wms\Order;
use App\Models\Wms\OrderItem;
use App\Models\Wms\SyncLog;
use App\Models\Wms\ReturnRecord;
use App\Models\Wms\StockMovement;
use App\Services\JtlWawiApiService;
use App\Services\StorlogixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WmsSyncController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        $jtl = new JtlWawiApiService($tenant);
        $logs = SyncLog::latest()->take(50)->get();

        return view('settings.wms-sync', [
            'jtlConfigured'    => $jtl->isConfigured(),
            'jtlAuthenticated' => $jtl->isAuthenticated(),
            'jtlMode'          => $jtl->getMode(),
            'tenant'           => $tenant,
            'logs'             => $logs,
        ]);
    }

    public function syncItems()
    {
        $tenant = auth()->user()->tenant;
        $jtl = new JtlWawiApiService($tenant);

        if (!$jtl->isConfigured()) {
            return redirect()->route('wms.sync.index')->with('error', 'JTL-Wawi nicht konfiguriert. Bitte zuerst unter Einstellungen → JTL-Wawi verbinden.');
        }

        try {
            $page = 1;
            $synced = 0;
            $totalPages = 1;

            while ($page <= $totalPages) {
                $data = $jtl->queryItems($page, 100);
                $totalPages = ceil(($data['totalItems'] ?? 0) / 100);

                foreach ($data['items'] ?? [] as $item) {
                    $identifiers = $item['identifiers'] ?? [];
                    Product::updateOrCreate(
                        [
                            'jtl_id'    => $item['id'],
                        ],
                        [
                            'sku'      => $item['sKU'] ?? $item['id'],
                            'name'     => $item['name'] ?? '',
                            'ean'      => $identifiers['gtin'] ?? null,
                            'price'    => $item['itemPriceData']['salesPriceNet'] ?? null,
                            'weight'   => $item['weights']['weight'] ?? null,
                            'length'   => $item['dimensions']['length'] ?? null,
                            'width'    => $item['dimensions']['width'] ?? null,
                            'height'   => $item['dimensions']['height'] ?? null,
                            'last_synced_at' => now(),
                        ]
                    );
                    $synced++;
                }

                $page++;
            }

            SyncLog::create([
                'direction' => 'in',
                'type'      => 'product',
                'status'    => 'success',
                'message'   => "{$synced} Artikel aus JTL-Wawi synchronisiert (via {$jtl->getMode()}).",
            ]);

            return redirect()->route('wms.sync.index')->with('success', "{$synced} Artikel synchronisiert.");
        } catch (\Exception $e) {
            SyncLog::create([
                'direction' => 'in',
                'type'      => 'product',
                'status'    => 'error',
                'message'   => $e->getMessage(),
            ]);
            return redirect()->route('wms.sync.index')->with('error', 'Artikel-Sync fehlgeschlagen: ' . $e->getMessage());
        }
    }

    public function syncStocks()
    {
        $tenant = auth()->user()->tenant;
        $jtl = new JtlWawiApiService($tenant);

        if (!$jtl->isConfigured()) {
            return redirect()->route('wms.sync.index')->with('error', 'JTL-Wawi nicht konfiguriert. Bitte zuerst unter Einstellungen → JTL-Wawi verbinden.');
        }

        try {
            $page = 1;
            $synced = 0;
            $totalPages = 1;

            while ($page <= $totalPages) {
                $data = $jtl->queryStocks($page, 100);
                $totalPages = ceil(($data['totalItems'] ?? 0) / 100);

                foreach ($data['items'] ?? [] as $stock) {
                    $product = Product::where('jtl_id', $stock['itemId'])
                        ->first();

                    if ($product) {
                        $product->update([
                            'quantity'      => (int) ($stock['quantityTotal'] ?? 0),
                            'last_synced_at' => now(),
                        ]);
                        $synced++;
                    }
                }

                $page++;
            }

            SyncLog::create([
                'direction' => 'in',
                'type'      => 'stock',
                'status'    => 'success',
                'message'   => "{$synced} Bestände aus JTL-Wawi synchronisiert (via {$jtl->getMode()}).",
            ]);

            return redirect()->route('wms.sync.index')->with('success', "{$synced} Bestände synchronisiert.");
        } catch (\Exception $e) {
            SyncLog::create([
                'direction' => 'in',
                'type'      => 'stock',
                'status'    => 'error',
                'message'   => $e->getMessage(),
            ]);
            return redirect()->route('wms.sync.index')->with('error', 'Bestands-Sync fehlgeschlagen: ' . $e->getMessage());
        }
    }

    public function syncOrders()
    {
        $tenant = auth()->user()->tenant;
        $jtl = new JtlWawiApiService($tenant);

        if (!$jtl->isConfigured()) {
            return redirect()->route('wms.sync.index')->with('error', 'JTL-Wawi nicht konfiguriert. Bitte zuerst unter Einstellungen → JTL-Wawi verbinden.');
        }

        try {
            $page = 1;
            $synced = 0;
            $totalPages = 1;

            while ($page <= $totalPages) {
                $data = $jtl->querySalesOrders($page, 100);
                $totalPages = ceil(($data['totalItems'] ?? 0) / 100);

                foreach ($data['items'] ?? [] as $orderData) {
                    $billing = $orderData['billingAddress'] ?? [];
                    $shipping = $orderData['shipmentAddress'] ?? [];

                    $order = Order::updateOrCreate(
                        [
                            'jtl_order_id'  => $orderData['id'],
                        ],
                        [
                            'order_number'     => $orderData['externalNumber'] ?? $orderData['number'] ?? '',
                            'customer_name'    => trim(($billing['firstName'] ?? '') . ' ' . ($billing['lastName'] ?? '')),
                            'customer_address' => $billing['street'] ?? '',
                            'customer_zip'     => $billing['postalCode'] ?? '',
                            'customer_city'    => $billing['city'] ?? '',
                            'customer_country' => $billing['countryIso'] ?? 'DE',
                            'total_amount'     => $orderData['salesOrderPaymentDetails']['totalGrossAmount'] ?? null,
                            'status'           => $orderData['isCancelled'] ? 'cancelled' : 'new',
                            'ordered_at'       => $orderData['salesOrderDate'] ?? null,
                            'last_synced_at'   => now(),
                        ]
                    );

                    foreach ($orderData['salesOrderLineItems'] ?? [] as $lineItem) {
                        if (($lineItem['lineItemType'] ?? 0) == 1) {
                            $product = Product::where('jtl_id', $lineItem['itemId'] ?? '')
                                ->first();

                            $order->items()->updateOrCreate(
                                [
                                    'wms_order_id'   => $order->id,
                                    'wms_product_id' => $product?->id,
                                ],
                                [
                                    'sku'        => $lineItem['name'] ?? '',
                                    'name'       => $lineItem['name'] ?? '',
                                    'quantity'   => (int) ($lineItem['quantity'] ?? 0),
                                    'unit_price' => $lineItem['netSalesPricePerUnit'] ?? null,
                                ]
                            );
                        }
                    }

                    $synced++;
                }

                $page++;
            }

            SyncLog::create([
                'direction' => 'in',
                'type'      => 'order',
                'status'    => 'success',
                'message'   => "{$synced} Bestellungen aus JTL-Wawi synchronisiert (via {$jtl->getMode()}).",
            ]);

            return redirect()->route('wms.sync.index')->with('success', "{$synced} Bestellungen synchronisiert.");
        } catch (\Exception $e) {
            SyncLog::create([
                'direction' => 'in',
                'type'      => 'stock',
                'status'    => 'error',
                'message'   => $e->getMessage(),
            ]);
            return redirect()->route('wms.sync.index')->with('error', 'Bestellungs-Sync fehlgeschlagen: ' . $e->getMessage());
        }
    }

    public function pushStocks(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $jtl = new JtlWawiApiService($tenant);

        if (!$jtl->isConfigured()) {
            return redirect()->route('wms.sync.index')->with('error', 'JTL-Wawi API nicht konfiguriert. Bitte zuerst unter Einstellungen → JTL-Wawi verbinden.');
        }

        $products = Product::whereNotNull('jtl_id')
            ->where('quantity', '>', 0)
            ->get();

        if ($products->isEmpty()) {
            return redirect()->route('wms.sync.index')->with('error', 'Keine Produkte mit JTL-ID und Bestand gefunden.');
        }

        $synced = 0;
        $errors = 0;

        foreach ($products as $product) {
            try {
                $jtl->updateStock($product->jtl_id, $product->quantity);
                $product->update(['last_synced_at' => now()]);
                $synced++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('JTL stock push failed', [
                    'product_id' => $product->id,
                    'jtl_id'     => $product->jtl_id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        SyncLog::create([
            'direction' => 'out',
            'type'      => 'stock',
            'status'    => $errors === 0 ? 'success' : 'error',
            'message'   => "{$synced} Bestände an JTL-Wawi gesendet (via {$jtl->getMode()}), {$errors} Fehler.",
        ]);

        $message = "{$synced} Bestände an JTL-Wawi gesendet.";
        if ($errors > 0) {
            $message .= " {$errors} Fehler aufgetreten.";
        }

        return redirect()->route('wms.sync.index')->with($errors > 0 ? 'error' : 'success', $message);
    }

    // ── Storlogix Sync ───────────────────────────────────────

    public function syncStorlogixReturns()
    {
        $tenant = auth()->user()->tenant;
        $storlogix = new StorlogixService($tenant);

        if (!$storlogix->isConfigured()) {
            return redirect()->route('wms.sync.index')->with('error', 'Storlogix nicht konfiguriert. Bitte zuerst unter Einstellungen → Storlogix Connect verbinden.');
        }

        try {
            $page = 1;
            $synced = 0;
            $totalPages = 1;

            while ($page <= $totalPages) {
                $data = $storlogix->getReturns($page, 100);
                $totalPages = ceil(($data['total'] ?? count($data['data'] ?? [])) / 100);

                foreach ($data['data'] ?? $data['returns'] ?? [] as $return) {
                    $orderNumber = $return['orderNumber'] ?? $return['order_number'] ?? null;
                    $order = null;
                    if ($orderNumber) {
                        $order = Order::where('order_number', $orderNumber)
                            ->first();
                    }

                    ReturnRecord::updateOrCreate(
                        [
                            'storlogix_return_id'  => $return['id'] ?? $return['returnId'] ?? null,
                        ],
                        [
                            'wms_order_id'  => $order?->id,
                            'return_number' => $return['returnNumber'] ?? $return['return_number'] ?? null,
                            'reason'        => $return['reason'] ?? null,
                            'status'        => $return['status'] ?? ReturnRecord::STATUS_RECEIVED,
                            'quantity'      => (int) ($return['quantity'] ?? 1),
                            'condition'     => $return['condition'] ?? null,
                            'received_at'   => $return['receivedAt'] ?? $return['received_at'] ?? now(),
                        ]
                    );
                    $synced++;
                }

                if (count($data['data'] ?? $data['returns'] ?? []) < 100) {
                    break;
                }
                $page++;
            }

            SyncLog::create([
                'direction' => 'in',
                'type'      => 'return',
                'status'    => 'success',
                'message'   => "{$synced} Retouren aus Storlogix synchronisiert.",
            ]);

            return redirect()->route('wms.sync.index')->with('success', "{$synced} Retouren synchronisiert.");
        } catch (\Exception $e) {
            SyncLog::create([
                'direction' => 'in',
                'type'      => 'order',
                'status'    => 'error',
                'message'   => $e->getMessage(),
            ]);
            return redirect()->route('wms.sync.index')->with('error', 'Retouren-Sync fehlgeschlagen: ' . $e->getMessage());
        }
    }

    public function syncStorlogixStock()
    {
        $tenant = auth()->user()->tenant;
        $storlogix = new StorlogixService($tenant);

        if (!$storlogix->isConfigured()) {
            return redirect()->route('wms.sync.index')->with('error', 'Storlogix nicht konfiguriert. Bitte zuerst unter Einstellungen → Storlogix Connect verbinden.');
        }

        $jtl = new JtlWawiApiService($tenant);
        $jtlReady = $jtl->isConfigured() && $jtl->isAuthenticated();

        try {
            $page = 1;
            $synced = 0;
            $pushed = 0;
            $totalPages = 1;

            while ($page <= $totalPages) {
                $data = $storlogix->getStock($page, 100);
                $totalPages = ceil(($data['total'] ?? count($data['data'] ?? [])) / 100);

                foreach ($data['data'] ?? $data['stock'] ?? [] as $item) {
                    $sku = $item['sku'] ?? null;
                    if (!$sku) {
                        continue;
                    }

                    $quantity = (int) ($item['quantity'] ?? $item['stock'] ?? 0);

                    $product = Product::where('sku', $sku)
                        ->first();

                    if ($product) {
                        $product->update([
                            'quantity'       => $quantity,
                            'last_synced_at' => now(),
                        ]);
                        $synced++;

                        if ($jtlReady && $product->jtl_id) {
                            try {
                                $jtl->updateStock($product->jtl_id, $quantity);
                                $pushed++;
                            } catch (\Exception $e) {
                                Log::error('JTL stock push failed from Storlogix sync', [
                                    'product_id' => $product->id,
                                    'jtl_id'     => $product->jtl_id,
                                    'error'      => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                }

                if (count($data['data'] ?? $data['stock'] ?? []) < 100) {
                    break;
                }
                $page++;
            }

            SyncLog::create([
                'direction' => 'out',
                'type'      => 'stock',
                'status'    => 'success',
                'message'   => "{$synced} Bestände aus Storlogix gelesen, {$pushed} an JTL-Wawi gesendet.",
            ]);

            $message = "{$synced} Bestände aus Storlogix synchronisiert.";
            if ($jtlReady) {
                $message .= " {$pushed} an JTL-Wawi gesendet.";
            } else {
                $message .= " JTL-Wawi nicht verbunden — Bestände nur lokal aktualisiert.";
            }

            return redirect()->route('wms.sync.index')->with('success', $message);
        } catch (\Exception $e) {
            SyncLog::create([
                'direction' => 'in',
                'type'      => 'stock',
                'status'    => 'error',
                'message'   => $e->getMessage(),
            ]);
            return redirect()->route('wms.sync.index')->with('error', 'Bestands-Sync fehlgeschlagen: ' . $e->getMessage());
        }
    }

    // ── Storelogix: Articles Push ───────────────────────────

    public function syncArticlesToStorlogix()
    {
        $tenant = auth()->user()->tenant;
        $storlogix = new StorlogixService($tenant);

        if (!$storlogix->isConfigured()) {
            return redirect()->route('wms.sync.index')->with('error', 'Storelogix nicht konfiguriert.');
        }

        try {
            $products = Product::whereNotNull('sku')->get();

            if ($products->isEmpty()) {
                return redirect()->route('wms.sync.index')->with('error', 'Keine Artikel zum Synchronisieren vorhanden.');
            }

            $result = $storlogix->syncArticles($products);

            SyncLog::create([
                'direction' => 'out',
                'type'      => 'product',
                'status'    => $result['errors'] === 0 ? 'success' : 'error',
                'message'   => "{$result['synced']} Artikel an Storelogix gesendet, {$result['errors']} Fehler.",
                'payload'   => $result,
            ]);

            $message = "{$result['synced']} Artikel an Storelogix gesendet.";
            if ($result['errors'] > 0) {
                $message .= " {$result['errors']} Fehler.";
            }

            return redirect()->route('wms.sync.index')->with($result['errors'] > 0 ? 'error' : 'success', $message);
        } catch (\Exception $e) {
            SyncLog::create([
                'direction' => 'out',
                'type'      => 'product',
                'status'    => 'error',
                'message'   => $e->getMessage(),
            ]);
            return redirect()->route('wms.sync.index')->with('error', 'Artikel-Sync fehlgeschlagen: ' . $e->getMessage());
        }
    }

    // ── Storelogix: Send Delivery Order ─────────────────────

    public function sendDeliveryOrder(int $orderId)
    {
        $tenant = auth()->user()->tenant;
        $storlogix = new StorlogixService($tenant);

        if (!$storlogix->isConfigured()) {
            return redirect()->route('wms.sync.index')->with('error', 'Storelogix nicht konfiguriert.');
        }

        $order = Order::with('items.product')->find($orderId);
        if (!$order) {
            return redirect()->route('wms.orders.show', $orderId)->with('error', 'Bestellung nicht gefunden.');
        }

        try {
            $result = $storlogix->sendDeliveryOrder($order);

            SyncLog::create([
                'direction' => 'out',
                'type'      => 'order',
                'entity_id' => (string) $orderId,
                'status'    => 'success',
                'message'   => "Lieferauftrag '{$order->order_number}' an Storelogix gesendet.",
                'payload'   => $result,
            ]);

            return redirect()->route('wms.orders.show', $orderId)
                ->with('success', 'Lieferauftrag an Storelogix gesendet.');
        } catch (\Exception $e) {
            SyncLog::create([
                'direction' => 'out',
                'type'      => 'order',
                'entity_id' => (string) $orderId,
                'status'    => 'error',
                'message'   => $e->getMessage(),
            ]);
            return redirect()->route('wms.orders.show', $orderId)
                ->with('error', 'Versand fehlgeschlagen: ' . $e->getMessage());
        }
    }

    // ── Storelogix: Pull Goods Receipts ─────────────────────

    public function syncGoodsReceipts()
    {
        $tenant = auth()->user()->tenant;
        $storlogix = new StorlogixService($tenant);

        if (!$storlogix->isConfigured()) {
            return redirect()->route('wms.sync.index')->with('error', 'Storelogix nicht konfiguriert.');
        }

        try {
            $data = $storlogix->getGoodsReceipts(100);
            $synced = 0;

            foreach ($data['items'] ?? [] as $item) {
                $sku = $item['customitemno'] ?? $item['itemno'] ?? null;
                if (!$sku) {
                    continue;
                }

                $product = Product::where('sku', $sku)->first();
                if ($product) {
                    $quantityReceived = (int) ($item['quantityavailable'] ?? 0);

                    $product->update([
                        'quantity'       => $product->quantity + $quantityReceived,
                        'last_synced_at' => now(),
                    ]);

                    StockMovement::create([
                        'wms_product_id'  => $product->id,
                        'sku'             => $sku,
                        'change_type'     => 'ADD',
                        'quantity_change' => $quantityReceived,
                        'reason'          => 'Wareneingang (Storelogix)',
                        'lot'             => $item['lot'] ?? null,
                        'bbd'             => $item['bbd'] ?? null,
                        'changed_at'      => now(),
                    ]);

                    $synced++;
                }
            }

            SyncLog::create([
                'direction' => 'in',
                'type'      => 'goods_receipt',
                'status'    => 'success',
                'message'   => "{$synced} Wareneingänge aus Storelogix verarbeitet.",
            ]);

            return redirect()->route('wms.sync.index')->with('success', "{$synced} Wareneingänge verarbeitet.");
        } catch (\Exception $e) {
            SyncLog::create([
                'direction' => 'in',
                'type'      => 'goods_receipt',
                'status'    => 'error',
                'message'   => $e->getMessage(),
            ]);
            return redirect()->route('wms.sync.index')->with('error', 'Wareneingang-Sync fehlgeschlagen: ' . $e->getMessage());
        }
    }

    // ── Storelogix: Pull Stock Changes ──────────────────────

    public function syncStockChanges()
    {
        $tenant = auth()->user()->tenant;
        $storlogix = new StorlogixService($tenant);

        if (!$storlogix->isConfigured()) {
            return redirect()->route('wms.sync.index')->with('error', 'Storelogix nicht konfiguriert.');
        }

        try {
            $data = $storlogix->getStockChanges(100);
            $synced = 0;

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

                $synced++;
            }

            SyncLog::create([
                'direction' => 'in',
                'type'      => 'stock_change',
                'status'    => 'success',
                'message'   => "{$synced} Bestandsänderungen aus Storelogix verarbeitet.",
            ]);

            return redirect()->route('wms.sync.index')->with('success', "{$synced} Bestandsänderungen verarbeitet.");
        } catch (\Exception $e) {
            SyncLog::create([
                'direction' => 'in',
                'type'      => 'stock_change',
                'status'    => 'error',
                'message'   => $e->getMessage(),
            ]);
            return redirect()->route('wms.sync.index')->with('error', 'Bestandsänderung-Sync fehlgeschlagen: ' . $e->getMessage());
        }
    }

    // ── Storelogix: Pull Order Updates ──────────────────────

    public function syncOrderUpdates()
    {
        $tenant = auth()->user()->tenant;
        $storlogix = new StorlogixService($tenant);

        if (!$storlogix->isConfigured()) {
            return redirect()->route('wms.sync.index')->with('error', 'Storelogix nicht konfiguriert.');
        }

        try {
            // Pull status updates
            $statusData = $storlogix->getOrderStatusUpdates(100);
            $statusUpdates = $statusData['deliveryorderresponse'] ?? $statusData;
            if (!is_array($statusUpdates)) {
                $statusUpdates = [$statusUpdates];
            }

            $synced = 0;
            foreach ($statusUpdates as $update) {
                $orderNumber = $update['ordernumber'] ?? null;
                if (!$orderNumber) {
                    continue;
                }

                $order = Order::where('order_number', $orderNumber)
                    ->orWhere('storlogix_order_number', $orderNumber)
                    ->first();

                if ($order) {
                    $order->update([
                        'storlogix_status' => $update['orderstatus'] ?? $order->storlogix_status,
                        'last_synced_at'   => now(),
                    ]);
                    $synced++;
                }
            }

            // Pull completed orders
            $completedData = $storlogix->getOrderCompleted(100);
            $completedOrders = $completedData['deliveryorderresponse'] ?? $completedData;
            if (!is_array($completedOrders)) {
                $completedOrders = [$completedOrders];
            }

            foreach ($completedOrders as $completed) {
                $orderNumber = $completed['ordernumber'] ?? null;
                if (!$orderNumber) {
                    continue;
                }

                $order = Order::where('order_number', $orderNumber)
                    ->orWhere('storlogix_order_number', $orderNumber)
                    ->first();

                if ($order && ($completed['orderstatus'] ?? '') === 'shipped') {
                    $order->update([
                        'status'           => Order::STATUS_COMPLETED,
                        'storlogix_status' => 'shipped',
                        'last_synced_at'   => now(),
                    ]);
                    $synced++;
                }
            }

            SyncLog::create([
                'direction' => 'in',
                'type'      => 'order',
                'status'    => 'success',
                'message'   => "{$synced} Bestellungs-Updates aus Storelogix verarbeitet.",
            ]);

            return redirect()->route('wms.sync.index')->with('success', "{$synced} Bestellungs-Updates verarbeitet.");
        } catch (\Exception $e) {
            SyncLog::create([
                'direction' => 'in',
                'type'      => 'order',
                'status'    => 'error',
                'message'   => $e->getMessage(),
            ]);
            return redirect()->route('wms.sync.index')->with('error', 'Bestellungs-Update fehlgeschlagen: ' . $e->getMessage());
        }
    }
}
