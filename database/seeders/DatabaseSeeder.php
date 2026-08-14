<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Firma',
            'slug' => 'test-firma',
            'plan' => 'pro',
            'company' => 'Test GmbH',
            'street' => 'Musterstr. 1',
            'zip' => '59065',
            'city' => 'Hamm',
            'country' => 'DE',
            'phone' => '+49 2381 12345',
            'modules' => ['fba_shipments', 'wms', 'customers', 'support'],
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@test.de',
            'password' => 'password',
            'role' => 'superadmin',
            'modules' => ['fba_shipments', 'wms', 'customers', 'invoices', 'support'],
        ]);
    }
}
