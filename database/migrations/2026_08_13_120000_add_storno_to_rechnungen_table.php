<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->foreignId('storno_von_id')->nullable()->after('intern_ref')->constrained('rechnungen')->nullOnDelete();
            $table->string('storno_pdf_path')->nullable()->after('pdf_path');
            $table->boolean('ist_storno')->default(false)->after('storno_pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->dropForeign(['storno_von_id']);
            $table->dropColumn(['storno_von_id', 'storno_pdf_path', 'ist_storno']);
        });
    }
};
