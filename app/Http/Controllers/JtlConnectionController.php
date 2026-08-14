<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class JtlConnectionController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        return view('settings.jtl-connect', compact('user'));
    }

    public function update(Request $request)
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

        return redirect()->route('jtl-connect.show')->with('success', 'JTL-Wawi Verbindung gespeichert.');
    }

    public function test(Request $request)
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
            'database.connections.jtl_test.driver'                => 'sqlsrv',
            'database.connections.jtl_test.host'                  => $request->jtl_host,
            'database.connections.jtl_test.port'                  => $request->jtl_port,
            'database.connections.jtl_test.database'              => $request->jtl_database,
            'database.connections.jtl_test.username'              => $request->jtl_username,
            'database.connections.jtl_test.password'              => $password,
            'database.connections.jtl_test.charset'               => 'utf8',
            'database.connections.jtl_test.prefix'                => '',
            'database.connections.jtl_test.encrypt'               => 'no',
            'database.connections.jtl_test.trust_server_certificate' => 'true',
        ]);

        try {
            DB::connection('jtl_test')->getPdo();

            return response()->json([
                'success' => true,
                'message' => 'Verbindung erfolgreich! Der SQL Server antwortet ordnungsgemäß.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Verbindung fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }
    }
}
