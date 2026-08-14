@extends('layouts.app')

@section('title', 'Versände')

@section('content')
<div class="page-header">
    <div class="page-title">Versandaufträge</div>
    <div class="page-subtitle">Versandaufträge an Storelogix</div>
</div>

<div class="action-bar">
    <div class="action-bar-left">
        <form method="GET" action="{{ route('wms.shipments.index') }}" style="display:flex;gap:var(--space-2)">
            <input type="text" name="search" class="inp" placeholder="Tracking, Storelogix-ID oder Bestellnr…" value="{{ request('search') }}" style="width:300px">
            <select name="status" class="inp">
                <option value="">Alle Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Ausstehend</option>
                <option value="created" {{ request('status') === 'created' ? 'selected' : '' }}>Übertragen</option>
                <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Versendet</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Sendungsdaten erhalten</option>
                <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>Fehler</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Suchen</button>
        </form>
    </div>
    <div class="action-bar-right">
    </div>
</div>

<div class="card">
    @if($shipments->isEmpty())
        <div style="text-align:center;padding:var(--space-10) 0;color:var(--color-text-faint)">
            Noch keine Versandaufträge vorhanden.
        </div>
    @else
        <div style="overflow-x:auto">
            <table class="article-table">
                <thead>
                    <tr>
                        <th>Tracking</th><th>Storelogix-ID</th><th>Bestellung</th><th>Carrier</th><th>Status</th><th>Datum</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shipments as $shipment)
                    <tr>
                        <td>
                            <a href="{{ route('wms.shipments.show', $shipment) }}" style="color:var(--color-text);text-decoration:none">
                                <b>{{ $shipment->tracking_number ?: '—' }}</b>
                            </a>
                        </td>
                        <td><span class="article-sku">{{ $shipment->storlogix_id ?: '—' }}</span></td>
                        <td>{{ $shipment->order?->order_number }}</td>
                        <td>{{ $shipment->carrier ?: '—' }}</td>
                        <td>
                            <span class="status-badge {{ $shipment->statusClass() }}">{{ $shipment->statusLabel() }}</span>
                        </td>
                        <td><small class="article-sku">{{ $shipment->shipped_at?->format('d.m.Y') ?: $shipment->created_at->format('d.m.Y') }}</small></td>
                        <td>
                            <a href="{{ route('wms.shipments.edit', $shipment) }}" class="btn btn-ghost btn-sm">Bearbeiten</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:var(--space-4)">
            {{ $shipments->links() }}
        </div>
    @endif
</div>
@endsection
