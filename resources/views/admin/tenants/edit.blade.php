@extends('layouts.admin')

@section('title', $tenant->company . ' bearbeiten — Admin')

@section('content')
<div class="page-header">
    <div class="page-title">{{ $tenant->company }} bearbeiten</div>
    <div class="page-subtitle">Firmendaten ändern</div>
</div>

<div style="max-width:40rem">
    <div class="card">
        <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="login-error" style="margin-bottom:var(--space-5)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="field">
                <label for="company">Firma <span class="req">*</span></label>
                <input type="text" id="company" name="company" class="inp" value="{{ old('company', $tenant->company) }}" required>
            </div>

            <div class="field">
                <label for="name">Ansprechpartner <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="inp" value="{{ old('name', $tenant->name) }}" required>
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
                <label for="email">E-Mail</label>
                <input type="email" id="email" name="email" class="inp" value="{{ old('email', $tenant->email) }}">
            </div>

            <div class="field">
                <label for="plan">Plan <span class="req">*</span></label>
                <select id="plan" name="plan" class="inp" required>
                    <option value="free" {{ old('plan', $tenant->plan) === 'free' ? 'selected' : '' }}>Free</option>
                    <option value="basic" {{ old('plan', $tenant->plan) === 'basic' ? 'selected' : '' }}>Basic</option>
                    <option value="pro" {{ old('plan', $tenant->plan) === 'pro' ? 'selected' : '' }}>Pro</option>
                    <option value="enterprise" {{ old('plan', $tenant->plan) === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                </select>
            </div>

            <div style="border-top:1px solid var(--color-border);margin:var(--space-5) 0;padding-top:var(--space-5)">
                <div style="font-size:var(--text-sm);font-weight:600;color:var(--color-text-muted);margin-bottom:var(--space-3)">Rechnungsdaten</div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="ust_id">USt-IdNr.</label>
                    <input type="text" id="ust_id" name="ust_id" class="inp" value="{{ old('ust_id', $tenant->ust_id) }}">
                </div>
                <div class="field">
                    <label for="steuernummer">Steuernummer</label>
                    <input type="text" id="steuernummer" name="steuernummer" class="inp" value="{{ old('steuernummer', $tenant->steuernummer) }}">
                </div>
            </div>

            <div class="field">
                <label for="hrb">HRB</label>
                <input type="text" id="hrb" name="hrb" class="inp" value="{{ old('hrb', $tenant->hrb) }}">
            </div>

            <div class="field">
                <label for="bank_name">Bankname</label>
                <input type="text" id="bank_name" name="bank_name" class="inp" value="{{ old('bank_name', $tenant->bank_name) }}">
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="iban">IBAN</label>
                    <input type="text" id="iban" name="iban" class="inp" value="{{ old('iban', $tenant->iban) }}">
                </div>
                <div class="field">
                    <label for="bic">BIC</label>
                    <input type="text" id="bic" name="bic" class="inp" value="{{ old('bic', $tenant->bic) }}">
                </div>
            </div>

            <div style="display:flex;gap:var(--space-3);margin-top:var(--space-6)">
                <button type="submit" class="btn btn-primary">Speichern</button>
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
@endsection
