@extends('layouts.app')

@section('title', 'Neue Umlagerung')

@section('content')
<div class="page-header">

    <div class="page-title">Neue FBA-Umlagerung</div>
    <div class="page-subtitle">Wähle Artikel, pflege Pflichtfelder und erstelle den Anlieferungsplan direkt in einem Schritt.</div>
</div>

<div class="stepper">
    <div class="step done">
        <div class="step-circle"><i data-lucide="check" width="14" height="14"></i></div>
        <div class="step-label">Basis</div>
    </div>
    <div class="step active">
        <div class="step-circle">2</div>
        <div class="step-label">Artikel</div>
    </div>
    <div class="step">
        <div class="step-circle">3</div>
        <div class="step-label">Etikettierung</div>
    </div>
    <div class="step">
        <div class="step-circle">4</div>
        <div class="step-label">Anlieferungsplan</div>
    </div>
    <div class="step">
        <div class="step-circle">5</div>
        <div class="step-label">Versand</div>
    </div>
</div>

<form method="POST" action="{{ route('fba-shipments.store') }}">
    @csrf

    <div class="card">
        <div class="card-title"><i data-lucide="settings" width="16" height="16"></i> Basiseinstellungen</div>
        <div class="form-grid">
            <div class="field">
                <label>Amazon Account</label>
                <select name="amazon_account_id" class="inp" id="accountSelect">
                    <option value="">Kein Konto (Entwurf/Planung)</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" data-marketplace="{{ $account->marketplace_id }}">{{ $account->name }} ({{ $account->marketplace_id }})</option>
                    @endforeach
                </select>
                <p style="font-size:0.75rem;color:var(--text-secondary);margin-top:var(--space-1)">Ohne Konto: Umlagerung als Entwurf. Plan wird erst mit Account gestartet.</p>
            </div>
            <div class="field">
                <label>Marketplace <span class="req">*</span></label>
                <select name="marketplace_id" class="inp" id="marketplaceSelect">
                    <option value="A1PA6795UKMFR9">Amazon.de (DE)</option>
                    <option value="A1F83G8C2ARO7P">Amazon.co.uk (UK)</option>
                    <option value="A13V1IB3VIYZZH">Amazon.fr (FR)</option>
                    <option value="APJ6JRA9NG5V4">Amazon.it (IT)</option>
                    <option value="A1RKKUPIHCS9HS">Amazon.es (ES)</option>
                    <option value="A1VC38T7YXB528">Amazon.nl (NL)</option>
                    <option value="A1805IZSGTT6HS">Amazon.pl (PL)</option>
                    <option value="A1C3SOZRJQ9KEC">Amazon.se (SE)</option>
                </select>
            </div>
            <div class="field">
                <label>Startlager <span class="req">*</span></label>
                <input type="text" name="source_warehouse" class="inp" placeholder="z.B. Hauptlager Hamm" value="{{ old('source_warehouse') }}">
            </div>
            <div class="field">
                <label>Versandart <span class="req">*</span></label>
                <select name="packaging_type" class="inp">
                    <option value="small_parcel">Small Parcel (SPD)</option>
                    <option value="ltl">LTL / FTL (Palette)</option>
                </select>
            </div>

            <div style="grid-column:1/-1;margin-top:var(--space-3);padding-top:var(--space-3);border-top:1px solid var(--border)">
                <div style="font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:var(--space-3)">Absenderadresse (ship-from)</div>
            </div>
            <div class="field">
                <label>Name / Firma</label>
                <input type="text" name="ship_from_name" class="inp" placeholder="Firmenname" value="{{ old('ship_from_name', auth()->user()->name ?? '') }}">
            </div>
            <div class="field">
                <label>Telefonnummer <span class="req">*</span></label>
                <input type="tel" name="ship_from_phone" class="inp" placeholder="+49 2381 123456" value="{{ old('ship_from_phone') }}">
            </div>
            <div class="field">
                <label>Straße + Nr.</label>
                <input type="text" name="ship_from_address" class="inp" placeholder="Musterstraße 1" value="{{ old('ship_from_address') }}">
            </div>
            <div class="field">
                <label>PLZ</label>
                <input type="text" name="ship_from_zip" class="inp" placeholder="59065" value="{{ old('ship_from_zip') }}">
            </div>
            <div class="field">
                <label>Stadt</label>
                <input type="text" name="ship_from_city" class="inp" placeholder="Hamm" value="{{ old('ship_from_city') }}">
            </div>
            <div class="field">
                <label>Land</label>
                <select name="ship_from_country" class="inp">
                    <option value="DE">Deutschland</option>
                    <option value="AT">Österreich</option>
                    <option value="CH">Schweiz</option>
                    <option value="NL">Niederlande</option>
                    <option value="PL">Polen</option>
                    <option value="IT">Italien</option>
                    <option value="ES">Spanien</option>
                    <option value="FR">Frankreich</option>
                    <option value="SE">Schweden</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">
            <i data-lucide="package" width="16" height="16"></i> Artikel auswählen
            <button type="button" id="btnAddItem" class="btn btn-sm btn-secondary" style="margin-left:auto">
                <i data-lucide="plus" width="14" height="14"></i> Artikel hinzufügen
            </button>
        </div>
        <div style="overflow-x:auto">
            <table class="article-table" id="article-table">
                <thead>
                    <tr>
                        <th>SKU</th><th>Artikelname</th><th>Menge</th><th>Vorbereitung</th><th>Kategorie</th><th>Vorbereitung durch</th><th>Label-Eigentümer</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="itemsContainer"></tbody>
            </table>
        </div>
    </div>

    <div style="display:flex;gap:var(--space-3);margin-top:var(--space-5)">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="arrow-right" width="16" height="16"></i> Umlagerung anlegen
        </button>
        <a href="{{ route('fba-shipments.index') }}" class="btn btn-secondary">Abbrechen</a>
    </div>
