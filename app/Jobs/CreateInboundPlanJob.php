<?php

namespace App\Jobs;

use App\Models\FbaShipment;
use App\Services\FbaInboundServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateInboundPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 1200;

    public function __construct(public FbaShipment $shipment) {}

    public function handle(FbaInboundServiceInterface $service): void
    {
        try {
            $service->createInboundPlan($this->shipment);
        } catch (\Throwable $e) {
            Log::error('CreateInboundPlanJob fehlgeschlagen', [
                'shipment_id' => $this->shipment->id,
                'error'       => $e->getMessage(),
            ]);

            $this->shipment->markError($e->getMessage());
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->shipment->markError('Job fehlgeschlagen: ' . $e->getMessage());
    }
}
