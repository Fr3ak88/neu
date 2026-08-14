<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Wms\Product;
use Illuminate\Http\Request;

class WmsProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('ean', 'like', "%{$search}%");
            });
        }

        $products = $query->latest()->paginate(25)->withQueryString();

        return view('wms.products.index', compact('products'));
    }
}
