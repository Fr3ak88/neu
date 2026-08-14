@extends('layouts.app')

@section('title', 'Aufträge')

@section('content')
@php
    $baseParams = request()->only('search', 'status', 'typ');
    $sortable = ['auftragsnummer', 'kunde', 'typ', 'intervall', 'startdatum', 'naechste_erstellung'];
    $sortLinks = [];
    foreach ($sortable as $col) {
        $active = $sort === $col;
        $nextDir = ($active && $direction === 'asc') ? 'desc' : 'asc';
        $sortLinks[$col] = [
            'url'       => route('auftraege.index', array_merge($baseParams, ['sort' => $col, 'direction' => $nextDir])),
            'active'    => $active,
            'direction' => $active ? $direction : null,
        ];
    }
@endphp

<div class="page-header">
    <div>
        <div class="page-title">Aufträge</div>
        <div class="page-subtitle">Aufträge suchen, filtern und verwalten</div>
    </div>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Gesamt</div>
        <div class="stat-value primary">{{ $stats['total'] }}</div>
        <div class="stat-sub">Aufträge</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Einmalig</div>
        <div class="stat-value">{{ $stats['einmalig'] }}</div>
        <div class="stat-sub">Aufträge</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Wiederkehrend</div>
        <div class="stat-value success">{{ $stats['wiederkehrend'] }}</div>
        <div class="stat-sub">aktiv</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Fällig</div>
        <div class="stat-value warning">{{ $stats['faellig'] }}</div>
        <div class="stat-sub">nächste Ausführung</div>
    </div>
</div>

<div class="action-bar">
    <div class="action-bar-left">
        <form method="GET" action="{{ route('auftraege.index') }}" style="display:flex;gap:var(--space-2)">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if(request('typ'))
                <input type="hidden" name="typ" value="{{ request('typ') }}">
            @endif
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
                <input type="hidden" name="direction" value="{{ request('direction') }}">
            @endif
            <input type="text" name="search" class="inp" placeholder="Auftragsnr., Kunde oder Bezeichnung…" value="{{ request('search') }}" style="width:300px">
            <select name="typ" class="inp" style="width:auto">
                <option value="">Alle Typen</option>
                <option value="einmalig" {{ request('typ') === 'einmalig' ? 'selected' : '' }}>Einmalig</option>
                <option value="wiederkehrend" {{ request('typ') === 'wiederkehrend' ? 'selected' : '' }}>Wiederkehrend</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Suchen</button>
            @if(request('search') || request('status') || request('typ') || request('sort'))
                <a href="{{ route('auftraege.index', ['reset' => 1]) }}" class="btn btn-ghost btn-sm">Zurücksetzen</a>
            @endif
        </form>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('auftraege.create') }}" class="btn btn-primary btn-sm">
            <i data-lucide="plus" width="14" height="14"></i> Neuer Auftrag
        </a>
    </div>
</div>

<div class="tabs" role="tablist">
    <a href="{{ route('auftraege.index', request()->only('search', 'typ', 'sort', 'direction')) }}" class="tab-btn {{ !request('status') ? 'active' : '' }}">Alle <span class="tab-count">{{ $stats['total'] }}</span></a>
    <a href="{{ route('auftraege.index', array_merge(request()->only('search', 'typ', 'sort', 'direction'), ['status' => 'erstellt'])) }}" class="tab-btn {{ request('status') === 'erstellt' ? 'active' : '' }}">Erstellt <span class="tab-count">{{ $stats['erstellt'] }}</span></a>
    <a href="{{ route('auftraege.index', array_merge(request()->only('search', 'typ', 'sort', 'direction'), ['status' => 'aktiv'])) }}" class="tab-btn {{ request('status') === 'aktiv' ? 'active' : '' }}">Aktiv <span class="tab-count">{{ $stats['aktiv'] }}</span></a>
    <a href="{{ route('auftraege.index', array_merge(request()->only('search', 'typ', 'sort', 'direction'), ['status' => 'pausiert'])) }}" class="tab-btn {{ request('status') === 'pausiert' ? 'active' : '' }}">Pausiert <span class="tab-count">{{ $stats['pausiert'] }}</span></a>
    <a href="{{ route('auftraege.index', array_merge(request()->only('search', 'typ', 'sort', 'direction'), ['status' => 'faellig'])) }}" class="tab-btn {{ request('status') === 'faellig' ? 'active' : '' }}">Fällig <span class="tab-count">{{ $stats['faellig'] }}</span></a>
    <a href="{{ route('auftraege.index', array_merge(request()->only('search', 'typ', 'sort', 'direction'), ['status' => 'abgelaufen'])) }}" class="tab-btn {{ request('status') === 'abgelaufen' ? 'active' : '' }}">Abgelaufen <span class="tab-count">{{ $stats['abgelaufen'] }}</span></a>