</form>

<script>
// Marketplace automatisch basierend auf Account auswählen
document.getElementById('accountSelect').addEventListener('change', function() {
    var mp = this.options[this.selectedIndex].getAttribute('data-marketplace');
    var select = document.getElementById('marketplaceSelect');
    if (mp) {
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value === mp) {
                select.selectedIndex = i;
                break;
            }
        }
    }
});
// Initial setzen
document.getElementById('accountSelect').dispatchEvent(new Event('change'));

let itemIndex = 0;
function addItem() {
    const i = itemIndex++;
    const tbody = document.getElementById('itemsContainer');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="items[${i}][sku]" class="inp" style="width:130px" placeholder="SKU" required></td>
        <td><input type="text" name="items[${i}][name]" class="inp" placeholder="Artikelname" required></td>
        <td><input type="number" name="items[${i}][quantity]" class="inp qty-input" value="1" min="1" required></td>
        <td>
            <select name="items[${i}][prep_instruction]" class="inp" style="width:150px">
                <option value="">Keine</option>
                <option value="ITEM_LABELING">Etikettierung</option>
                <option value="ITEM_POLYBAGGING">Polybagging</option>
                <option value="ITEM_BUBBLEWRAP">Bubble Wrap</option>
                <option value="ITEM_TAPING">Klebeband</option>
                <option value="ITEM_BLACK_SHRINKWRAP">Shrink-Wrap</option>
                <option value="ITEM_HANG_GARMENT">Hängend</option>
                <option value="ITEM_BOXING">Kartonieren</option>
                <option value="ITEM_SIOC">Ohne Verpackung (SIOC)</option>
                <option value="ITEM_NO_PREP">Keine Vorbereitung</option>
            </select>
        </td>
        <td>
            <select name="items[${i}][prep_category]" class="inp" style="width:140px">
                <option value="">Standard</option>
                <option value="NONE">Keine</option>
                <option value="FRAGILE">Zerbrechlich</option>
                <option value="SMALL">Klein</option>
                <option value="TEXTILE">Textil</option>
                <option value="LIQUID">Flüssig</option>
                <option value="SHARP">Scharf</option>
                <option value="BABY">Baby</option>
                <option value="ADULT">Erwachsenen</option>
                <option value="HANGER">Hänger</option>
                <option value="SET">Set</option>
                <option value="GRANULAR">Körnig</option>
                <option value="PERFORATED">Perforiert</option>
                <option value="FC_PROVIDED">Von FC bereitgestellt</option>
                <option value="UNKNOWN">Unbekannt</option>
            </select>
        </td>
        <td>
            <select name="items[${i}][prep_owner]" class="inp" style="width:130px">
                <option value="">NONE</option>
                <option value="SELLER">Seller</option>
                <option value="AMAZON">Amazon (FBA)</option>
            </select>
        </td>
        <td>
            <select name="items[${i}][label_owner]" class="inp" style="width:130px">
                <option value="">NONE</option>
                <option value="SELLER">Seller</option>
                <option value="AMAZON">Amazon (FBA)</option>
            </select>
        </td>
        <td><button type="button" class="btn btn-icon btn-ghost" onclick="this.closest('tr').remove()"><i data-lucide="trash-2" width="14" height="14"></i></button></td>
    `;
    tbody.appendChild(tr);
    lucide.createIcons();
}
document.getElementById('btnAddItem').addEventListener('click', addItem);
addItem();
</script>
@endsection
