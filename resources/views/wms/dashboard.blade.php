@extends('layouts.app')

@section('title', 'WMS Dashboard')

@section('content')
<div class="page-header">
    <div class="page-title">Storelogix Dashboard</div>
    <div class="page-subtitle">Warehouses Management System – Überblick</div>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Bestände</div>
        <div class="stat-value primary">{{ $stats['products'] }}</div>
        <div class="stat-sub">Artikel im System</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Bestellungen heute</div>
        <div class="stat-value">{{ $stats['orders_today'] }}</div>
        <div class="stat-sub">{{ $stats['open_orders'] }} offen</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Versandaufträge ausstehend</div>
        <div class="stat-value warning">{{ $stats['shipments_pending'] }}</div>
        <div class="stat-sub">{{ $stats['shipments_today'] }} heute versendet</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Retouren</div>
        <div class="stat-value">{{ $stats['returns'] }}</div>
        <div class="stat-sub">{{ $stats['returns_open'] }} offen</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6)">
    <div class="card">
        <div class="card-title"><i data-lucide="shopping-cart" width="16" height="16"></i> Letzte Bestellungen</div>
        @if($recentOrders->isEmpty())
            <div style="text-align:center;padding:var(--space-6) 0;color:var(--color-text-faint);font-size:var(--text-sm)">
                Noch keine Bestellungen vorhanden.
            </div>
        @else
            <div style="overflow-x:auto">
                <table class="article-table">
                    <thead>
                        <tr>
                            <th>Nr.</th><th>Kunde</th><th>Status</th><th>Datum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('wms.orders.show', $order) }}" style="color:var(--color-text);text-decoration:none">
                                    <b>{{ $order->order_number }}</b>
                                </a>
                            </td>
                            <td>{{ $order->customer_name }}</td>
                            <td>
                                <span class="status-badge {{ $order->statusClass() }}">{{ $order->statusLabel() }}</span>
                            </td>
                            <td><small class="article-sku">{{ $order->ordered_at?->format('d.m.Y H:i') }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-title"><i data-lucide="truck" width="16" height="16"></i> Letzte Versandaufträge</div>
        @if($recentShipments->isEmpty())
            <div style="text-align:center;padding:var(--space-6) 0;color:var(--color-text-faint);font-size:var(--text-sm)">
                Noch keine Versandaufträge vorhanden.
            </div>
        @else
            <div style="overflow-x:auto">
                <table class="article-table">
                    <thead>
                        <tr>
                            <th>Tracking</th><th>Bestellung</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentShipments as $shipment)
                        <tr>
                            <td>
                                <a href="{{ route('wms.shipments.show', $shipment) }}" style="color:var(--color-text);text-decoration:none">
                                    <b>{{ $shipment->tracking_number ?: $shipment->storlogix_id ?: '—' }}</b>
                                </a>
                            </td>
                            <td>{{ $shipment->order?->order_number }}</td>
                            <td>
                                <span class="status-badge {{ $shipment->statusClass() }}">{{ $shipment->statusLabel() }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card" style="margin-top:var(--space-6)">
    <div class="card-title"><i data-lucide="activity" width="16" height="16"></i> Sync-Log</div>
    @if($recentLogs->isEmpty())
        <div style="text-align:center;padding:var(--space-6) 0;color:var(--color-text-faint);font-size:var(--text-sm)">
            Noch keine Synchronisationen durchgeführt.
        </div>
    @else
        <div style="overflow-x:auto">
            <table class="article-table">
                <thead>
                    <tr>
                        <th>Zeit</th><th>Richtung</th><th>Typ</th><th>Status</th><th>Nachricht</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                    <tr>
                        <td><small class="article-sku">{{ $log->created_at->format('d.m.Y H:i:s') }}</small></td>
                        <td>
                            @if($log->direction === 'in')
                                <span class="status-badge status-ok">IN</span>
                            @else
                                <span class="status-badge status-pending">OUT</span>
                            @endif
                        </td>
                        <td>{{ $log->type }}</td>
                        <td>
                            @if($log->status === 'success')
                                <span class="status-badge status-ok">OK</span>
                            @else
                                <span class="status-badge status-error">Fehler</span>
                            @endif
                        </td>
                        <td><small>{{ Str::limit($log->message, 60) }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
