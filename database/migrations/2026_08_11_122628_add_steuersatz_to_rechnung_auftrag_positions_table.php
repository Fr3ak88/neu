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
        Schema::table('rechnung_auftrag_positions', function (Blueprint $table) {
            $table->decimal('steuersatz', 5, 2)->default(19.00)->after('einzelpreis');
        });
    }

    public function down(): void
    {
        Schema::table('rechnung_auftrag_positions', function (Blueprint $table) {
            $table->dropColumn('steuersatz');
        });
    }
};
