<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('storlogix_client_name')->nullable()->after('storlogix_api_secret');
            $table->string('storlogix_location')->nullable()->after('storlogix_client_name');
            $table->string('storlogix_warehouse')->nullable()->after('storlogix_location');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['storlogix_client_name', 'storlogix_location', 'storlogix_warehouse']);
        });
    }
};
