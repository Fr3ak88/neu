<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Wms\Order;
use App\Models\Wms\Product;
use App\Models\Wms\Shipment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StorlogixService
{
    private Tenant $tenant;

    private const TOKEN_CACHE_KEY = 'storlogix_access_token_';
    private const TOKEN_BUFFER_SECONDS = 30;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function isConfigured(): bool
    {
        return !empty($this->tenant->storlogix_api_url)
            && !empty($this->tenant->storlogix_api_key)
            && !empty($this->tenant->storlogix_client_name);
    }

    private function getBaseUrl(): string
    {
        return rtrim($this->tenant->storlogix_api_url ?? '', '/');
    }

    private function getClientName(): string
    {
        return $this->tenant->storlogix_client_name ?? '';
    }

    private function getLocation(): string
    {
        return $this->tenant->storlogix_location ?? '';
    }

    private function getWarehouse(): string
    {
        return $this->tenant->storlogix_warehouse ?? '';
    }

    // ── Authentication ──────────────────────────────────────

    public function getAccessToken(): string
    {
        $cacheKey = self::TOKEN_CACHE_KEY . $this->tenant->id;

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $username = $this->tenant->storlogix_api_key;
        $password = $this->tenant->storlogix_api_secret
            ? decrypt($this->tenant->storlogix_api_secret)
            : '';

        $response = Http::withBasicAuth($username, $password)
            ->timeout(15)
            ->get($this->getBaseUrl() . '/REST/Order/Login', [
                'username' => $username,
                'password' => $password,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Storelogix Login fehlgeschlagen (' . $response->status() . '): ' . $response->body()
            );
        }

        $data = $response->json();

        if (($data['ErrorCode'] ?? 1) !== 0) {
            throw new \RuntimeException(
                'Storelogix Login Fehler: ' . ($data['Message'] ?? 'Unbekannt')
            );
        }

        $token = $data['AccessToken'];
        $expiresIn = ($data['ExpiresIn'] ?? 360) - self::TOKEN_BUFFER_SECONDS;

        Cache::put($cacheKey, $token, now()->addSeconds($expiresIn));

        Log::info('Storelogix access token obtained', [
            'tenant_id'  => $this->tenant->id,
            'expires_in' => $expiresIn,
        ]);

        return $token;
    }

    private function getAuthHeaders(): array
    {
        return [
            'AccessToken' => $this->getAccessToken(),
            'Accept'      => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    // ── Base Request ────────────────────────────────────────

    private function request(string $method, string $endpoint, array $payload = []): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Storelogix nicht konfiguriert.');
        }

        $url = $this->getBaseUrl() . $endpoint;

        $response = Http::withHeaders($this->getAuthHeaders())
            ->timeout(30)
            ->$method($url, $payload);

        if ($response->failed()) {
            Log::error('Storelogix API error', [
                'endpoint' => $endpoint,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            throw new \RuntimeException(
                'Storelogix API Fehler (' . $response->status() . '): ' . $response->body()
            );
        }

        return $response->json();
    }

    private function requestGet(string $endpoint): array
    {
        return $this->request('get', $endpoint);
    }

    // ── Connection Test ─────────────────────────────────────

    public function testConnection(): array
    {
        try {
            $token = $this->getAccessToken();
            return [
                'success' => true,
                'message' => 'Storelogix Verbindung erfolgreich.',
                'data'    => ['AccessToken' => substr($token, 0, 8) . '...'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Verbindung fehlgeschlagen: ' . $e->getMessage(),
            ];
        }
    }

    // ── Articles → Storelogix ───────────────────────────────

    public function syncArticle(Product $product): array
    {
        $payload = [
            'article' => [
                'article_info' => [
                    'status'      => 'U',
                    'client'      => $this->getClientName(),
                    'article_nr'  => $product->sku,
                    'article_add_nr' => $product->ean,
                    'brand'       => null,
                    'bbd_type'    => 'N',
                    'lot_type'    => 'N',
                    'article_units' => [
                        [
                            'unit_type'        => 'ST',
                            'unit_variant'     => $product->sku,
                            'unit_description' => $product->name,
                            'unit_ean13'       => $product->ean,
                            'unit_length'      => $product->length ? (int) ($product->length * 10) : null,
                            'unit_width'       => $product->width ? (int) ($product->width * 10) : null,
                            'unit_height'      => $product->height ? (int) ($product->height * 10) : null,
                            'unit_weight_net'  => $product->weight ? (int) ($product->weight * 1000) : null,
                            'unit_price_net'   => $product->price,
                            'unit_tax'         => 19,
                        ],
                    ],
                    'article_texts' => [
                        [
                            'language'          => 'DE',
                            'article_short_text' => $product->name,
                            'article_long_text'  => $product->name,
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->request(
            'post',
            '/REST/Article/insert_article?Client=' . urlencode($this->getClientName()),
            $payload
        );

        Log::info('Storelogix article synced', [
            'product_id' => $product->id,
            'sku'        => $product->sku,
            'response'   => $result,
        ]);

        return $result;
    }

    public function syncArticles(\Illuminate\Support\Collection $products): array
    {
        $results = ['synced' => 0, 'errors' => 0, 'details' => []];

        foreach ($products as $product) {
            try {
                $result = $this->syncArticle($product);
                $results['synced']++;
                $results['details'][] = [
                    'sku'      => $product->sku,
                    'status'   => $result['ArticleStatus'] ?? 'unknown',
                    'error'    => $result['ErrorText'] ?? null,
                ];
            } catch (\Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'sku'    => $product->sku,
                    'status' => 'error',
                    'error'  => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    // ── Delivery Orders → Storelogix ────────────────────────

    public function sendDeliveryOrder(Order $order): array
    {
        $order->load('items.product');

        $items = [];
        foreach ($order->items as $index => $item) {
            $items[] = [
                'posnumber'       => $index + 1,
                'itemno'          => $item->sku,
                'customitemno'    => $item->product?->ean,
                'itemunit'        => 'ST',
                'quantity'        => (int) $item->quantity,
                'priceperunitnet' => $item->unit_price,
                'articletext'     => $item->name,
            ];
        }

        $payload = [
            'deliveryorder' => [
                'location'       => $this->getLocation(),
                'warehouse'      => $this->getWarehouse(),
                'client'         => $this->getClientName(),
                'source'         => 'Fritzler-SaaS',
                'ordernumber'    => $order->order_number,
                'orderreferenz'  => $order->jtl_order_id,
                'ordertype'      => 'NOR',
                'orderdate'      => $order->ordered_at?->format('Y-m-d'),
                'shippingdate'   => now()->format('Y-m-d'),
                'deliverydate'   => now()->addDays(2)->format('Y-m-d'),
                'customernumber' => $order->customer_name,
                'languagecode'   => 'de',
                'priority'       => 100,

                'trading' => [
                    'traderordernumber' => $order->order_number,
                    'address' => [
                        'name1'       => $order->customer_name,
                        'street'      => $order->customer_address ?? '',
                        'postcode'    => $order->customer_zip ?? '',
                        'city'        => $order->customer_city ?? '',
                        'countrycode' => $order->customer_country ?? 'DE',
                    ],
                ],

                'invoicing' => [
                    'invoicecurrency'    => 'EUR',
                    'invoiceamountnet'   => $order->total_amount,
                    'invoiceamountgross' => $order->total_amount,
                    'address' => [
                        'name1'       => $order->customer_name,
                        'street'      => $order->customer_address ?? '',
                        'postcode'    => $order->customer_zip ?? '',
                        'city'        => $order->customer_city ?? '',
                        'countrycode' => $order->customer_country ?? 'DE',
                    ],
                ],

                'shipping' => [
                    'service'      => $order->shipping_method ?? 'DHL',
                    'shippingdate' => now()->format('Y-m-d'),
                    'address' => [
                        'name1'       => $order->customer_name,
                        'street'      => $order->customer_address ?? '',
                        'postcode'    => $order->customer_zip ?? '',
                        'city'        => $order->customer_city ?? '',
                        'countrycode' => $order->customer_country ?? 'DE',
                    ],
                ],

                'items' => $items,
            ],
        ];

        $result = $this->request(
            'post',
            '/REST/Order/insert_order?Client=' . urlencode($this->getClientName()),
            $payload
        );

        $order->update([
            'storlogix_order_number' => $order->order_number,
            'storlogix_status'       => $result['orderstatus'] ?? 'received',
        ]);

        Log::info('Storelogix delivery order created', [
            'order_id'  => $order->id,
            'response'  => $result,
        ]);

        return $result;
    }

    // ── Purchase Orders → Storelogix ────────────────────────

    public function sendPurchaseOrder(array $data): array
    {
        $payload = [
            'purchaseorder' => [
                'location'          => $this->getLocation(),
                'warehouse'         => $this->getWarehouse(),
                'client'            => $this->getClientName(),
                'purchasenumber'    => $data['purchase_number'],
                'purchasereference' => $data['reference'] ?? null,
                'purchasedate'      => $data['purchase_date'] ?? now()->format('Y-m-d'),
                'deliverydate'      => $data['delivery_date'] ?? null,
                'suppliernumber'    => $data['supplier_number'] ?? null,
                'suppliername'      => $data['supplier_name'] ?? null,
                'hint'              => $data['hint'] ?? null,

                'supplieraddress' => [
                    'name1'       => $data['supplier_name'] ?? '',
                    'street'      => $data['supplier_street'] ?? '',
                    'postcode'    => $data['supplier_zip'] ?? '',
                    'city'        => $data['supplier_city'] ?? '',
                    'countrycode' => $data['supplier_country'] ?? 'DE',
                ],

                'items' => array_map(function ($item, $index) {
                    return [
                        'posnumber'    => $index + 1,
                        'itemno'       => $item['sku'],
                        'customitemno' => $item['ean'] ?? null,
                        'itemunit'     => 'ST',
                        'quantity'     => (int) $item['quantity'],
                        'netweight'    => $item['weight'] ?? null,
                        'bbd'          => $item['bbd'] ?? null,
                        'lot'          => $item['lot'] ?? null,
                    ];
                }, $data['items'], array_keys($data['items'])),
            ],
        ];

        $result = $this->request(
            'post',
            '/REST/PurchaseOrder/insert_purchaseorder?Client=' . urlencode($this->getClientName()),
            $payload
        );

        Log::info('Storelogix purchase order created', [
            'purchase_number' => $data['purchase_number'],
            'response'        => $result,
        ]);

        return $result;
    }

    // ── Incoming Goods → Storelogix ─────────────────────────

    public function sendGoodsReceipt(array $data): array
    {
        $payload = [
            'incominggood' => [
                'location'          => $this->getLocation(),
                'warehouse'         => $this->getWarehouse(),
                'client'            => $this->getClientName(),
                'receiptnumber'     => $data['receipt_number'] ?? null,
                'purchasenumber'    => $data['purchase_number'] ?? null,
                'purchasereference' => $data['reference'] ?? null,
                'deliverydatenumber' => $data['delivery_note_number'] ?? null,
                'deliverydate'      => $data['delivery_date'] ?? now()->format('Y-m-d'),
                'suppliernumber'    => $data['supplier_number'] ?? null,
                'suppliername'      => $data['supplier_name'] ?? null,
                'receiptarea'       => $data['receipt_area'] ?? null,
                'hint'              => $data['hint'] ?? null,

                'supplieraddress' => [
                    'name1'       => $data['supplier_name'] ?? '',
                    'street'      => $data['supplier_street'] ?? '',
                    'postcode'    => $data['supplier_zip'] ?? '',
                    'city'        => $data['supplier_city'] ?? '',
                    'countrycode' => $data['supplier_country'] ?? 'DE',
                ],

                'items' => array_map(function ($item, $index) {
                    return [
                        'posnumber'    => $index + 1,
                        'itemno'       => $item['sku'],
                        'customitemno' => $item['ean'] ?? null,
                        'itemunit'     => 'ST',
                        'quantity'     => (int) $item['quantity'],
                        'netweight'    => $item['weight'] ?? null,
                        'bbd'          => $item['bbd'] ?? null,
                        'lot'          => $item['lot'] ?? null,
                    ];
                }, $data['items'], array_keys($data['items'])),
            ],
        ];

        $result = $this->request(
            'post',
            '/REST/IncomingGoods/create_goodsreceipt?Client=' . urlencode($this->getClientName()),
            $payload
        );

        Log::info('Storelogix goods receipt created', [
            'receipt_number' => $data['receipt_number'] ?? null,
            'response'       => $result,
        ]);

        return $result;
    }

    // ── Returns → Storelogix ────────────────────────────────

    public function sendReturnNotification(array $data): array
    {
        $payload = [
            'returnnotification' => [
                'location'              => $this->getLocation(),
                'warehouse'             => $this->getWarehouse(),
                'client'                => $this->getClientName(),
                'returnadvicenumber'    => $data['return_advice_number'],
                'rmanumber'             => $data['rma_number'] ?? null,
                'customernumber'        => $data['customer_number'] ?? null,
                'originalordernumber'   => $data['original_order_number'] ?? null,
                'originalinvoicenumber' => $data['original_invoice_number'] ?? null,
                'returnreason'          => $data['return_reason'] ?? null,
                'returnreasoncode'      => $data['return_reason_code'] ?? null,
                'returncarrier'         => $data['carrier'] ?? null,
                'returnshippingnumber'  => $data['shipping_number'] ?? null,
                'hint'                  => $data['hint'] ?? null,

                'customeraddress' => [
                    'name1'       => $data['customer_name'] ?? '',
                    'street'      => $data['customer_street'] ?? '',
                    'postcode'    => $data['customer_zip'] ?? '',
                    'city'        => $data['customer_city'] ?? '',
                    'countrycode' => $data['customer_country'] ?? 'DE',
                ],

                'items' => array_map(function ($item, $index) {
                    return [
                        'posnumber'         => $index + 1,
                        'itemno'            => $item['sku'],
                        'itemunit'          => 'ST',
                        'quantity'          => (int) $item['quantity'],
                        'itemreturnreason'  => $item['reason'] ?? null,
                        'serialno'          => $item['serial_number'] ?? null,
                    ];
                }, $data['items'], array_keys($data['items'])),
            ],
        ];

        $result = $this->request(
            'post',
            '/REST/Returns/insert_returns?Client=' . urlencode($this->getClientName()),
            $payload
        );

        Log::info('Storelogix return notification created', [
            'return_advice_number' => $data['return_advice_number'],
            'response'             => $result,
        ]);

        return $result;
    }

    // ── Feedback: Pull from Storelogix ──────────────────────

    public function getOrderStatusUpdates(int $count = 100): array
    {
        return $this->requestGet(
            '/REST/get_response/DeliveryOrderResponse?ResponseCount=' . $count
        );
    }

    public function getOrderCompleted(int $count = 100): array
    {
        return $this->requestGet(
            '/REST/get_response/DeliveryOrderClose?ResponseCount=' . $count
        );
    }

    public function getOrderCancelled(int $count = 100): array
    {
        return $this->requestGet(
            '/REST/get_response/DeliveryOrderResponse?ResponseCount=' . $count
        );
    }

    public function getGoodsReceipts(int $count = 100): array
    {
        return $this->requestGet(
            '/REST/get_response/GoodsReceiptResponse?ResponseCount=' . $count
        );
    }

    public function getReturnsReceipts(int $count = 100): array
    {
        return $this->requestGet(
            '/REST/get_response/ReturnReceiptResponse?ResponseCount=' . $count
        );
    }

    public function getStockChanges(int $count = 100): array
    {
        return $this->requestGet(
            '/REST/get_response/InventorResponse?ResponseCount=' . $count
        );
    }

    public function getPurchaseOrderResponses(int $count = 100): array
    {
        return $this->requestGet(
            '/REST/get_response/PurchaseOrderResponse?ResponseCount=' . $count
        );
    }

    // ── Stock ───────────────────────────────────────────────

    public function getStockWithQuality(string $articleNo = '', bool $includeQualities = false): array
    {
        $params = [
            'Client' => $this->getClientName(),
        ];

        if (!empty($articleNo)) {
            $params['ArticleNo'] = $articleNo;
        }

        if ($includeQualities) {
            $params['IncludeQualities'] = 'true';
        }

        $queryString = http_build_query($params);

        return $this->requestGet('/REST/inventory/stock?' . $queryString);
    }

    public function getStock(int $page = 1, int $pageSize = 100): array
    {
        return $this->getStockWithQuality();
    }

    public function getStockBySku(string $sku): array
    {
        return $this->getStockWithQuality($sku);
    }

    // ── Shipment (Legacy - kept for backwards compatibility) ─

    public function sendShipment(Shipment $shipment): array
    {
        return $this->sendDeliveryOrder($shipment->order);
    }

    public function getShipmentStatus(string $storlogixId): array
    {
        return $this->requestGet("/REST/get_response/DeliveryOrderResponse?ResponseCount=1");
    }

    public function cancelShipment(string $storlogixId): array
    {
        return $this->requestGet("/REST/get_response/DeliveryOrderResponse?ResponseCount=1");
    }

    public function sendReturn(array $returnData): array
    {
        return $this->sendReturnNotification($returnData);
    }

    public function getReturns(int $page = 1, int $pageSize = 100): array
    {
        return $this->getReturnsReceipts();
    }

    public function getReturn(string $returnId): array
    {
        return $this->getReturnsReceipts();
    }

    // ── Webhook Verification ────────────────────────────────

    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
}
