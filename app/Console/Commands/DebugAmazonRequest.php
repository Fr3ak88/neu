<?php

namespace App\Console\Commands;

use App\Models\AmazonAccount;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:debug-amazon-request')]
#[Description('Zeigt den exakten Request an Amazon')]
class DebugAmazonRequest extends Command
{
    public function handle(): int
    {
        $account = AmazonAccount::withoutGlobalScopes()->find(1);

        $clientId = $account->lwa_client_id;
        $clientSecret = $account->lwa_client_secret;
        $refreshToken = $account->lwa_refresh_token;

        $this->info("=== Credentials ===");
        $this->info("client_id: [{$clientId}]");
        $this->info("client_id len: " . strlen($clientId));
        $this->info("secret len: " . strlen($clientSecret));
        $this->info("token len: " . strlen($refreshToken));
        $this->info("token start: " . substr($refreshToken, 0, 15));
        $this->info("has s: prefix: " . (str_starts_with($refreshToken, 's:') ? 'YES' : 'no'));

        $this->newLine();
        $this->info("=== Raw Request Body (copy this to Postman!) ===");

        $body = http_build_query([
            'grant_type'    => 'refresh_token',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ]);

        $this->line($body);

        $this->newLine();
        $this->info("=== Teste mit curl ===");

        $ch = curl_init('https://api.amazon.com/auth/o2/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_USERAGENT      => 'MyApp/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $this->info("HTTP Status: {$httpCode}");
        $this->info("Curl Error: {$curlErr}");
        $this->info("Response: {$response}");

        return $httpCode === 200 ? self::SUCCESS : self::FAILURE;
    }
}
