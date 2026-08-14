<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FbaInboundSplit extends Model
{
    protected $fillable = [
        'fba_shipment_id',
        'amazon_shipment_id',
        'fulfillment_center_id',
        'destination_address',
        'status',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(FbaShipment::class, 'fba_shipment_id');
    }
}
