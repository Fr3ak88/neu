<?php

namespace App\Services;

use App\Models\AmazonAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FbaInventoryService
{
    private string $baseUrl = 'https://sellingpartnerapi-eu.amazon.com';

    public function __construct(
        private readonly SpApiAuthService $auth
    ) {}

    private function getBaseUrl(AmazonAccount $account): string
    {
        return match($account->region) {
            'na' => 'https://sellingpartnerapi-na.amazon.com',
            'fe' => 'https://sellingpartnerapi-fe.amazon.com',
            default => 'https://sellingpartnerapi-eu.amazon.com',
        };
    }

    /**
     * FBA Inventory Summaries abrufen (mit Pagination und optionalem Fortschritt-Callback).
     */
    public function getSummaries(AmazonAccount $account, array $options = [], ?callable $onProgress = null): array
    {
        $token = $this->auth->getAccessToken($account);

        $baseUrl = "{$this->getBaseUrl($account)}/fba/inventory/v1/summaries";

        $params = array_merge([
            'details'            => 'true',
            'granularityType'    => 'Marketplace',
            'granularityId'      => $account->marketplace_id,
            'marketplaceIds'     => $account->marketplace_id,
        ], $options);

        $allSummaries = [];
        $nextToken = null;
        $page = 0;

        do {
            $page++;
            $queryParams = $params;
            if ($nextToken) {
                $queryParams['nextToken'] = $nextToken;
            }

            $resp = Http::withHeaders([
                'x-amz-access-token' => $token,
                'Content-Type'       => 'application/json',
            ])->get($baseUrl, $queryParams);

            if ($resp->status() === 429) {
                sleep(1);
                continue;
            }

            $resp->throw();

            $fullResponse = $resp->json();
            $summaries = $fullResponse['payload']['inventorySummaries'] ?? [];
            $allSummaries = array_merge($allSummaries, $summaries);

            $nextToken = $fullResponse['pagination']['nextToken'] ?? null;

            if ($onProgress) {
                $onProgress($page, count($allSummaries), $nextToken !== null);
            }

            if ($nextToken) {
                usleep(250000);
            }

            Log::info("FBA Inventory Seite {$page}: " . count($summaries) . " SKUs (gesamt: " . count($allSummaries) . ")");

        } while ($nextToken);

        Log::info("FBA Inventory abgeschlossen: " . count($allSummaries) . " SKUs für Account {$account->name}");

        return $allSummaries;
    }

    /**
     * Einzelnen SKU-Summary abrufen.
     */
    public function getSummaryBySku(AmazonAccount $account, string $sellerSku): ?array
    {
        $summaries = $this->getSummaries($account, [
            'sellerSkus' => [$sellerSku],
        ]);

        return collect($summaries)->first() ?? null;
    }
}
