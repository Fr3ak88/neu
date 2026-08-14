@extends('layouts.app')

@section('title', 'Bestellungen')

@section('content')
<div class="page-header">
    <div class="page-title">Bestellungen</div>
    <div class="page-subtitle">Bestell-Queue und Verwaltung</div>
</div>

<div class="action-bar">
    <div class="action-bar-left">
        <form method="GET" action="{{ route('wms.orders.index') }}" style="display:flex;gap:var(--space-2)">
            <input type="text" name="search" class="inp" placeholder="Bestellnr. oder Kunde…" value="{{ request('search') }}" style="width:260px">
            <select name="status" class="inp">
                <option value="">Alle Status</option>
                <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>Neu</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>In Bearbeitung</option>
                <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Versendet</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Abgeschlossen</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Storniert</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Suchen</button>
        </form>
    </div>
    <div class="action-bar-right">
    </div>
</div>

<div class="card">
    @if($orders->isEmpty())
        <div style="text-align:center;padding:var(--space-10) 0;color:var(--color-text-faint)">
            Noch keine Bestellungen vorhanden.
        </div>
    @else
        <div style="overflow-x:auto">
            <table class="article-table">
                <thead>
                    <tr>
                        <th>Bestellnr.</th><th>Kunde</th><th>Artikel</th><th>Betrag</th><th>Status</th><th>Datum</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('wms.orders.show', $order) }}" style="color:var(--color-text);text-decoration:none">
                                <b>{{ $order->order_number }}</b>
                            </a>
                        </td>
                        <td>{{ $order->customer_name }}</td>
                        <td style="font-family:var(--font-mono)">{{ $order->items->count() }}</td>
                        <td style="font-family:var(--font-mono)">{{ $order->total_amount ? number_format($order->total_amount, 2, ',', '.') . ' €' : '—' }}</td>
                        <td>
                            <span class="status-badge {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
                        </td>
                        <td><small class="article-sku">{{ $order->ordered_at?->format('d.m.Y') }}</small></td>
                        <td></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:var(--space-4)">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
