<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_products', function (Blueprint $table) {
            $table->id();
            $table->string('jtl_id')->nullable()->index();
            $table->string('sku')->index();
            $table->string('name');
            $table->string('ean')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_products');
    }
};
