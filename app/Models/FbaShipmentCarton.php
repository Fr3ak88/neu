<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FbaShipmentCarton extends Model
{
    protected $fillable = [
        'fba_shipment_id',
        'carton_id',
        'weight_value',
        'weight_unit',
        'length',
        'width',
        'height',
        'dimension_unit',
        'contents',
    ];

    protected $casts = [
        'contents' => 'array',
        'weight_value' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(FbaShipment::class, 'fba_shipment_id');
    }

    public function totalQuantity(): int
    {
        return collect($this->contents ?? [])->sum('quantity');
    }
}
