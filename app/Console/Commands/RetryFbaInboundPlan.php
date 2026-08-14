<?php

namespace App\Console\Commands;

use App\Jobs\CreateInboundPlanJob;
use App\Models\FbaShipment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fba:retry {shipment_id?}')]
#[Description('Retry a failed FBA inbound plan operation. If no shipment_id given, retries all errored shipments.')]
class RetryFbaInboundPlan extends Command
{
    public function handle(): int
    {
        $id = $this->argument('shipment_id');

        if ($id) {
            $shipment = FbaShipment::findOrFail($id);

            if (!$shipment->hasError()) {
                $this->warn("Shipment #{$id} hat keinen Fehler.");
                return self::FAILURE;
            }

            $shipment->clearError();
            CreateInboundPlanJob::dispatch($shipment);
            $this->info("Retry gestartet für #{$id} ({$shipment->internal_ref})");
            return self::SUCCESS;
        }

        $errored = FbaShipment::where('status', FbaShipment::STATUS_ERROR)->get();

        if ($errored->isEmpty()) {
            $this->info("Keine fehlgeschlagenen Sendungen gefunden.");
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($errored->count());
        $bar->start();

        foreach ($errored as $shipment) {
            $shipment->clearError();
            CreateInboundPlanJob::dispatch($shipment);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("{$errored->count()} Retry(s) gestartet.");

        return self::SUCCESS;
    }
}
