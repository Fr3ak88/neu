@extends('layouts.app')

@section('title', 'E-Mail-Einstellungen')

@section('content')
<div class="page-header">
    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">E-Mail-Einstellungen</div>
    <div class="page-subtitle">Konfiguriere den SMTP-Versand für E-Mails</div>
</div>

<div class="card">
    <div class="card-title"><i data-lucide="mail" width="16" height="16"></i> SMTP-Konfiguration</div>
    <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-4)">
        Konfiguriere die E-Mail-Server-Einstellungen für den Versand von System-E-Mails.
    </p>
    <form method="POST" action="{{ route('email-settings.update') }}" id="formEmail">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <div class="field form-full">
                <label>SMTP Host <span class="req">*</span></label>
                <input type="text" name="MAIL_HOST" class="inp" placeholder="z.B. smtp.gmail.com" value="{{ old('MAIL_HOST', $settings['MAIL_HOST']) }}" required>
            </div>
            <div class="field">
                <label>SMTP Port <span class="req">*</span></label>
                <input type="number" name="MAIL_PORT" class="inp" placeholder="z.B. 587" value="{{ old('MAIL_PORT', $settings['MAIL_PORT']) }}" required>
            </div>
            <div class="field">
                <label>Absender-Name <span class="req">*</span></label>
                <input type="text" name="MAIL_FROM_NAME" class="inp" placeholder="z.B. Fritzler-Solution" value="{{ old('MAIL_FROM_NAME', $settings['MAIL_FROM_NAME']) }}" required>
            </div>
            <div class="field form-full">
                <label>Benutzername</label>
                <input type="text" name="MAIL_USERNAME" class="inp" placeholder="z.B. user@gmail.com" value="{{ old('MAIL_USERNAME', $settings['MAIL_USERNAME']) }}">
            </div>
            <div class="field form-full">
                <label>Passwort</label>
                <input type="password" name="MAIL_PASSWORD" class="inp" placeholder="{{ $settings['MAIL_PASSWORD'] ? '•••••••• (gesetzt — leer lassen zum Beibehalten)' : 'Passwort eingeben' }}">
                <div class="field-hint">Leer lassen, um das bestehende Passwort beizubehalten.</div>
            </div>
            <div class="field form-full">
                <label>Absender-Adresse <span class="req">*</span></label>
                <input type="email" name="MAIL_FROM_ADDRESS" class="inp" placeholder="z.B. info@example.com" value="{{ old('MAIL_FROM_ADDRESS', $settings['MAIL_FROM_ADDRESS']) }}" required>
            </div>
        </div>
        <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4)">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save" width="16" height="16"></i> Speichern
            </button>
            <button type="button" class="btn btn-secondary" onclick="testEmail()">
                <i data-lucide="send" width="16" height="16"></i> Test-E-Mail senden
            </button>
        </div>
    </form>
    <div id="resultEmail" style="margin-top:var(--space-4)"></div>
</div>

<div id="testModal" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-4)">
            <div style="font-weight:600;font-size:var(--text-base)">Test-E-Mail senden</div>
            <button onclick="document.getElementById('testModal').classList.remove('open')" class="icon-btn" style="margin:0">
                <i data-lucide="x" width="16" height="16"></i>
            </button>
        </div>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-4)">
            An welche Adresse soll die Test-E-Mail gesendet werden?
        </p>
        <div class="field" style="margin-bottom:var(--space-4)">
            <input type="email" id="testEmailInput" class="inp" placeholder="test@example.com" value="{{ $settings['MAIL_FROM_ADDRESS'] }}">
        </div>
        <div style="display:flex;gap:var(--space-3);justify-content:flex-end">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('testModal').classList.remove('open')">Abbrechen</button>
            <button type="button" class="btn btn-primary" onclick="sendTestEmail()">
                <i data-lucide="send" width="14" height="14"></i> Senden
            </button>
        </div>
    </div>
</div>

<style>
.modal-overlay{display:none;position:fixed;inset:0;z-index:100;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:var(--space-6)}
.modal-overlay.open{display:flex}
.modal-card{width:100%;max-width:28rem;background:var(--color-surface);border:1px solid var(--color-border);border-radius:var(--radius-xl);padding:var(--space-6);box-shadow:var(--shadow-lg)}
</style>
@endsection

@section('scripts')
<script>
function testEmail() {
    document.getElementById('testModal').classList.add('open');
}

async function sendTestEmail() {
    const emailInput = document.getElementById('testEmailInput');
    const email = emailInput.value.trim();
    if (!email) {
        emailInput.focus();
        return;
    }

    const btn = document.querySelector('#testModal .btn-primary');
    const form = document.getElementById('formEmail');
    const data = new FormData(form);
    data.delete('_method');
    data.append('TEST_EMAIL', email);

    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader" width="14" height="14"></i> Sende…';
    lucide.createIcons();

    document.getElementById('testModal').classList.remove('open');

    try {
        const resp = await fetch('{{ route("email-settings.test") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: data
        });
        const text = await resp.text();
        let json;
        try {
            json = JSON.parse(text);
        } catch (e) {
            throw new Error('Unerwartete Antwort vom Server');
        }
        const el = document.getElementById('resultEmail');
        el.className = json.success ? 'alert alert-success' : 'alert alert-error';
        el.textContent = json.message;
    } catch (e) {
        const el = document.getElementById('resultEmail');
        el.className = 'alert alert-error';
        el.textContent = 'Fehler: ' + e.message;
    }
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="send" width="14" height="14"></i> Senden';
    lucide.createIcons();
}
</script>
@endsection
