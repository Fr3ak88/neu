@extends('layouts.app')

@section('title', 'Team verwalten')

@section('content')
<div class="page-header">
    <div class="page-title">Team verwalten</div>
    <div class="page-subtitle">Benutzer innerhalb deiner Firma verwalten</div>
</div>

<div class="action-bar">
    <div class="action-bar-left">
        <span class="article-sku">{{ $users->count() }} Team-Mitglied(er)</span>
    </div>
    <div class="action-bar-right">
        <a href="{{ route('firmenadmin.users.create') }}" class="btn btn-primary">
            <i data-lucide="user-plus" width="16" height="16"></i> Benutzer hinzufügen
        </a>
    </div>
</div>

<div class="card">
    @if($users->isEmpty())
        <div style="text-align:center;padding:var(--space-10) 0;color:var(--color-text-faint)">
            Noch keine weiteren Benutzer vorhanden.
        </div>
    @else
        <table class="article-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>E-Mail</th>
                    <th>Rolle</th>
                    <th>Erstellt</th>
                    <th style="text-align:right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
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
                    <td class="article-sku">{{ $user->created_at->format('d.m.Y') }}</td>
                    <td style="text-align:right;white-space:nowrap">
                        <div style="display:inline-flex;gap:var(--space-2);align-items:center">
                            <a href="{{ route('firmenadmin.users.edit', $user) }}" class="btn btn-sm btn-secondary">Bearbeiten</a>
                            <button type="button" class="btn btn-sm btn-ghost" style="color:var(--color-error)"
                                    onclick="openDeleteModal('{{ $user->id }}', '{{ addslashes($user->name) }}')">
                                <i data-lucide="trash-2" width="12" height="12"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div id="deleteModal" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
        <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-4)">
            <div style="width:40px;height:40px;border-radius:var(--radius-full);background:var(--color-error-highlight);display:flex;align-items:center;justify-content:center">
                <i data-lucide="alert-triangle" width="20" height="20" style="color:var(--color-error)"></i>
            </div>
            <div>
                <div style="font-size:var(--text-base);font-weight:600;color:var(--color-text)">Benutzer wirklich löschen?</div>
                <div style="font-size:var(--text-sm);color:var(--color-text-muted)">Diese Aktion kann nicht rückgängig gemacht werden.</div>
            </div>
        </div>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-6);line-height:1.6">
            Der Benutzer <b id="deleteUserName"></b> wird dauerhaft aus deinem Team entfernt.
        </p>
        <div style="display:flex;gap:var(--space-3);justify-content:flex-end">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('deleteModal').classList.remove('open')">Abbrechen</button>
            <form id="deleteForm" method="POST">
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

<script>
function openDeleteModal(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteForm').action = '{{ url("team") }}/' + userId;
    document.getElementById('deleteModal').classList.add('open');
}
</script>
@endsection
