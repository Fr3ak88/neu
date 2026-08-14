<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wms_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('storlogix_return_id')->nullable()->index();
            $table->string('return_number')->nullable();
            $table->string('reason')->nullable();
            $table->string('status')->default('received');
            $table->integer('quantity')->default(1);
            $table->string('condition')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_returns');
    }
};
