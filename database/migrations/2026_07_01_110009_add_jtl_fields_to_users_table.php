<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('jtl_host')->nullable()->after('email');
            $table->string('jtl_port')->nullable()->after('jtl_host');
            $table->string('jtl_database')->nullable()->after('jtl_port');
            $table->string('jtl_username')->nullable()->after('jtl_database');
            $table->text('jtl_password')->nullable()->after('jtl_username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'jtl_host',
                'jtl_port',
                'jtl_database',
                'jtl_username',
                'jtl_password',
            ]);
        });
    }
};
