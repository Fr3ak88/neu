@extends('layouts.customer')

@section('title', 'Kundenbereich')

@section('content')
<div class="page-header">
    <div class="page-title">Willkommen, {{ $customer->name ?? $customer->email }}</div>
    <div class="page-subtitle">Dein persönlicher Kundenbereich</div>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Konto</div>
        <div class="stat-value primary">{{ $customer->name ?? '—' }}</div>
        <div class="stat-sub">{{ $customer->company ?? 'Privatkunde' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">E-Mail</div>
        <div class="stat-value">{{ $customer->email }}</div>
        <div class="stat-sub">Kontaktadresse</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Telefon</div>
        <div class="stat-value">{{ $customer->phone ?? '—' }}</div>
        <div class="stat-sub">Rückfragen</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Meine Daten</div>
        </div>
        <div class="card-body">
            <div class="space-y-3">
                <div>
                    <div class="text-muted" style="font-size:var(--text-xs)">Name</div>
                    <div class="font-medium">{{ $customer->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-muted" style="font-size:var(--text-xs)">Firma</div>
                    <div class="font-medium">{{ $customer->company ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-muted" style="font-size:var(--text-xs)">Adresse</div>
                    <div class="font-medium">{{ $customer->full_address ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Kontakt</div>
        </div>
        <div class="card-body">
            <div class="space-y-3">
                <div>
                    <div class="text-muted" style="font-size:var(--text-xs)">E-Mail</div>
                    <div class="font-medium">{{ $customer->email }}</div>
                </div>
                <div>
                    <div class="text-muted" style="font-size:var(--text-xs)">Telefon</div>
                    <div class="font-medium">{{ $customer->phone ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="margin-top:var(--space-6);text-align:right">
    <form method="POST" action="{{ route('customer.logout') }}" style="display:inline">
        @csrf
        <button type="submit" class="btn btn-secondary">
            <i data-lucide="log-out" width="16" height="16"></i> Abmelden
        </button>
    </form>
</div>
@endsection
