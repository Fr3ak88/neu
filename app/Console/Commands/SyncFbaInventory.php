<?php

namespace App\Console\Commands;

use App\Jobs\SyncFbaInventoryJob;
use App\Models\AmazonAccount;
use App\Models\InventorySyncLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-fba-inventory')]
#[Description('Synchronisiert den FBA-Bestand für alle aktiven Amazon-Konten')]
class SyncFbaInventory extends Command
{
    public function handle(): int
    {
        $accounts = AmazonAccount::where('active', true)->get();

        if ($accounts->isEmpty()) {
            $this->error('Kein aktives Amazon-Konto vorhanden.');
            return self::FAILURE;
        }

        foreach ($accounts as $account) {
            if (\Schema::hasTable('inventory_sync_logs')) {
                $running = InventorySyncLog::where('amazon_account_id', $account->id)
                    ->where('status', 'running')
                    ->exists();

                if ($running) {
                    $this->warn("Überspringe {$account->name} - Sync bereits aktiv.");
                    continue;
                }
            }

            $this->info("Synchronisiere: {$account->name}...");
            SyncFbaInventoryJob::dispatch($account);
        }

        $this->info('Alle Sync-Jobs dispatcht.');
        return self::SUCCESS;
    }
}
