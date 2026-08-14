<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventorySyncLog extends Model
{
    protected $fillable = [
        'amazon_account_id',
        'status',
        'total_pages',
        'current_page',
        'total_skus',
        'fetched_skus',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function amazonAccount(): BelongsTo
    {
        return $this->belongsTo(AmazonAccount::class);
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function getProgressPercent(): int
    {
        if ($this->total_skus === 0) {
            return 0;
        }
        return (int) round(($this->fetched_skus / $this->total_skus) * 100);
    }
}
