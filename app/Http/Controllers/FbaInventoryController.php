<?php

namespace App\Http\Controllers;

use App\Models\AmazonAccount;
use App\Models\InventorySyncLog;
use App\Services\FbaInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FbaInventoryController extends Controller
{
    public function index(Request $request)
    {
        $account = AmazonAccount::where('active', true)->first();

        if (!$account) {
            return redirect()->route('fba-shipments.index')
                ->with('error', 'Kein aktives Amazon-Konto vorhanden.');
        }

        $cacheKey = "fba_inventory_{$account->id}";
        $summaries = \Cache::get($cacheKey);

        if ($request->has('sku') && $request->sku) {
            $service = app(FbaInventoryService::class);
            try {
                $summaries = $service->getSummaries($account, [
                    'sellerSkus' => [$request->sku],
                ]);
            } catch (\Throwable $e) {
                \Session::flash('error', 'Inventory-Abruf fehlgeschlagen: ' . $e->getMessage());
            }
        } elseif ($summaries === null) {
            $summaries = [];
            \Session::flash('error', 'Noch keine Bestandsdaten vorhanden. Bitte zuerst "Bestand synchronisieren" klicken.');
        }

        $activeSync = null;
        if (\Schema::hasTable('inventory_sync_logs')) {
            $activeSync = InventorySyncLog::where('amazon_account_id', $account->id)
                ->latest()
                ->first();
        }

        return view('fba-inventory.index', [
            'summaries'  => $summaries,
            'account'    => $account,
            'activeSync' => $activeSync,
        ]);
    }

    public function sync()
    {
        $account = AmazonAccount::where('active', true)->first();

        if (!$account) {
            return redirect()->route('fba-shipments.index')
                ->with('error', 'Kein aktives Amazon-Konto vorhanden.');
        }

        if (\Schema::hasTable('inventory_sync_logs')) {
            $running = InventorySyncLog::where('amazon_account_id', $account->id)
                ->whereIn('status', ['pending', 'running'])
                ->exists();

            if ($running) {
                $stale = InventorySyncLog::where('amazon_account_id', $account->id)
                    ->whereIn('status', ['pending', 'running'])
                    ->where('created_at', '<', now()->subMinutes(30))
                    ->update(['status' => 'failed', 'error_message' => 'Abgebrochen: Neue Synchronisation gestartet', 'completed_at' => now()]);

                if (!$stale) {
                    return redirect()->route('fba-inventory.index')
                        ->with('error', 'Synchronisation läuft bereits.');
                }
            }

            InventorySyncLog::create([
                'amazon_account_id' => $account->id,
                'status'            => 'pending',
                'started_at'        => now(),
            ]);
        }

        \App\Jobs\SyncFbaInventoryJob::dispatch($account);

        return redirect()->route('fba-inventory.index')
            ->with('success', 'Inventory-Synchronisation gestartet…')
            ->withQuery(['syncing' => '1']);
    }

    public function syncProgress()
    {
        $account = AmazonAccount::where('active', true)->first();

        if (!$account || !\Schema::hasTable('inventory_sync_logs')) {
            return response()->json(['status' => 'none']);
        }

        $log = InventorySyncLog::where('amazon_account_id', $account->id)
            ->latest()
            ->first();

        if (!$log) {
            return response()->json(['status' => 'none']);
        }

        return response()->json([
            'status'       => $log->status,
            'current_page' => $log->current_page,
            'total_pages'  => $log->total_pages,
            'fetched_skus' => $log->fetched_skus,
            'total_skus'   => $log->total_skus,
            'started_at'   => $log->started_at?->timestamp,
            'error'        => $log->error_message,
        ]);
    }
}
