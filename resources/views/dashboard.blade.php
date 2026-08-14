@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;width:100%">
        <div>
            <div class="page-title">Dashboard</div>
            <div class="page-subtitle">Willkommen zurück.</div>
        </div>
        @if(auth()->user()->isSuperadmin())
        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
            <i data-lucide="shield" width="16" height="16"></i> Admin Panel
        </a>
        @endif
    </div>
</div>

@if($fbaEnabled && !$hasAmazonAccount)
<div class="card" style="border-left:3px solid var(--color-primary);margin-top:var(--space-6)">
    <div style="display:flex;align-items:flex-start;gap:var(--space-4)">
        <div style="width:40px;height:40px;border-radius:var(--radius-full);background:var(--color-primary-highlight);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i data-lucide="rocket" width="20" height="20" style="color:var(--color-primary)"></i>
        </div>
        <div>
            <div style="font-size:var(--text-base);font-weight:600;color:var(--color-text);margin-bottom:var(--space-1)">Loslegen mit FBA Umlagerungen</div>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-4);line-height:1.6">
                Um deine ersten Umlagerungen zu erstellen, verbinde zuerst dein Amazon Seller-Konto über die SP-API.
            </p>
            <div style="display:flex;gap:var(--space-3)">
                <a href="{{ route('amazon-accounts.create') }}" class="btn btn-primary btn-sm">
                    <i data-lucide="plus" width="14" height="14"></i> Amazon Account hinzufügen
                </a>
                <a href="{{ route('profile.edit') }}" class="btn btn-secondary btn-sm">
                    <i data-lucide="user" width="14" height="14"></i> Profil vervollständigen
                </a>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
