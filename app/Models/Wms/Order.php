<?php

namespace App\Models\Wms;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToTenant;
    protected $table = 'wms_orders';

    const STATUS_NEW        = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED    = 'shipped';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_CANCELLED  = 'cancelled';

    const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_PROCESSING,
        self::STATUS_SHIPPED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'tenant_id',
        'jtl_order_id', 'order_number', 'storlogix_order_number',
        'customer_name', 'customer_address', 'customer_zip', 'customer_city', 'customer_country',
        'status', 'storlogix_status', 'total_amount', 'shipping_method',
        'delivery_note_number', 'source',
        'ordered_at', 'last_synced_at',
    ];

    protected $casts = [
        'total_amount'   => 'decimal:2',
        'ordered_at'     => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'wms_order_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRecord::class, 'wms_order_id');
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            self::STATUS_NEW        => 'Neu',
            self::STATUS_PROCESSING => 'In Bearbeitung',
            self::STATUS_SHIPPED    => 'Versendet',
            self::STATUS_COMPLETED  => 'Abgeschlossen',
            self::STATUS_CANCELLED  => 'Storniert',
            default                 => $this->status,
        };
    }

    public function statusClass(): string
    {
        return match($this->status) {
            self::STATUS_NEW        => 'status-pending',
            self::STATUS_PROCESSING => 'status-warn',
            self::STATUS_SHIPPED    => 'status-ok',
            self::STATUS_COMPLETED  => 'status-ok',
            self::STATUS_CANCELLED  => 'status-error',
            default                 => 'status-pending',
        };
    }
}
