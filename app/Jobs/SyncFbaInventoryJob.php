<?php

namespace App\Jobs;

use App\Models\AmazonAccount;
use App\Models\InventorySyncLog;
use App\Services\FbaInventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncFbaInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(
        public AmazonAccount $account
    ) {
        $this->onQueue('default');
    }

    public function handle(FbaInventoryService $service): void
    {
        $log = null;
        if (\Schema::hasTable('inventory_sync_logs')) {
            $log = InventorySyncLog::where('amazon_account_id', $this->account->id)
                ->whereIn('status', ['pending', 'running'])
                ->latest()
                ->first();

            if ($log && $log->status === 'pending') {
                $log->update(['status' => 'running']);
            }

            if (!$log) {
                $log = InventorySyncLog::create([
                    'amazon_account_id' => $this->account->id,
                    'status'            => 'running',
                    'started_at'        => now(),
                ]);
            }
        }

        try {
            $summaries = $service->getSummaries($this->account, [], function ($page, $totalFetched, $hasMore) use ($log) {
                if ($log) {
                    $log->update([
                        'current_page' => $page,
                        'fetched_skus' => $totalFetched,
                    ]);
                }
            });

            \Illuminate\Support\Facades\Cache::put(
                "fba_inventory_{$this->account->id}",
                $summaries,
                now()->addHours(4)
            );

            if ($log) {
                $log->update([
                    'status'       => 'completed',
                    'total_pages'  => $log->current_page,
                    'total_skus'   => count($summaries),
                    'fetched_skus' => count($summaries),
                    'completed_at' => now(),
                ]);
            }

            \App\Events\InventorySyncCompleted::dispatch(count($summaries));

            \Illuminate\Support\Facades\Log::info(
                "FBA Inventory synchronisiert: " . count($summaries) . " SKUs für {$this->account->name}"
            );
        } catch (\Throwable $e) {
            if ($log) {
                $log->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at'  => now(),
                ]);
            }

            throw $e;
        }
    }
}
