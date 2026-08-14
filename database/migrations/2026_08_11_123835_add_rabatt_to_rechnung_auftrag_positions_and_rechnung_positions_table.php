<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rechnung_auftrag_positions', function (Blueprint $table) {
            $table->decimal('rabatt', 5, 2)->default(0)->after('steuersatz');
        });

        Schema::table('rechnung_positions', function (Blueprint $table) {
            $table->decimal('rabatt', 5, 2)->default(0)->after('steuersatz');
        });
    }

    public function down(): void
    {
        Schema::table('rechnung_auftrag_positions', function (Blueprint $table) {
            $table->dropColumn('rabatt');
        });

        Schema::table('rechnung_positions', function (Blueprint $table) {
            $table->dropColumn('rabatt');
        });
    }
};
