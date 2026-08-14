<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('company')->nullable()->after('plan');
            $table->string('street')->nullable()->after('company');
            $table->string('zip')->nullable()->after('street');
            $table->string('city')->nullable()->after('zip');
            $table->string('country', 2)->default('DE')->after('city');
            $table->string('phone')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['street', 'zip', 'city', 'country', 'phone']);
        });
    }
};
