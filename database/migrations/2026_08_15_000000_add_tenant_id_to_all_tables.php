<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
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
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
