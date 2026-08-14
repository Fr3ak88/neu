<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'company')) {
                $table->string('company')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('customers', 'street')) {
                $table->string('street')->nullable()->after('company');
            }
            if (!Schema::hasColumn('customers', 'zip')) {
                $table->string('zip')->nullable()->after('street');
            }
            if (!Schema::hasColumn('customers', 'city')) {
                $table->string('city')->nullable()->after('zip');
            }
            if (!Schema::hasColumn('customers', 'country')) {
                $table->string('country', 2)->nullable()->default('DE')->after('city');
            }
            if (!Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')->nullable()->after('country');
            }
            if (!Schema::hasColumn('customers', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'company',
                'street',
                'zip',
                'city',
                'country',
                'notes',
                'email_verified_at',
            ]);
        });
    }
};
