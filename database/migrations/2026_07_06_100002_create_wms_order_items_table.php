<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wms_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wms_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku');
            $table->string('name')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_order_items');
    }
};
