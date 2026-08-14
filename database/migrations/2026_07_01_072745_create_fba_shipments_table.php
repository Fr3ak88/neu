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
        Schema::create('fba_shipments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('amazon_account_id')->constrained()->cascadeOnDelete();
    $table->string('internal_ref')->unique();       // UML-2026-0042
    $table->string('inbound_plan_id')->nullable();  // Amazon Plan ID
    $table->string('status')->default('draft');
    // draft | plan_creating | plan_ready | registered | shipped | completed | error
    $table->string('source_warehouse');
    $table->string('marketplace_id');
    $table->string('ship_from_phone')->nullable();
    $table->string('carrier')->nullable();
    $table->json('carrier_tracking')->nullable();
    $table->date('planned_ship_date')->nullable();
    $table->text('error_message')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fba_shipments');
    }
};
