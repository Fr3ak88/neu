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

class PollShipmentStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(
        public FbaShipment $shipment
    ) {
        $this->onQueue('default');
    }

    public function handle(FbaInboundServiceInterface $service): void
    {
        try {
            $result = $service->getShipmentStatus($this->shipment);

            $planStatus = $result['plan_status'] ?? null;

            // Amazon-Status auf lokale Status mappen
            $newStatus = match (true) {
                $planStatus === 'ACTIVE'                    => FbaShipment::STATUS_PLAN_READY,
                $planStatus === 'SHIPPED'                   => FbaShipment::STATUS_SHIPPED,
                $planStatus === 'READY_TO_SHIP'             => FbaShipment::STATUS_LABEL_READY,
                $planStatus === 'CLOSED'                    => FbaShipment::STATUS_COMPLETED,
                $planStatus === 'CANCELLED'                 => FbaShipment::STATUS_ERROR,
                in_array($planStatus, ['DELETED', null])    => null,
                default => null,
            };

            // Splits aktualisieren
            if (!empty($result['shipments'])) {
                foreach ($result['shipments'] as $s) {
                    $split = $this->shipment->splits()
                        ->where('amazon_shipment_id', $s['shipmentId'])
                        ->first();

                    if ($split) {
                        $split->update(['status' => $s['status'] ?? $split->status]);
                    }
                }
            }

            // Shipment-Status nur updaten wenn sich was geändert hat
            if ($newStatus && $newStatus !== $this->shipment->status) {
                $this->shipment->update(['status' => $newStatus]);
                Log::info("Status-Polling: {$this->shipment->internal_ref} → {$newStatus}");
            }

            \App\Events\ShipmentStatusUpdated::dispatch(
                $this->shipment->id,
                $planStatus
            );
        } catch (\Throwable $e) {
            Log::error(
                "Status-Polling fehlgeschlagen für {$this->shipment->internal_ref}: " . $e->getMessage()
            );
        }
    }
}
