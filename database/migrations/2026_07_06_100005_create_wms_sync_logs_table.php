<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('direction'); // in / out
            $table->string('type'); // product / order / shipment / return
            $table->string('entity_id')->nullable();
            $table->string('status'); // success / error
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_sync_logs');
    }
};
