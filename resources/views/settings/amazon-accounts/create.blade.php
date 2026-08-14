@extends('layouts.app')

@section('title', 'Account hinzufügen')

@section('content')
<div class="page-header">
    <div class="page-title">Amazon Account hinzufügen</div>
    <div class="page-subtitle">Verbinde einen neuen Amazon Seller-Account per SP-API</div>
</div>

<form method="POST" action="{{ route('amazon-accounts.store') }}">
    @csrf
    <div class="card">
        <div class="card-title"><i data-lucide="settings" width="16" height="16"></i> Kontodaten</div>
        <div class="form-grid">
            <div class="field">
                <label>Name <span class="req">*</span></label>
                <input type="text" name="name" class="inp" placeholder="z.B. DE MeinShop GmbH" value="{{ old('name') }}">
            </div>
            <div class="field">
                <label>Marketplace <span class="req">*</span></label>
                <select name="marketplace_id" class="inp">
                    <option value="">— Bitte wählen —</option>
                    @php
                        $mps = [
                            'A1PA6795UKMFR9' => 'Amazon.de',
                            'A1F83G8C2ARO7P' => 'Amazon.co.uk',
                            'A13V1IB3VIYZZH' => 'Amazon.fr',
                            'A1RKKUPIHCS9HS' => 'Amazon.es',
                            'A21TJRUUN4KGV'  => 'Amazon.it',
                            'A1V3QWFUAP3C01' => 'Amazon.nl',
                            'A1VC38T7YXB528' => 'Amazon.pl',
                            'A2825NDLA7WDZV' => 'Amazon.com.tr',
                            'A2Q3F2V9MKYQXB' => 'Amazon.se',
                            'A1C6SO00UECEJB' => 'Amazon.ae',
                            'A2NODRKZP88ZB9' => 'Amazon.sa',
                            'A1AM78C64UM0Y8' => 'Amazon.com (US)',
                            'A2EUQ1WTGCTBG2' => 'Amazon.ca',
                            'A1AMZ8RQ3WNRSE' => 'Amazon.com.mx',
                            'A17E79C6D8DWNP' => 'Amazon.com.br',
                            'A33AVAJ2PDY3EV' => 'Amazon.in',
                            'A1C6SO00UECEJB' => 'Amazon.sg',
                            'A21Z3CGI8UIP0F' => 'Amazon.com.au',
                        ];
                    @endphp
                    @foreach($mps as $mp => $label)
                        <option value="{{ $mp }}" {{ old('marketplace_id') === $mp ? 'selected' : '' }}>
                            {{ $label }} ({{ $mp }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Region <span class="req">*</span></label>
                <select name="region" class="inp">
                    @foreach(['eu' => 'EU (sellingpartnerapi-eu)', 'na' => 'NA (sellingpartnerapi-na)', 'fe' => 'FE (sellingpartnerapi-fe)'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('region', 'eu') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Seller ID</label>
                <input type="text" name="seller_id" class="inp" placeholder="Merchant Token aus Seller Central" value="{{ old('seller_id') }}">
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:var(--space-4)">
        <div class="card-title"><i data-lucide="key" width="16" height="16"></i> SP-API Credentials</div>
        <p style="font-size:0.8125rem;color:var(--text-secondary);margin-bottom:var(--space-4)">
            Aus Amazon Seller Central > Apps & Services > Develop Apps > LWA Credentials
        </p>
        <div class="form-grid">
            <div class="field">
                <label>LWA Client ID <span class="req">*</span></label>
                <input type="text" name="lwa_client_id" class="inp" placeholder="amzn1.application-oa2-client.xxxx" value="{{ old('lwa_client_id') }}">
            </div>
            <div class="field">
                <label>LWA Client Secret <span class="req">*</span></label>
                <input type="password" name="lwa_client_secret" class="inp" placeholder="••••••••">
            </div>
            <div class="field form-full">
                <label>LWA Refresh Token <span class="req">*</span></label>
                <input type="password" name="lwa_refresh_token" class="inp" placeholder="Atzr|...">
            </div>
        </div>
    </div>

    <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4)">
        <button type="submit" class="btn btn-primary"><i data-lucide="check" width="16" height="16"></i> Account speichern</button>
        <a href="{{ route('amazon-accounts.index') }}" class="btn btn-secondary">Abbrechen</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const mpSelect = document.querySelector('select[name="marketplace_id"]');
    const regionSelect = document.querySelector('select[name="region"]');

    const regionMap = {
        'A1PA6795UKMFR9': 'eu', 'A1F83G8C2ARO7P': 'eu', 'A13V1IB3VIYZZH': 'eu',
        'A1RKKUPIHCS9HS': 'eu', 'A21TJRUUN4KGV': 'eu', 'A1V3QWFUAP3C01': 'eu',
        'A1VC38T7YXB528': 'eu', 'A2825NDLA7WDZV': 'eu', 'A2Q3F2V9MKYQXB': 'eu',
        'A1AM78C64UM0Y8': 'na', 'A2EUQ1WTGCTBG2': 'na', 'A1AMZ8RQ3WNRSE': 'na',
        'A1C6SO00UECEJB': 'fe', 'A1C6SO00UECEJB': 'fe', 'A21Z3CGI8UIP0F': 'fe',
        'A33AVAJ2PDY3EV': 'fe', 'A2NODRKZP88ZB9': 'fe', 'A1C6SO00UECEJB': 'fe',
    };

    mpSelect?.addEventListener('change', () => {
        const region = regionMap[mpSelect.value];
        if (region) regionSelect.value = region;
    });
});
</script>
@endsection
