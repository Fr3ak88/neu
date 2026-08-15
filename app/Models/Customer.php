<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'phone',
        'company',
        'street',
        'zip',
        'city',
        'country',
        'notes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([$this->street, $this->zip, $this->city, $this->country]);
        return $parts ? implode(', ', $parts) : null;
    }
}
