<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventorySyncCompleted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $skuCount
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('inventory'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'sku_count' => $this->skuCount,
            'message'   => "Inventory-Sync abgeschlossen: {$this->skuCount} SKUs",
        ];
    }
}
