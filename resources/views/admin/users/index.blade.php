@extends('layouts.admin')

@section('title', 'Benutzer — Admin')

@section('content')
<div class="page-header">
    <div class="page-title">Benutzer</div>
    <div class="page-subtitle">Alle registrierten Benutzer im System</div>
</div>

<div class="action-bar">
    <div class="action-bar-left">
        <span class="article-sku">{{ $users->count() }} Benutzer</span>
    </div>
</div>

<div class="card">
    @if($users->isEmpty())
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
                    <th style="text-align:right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td><b>{{ $user->name }}</b></td>
                    <td class="article-sku">{{ $user->email }}</td>
                    <td>{{ (\App\Models\Tenant::first()->company ?? '—') ?? '—' }}</td>
                    <td class="article-sku">{{ $user->created_at->format('d.m.Y') }}</td>
                    <td>
                        @if($user->role === 'superadmin')
                            <span class="status-badge status-ok"><i data-lucide="shield" width="10" height="10"></i> Superadmin</span>
                        @elseif($user->role === 'firmenadmin')
                            <span class="status-badge status-warn"><i data-lucide="shield" width="10" height="10"></i> Firmen-Admin</span>
                        @else
                            <span class="status-badge status-pending">Benutzer</span>
                        @endif
                    </td>
                    <td style="text-align:right">
                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-secondary">Details</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
