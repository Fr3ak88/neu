@extends('layouts.app')

@section('title', 'WMS Sync')

@section('content')
<div class="page-header">
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">Storelogix Synchronisation</div>
    <div class="page-subtitle">Artikel, Bestände, Bestellungen und Retouren verwalten</div>
</div>

@if(!$jtlConfigured)
    <div class="card" style="max-width:32rem;margin-bottom:var(--space-6)">
        <div class="card-title"><i data-lucide="plug" width="16" height="16"></i> JTL-Wawi nicht verbunden</div>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-4)">
            Bitte zuerst die JTL-Wawi Verbindung unter <a href="{{ route('jtl.settings') }}" style="color:var(--color-primary)">Einstellungen → JTL-Wawi</a> einrichten.
        </p>
        <a href="{{ route('jtl.settings') }}" class="btn btn-primary">
            <i data-lucide="settings" width="16" height="16"></i> Verbindung einrichten
        </a>
    </div>
@else
    <div style="display:flex;gap:var(--space-3);margin-bottom:var(--space-6)">
        @if($jtlAuthenticated)
            <span class="status-badge status-ok" style="padding:var(--space-2) var(--space-4);font-size:var(--text-sm)">
                <i data-lucide="check-circle" width="14" height="14"></i> JTL verbunden via {{ $jtlMode === 'cloud' ? 'Cloud API' : 'API-Key' }}
            </span>
        @else
            <span class="status-badge status-error" style="padding:var(--space-2) var(--space-4);font-size:var(--text-sm)">
                <i data-lucide="alert-circle" width="14" height="14"></i> JTL Token abgelaufen
            </span>
            <a href="{{ route('jtl.settings') }}" class="btn btn-sm btn-secondary">
                <i data-lucide="refresh-cw" width="14" height="14"></i> Neu verbinden
            </a>
        @endif

        @if($tenant->storlogix_api_url && $tenant->storlogix_client_name)
            <span class="status-badge status-ok" style="padding:var(--space-2) var(--space-4);font-size:var(--text-sm)">
                <i data-lucide="check-circle" width="14" height="14"></i> Storelogix verbunden ({{ $tenant->storlogix_client_name }})
            </span>
        @else
            <a href="{{ route('storlogix-connect.show') }}" class="btn btn-sm btn-secondary">
                <i data-lucide="settings" width="14" height="14"></i> Storelogix verbinden
            </a>
        @endif
    </div>

    {{-- JTL-Wawi Sync --}}
    <h3 style="font-size:var(--text-lg);font-weight:600;margin-bottom:var(--space-4)">
        <i data-lucide="database" width="18" height="18" style="display:inline"></i> JTL-Wawi
    </h3>
    <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:var(--space-4);margin-bottom:var(--space-6)">
        <div class="card">
            <div class="card-title"><i data-lucide="package" width="16" height="16"></i> Artikel</div>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-3)">
                Alle Artikel aus JTL-Wawi abrufen.
            </p>
            <form method="POST" action="{{ route('wms.sync.items') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm" @if(!$jtlAuthenticated) disabled @endif>
                    <i data-lucide="refresh-cw" width="14" height="14"></i> Aus JTL holen
                </button>
            </form>
        </div>

        <div class="card">
            <div class="card-title"><i data-lucide="boxes" width="16" height="16"></i> Bestände</div>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-3)">
                Lagerbestände abrufen oder senden.
            </p>
            <div style="display:flex;gap:var(--space-2)">
                <form method="POST" action="{{ route('wms.sync.stocks') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm" @if(!$jtlAuthenticated) disabled @endif>
                        <i data-lucide="download" width="14" height="14"></i> Import
                    </button>
                </form>
                <form method="POST" action="{{ route('wms.sync.stocks.push') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm" @if(!$jtlAuthenticated) disabled @endif>
                        <i data-lucide="upload" width="14" height="14"></i> Export
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-title"><i data-lucide="shopping-cart" width="16" height="16"></i> Bestellungen</div>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-3)">
                Verkaufsaufträge aus JTL-Wawi abrufen.
            </p>
            <form method="POST" action="{{ route('wms.sync.orders') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm" @if(!$jtlAuthenticated) disabled @endif>
                    <i data-lucide="refresh-cw" width="14" height="14"></i> Aus JTL holen
                </button>
            </form>
        </div>
    </div>

    {{-- Storelogix Sync --}}
    <h3 style="font-size:var(--text-lg);font-weight:600;margin-bottom:var(--space-4)">
        <i data-lucide="warehouse" width="18" height="18" style="display:inline"></i> Storelogix
    </h3>
    <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:var(--space-4);margin-bottom:var(--space-6)">
        <div class="card" style="border-left:3px solid var(--color-primary)">
            <div class="card-title"><i data-lucide="upload" width="16" height="16"></i> Artikel → Storelogix</div>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-3)">
                Alle lokalen Artikel an Storelogix senden.
            </p>
            <form method="POST" action="{{ route('wms.sync.storlogix.articles') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="send" width="14" height="14"></i> Artikel senden
                </button>
            </form>
        </div>

        <div class="card" style="border-left:3px solid var(--color-primary)">
            <div class="card-title"><i data-lucide="download" width="16" height="16"></i> Bestände ← Storelogix</div>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-3)">
                Bestände aus Storelogix abrufen und mit JTL synchronisieren.
            </p>
            <form method="POST" action="{{ route('wms.sync.storlogix.stock') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="refresh-cw" width="14" height="14"></i> Bestände syncen
                </button>
            </form>
        </div>

        <div class="card" style="border-left:3px solid var(--color-primary)">
            <div class="card-title"><i data-lucide="package-check" width="16" height="16"></i> Wareneingang ← Storelogix</div>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-3)">
                Wareneingänge aus Storelogix abrufen.
            </p>
            <form method="POST" action="{{ route('wms.sync.storlogix.goods-receipts') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="download" width="14" height="14"></i> Wareneingang
                </button>
            </form>
        </div>

        <div class="card" style="border-left:3px solid var(--color-primary)">
            <div class="card-title"><i data-lucide="rotate-ccw" width="16" height="16"></i> Retouren ← Storelogix</div>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-3)">
                Retouren-Status aus Storelogix abrufen.
            </p>
            <form method="POST" action="{{ route('wms.sync.storlogix.returns') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="refresh-cw" width="14" height="14"></i> Retouren syncen
                </button>
            </form>
        </div>

        <div class="card" style="border-left:3px solid var(--color-primary)">
            <div class="card-title"><i data-lucide="arrow-left-right" width="16" height="16"></i> Bestandsänderungen ← Storelogix</div>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-3)">
                Ungeplante Bestandsänderungen aus Storelogix.
            </p>
            <form method="POST" action="{{ route('wms.sync.storlogix.stock-changes') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="refresh-cw" width="14" height="14"></i> Änderungen syncen
                </button>
            </form>
        </div>

        <div class="card" style="border-left:3px solid var(--color-primary)">
            <div class="card-title"><i data-lucide="list-checks" width="16" height="16"></i> Bestell-Updates ← Storelogix</div>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-3)">
                Status-Updates und abgeschlossene Bestellungen.
            </p>
            <form method="POST" action="{{ route('wms.sync.storlogix.order-updates') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="refresh-cw" width="14" height="14"></i> Updates syncen
                </button>
            </form>
        </div>
    </div>

    {{-- Sync Log --}}
    <div class="card">
        <div class="card-title"><i data-lucide="activity" width="16" height="16"></i> Sync-Protokoll</div>
        @if($logs->isEmpty())
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
                        @foreach($logs as $log)
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
                            <td><small>{{ Str::limit($log->message, 80) }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
@endsection
