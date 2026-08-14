<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fba_shipments', function (Blueprint $table) {
            $table->string('jtl_ref')->nullable()->after('internal_ref');
            $table->dateTime('jtl_datum')->nullable()->after('jtl_ref');
        });
    }

    public function down(): void
    {
        Schema::table('fba_shipments', function (Blueprint $table) {
            $table->dropColumn(['jtl_ref', 'jtl_datum']);
        });
    }
};
