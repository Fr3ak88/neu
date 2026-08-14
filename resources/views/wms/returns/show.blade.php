@extends('layouts.app')

@section('title', 'Retoure ' . ($return->return_number ?: $return->id))

@section('content')
<div class="page-header">
    <a href="{{ route('wms.returns.index') }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">Retoure {{ $return->return_number ?: $return->storlogix_return_id ?: '#' . $return->id }}</div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-6)">
    <div>
        <div class="card">
            <div class="card-title"><i data-lucide="info" width="16" height="16"></i> Retouren-Details</div>
            <div style="display:flex;flex-direction:column;gap:var(--space-3)">
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Status</span>
                    <span class="status-badge {{ $return->statusClass() }}">{{ $return->statusLabel() }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Bestellung</span>
                    @if($return->order)
                        <a href="{{ route('wms.orders.show', $return->order) }}" style="color:var(--color-primary);text-decoration:none">
                            {{ $return->order->order_number }}
                        </a>
                    @else
                        <span>—</span>
                    @endif
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Storlogix-ID</span>
                    <span class="article-sku">{{ $return->storlogix_return_id ?: '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Grund</span>
                    <span>{{ $return->reason ?: '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Menge</span>
                    <span style="font-family:var(--font-mono)">{{ $return->quantity }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Zustand</span>
                    <span>{{ $return->condition ?: '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="color:var(--color-text-muted)">Eingang am</span>
                    <span>{{ $return->received_at?->format('d.m.Y H:i') ?: '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-title"><i data-lucide="settings" width="16" height="16"></i> Status aktualisieren</div>
            <form method="POST" action="{{ route('wms.returns.update', $return) }}">
                @csrf
                @method('PUT')
                <div class="field" style="margin-bottom:var(--space-4)">
                    <label>Status</label>
                    <select name="status" class="inp">
                        @foreach(['received' => 'Eingegangen', 'inspected' => 'Geprüft', 'restocked' => 'Eingelagert', 'disposed' => 'Entorgt'] as $val => $lbl)
                            <option value="{{ $val }}" {{ $return->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin-bottom:var(--space-4)">
                    <label>Zustand</label>
                    <input type="text" name="condition" class="inp" value="{{ $return->condition }}" placeholder="z.B. Neuwertig, Beschädigt">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">
                    <i data-lucide="check" width="16" height="16"></i> Aktualisieren
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
