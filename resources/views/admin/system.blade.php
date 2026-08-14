@extends('layouts.admin')

@section('title', 'System — Admin')

@section('content')
<div class="page-header">
    <div class="page-title">System</div>
    <div class="page-subtitle">Systeminformationen und Auslastung</div>
</div>

<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label">Benutzer</div>
        <div class="stat-value primary">{{ $stats['users'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Firmen</div>
        <div class="stat-value">{{ $stats['tenants'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Umlagerungen</div>
        <div class="stat-value success">{{ $stats['shipments'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Amazon Accounts</div>
        <div class="stat-value warning">{{ $stats['amazon_accounts'] }}</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-top:var(--space-6)">
    <div class="card">
        <div class="card-title">
            <i data-lucide="cpu" width="16" height="16"></i> CPU
        </div>
        <div style="margin-bottom:var(--space-4)">
            <div style="display:flex;justify-content:space-between;margin-bottom:var(--space-2)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Auslastung</span>
                <span style="font-size:var(--text-sm);font-weight:600;font-family:var(--font-mono);color:{{ $system['cpu_load']['percent'] > 80 ? 'var(--color-error)' : ($system['cpu_load']['percent'] > 50 ? 'var(--color-warning)' : 'var(--color-text)') }}">{{ $system['cpu_load']['percent'] }}%</span>
            </div>
            <div style="width:100%;height:8px;background:var(--color-surface-2);border-radius:var(--radius-full);overflow:hidden">
                <div style="width:{{ $system['cpu_load']['percent'] }}%;height:100%;background:{{ $system['cpu_load']['percent'] > 80 ? 'var(--color-error)' : ($system['cpu_load']['percent'] > 50 ? 'var(--color-warning)' : 'var(--color-primary)') }};border-radius:var(--radius-full);transition:width .3s"></div>
            </div>
        </div>
        <table class="article-table">
            <tbody>
                <tr><td style="width:140px;color:var(--color-text-muted)">Cores</td><td style="font-family:var(--font-mono)">{{ $system['cpu_load']['cores'] }}</td></tr>
                @if(isset($system['cpu_load']['load_1']))
                <tr><td style="color:var(--color-text-muted)">Load (1 min)</td><td style="font-family:var(--font-mono)">{{ $system['cpu_load']['load_1'] }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Load (5 min)</td><td style="font-family:var(--font-mono)">{{ $system['cpu_load']['load_5'] }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Load (15 min)</td><td style="font-family:var(--font-mono)">{{ $system['cpu_load']['load_15'] }}</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-title">
            <i data-lucide="memory-stick" width="16" height="16"></i> RAM
        </div>
        <div style="margin-bottom:var(--space-4)">
            <div style="display:flex;justify-content:space-between;margin-bottom:var(--space-2)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Auslastung</span>
                <span style="font-size:var(--text-sm);font-weight:600;font-family:var(--font-mono);color:{{ $system['ram']['percent'] > 80 ? 'var(--color-error)' : ($system['ram']['percent'] > 50 ? 'var(--color-warning)' : 'var(--color-text)') }}">{{ $system['ram']['percent'] }}%</span>
            </div>
            <div style="width:100%;height:8px;background:var(--color-surface-2);border-radius:var(--radius-full);overflow:hidden">
                <div style="width:{{ $system['ram']['percent'] }}%;height:100%;background:{{ $system['ram']['percent'] > 80 ? 'var(--color-error)' : ($system['ram']['percent'] > 50 ? 'var(--color-warning)' : 'var(--color-primary)') }};border-radius:var(--radius-full);transition:width .3s"></div>
            </div>
        </div>
        <table class="article-table">
            <tbody>
                <tr><td style="width:140px;color:var(--color-text-muted)">Gesamt</td><td style="font-family:var(--font-mono)">{{ $system['ram']['total'] }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Belegt</td><td style="font-family:var(--font-mono)">{{ $system['ram']['used'] }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Frei</td><td style="font-family:var(--font-mono)">{{ $system['ram']['free'] }}</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-top:var(--space-6)">
    <div class="card">
        <div class="card-title">
            <i data-lucide="hard-drive" width="16" height="16"></i> Festplatte
        </div>
        <div style="margin-bottom:var(--space-4)">
            <div style="display:flex;justify-content:space-between;margin-bottom:var(--space-2)">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Belegt</span>
                <span style="font-size:var(--text-sm);font-weight:600;font-family:var(--font-mono)">{{ $system['disk_percent'] }}%</span>
            </div>
            <div style="width:100%;height:8px;background:var(--color-surface-2);border-radius:var(--radius-full);overflow:hidden">
                <div style="width:{{ $system['disk_percent'] }}%;height:100%;background:{{ $system['disk_percent'] > 90 ? 'var(--color-error)' : ($system['disk_percent'] > 70 ? 'var(--color-warning)' : 'var(--color-primary)') }};border-radius:var(--radius-full);transition:width .3s"></div>
            </div>
        </div>
        <table class="article-table">
            <tbody>
                <tr><td style="width:140px;color:var(--color-text-muted)">Gesamt</td><td style="font-family:var(--font-mono)">{{ $system['disk_total'] }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Frei</td><td style="font-family:var(--font-mono)">{{ $system['disk_free'] }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-title">
            <i data-lucide="server" width="16" height="16"></i> Server
        </div>
        <table class="article-table">
            <tbody>
                <tr><td style="width:140px;color:var(--color-text-muted)">PHP Version</td><td style="font-family:var(--font-mono)">{{ $system['php_version'] }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Laravel Version</td><td style="font-family:var(--font-mono)">{{ $system['laravel_version'] }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Betriebssystem</td><td>{{ $system['server'] }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">PHP Speicher</td><td style="font-family:var(--font-mono)">{{ $system['memory_used'] }} / {{ $system['memory_limit'] }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">PHP Peak</td><td style="font-family:var(--font-mono)">{{ $system['memory_peak'] }}</td></tr>
                <tr><td style="color:var(--color-text-muted)">Max. Ausführung</td><td style="font-family:var(--font-mono)">{{ $system['max_execution'] }}</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
