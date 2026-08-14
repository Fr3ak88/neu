@extends('layouts.app')

@section('title', 'Bestellung bearbeiten')

@section('content')
<div class="page-header">
    <a href="{{ route('wms.orders.show', $order) }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">{{ $order->order_number }} bearbeiten</div>
</div>

<form method="POST" action="{{ route('wms.orders.update', $order) }}">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-title"><i data-lucide="file-text" width="16" height="16"></i> Bestelldaten</div>
        <div class="form-grid">
            <div class="field">
                <label>Kunde <span class="req">*</span></label>
                <input type="text" name="customer_name" class="inp" value="{{ old('customer_name', $order->customer_name) }}" required>
            </div>
            <div class="field">
                <label>Status <span class="req">*</span></label>
                <select name="status" class="inp" required>
                    @foreach(['new' => 'Neu', 'processing' => 'In Bearbeitung', 'shipped' => 'Versendet', 'completed' => 'Abgeschlossen', 'cancelled' => 'Storniert'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('status', $order->status) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field form-full">
                <label>Adresse</label>
                <input type="text" name="customer_address" class="inp" value="{{ old('customer_address', $order->customer_address) }}">
            </div>
            <div class="field">
                <label>PLZ</label>
                <input type="text" name="customer_zip" class="inp" value="{{ old('customer_zip', $order->customer_zip) }}">
            </div>
            <div class="field">
                <label>Stadt</label>
                <input type="text" name="customer_city" class="inp" value="{{ old('customer_city', $order->customer_city) }}">
            </div>
            <div class="field">
                <label>Land</label>
                <select name="customer_country" class="inp">
                    <option value="DE" {{ old('customer_country', $order->customer_country) === 'DE' ? 'selected' : '' }}>Deutschland</option>
                    <option value="AT" {{ old('customer_country', $order->customer_country) === 'AT' ? 'selected' : '' }}>Österreich</option>
                    <option value="CH" {{ old('customer_country', $order->customer_country) === 'CH' ? 'selected' : '' }}>Schweiz</option>
                </select>
            </div>
            <div class="field">
                <label>Gesamtbetrag (€)</label>
                <input type="number" name="total_amount" class="inp" value="{{ old('total_amount', $order->total_amount) }}" min="0" step="0.01">
            </div>
            <div class="field">
                <label>Versandart</label>
                <input type="text" name="shipping_method" class="inp" value="{{ old('shipping_method', $order->shipping_method) }}">
            </div>
        </div>
    </div>

    <div style="display:flex;gap:var(--space-3);margin-top:var(--space-4)">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="check" width="16" height="16"></i> Speichern
        </button>
        <a href="{{ route('wms.orders.show', $order) }}" class="btn btn-ghost">Abbrechen</a>
    </div>
</form>
@endsection
