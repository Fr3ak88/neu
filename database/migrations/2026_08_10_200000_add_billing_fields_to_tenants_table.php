<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('ust_id')->nullable()->after('phone');
            $table->string('steuernummer')->nullable()->after('ust_id');
            $table->string('bank_name')->nullable()->after('steuernummer');
            $table->string('iban')->nullable()->after('bank_name');
            $table->string('bic')->nullable()->after('iban');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['ust_id', 'steuernummer', 'bank_name', 'iban', 'bic']);
        });
    }
};
