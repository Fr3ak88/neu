<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Wms\Order;
use Illuminate\Http\Request;

class WmsOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $orders = $query->latest('ordered_at')->paginate(25)->withQueryString();

        return view('wms.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'shipments', 'returns');
        return view('wms.orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        $order->load('items');
        return view('wms.orders.edit', compact('order'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_address' => 'nullable|string',
            'customer_zip'     => 'nullable|string|max:10',
            'customer_city'    => 'nullable|string|max:255',
            'customer_country' => 'nullable|string|size:2',
            'total_amount'     => 'nullable|numeric|min:0',
            'shipping_method'  => 'nullable|string|max:100',
            'status'           => 'required|string|in:' . implode(',', Order::STATUSES),
        ]);

        $order->update($validated);

        return redirect()->route('wms.orders.show', $order)
            ->with('success', 'Bestellung aktualisiert.');
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return redirect()->route('wms.orders.index')
            ->with('success', 'Bestellung gelöscht.');
    }
}
