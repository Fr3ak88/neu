@extends('layouts.app')

@section('title', 'Bestellung ' . $order->order_number)

@section('content')
<div class="page-header">
    <a href="{{ route('wms.orders.index') }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">{{ $order->order_number }}</div>
    <div class="page-subtitle">{{ $order->customer_name }} · {{ $order->ordered_at?->format('d.m.Y H:i') }}</div>
</div>

<div style="display:flex;gap:var(--space-3);margin-bottom:var(--space-6)">
    <a href="{{ route('wms.orders.edit', $order) }}" class="btn btn-primary">
        <i data-lucide="pencil" width="16" height="16"></i> Bearbeiten
    </a>
    <a href="{{ route('wms.shipments.create') }}?order={{ $order->id }}" class="btn btn-secondary">
        <i data-lucide="truck" width="16" height="16"></i> Versandauftrag erstellen
    </a>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-6)">
    <div>
        <div class="card">
            <div class="card-title"><i data-lucide="list" width="16" height="16"></i> Bestellpositionen</div>
            @if($order->items->isEmpty())
                <div style="text-align:center;padding:var(--space-6) 0;color:var(--color-text-faint);font-size:var(--text-sm)">
                    Keine Positionen vorhanden.
                </div>
            @else
                <div style="overflow-x:auto">
                    <table class="article-table">
                        <thead>
                            <tr><th>SKU</th><th>Name</th><th>Menge</th><th>Einzelpreis</th></tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td><span class="article-sku">{{ $item->sku }}</span></td>
                                <td>{{ $item->name ?: '—' }}</td>
                                <td style="font-family:var(--font-mono)">{{ $item->quantity }}</td>
                                <td style="font-family:var(--font-mono)">{{ $item->unit_price ? number_format($item->unit_price, 2, ',', '.') . ' €' : '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if($order->shipments->isNotEmpty())
        <div class="card">
            <div class="card-title"><i data-lucide="truck" width="16" height="16"></i> Versände</div>
            <div style="overflow-x:auto">
                <table class="article-table">
                    <thead>
                        <tr><th>Tracking</th><th>Carrier</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($order->shipments as $shipment)
                        <tr>
                            <td><span class="article-sku">{{ $shipment->tracking_number ?: $shipment->storlogix_id ?: '—' }}</span></td>
                            <td>{{ $shipment->carrier ?: '—' }}</td>
                            <td><span class="status-badge {{ $shipment->statusClass() }}">{{ $shipment->statusLabel() }}</span></td>
                            <td><a href="{{ route('wms.shipments.show', $shipment) }}" class="btn btn-ghost btn-sm">Details</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div>
        <div class="card">
            <div class="card-title"><i data-lucide="info" width="16" height="16"></i> Details</div>
            <div style="display:flex;flex-direction:column;gap:var(--space-3)">
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Status</span>
                    <span class="status-badge {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Betrag</span>
                    <span style="font-family:var(--font-mono)">{{ $order->total_amount ? number_format($order->total_amount, 2, ',', '.') . ' €' : '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Versandart</span>
                    <span>{{ $order->shipping_method ?: '—' }}</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title"><i data-lucide="user" width="16" height="16"></i> Kunde</div>
            <div style="display:flex;flex-direction:column;gap:var(--space-2)">
                <b>{{ $order->customer_name }}</b>
                @if($order->customer_address)
                    <span style="font-size:var(--text-sm);color:var(--color-text-muted)">{{ $order->customer_address }}</span>
                @endif
                @if($order->customer_zip || $order->customer_city)
                    <span style="font-size:var(--text-sm);color:var(--color-text-muted)">{{ $order->customer_zip }} {{ $order->customer_city }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
