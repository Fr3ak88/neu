<?php

namespace App\Models\Wms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $table = 'wms_stock_movements';

    protected $fillable = [
        'wms_product_id', 'sku', 'change_type',
        'quantity_change', 'reason',
        'location', 'warehouse', 'client',
        'lot', 'bbd', 'changed_at',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'bbd'            => 'date',
        'changed_at'     => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'wms_product_id');
    }
}
