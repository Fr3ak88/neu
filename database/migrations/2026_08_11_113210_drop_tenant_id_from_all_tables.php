<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'rechnungen',
            'rechnung_auftraege',
            'customers',
            'fba_shipments',
            'amazon_accounts',
            'wms_orders',
            'wms_products',
            'wms_returns',
            'wms_shipments',
            'wms_sync_logs',
            'support_tickets',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            // Drop foreign key if it exists
            $fks = DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = 'tenant_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$table]);

            foreach ($fks as $fk) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }
    }

    public function down(): void
    {
        //
    }
};
