@extends('layouts.wms')

@section('title', 'Support-System')

@section('content')
<div class="page-header">
    <div class="page-title">Support-System</div>
    <div class="page-subtitle">Tenant-Support-Informationen</div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Firmeninformationen</div>
        </div>
        <div class="card-body">
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-muted">Firma:</span>
                    <span class="font-medium">{{ $tenant->company }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted">Name:</span>
                    <span class="font-medium">{{ $tenant->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted">Slug:</span>
                    <span class="font-medium">{{ $tenant->slug }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted">Plan:</span>
                    <span class="font-medium">{{ $tenant->plan }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted">Telefon:</span>
                    <span class="font-medium">{{ $tenant->phone }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Konnektivitätsdaten</div>
        </div>
        <div class="card-body">
            @if($tenantData)
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-muted">Api-Schlüssel:</span>
                        <span class="font-mono text-sm">{{ str($tenantData['jtl_api_key'] ?? 'Nicht gesetzt')->mask('*********').end() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Firmen-ID:</span>
                        <span class="font-medium">{{ $tenantData['jtl_tenant_id'] ?? 'Nicht gesetzt' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">JTL-Modul:</span>
                        <span class="{{ $tenantData['has_module'] ? 'text-success' : 'text-danger' }} font-medium">
                            {{ $tenantData['has_module'] ? 'Aktiviert' : 'Nicht aktiviert' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Module:</span>
                        <span class="font-medium">{{ collect($tenantData['modules'] ?? [])->join(', ') ?: 'Keine' }}</span>
                    </div>
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    Keine Support-Konfiguration für diesen Tenant gefunden.
                </div>
            @endif
        </div>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header">
            <div class="card-title">Adresse</div>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="text-muted">Straße:</span>
                    <p class="font-medium">{{ $tenant->street }}</p>
                </div>
                <div>
                    <span class="text-muted">PLZ:</span>
                    <p class="font-medium">{{ $tenant->zip }}</p>
                </div>
                <div>
                    <span class="text-muted">Stadt:</span>
                    <p class="font-medium">{{ $tenant->city }}</p>
                </div>
                <div>
                    <span class="text-muted">Land:</span>
                    <p class="font-medium">{{ $tenant->country }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection