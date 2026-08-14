<?php

namespace App\Models\Wms;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $table = 'wms_sync_logs';

    protected $fillable = [
        'direction', 'type', 'entity_id',
        'status', 'message', 'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

}
