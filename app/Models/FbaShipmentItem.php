<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FbaShipmentItem extends Model
{
    protected $fillable = [
        'fba_shipment_id',
        'sku',
        'asin',
        'name',
        'quantity',
        'prep_type',
        'prep_category',
        'prep_instruction',
        'prep_owner',
        'label_owner',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(FbaShipment::class, 'fba_shipment_id');
    }
}
