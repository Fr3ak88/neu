<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amazon_account_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->integer('total_pages')->default(0);
            $table->integer('current_page')->default(0);
            $table->integer('total_skus')->default(0);
            $table->integer('fetched_skus')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_sync_logs');
    }
};
