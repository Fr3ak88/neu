<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fba_shipment_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('fba_shipment_id')->constrained()->cascadeOnDelete();
    $table->string('sku');
    $table->string('asin')->nullable();
    $table->string('name');
    $table->unsignedInteger('quantity');
    // Pflichtfelder ab Wawi 1.9.6 / SP-API v2024
    $table->string('label_prep_preference')->default('SELLER_LABEL');
    // SELLER_LABEL | AMAZON_LABEL | NO_LABEL
    $table->string('prep_owner')->default('SELLER');
    // SELLER | AMAZON
    $table->string('prep_type')->nullable();
    // ITEM_NO_PREP | ITEM_BOXING | ITEM_BUBBLEWRAP | ITEM_TAPING | ...
    $table->string('prep_category')->nullable();
    // ADULT | FRAGILE | LIQUID | SHARP | SMALL | HANGER | TEXTILE | GRANULAR
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fba_shipment_items');
    }
};
