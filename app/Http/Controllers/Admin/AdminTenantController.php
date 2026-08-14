<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class AdminTenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::withCount('users')->latest()->get();

        return view('admin.tenants.index', compact('tenants'));
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('users');

        return view('admin.tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'company' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'zip' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'country' => 'required|string|size:2',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'plan' => 'required|string|in:free,basic,pro,enterprise',
            'ust_id' => 'nullable|string|max:50',
            'steuernummer' => 'nullable|string|max:50',
            'hrb' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:50',
            'bic' => 'nullable|string|max:50',
        ]);

        $tenant->update($data);

        return redirect()->route('admin.tenants.show', $tenant)->with('success', 'Firma aktualisiert.');
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return redirect()->route('admin.tenants.index')->with('success', 'Firma gelöscht.');
    }
}
