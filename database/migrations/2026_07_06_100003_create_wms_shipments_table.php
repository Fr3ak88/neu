<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wms_order_id')->constrained()->cascadeOnDelete();
            $table->string('storlogix_id')->nullable()->index();
            $table->string('status')->default('pending');
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->string('package_count')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_shipments');
    }
};
