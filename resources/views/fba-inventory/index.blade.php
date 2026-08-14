@extends('layouts.app')

@section('title', 'FBA Bestand')

@section('content')
<div class="page-header">
    <div class="page-title">FBA Bestand</div>
    <div class="page-subtitle">Aktuelle Bestände aus Amazon FBA</div>
</div>

<div style="display:flex;gap:var(--space-3);margin-bottom:var(--space-4)">
    <form method="POST" action="{{ route('fba-inventory.sync') }}">
        @csrf
        <button type="submit" class="btn btn-primary" id="syncBtn">
            <i data-lucide="refresh-cw" width="16" height="16"></i> Bestand synchronisieren
        </button>
    </form>
    <form method="GET" action="{{ route('fba-inventory.index') }}" style="display:flex;gap:var(--space-2)">
        <input type="text" name="sku" class="inp" placeholder="SKU filtern…" value="{{ request('sku') }}">
        <button type="submit" class="btn btn-ghost">Suchen</button>
    </form>
</div>

@if(Session::has('success'))
    <div class="alert alert-ok">
        <i data-lucide="check-circle" width="18" height="18" class="alert-icon"></i>
        <div>{{ Session::get('success') }}</div>
    </div>
@endif
@if(Session::has('error'))
    <div class="alert alert-error">
        <i data-lucide="alert-triangle" width="18" height="18" class="alert-icon"></i>
        <div>{{ Session::get('error') }}</div>
    </div>
@endif

<div id="syncProgress" style="display:none;margin-bottom:var(--space-4)">
    <div class="card">
        <div class="card-title"><i data-lucide="loader" width="16" height="16" class="spin"></i> Synchronisation läuft…</div>
        <div style="margin-bottom:var(--space-3)">
            <div style="display:flex;justify-content:space-between;margin-bottom:var(--space-2)">
                <span id="progressText" style="font-size:var(--text-sm);color:var(--color-text-muted)">Seite 0 abgerufen…</span>
                <span id="progressPercent" style="font-size:var(--text-sm);font-weight:600">0%</span>
            </div>
            <div style="height:8px;background:var(--color-bg);border-radius:var(--radius-full);overflow:hidden">
                <div id="progressBar" style="height:100%;width:0%;background:var(--color-primary);border-radius:var(--radius-full);transition:width 0.3s ease"></div>
            </div>
        </div>
        <div id="progressSkus" style="font-size:var(--text-xs);color:var(--color-text-faint)">0 SKUs abgerufen</div>
    </div>
</div>

<div id="syncError" style="display:none;margin-bottom:var(--space-4)">
    <div class="alert alert-error">
        <i data-lucide="alert-triangle" width="18" height="18" class="alert-icon"></i>
        <div id="syncErrorText"></div>
    </div>
</div>

<div class="card">
    <div class="card-title"><i data-lucide="boxes" width="16" height="16"></i> Inventar ({{ count($summaries) }} SKUs)</div>
    <div style="overflow-x:auto">
        <table class="article-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>ASIN</th>
                    <th>FNSKU</th>
                    <th>Produktname</th>
                    <th>Gesamt</th>
                    <th>Verfügbar</th>
                    <th>Beim FC</th>
                    <th>Unterwegs</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summaries as $item)
                <tr>
                    <td class="article-sku">{{ $item['sellerSku'] ?? '—' }}</td>
                    <td class="article-sku">{{ $item['asin'] ?? '—' }}</td>
                    <td class="article-sku">{{ $item['fnSku'] ?? '—' }}</td>
                    <td>{{ $item['productName'] ?? '—' }}</td>
                    <td style="font-family:var(--font-mono)">{{ $item['totalQuantity'] ?? 0 }}</td>
                    <td style="font-family:var(--font-mono);color:var(--color-ok)">{{ $item['inventoryDetails']['fulfillableQuantity'] ?? 0 }}</td>
                    <td style="font-family:var(--font-mono)">{{ $item['inventoryDetails']['inboundReceivingQuantity'] ?? 0 }}</td>
                    <td style="font-family:var(--font-mono)">{{ $item['inventoryDetails']['inboundShippedQuantity'] ?? 0 }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;color:var(--color-muted)">
                        —
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
.spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<script>
const progressUrl = '{{ route("fba-inventory.sync-progress") }}';
const syncProgress = document.getElementById('syncProgress');
const syncError = document.getElementById('syncError');
const progressBar = document.getElementById('progressBar');
const progressText = document.getElementById('progressText');
const progressPercent = document.getElementById('progressPercent');
const progressSkus = document.getElementById('progressSkus');
const syncErrorText = document.getElementById('syncErrorText');
const syncBtn = document.getElementById('syncBtn');
let pollInterval = null;

function startPolling() {
    if (pollInterval) return;
    syncProgress.style.display = 'block';
    syncError.style.display = 'none';
    syncBtn.disabled = true;
    pollInterval = setInterval(checkProgress, 2000);
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
    syncBtn.disabled = false;
}

async function checkProgress() {
    try {
        const resp = await fetch(progressUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            }
        });
        const data = await resp.json();

        if (data.status === 'none') {
            syncProgress.style.display = 'none';
            stopPolling();
            return;
        }

        if (data.status === 'running' || data.status === 'pending') {
            const pct = data.fetched_skus > 0 ? Math.min(Math.round((data.fetched_skus / Math.max(data.total_skus || 1, data.fetched_skus + 50)) * 100), 99) : 0;
            progressBar.style.width = pct + '%';
            progressText.textContent = data.status === 'pending' ? 'Warte auf Verarbeitung…' : 'Seite ' + data.current_page + ' abgerufen…';
            progressPercent.textContent = pct + '%';
            progressSkus.textContent = data.fetched_skus + ' SKUs abgerufen';
            return;
        }

        if (data.status === 'completed') {
            stopPolling();
            progressBar.style.width = '100%';
            progressPercent.textContent = '100%';
            progressText.textContent = 'Synchronisation abgeschlossen!';
            progressSkus.textContent = data.total_skus + ' SKUs synchronisiert';
            setTimeout(() => { location.reload(); }, 1500);
            return;
        }

        if (data.status === 'failed') {
            stopPolling();
            syncProgress.style.display = 'none';
            syncError.style.display = 'block';
            syncErrorText.textContent = 'Synchronisation fehlgeschlagen: ' + (data.error || 'Unbekannter Fehler');
        }
    } catch (e) {
        // Ignore polling errors
    }
}

document.addEventListener('DOMContentLoaded', function() {
    checkProgress();
});
</script>
@endsection
