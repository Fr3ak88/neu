<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fba_shipments', function (Blueprint $table) {
            $table->string('ship_from_name')->nullable()->after('ship_from_phone');
            $table->string('ship_from_address')->nullable()->after('ship_from_name');
            $table->string('ship_from_city')->nullable()->after('ship_from_address');
            $table->string('ship_from_zip')->nullable()->after('ship_from_city');
            $table->string('ship_from_country')->default('DE')->after('ship_from_zip');
        });
    }

    public function down(): void
    {
        Schema::table('fba_shipments', function (Blueprint $table) {
            $table->dropColumn([
                'ship_from_name',
                'ship_from_address',
                'ship_from_city',
                'ship_from_zip',
                'ship_from_country',
            ]);
        });
    }
};
