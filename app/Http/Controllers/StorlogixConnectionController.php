<?php

namespace App\Http\Controllers;

use App\Services\StorlogixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorlogixConnectionController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        return view('settings.storlogix-connect', ['user' => $user]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'storlogix_api_url'       => 'required|url|max:500',
            'storlogix_api_key'       => 'required|string|max:500',
            'storlogix_api_secret'    => 'nullable|string|max:500',
            'storlogix_client_name'   => 'required|string|max:100',
            'storlogix_location'      => 'nullable|string|max:50',
            'storlogix_warehouse'     => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $tenant = $user->tenant;

        if (!$tenant) {
            return back()->with('error', 'Keine Firma zugeordnet.');
        }

        DB::table('tenants')->where('id', $tenant->id)->update([
            'storlogix_api_url'       => $request->storlogix_api_url,
            'storlogix_api_key'       => $request->storlogix_api_key,
            'storlogix_api_secret'    => $request->filled('storlogix_api_secret')
                ? encrypt($request->storlogix_api_secret)
                : $tenant->storlogix_api_secret,
            'storlogix_client_name'   => $request->storlogix_client_name,
            'storlogix_location'      => $request->storlogix_location,
            'storlogix_warehouse'     => $request->storlogix_warehouse,
        ]);

        return redirect()->route('storlogix-connect.show')
            ->with('success', 'Storelogix Verbindung gespeichert.');
    }

    public function test(Request $request)
    {
        $request->validate([
            'storlogix_api_url'       => 'required|url|max:500',
            'storlogix_api_key'       => 'required|string|max:500',
            'storlogix_api_secret'    => 'nullable|string|max:500',
            'storlogix_client_name'   => 'required|string|max:100',
        ]);

        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Firma zugeordnet.',
            ]);
        }

        DB::table('tenants')->where('id', $tenant->id)->update([
            'storlogix_api_url'     => $request->storlogix_api_url,
            'storlogix_api_key'     => $request->storlogix_api_key,
            'storlogix_api_secret'  => $request->filled('storlogix_api_secret')
                ? encrypt($request->storlogix_api_secret)
                : $tenant->storlogix_api_secret,
            'storlogix_client_name' => $request->storlogix_client_name,
        ]);

        $tenant->refresh();
        $service = new StorlogixService($tenant);

        return response()->json($service->testConnection());
    }
}
