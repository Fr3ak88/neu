<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('company')->nullable(false)->change();
            $table->string('street')->nullable(false)->change();
            $table->string('zip')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('company')->nullable()->change();
            $table->string('street')->nullable()->change();
            $table->string('zip')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });
    }
};
