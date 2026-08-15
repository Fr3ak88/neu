<?php

namespace App\Models\Wms;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    use BelongsToTenant;
    protected $table = 'wms_sync_logs';

    protected $fillable = [
        'tenant_id',
        'direction', 'type', 'entity_id',
        'status', 'message', 'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

}
