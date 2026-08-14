<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JtlWawiApiService
{
    private const BASE_URL = 'https://api.jtl-cloud.com/erp';
    private const AUTH_URL = 'https://auth.jtl-cloud.com';

    private Tenant $tenant;
    private string $mode; // 'apikey' | 'cloud'

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
        $this->mode = $this->detectMode();
    }

    private function detectMode(): string
    {
        if (!empty($this->tenant->jtl_cloud_client_id)) {
            return 'cloud';
        }
        if (!empty($this->tenant->jtl_api_key)) {
            return 'apikey';
        }
        return 'none';
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function isConfigured(): bool
    {
        return $this->mode !== 'none';
    }

    public function isAuthenticated(): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        return !empty($this->tenant->jtl_api_token)
            && $this->tenant->jtl_api_token_expires_at
            && $this->tenant->jtl_api_token_expires_at->isFuture();
    }

    public function getTenantId(): string
    {
        return $this->mode === 'cloud'
            ? ($this->tenant->jtl_cloud_tenant_id ?? '')
            : ($this->tenant->jtl_tenant_id ?? '');
    }

    // ── Token austauschen ──────────────────────────────────────

    public function exchangeApiKeyForToken(string $apiKey): array
    {
        $response = Http::post(self::AUTH_URL . '/oauth2/token', [
            'grant_type' => 'api_key',
            'client_id'  => $apiKey,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('JTL API Key exchange failed: ' . $response->body());
        }

        $data = $response->json();

        $this->tenant->update([
            'jtl_api_token'            => $data['access_token'],
            'jtl_api_refresh_token'    => $data['refresh_token'] ?? null,
            'jtl_api_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $data;
    }

    public function exchangeCloudCredentialsForToken(): array
    {
        $clientId     = $this->tenant->jtl_cloud_client_id;
        $clientSecret = $this->tenant->jtl_cloud_client_secret;

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode("$clientId:$clientSecret"),
        ])->post(self::AUTH_URL . '/oauth2/token', [
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('JTL Cloud auth failed: ' . $response->body());
        }

        $data = $response->json();

        $this->tenant->update([
            'jtl_api_token'            => $data['access_token'],
            'jtl_api_refresh_token'    => null,
            'jtl_api_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $data;
    }

    public function refreshAccessToken(): array
    {
        if ($this->mode === 'cloud') {
            return $this->exchangeCloudCredentialsForToken();
        }

        if (empty($this->tenant->jtl_api_refresh_token)) {
            throw new \RuntimeException('No refresh token available. Re-authenticate with JTL-Wawi.');
        }

        $response = Http::post(self::AUTH_URL . '/oauth2/token', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->tenant->jtl_api_refresh_token,
            'client_id'     => $this->tenant->jtl_api_key,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('JTL token refresh failed: ' . $response->body());
        }

        $data = $response->json();

        $this->tenant->update([
            'jtl_api_token'            => $data['access_token'],
            'jtl_api_refresh_token'    => $data['refresh_token'] ?? $this->tenant->jtl_api_refresh_token,
            'jtl_api_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $data;
    }

    // ── Token-Logik ───────────────────────────────────────────

    private function ensureToken(): void
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('JTL-Wawi API nicht konfiguriert.');
        }

        if (!$this->isAuthenticated()) {
            $this->refreshAccessToken();
        }
    }

    // ── API-Anfragen ──────────────────────────────────────────

    private function request(string $method, string $endpoint, array $query = []): array
    {
        $this->ensureToken();

        $url = self::BASE_URL . $endpoint;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->tenant->jtl_api_token,
            'x-tenant-id'   => $this->getTenantId(),
            'Accept'        => 'application/json',
        ])->$method($url, $query);

        if ($response->status() === 401) {
            $this->refreshAccessToken();
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->tenant->jtl_api_token,
                'x-tenant-id'   => $this->getTenantId(),
                'Accept'        => 'application/json',
            ])->$method($url, $query);
        }

        if ($response->failed()) {
            throw new \RuntimeException("JTL API error on {$endpoint}: " . $response->body());
        }

        return $response->json();
    }

    // ── Öffentliche API-Methoden ──────────────────────────────

    public function queryItems(int $page = 1, int $pageSize = 100, ?string $search = null): array
    {
        $params = [
            'pageNumber' => $page,
            'pageSize'   => $pageSize,
        ];
        if ($search) {
            $params['searchKeyWord'] = $search;
        }
        return $this->request('get', '/v2/items', $params);
    }

    public function queryStocks(int $page = 1, int $pageSize = 100, ?string $itemId = null): array
    {
        $params = [
            'pageNumber' => $page,
            'pageSize'   => $pageSize,
        ];
        if ($itemId) {
            $params['itemId'] = $itemId;
        }
        return $this->request('get', '/v2/stocks', $params);
    }

    public function querySalesOrders(int $page = 1, int $pageSize = 100, ?string $search = null, ?string $createdSince = null): array
    {
        $params = [
            'pageNumber' => $page,
            'pageSize'   => $pageSize,
        ];
        if ($search) {
            $params['externalOrderNumber'] = $search;
        }
        if ($createdSince) {
            $params['createdSince'] = $createdSince;
        }
        return $this->request('get', '/v2/salesOrders', $params);
    }

    public function queryReturns(int $page = 1, int $pageSize = 100): array
    {
        return $this->request('get', '/v2/returns', [
            'pageNumber' => $page,
            'pageSize'   => $pageSize,
        ]);
    }

    public function queryStocksByItem(string $itemId, int $page = 1, int $pageSize = 100): array
    {
        return $this->request('get', '/v2/stocks', [
            'itemId'     => $itemId,
            'pageNumber' => $page,
            'pageSize'   => $pageSize,
        ]);
    }

    public function updateStock(string $itemId, int $quantity): array
    {
        return $this->request('put', "/v2/stocks/{$itemId}", [
            'quantity' => $quantity,
        ]);
    }

    public function updateOrderStatus(string $orderId, string $status): array
    {
        return $this->request('patch', "/v2/salesOrders/{$orderId}", [
            'status' => $status,
        ]);
    }
}
