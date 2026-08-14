<?php

namespace App\Console\Commands;

use App\Models\AmazonAccount;
use App\Services\SpApiAuthService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:test-sp-api-connection {--id= : Amazon Account ID} {--us : US-Endpoint verwenden}')]
#[Description('Testet die SP-API Verbindung für ein Amazon Account')]
class TestSpApiConnection extends Command
{
    public function handle(SpApiAuthService $auth): int
    {
        $id = $this->option('id');
        $useUs = $this->option('us');
        $endpoint = $useUs ? 'https://sellingpartnerapi-na.amazon.com' : 'https://sellingpartnerapi-eu.amazon.com';

        $this->info("Endpoint: " . ($useUs ? 'US (NA)' : 'EU'));

        if ($id) {
            $accounts = AmazonAccount::where('id', $id)->get();
        } else {
            $accounts = AmazonAccount::all();
        }

        if ($accounts->isEmpty()) {
            $this->error('Keine Amazon Accounts gefunden.');
            return self::FAILURE;
        }

        foreach ($accounts as $account) {
            $this->newLine();
            $this->info("=== Account: {$account->name} (ID: {$account->id}) ===");
            $this->info("Marketplace: {$account->marketplace_id}");
            $this->info("Region: {$account->region}");
            $this->info("Active: " . ($account->active ? 'Ja' : 'Nein'));

            // Prüfe Längen der Credentials (ohne Werte preiszugeben)
            $this->newLine();
            $this->info("Credential-Check:");
            $this->info("  lwa_client_id: " . (strlen($account->lwa_client_id) . " Zeichen"));
            $this->info("  lwa_client_secret: " . (strlen($account->lwa_client_secret) . " Zeichen"));
            $this->info("  lwa_refresh_token: " . (strlen($account->lwa_refresh_token) . " Zeichen"));

            // Teste Token-Abruf
            $this->newLine();
            $this->info("Teste Token-Abruf...");

            try {
                $token = $auth->fetchAccessToken($account);
                $this->info("✓ Token erfolgreich abgerufen (" . strlen($token) . " Zeichen)");
            } catch (\Throwable $e) {
                $this->error("✗ Token-Abruf fehlgeschlagen:");
                $this->error("  " . $e->getMessage());

                // Detaillierte Fehleranalyse
                if (str_contains($e->getMessage(), 'invalid_client')) {
                    $this->newLine();
                    $this->error("Ursache: Client Authentication Failed");
                    $this->error("Mögliche Ursachen:");
                    $this->error("  1. Client ID oder Client Secret ist ungültig");
                    $this->error("  2. App wurde im SPS Portal noch nicht aktiviert");
                    $this->error("  3. Credentials gehören zu einer anderen App");
                    $this->error("  4. Refresh Token wurde widerrufen oder ist abgelaufen");
                }

                return self::FAILURE;
            }

            // Teste API-Call
            $this->info("Teste API-Call (marketplaceParticipations)...");
            try {
                $result = $auth->testConnection($account, $endpoint);
                if ($result['success']) {
                    $this->info("✓ Verbindung erfolgreich!");
                    $this->info("  Marktplätze: " . implode(', ', $result['marketplaces']));
                } else {
                    $this->error("✗ API-Call fehlgeschlagen: " . $result['message']);
                }
            } catch (\Throwable $e) {
                $this->error("✗ API-Call fehlgeschlagen: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
