@extends('layouts.app')

@section('title', $shipment->internal_ref)

@section('content')
<div class="page-header">
    <a href="{{ route('fba-shipments.index') }}" class="btn btn-sm btn-ghost" style="margin-bottom:var(--space-3)">
        <i data-lucide="arrow-left" width="14" height="14"></i> Zurück
    </a>
    <div class="page-title">{{ $shipment->internal_ref }}</div>
    <div class="page-subtitle">{{ $shipment->amazonAccount?->name ?? 'Kein Konto' }} · {{ $shipment->source_warehouse }}</div>
</div>

@php
    $statusMap = [
        'draft'          => ['status-pending', 'circle',   'Entwurf'],
        'plan_creating'  => ['status-warn',    'clock',    'Plan wird erstellt…'],
        'plan_ready'     => ['status-warn',    'clock',    'Plan bereit'],
        'registered'     => ['status-ok',      'check',    'Angemeldet'],
        'label_ready'    => ['status-ok',      'check',    'Etikett bereit'],
        'shipped'        => ['status-ok',      'truck',    'Versendet'],
        'completed'      => ['status-ok',      'check',    'Abgeschlossen'],
        'error'          => ['status-error',   'alert-triangle', 'Fehler'],
        'cancelled'      => ['status-error',   'x-circle', 'Storniert'],
    ];
    [$badgeClass, $icon, $label] = $statusMap[$shipment->status] ?? ['status-pending', 'circle', $shipment->status];
@endphp

<div style="margin-bottom:var(--space-6)">
    <span class="status-badge {{ $badgeClass }}"><i data-lucide="{{ $icon }}" width="10" height="10"></i> {{ $label }}</span>
    @if($shipment->inbound_plan_id)
        <span class="article-sku" style="margin-left:var(--space-3)">Plan: {{ $shipment->inbound_plan_id }}</span>
    @endif
    @if($shipment->shipment_ids)
        <span class="article-sku" style="margin-left:var(--space-3)">Sendungen: {{ count($shipment->shipment_ids) }}</span>
    @endif
    @if($shipment->packaging_type)
        <span class="article-sku" style="margin-left:var(--space-3)">
            {{ $shipment->packaging_type === 'ltl' ? 'LTL/FTL' : 'Small Parcel' }}
        </span>
    @endif
</div>

@if($shipment->hasError())
    <div class="alert alert-error">
        <i data-lucide="alert-triangle" width="18" height="18" class="alert-icon"></i>
        <div>
            <div class="alert-title">Fehler</div>
            <div class="alert-text">{{ $shipment->error_message }}</div>
        </div>
        <form method="POST" action="{{ route('fba-shipments.retry', $shipment) }}" style="margin-left:auto">
            @csrf
            <button type="submit" class="btn btn-sm btn-ghost" style="color:var(--color-error)">
                <i data-lucide="refresh-cw" width="14" height="14"></i> Erneut versuchen
            </button>
        </form>
    </div>
@endif

