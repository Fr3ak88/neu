<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fba_shipments', function (Blueprint $table) {
            $table->foreignId('amazon_account_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fba_shipments', function (Blueprint $table) {
            $table->foreignId('amazon_account_id')->constrained()->cascadeOnDelete()->change();
        });
    }
};
