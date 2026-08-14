@extends('layouts.app')

@section('title', 'JTL-Wawi Connect')

@section('content')
<div class="page-header">
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">JTL-Wawi Connect</div>
    <div class="page-subtitle">Verbinde deine JTL-Wawi SQL Server Datenbank</div>
</div>

<form method="POST" action="{{ route('jtl-connect.update') }}" id="jtlForm">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-title"><i data-lucide="database" width="16" height="16"></i> Datenbank-Verbindung</div>
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
    </div>

    <div style="display:flex;gap:var(--space-3)">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save" width="16" height="16"></i> Verbindung speichern
        </button>
        <button type="button" class="btn btn-secondary" id="btnTest">
            <i data-lucide="activity" width="16" height="16"></i> Verbindung testen
        </button>
    </div>
</form>

<div id="testResult" style="margin-top:var(--space-4)"></div>
@endsection

@section('scripts')
<script>
document.getElementById('btnTest').addEventListener('click', async function () {
    const btn    = this;
    const result = document.getElementById('testResult');
    const form   = document.getElementById('jtlForm');

    const data = new FormData(form);
    data.set('_method', 'POST');

    btn.disabled = true;
    btn.textContent = 'Teste…';

    try {
        const resp = await fetch('{{ route("jtl-connect.test") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: data,
        });
        const json = await resp.json();
        result.className = json.success ? 'alert alert-success' : 'alert alert-error';
        result.textContent = json.message;
    } catch (e) {
        result.className = 'alert alert-error';
        result.textContent = 'Fehler: ' + e.message;
    }

    btn.disabled = false;
    btn.textContent = 'Verbindung testen';
});
</script>
@endsection
