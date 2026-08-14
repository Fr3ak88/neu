<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Tenant;

class JtlSettingsController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        $tenant = Tenant::first();

        $jtl = new \App\Services\JtlWawiApiService($tenant);
        $jtlConfigured = $jtl->isConfigured();
        $jtlAuthenticated = $jtl->isAuthenticated();
        $jtlMode = $jtl->getMode();

        return view('settings.jtl-settings', compact('user', 'tenant', 'jtlConfigured', 'jtlAuthenticated', 'jtlMode'));
    }

    public function saveDb(Request $request)
    {
        $request->validate([
            'jtl_host'     => 'required|string',
            'jtl_port'     => 'required|string',
            'jtl_database' => 'required|string',
            'jtl_username' => 'required|string',
            'jtl_password' => 'nullable|string',
        ]);

        $user = $request->user();
        $user->jtl_host     = $request->jtl_host;
        $user->jtl_port     = $request->jtl_port;
        $user->jtl_database = $request->jtl_database;
        $user->jtl_username = $request->jtl_username;

        if ($request->filled('jtl_password')) {
            $user->jtl_password = Crypt::encryptString($request->jtl_password);
        }

        $user->save();

        return redirect()->route('jtl.settings')->with('success', 'Datenbank-Verbindung gespeichert.');
    }

    public function testDb(Request $request)
    {
        $request->validate([
            'jtl_host'     => 'required|string',
            'jtl_port'     => 'required|string',
            'jtl_database' => 'required|string',
            'jtl_username' => 'required|string',
            'jtl_password' => 'nullable|string',
        ]);

        $password = $request->jtl_password;
        if (blank($password) && $request->user()->jtl_password) {
            $password = Crypt::decryptString($request->user()->jtl_password);
        }

        config([
            'database.connections.jtl_test.driver'                  => 'sqlsrv',
            'database.connections.jtl_test.host'                    => $request->jtl_host,
            'database.connections.jtl_test.port'                    => $request->jtl_port,
            'database.connections.jtl_test.database'                => $request->jtl_database,
            'database.connections.jtl_test.username'                => $request->jtl_username,
            'database.connections.jtl_test.password'                => $password,
            'database.connections.jtl_test.charset'                 => 'utf8',
            'database.connections.jtl_test.prefix'                  => '',
            'database.connections.jtl_test.encrypt'                 => 'no',
            'database.connections.jtl_test.trust_server_certificate' => 'true',
        ]);

        try {
            DB::connection('jtl_test')->getPdo();
            return response()->json([
                'success' => true,
                'message' => 'Datenbank-Verbindung erfolgreich!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Verbindung fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }
    }

    public function saveApiKey(Request $request)
    {
        $request->validate([
            'jtl_api_key'   => 'required|string',
            'jtl_tenant_id' => 'required|string',
        ]);

        $tenant = Tenant::first();

        $tenant->update([
            'jtl_api_key'   => $request->jtl_api_key,
            'jtl_tenant_id' => $request->jtl_tenant_id,
        ]);

        $jtl = new \App\Services\JtlWawiApiService($tenant->fresh());

        try {
            $jtl->exchangeApiKeyForToken($request->jtl_api_key);
            \App\Models\Wms\SyncLog::create([
                'tenant_id' => $tenant->id,
                'direction' => 'in',
                'type'      => 'auth',
                'status'    => 'success',
                'message'   => 'JTL-Wawi API Authentifizierung erfolgreich.',
            ]);
            return redirect()->route('jtl.settings')->with('success', 'API-Key gespeichert und authentifiziert.');
        } catch (\Exception $e) {
            return redirect()->route('jtl.settings')->with('error', 'Authentifizierung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    public function testApiKey(Request $request)
    {
        $tenant = Tenant::first();

        if (!$tenant->jtl_api_key || !$tenant->jtl_tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'API-Key und Tenant-ID müssen zuerst gespeichert werden.',
            ]);
        }

        try {
            $jtl = new \App\Services\JtlWawiApiService($tenant);
            $data = $jtl->queryItems(1, 1);
            return response()->json([
                'success' => true,
                'message' => 'API-Key Verbindung erfolgreich! (' . ($data['totalItems'] ?? 0) . ' Artikel gefunden)',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API-Key Verbindung fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }
    }

    public function saveCloud(Request $request)
    {
        $request->validate([
            'jtl_client_id'       => 'required|string',
            'jtl_client_secret'   => 'required|string',
            'jtl_cloud_tenant_id' => 'required|string',
        ]);

        $user = $request->user();

        $user->jtl_cloud_client_id     = Crypt::encryptString($request->jtl_client_id);
        $user->jtl_cloud_client_secret = Crypt::encryptString($request->jtl_client_secret);
        $user->jtl_cloud_tenant_id     = $request->jtl_cloud_tenant_id;

        $user->save();

        $tenant = Tenant::first();
        $jtl = new \App\Services\JtlWawiApiService($tenant->fresh());

        try {
            $jtl->exchangeCloudCredentialsForToken();
            \App\Models\Wms\SyncLog::create([
                'tenant_id' => $tenant->id,
                'direction' => 'in',
                'type'      => 'auth',
                'status'    => 'success',
                'message'   => 'JTL-Wawi Cloud API Authentifizierung erfolgreich.',
            ]);
            return redirect()->route('jtl.settings')->with('success', 'Cloud API gespeichert und authentifiziert.');
        } catch (\Exception $e) {
            return redirect()->route('jtl.settings')->with('error', 'Cloud Auth fehlgeschlagen: ' . $e->getMessage());
        }
    }

    public function testCloud(Request $request)
    {
        $tenant = Tenant::first();

        if (!$tenant->jtl_cloud_client_id || !$tenant->jtl_cloud_tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cloud Client ID und Tenant-ID müssen zuerst gespeichert werden.',
            ]);
        }

        try {
            $jtl = new \App\Services\JtlWawiApiService($tenant);
            $data = $jtl->queryItems(1, 1);
            return response()->json([
                'success' => true,
                'message' => 'Cloud API Verbindung erfolgreich! (' . ($data['totalItems'] ?? 0) . ' Artikel gefunden)',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cloud API Verbindung fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }
    }
}
