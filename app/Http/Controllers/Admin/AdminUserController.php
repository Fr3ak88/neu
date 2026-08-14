<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    private array $availableModules = [
        'fba_shipments' => 'FBA Umlagerungen',
        'wms'           => 'WMS (Warehouse Management)',
        'customers'     => 'Kundenverwaltung',
        'invoices'      => 'Rechnungen',
        'support'       => 'Support-System',
    ];

    public function index()
    {
        $users = User::latest()->get();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:user,firmenadmin,superadmin',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];

        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()->route('admin.users.show', $user)->with('success', 'Benutzer aktualisiert.');
    }

    public function modules(User $user)
    {
        $available = $this->availableModules;

        return view('admin.users.modules', compact('user', 'available'));
    }

    public function updateModules(Request $request, User $user)
    {
        $modules = $request->input('modules', []);

        if (in_array('support', $modules) && !in_array('customers', $modules)) {
            $modules[] = 'customers';
        }

        $user->modules = $modules;
        $user->save();

        return redirect()->route('admin.users.modules', $user)->with('success', 'Module aktualisiert.');
    }
}
