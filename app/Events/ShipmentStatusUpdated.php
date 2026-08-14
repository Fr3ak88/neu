<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// app/Events/ShipmentStatusUpdated.php

class ShipmentStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $tenantId,
        public int    $shipmentId,
        public string $status,
        public ?string $error = null
    ) {}

    // Privater Channel pro Tenant → kein Cross-Tenant-Datenleck
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->tenantId}.shipments"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'shipment_id' => $this->shipmentId,
            'status'      => $this->status,
            'error'       => $this->error,
        ];
    }
}
