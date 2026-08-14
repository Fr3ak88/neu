<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('street')->nullable();
            $table->string('zip')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->nullable()->default('DE');
            $table->text('notes')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
