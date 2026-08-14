<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_orders', function (Blueprint $table) {
            $table->id();
            $table->string('jtl_order_id')->nullable()->index();
            $table->string('order_number')->index();
            $table->string('customer_name');
            $table->text('customer_address')->nullable();
            $table->string('customer_zip')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_country', 2)->default('DE');
            $table->string('status')->default('new');
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->string('shipping_method')->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_orders');
    }
};
