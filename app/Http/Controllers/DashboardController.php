<?php

namespace App\Http\Controllers;

use App\Models\AmazonAccount;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $hasAmazonAccount = AmazonAccount::where('active', true)->exists();
        $fbaEnabled = $user->hasModule('fba_shipments');

        return view('dashboard', compact('hasAmazonAccount', 'fbaEnabled'));
    }
}
