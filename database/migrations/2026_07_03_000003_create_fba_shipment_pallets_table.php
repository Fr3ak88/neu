<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fba_shipment_pallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fba_shipment_id')->constrained()->cascadeOnDelete();
            $table->string('pallet_id')->comment('Eindeutige Paletten-ID');
            $table->decimal('weight_value', 8, 2)->nullable();
            $table->string('weight_unit')->default('KG');
            $table->decimal('length', 8, 2)->nullable()->comment('Länge in cm');
            $table->decimal('width', 8, 2)->nullable()->comment('Breite in cm');
            $table->decimal('height', 8, 2)->nullable()->comment('Höhe in cm');
            $table->string('dimension_unit')->default('CM');
            $table->boolean('is_stacked')->default(false);
            $table->json('carton_ids')->nullable()->comment('JSON: Array von Carton-IDs auf dieser Palette');
            $table->timestamps();

            $table->unique(['fba_shipment_id', 'pallet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fba_shipment_pallets');
    }
};
