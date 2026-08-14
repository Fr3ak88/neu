@extends('layouts.app')

@section('title', 'JTL-Wawi Einstellungen')

@section('content')
<div class="page-header">
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">JTL-Wawi Einstellungen</div>
    <div class="page-subtitle">Verbinde JTL-Wawi mit deiner Anwendung</div>
</div>

@if($jtlConfigured)
    <div style="display:flex;gap:var(--space-3);margin-bottom:var(--space-4)">
        <span class="status-badge status-ok" style="padding:var(--space-2) var(--space-4);font-size:var(--text-sm)">
            <i data-lucide="check-circle" width="14" height="14"></i>
            Verbunden via {{ $jtlMode === 'cloud' ? 'Cloud API' : 'API-Key' }}
        </span>
        @if($jtlAuthenticated)
            <span class="status-badge status-ok" style="padding:var(--space-2) var(--space-4);font-size:var(--text-sm)">
                Token gültig
            </span>
        @else
            <span class="status-badge status-error" style="padding:var(--space-2) var(--space-4);font-size:var(--text-sm)">
                Token abgelaufen
            </span>
        @endif
    </div>
@endif

<style>
    .tabs { display:flex; gap:0; border-bottom:2px solid var(--color-border); margin-bottom:var(--space-6); }
    .tab-btn { padding:var(--space-3) var(--space-5); background:none; border:none; border-bottom:2px solid transparent; margin-bottom:-2px; cursor:pointer; font-size:var(--text-sm); font-weight:500; color:var(--color-text-muted); display:flex; align-items:center; gap:var(--space-2); transition:all .15s; }
    .tab-btn:hover { color:var(--color-text); }
    .tab-btn.active { color:var(--color-primary); border-bottom-color:var(--color-primary); }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }
    .tab-status { width:8px; height:8px; border-radius:50%; display:inline-block; }
    .tab-status.ok { background:var(--color-success, #22c55e); }
    .tab-status.off { background:var(--color-text-faint); }
</style>

<div class="tabs" role="tablist">
    <button class="tab-btn active" onclick="switchTab('db')" id="tab-btn-db" role="tab">
        <i data-lucide="database" width="14" height="14"></i>
        Datenbank (OnPremise)
        @if(!empty($user->jtl_host))
            <span class="tab-status ok"></span>
        @endif
    </button>
    <button class="tab-btn" onclick="switchTab('apikey')" id="tab-btn-apikey" role="tab">
        <i data-lucide="key" width="14" height="14"></i>
        API-Key (OnPremise)
        @if($jtlConfigured)
            <span class="tab-status ok"></span>
        @endif
    </button>
    <button class="tab-btn" onclick="switchTab('cloud')" id="tab-btn-cloud" role="tab">
        <i data-lucide="cloud" width="14" height="14"></i>
        Cloud API
        @if(!empty($user->jtl_cloud_client_id))
            <span class="tab-status ok"></span>
        @endif
    </button>
</div>

{{-- ═══ TAB 1: Datenbank ═══ --}}
<div class="tab-panel active" id="panel-db" role="tabpanel">
    <div class="card">
        <div class="card-title"><i data-lucide="database" width="16" height="16"></i> SQL Server Datenbankverbindung</div>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-4)">
            Verbinde dich direkt mit der JTL-Wawi SQL Server Datenbank auf deinem lokalen Rechner oder Netzwerk.
        </p>
        <form method="POST" action="{{ route('jtl.settings.db.save') }}" id="formDb">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="field">
                    <label>Host <span class="req">*</span></label>
                    <input type="text" name="jtl_host" class="inp" placeholder="z.B. 192.168.1.100" value="{{ old('jtl_host', $user->jtl_host) }}" required>
                </div>
                <div class="field">
                    <label>Port <span class="req">*</span></label>
                    <input type="text" name="jtl_port" class="inp" placeholder="z.B. 1433" value="{{ old('jtl_port', $user->jtl_port) }}" required>
                </div>
                <div class="field">
                    <label>Datenbank <span class="req">*</span></label>
                    <input type="text" name="jtl_database" class="inp" placeholder="z.B. jtl_wawi" value="{{ old('jtl_database', $user->jtl_database) }}" required>
                </div>
                <div class="field">
                    <label>Benutzername <span class="req">*</span></label>
                    <input type="text" name="jtl_username" class="inp" placeholder="z.B. sa" value="{{ old('jtl_username', $user->jtl_username) }}" required>
                </div>
                <div class="field form-full">
                    <label>Passwort</label>
                    <input type="password" name="jtl_password" class="inp" placeholder="{{ $user->jtl_password ? '•••••••• (gesetzt — leer lassen zum Beibehalten)' : 'Passwort eingeben' }}">
                    <div class="field-hint">Leer lassen, um das bestehende Passwort beizubehalten.</div>
                </div>
            </div>
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4)">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save" width="16" height="16"></i> Speichern
                </button>
                <button type="button" class="btn btn-secondary" onclick="testDb()">
                    <i data-lucide="activity" width="16" height="16"></i> Testen
                </button>
            </div>
        </form>
        <div id="resultDb" style="margin-top:var(--space-4)"></div>
    </div>
</div>

