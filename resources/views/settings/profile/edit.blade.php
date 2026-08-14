@extends('layouts.app')

@section('title', 'Mein Profil')

@section('content')
<div class="page-header">
    <div class="page-title">Mein Profil</div>
    <div class="page-subtitle">Deine persönlichen Daten verwalten</div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);max-width:56rem">
    <div class="card">
        <div class="card-title">
            <i data-lucide="user" width="16" height="16"></i> Benutzerdaten
        </div>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div style="display:flex;align-items:center;gap:var(--space-2);padding:var(--space-3) var(--space-4);border-radius:var(--radius-lg);margin-bottom:var(--space-4);font-size:var(--text-sm);background:var(--color-error-highlight);color:var(--color-error);border:1px solid var(--color-error)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="field">
                <label for="name">Name <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="inp" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="field">
                <label for="email">E-Mail <span class="req">*</span></label>
                <input type="email" id="email" name="email" class="inp" value="{{ old('email', $user->email) }}" required>
            </div>

            <div style="margin-top:var(--space-6);margin-bottom:var(--space-2)">
                <div style="font-size:var(--text-sm);font-weight:600;color:var(--color-text-muted);margin-bottom:var(--space-3)">Passwort ändern</div>
            </div>

            <div class="field">
                <label for="current_password">Aktuelles Passwort</label>
                <input type="password" id="current_password" name="current_password" class="inp" placeholder="Nur ausfüllen, wenn Passwort geändert werden soll" autocomplete="current-password">
            </div>

            <div class="field">
                <label for="password">Neues Passwort</label>
                <input type="password" id="password" name="password" class="inp" placeholder="Mind. 8 Zeichen" autocomplete="new-password">
            </div>

            <div class="field">
                <label for="password_confirmation">Passwort bestätigen</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="inp" placeholder="Passwort wiederholen" autocomplete="new-password">
            </div>

            <div style="margin-top:var(--space-6)">
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-title">
            <i data-lucide="building-2" width="16" height="16"></i> Meine Firma
        </div>
        @if($tenant)
            <form method="POST" action="{{ route('profile.update-tenant') }}">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="company">Firma <span class="req">*</span></label>
                    <input type="text" id="company" name="company" class="inp" value="{{ old('company', $tenant->company) }}" required>
                </div>

                <div class="field">
                    <label for="tenant_name">Ansprechpartner <span class="req">*</span></label>
                    <input type="text" id="tenant_name" name="name" class="inp" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="field">
                    <label for="street">Straße und Hausnummer <span class="req">*</span></label>
                    <input type="text" id="street" name="street" class="inp" value="{{ old('street', $tenant->street) }}" required>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="zip">PLZ <span class="req">*</span></label>
                        <input type="text" id="zip" name="zip" class="inp" value="{{ old('zip', $tenant->zip) }}" required>
                    </div>
                    <div class="field">
                        <label for="city">Stadt <span class="req">*</span></label>
                        <input type="text" id="city" name="city" class="inp" value="{{ old('city', $tenant->city) }}" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="country">Land <span class="req">*</span></label>
                        <select id="country" name="country" class="inp" required>
                            <option value="DE" {{ old('country', $tenant->country) === 'DE' ? 'selected' : '' }}>Deutschland</option>
                            <option value="AT" {{ old('country', $tenant->country) === 'AT' ? 'selected' : '' }}>Österreich</option>
                            <option value="CH" {{ old('country', $tenant->country) === 'CH' ? 'selected' : '' }}>Schweiz</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="phone">Telefon <span class="req">*</span></label>
                        <input type="tel" id="phone" name="phone" class="inp" value="{{ old('phone', $tenant->phone) }}" required>
                    </div>
                </div>

                <div class="field">
                    <label for="tenant_email">E-Mail</label>
                    <input type="email" id="tenant_email" name="email" class="inp" value="{{ old('email', $tenant->email) }}">
                </div>

                <div class="field">
                    <label>Plan</label>
                    <div style="padding:var(--space-3) 0"><span class="status-badge status-ok">{{ $tenant->plan }}</span></div>
                </div>

                <div style="margin-top:var(--space-6)">
                    <button type="submit" class="btn btn-primary">Adresse speichern</button>
                </div>
            </form>
        @else
            <div style="text-align:center;padding:var(--space-10) 0;color:var(--color-text-faint)">
                Keine Firma zugeordnet.
            </div>
        @endif
    </div>
</div>

@if($tenant)
<div style="max-width:56rem;margin-top:var(--space-6)">
    <div class="card">
        <div class="card-title">
            <i data-lucide="receipt" width="16" height="16"></i> Rechnungsdaten
        </div>
        <form method="POST" action="{{ route('profile.update-tenant') }}">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="field">
                    <label for="ust_id">USt-IdNr.</label>
                    <input type="text" id="ust_id" name="ust_id" class="inp" value="{{ old('ust_id', $tenant->ust_id) }}" placeholder="DE123456789">
                </div>
                <div class="field">
                    <label for="steuernummer">Steuernummer</label>
                    <input type="text" id="steuernummer" name="steuernummer" class="inp" value="{{ old('steuernummer', $tenant->steuernummer) }}" placeholder="123/456/78901">
                </div>
            </div>

            <div class="field">
                <label for="hrb">HRB</label>
                <input type="text" id="hrb" name="hrb" class="inp" value="{{ old('hrb', $tenant->hrb) }}" placeholder="z.B. HRB 12345">
            </div>

            <div class="field">
                <label for="bank_name">Bankname</label>
                <input type="text" id="bank_name" name="bank_name" class="inp" value="{{ old('bank_name', $tenant->bank_name) }}" placeholder="Deutsche Bank">
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="iban">IBAN</label>
                    <input type="text" id="iban" name="iban" class="inp" value="{{ old('iban', $tenant->iban) }}" placeholder="DE89 3704 0044 0532 0130 00">
                </div>
                <div class="field">
                    <label for="bic">BIC</label>
                    <input type="text" id="bic" name="bic" class="inp" value="{{ old('bic', $tenant->bic) }}" placeholder="COBADEFFXXX">
                </div>
            </div>

            <div style="margin-top:var(--space-6)">
                <button type="submit" class="btn btn-primary">Rechnungsdaten speichern</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
