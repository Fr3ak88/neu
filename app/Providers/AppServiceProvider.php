<?php

namespace App\Providers;

use App\Services\FakeInboundService;
use App\Services\FbaInboundService;
use App\Services\FbaInboundServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (env('USE_REAL_SP_API', false)) {
            $this->app->bind(FbaInboundServiceInterface::class, FbaInboundService::class);
        } elseif ($this->app->isLocal()) {
            $this->app->bind(FbaInboundServiceInterface::class, FakeInboundService::class);
        } else {
            $this->app->bind(FbaInboundServiceInterface::class, FbaInboundService::class);
        }

        // Support package removed — no registration needed
    }

    public function boot(): void
    {
        //
    }
}
