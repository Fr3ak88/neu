@extends('layouts.app')

@section('title', 'Amazon Accounts')

@section('content')
<div class="page-header">
    <div class="page-title">Amazon Accounts</div>
    <div class="page-subtitle">Verwalte deine verbundenen Amazon Seller-Konten</div>
</div>

<div class="action-bar">
    <div class="action-bar-left">
        <span class="article-sku">{{ $accounts->count() }} Account(s) verbunden</span>
    </div>
    <div class="action-bar-right">
        @if(auth()->user()->isFirmenadmin() || auth()->user()->isSuperadmin())
        <a href="{{ route('amazon-accounts.create') }}" class="btn btn-primary">
            <i data-lucide="plus" width="16" height="16"></i> Account hinzufügen
        </a>
        @endif
    </div>
</div>

<div class="card">
    @if($accounts->isEmpty())
        <div style="text-align:center;padding:var(--space-10) 0;color:var(--color-text-faint)">
            Noch kein Amazon Account verbunden.
        </div>
    @else
        <table class="article-table">
            <thead><tr><th>Name</th><th>Marketplace</th><th>Status</th><th style="text-align:right">Aktionen</th></tr></thead>
            <tbody>
                @foreach($accounts as $account)
                <tr>
                    <td><b>{{ $account->name }}</b></td>
                    <td class="article-sku">{{ $account->marketplace_id }}</td>
                    <td>
                        @if($account->active)
                            <span class="status-badge status-ok"><i data-lucide="check" width="10" height="10"></i> Aktiv</span>
                        @else
                            <span class="status-badge status-pending">Inaktiv</span>
                        @endif
                    </td>
                    <td style="text-align:right;white-space:nowrap">
                        <div style="display:inline-flex;gap:var(--space-2);align-items:center">
                            <a href="{{ route('amazon-accounts.show', $account) }}" class="btn btn-sm btn-secondary">Details</a>
                            @if(auth()->user()->isFirmenadmin() || auth()->user()->isSuperadmin())
                            <a href="{{ route('amazon-accounts.edit', $account) }}" class="btn btn-sm btn-ghost">
                                <i data-lucide="pencil" width="12" height="12"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-ghost" style="color:var(--color-error)"
                                    onclick="openDeleteModal('{{ $account->id }}', '{{ $account->name }}')">
                                <i data-lucide="trash-2" width="12" height="12"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal-overlay" onclick="if(event.target===this)closeDeleteModal()">
    <div class="modal-card">
        <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-4)">
            <div style="width:40px;height:40px;border-radius:var(--radius-full);background:var(--color-error-highlight);display:flex;align-items:center;justify-content:center">
                <i data-lucide="alert-triangle" width="20" height="20" style="color:var(--color-error)"></i>
            </div>
            <div>
                <div style="font-size:var(--text-base);font-weight:600;color:var(--color-text)">Account wirklich löschen?</div>
                <div style="font-size:var(--text-sm);color:var(--color-text-muted)">Diese Aktion kann nicht rückgängig gemacht werden.</div>
            </div>
        </div>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-6);line-height:1.6">
            Der Amazon-Account <b id="deleteAccountName"></b> wird dauerhaft gelöscht. Bestehende Umlagerungen bleiben erhalten, können aber keine neuen Pläne mehr erstellen.
        </p>
        <div style="display:flex;gap:var(--space-3);justify-content:flex-end">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Abbrechen</button>
            <form method="POST" id="deleteForm">
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
let deleteAccountId = null;

function openDeleteModal(id, name) {
    deleteAccountId = id;
    document.getElementById('deleteAccountName').textContent = name;
    document.getElementById('deleteForm').action = '/amazon-accounts/' + id;
    document.getElementById('deleteModal').classList.add('open');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('open');
    deleteAccountId = null;
}
</script>
@endsection