{{-- ═══ TAB 2: API-Key ═══ --}}
<div class="tab-panel" id="panel-apikey" role="tabpanel">
    <div class="card">
        <div class="card-title"><i data-lucide="key" width="16" height="16"></i> JTL-Wawi API-Key</div>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-4)">
            Verbinde dich über die JTL-Wawi REST API. Den API-Key findest du in JTL-Wawi unter <b>Admin → App-Registrierung</b>.
        </p>
        <form method="POST" action="{{ route('jtl.settings.apikey.save') }}" id="formApiKey">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="field form-full">
                    <label>API-Key <span class="req">*</span></label>
                    <input type="text" name="jtl_api_key" class="inp" placeholder="Dein JTL-Wawi API-Key" value="{{ old('jtl_api_key', $tenant->jtl_api_key ? '•••••••• (gesetzt)' : '') }}" required>
                </div>
                <div class="field form-full">
                    <label>Tenant-ID <span class="req">*</span></label>
                    <input type="text" name="jtl_tenant_id" class="inp" placeholder="UUID deines JTL-Wawi Tenants" value="{{ old('jtl_tenant_id', $tenant->jtl_tenant_id) }}" required>
                </div>
            </div>
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4)">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save" width="16" height="16"></i> Speichern & Authentifizieren
                </button>
                <button type="button" class="btn btn-secondary" onclick="testApiKey()">
                    <i data-lucide="activity" width="16" height="16"></i> Verbindung testen
                </button>
            </div>
        </form>
        <div id="resultApiKey" style="margin-top:var(--space-4)"></div>
    </div>
</div>

{{-- ═══ TAB 3: Cloud API ═══ --}}
<div class="tab-panel" id="panel-cloud" role="tabpanel">
    <div class="card">
        <div class="card-title"><i data-lucide="cloud" width="16" height="16"></i> JTL-Wawi Cloud API (OAuth2)</div>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-4)">
            Verbinde dich über die JTL Cloud API mit Client Credentials. Registriere deine App im <a href="https://partner.jtl-cloud.com" target="_blank" style="color:var(--color-primary)">JTL Partner Portal</a>.
        </p>
        <form method="POST" action="{{ route('jtl.settings.cloud.save') }}" id="formCloud">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="field form-full">
                    <label>Client ID <span class="req">*</span></label>
                    <input type="text" name="jtl_client_id" class="inp" placeholder="z.B. client_xxxxx" value="{{ old('jtl_client_id', $user->jtl_cloud_client_id ? '•••••••• (gesetzt)' : '') }}" required>
                    <div class="field-hint">Client ID aus dem JTL Partner Portal.</div>
                </div>
                <div class="field form-full">
                    <label>Client Secret <span class="req">*</span></label>
                    <input type="password" name="jtl_client_secret" class="inp" placeholder="Client Secret eingeben" value="{{ old('jtl_client_secret') }}" required>
                    <div class="field-hint">Client Secret aus dem Partner Portal.</div>
                </div>
                <div class="field form-full">
                    <label>Tenant ID <span class="req">*</span></label>
                    <input type="text" name="jtl_cloud_tenant_id" class="inp" placeholder="z.B. tenant_xxxxx" value="{{ old('jtl_cloud_tenant_id', $user->jtl_cloud_tenant_id) }}" required>
                    <div class="field-hint">Die JTL-Wawi Tenant ID für die spezifische Instanz.</div>
                </div>
            </div>
            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4)">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save" width="16" height="16"></i> Speichern
                </button>
                <button type="button" class="btn btn-secondary" onclick="testCloud()">
                    <i data-lucide="activity" width="16" height="16"></i> Verbindung testen
                </button>
            </div>
        </form>
        <div id="resultCloud" style="margin-top:var(--space-4)"></div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-btn-' + tab).classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
}

function showResult(elId, success, message) {
    const el = document.getElementById(elId);
    el.className = success ? 'alert alert-success' : 'alert alert-error';
    el.textContent = message;
}

async function testDb() {
    const btn = event.target;
    const form = document.getElementById('formDb');
    const data = new FormData(form);
    data.delete('_method');
    btn.disabled = true; btn.textContent = 'Teste…';
    try {
        const resp = await fetch('{{ route("jtl.settings.db.test") }}', {
            method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: data
        });
        const json = await resp.json();
        showResult('resultDb', json.success, json.message);
    } catch (e) { showResult('resultDb', false, 'Fehler: ' + e.message); }
    btn.disabled = false; btn.textContent = 'Testen';
}

async function testApiKey() {
    const btn = event.target;
    const form = document.getElementById('formApiKey');
    const data = new FormData(form);
    data.delete('_method');
    btn.disabled = true; btn.textContent = 'Teste…';
    try {
        const resp = await fetch('{{ route("jtl.settings.apikey.test") }}', {
            method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: data
        });
        const json = await resp.json();
        showResult('resultApiKey', json.success, json.message);
    } catch (e) { showResult('resultApiKey', false, 'Fehler: ' + e.message); }
    btn.disabled = false; btn.textContent = 'Verbindung testen';
}

async function testCloud() {
    const btn = event.target;
    const form = document.getElementById('formCloud');
    const data = new FormData(form);
    data.delete('_method');
    btn.disabled = true; btn.textContent = 'Teste…';
    try {
        const resp = await fetch('{{ route("jtl.settings.cloud.test") }}', {
            method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: data
        });
        const json = await resp.json();
        showResult('resultCloud', json.success, json.message);
    } catch (e) { showResult('resultCloud', false, 'Fehler: ' + e.message); }
    btn.disabled = false; btn.textContent = 'Verbindung testen';
}
</script>
@endsection
