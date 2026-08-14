<?php

namespace App\Console\Commands;

use App\Models\AmazonAccount;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:set-amazon-credentials {--id= : Account ID (default 1)} {--client-id= : LWA Client ID} {--client-secret= : LWA Client Secret} {--refresh-token= : LWA Refresh Token}')]
#[Description('Speichert Amazon SP-API Credentials korrekt (verschlüsselt)')]
class SetAmazonCredentials extends Command
{
    public function handle(): int
    {
        $id = $this->option('id') ?? 1;
        $clientId = $this->option('client-id');
        $clientSecret = $this->option('client-secret');
        $refreshToken = $this->option('refresh-token');

        if (!$clientId || !$clientSecret || !$refreshToken) {
            $this->error('Alle drei Optionen müssen angegeben werden:');
            $this->error('php artisan app:set-amazon-credentials --client-id="DEINE_ID" --client-secret="DEIN_SECRET" --refresh-token="DEIN_TOKEN"');
            return self::FAILURE;
        }

        $account = AmazonAccount::withoutGlobalScopes()->find($id);

        if (!$account) {
            $this->error("Account mit ID {$id} nicht gefunden.");
            return self::FAILURE;
        }

        $this->info("Aktualisiere Account: {$account->name} (ID: {$account->id})");
        $this->info("Alt - client_id: " . strlen($account->lwa_client_id) . " Zeichen");
        $this->info("Alt - secret: " . strlen($account->lwa_client_secret) . " Zeichen");
        $this->info("Alt - token: " . strlen($account->lwa_refresh_token) . " Zeichen");

        $account->lwa_client_id = $clientId;
        $account->lwa_client_secret = $clientSecret;
        $account->lwa_refresh_token = $refreshToken;
        $account->save();

        $account->refresh();

        $this->newLine();
        $this->info("Neu - client_id: " . strlen($account->lwa_client_id) . " Zeichen");
        $this->info("Neu - secret: " . strlen($account->lwa_client_secret) . " Zeichen");
        $this->info("Neu - token: " . strlen($account->lwa_refresh_token) . " Zeichen");
        $this->info("Token-Start: " . substr($account->lwa_refresh_token, 0, 10));

        if (str_starts_with($account->lwa_refresh_token, 's:')) {
            $this->error("WARNUNG: Token ist noch serialisiert!");
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Credentials korrekt gespeichert!");
        return self::SUCCESS;
    }
}
