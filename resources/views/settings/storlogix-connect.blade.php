@extends('layouts.app')

@section('title', 'Storelogix Connect')

@section('content')
<div class="page-header">
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">Storelogix Connect</div>
    <div class="page-subtitle">Verbinde deine Storelogix Lagerverwaltung</div>
</div>

@php
    $activeTab = session('active_tab', 'credentials');
@endphp

<form method="POST" action="{{ route('storlogix-connect.update') }}" id="storlogixForm">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-title"><i data-lucide="warehouse" width="16" height="16"></i> Storelogix API</div>
        <div class="form-grid">
            <div class="field form-full">
                <label>API Base URL <span class="req">*</span></label>
                <input type="url" name="storlogix_api_url" class="inp" placeholder="https://api.storelogix.de:12345" value="{{ old('storlogix_api_url', $tenant->storlogix_api_url ?? '') }}" required>
                <div class="field-hint">Basis-URL der Storelogix API (ohne trailing slash)</div>
            </div>
            <div class="field">
                <label>Benutzername <span class="req">*</span></label>
                <input type="text" name="storlogix_api_key" class="inp" placeholder="IFC-TEST" value="{{ old('storlogix_api_key', $tenant->storlogix_api_key ?? '') }}" required>
                <div class="field-hint">Wird für Basic Auth und Login verwendet</div>
            </div>
            <div class="field">
                <label>Passwort</label>
                <input type="password" name="storlogix_api_secret" class="inp" placeholder="{{ ($tenant->storlogix_api_secret ?? null) ? '•••••••• (gesetzt — leer lassen zum Beibehalten)' : 'Passwort eingeben' }}">
                <div class="field-hint">Wird für Basic Auth und Webhook-Signatur benötigt</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:var(--space-3)">
        <div class="card-title"><i data-lucide="settings" width="16" height="16"></i> Storelogix Konfiguration</div>
        <div class="form-grid">
            <div class="field">
                <label>Client-Name <span class="req">*</span></label>
                <input type="text" name="storlogix_client_name" class="inp" placeholder="MeinClient" value="{{ old('storlogix_client_name', $tenant->storlogix_client_name ?? '') }}" required>
                <div class="field-hint">Name des Subclients in Storelogix</div>
            </div>
            <div class="field">
                <label>Location</label>
                <input type="text" name="storlogix_location" class="inp" placeholder="STD" value="{{ old('storlogix_location', $tenant->storlogix_location ?? '') }}">
                <div class="field-hint">Standort-Code (z.B. STD)</div>
            </div>
            <div class="field">
                <label>Warehouse</label>
                <input type="text" name="storlogix_warehouse" class="inp" placeholder="Lager1" value="{{ old('storlogix_warehouse', $tenant->storlogix_warehouse ?? '') }}">
                <div class="field-hint">Lager-Bezeichnung</div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:var(--space-3);margin-top:var(--space-3)">
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
    const form   = document.getElementById('storlogixForm');

    const data = new FormData(form);
    data.set('_method', 'POST');

    btn.disabled = true;
    btn.textContent = 'Teste…';

    try {
        const resp = await fetch('{{ route("storlogix-connect.test") }}', {
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
