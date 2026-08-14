@extends('layouts.admin')

@section('title', 'Firmen — Admin')

@section('content')
<div class="page-header">
    <div class="page-title">Firmen</div>
    <div class="page-subtitle">Alle registrierten Firmen im System</div>
</div>

<div class="action-bar">
    <div class="action-bar-left">
        <span class="article-sku">{{ $tenants->count() }} Firma(n)</span>
    </div>
</div>

<div class="card">
    @if($tenants->isEmpty())
        <div style="text-align:center;padding:var(--space-10) 0;color:var(--color-text-faint)">
            Noch keine Firmen vorhanden.
        </div>
    @else
        <table class="article-table">
            <thead>
                <tr>
                    <th>Firma</th>
                    <th>PLZ / Ort</th>
                    <th>Land</th>
                    <th>Plan</th>
                    <th>Benutzer</th>
                    <th style="text-align:right">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tenants as $tenant)
                <tr>
                    <td><b>{{ $tenant->company }}</b><br><span class="article-sku">{{ $tenant->name }}</span></td>
                    <td>{{ $tenant->zip }} {{ $tenant->city }}</td>
                    <td class="article-sku">{{ $tenant->country }}</td>
                    <td><span class="status-badge status-ok">{{ $tenant->plan }}</span></td>
                    <td style="font-family:var(--font-mono)">{{ $tenant->users_count }}</td>
                    <td style="text-align:right">
                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-sm btn-secondary">Details</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
