<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_orders', function (Blueprint $table) {
            $table->string('storlogix_order_number')->nullable()->after('order_number');
            $table->string('delivery_note_number')->nullable()->after('storlogix_order_number');
            $table->string('source')->nullable()->after('delivery_note_number');
            $table->string('storlogix_status')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('wms_orders', function (Blueprint $table) {
            $table->dropColumn(['storlogix_order_number', 'delivery_note_number', 'source', 'storlogix_status']);
        });
    }
};
