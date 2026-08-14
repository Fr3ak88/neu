<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('jtl_api_key')->nullable()->after('modules');
            $table->text('jtl_api_token')->nullable()->after('jtl_api_key');
            $table->text('jtl_api_refresh_token')->nullable()->after('jtl_api_token');
            $table->timestamp('jtl_api_token_expires_at')->nullable()->after('jtl_api_refresh_token');
            $table->string('jtl_tenant_id')->nullable()->after('jtl_api_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'jtl_api_key', 'jtl_api_token', 'jtl_api_refresh_token',
                'jtl_api_token_expires_at', 'jtl_tenant_id',
            ]);
        });
    }
};
