@extends('layouts.admin')

@section('title', $user->name . ' — Admin')

@section('content')
<div class="page-header">
    <div class="page-title">{{ $user->name }}</div>
    <div class="page-subtitle">Benutzerdetails</div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6)">
    <div class="card">
        <div class="card-title">
            <i data-lucide="user" width="16" height="16"></i> Benutzerdaten
        </div>
        <table class="article-table">
            <tbody>
                <tr><td style="width:140px;color:var(--color-text-muted)">Name</td><td><b>{{ $user->name }}</b></td></tr>
                <tr><td style="color:var(--color-text-muted)">E-Mail</td><td>{{ $user->email }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Registriert</td><td>{{ $user->created_at->format('d.m.Y H:i') }}</td></tr>
                <tr>
                    <td style="color:var(--color-text-muted)">Status</td>
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
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-title">
            <i data-lucide="puzzle" width="16" height="16"></i> Module
        </div>
        @if(!empty($user->modules) && count($user->modules) > 0)
            <div style="display:flex;flex-wrap:wrap;gap:var(--space-2)">
                @foreach($user->modules as $module)
                    <span class="status-badge status-ok">{{ $module }}</span>
                @endforeach
            </div>
        @else
            <div style="text-align:center;padding:var(--space-10) 0;color:var(--color-text-faint)">
                Keine Module aktiviert.
            </div>
        @endif
        <div style="margin-top:var(--space-4)">
            <a href="{{ route('admin.users.modules', $user) }}" class="btn btn-sm btn-secondary">
                <i data-lucide="puzzle" width="14" height="14"></i> Module verwalten
            </a>
        </div>
    </div>
</div>

<div style="margin-top:var(--space-6);display:flex;gap:var(--space-3)">
    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
        <i data-lucide="pencil" width="16" height="16"></i> Bearbeiten
    </a>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left" width="16" height="16"></i> Zurück zur Übersicht
    </a>
</div>
@endsection
