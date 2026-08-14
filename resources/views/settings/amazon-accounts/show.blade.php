@extends('layouts.app')

@section('title', $account->name)

@section('content')
<div class="page-header">
    <div class="page-title">{{ $account->name }}</div>
    <div class="page-subtitle">{{ $account->marketplace_id }} · {{ $account->seller_id ?? 'Seller ID nicht gesetzt' }}</div>
</div>

<div style="display:flex;gap:var(--space-3);margin-bottom:var(--space-6)">
    @if(auth()->user()->isFirmenadmin() || auth()->user()->isSuperadmin())
    @if($account->active)
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('deactivateModal').classList.add('open')">
            <i data-lucide="pause" width="16" height="16"></i> Deaktivieren
        </button>
    @else
        <button type="button" class="btn btn-success" onclick="document.getElementById('activateModal').classList.add('open')">
            <i data-lucide="play" width="16" height="16"></i> Aktivieren
        </button>
    @endif
    @endif
    <button id="btnTest"
            data-url="{{ route('amazon-accounts.test-connection', $account) }}"
            class="btn btn-secondary">
        Verbindung testen
    </button>
    @if(auth()->user()->isFirmenadmin() || auth()->user()->isSuperadmin())
    <a href="{{ route('amazon-accounts.edit', $account) }}" class="btn btn-primary">
        Bearbeiten
    </a>
    <button type="button" class="btn btn-ghost" style="color:var(--color-error);margin-left:auto"
            onclick="document.getElementById('deleteModal').classList.add('open')">
        <i data-lucide="trash-2" width="16" height="16"></i> Löschen
    </button>
    @endif
</div>

<div id="testResult" class="mb-4 hidden"></div>

<div class="card">
    <div class="card-title"><i data-lucide="settings" width="16" height="16"></i> Kontodaten</div>
    <div style="display:flex;flex-direction:column;gap:var(--space-3)">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--color-text-muted)">LWA Client ID</span>
            <span class="article-sku">{{ $account->lwa_client_id }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--color-text-muted)">Client Secret</span>
            <span class="article-sku">••••••••• (verschlüsselt)</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--color-text-muted)">Refresh Token</span>
            <span class="article-sku">••••••••• (verschlüsselt)</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--color-text-muted)">Region</span>
            <span>{{ $account->region }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="color:var(--color-text-muted)">Status</span>
            @if($account->active)
                <span class="status-badge status-ok"><i data-lucide="check" width="10" height="10"></i> Aktiv</span>
            @else
                <span class="status-badge status-pending"><i data-lucide="circle" width="10" height="10"></i> Inaktiv</span>
            @endif
        </div>
    </div>
</div>

<!-- Deactivate Modal -->
<div id="deactivateModal" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
        <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-4)">
            <div style="width:40px;height:40px;border-radius:var(--radius-full);background:var(--color-warning-highlight);display:flex;align-items:center;justify-content:center">
                <i data-lucide="alert-triangle" width="20" height="20" style="color:var(--color-warning)"></i>
            </div>
            <div>
                <div style="font-size:var(--text-base);font-weight:600;color:var(--color-text)">Account deaktivieren?</div>
                <div style="font-size:var(--text-sm);color:var(--color-text-muted)">Dieser Account kann danach keine neuen Pläne mehr erstellen.</div>
            </div>
        </div>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-6);line-height:1.6">
            Der Account <b>{{ $account->name }}</b> wird deaktiviert. Bestehende Umlagerungen bleiben erhalten.
        </p>
        <div style="display:flex;gap:var(--space-3);justify-content:flex-end">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('deactivateModal').classList.remove('open')">Abbrechen</button>
            <form method="POST" action="{{ route('amazon-accounts.toggle-active', $account) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-warning">
                    <i data-lucide="pause" width="16" height="16"></i> Deaktivieren
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Activate Modal -->
<div id="activateModal" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
        <div style="display:flex;align-items:center;gap:var(--space-3);margin-bottom:var(--space-4)">
            <div style="width:40px;height:40px;border-radius:var(--radius-full);background:var(--color-success-highlight);display:flex;align-items:center;justify-content:center">
                <i data-lucide="check-circle" width="20" height="20" style="color:var(--color-success)"></i>
            </div>
            <div>
                <div style="font-size:var(--text-base);font-weight:600;color:var(--color-text)">Account aktivieren?</div>
                <div style="font-size:var(--text-sm);color:var(--color-text-muted)">Der Account kann dann wieder für neue Pläne verwendet werden.</div>
            </div>
        </div>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-6);line-height:1.6">
            Der Account <b>{{ $account->name }}</b> wird aktiviert.
        </p>
        <div style="display:flex;gap:var(--space-3);justify-content:flex-end">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('activateModal').classList.remove('open')">Abbrechen</button>
            <form method="POST" action="{{ route('amazon-accounts.toggle-active', $account) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-success">
                    <i data-lucide="play" width="16" height="16"></i> Aktivieren
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
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
            Der Amazon-Account <b>{{ $account->name }}</b> wird dauerhaft gelöscht. Bestehende Umlagerungen bleiben erhalten, können aber keine neuen Pläne mehr erstellen.
        </p>
        <div style="display:flex;gap:var(--space-3);justify-content:flex-end">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('deleteModal').classList.remove('open')">Abbrechen</button>
            <form method="POST" action="{{ route('amazon-accounts.destroy', $account) }}">
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
document.getElementById('btnTest').addEventListener('click', async function () {
    const btn    = this;
    const result = document.getElementById('testResult');
    btn.disabled = true;
    btn.textContent = 'Teste…';

    try {
        const resp = await fetch(btn.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            }
        });
        const data = await resp.json();
        result.className = data.success
            ? 'alert alert-success'
            : 'alert alert-error';
        result.textContent = data.message + (data.marketplaces ? ' · Marketplaces: ' + data.marketplaces.join(', ') : '');
        result.classList.remove('hidden');
    } catch (e) {
        result.className = 'alert alert-error';
        result.textContent = 'Fehler: ' + e.message;
        result.classList.remove('hidden');
    }

    btn.disabled = false;
    btn.textContent = 'Verbindung testen';
});
</script>
@endsection
