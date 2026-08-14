@extends('layouts.admin')

@section('title', $tenant->company . ' — Admin')

@section('content')
<div class="page-header">
    <div class="page-title">{{ $tenant->company }}</div>
    <div class="page-subtitle">Firmendetails</div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6)">
    <div class="card">
        <div class="card-title">
            <i data-lucide="building-2" width="16" height="16"></i> Adressdaten
        </div>
        <table class="article-table">
            <tbody>
                <tr><td style="width:140px;color:var(--color-text-muted)">Firma</td><td><b>{{ $tenant->company }}</b></td></tr>
                <tr><td style="color:var(--color-text-muted)">Name</td><td>{{ $tenant->name }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Straße</td><td>{{ $tenant->street }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">PLZ / Ort</td><td>{{ $tenant->zip }} {{ $tenant->city }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Land</td><td>{{ $tenant->country }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Telefon</td><td>{{ $tenant->phone }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Plan</td><td><span class="status-badge status-ok">{{ $tenant->plan }}</span></td></tr>
                <tr><td style="color:var(--color-text-muted)">Erstellt</td><td>{{ $tenant->created_at->format('d.m.Y H:i') }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-title">
            <i data-lucide="users" width="16" height="16"></i> Zugeordnete Benutzer ({{ $tenant->users->count() }})
        </div>
        @if($tenant->users->isEmpty())
            <div style="text-align:center;padding:var(--space-10) 0;color:var(--color-text-faint)">
                Keine Benutzer zugeordnet.
            </div>
        @else
            <table class="article-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tenant->users as $user)
                    <tr>
                        <td><b>{{ $user->name }}</b></td>
                        <td class="article-sku">{{ $user->email }}</td>
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
</div>

<div style="margin-top:var(--space-6);display:flex;gap:var(--space-3)">
    <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn btn-primary">
        <i data-lucide="pencil" width="16" height="16"></i> Bearbeiten
    </a>
    <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteModal').classList.add('open')">
        <i data-lucide="trash-2" width="16" height="16"></i> Löschen
    </button>
    <a href="{{ route('admin.tenants.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left" width="16" height="16"></i> Zurück zur Übersicht
    </a>
</div>

<div id="deleteModal" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
        <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-4)">
            <div style="width:40px;height:40px;border-radius:var(--radius-full);background:var(--color-error-highlight);display:flex;align-items:center;justify-content:center">
                <i data-lucide="alert-triangle" width="20" height="20" style="color:var(--color-error)"></i>
            </div>
            <div>
                <div style="font-size:var(--text-base);font-weight:600;color:var(--color-text)">Firma wirklich löschen?</div>
                <div style="font-size:var(--text-sm);color:var(--color-text-muted)">Diese Aktion kann nicht rückgängig gemacht werden.</div>
            </div>
        </div>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-6);line-height:1.6">
            Alle Daten der Firma <b>{{ $tenant->company }}</b> including Benutzer, Amazon-Accounts und Umlagerungen werden dauerhaft gelöscht.
        </p>
        <div style="display:flex;gap:var(--space-3);justify-content:flex-end">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('deleteModal').classList.remove('open')">Abbrechen</button>
            <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i data-lucide="trash-2" width="16" height="16"></i> Wirklich löschen
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.modal-overlay{display:none;position:fixed;inset:0;z-index:100;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:var(--space-6)}
.modal-overlay.open{display:flex}
.modal-card{width:100%;max-width:28rem;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-xl);padding:var(--space-6);box-shadow:var(--shadow-lg)}
</style>
@endsection
