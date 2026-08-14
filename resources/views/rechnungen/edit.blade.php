@extends('layouts.app')

@section('title', 'Rechnung bearbeiten — ' . $rechnung->rechnungsnummer)

@section('content')
<div class="page-header">
    <div class="page-title">{{ $rechnung->rechnungsnummer }} bearbeiten</div>
    <a href="{{ route('rechnungen.show', $rechnung) }}" class="btn btn-secondary">
        <i data-lucide="arrow-left" width="16" height="16"></i> Zurück
    </a>
</div>

<form method="POST" action="{{ route('rechnungen.update', $rechnung) }}" id="rechnungForm">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Empfänger</div>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        <div class="form-grid">
                            <div class="field">
                                <label class="form-label">Name</label>
                                <input type="text" name="kunde_name" class="form-input" value="{{ old('kunde_name', $rechnung->kunde_name) }}">
                            </div>
                            <div class="field">
                                <label class="form-label">Firma</label>
                                <input type="text" name="kunde_firma" class="form-input" value="{{ old('kunde_firma', $rechnung->kunde_firma) }}">
                            </div>
                        </div>
                        <div class="field">
                            <label class="form-label">E-Mail</label>
                            <input type="email" name="kunde_email" class="form-input" value="{{ old('kunde_email', $rechnung->kunde_email) }}">
                        </div>
                        <div class="field">
                            <label class="form-label">Straße, Hausnummer</label>
                            <input type="text" name="kunde_strasse" class="form-input" value="{{ old('kunde_strasse', $rechnung->kunde_strasse) }}">
                        </div>
                        <div class="form-grid">
                            <div class="field">
                                <label class="form-label">PLZ</label>
                                <input type="text" name="kunde_plz" class="form-input" value="{{ old('kunde_plz', $rechnung->kunde_plz) }}">
                            </div>
                            <div class="field">
                                <label class="form-label">Ort</label>
                                <input type="text" name="kunde_ort" class="form-input" value="{{ old('kunde_ort', $rechnung->kunde_ort) }}">
                            </div>
                            <div class="field">
                                <label class="form-label">Land</label>
                                <input type="text" name="kunde_land" class="form-input" value="{{ old('kunde_land', $rechnung->kunde_land) }}" maxlength="2">
                            </div>
                        </div>
                        <div class="field">
                            <label class="form-label">Steuernummer</label>
                            <input type="text" name="kunde_steuernummer" class="form-input" value="{{ old('kunde_steuernummer', $rechnung->kunde_steuernummer) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
                    <div class="card-title" style="margin-bottom:0"><i data-lucide="list" width="16" height="16"></i> Positionen</div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addRow()">
                        <i data-lucide="plus" width="14" height="14"></i> Hinzufügen
                    </button>
                </div>
                <div class="card-body" style="padding:0">
                    <table class="article-table" id="positionsTable">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Beschreibung</th>
                                <th style="width:80px">Menge</th>
                                <th style="width:80px">Einheit</th>
                                <th style="width:120px">Einzelpreis</th>
                                <th style="width:120px">Netto</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="positionsBody">
                            @foreach($rechnung->positions as $i => $pos)
                            <tr class="position-row">
                                <td style="font-family:var(--font-mono);color:var(--color-text-faint)">{{ $i + 1 }}</td>
                                <td><input type="text" name="positions[{{ $i }}][beschreibung]" class="form-input" value="{{ $pos->beschreibung }}" required></td>
                                <td><input type="number" name="positions[{{ $i }}][menge]" class="form-input" value="{{ $pos->menge }}" min="0.01" step="0.01" required onchange="calcRow(this)"></td>
                                <td><input type="text" name="positions[{{ $i }}][einheit]" class="form-input" value="{{ $pos->einheit }}"></td>
                                <td><input type="number" name="positions[{{ $i }}][einzelpreis]" class="form-input" value="{{ $pos->einzelpreis }}" min="0" step="0.01" required onchange="calcRow(this)"></td>
                                <td><span class="row-netto" style="font-family:var(--font-mono);font-weight:500;color:var(--color-text-muted)">{{ number_format($pos->nettobetrag, 2, ',', '.') }} €</span></td>
                                <td><button type="button" class="btn btn-secondary btn-sm" onclick="removeRow(this)" title="Entfernen" style="color:var(--color-error)"><i data-lucide="trash-2" width="14" height="14"></i></button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i data-lucide="calendar" width="16" height="16"></i> Rechnungsdaten</div>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        <div class="field">
                            <label class="form-label">Rechnungsdatum <span style="color:var(--color-error)">*</span></label>
                            <input type="date" name="datum" class="form-input" value="{{ old('datum', $rechnung->datum->format('Y-m-d')) }}" required>
                        </div>
                        <div class="field">
                            <label class="form-label">Fälligkeitsdatum <span style="color:var(--color-error)">*</span></label>
                            <input type="date" name="faelligkeitsdatum" class="form-input" value="{{ old('faelligkeitsdatum', $rechnung->faelligkeitsdatum->format('Y-m-d')) }}" required>
                        </div>
                        <div class="field">
                            <label class="form-label">Leistungsdatum</label>
                            <input type="date" name="leistungsdatum" class="form-input" value="{{ old('leistungsdatum', $rechnung->leistungsdatum?->format('Y-m-d')) }}">
                        </div>
                        <div class="field">
                            <label class="form-label">MwSt-Satz (%) <span style="color:var(--color-error)">*</span></label>
                            <input type="number" name="steuersatz" class="form-input" value="{{ old('steuersatz', $rechnung->steuersatz) }}" min="0" max="100" step="0.01" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="background:var(--color-surface-offset)">
                <div class="card-header">
                    <div class="card-title"><i data-lucide="calculator" width="16" height="16"></i> Summen</div>
                </div>
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;margin-bottom:var(--space-2);font-size:var(--text-sm)">
                        <span style="color:var(--color-text-muted)">Nettobetrag</span>
                        <span id="sumNetto" style="font-family:var(--font-mono);font-weight:500">{{ number_format($rechnung->nettobetrag, 2, ',', '.') }} €</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:var(--space-2);font-size:var(--text-sm)">
                        <span style="color:var(--color-text-muted)">MwSt (<span id="sumSteuersatz">{{ number_format($rechnung->steuersatz, 0, ',', '.') }}</span>%)</span>
                        <span id="sumSteuer" style="font-family:var(--font-mono);font-weight:500">{{ number_format($rechnung->steuerbetrag, 2, ',', '.') }} €</span>
                    </div>
                    <div style="height:2px;background:var(--color-border);margin:var(--space-3) 0;border-radius:var(--radius-full)"></div>
                    <div style="display:flex;justify-content:space-between;font-weight:700;font-size:var(--text-base)">
                        <span>Bruttobetrag</span>
                        <span id="sumBrutto" style="font-family:var(--font-mono);color:var(--color-primary)">{{ number_format($rechnung->bruttobetrag, 2, ',', '.') }} €</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Referenzen</div>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        <div class="field">
                            <label class="form-label">Interne Referenz</label>
                            <input type="text" name="intern_ref" class="form-input" value="{{ old('intern_ref', $rechnung->intern_ref) }}">
                        </div>
                        <div class="field">
                            <label class="form-label">Notizen</label>
                            <textarea name="notizen" class="form-input" rows="3">{{ old('notizen', $rechnung->notizen) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%">
                <i data-lucide="check" width="16" height="16"></i> Speichern
            </button>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
let rowIndex = {{ $rechnung->positions->count() }};

function addRow() {
    const tbody = document.getElementById('positionsBody');
    const row = document.createElement('tr');
    row.className = 'position-row';
    row.innerHTML = `
        <td style="font-family:var(--font-mono);color:var(--color-text-faint)">${rowIndex + 1}</td>
        <td><input type="text" name="positions[${rowIndex}][beschreibung]" class="form-input" placeholder="Artikel / Leistung" required></td>
        <td><input type="number" name="positions[${rowIndex}][menge]" class="form-input" value="1" min="0.01" step="0.01" required onchange="calcRow(this)"></td>
        <td><input type="text" name="positions[${rowIndex}][einheit]" class="form-input" value="Stk"></td>
        <td><input type="number" name="positions[${rowIndex}][einzelpreis]" class="form-input" value="0.00" min="0" step="0.01" required onchange="calcRow(this)"></td>
        <td><span class="row-netto" style="font-family:var(--font-mono);font-weight:500;color:var(--color-text-muted)">0,00 €</span></td>
        <td><button type="button" class="btn btn-secondary btn-sm" onclick="removeRow(this)" title="Entfernen" style="color:var(--color-error)"><i data-lucide="trash-2" width="14" height="14"></i></button></td>
    `;
    tbody.appendChild(row);
    rowIndex++;
    lucide.createIcons();
    recalcAll();
}

function removeRow(btn) {
    const tbody = document.getElementById('positionsBody');
    if (tbody.children.length > 1) {
        btn.closest('tr').remove();
        recalcAll();
    }
}

function calcRow(el) {
    const row = el.closest('tr');
    const menge = parseFloat(row.querySelector('input[name*="[menge]"]').value) || 0;
    const preis = parseFloat(row.querySelector('input[name*="[einzelpreis]"]').value) || 0;
    const netto = menge * preis;
    row.querySelector('.row-netto').textContent = netto.toLocaleString('de-DE', {minimumFractionDigits: 2}) + ' €';
    recalcAll();
}

function recalcAll() {
    let netto = 0;
    document.querySelectorAll('.position-row').forEach(row => {
        const menge = parseFloat(row.querySelector('input[name*="[menge]"]')?.value) || 0;
        const preis = parseFloat(row.querySelector('input[name*="[einzelpreis]"]')?.value) || 0;
        netto += menge * preis;
    });
    const steuersatz = parseFloat(document.querySelector('input[name="steuersatz"]').value) || 0;
    const steuer = netto * steuersatz / 100;
    const brutto = netto + steuer;

    document.getElementById('sumNetto').textContent = netto.toLocaleString('de-DE', {minimumFractionDigits: 2}) + ' €';
    document.getElementById('sumSteuer').textContent = steuer.toLocaleString('de-DE', {minimumFractionDigits: 2}) + ' €';
    document.getElementById('sumBrutto').textContent = brutto.toLocaleString('de-DE', {minimumFractionDigits: 2}) + ' €';
    document.getElementById('sumSteuersatz').textContent = steuersatz;
}

document.querySelector('input[name="steuersatz"]').addEventListener('input', recalcAll);
recalcAll();
</script>
@endsection
