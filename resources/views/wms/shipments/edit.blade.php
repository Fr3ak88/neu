@extends('layouts.app')

@section('title', 'Versandauftrag bearbeiten')

@section('content')
<div class="page-header">
    <a href="{{ route('wms.shipments.show', $shipment) }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">Versandauftrag bearbeiten</div>
</div>

<form method="POST" action="{{ route('wms.shipments.update', $shipment) }}">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-title"><i data-lucide="truck" width="16" height="16"></i> Versanddaten</div>
        <div class="form-grid">
            <div class="field">
                <label>Status <span class="req">*</span></label>
                <select name="status" class="inp" required>
                    @foreach(['pending' => 'Ausstehend', 'created' => 'Erstellt', 'shipped' => 'Versendet', 'delivered' => 'Zugestellt', 'error' => 'Fehler'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('status', $shipment->status) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Storlogix-ID</label>
                <input type="text" name="storlogix_id" class="inp" value="{{ old('storlogix_id', $shipment->storlogix_id) }}">
            </div>
            <div class="field">
                <label>Versanddienstleister</label>
                <input type="text" name="carrier" class="inp" value="{{ old('carrier', $shipment->carrier) }}">
            </div>
            <div class="field">
                <label>Tracking-Nummer</label>
                <input type="text" name="tracking_number" class="inp" value="{{ old('tracking_number', $shipment->tracking_number) }}">
            </div>
            <div class="field">
                <label>Anzahl Pakete</label>
                <input type="number" name="package_count" class="inp" value="{{ old('package_count', $shipment->package_count) }}" min="1">
            </div>
            <div class="field">
                <label>Gewicht (kg)</label>
                <input type="number" name="weight" class="inp" value="{{ old('weight', $shipment->weight) }}" min="0" step="0.1">
            </div>
            <div class="field">
                <label>Versanddatum</label>
                <input type="datetime-local" name="shipped_at" class="inp" value="{{ old('shipped_at', $shipment->shipped_at?->format('Y-m-d\TH:i')) }}">
            </div>
        </div>
    </div>

    <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4)">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="check" width="16" height="16"></i> Speichern
        </button>
        <a href="{{ route('wms.shipments.show', $shipment) }}" class="btn btn-ghost">Abbrechen</a>
    </div>
</form>
@endsection
