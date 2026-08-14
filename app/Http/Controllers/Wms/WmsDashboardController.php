<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Wms\Order;
use App\Models\Wms\Product;
use App\Models\Wms\ReturnRecord;
use App\Models\Wms\Shipment;
use App\Models\Wms\SyncLog;

class WmsDashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->startOfDay();

        $stats = [
            'products'      => Product::count(),
            'orders_today'  => Order::where('ordered_at', '>=', $today)->count(),
            'orders_total'  => Order::count(),
            'open_orders'   => Order::whereIn('status', ['new', 'processing'])->count(),
            'shipments_pending' => Shipment::where('status', 'pending')->count(),
            'shipments_today'   => Shipment::where('shipped_at', '>=', $today)->count(),
            'returns'       => ReturnRecord::count(),
            'returns_open'  => ReturnRecord::where('status', 'received')->count(),
        ];

        $recentOrders = Order::latest('ordered_at')
            ->take(10)
            ->get();

        $recentShipments = Shipment::with('order')
            ->latest()
            ->take(10)
            ->get();

        $recentReturns = ReturnRecord::latest('received_at')
            ->take(10)
            ->get();

        $recentLogs = SyncLog::latest()
            ->take(20)
            ->get();

        return view('wms.dashboard', compact(
            'stats', 'recentOrders', 'recentShipments', 'recentReturns', 'recentLogs'
        ));
    }
}
