<?php

namespace App\Models\Wms;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use BelongsToTenant;
    protected $table = 'wms_products';

    protected $fillable = [
        'tenant_id',
        'jtl_id', 'sku', 'name', 'ean',
        'quantity', 'weight', 'length', 'width', 'height',
        'price', 'status', 'last_synced_at',
    ];

    protected $casts = [
        'quantity'       => 'integer',
        'weight'         => 'decimal:2',
        'length'         => 'decimal:2',
        'width'          => 'decimal:2',
        'height'         => 'decimal:2',
        'price'          => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
