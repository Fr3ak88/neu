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
        Schema::create('fba_inbound_splits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('fba_shipment_id')->constrained()->cascadeOnDelete();
    $table->string('amazon_shipment_id');           // FBA15XD9K2P
    $table->string('fulfillment_center_id');        // DEA4
    $table->string('destination_address');
    $table->string('status')->default('working');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fba_inbound_splits');
    }
};
