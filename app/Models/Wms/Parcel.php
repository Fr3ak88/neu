<?php

namespace App\Models\Wms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parcel extends Model
{
    protected $table = 'wms_parcels';

    protected $fillable = [
        'wms_shipment_id', 'shipper', 'shipping_service',
        'sscc', 'tracking_number', 'tracking_url',
        'parcel_weight', 'package_type', 'package_sku',
        'package_length', 'package_width', 'package_height',
    ];

    protected $casts = [
        'parcel_weight' => 'decimal:3',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'wms_shipment_id');
    }
}
