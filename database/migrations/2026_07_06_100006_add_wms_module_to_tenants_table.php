<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = DB::table('tenants')->get();
        foreach ($tenants as $t) {
            $modules = json_decode($t->modules, true) ?? [];
            if (!in_array('wms', $modules)) {
                $modules[] = 'wms';
                DB::table('tenants')->where('id', $t->id)->update(['modules' => json_encode($modules)]);
            }
            if (!in_array('support', $modules)) {
                $modules[] = 'support';
                DB::table('tenants')->where('id', $t->id)->update(['modules' => json_encode($modules)]);
            }
        }
    }

    public function down(): void
    {
        $tenants = DB::table('tenants')->get();
        foreach ($tenants as $t) {
            $modules = json_decode($t->modules, true) ?? [];
            $modules = array_filter($modules, fn($m) => $m !== 'wms' && $m !== 'support');
            DB::table('tenants')->where('id', $t->id)->update(['modules' => json_encode(array_values($modules))]);
        }
    }
};
