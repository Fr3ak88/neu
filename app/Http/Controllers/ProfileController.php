<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        return view('settings.profile.edit', compact('user', 'tenant'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (!empty($data['password'])) {
            if (!Hash::check($data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Das aktuelle Passwort ist nicht korrekt.']);
            }
            $user->password = $data['password'];
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Benutzerdaten aktualisiert.');
    }

    public function updateTenant(Request $request)
    {
        $user = auth()->user();

        $tenant = $user->tenant;

        if (!$tenant) {
            return back()->with('error', 'Keine Firma zugeordnet.');
        }

        $rules = [
            'ust_id' => 'nullable|string|max:50',
            'steuernummer' => 'nullable|string|max:50',
            'hrb' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:50',
            'bic' => 'nullable|string|max:50',
        ];

        if ($request->has('company')) {
            $rules = array_merge($rules, [
                'company' => 'required|string|max:255',
                'street' => 'required|string|max:255',
                'zip' => 'required|string|max:10',
                'city' => 'required|string|max:255',
                'country' => 'required|string|size:2',
                'phone' => 'required|string|max:50',
                'email' => 'nullable|email|max:255',
            ]);
        }

        $data = $request->validate($rules);

        $tenant->update($data);

        return redirect()->route('profile.edit')->with('success', 'Adressdaten aktualisiert.');
    }
}
