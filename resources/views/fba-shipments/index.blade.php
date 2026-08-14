@extends('layouts.app')

@section('title', 'Übersicht')

@section('content')
<div class="page-header">
    
    <div class="page-title">Umlagerungen</div>
    <div class="page-subtitle">Alle Umlagerungen auf einen Blick</div>
</div>

@if($jtlStatus && str_contains($jtlStatus, 'error'))
    <div class="alert alert-warning">
        <i data-lucide="alert-triangle" width="18" height="18" class="alert-icon"></i>
        <div><div class="alert-text">JTL-Fehler: {{ Str::after($jtlStatus, 'error: ') }}</div></div>
    </div>
@endif

@php
    $appShipments = $alle->where('source', 'app');
    $jtlEntries = $alle->where('source', 'jtl');
    $offeneCount = $alle->where('status', '!=', 'completed')->count();
    $abgeschlossenCount = $alle->where('status', 'completed')->count();
    $fehlerCount = $appShipments->where('status', 'error')->count();
    $einheiten = $appShipments->sum('units');
@endphp

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Offene Umlagerungen</div>
        <div class="stat-value primary">{{ $offeneCount }}</div>
        <div class="stat-sub">Aktive Prozesse</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Einheiten unterwegs</div>
        <div class="stat-value">{{ $einheiten }}</div>
        <div class="stat-sub">in allen offenen Plänen</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Abgeschlossen</div>
        <div class="stat-value success">{{ $abgeschlossenCount }}</div>
        <div class="stat-sub">fertige Umlagerungen</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Fehler</div>
        <div class="stat-value warning">{{ $fehlerCount }}</div>
        <div class="stat-sub">müssen geprüft werden</div>
    </div>
</div>

<div class="card">
    <div class="card-title"><i data-lucide="package" width="16" height="16"></i> Alle Umlagerungen</div>

    @if($alle->isEmpty())
        <div style="text-align:center;padding:var(--space-10) 0;color:var(--color-text-faint)">
            Noch keine Umlagerungen vorhanden.
            @if($jtlStatus === null)
                <br><a href="{{ route('jtl-connect.show') }}" style="color:var(--color-primary);text-decoration:none">JTL-Wawi verbinden →</a>
            @endif
        </div>
    @else
        <div class="overview-header">
            <div class="overview-row">
                <span>Umlagerung</span><span>Quelle</span><span>Konto</span><span>Einheiten</span><span>Status</span>
            </div>
        </div>

        @foreach($alle as $item)
            @php
                $statusMap = [
                    'draft' => ['status-pending', 'circle'],
                    'plan_creating' => ['status-warn', 'clock'],
                    'plan_ready' => ['status-warn', 'clock'],
                    'registered' => ['status-ok', 'check'],
                    'shipped' => ['status-ok', 'check'],
                    'completed' => ['status-ok', 'check'],
                    'error' => ['status-error', 'alert-triangle'],
                    'open' => ['status-pending', 'circle'],
                ];
                [$badgeClass, $icon] = $statusMap[$item['status']] ?? ['status-pending', 'circle'];
            @endphp

            <div class="overview-row">
                <span>
                    @if($item['source'] === 'app')
                        <a href="{{ route('fba-shipments.show', $item['id']) }}" style="color:var(--color-text);text-decoration:none">
                            <b>{{ $item['ref'] }}</b><br>
                            <small class="article-sku">{{ $item['date']->format('d.m.Y') }}</small>
                        </a>
                    @elseif(in_array($item['ref'], $importedJtlRefs))
                        @php $importedId = App\Models\FbaShipment::where('jtl_ref', $item['ref'])->value('id'); @endphp
                        <a href="{{ route('fba-shipments.show', $importedId) }}" style="color:var(--color-text);text-decoration:none">
                            <b>{{ $item['ref'] }}</b><br>
                            <small class="article-sku">{{ $item['date']->format('d.m.Y') }}</small>
                        </a>
                    @else
                        <form action="{{ route('fba-shipments.import-jtl') }}" method="POST" style="display:inline">
                            @csrf
                            <input type="hidden" name="jtl_ref" value="{{ $item['ref'] }}">
                            <input type="hidden" name="jtl_datum" value="{{ $item['date']->format('Y-m-d H:i:s') }}">
                            <button type="submit" style="background:none;border:none;padding:0;cursor:pointer;text-align:left">
                                <b style="color:var(--color-primary)">{{ $item['ref'] }}</b><br>
                                <small class="article-sku">{{ $item['date']->format('d.m.Y') }}</small>
                            </button>
                        </form>
                    @endif
                </span>
                <span>
                    @if($item['source'] === 'app')
                        <span class="status-badge status-ok">App</span>
                    @else
                        <span class="status-badge status-pending">JTL</span>
                    @endif
                </span>
                <span><small>{{ $item['account'] }}</small></span>
                <span style="font-family:var(--font-mono)">
                    {{ $item['units'] ?? '—' }}
                </span>
                <span>
                    <span class="status-badge {{ $badgeClass }}">
                        <i data-lucide="{{ $icon }}" width="10" height="10"></i> {{ $item['status_label'] }}
                    </span>
                </span>
                @if(in_array($item['ref'], $importedJtlRefs))
                    <span>
                        <span class="status-badge status-ok" style="font-size:var(--text-xs)">
                            <i data-lucide="check" width="10" height="10"></i> importiert
                        </span>
                    </span>
                @endif
            </div>
        @endforeach
    @endif
</div>

@if($jtlStatus === null)
<div class="card">
    <div class="card-title"><i data-lucide="database" width="16" height="16"></i> JTL-Wawi</div>
    <div style="text-align:center;padding:var(--space-6) 0;color:var(--color-text-faint);font-size:var(--text-sm)">
        Noch nicht verbunden.
        <a href="{{ route('jtl-connect.show') }}" style="color:var(--color-primary);text-decoration:none">Verbindung einrichten →</a>
    </div>
</div>
@endif

@endsection