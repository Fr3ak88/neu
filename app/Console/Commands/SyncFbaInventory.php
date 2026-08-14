<?php

namespace App\Console\Commands;

use App\Models\AmazonAccount;
use App\Models\InventorySyncLog;
use App\Services\FbaInventoryService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-fba-inventory')]
#[Description('Synchronisiert den FBA-Bestand für alle aktiven Amazon-Konten')]
class SyncFbaInventory extends Command
{
    public function __construct(
        private readonly FbaInventoryService $service
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $accounts = AmazonAccount::where('active', true)->get();

        if ($accounts->isEmpty()) {
            $this->error('Kein aktives Amazon-Konto vorhanden.');
            return self::FAILURE;
        }

        foreach ($accounts as $account) {
            $log = null;
            if (\Schema::hasTable('inventory_sync_logs')) {
                $log = InventorySyncLog::where('amazon_account_id', $account->id)
                    ->whereIn('status', ['pending', 'running'])
                    ->latest()
                    ->first();

                if (!$log) {
                    $log = InventorySyncLog::create([
                        'amazon_account_id' => $account->id,
                        'status'            => 'running',
                        'started_at'        => now(),
                    ]);
                } elseif ($log->status === 'pending') {
                    $log->update(['status' => 'running']);
                }
            }

            $this->info("Synchronisiere: {$account->name}...");

            try {
                $summaries = $this->service->getSummaries($account, [], function ($page, $totalFetched, $hasMore) use ($log) {
                    if ($log) {
                        $log->update([
                            'current_page' => $page,
                            'fetched_skus' => $totalFetched,
                        ]);
                    }
                });

                \Illuminate\Support\Facades\Cache::put(
                    "fba_inventory_{$account->id}",
                    $summaries,
                    now()->addHours(4)
                );

                if ($log) {
                    $log->update([
                        'status'       => 'completed',
                        'total_pages'  => $log->current_page ?? 0,
                        'total_skus'   => count($summaries),
                        'fetched_skus' => count($summaries),
                        'completed_at' => now(),
                    ]);
                }

                $this->info("Fertig: " . count($summaries) . " SKUs synchronisiert.");
            } catch (\Throwable $e) {
                if ($log) {
                    $log->update([
                        'status'        => 'failed',
                        'error_message' => $e->getMessage(),
                        'completed_at'  => now(),
                    ]);
                }

                $this->error("Fehler: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
