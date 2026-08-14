<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rechnung_auftraege', function (Blueprint $table) {
            $table->string('typ')->default('einmalig')->after('bezeichnung');
        });
    }

    public function down(): void
    {
        Schema::table('rechnung_auftraege', function (Blueprint $table) {
            $table->dropColumn('typ');
        });
    }
};
