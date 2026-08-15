<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (is_null($model->tenant_id) && auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        if (static::class !== User::class) {
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (auth()->check() && !auth()->user()->isSuperadmin()) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
                }
            });
        }
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForCurrentTenant(Builder $query): Builder
    {
        if (auth()->check() && !auth()->user()->isSuperadmin()) {
            return $query->where('tenant_id', auth()->user()->tenant_id);
        }

        return $query;
    }

    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
