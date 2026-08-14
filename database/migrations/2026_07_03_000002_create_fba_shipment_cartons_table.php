<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fba_shipment_cartons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fba_shipment_id')->constrained()->cascadeOnDelete();
            $table->string('carton_id')->comment('Eindeutige Karton-ID vom Seller');
            $table->decimal('weight_value', 8, 2)->nullable();
            $table->string('weight_unit')->default('KG');
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->string('dimension_unit')->default('CM');
            $table->json('contents')->comment('JSON: [{sku, quantity}]');
            $table->timestamps();

            $table->unique(['fba_shipment_id', 'carton_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fba_shipment_cartons');
    }
};
