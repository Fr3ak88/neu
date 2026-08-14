<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Wms\ReturnRecord;
use Illuminate\Http\Request;

class WmsReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturnRecord::with('order');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhere('storlogix_return_id', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $returns = $query->latest('received_at')->paginate(25)->withQueryString();

        return view('wms.returns.index', compact('returns'));
    }

    public function show(ReturnRecord $return)
    {
        $return->load('order');
        return view('wms.returns.show', compact('return'));
    }

    public function update(Request $request, ReturnRecord $return)
    {
        $validated = $request->validate([
            'status'    => 'required|string|in:received,inspected,restocked,disposed',
            'condition' => 'nullable|string|max:255',
        ]);

        $return->update($validated);

        if ($validated['status'] === 'restocked' && $return->product) {
            $return->product->increment('quantity', $return->quantity);
        }

        return redirect()->route('wms.returns.show', $return)
            ->with('success', 'Retoure aktualisiert.');
    }
}
