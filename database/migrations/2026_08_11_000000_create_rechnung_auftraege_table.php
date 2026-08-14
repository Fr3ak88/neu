<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rechnung_auftraege', function (Blueprint $table) {
            $table->id();
            $table->string('auftragsnummer')->unique();
            $table->string('bezeichnung');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('kunde_name')->nullable();
            $table->string('kunde_firma')->nullable();
            $table->string('kunde_email')->nullable();
            $table->string('kunde_strasse')->nullable();
            $table->string('kunde_plz')->nullable();
            $table->string('kunde_ort')->nullable();
            $table->string('kunde_land', 2)->nullable()->default('DE');
            $table->string('kunde_steuernummer')->nullable();
            $table->enum('intervall', ['woechentlich', 'monatlich', 'vierteljaehrlich', 'jaehrlich']);
            $table->integer('faelligkeit_tage')->default(30);
            $table->decimal('steuersatz', 5, 2)->default(19.00);
            $table->text('notizen')->nullable();
            $table->date('startdatum');
            $table->date('enddatum')->nullable();
            $table->date('naechste_erstellung');
            $table->date('letzte_erstellung')->nullable();
            $table->integer('erstellt_count')->default(0);
            $table->boolean('aktiv')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rechnung_auftraege');
    }
};
