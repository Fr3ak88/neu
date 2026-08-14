@extends('layouts.app')

@section('title', $account->name . ' bearbeiten')

@section('content')
<div class="page-header">
    <a href="{{ route('amazon-accounts.show', $account) }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">Account bearbeiten</div>
    <div class="page-subtitle">{{ $account->name }}</div>
</div>

<form method="POST" action="{{ route('amazon-accounts.update', $account) }}">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-title"><i data-lucide="settings" width="16" height="16"></i> Kontodaten</div>
        <div class="form-grid">
            <div class="field">
                <label>Name <span class="req">*</span></label>
                <input type="text" name="name" class="inp" value="{{ old('name', $account->name) }}">
            </div>
            <div class="field">
                <label>Marketplace ID <span class="req">*</span></label>
                <select name="marketplace_id" class="inp">
                    @php
                        $mps = [
                            'A1PA6795UKMFR9' => 'Amazon.de',
                            'A1V3QWFUAP3C01' => 'Amazon.nl',
                            'A1RKKUPIHCS9HS' => 'Amazon.es',
                            'A13V1IB3VIYZZH' => 'Amazon.fr',
                            'A1F83G8C2ARO7P' => 'Amazon.co.uk',
                            'A21TJRUUN4KGV'  => 'Amazon.it',
                            'A2825NDLA7WDZV' => 'Amazon.com.tr',
                            'A1VC38T7YXB528' => 'Amazon.pl',
                        ];
                    @endphp
                    @foreach($mps as $mp => $label)
                        <option value="{{ $mp }}" {{ old('marketplace_id', $account->marketplace_id) === $mp ? 'selected' : '' }}>
                            {{ $label }} ({{ $mp }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Seller ID</label>
                <input type="text" name="seller_id" class="inp" value="{{ old('seller_id', $account->seller_id) }}">
            </div>
            <div class="field">
                <label>Region <span class="req">*</span></label>
                <select name="region" class="inp">
                    @foreach(['eu' => 'EU (sellingpartnerapi-eu)', 'na' => 'NA (sellingpartnerapi-na)', 'fe' => 'FE (sellingpartnerapi-fe)'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('region', $account->region) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:var(--space-4)">
        <div class="card-title"><i data-lucide="key" width="16" height="16"></i> SP-API Credentials</div>
        <p style="font-size:0.8125rem;color:var(--text-secondary);margin-bottom:var(--space-4)">
            Lass die Felder leer, um die aktuellen Werte beizubehalten.
        </p>
        <div class="form-grid">
            <div class="field">
                <label>LWA Client ID <span class="req">*</span></label>
                <input type="text" name="lwa_client_id" class="inp" value="{{ old('lwa_client_id', $account->lwa_client_id) }}">
            </div>
            <div class="field">
                <label>LWA Client Secret</label>
                <input type="password" name="lwa_client_secret" class="inp" placeholder="Nicht geändert">
            </div>
            <div class="field form-full">
                <label>LWA Refresh Token</label>
                <input type="password" name="lwa_refresh_token" class="inp" placeholder="Nicht geändert">
            </div>
        </div>
    </div>

    <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4)">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="check" width="16" height="16"></i> Änderungen speichern
        </button>
        <a href="{{ route('amazon-accounts.show', $account) }}" class="btn btn-ghost">Abbrechen</a>
    </div>
</form>
@endsection
