@extends('layouts.app')

@section('title', 'Rechnungen')

@section('content')
@php
    $baseParams = request()->only('search', 'status');
    $sortable = ['rechnungsnummer', 'datum', 'faelligkeitsdatum', 'bruttobetrag', 'status'];
    $sortLinks = [];
    foreach ($sortable as $col) {
        $active = $sort === $col;
        $nextDir = ($active && $direction === 'asc') ? 'desc' : 'asc';
        $sortLinks[$col] = [
            'url'   => route('rechnungen.index', array_merge($baseParams, ['sort' => $col, 'direction' => $nextDir])),
            'active' => $active,
            'direction' => $active ? $direction : null,
        ];
    }
@endphp

<div class="page-header">
    <div class="page-title">Rechnungen</div>
    <div class="page-subtitle">Rechnungen suchen, filtern und verwalten</div>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Gesamt</div>
        <div class="stat-value primary">{{ $stats['total'] }}</div>
        <div class="stat-sub">Rechnungen</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Entwürfe</div>
        <div class="stat-value">{{ $stats['draft'] }}</div>
        <div class="stat-sub">noch nicht versendet</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Offen</div>
        <div class="stat-value warning">{{ $stats['offen'] }}</div>
        <div class="stat-sub">warten auf Zahlung</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Bezahlt</div>
        <div class="stat-value success">{{ $stats['bezahlt'] }}</div>
        <div class="stat-sub">eingegangen</div>
    </div>
</div>

<div class="action-bar">
    <div class="action-bar-left">
        <form method="GET" action="{{ route('rechnungen.index') }}" style="display:flex;gap:var(--space-2)">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
                <input type="hidden" name="direction" value="{{ request('direction') }}">
            @endif
            <input type="text" name="search" class="inp" placeholder="Rechnungsnr., Kunde oder Referenz…" value="{{ request('search') }}" style="width:300px">
            <button type="submit" class="btn btn-secondary btn-sm">Suchen</button>
            @if(request('search') || request('status') || request('sort'))
                <a href="{{ route('rechnungen.index', ['reset' => 1]) }}" class="btn btn-ghost btn-sm">Zurücksetzen</a>
            @endif
        </form>
    </div>
</div>

<div class="tabs" role="tablist">
    <a href="{{ route('rechnungen.index', request()->only('search', 'sort', 'direction')) }}" class="tab-btn {{ !request('status') ? 'active' : '' }}">Alle <span class="tab-count">{{ $stats['total'] }}</span></a>
    <a href="{{ route('rechnungen.index', array_merge(request()->only('search', 'sort', 'direction'), ['status' => 'draft'])) }}" class="tab-btn {{ request('status') === 'draft' ? 'active' : '' }}">Entwürfe <span class="tab-count">{{ $stats['draft'] }}</span></a>
    <a href="{{ route('rechnungen.index', array_merge(request()->only('search', 'sort', 'direction'), ['status' => 'bestaetigt'])) }}" class="tab-btn {{ request('status') === 'bestaetigt' ? 'active' : '' }}">Bestätigt <span class="tab-count">{{ $stats['bestaetigt'] }}</span></a>
    <a href="{{ route('rechnungen.index', array_merge(request()->only('search', 'sort', 'direction'), ['status' => 'versendet'])) }}" class="tab-btn {{ request('status') === 'versendet' ? 'active' : '' }}">Offen <span class="tab-count">{{ $stats['offen'] }}</span></a>
    <a href="{{ route('rechnungen.index', array_merge(request()->only('search', 'sort', 'direction'), ['status' => 'bezahlt'])) }}" class="tab-btn {{ request('status') === 'bezahlt' ? 'active' : '' }}">Bezahlt <span class="tab-count">{{ $stats['bezahlt'] }}</span></a>
    <a href="{{ route('rechnungen.index', array_merge(request()->only('search', 'sort', 'direction'), ['status' => 'ueberfaellig'])) }}" class="tab-btn {{ request('status') === 'ueberfaellig' ? 'active' : '' }}">Überfällig <span class="tab-count">{{ $stats['ueberfaellig'] }}</span></a>
    <a href="{{ route('rechnungen.index', array_merge(request()->only('search', 'sort', 'direction'), ['status' => 'storniert'])) }}" class="tab-btn {{ request('status') === 'storniert' ? 'active' : '' }}">Storniert <span class="tab-count">{{ $stats['storniert'] }}</span></a>
    <a href="{{ route('rechnungen.index', array_merge(request()->only('search', 'sort', 'direction'), ['status' => 'stornobelege'])) }}" class="tab-btn {{ request('status') === 'stornobelege' ? 'active' : '' }}">Stornobelege <span class="tab-count">{{ $stats['stornobelege'] }}</span></a>
</div>

