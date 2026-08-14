@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="page-header">
    <div class="page-title">Admin Dashboard</div>
    <div class="page-subtitle">Übersicht über alle Systemdaten</div>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Benutzer</div>
        <div class="stat-value primary">{{ $stats['users'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Firmen</div>
        <div class="stat-value">{{ $stats['tenants'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Umlagerungen</div>
        <div class="stat-value success">{{ $stats['shipments'] }}</div>
    </div>
</div>

<div class="card" style="margin-top:var(--space-6)">
    <div class="card-title">
        <i data-lucide="users" width="16" height="16"></i> Letzte Registrierungen
    </div>
    @if($recentUsers->isEmpty())
        <div style="text-align:center;padding:var(--space-10) 0;color:var(--color-text-faint)">
            Noch keine Benutzer vorhanden.
        </div>
    @else
        <table class="article-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>E-Mail</th>
                    <th>Firma</th>
                    <th>Registriert</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentUsers as $user)
                <tr>
                    <td><b>{{ $user->name }}</b></td>
                    <td class="article-sku">{{ $user->email }}</td>
                    <td>{{ (\App\Models\Tenant::first()->company ?? '—') ?? '—' }}</td>
                    <td class="article-sku">{{ $user->created_at->format('d.m.Y H:i') }}</td>
                    <td>
                        @if($user->role === 'superadmin')
                            <span class="status-badge status-ok"><i data-lucide="shield" width="10" height="10"></i> Superadmin</span>
                        @elseif($user->role === 'firmenadmin')
                            <span class="status-badge status-warn"><i data-lucide="shield" width="10" height="10"></i> Firmen-Admin</span>
                        @else
                            <span class="status-badge status-pending">Benutzer</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
