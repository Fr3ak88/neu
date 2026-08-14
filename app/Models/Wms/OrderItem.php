<?php

namespace App\Models\Wms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'wms_order_items';
    protected $fillable = [
        'wms_order_id', 'wms_product_id', 'sku', 'name', 'quantity', 'unit_price',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'wms_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'wms_product_id');
    }
}