<div class="card">
    @if($stats['total'] === 0)
        <div style="text-align:center;padding:var(--space-12) 0;color:var(--color-text-faint)">
            <div style="margin-bottom:var(--space-4)">
                <i data-lucide="file-text" width="48" height="48" style="opacity:.3;margin:0 auto"></i>
            </div>
            <div style="font-size:var(--text-base);font-weight:500;margin-bottom:var(--space-2);color:var(--color-text-muted)">Noch keine Rechnungen vorhanden</div>
            <div style="font-size:var(--text-sm);margin-bottom:var(--space-6)">Erstelle deine erste Rechnung, um loszulegen.</div>
            <a href="{{ route('rechnungen.create') }}" class="btn btn-primary">
                <i data-lucide="plus" width="16" height="16"></i> Rechnung erstellen
            </a>
        </div>
    @elseif($rechnungen->isEmpty())
        <div style="text-align:center;padding:var(--space-12) 0;color:var(--color-text-faint)">
            <div style="font-size:var(--text-base);font-weight:500;margin-bottom:var(--space-2);color:var(--color-text-muted)">Keine Rechnungen gefunden</div>
            <div style="font-size:var(--text-sm);margin-bottom:var(--space-6)">Für die aktuellen Filter und Suchbegriffe.</div>
            <a href="{{ route('rechnungen.index', ['reset' => 1]) }}" class="btn btn-secondary">
                <i data-lucide="rotate-ccw" width="16" height="16"></i> Filter zurücksetzen
            </a>
        </div>
    @else
        <div class="overview-header">
            <div class="overview-row" style="grid-template-columns:1.5fr 2fr 1fr 1fr 1fr 1fr">
                <span>
                    <a href="{{ $sortLinks['rechnungsnummer']['url'] }}" class="sort-link {{ $sortLinks['rechnungsnummer']['active'] ? 'active' : '' }}">
                        Nr. @if($sortLinks['rechnungsnummer']['active'])<span class="sort-arrow">{{ $sortLinks['rechnungsnummer']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span>Kunde</span>
                <span>
                    <a href="{{ $sortLinks['datum']['url'] }}" class="sort-link {{ $sortLinks['datum']['active'] ? 'active' : '' }}">
                        Datum @if($sortLinks['datum']['active'])<span class="sort-arrow">{{ $sortLinks['datum']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span>
                    <a href="{{ $sortLinks['faelligkeitsdatum']['url'] }}" class="sort-link {{ $sortLinks['faelligkeitsdatum']['active'] ? 'active' : '' }}">
                        Fällig @if($sortLinks['faelligkeitsdatum']['active'])<span class="sort-arrow">{{ $sortLinks['faelligkeitsdatum']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span>
                    <a href="{{ $sortLinks['bruttobetrag']['url'] }}" class="sort-link {{ $sortLinks['bruttobetrag']['active'] ? 'active' : '' }}">
                        Brutto @if($sortLinks['bruttobetrag']['active'])<span class="sort-arrow">{{ $sortLinks['bruttobetrag']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span>
                    <a href="{{ $sortLinks['status']['url'] }}" class="sort-link {{ $sortLinks['status']['active'] ? 'active' : '' }}">
                        Status @if($sortLinks['status']['active'])<span class="sort-arrow">{{ $sortLinks['status']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
            </div>
        </div>

        @foreach($rechnungen as $r)
            <a href="{{ route('rechnungen.show', $r) }}" style="text-decoration:none;color:inherit">
                <div class="overview-row" style="grid-template-columns:1.5fr 2fr 1fr 1fr 1fr 1fr">
                    <span>
                        <span style="font-family:var(--font-mono);font-weight:500;color:var(--color-primary)">{{ $r->rechnungsnummer }}</span>
                        @if($r->intern_ref)
                            <br><small class="article-sku">{{ $r->intern_ref }}</small>
                        @endif
                        @if($r->ist_storno && $r->stornoVon)
                            <br><small class="article-sku" style="color:var(--color-error)">Storno zu {{ $r->stornoVon->rechnungsnummer }}</small>
                        @endif
                    </span>
                    <span>
                        <span style="font-weight:500;color:var(--color-text)">{{ $r->kunde_firma ?? $r->kunde_name ?? '—' }}</span>
                        @if($r->kunde_firma && $r->kunde_name)
                            <br><small class="article-sku">{{ $r->kunde_name }}</small>
                        @endif
                    </span>
                    <span style="font-size:var(--text-sm)">{{ $r->datum->format('d.m.Y') }}</span>
                    <span style="font-size:var(--text-sm)">{{ $r->faelligkeitsdatum->format('d.m.Y') }}</span>
                    <span style="font-family:var(--font-mono);font-weight:500">{{ number_format($r->bruttobetrag, 2, ',', '.') }} €</span>
                    <span>
                        <span class="status-badge {{ $r->statusBadgeClass() }}">{{ $r->statusLabel() }}</span>
                    </span>
                </div>
            </a>
        @endforeach

        @if($rechnungen->hasPages())
            <div style="margin-top:var(--space-4)">
                {{ $rechnungen->links() }}
            </div>
        @endif
    @endif
</div>

<style>
.tabs{display:flex;gap:0;border-bottom:2px solid var(--color-border);margin-bottom:var(--space-6);flex-wrap:wrap}
.tab-btn{padding:var(--space-3) var(--space-5);background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;font-size:var(--text-sm);font-weight:500;color:var(--color-text-muted);display:flex;align-items:center;gap:var(--space-2);transition:all .15s;text-decoration:none}
.tab-btn:hover{color:var(--color-text)}
.tab-btn.active{color:var(--color-primary);border-bottom-color:var(--color-primary)}
.tab-count{background:var(--color-surface-offset);border:1px solid var(--color-border);border-radius:999px;padding:0 8px;font-size:var(--text-xs);font-weight:600;color:var(--color-text-muted)}
.tab-btn.active .tab-count{background:var(--color-primary);border-color:var(--color-primary);color:#fff}
.sort-link{text-decoration:none;color:inherit;display:inline-flex;align-items:center;gap:2px}
.sort-link:hover{color:var(--color-primary)}
.sort-link.active{color:var(--color-primary)}
.sort-arrow{font-size:9px}
@media (max-width:600px){.tab-btn{padding:var(--space-2) var(--space-3);font-size:var(--text-xs)}}
</style>
@endsection
