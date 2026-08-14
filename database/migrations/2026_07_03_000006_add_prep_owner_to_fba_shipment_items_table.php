<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fba_shipment_items', function (Blueprint $table) {
            $table->string('prep_owner')->nullable()->after('label_owner');
        });
    }

    public function down(): void
    {
        Schema::table('fba_shipment_items', function (Blueprint $table) {
            $table->dropColumn('prep_owner');
        });
    }
};
