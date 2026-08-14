<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rechnung_auftrag_positions', function (Blueprint $table) {
            $table->text('notizen')->nullable()->after('rabatt');
        });

        Schema::table('rechnung_positions', function (Blueprint $table) {
            $table->text('notizen')->nullable()->after('rabatt');
        });
    }

    public function down(): void
    {
        Schema::table('rechnung_auftrag_positions', function (Blueprint $table) {
            $table->dropColumn('notizen');
        });

        Schema::table('rechnung_positions', function (Blueprint $table) {
            $table->dropColumn('notizen');
        });
    }
};
