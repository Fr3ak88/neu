<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rechnung_auftrag_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rechnung_auftrag_id')->constrained('rechnung_auftraege')->cascadeOnDelete();
            $table->integer('position');
            $table->string('beschreibung');
            $table->decimal('menge', 10, 2)->default(1);
            $table->string('einheit', 10)->default('Stk');
            $table->decimal('einzelpreis', 12, 2);
            $table->timestamps();

            $table->index('rechnung_auftrag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rechnung_auftrag_positions');
    }
};
