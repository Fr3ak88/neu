<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->date('bezahldatum')->nullable()->after('status');
            $table->integer('mahnungen_count')->default(0)->after('bezahldatum');
            $table->timestamp('last_mahnung_at')->nullable()->after('mahnungen_count');
            $table->text('mahnung_notizen')->nullable()->after('last_mahnung_at');
        });
    }

    public function down(): void
    {
        Schema::table('rechnungen', function (Blueprint $table) {
            $table->dropColumn(['bezahldatum', 'mahnungen_count', 'last_mahnung_at', 'mahnung_notizen']);
        });
    }
};
