<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_returns', function (Blueprint $table) {
            $table->string('rma_number')->nullable()->after('return_number');
            $table->string('return_advice_number')->nullable()->after('rma_number');
            $table->string('return_quality')->nullable()->after('condition');
            $table->string('return_condition_description')->nullable()->after('return_quality');
            $table->string('item_return_status')->nullable()->after('return_condition_description');
            $table->string('serial_number')->nullable()->after('item_return_status');
        });
    }

    public function down(): void
    {
        Schema::table('wms_returns', function (Blueprint $table) {
            $table->dropColumn([
                'rma_number', 'return_advice_number', 'return_quality',
                'return_condition_description', 'item_return_status', 'serial_number',
            ]);
        });
    }
};
