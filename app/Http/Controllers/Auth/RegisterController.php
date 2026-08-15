<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'company' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'zip' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'country' => 'required|string|size:2',
            'phone' => 'required|string|max:50',
        ]);

        $tenant = Tenant::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::random(5),
            'plan' => 'free',
            'company' => $data['company'],
            'street' => $data['street'],
            'zip' => $data['zip'],
            'city' => $data['city'],
            'country' => $data['country'],
            'phone' => $data['phone'],
            'modules' => ['fba_shipments', 'wms'],
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => User::ROLE_FIRMENADMIN,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
