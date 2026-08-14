<?php

namespace App\Console\Commands;

use App\Jobs\PollShipmentStatusJob;
use App\Models\FbaShipment;
use Illuminate\Console\Command;

class PollFbaShipments extends Command
{
    protected $signature = 'fba:poll-shipments';
    protected $description = 'Poll status for all active FBA shipments from Amazon SP-API';

    public function handle(): int
    {
        $active = FbaShipment::query()
            ->whereIn('status', [
                FbaShipment::STATUS_PLAN_CREATING,
                FbaShipment::STATUS_PLAN_READY,
                FbaShipment::STATUS_REGISTERED,
            ])
            ->whereNotNull('inbound_plan_id')
            ->get();

        if ($active->isEmpty()) {
            $this->info('Keine aktiven Sendungen zum Prüfen.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($active->count());
        $bar->start();

        foreach ($active as $shipment) {
            PollShipmentStatusJob::dispatch($shipment);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("{$active->count()} Status-Abfrage(n) gestartet.");

        return self::SUCCESS;
    }
}
