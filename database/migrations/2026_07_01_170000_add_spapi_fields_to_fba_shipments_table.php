<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fba_shipments', function (Blueprint $table) {
            $table->string('placement_option_id')->nullable()->after('inbound_plan_id');
            $table->string('transportation_option_id')->nullable()->after('placement_option_id');
            $table->json('shipment_ids')->nullable()->after('transportation_option_id');
        });
    }

    public function down(): void
    {
        Schema::table('fba_shipments', function (Blueprint $table) {
            $table->dropColumn(['placement_option_id', 'transportation_option_id', 'shipment_ids']);
        });
    }
};
