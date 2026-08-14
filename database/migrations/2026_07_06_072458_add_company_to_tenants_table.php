<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // company wird bereits in 072208_add_address_to_tenants_table hinzugefügt
        });
    }

    public function down(): void
    {
        //
    }
};
