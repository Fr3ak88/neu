<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rechnungen', function (Blueprint $table) {
            $table->id();
            $table->string('rechnungsnummer')->unique();
            $table->string('kunde_name')->nullable();
            $table->string('kunde_firma')->nullable();
            $table->string('kunde_email')->nullable();
            $table->string('kunde_strasse')->nullable();
            $table->string('kunde_plz')->nullable();
            $table->string('kunde_ort')->nullable();
            $table->string('kunde_land', 2)->nullable()->default('DE');
            $table->string('kunde_steuernummer')->nullable();
            $table->date('datum');
            $table->date('faelligkeitsdatum');
            $table->date('leistungsdatum')->nullable();
            $table->string('status')->default('draft');
            $table->string('waehrung', 3)->default('EUR');
            $table->decimal('nettobetrag', 12, 2)->default(0);
            $table->decimal('steuerbetrag', 12, 2)->default(0);
            $table->decimal('bruttobetrag', 12, 2)->default(0);
            $table->decimal('steuersatz', 5, 2)->default(19.00);
            $table->string('ust_id')->nullable();
            $table->string('steuernummer')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('iban')->nullable();
            $table->string('bic')->nullable();
            $table->text('notizen')->nullable();
            $table->string('intern_ref')->nullable();
            $table->string('jtl_ref')->nullable();
            $table->string('amazon_order_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rechnungen');
    }
};