</div>

<div class="card">
    @if($stats['total'] === 0)
        <div style="text-align:center;padding:var(--space-12) 0;color:var(--color-text-faint)">
            <div style="margin-bottom:var(--space-4)">
                <i data-lucide="file-text" width="48" height="48" style="opacity:.3;margin:0 auto"></i>
            </div>
            <div style="font-size:var(--text-base);font-weight:500;margin-bottom:var(--space-2);color:var(--color-text-muted)">Noch keine Aufträge vorhanden</div>
            <div style="font-size:var(--text-sm);margin-bottom:var(--space-6)">Erstelle deinen ersten Auftrag, um loszulegen.</div>
            <a href="{{ route('auftraege.create') }}" class="btn btn-primary">
                <i data-lucide="plus" width="16" height="16"></i> Auftrag erstellen
            </a>
        </div>
    @elseif($auftraege->isEmpty())
        <div style="text-align:center;padding:var(--space-12) 0;color:var(--color-text-faint)">
            <div style="font-size:var(--text-base);font-weight:500;margin-bottom:var(--space-2);color:var(--color-text-muted)">Keine Aufträge gefunden</div>
            <div style="font-size:var(--text-sm);margin-bottom:var(--space-6)">Für die aktuellen Filter und Suchbegriffe.</div>
            <a href="{{ route('auftraege.index', ['reset' => 1]) }}" class="btn btn-secondary">
                <i data-lucide="rotate-ccw" width="16" height="16"></i> Filter zurücksetzen
            </a>
        </div>
    @else
        <div class="overview-header">
            <div class="overview-row" style="grid-template-columns:1fr 1.5fr 1fr 1fr 1fr 1fr auto">
                <span>
                    <a href="{{ $sortLinks['auftragsnummer']['url'] }}" class="sort-link {{ $sortLinks['auftragsnummer']['active'] ? 'active' : '' }}">
                        Auftragsnr. @if($sortLinks['auftragsnummer']['active'])<span class="sort-arrow">{{ $sortLinks['auftragsnummer']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span>
                    <a href="{{ $sortLinks['kunde']['url'] }}" class="sort-link {{ $sortLinks['kunde']['active'] ? 'active' : '' }}">
                        Kunde @if($sortLinks['kunde']['active'])<span class="sort-arrow">{{ $sortLinks['kunde']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span>
                    <a href="{{ $sortLinks['typ']['url'] }}" class="sort-link {{ $sortLinks['typ']['active'] ? 'active' : '' }}">
                        Typ @if($sortLinks['typ']['active'])<span class="sort-arrow">{{ $sortLinks['typ']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span>
                    <a href="{{ $sortLinks['intervall']['url'] }}" class="sort-link {{ $sortLinks['intervall']['active'] ? 'active' : '' }}">
                        Intervall @if($sortLinks['intervall']['active'])<span class="sort-arrow">{{ $sortLinks['intervall']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span>Betrag</span>
                <span>Status</span>
                <span></span>
            </div>
        </div>

        @foreach($auftraege as $a)
            <div class="overview-row" style="grid-template-columns:1fr 1.5fr 1fr 1fr 1fr 1fr auto;cursor:pointer" onclick="window.location='{{ route('auftraege.show', $a) }}'">
                <span style="font-family:var(--font-mono);font-weight:500;color:var(--color-primary)">{{ $a->auftragsnummer }}</span>
                <span style="font-size:var(--text-sm)">{{ $a->kunde_firma ?? $a->kunde_name ?? '—' }}</span>
                <span>
                    @if($a->isEinmalig())
                        <span class="status-badge status-info">Einmalig</span>
                    @else
                        <span class="status-badge status-ok">Wiederkehrend</span>
                    @endif
                </span>
                <span style="font-size:var(--text-sm)">{{ $a->isEinmalig() ? '—' : $a->intervallLabel() }}</span>
                <span style="font-family:var(--font-mono);font-weight:500">{{ number_format($a->bruttobetrag(), 2, ',', '.') }} €</span>
                <span>
                    <span class="status-badge {{ $a->statusClass() }}">{{ $a->statusLabel() }}</span>
                </span>
                <span style="display:flex;gap:var(--space-2)" onclick="event.stopPropagation()">
                    <a href="{{ route('auftraege.edit', $a) }}" class="btn btn-secondary" style="padding:var(--space-1) var(--space-2);font-size:var(--text-xs)">
                        <i data-lucide="pencil" width="14" height="14"></i>
                    </a>
                </span>
            </div>
        @endforeach

        @if($auftraege->hasPages())
            <div style="margin-top:var(--space-4)">
                {{ $auftraege->links() }}
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
