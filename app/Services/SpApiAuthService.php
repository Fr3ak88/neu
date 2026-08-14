<?php

namespace App\Services;

use App\Models\AmazonAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SpApiAuthService
{
    /**
     * Gibt einen gültigen Access Token zurück (aus Cache oder neu geholt).
     */
    public function getAccessToken(AmazonAccount $account): string
    {
        $cacheKey = "spapi_token_{$account->id}";

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($account) {
            return $this->fetchAccessToken($account);
        });
    }

    /**
     * Neuen Access Token über LWA holen.
     */
    public function fetchAccessToken(AmazonAccount $account, bool $sandbox = false): string
    {
        $authUrl = 'https://api.amazon.com/auth/o2/token';

        $payload = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $account->lwa_client_id,
            'client_secret' => $account->lwa_client_secret,
            'refresh_token' => $account->lwa_refresh_token,
        ];

        // Body exakt wie Postman aufbauen
        $body = 'grant_type=refresh_token'
            . '&refresh_token=' . rawurlencode($payload['refresh_token'])
            . '&client_id=' . rawurlencode($payload['client_id'])
            . '&client_secret=' . rawurlencode($payload['client_secret']);

        \Log::info('SP-API Curl Request', [
            'url'              => $authUrl,
            'body_length'      => strlen($body),
            'client_id'        => substr($payload['client_id'], 0, 15) . '...',
            'client_id_len'    => strlen($payload['client_id']),
            'secret_len'       => strlen($payload['client_secret']),
            'refresh_len'      => strlen($payload['refresh_token']),
            'refresh_start'    => substr($payload['refresh_token'], 0, 10),
            'body_first_100'   => substr($body, 0, 100),
        ]);

        $ch = curl_init($authUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        \Log::info('SP-API Curl Response', [
            'http_code'  => $httpCode,
            'curl_error' => $curlError,
            'response'   => $raw,
        ]);

        if ($httpCode !== 200) {
            throw new \Exception("Curl {$httpCode}: {$raw}");
        }

        $data = json_decode($raw, true);
        return $data['access_token'];
    }

    /**
     * Verbindung testen — gibt true/false zurück.
     */
    public function testConnection(AmazonAccount $account, string $endpoint = 'https://sellingpartnerapi-eu.amazon.com'): array
    {
        try {
            $token = $this->fetchAccessToken($account);

            $resp = Http::withHeaders([
                'x-amz-access-token' => $token,
                'Content-Type'       => 'application/json',
            ])->get("{$endpoint}/sellers/v1/marketplaceParticipations");

            if ($resp->successful()) {
                $marketplaces = collect($resp->json('payload'))
                    ->pluck('marketplace.id')
                    ->toArray();

                return [
                    'success'      => true,
                    'marketplaces' => $marketplaces,
                    'message'      => 'Verbindung erfolgreich.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Amazon API Fehler: ' . $resp->status() . ' ' . $resp->body(),
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Verbindungsfehler: ' . $e->getMessage(),
            ];
        }
    }
}
