@extends('layouts.app')

@section('title', 'JTL-Wawi Cloud API Connect')

@section('content')
<div class="page-header">
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">JTL-Wawi Cloud API Connect</div>
    <div class="page-subtitle">Verbinde deine JTL-Wawi-Instanz über den Cloud API-Zugang</div>
</div>

<form method="POST" action="{{ route('jtl-cloud.configure') }}" id="cloudForm">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-title"><i data-lucide="key" width="16" height="16"></i> Cloud API Anmeldeinformationen</div>
        <div class="form-grid">
            <div class="field form-full">
                <label>Client ID (Partner Portal) <span class="req">*</span></label>
                <input type="text" name="jtl_client_id" class="inp" placeholder="z.B. client_xxxxx" value="{{ old('jtl_client_id', $user->jtl_cloud_client_id ? '•••••••• (gesetzt)' : '') }}" required>
                <div class="field-hint">Client ID, den Sie im JTL Partner Portal erhalten haben.</div>
            </div>
            <div class="field form-full">
                <label>Client Secret <span class="req">*</span></label>
                <input type="password" name="jtl_client_secret" class="inp" placeholder="Client Secret eingeben" value="{{ old('jtl_client_secret') }}" required>
                <div class="field-hint">Client Secret aus dem Partner Portal (geheim, niemals teilen)</div>
            </div>
            <div class="field form-full">
                <label>Tenant ID <span class="req">*</span></label>
                <input type="text" name="jtl_tenant_id" class="inp" placeholder="z.B. tenant_xxxxx" value="{{ old('jtl_tenant_id', $user->jtl_cloud_tenant_id) }}" required>
                <div class="field-hint">Die JTL-Wawi Tenant ID für die spezifische Instanz.</div>
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
    const form   = document.getElementById('cloudForm');

    const data = new FormData(form);

    btn.disabled = true;
    btn.textContent = 'Teste…';

    try {
        const resp = await fetch('{{ route("jtl-cloud.test") }}', {
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
