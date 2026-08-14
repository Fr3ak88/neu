<?php

namespace App\Console\Commands;

use App\Models\AmazonAccount;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:fix-amazon-credentials')]
#[Description('Liest Credentials aus credentials.json und speichert korrekt')]
class FixAmazonCredentials extends Command
{
    public function handle(): int
    {
        $file = base_path('credentials.json');

        if (!file_exists($file)) {
            $this->error("credentials.json nicht gefunden!");
            $this->info("Erstelle die Datei mit diesem Inhalt:");
            $this->newLine();
            $this->line(json_encode([
                'client_id'     => 'DEINE_CLIENT_ID',
                'client_secret' => 'DEIN_CLIENT_SECRET',
                'refresh_token' => 'DEIN_REFRESH_TOKEN',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($file), true);

        if (!$data || empty($data['client_id']) || empty($data['client_secret']) || empty($data['refresh_token'])) {
            $this->error("Ungültige credentials.json!");
            return self::FAILURE;
        }

        $account = AmazonAccount::withoutGlobalScopes()->find(1);
        if (!$account) {
            $this->error("Kein Amazon Account gefunden.");
            return self::FAILURE;
        }

        $this->info("Account: {$account->name}");
        $this->info("Alt - token Start: " . substr($account->lwa_refresh_token, 0, 10));

        // Korrekt über Eloquent setzen (Cast macht serialize + encrypt)
        $account->lwa_client_id = $data['client_id'];
        $account->lwa_client_secret = $data['client_secret'];
        $account->lwa_refresh_token = $data['refresh_token'];
        $account->save();

        $account = $account->fresh();

        $this->newLine();
        $this->info("Neu - token Start: " . substr($account->lwa_refresh_token, 0, 10));
        $this->info("Neu - token Len:   " . strlen($account->lwa_refresh_token));

        if (str_starts_with($account->lwa_refresh_token, 's:')) {
            $this->error("WARNUNG: Token noch serialisiert!");
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("OK! Credentials korrekt gespeichert.");
        return self::SUCCESS;
    }
}
