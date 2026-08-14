<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email_verified_at');
        });

        DB::table('users')->where('is_superadmin', true)->update(['role' => 'superadmin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_superadmin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_superadmin')->default(false)->after('email_verified_at');
        });

        DB::table('users')->where('role', 'superadmin')->update(['is_superadmin' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
