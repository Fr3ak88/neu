<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'plan',
        'company',
        'street',
        'zip',
        'city',
        'country',
        'phone',
        'email',
        'hrb',
        'ust_id',
        'steuernummer',
        'bank_name',
        'iban',
        'bic',
        'modules',
        'jtl_api_key',
        'jtl_api_token',
        'jtl_api_refresh_token',
        'jtl_api_token_expires_at',
        'jtl_tenant_id',
        'storlogix_api_url',
        'storlogix_api_key',
        'storlogix_api_secret',
        'storlogix_client_name',
        'storlogix_location',
        'storlogix_warehouse',
        'jtl_cloud_client_id',
        'jtl_cloud_client_secret',
        'jtl_cloud_tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'modules' => 'array',
            'jtl_api_token' => 'encrypted',
            'jtl_api_refresh_token' => 'encrypted',
            'jtl_api_token_expires_at' => 'datetime',
            'storlogix_api_secret' => 'encrypted',
            'jtl_cloud_client_id' => 'encrypted',
            'jtl_cloud_client_secret' => 'encrypted',
        ];
    }

    public function hasModule(string $key): bool
    {
        return in_array($key, $this->modules ?? []);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function amazonAccounts(): HasMany
    {
        return $this->hasMany(AmazonAccount::class);
    }

    public function fbaShipments(): HasMany
    {
        return $this->hasMany(FbaShipment::class);
    }

    public function wmsProducts(): HasMany
    {
        return $this->hasMany(\App\Models\Wms\Product::class);
    }

    public function wmsOrders(): HasMany
    {
        return $this->hasMany(\App\Models\Wms\Order::class);
    }

    public function wmsShipments(): HasMany
    {
        return $this->hasMany(\App\Models\Wms\Shipment::class);
    }

    public function wmsReturns(): HasMany
    {
        return $this->hasMany(\App\Models\Wms\ReturnRecord::class);
    }

    public function wmsStockMovements(): HasMany
    {
        return $this->hasMany(\App\Models\Wms\StockMovement::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

}
