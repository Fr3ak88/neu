<?php

namespace App\Models\Wms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $table = 'wms_shipments';

    const STATUS_PENDING   = 'pending';
    const STATUS_CREATED   = 'created';
    const STATUS_SHIPPED   = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_ERROR     = 'error';

    protected $fillable = [
        'wms_order_id', 'storlogix_id', 'status',
        'tracking_number', 'tracking_url', 'carrier', 'shipping_service',
        'sscc', 'package_count', 'weight',
        'shipped_at', 'shipped_date', 'last_synced_at',
    ];

    protected $casts = [
        'weight'         => 'decimal:2',
        'shipped_at'     => 'datetime',
        'shipped_date'   => 'date',
        'last_synced_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'wms_order_id');
    }

    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class, 'wms_shipment_id');
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING   => 'Ausstehend',
            self::STATUS_CREATED   => 'Erstellt',
            self::STATUS_SHIPPED   => 'Versendet',
            self::STATUS_DELIVERED => 'Zugestellt',
            self::STATUS_ERROR     => 'Fehler',
            default                => $this->status,
        };
    }

    public function statusClass(): string
    {
        return match($this->status) {
            self::STATUS_PENDING   => 'status-pending',
            self::STATUS_CREATED   => 'status-warn',
            self::STATUS_SHIPPED   => 'status-ok',
            self::STATUS_DELIVERED => 'status-ok',
            self::STATUS_ERROR     => 'status-error',
            default                => 'status-pending',
        };
    }
}
