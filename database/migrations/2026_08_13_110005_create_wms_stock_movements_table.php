<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wms_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->nullable()->index();
            $table->string('change_type')->nullable(); // DEL, ADD, CHG, INV
            $table->integer('quantity_change')->default(0);
            $table->string('reason')->nullable();
            $table->string('location')->nullable();
            $table->string('warehouse')->nullable();
            $table->string('client')->nullable();
            $table->string('lot')->nullable();
            $table->date('bbd')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_stock_movements');
    }
};
