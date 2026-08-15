<?php

namespace App\Http\Controllers;

use App\Models\AmazonAccount;
use App\Services\SpApiAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AmazonAccountController extends Controller
{
    public function index()
    {
        $accounts = AmazonAccount::where('tenant_id', auth()->user()->tenant_id)->get();
        return view('settings.amazon-accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('settings.amazon-accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'marketplace_id'    => 'required|string',
            'seller_id'         => 'nullable|string',
            'lwa_client_id'     => 'required|string',
            'lwa_client_secret' => 'required|string',
            'lwa_refresh_token' => 'required|string',
            'region'            => 'required|string',
        ]);

        $account = AmazonAccount::create($validated);

        return redirect()
            ->route('amazon-accounts.show', $account)
            ->with('success', 'Amazon-Account gespeichert.');
    }

    public function show(AmazonAccount $amazonAccount)
    {
        return view('settings.amazon-accounts.show', ['account' => $amazonAccount]);
    }

    public function edit(AmazonAccount $amazonAccount)
    {
        return view('settings.amazon-accounts.edit', ['account' => $amazonAccount]);
    }

    public function update(Request $request, AmazonAccount $amazonAccount)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'marketplace_id'    => 'required|string',
            'seller_id'         => 'nullable|string',
            'lwa_client_id'     => 'required|string',
            'lwa_client_secret' => 'nullable|string',
            'lwa_refresh_token' => 'nullable|string',
            'region'            => 'required|string',
        ]);

        $secret = trim($validated['lwa_client_secret'] ?? '');
        $token  = trim($validated['lwa_refresh_token'] ?? '');
        unset($validated['lwa_client_secret'], $validated['lwa_refresh_token']);

        $amazonAccount->update($validated);

        $updates = [];
        if ($secret !== '') {
            $updates['lwa_client_secret'] = encrypt($secret);
        }
        if ($token !== '') {
            $updates['lwa_refresh_token'] = encrypt($token);
        }
        if ($updates) {
            DB::table('amazon_accounts')
                ->where('id', $amazonAccount->id)
                ->update($updates);
        }

        return redirect()
            ->route('amazon-accounts.show', $amazonAccount)
            ->with('success', 'Account aktualisiert.');
    }

    public function destroy(AmazonAccount $amazonAccount)
    {
        $amazonAccount->delete();
        return redirect()->route('amazon-accounts.index')->with('success', 'Account gelöscht.');
    }

    public function toggleActive(AmazonAccount $amazonAccount)
    {
        $amazonAccount->update(['active' => ! $amazonAccount->active]);
        return redirect()
            ->route('amazon-accounts.show', $amazonAccount)
            ->with('success', 'Account-' . ($amazonAccount->active ? 'aktiviert' : 'deaktiviert') . '.');
    }

    // Connection-Test per AJAX
    public function testConnection(AmazonAccount $amazonAccount, SpApiAuthService $auth)
    {
        $result = $auth->testConnection($amazonAccount);
        return response()->json($result);
    }
}
