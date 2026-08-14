@extends('layouts.app')

@section('title', 'Neuer Auftrag')

@section('content')
<div class="page-header">
    <div class="page-title">Neuer Auftrag</div>
    <a href="{{ route('auftraege.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left" width="16" height="16"></i> Zurück
    </a>
</div>

<form method="POST" action="{{ route('auftraege.store') }}" id="auftragForm">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            {{-- Empfänger --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i data-lucide="user" width="16" height="16"></i> Empfänger</div>
                </div>
                <div class="card-body">
                    <div class="field" style="margin-bottom:var(--space-5);position:relative" id="customerSearchWrap">
                        <label class="form-label">Kunde suchen</label>
                        <div style="position:relative">
                            <input type="text" id="customerSearch" class="form-input" placeholder="Name, Firma oder E-Mail eingeben..." autocomplete="off" style="padding-left:var(--space-10)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:var(--space-3);top:50%;transform:translateY(-50%);color:var(--color-text-faint);pointer-events:none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <button type="button" id="customerClear" style="display:none;position:absolute;right:var(--space-3);top:50%;transform:translateY(-50%);color:var(--color-text-faint);padding:var(--space-1);border-radius:var(--radius-sm)" title="Auswahl aufheben">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="customer_id" id="customerId">
                        <div id="customerDropdown" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:50;margin-top:var(--space-1);background:var(--color-surface-2);border:1.5px solid var(--color-border);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);max-height:260px;overflow-y:auto"></div>
                    </div>
                    <div class="form-section">
                        <div class="form-section-title">Adressdaten</div>
                    </div>
                    <div class="space-y-4">
                        <div class="form-grid">
                            <div class="field">
                                <label class="form-label">Name</label>
                                <input type="text" name="kunde_name" class="form-input" value="{{ old('kunde_name') }}" placeholder="Vor- und Nachname">
                            </div>
                            <div class="field">
                                <label class="form-label">Firma</label>
                                <input type="text" name="kunde_firma" class="form-input" value="{{ old('kunde_firma') }}" placeholder="Firmenname">
                            </div>
                        </div>
                        <div class="field">
                            <label class="form-label">E-Mail</label>
                            <input type="email" name="kunde_email" class="form-input" value="{{ old('kunde_email') }}" placeholder="kunde@beispiel.de">
                        </div>
                        <div class="field">
                            <label class="form-label">Straße, Hausnummer</label>
                            <input type="text" name="kunde_strasse" class="form-input" value="{{ old('kunde_strasse') }}" placeholder="Musterstraße 1">
                        </div>
                        <div class="form-grid-3">
                            <div class="field">
                                <label class="form-label">PLZ</label>
                                <input type="text" name="kunde_plz" class="form-input" value="{{ old('kunde_plz') }}" placeholder="12345">
                            </div>
                            <div class="field">
                                <label class="form-label">Ort</label>
                                <input type="text" name="kunde_ort" class="form-input" value="{{ old('kunde_ort') }}" placeholder="Musterstadt">
                            </div>
                            <div class="field">
                                <label class="form-label">Land</label>
                                <input type="text" name="kunde_land" class="form-input" value="{{ old('kunde_land', 'DE') }}" maxlength="2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Positionen --}}
            <div class="card">
                <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
                    <div class="card-title" style="margin-bottom:0"><i data-lucide="list" width="16" height="16"></i> Positionen (Vorlage)</div>
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
                                <th style="width:70px">Menge</th>
                                <th style="width:60px">Einheit</th>
                                <th style="width:90px">Einzelpreis</th>
                                <th style="width:70px">MwSt %</th>
                                <th style="width:70px">Rabatt %</th>
                                <th style="width:110px">Netto</th>
                                <th style="width:100px"></th>
                            </tr>
                        </thead>
                        <tbody id="positionsBody">
                            <tr class="position-row">
                                <td style="font-family:var(--font-mono);color:var(--color-text-faint)">1</td>
                                <td><input type="text" name="positions[0][beschreibung]" class="form-input" placeholder="Artikel / Leistung" required></td>
                                <td><input type="number" name="positions[0][menge]" class="form-input" value="1" min="0.01" step="0.01" required onchange="calcRow(this)"></td>
                                <td><input type="text" name="positions[0][einheit]" class="form-input" value="Stk"></td>
                                <td><input type="number" name="positions[0][einzelpreis]" class="form-input" value="0.00" min="0" step="0.01" required onchange="calcRow(this)"></td>
                                <td><input type="number" name="positions[0][steuersatz]" class="form-input" value="19" min="0" max="100" step="0.01" required onchange="calcRow(this)"></td>
                                <td><input type="number" name="positions[0][rabatt]" class="form-input" value="0" min="0" max="100" step="0.01" onchange="calcRow(this)"></td>
                                <td><span class="row-netto" style="font-family:var(--font-mono);font-weight:500;color:var(--color-text-muted)">0,00 €</span></td>
                                <td style="display:flex;gap:var(--space-1)">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleNote(this)" title="Notiz"><i data-lucide="file-text" width="14" height="14"></i></button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="removeRow(this)" title="Entfernen" style="color:var(--color-error)"><i data-lucide="trash-2" width="14" height="14"></i></button>
                                </td>
                            </tr>
                            <tr class="note-row" style="display:none">
                                <td colspan="9" style="padding:0 var(--space-2) var(--space-2)">
                                    <textarea name="positions[0][notizen]" class="form-input" rows="2" placeholder="Optionale Notiz zur Position..." style="font-size:var(--text-sm)"></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            {{-- Auftragsdaten --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i data-lucide="settings" width="16" height="16"></i> Auftragsdaten</div>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        <div class="field">
                            <label class="form-label">Auftragstyp <span style="color:var(--color-error)">*</span></label>
                            <select name="typ" class="form-input" required id="typSelect">
                                <option value="einmalig" {{ old('typ', 'einmalig') === 'einmalig' ? 'selected' : '' }}>Einmalig</option>
                                <option value="wiederkehrend" {{ old('typ') === 'wiederkehrend' ? 'selected' : '' }}>Wiederkehrend – Automatisch Rechnung erstellen</option>
                            </select>
                        </div>
                        <div class="field" id="intervallWrap" style="{{ old('typ', 'einmalig') === 'einmalig' ? 'display:none' : '' }}">
                            <label class="form-label">Intervall <span style="color:var(--color-error)">*</span></label>
                            <select name="intervall" class="form-input" id="intervallSelect">
                                <option value="woechentlich" {{ old('intervall') === 'woechentlich' ? 'selected' : '' }}>Wöchentlich</option>
                                <option value="monatlich" {{ old('intervall', 'monatlich') === 'monatlich' ? 'selected' : '' }}>Monatlich</option>
                                <option value="vierteljaehrlich" {{ old('intervall') === 'vierteljaehrlich' ? 'selected' : '' }}>Vierteljährlich</option>
                                <option value="jaehrlich" {{ old('intervall') === 'jaehrlich' ? 'selected' : '' }}>Jährlich</option>
                            </select>
                        </div>
                        <div class="form-grid">
                            <div class="field">
                                <label class="form-label">Startdatum <span style="color:var(--color-error)">*</span></label>
                                <input type="date" name="startdatum" class="form-input" value="{{ old('startdatum', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="field">
                                <label class="form-label">Enddatum</label>
                                <input type="date" name="enddatum" class="form-input" value="{{ old('enddatum') }}">
                            </div>
                        </div>
                        <div class="field">
                            <label class="form-label">Zahlungsziel (Tage)</label>
                            <input type="number" name="faelligkeit_tage" class="form-input" value="{{ old('faelligkeit_tage', '30') }}" min="0" max="365">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Summen --}}
            <div class="card" style="background:var(--color-surface-offset)">
                <div class="card-header">
                    <div class="card-title"><i data-lucide="calculator" width="16" height="16"></i> Beträge</div>
                </div>
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;margin-bottom:var(--space-2);font-size:var(--text-sm)">
                        <span style="color:var(--color-text-muted)">Netto pro Periode</span>
                        <span id="sumNetto" style="font-family:var(--font-mono);font-weight:500">0,00 €</span>
                    </div>
                    <div id="mwstDetails" style="margin-bottom:var(--space-2);font-size:var(--text-sm)"></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:var(--space-3);font-size:var(--text-sm);padding-top:var(--space-2);border-top:1px solid var(--color-divider)">
                        <span style="font-weight:600">Brutto pro Periode</span>
                        <span id="sumBrutto" style="font-family:var(--font-mono);font-weight:600">0,00 €</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:var(--text-sm)">
                        <span style="color:var(--color-text-muted)" id="sumMonatlichLabel">Geschätzter Monatsbetrag</span>
                        <span id="sumMonatlich" style="font-family:var(--font-mono);font-weight:500;color:var(--color-primary)">0,00 €</span>
                    </div>
                </div>
            </div>

            {{-- Notizen --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Notizen</div>
                </div>
                <div class="card-body">
                    <div class="field">
                        <textarea name="notizen" class="form-input" rows="3" placeholder="Optionale Notizen...">{{ old('notizen') }}</textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%">
                <i data-lucide="check" width="16" height="16"></i> Auftrag erstellen
            </button>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
let rowIndex = 1;

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
        <td><input type="number" name="positions[${rowIndex}][steuersatz]" class="form-input" value="19" min="0" max="100" step="0.01" required onchange="calcRow(this)"></td>
        <td><input type="number" name="positions[${rowIndex}][rabatt]" class="form-input" value="0" min="0" max="100" step="0.01" onchange="calcRow(this)"></td>
        <td><span class="row-netto" style="font-family:var(--font-mono);font-weight:500;color:var(--color-text-muted)">0,00 €</span></td>
        <td style="display:flex;gap:var(--space-1)">
            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleNote(this)" title="Notiz"><i data-lucide="file-text" width="14" height="14"></i></button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="removeRow(this)" title="Entfernen" style="color:var(--color-error)"><i data-lucide="trash-2" width="14" height="14"></i></button>
        </td>
    `;
    const noteRow = document.createElement('tr');
    noteRow.className = 'note-row';
    noteRow.style.display = 'none';
    noteRow.innerHTML = `<td colspan="9" style="padding:0 var(--space-2) var(--space-2)"><textarea name="positions[${rowIndex}][notizen]" class="form-input" rows="2" placeholder="Optionale Notiz zur Position..." style="font-size:var(--text-sm)"></textarea></td>`;
    tbody.appendChild(row);
    tbody.appendChild(noteRow);
    rowIndex++;
    lucide.createIcons();
    recalcAll();
}

function toggleNote(btn) {
    const posRow = btn.closest('tr');
    const noteRow = posRow.nextElementSibling;
    if (noteRow && noteRow.classList.contains('note-row')) {
        noteRow.style.display = noteRow.style.display === 'none' ? '' : 'none';
    }
}

function removeRow(btn) {
    const tbody = document.getElementById('positionsBody');
    const posRow = btn.closest('tr');
    const noteRow = posRow.nextElementSibling;
    if (tbody.children.length > 2) {
        posRow.remove();
        if (noteRow && noteRow.classList.contains('note-row')) noteRow.remove();
        recalcAll();
    }
}

function calcRow(el) {
    const row = el.closest('tr');
    const menge = parseFloat(row.querySelector('input[name*="[menge]"]').value) || 0;
    const preis = parseFloat(row.querySelector('input[name*="[einzelpreis]"]').value) || 0;
    const rabatt = parseFloat(row.querySelector('input[name*="[rabatt]"]').value) || 0;
    const netto = menge * preis * (1 - rabatt / 100);
    row.querySelector('.row-netto').textContent = netto.toLocaleString('de-DE', {minimumFractionDigits: 2}) + ' €';
    recalcAll();
}

function recalcAll() {
    let netto = 0;
    const steuerByRate = {};
    document.querySelectorAll('.position-row').forEach(row => {
        const menge = parseFloat(row.querySelector('input[name*="[menge]"]')?.value) || 0;
        const preis = parseFloat(row.querySelector('input[name*="[einzelpreis]"]')?.value) || 0;
        const ust = parseFloat(row.querySelector('input[name*="[steuersatz]"]')?.value) || 0;
        const rabatt = parseFloat(row.querySelector('input[name*="[rabatt]"]')?.value) || 0;
        const posNetto = menge * preis * (1 - rabatt / 100);
        netto += posNetto;
        if (posNetto > 0 && ust > 0) {
            steuerByRate[ust] = (steuerByRate[ust] || 0) + posNetto * ust / 100;
        }
    });
    let steuer = 0;
    Object.values(steuerByRate).forEach(v => steuer += v);
    const brutto = netto + steuer;
    document.getElementById('sumNetto').textContent = netto.toLocaleString('de-DE', {minimumFractionDigits: 2}) + ' €';
    const mwstEl = document.getElementById('mwstDetails');
    const rates = Object.keys(steuerByRate).sort((a, b) => b - a);
    if (rates.length === 0) {
        mwstEl.innerHTML = '';
    } else if (rates.length === 1) {
        mwstEl.innerHTML = `<div style="display:flex;justify-content:space-between"><span style="color:var(--color-text-muted)">MwSt (${rates[0]}%)</span><span style="font-family:var(--font-mono);font-weight:500">${steuerByRate[rates[0]].toLocaleString('de-DE', {minimumFractionDigits: 2})} €</span></div>`;
    } else {
        mwstEl.innerHTML = rates.map(r =>
            `<div style="display:flex;justify-content:space-between;padding-left:var(--space-3)"><span style="color:var(--color-text-muted)">${r}%</span><span style="font-family:var(--font-mono);font-weight:500">${steuerByRate[r].toLocaleString('de-DE', {minimumFractionDigits: 2})} €</span></div>`
        ).join('') + `<div style="display:flex;justify-content:space-between;margin-top:var(--space-1)"><span style="color:var(--color-text-muted)">Summe MwSt</span><span style="font-family:var(--font-mono);font-weight:500">${steuer.toLocaleString('de-DE', {minimumFractionDigits: 2})} €</span></div>`;
    }
    document.getElementById('sumBrutto').textContent = brutto.toLocaleString('de-DE', {minimumFractionDigits: 2}) + ' €';

    const typ = document.getElementById('typSelect').value;
    const intervall = document.getElementById('intervallSelect').value;
    let monatlich = brutto;
    if (typ === 'wiederkehrend') {
        if (intervall === 'woechentlich') monatlich = brutto * 4.33;
        else if (intervall === 'vierteljaehrlich') monatlich = brutto / 3;
        else if (intervall === 'jaehrlich') monatlich = brutto / 12;
    }
    document.getElementById('sumMonatlich').textContent = monatlich.toLocaleString('de-DE', {minimumFractionDigits: 2}) + ' €';
}

document.querySelector('select[name="intervall"]').addEventListener('change', recalcAll);

document.getElementById('typSelect').addEventListener('change', function() {
    const wrap = document.getElementById('intervallWrap');
    const label = document.getElementById('sumMonatlichLabel');
    if (this.value === 'wiederkehrend') {
        wrap.style.display = '';
        label.textContent = 'Geschätzter Monatsbetrag';
    } else {
        wrap.style.display = 'none';
        label.textContent = 'Gesamtbetrag';
    }
    recalcAll();
});

// ── Customer Search ────────────────────────────────────────
const customers = @json($customers);

const searchInput = document.getElementById('customerSearch');
const dropdown = document.getElementById('customerDropdown');
const idInput = document.getElementById('customerId');
const clearBtn = document.getElementById('customerClear');
let activeIndex = -1;

function renderDropdown(query) {
    const q = query.toLowerCase().trim();
    if (!q) { dropdown.style.display = 'none'; return; }

    const matches = customers.filter(c =>
        (c.name && c.name.toLowerCase().includes(q)) ||
        (c.company && c.company.toLowerCase().includes(q)) ||
        (c.email && c.email.toLowerCase().includes(q))
    ).slice(0, 10);

    if (!matches.length) {
        dropdown.innerHTML = '<div style="padding:var(--space-3) var(--space-4);font-size:var(--text-sm);color:var(--color-text-faint)">Keine Treffer</div>';
        dropdown.style.display = 'block';
        return;
    }

    dropdown.innerHTML = matches.map((c, i) => {
        const label = c.company
            ? (c.name ? `${c.name} · ${c.company}` : c.company)
            : (c.name || c.email || 'Unbekannt');
        const sub = [c.email, [c.zip, c.city].filter(Boolean).join(' ')].filter(Boolean).join(' · ');
        return `<div class="customer-result" data-index="${i}" style="padding:var(--space-3) var(--space-4);cursor:pointer;border-bottom:1px solid var(--color-divider);transition:background var(--transition)">
            <div style="font-size:var(--text-sm);font-weight:500;color:var(--color-text)">${label}</div>
            ${sub ? `<div style="font-size:var(--text-xs);color:var(--color-text-muted);margin-top:2px">${sub}</div>` : ''}
        </div>`;
    }).join('');

    dropdown.style.display = 'block';
    activeIndex = -1;
}

function selectCustomer(c) {
    searchInput.value = c.company || c.name || '';
    idInput.value = c.id;
    clearBtn.style.display = 'flex';
    dropdown.style.display = 'none';

    const setVal = (name, val) => { const el = document.querySelector(`[name="${name}"]`); if (el) el.value = val || ''; };
    setVal('kunde_name', c.name);
    setVal('kunde_firma', c.company);
    setVal('kunde_email', c.email);
    setVal('kunde_strasse', c.street);
    setVal('kunde_plz', c.zip);
    setVal('kunde_ort', c.city);
    setVal('kunde_land', c.country);
}

function highlightResult(index) {
    dropdown.querySelectorAll('.customer-result').forEach((el, i) => {
        el.style.background = i === index ? 'var(--color-surface-offset)' : '';
    });
}

searchInput.addEventListener('input', function() {
    idInput.value = '';
    clearBtn.style.display = 'none';
    renderDropdown(this.value);
});

searchInput.addEventListener('keydown', function(e) {
    const items = dropdown.querySelectorAll('.customer-result');
    if (!items.length) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex = Math.min(activeIndex + 1, items.length - 1);
        highlightResult(activeIndex);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex = Math.max(activeIndex - 1, 0);
        highlightResult(activeIndex);
    } else if (e.key === 'Enter' && activeIndex >= 0) {
        e.preventDefault();
        items[activeIndex].click();
    } else if (e.key === 'Escape') {
        dropdown.style.display = 'none';
    }
});

dropdown.addEventListener('click', function(e) {
    const item = e.target.closest('.customer-result');
    if (!item) return;
    const idx = parseInt(item.dataset.index);
    const q = searchInput.value.toLowerCase().trim();
    const matches = customers.filter(c =>
        (c.name && c.name.toLowerCase().includes(q)) ||
        (c.company && c.company.toLowerCase().includes(q)) ||
        (c.email && c.email.toLowerCase().includes(q))
    ).slice(0, 10);
    if (matches[idx]) selectCustomer(matches[idx]);
});

clearBtn.addEventListener('click', function() {
    searchInput.value = '';
    idInput.value = '';
    this.style.display = 'none';
    const setVal = (name) => { const el = document.querySelector(`[name="${name}"]`); if (el) el.value = ''; };
    ['kunde_name','kunde_firma','kunde_email','kunde_strasse','kunde_plz','kunde_ort','kunde_land'].forEach(setVal);
    searchInput.focus();
});

document.addEventListener('click', function(e) {
    if (!document.getElementById('customerSearchWrap').contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

recalcAll();
</script>
@endsection
