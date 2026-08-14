<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('storlogix_api_url')->nullable()->after('jtl_tenant_id');
            $table->text('storlogix_api_key')->nullable()->after('storlogix_api_url');
            $table->text('storlogix_api_secret')->nullable()->after('storlogix_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['storlogix_api_url', 'storlogix_api_key', 'storlogix_api_secret']);
        });
    }
};
