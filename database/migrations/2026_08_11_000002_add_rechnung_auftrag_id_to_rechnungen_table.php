<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->foreignId('rechnung_auftrag_id')->nullable()->after('id')->constrained('rechnung_auftraege')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->dropForeign(['rechnung_auftrag_id']);
            $table->dropColumn('rechnung_auftrag_id');
        });
    }
};
