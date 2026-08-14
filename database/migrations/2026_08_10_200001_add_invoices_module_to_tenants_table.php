<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $modules = json_decode($tenant->modules, true) ?? [];
            if (!in_array('invoices', $modules)) {
                $modules[] = 'invoices';
                DB::table('tenants')->where('id', $tenant->id)->update(['modules' => json_encode($modules)]);
            }
        }
    }

    public function down(): void
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $modules = json_decode($tenant->modules, true) ?? [];
            $modules = array_values(array_filter($modules, fn($m) => $m !== 'invoices'));
            DB::table('tenants')->where('id', $tenant->id)->update(['modules' => json_encode($modules)]);
        }
    }
};
