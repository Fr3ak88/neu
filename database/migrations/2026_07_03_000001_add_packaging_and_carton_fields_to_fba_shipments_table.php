<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fba_shipments', function (Blueprint $table) {
            $table->string('packaging_type')->default('small_parcel')->after('ship_from_phone');
            $table->string('delivery_window_id')->nullable()->after('transportation_option_id');
            $table->text('packing_note')->nullable()->after('packaging_type');
        });
    }

    public function down(): void
    {
        Schema::table('fba_shipments', function (Blueprint $table) {
            $table->dropColumn(['packaging_type', 'delivery_window_id', 'packing_note']);
        });
    }
};
