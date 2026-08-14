<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fba_shipment_items', function (Blueprint $table) {
            $table->string('label_owner')->nullable()->after('prep_category')->comment('SELLER_LABEL oder AMAZON_LABEL');
            $table->string('prep_instruction')->nullable()->after('label_owner')->comment('z.B. polybagging, shrink-wrapping');
        });
    }

    public function down(): void
    {
        Schema::table('fba_shipment_items', function (Blueprint $table) {
            $table->dropColumn(['label_owner', 'prep_instruction']);
        });
    }
};
