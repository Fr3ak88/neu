@extends('layouts.app')

@section('title', 'Kunden')

@section('content')
@php
    $baseParams = request()->only('search');
    $sortableCols = ['name', 'email', 'company', 'city', 'created_at'];
    $sortLinks = [];
    foreach ($sortableCols as $col) {
        $active = $sort === $col;
        $nextDir = ($active && $direction === 'asc') ? 'desc' : 'asc';
        $sortLinks[$col] = [
            'url'       => route('customers.index', array_merge($baseParams, ['sort' => $col, 'direction' => $nextDir])),
            'active'    => $active,
            'direction' => $active ? $direction : null,
        ];
    }
@endphp

<div class="page-header">
    <div>
        <div class="page-title">Kunden</div>
        <div class="page-subtitle">Kundenverwaltung und Kontaktinformationen</div>
    </div>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Gesamt</div>
        <div class="stat-value primary">{{ $stats['total'] }}</div>
        <div class="stat-sub">Kunden im System</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Mit Firma</div>
        <div class="stat-value">{{ $stats['with_company'] }}</div>
        <div class="stat-sub">Geschäftskunden</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Mit Telefon</div>
        <div class="stat-value success">{{ $stats['with_phone'] }}</div>
        <div class="stat-sub">Erreichbar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Neu (30 Tage)</div>
        <div class="stat-value warning">{{ $stats['recent'] }}</div>
        <div class="stat-sub">Kürzlich hinzugefügt</div>
    </div>
</div>

<div class="action-bar">
    <div class="action-bar-left">
        <form method="GET" action="{{ route('customers.index') }}" style="display:flex;gap:var(--space-2)">
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
                <input type="hidden" name="direction" value="{{ request('direction') }}">
            @endif
            <input type="text" name="search" class="inp" placeholder="Name, E-Mail, Firma, Stadt…" value="{{ request('search') }}" style="width:300px">
            <button type="submit" class="btn btn-secondary btn-sm">Suchen</button>
            @if(request('search') || request('sort'))
                <a href="{{ route('customers.index', ['reset' => 1]) }}" class="btn btn-ghost btn-sm">Zurücksetzen</a>
            @endif
        </form>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
            <i data-lucide="plus" width="14" height="14"></i> Neuer Kunde
        </a>
    </div>
</div>

<div class="card">
    @if($stats['total'] === 0)
        <div style="text-align:center;padding:var(--space-12) 0;color:var(--color-text-faint)">
            <div style="margin-bottom:var(--space-4)">
                <i data-lucide="users" width="48" height="48" style="opacity:.3;margin:0 auto"></i>
            </div>
            <div style="font-size:var(--text-base);font-weight:500;margin-bottom:var(--space-2);color:var(--color-text-muted)">Noch keine Kunden vorhanden</div>
            <div style="font-size:var(--text-sm);margin-bottom:var(--space-6)">Erstelle deinen ersten Kunden, um loszulegen.</div>
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                <i data-lucide="plus" width="16" height="16"></i> Kunde anlegen
            </a>
        </div>
    @elseif($customers->isEmpty())
        <div style="text-align:center;padding:var(--space-12) 0;color:var(--color-text-faint)">
            <div style="font-size:var(--text-base);font-weight:500;margin-bottom:var(--space-2);color:var(--color-text-muted)">Keine Kunden gefunden</div>
            <div style="font-size:var(--text-sm);margin-bottom:var(--space-6)">Für die aktuellen Suchbegriffe.</div>
            <a href="{{ route('customers.index', ['reset' => 1]) }}" class="btn btn-secondary">
                <i data-lucide="rotate-ccw" width="16" height="16"></i> Filter zurücksetzen
            </a>
        </div>
    @else
        <div class="overview-header">
            <div class="overview-row" style="grid-template-columns:2fr 2fr 1fr 1.5fr 1fr 1fr 0.5fr">
                <span>
                    <a href="{{ $sortLinks['name']['url'] }}" class="sort-link {{ $sortLinks['name']['active'] ? 'active' : '' }}">
                        Name @if($sortLinks['name']['active'])<span class="sort-arrow">{{ $sortLinks['name']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span>
                    <a href="{{ $sortLinks['email']['url'] }}" class="sort-link {{ $sortLinks['email']['active'] ? 'active' : '' }}">
                        E-Mail @if($sortLinks['email']['active'])<span class="sort-arrow">{{ $sortLinks['email']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span>Telefon</span>
                <span>
                    <a href="{{ $sortLinks['company']['url'] }}" class="sort-link {{ $sortLinks['company']['active'] ? 'active' : '' }}">
                        Firma @if($sortLinks['company']['active'])<span class="sort-arrow">{{ $sortLinks['company']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span>
                    <a href="{{ $sortLinks['city']['url'] }}" class="sort-link {{ $sortLinks['city']['active'] ? 'active' : '' }}">
                        Ort @if($sortLinks['city']['active'])<span class="sort-arrow">{{ $sortLinks['city']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span>
                    <a href="{{ $sortLinks['created_at']['url'] }}" class="sort-link {{ $sortLinks['created_at']['active'] ? 'active' : '' }}">
                        Erstellt @if($sortLinks['created_at']['active'])<span class="sort-arrow">{{ $sortLinks['created_at']['direction'] === 'asc' ? '▲' : '▼' }}</span>@endif
                    </a>
                </span>
                <span></span>
            </div>
        </div>

        @foreach($customers as $customer)
            <a href="{{ route('customers.show', $customer) }}" style="text-decoration:none;color:inherit">
                <div class="overview-row" style="grid-template-columns:2fr 2fr 1fr 1.5fr 1fr 1fr 0.5fr">
                    <span>
                        <span style="font-weight:500;color:var(--color-text)">{{ $customer->name ?? '—' }}</span>
                    </span>
                    <span style="font-size:var(--text-sm);color:var(--color-text-muted)">{{ $customer->email }}</span>
                    <span style="font-size:var(--text-sm);color:var(--color-text-muted)">{{ $customer->phone ?? '—' }}</span>
                    <span style="font-size:var(--text-sm);color:var(--color-text-muted)">{{ $customer->company ?? '—' }}</span>
                    <span style="font-size:var(--text-sm);color:var(--color-text-muted)">{{ $customer->city ?? '—' }}</span>
                    <span style="font-size:var(--text-sm);color:var(--color-text-muted)">{{ $customer->created_at->format('d.m.Y') }}</span>
                    <span>
                        <span class="btn btn-sm btn-secondary">Ansehen</span>
                    </span>
                </div>
            </a>
        @endforeach

        @if($customers->hasPages())
            <div style="margin-top:var(--space-4)">
                {{ $customers->links() }}
            </div>
        @endif
    @endif
</div>

<style>
.sort-link{text-decoration:none;color:inherit;display:inline-flex;align-items:center;gap:2px}
.sort-link:hover{color:var(--color-primary)}
.sort-link.active{color:var(--color-primary)}
.sort-arrow{font-size:9px}
</style>
@endsection
