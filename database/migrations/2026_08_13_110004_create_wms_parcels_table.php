<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wms_shipment_id')->constrained()->cascadeOnDelete();
            $table->string('shipper')->nullable();
            $table->string('shipping_service')->nullable();
            $table->string('sscc')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->decimal('parcel_weight', 8, 3)->nullable();
            $table->string('package_type')->nullable();
            $table->string('package_sku')->nullable();
            $table->integer('package_length')->nullable();
            $table->integer('package_width')->nullable();
            $table->integer('package_height')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_parcels');
    }
};
