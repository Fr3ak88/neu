<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AmazonAccount extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'name',
        'marketplace_id',
        'seller_id',
        'lwa_client_id',
        'lwa_client_secret',
        'lwa_refresh_token',
        'region',
        'active',
    ];

    protected $casts = [
        'lwa_client_secret' => 'encrypted',
        'lwa_refresh_token' => 'encrypted',
        'token_expires_at'  => 'datetime',
        'active'            => 'boolean',
    ];

    protected $hidden = [
        'lwa_client_secret',
        'lwa_refresh_token',
    ];

    public function fbaShipments(): HasMany
    {
        return $this->hasMany(FbaShipment::class);
    }
}
