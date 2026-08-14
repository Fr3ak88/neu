<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FirmenadminController extends Controller
{
    public function index()
    {
        $users = User::where('id', '!=', auth()->id())
            ->latest()
            ->get();

        return view('firmenadmin.users.index', compact('users'));
    }

    public function create()
    {
        return view('firmenadmin.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:user,firmenadmin',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ]);

        return redirect()->route('firmenadmin.users.index')->with('success', 'Benutzer erstellt.');
    }

    public function edit(User $user)
    {
        return view('firmenadmin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:user,firmenadmin',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];

        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()->route('firmenadmin.users.index')->with('success', 'Benutzer aktualisiert.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Du kannst dich nicht selbst löschen.');
        }

        $user->delete();

        return redirect()->route('firmenadmin.users.index')->with('success', 'Benutzer gelöscht.');
    }
}
