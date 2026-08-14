<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('amazon_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        // "DE – MeinShop GmbH"
            $table->string('marketplace_id');              // "A1PA6795UKMFR9" (DE)
            $table->string('seller_id');
            $table->text('lwa_client_id');                 // Login with Amazon
            $table->text('lwa_client_secret');             // verschlüsselt (encrypted cast)
            $table->text('lwa_refresh_token');             // verschlüsselt
            $table->string('region')->default('eu-west-1');
            $table->boolean('active')->default(true);
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amazon_accounts');
    }
};
