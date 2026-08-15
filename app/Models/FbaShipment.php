<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FbaShipment extends Model
{
    use BelongsToTenant;
    // Status-Konstanten
    const STATUS_DRAFT          = 'draft';
    const STATUS_PLAN_CREATING  = 'plan_creating';
    const STATUS_PLAN_READY     = 'plan_ready';
    const STATUS_REGISTERED     = 'registered';
    const STATUS_LABEL_READY    = 'label_ready';
    const STATUS_SHIPPED        = 'shipped';
    const STATUS_COMPLETED      = 'completed';
    const STATUS_ERROR          = 'error';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PLAN_CREATING,
        self::STATUS_PLAN_READY,
        self::STATUS_REGISTERED,
        self::STATUS_LABEL_READY,
        self::STATUS_SHIPPED,
        self::STATUS_COMPLETED,
        self::STATUS_ERROR,
    ];

    protected $fillable = [
        'tenant_id',
        'amazon_account_id',
        'internal_ref',
        'jtl_ref',
        'jtl_datum',
        'inbound_plan_id',
        'placement_option_id',
        'transportation_option_id',
        'delivery_window_id',
        'shipment_ids',
        'status',
        'packaging_type',
        'packing_note',
        'source_warehouse',
        'marketplace_id',
        'ship_from_phone',
        'ship_from_name',
        'ship_from_address',
        'ship_from_city',
        'ship_from_zip',
        'ship_from_country',
        'carrier',
        'carrier_tracking',
        'planned_ship_date',
        'error_message',
    ];

    protected $casts = [
        'carrier_tracking'  => 'array',
        'planned_ship_date' => 'date',
        'jtl_datum'         => 'datetime',
        'shipment_ids'      => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->internal_ref)) {
                $year  = now()->format('Y');
                $count = static::whereYear('created_at', $year)
                    ->count() + 1;
                $model->internal_ref = 'UML-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Beziehungen ──────────────────────────────────────────

    public function amazonAccount(): BelongsTo
    {
        return $this->belongsTo(AmazonAccount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FbaShipmentItem::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(FbaInboundSplit::class);
    }

    public function cartons(): HasMany
    {
        return $this->hasMany(FbaShipmentCarton::class);
    }

    public function pallets(): HasMany
    {
        return $this->hasMany(FbaShipmentPallet::class);
    }

    // ── Status-Helpers ───────────────────────────────────────

    public function isDraft(): bool          { return $this->status === self::STATUS_DRAFT; }
    public function isPlanCreating(): bool   { return $this->status === self::STATUS_PLAN_CREATING; }
    public function isPlanReady(): bool      { return $this->status === self::STATUS_PLAN_READY; }
    public function isRegistered(): bool     { return $this->status === self::STATUS_REGISTERED; }
    public function isLabelReady(): bool     { return $this->status === self::STATUS_LABEL_READY; }
    public function isShipped(): bool        { return $this->status === self::STATUS_SHIPPED; }
    public function isCompleted(): bool      { return $this->status === self::STATUS_COMPLETED; }
    public function hasError(): bool         { return $this->status === self::STATUS_ERROR; }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_ERROR,
        ]);
    }

    public function isRegisteredOrLater(): bool
    {
        return in_array($this->status, [
            self::STATUS_REGISTERED,
            self::STATUS_LABEL_READY,
            self::STATUS_SHIPPED,
            self::STATUS_COMPLETED,
        ]);
    }

    // ── Fehlerbehandlung ─────────────────────────────────────

    public function markError(string $message): void
    {
        $this->update([
            'status'        => self::STATUS_ERROR,
            'error_message' => $message,
        ]);
    }

    public function clearError(): void
    {
        $this->update(['error_message' => null]);
    }
}
