@extends('layouts.app')

@section('title', 'Versandauftrag ' . ($shipment->tracking_number ?: $shipment->storlogix_id ?: $shipment->id))

@section('content')
<div class="page-header">
    <a href="{{ route('wms.shipments.index') }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">Versandauftrag</div>
    <div class="page-subtitle">{{ $shipment->tracking_number ?: $shipment->storlogix_id ?: 'Nr. ' . $shipment->id }}</div>
</div>

<div style="display:flex;gap:var(--space-3);margin-bottom:var(--space-6)">
    <a href="{{ route('wms.shipments.edit', $shipment) }}" class="btn btn-primary">
        <i data-lucide="pencil" width="16" height="16"></i> Bearbeiten
    </a>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-6)">
    <div>
        <div class="card">
            <div class="card-title"><i data-lucide="list" width="16" height="16"></i> Bestellpositionen</div>
            @if($shipment->order && $shipment->order->items->isNotEmpty())
                <div style="overflow-x:auto">
                    <table class="article-table">
                        <thead>
                            <tr><th>SKU</th><th>Name</th><th>Menge</th></tr>
                        </thead>
                        <tbody>
                            @foreach($shipment->order->items as $item)
                            <tr>
                                <td><span class="article-sku">{{ $item->sku }}</span></td>
                                <td>{{ $item->name ?: '—' }}</td>
                                <td style="font-family:var(--font-mono)">{{ $item->quantity }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align:center;padding:var(--space-6) 0;color:var(--color-text-faint);font-size:var(--text-sm)">
                    Keine Positionen vorhanden.
                </div>
            @endif
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-title"><i data-lucide="info" width="16" height="16"></i> Versanddetails</div>
            <div style="display:flex;flex-direction:column;gap:var(--space-3)">
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Status</span>
                    <span class="status-badge {{ $shipment->statusClass() }}">{{ $shipment->statusLabel() }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Bestellung</span>
                    <a href="{{ route('wms.orders.show', $shipment->order) }}" style="color:var(--color-primary);text-decoration:none">
                        {{ $shipment->order?->order_number ?: '—' }}
                    </a>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Tracking</span>
                    <span class="article-sku">{{ $shipment->tracking_number ?: '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Storlogix-ID</span>
                    <span class="article-sku">{{ $shipment->storlogix_id ?: '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Carrier</span>
                    <span>{{ $shipment->carrier ?: '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Pakete</span>
                    <span>{{ $shipment->package_count ?: '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Gewicht</span>
                    <span>{{ $shipment->weight ? $shipment->weight . ' kg' : '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Versendet am</span>
                    <span>{{ $shipment->shipped_at?->format('d.m.Y H:i') ?: '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
