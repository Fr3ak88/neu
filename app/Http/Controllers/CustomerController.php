<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $sortable = ['name', 'email', 'company', 'city', 'created_at'];

        if ($request->boolean('reset')) {
            $request->session()->forget(['customers.sort', 'customers.direction']);
        }

        if ($request->filled('sort') && in_array($request->input('sort'), $sortable, true)) {
            $sort = $request->input('sort');
            $request->session()->put('customers.sort', $sort);
        } else {
            $sort = $request->session()->get('customers.sort', 'name');
            if (!in_array($sort, $sortable, true)) {
                $sort = 'name';
            }
        }

        $direction = strtolower((string) $request->input('direction'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = $request->session()->get('customers.direction', 'asc');
            if (!in_array($direction, ['asc', 'desc'], true)) {
                $direction = 'asc';
            }
        } else {
            $request->session()->put('customers.direction', $direction);
        }

        $query = Customer::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('street', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy($sort, $direction)->paginate(25)->withQueryString();

        $stats = [
            'total'       => Customer::count(),
            'with_company' => Customer::whereNotNull('company')->count(),
            'with_phone'  => Customer::whereNotNull('phone')->count(),
            'recent'      => Customer::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return view('customers.index', compact('customers', 'stats', 'sort', 'direction'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'nullable|string|max:255',
            'email'    => 'required|email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'phone'    => 'nullable|string|max:50',
            'company'  => 'nullable|string|max:255',
            'street'   => 'nullable|string|max:255',
            'zip'      => 'nullable|string|max:20',
            'city'     => 'nullable|string|max:255',
            'country'  => 'nullable|string|size:2',
            'notes'    => 'nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $customer = Customer::create($validated);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Kunde „' . ($customer->name ?? $customer->email) . '" erstellt.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name'     => 'nullable|string|max:255',
            'email'    => 'required|email|max:255',
            'phone'    => 'nullable|string|max:50',
            'company'  => 'nullable|string|max:255',
            'street'   => 'nullable|string|max:255',
            'zip'      => 'nullable|string|max:20',
            'city'     => 'nullable|string|max:255',
            'country'  => 'nullable|string|size:2',
            'notes'    => 'nullable|string',
        ]);

        $customer->update($validated);

        return back()->with('success', 'Kunde aktualisiert.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Kunde gelöscht.');
    }
}
