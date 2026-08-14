<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_shipments', function (Blueprint $table) {
            $table->string('tracking_url')->nullable()->after('tracking_number');
            $table->string('shipping_service')->nullable()->after('carrier');
            $table->string('sscc')->nullable()->after('shipping_service');
            $table->timestamp('shipped_date')->nullable()->after('shipped_at');
        });
    }

    public function down(): void
    {
        Schema::table('wms_shipments', function (Blueprint $table) {
            $table->dropColumn(['tracking_url', 'shipping_service', 'sscc', 'shipped_date']);
        });
    }
};
