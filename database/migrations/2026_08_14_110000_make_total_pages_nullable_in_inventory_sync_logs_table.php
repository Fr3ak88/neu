<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_sync_logs', function (Blueprint $table) {
            $table->integer('total_pages')->nullable()->change();
            $table->integer('current_page')->nullable()->change();
            $table->integer('total_skus')->nullable()->change();
            $table->integer('fetched_skus')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_sync_logs', function (Blueprint $table) {
            $table->integer('total_pages')->default(0)->change();
            $table->integer('current_page')->default(0)->change();
            $table->integer('total_skus')->default(0)->change();
            $table->integer('fetched_skus')->default(0)->change();
        });
    }
};
