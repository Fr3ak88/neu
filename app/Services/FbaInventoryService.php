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
        $maxRetries = 5;

        do {
            $queryParams = $params;
            if ($nextToken) {
                $queryParams['nextToken'] = $nextToken;
            }

            $retryCount = 0;
            $resp = null;

            while ($retryCount <= $maxRetries) {
                $resp = Http::withHeaders([
                    'x-amz-access-token' => $token,
                    'Content-Type'       => 'application/json',
                ])->get($baseUrl, $queryParams);

                if ($resp->status() === 429) {
                    $retryCount++;
                    if ($retryCount > $maxRetries) {
                        throw new \Exception("Amazon SP-API Rate-Limit: {$maxRetries} Retries überschritten für Seite " . ($page + 1));
                    }
                    $sleepTime = min($retryCount * 2, 10);
                    Log::warning("FBA Inventory Rate-Limit (429), Retry {$retryCount}/{$maxRetries}, warte {$sleepTime}s");
                    sleep($sleepTime);
                    continue;
                }

                break;
            }

            $resp->throw();
            $page++;

            $fullResponse = $resp->json();
            $summaries = $fullResponse['payload']['inventorySummaries'] ?? [];
            $allSummaries = array_merge($allSummaries, $summaries);

            $nextToken = $fullResponse['pagination']['nextToken'] ?? null;

            if ($onProgress) {
                $onProgress($page, count($allSummaries), $nextToken !== null);
            }

            if ($nextToken) {
                usleep(500000);
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