@if($shipment->isDraft() && !$shipment->amazon_account_id)
    <div class="alert" style="border:1px solid var(--border);background:var(--bg-secondary)">
        <i data-lucide="info" width="18" height="18" style="color:var(--accent);flex-shrink:0"></i>
        <div style="flex:1">
            <div class="alert-title">Kein Amazon-Konto zugewiesen</div>
            <div class="alert-text">Weise ein Konto zu, um den Anlieferungsplan bei Amazon zu erstellen.</div>
        </div>
        <form method="POST" action="{{ route('fba-shipments.update-account', $shipment) }}" style="display:flex;gap:var(--space-2);align-items:center">
            @csrf
            @method('PATCH')
            <select name="amazon_account_id" class="inp" style="width:auto;margin:0">
                @foreach(\App\Models\AmazonAccount::where('active', true)->get() as $ac)
                    <option value="{{ $ac->id }}">{{ $ac->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Zuweisen</button>
        </form>
    </div>
@endif

@if($shipment->isPlanCreating())
    <div class="alert alert-warning">
        <i data-lucide="clock" width="18" height="18" class="alert-icon"></i>
        <div><div class="alert-title">Bitte warten (bis zu 90 Sekunden)</div>
        <div class="alert-text">Amazon verarbeitet den Plan. Seite lädt automatisch neu.</div></div>
    </div>
    <script>setTimeout(() => location.reload(), 10000);</script>
@endif

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

{{-- ── Versandadresse ─────────────────────────────────────── --}}
@if($shipment->isDraft())
<div class="card">
    <div class="card-title"><i data-lucide="map-pin" width="16" height="16"></i> Versandadresse</div>
    <form method="POST" action="{{ route('fba-shipments.update', $shipment) }}">
        @csrf
        @method('PUT')
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--space-3)">
            <div>
                <label class="form-label">Name *</label>
                <input type="text" name="ship_from_name" class="form-input" required value="{{ old('ship_from_name', $shipment->ship_from_name) }}">
            </div>
            <div>
                <label class="form-label">Telefon *</label>
                <input type="text" name="ship_from_phone" class="form-input" required value="{{ old('ship_from_phone', $shipment->ship_from_phone) }}">
            </div>
            <div style="grid-column:1/-1">
                <label class="form-label">Adresse *</label>
                <input type="text" name="ship_from_address" class="form-input" required value="{{ old('ship_from_address', $shipment->ship_from_address) }}">
            </div>
            <div>
                <label class="form-label">PLZ *</label>
                <input type="text" name="ship_from_zip" class="form-input" required value="{{ old('ship_from_zip', $shipment->ship_from_zip) }}">
            </div>
            <div>
                <label class="form-label">Stadt *</label>
                <input type="text" name="ship_from_city" class="form-input" required value="{{ old('ship_from_city', $shipment->ship_from_city) }}">
            </div>
            <div>
                <label class="form-label">Land *</label>
                <select name="ship_from_country" class="form-input" required>
                    @foreach(['DE'=>'Deutschland','AT'=>'Österreich','CH'=>'Schweiz','NL'=>'Niederlande','PL'=>'Polen','FR'=>'Frankreich','ES'=>'Spanien','IT'=>'Italien','GB'=>'Großbritannien','US'=>'USA'] as $code => $name)
                        <option value="{{ $code }}" {{ $shipment->ship_from_country === $code ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="margin-top:var(--space-3)">
            <button type="submit" class="btn btn-sm btn-primary">
                <i data-lucide="save" width="14" height="14"></i> Adresse speichern
            </button>
        </div>
    </form>
</div>
@else
<div class="card">
    <div class="card-title"><i data-lucide="map-pin" width="16" height="16"></i> Versandadresse</div>
    <div style="font-size:0.875rem;color:var(--text-primary);line-height:1.6">
        <strong>{{ $shipment->ship_from_name }}</strong><br>
        {{ $shipment->ship_from_address }}<br>
        {{ $shipment->ship_from_zip }} {{ $shipment->ship_from_city }}<br>
        {{ $shipment->ship_from_country }}<br>
        Tel: {{ $shipment->ship_from_phone }}
    </div>
</div>
@endif

{{-- ── Artikel ────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-title"><i data-lucide="package" width="16" height="16"></i> Artikel ({{ $shipment->items->count() }})</div>
    <div style="overflow-x:auto">
        <form method="POST" action="{{ route('fba-shipments.bulk-update-items', $shipment) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="items_json" id="itemsJson">
            <table class="article-table">
                <thead>
                    <tr>
                        <th>SKU</th><th>Name</th><th>Menge</th><th>Vorbereitung</th><th>Kategorie</th><th>Vorbereitung durch</th><th>Label-Eigentümer</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shipment->items as $item)
                    <tr data-item-id="{{ $item->id }}">
                        <td class="article-sku">{{ $item->sku }}</td>
                        <td>{{ $item->name }}</td>
                        <td><input type="number" name="quantity" class="inp" style="width:60px" value="{{ $item->quantity }}" min="1" data-field="quantity"></td>
                        <td>
                            <select name="prep_instruction" class="inp" style="width:150px" data-field="prep_instruction">
                                <option value="">Keine</option>
                                @foreach(['ITEM_LABELING'=>'Etikettierung','ITEM_POLYBAGGING'=>'Polybagging','ITEM_BUBBLEWRAP'=>'Bubble Wrap','ITEM_TAPING'=>'Klebeband','ITEM_BLACK_SHRINKWRAP'=>'Shrink-Wrap','ITEM_HANG_GARMENT'=>'Hängend','ITEM_BOXING'=>'Kartonieren','ITEM_SIOC'=>'Ohne Verpackung (SIOC)','ITEM_NO_PREP'=>'Keine Vorbereitung'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ $item->prep_instruction === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="prep_category" class="inp" style="width:140px" data-field="prep_category">
                                <option value="">Standard</option>
                                @foreach(['NONE'=>'Keine','FRAGILE'=>'Zerbrechlich','SMALL'=>'Klein','TEXTILE'=>'Textil','LIQUID'=>'Flüssig','SHARP'=>'Scharf','BABY'=>'Baby','ADULT'=>'Erwachsenen','HANGER'=>'Hänger','SET'=>'Set','GRANULAR'=>'Körnig','PERFORATED'=>'Perforiert','FC_PROVIDED'=>'Von FC bereitgestellt','UNKNOWN'=>'Unbekannt'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ $item->prep_category === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="prep_owner" class="inp" style="width:130px" data-field="prep_owner">
                                <option value="">NONE</option>
                                @foreach(['SELLER'=>'Seller','AMAZON'=>'Amazon (FBA)'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ $item->prep_owner === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="label_owner" class="inp" style="width:130px" data-field="label_owner">
                                <option value="">NONE</option>
                                @foreach(['SELLER'=>'Seller','AMAZON'=>'Amazon (FBA)'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ $item->label_owner === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($shipment->isEditable())
            <div style="margin-top:var(--space-3)">
                <button type="submit" class="btn btn-primary btn-sm" id="btnSaveItems">
                    <i data-lucide="save" width="14" height="14"></i> Änderungen speichern
                </button>
            </div>
            @endif
        </form>
    </div>
</div>

{{-- ── Kartons (Carton-Level) ─────────────────────────────── --}}
@if($shipment->cartons->isNotEmpty() || $shipment->isDraft() || $shipment->isPlanReady())
<div class="card">
    <div class="card-title"><i data-lucide="box" width="16" height="16"></i> Kartons ({{ $shipment->cartons->count() }})</div>

    @if($shipment->cartons->isNotEmpty())
    <div style="overflow-x:auto">
        <table class="article-table">
            <thead><tr><th>Karton-ID</th><th>Gewicht</th><th>Abmessungen</th><th>Inhalte</th><th></th></tr></thead>
            <tbody>
                @foreach($shipment->cartons as $carton)
                <tr>
                    <td class="article-sku">{{ $carton->carton_id }}</td>
                    <td>{{ $carton->weight_value ? $carton->weight_value . ' ' . $carton->weight_unit : '—' }}</td>
                    <td>{{ $carton->length && $carton->width && $carton->height ? "{$carton->length}×{$carton->width}×{$carton->height} {$carton->dimension_unit}" : '—' }}</td>
                    <td>
                        @foreach($carton->contents ?? [] as $c)
                            <span class="article-sku" style="margin-right:4px">{{ $c['sku'] }} ×{{ $c['quantity'] }}</span>
                        @endforeach
                    </td>
                    <td>
                        @if($shipment->isDraft() || $shipment->isPlanReady())
                        <form method="POST" action="{{ route('fba-shipments.destroy-carton', [$shipment, $carton]) }}" style="display:inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-ghost" title="Löschen"><i data-lucide="trash-2" width="12" height="12"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p style="color:var(--color-muted)">Noch keine Kartons erfasst.</p>
    @endif

    @if($shipment->isDraft() || $shipment->isPlanReady())
    <details style="margin-top:var(--space-3)">
        <summary class="btn btn-sm btn-ghost" style="cursor:pointer"><i data-lucide="plus" width="14" height="14"></i> Karton hinzufügen</summary>
        <form method="POST" action="{{ route('fba-shipments.store-carton', $shipment) }}" id="cartonForm" style="margin-top:var(--space-3)">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--space-3)">
                <div>
                    <label class="form-label">Karton-ID</label>
                    <input type="text" name="carton_id" class="form-input" required placeholder="z.B. KARTON-001">
                </div>
                <div>
                    <label class="form-label">Gewicht (kg)</label>
                    <input type="number" step="0.1" name="weight_value" class="form-input" placeholder="optional">
                </div>
                <div>
                    <label class="form-label">Länge (cm)</label>
                    <input type="number" step="0.1" name="length" class="form-input" placeholder="optional">
                </div>
                <div>
                    <label class="form-label">Breite (cm)</label>
                    <input type="number" step="0.1" name="width" class="form-input" placeholder="optional">
                </div>
                <div>
                    <label class="form-label">Höhe (cm)</label>
                    <input type="number" step="0.1" name="height" class="form-input" placeholder="optional">
                </div>
            </div>

            {{-- ── Dynamische Karton-Inhalte ─────────────────── --}}
            <div style="margin-top:var(--space-3)">
                <label class="form-label">Karton-Inhalte</label>
                <div id="cartonContents">
                    <div class="carton-content-row" style="display:flex;gap:var(--space-2);align-items:center;margin-bottom:var(--space-2)">
                        <select class="form-input" style="flex:2" required>
                            <option value="">SKU wählen…</option>
                            @foreach($shipment->items as $item)
                                <option value="{{ $item->sku }}" data-remaining="{{ $item->quantity }}">{{ $item->sku }} — {{ $item->name }} ({{ $item->quantity }} Stk.)</option>
                            @endforeach
                        </select>
                        <input type="number" class="form-input" style="flex:0 0 80px" value="1" min="1" required placeholder="Menge">
                        <button type="button" class="btn btn-sm btn-ghost" onclick="removeCartonRow(this)" title="Entfernen"><i data-lucide="x" width="14" height="14"></i></button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-ghost" onclick="addCartonRow()" style="margin-top:var(--space-1)">
                    <i data-lucide="plus" width="12" height="12"></i> Weitere SKU hinzufügen
                </button>
            </div>

            <input type="hidden" name="contents_json" id="contentsJson">

            <div style="display:flex;gap:var(--space-2);align-items:center;margin-top:var(--space-2)">
                <span class="article-sku" id="cartonSummary">0 Artikel im Karton</span>
            </div>

            <button type="submit" class="btn btn-sm btn-primary" style="margin-top:var(--space-3)">
                <i data-lucide="plus" width="14" height="14"></i> Speichern
            </button>
        </form>
    </details>
    @endif
</div>
@endif

{{-- ── Paletten (LTL) ─────────────────────────────────────── --}}
@if($shipment->packaging_type === 'ltl' || $shipment->pallets->isNotEmpty())
<div class="card">
    <div class="card-title"><i data-lucide="layers" width="16" height="16"></i> Paletten ({{ $shipment->pallets->count() }})</div>

    @if($shipment->pallets->isNotEmpty())
    <div style="overflow-x:auto">
        <table class="article-table">
            <thead><tr><th>Paletten-ID</th><th>Gewicht</th><th>Abmessungen</th><th>Stapelbar</th><th>Kartons</th><th></th></tr></thead>
            <tbody>
                @foreach($shipment->pallets as $pallet)
                <tr>
                    <td class="article-sku">{{ $pallet->pallet_id }}</td>
                    <td>{{ $pallet->weight_value ? $pallet->weight_value . ' ' . $pallet->weight_unit : '—' }}</td>
                    <td>{{ $pallet->length && $pallet->width && $pallet->height ? "{$pallet->length}×{$pallet->width}×{$pallet->height} {$pallet->dimension_unit}" : '—' }}</td>
                    <td>{{ $pallet->is_stacked ? 'Ja' : 'Nein' }}</td>
                    <td>{{ $pallet->carton_ids ? implode(', ', $pallet->carton_ids) : '—' }}</td>
                    <td>
                        @if($shipment->isDraft() || $shipment->isPlanReady())
                        <form method="POST" action="{{ route('fba-shipments.destroy-pallet', [$shipment, $pallet]) }}" style="display:inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-ghost" title="Löschen"><i data-lucide="trash-2" width="12" height="12"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p style="color:var(--color-muted)">Noch keine Paletten erfasst.</p>
    @endif

    @if($shipment->isDraft() || $shipment->isPlanReady())
    <details style="margin-top:var(--space-3)">
        <summary class="btn btn-sm btn-ghost" style="cursor:pointer"><i data-lucide="plus" width="14" height="14"></i> Palette hinzufügen</summary>
        <form method="POST" action="{{ route('fba-shipments.store-pallet', $shipment) }}" style="margin-top:var(--space-3)">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--space-3)">
                <div>
                    <label class="form-label">Paletten-ID</label>
                    <input type="text" name="pallet_id" class="form-input" required placeholder="z.B. PALETTE-001">
                </div>
                <div>
                    <label class="form-label">Gewicht (kg)</label>
                    <input type="number" step="0.1" name="weight_value" class="form-input" placeholder="optional">
                </div>
                <div>
                    <label class="form-label">Länge (cm)</label>
                    <input type="number" step="0.1" name="length" class="form-input" placeholder="optional">
                </div>
                <div>
                    <label class="form-label">Breite (cm)</label>
                    <input type="number" step="0.1" name="width" class="form-input" placeholder="optional">
                </div>
                <div>
                    <label class="form-label">Höhe (cm)</label>
                    <input type="number" step="0.1" name="height" class="form-input" placeholder="optional">
                </div>
                <div>
                    <label class="form-label">Stapelbar</label>
                    <select name="is_stacked" class="form-input">
                        <option value="0">Nein</option>
                        <option value="1">Ja</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:var(--space-3)">
                <label class="form-label">Zugehörige Karton-IDs (JSON-Array)</label>
                <textarea name="carton_ids" class="form-input" rows="1" placeholder='["KARTON-001","KARTON-002"]'></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-primary" style="margin-top:var(--space-3)">
                <i data-lucide="plus" width="14" height="14"></i> Speichern
            </button>
        </form>
    </details>
    @endif
</div>
@endif

{{-- ── Aufgeteilte Sendungen ───────────────────────────────── --}}
@if($shipment->splits->isNotEmpty())
<div class="card">
    <div class="card-title"><i data-lucide="truck" width="16" height="16"></i> Aufgeteilte Sendungen</div>
    <div class="shipment-list">
        @foreach($shipment->splits as $split)
        <div class="shipment-item">
            <div class="shipment-header">
                <div>
                    <div class="shipment-id">{{ $split->amazon_shipment_id }}</div>
                    <div class="shipment-fc">Fulfillment Center {{ $split->fulfillment_center_id }} — {{ $split->destination_address }}</div>
                </div>
                @php
                    $splitStatusClass = match($split->status) {
                        'working'   => 'status-pending',
                        'ready'     => 'status-warn',
                        'shipped'   => 'status-ok',
                        default     => 'status-pending',
                    };
                @endphp
                <span class="status-badge {{ $splitStatusClass }}">{{ $split->status }}</span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Actions ────────────────────────────────────────────── --}}
<div style="display:flex;flex-wrap:wrap;gap:var(--space-3)">
    @if($shipment->isDraft())
        @if($shipment->amazon_account_id)
            <form method="POST" action="{{ route('fba-shipments.create-plan', $shipment) }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="zap" width="16" height="16"></i> Anlieferungsplan bei Amazon erstellen
                </button>
            </form>
        @else
            <a href="{{ route('fba-shipments.edit', $shipment) }}" class="btn btn-secondary">
                <i data-lucide="pencil" width="16" height="16"></i> Account zuweisen & Plan erstellen
            </a>
        @endif
    @endif

    @if($shipment->isPlanReady())
        <form method="POST" action="{{ route('fba-shipments.register', $shipment) }}">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i data-lucide="send" width="16" height="16"></i> Bei Amazon anmelden
            </button>
        </form>
    @endif

    @if(in_array($shipment->status, ['plan_ready', 'plan_creating']))
        <form method="POST" action="{{ route('fba-shipments.cancel', $shipment) }}">
            @csrf
            <button type="submit" class="btn btn-ghost" style="color:var(--color-error)" onclick="return confirm('Plan wirklich stornieren?')">
                <i data-lucide="x-circle" width="16" height="16"></i> Plan stornieren
            </button>
        </form>
    @endif

    @if($shipment->isRegisteredOrLater())
        <form method="POST" action="{{ route('fba-shipments.check-status', $shipment) }}">
            @csrf
            <button type="submit" class="btn btn-ghost">
                <i data-lucide="refresh-cw" width="16" height="16"></i> Status prüfen
            </button>
        </form>
    @endif

    @if(in_array($shipment->status, ['registered', 'plan_ready', 'label_ready']))
        <a href="{{ route('fba-shipments.labels', $shipment) }}" class="btn btn-ghost" target="_blank">
            <i data-lucide="printer" width="16" height="16"></i> Labels herunterladen
        </a>
    @endif

    @if($shipment->inbound_plan_id && $shipment->shipment_ids)
        @foreach($shipment->shipment_ids as $sid)
            <a href="{{ route('fba-shipments.amazon-items', $shipment) }}?shipment_id={{ $sid }}" class="btn btn-ghost">
                <i data-lucide="list" width="16" height="16"></i> Amazon Items: {{ substr($sid, 0, 12) }}…
            </a>
        @endforeach
    @endif
</div>

@section('scripts')
<script>
// Bulk-Update: Items in JSON sammeln vor Submit
(function() {
    var btnSave = document.getElementById('btnSaveItems');
    if (!btnSave) return;

    btnSave.closest('form').addEventListener('submit', function(e) {
        var rows = document.querySelectorAll('tbody tr[data-item-id]');
        var items = [];
        rows.forEach(function(row) {
            items.push({
                id: row.dataset.itemId,
                quantity: row.querySelector('[data-field="quantity"]').value,
                prep_instruction: row.querySelector('[data-field="prep_instruction"]').value,
                prep_category: row.querySelector('[data-field="prep_category"]').value,
                prep_owner: row.querySelector('[data-field="prep_owner"]').value,
                label_owner: row.querySelector('[data-field="label_owner"]').value,
            });
        });
        document.getElementById('itemsJson').value = JSON.stringify(items);
    });
})();
</script>
<script>
(function() {
    const container = document.getElementById('cartonContents');
    const summary = document.getElementById('cartonSummary');
    const form = document.getElementById('cartonForm');

    function updateCartonSummary() {
        const rows = container.querySelectorAll('.carton-content-row');
        let total = 0;
        rows.forEach(function(row) {
            var inp = row.querySelector('input[type="number"]');
            total += parseInt(inp.value) || 0;
        });
        summary.textContent = total + ' Artikel im Karton';
    }

    function bindRowEvents(row) {
        var inp = row.querySelector('input[type="number"]');
        inp.addEventListener('input', updateCartonSummary);
        inp.addEventListener('change', updateCartonSummary);
    }

    // Initiale Zeilen binden + Zähler starten
    container.querySelectorAll('.carton-content-row').forEach(function(row) {
        bindRowEvents(row);
    });
    updateCartonSummary();

    // Neue Zeile hinzufügen
    window.addCartonRow = function() {
        var firstRow = container.querySelector('.carton-content-row');
        var newRow = firstRow.cloneNode(true);
        newRow.querySelector('select').value = '';
        newRow.querySelector('input[type="number"]').value = '1';
        container.appendChild(newRow);
        bindRowEvents(newRow);
        updateCartonSummary();
        lucide.createIcons();
    };

    // Zeile entfernen
    window.removeCartonRow = function(btn) {
        if (container.children.length > 1) {
            btn.closest('.carton-content-row').remove();
            updateCartonSummary();
        }
    };

    // Beim Absenden: JSON bauen
    form.addEventListener('submit', function(e) {
        var rows = container.querySelectorAll('.carton-content-row');
        var contents = [];
        rows.forEach(function(row) {
            var sku = row.querySelector('select').value;
            var qty = parseInt(row.querySelector('input[type="number"]').value) || 0;
            if (sku && qty > 0) {
                contents.push({ sku: sku, quantity: qty });
            }
        });
        if (contents.length === 0) {
            e.preventDefault();
            alert('Bitte mindestens einen Artikel auswählen.');
            return;
        }
        document.getElementById('contentsJson').value = JSON.stringify(contents);
    });
})();
</script>
@if($shipment->status === 'plan_creating')
<script>
// Auto-Refresh bei "Plan wird erstellt…"
(function() {
    setTimeout(function() { location.reload(); }, 10000);
})();
</script>
@endif
@endsection
@endsection
