<?php

namespace App\Models\Wms;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRecord extends Model
{
    use BelongsToTenant;
    protected $table = 'wms_returns';

    const STATUS_RECEIVED  = 'received';
    const STATUS_INSPECTED = 'inspected';
    const STATUS_RESTOCKED = 'restocked';
    const STATUS_DISPOSED  = 'disposed';

    protected $fillable = [
        'tenant_id',
        'wms_order_id', 'storlogix_return_id', 'return_number',
        'rma_number', 'return_advice_number',
        'reason', 'status', 'quantity', 'condition',
        'return_quality', 'return_condition_description',
        'item_return_status', 'serial_number',
        'received_at',
    ];

    protected $casts = [
        'quantity'    => 'integer',
        'received_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'wms_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            self::STATUS_RECEIVED  => 'Eingegangen',
            self::STATUS_INSPECTED => 'Geprüft',
            self::STATUS_RESTOCKED => 'Eingelagert',
            self::STATUS_DISPOSED  => 'Entorgt',
            default                => $this->status,
        };
    }

    public function statusClass(): string
    {
        return match($this->status) {
            self::STATUS_RECEIVED  => 'status-pending',
            self::STATUS_INSPECTED => 'status-warn',
            self::STATUS_RESTOCKED => 'status-ok',
            self::STATUS_DISPOSED  => 'status-error',
            default                => 'status-pending',
        };
    }
}
