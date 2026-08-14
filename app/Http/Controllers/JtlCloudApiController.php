<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Base64;

class JtlCloudApiController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        return view('settings.jtl-cloud', compact('user'));
    }

    public function configure(Request $request)
    {
        $request->validate([
            'jtl_client_id'     => 'required|string',
            'jtl_client_secret' => 'required|string',
            'jtl_tenant_id'     => 'required|string',
        ]);

        $user = $request->user();

        $user->jtl_cloud_client_id     = Crypt::encryptString($request->jtl_client_id);
        $user->jtl_cloud_client_secret = Crypt::encryptString($request->jtl_client_secret);
        $user->jtl_cloud_tenant_id     = $request->jtl_tenant_id;

        $user->save();

        return redirect()->route('jtl-cloud.show')->with('success', 'JTL-Wawi Cloud API Verbindung gespeichert.');
    }

    public function test(Request $request)
    {
        $request->validate([
            'jtl_client_id'     => 'required|string',
            'jtl_client_secret' => 'required|string',
            'jtl_tenant_id'     => 'required|string',
        ]);

        $password = $request->jtl_client_secret;
        if (blank($password) && $request->user()->jtl_cloud_client_secret) {
            $password = Crypt::decryptString($request->user()->jtl_cloud_client_secret);
        }

        $clientId = $request->jtl_client_id;
        if (blank($clientId) && $request->user()->jtl_cloud_client_id) {
            $clientId = Crypt::decryptString($request->user()->jtl_cloud_client_id);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode("$clientId:$password"),
            ])->post('https://auth.jtl-cloud.com/oauth2/token', [
                'grant_type' => 'client_credentials'
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cloud API-Authentifizierung fehlgeschlagen: ' . $response->body(),
                ]);
            }

            $data = $response->json();

            return response()->json([
                'success'   => true,
                'message'   => 'Cloud API-Authentifizierung erfolgreich!',
                'token_type' => $data['token_type'],
                'expires_in' => $data['expires_in'],
                'tenant_id'  => $request->jtl_tenant_id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cloud API-Authentifizierung fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }
    }
}