<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $auftrag->auftragsnummer }}</title>
    <style>
        @page {
            margin: 15mm 20mm 20mm 20mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9pt;
            color: #222;
            line-height: 1.3;
            border: 1pt dashed #cccccc;
            padding: 8mm;
            position: relative;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .company_header {
            width: 100%;
            height: 27mm;
            margin-bottom: 5mm;
        }

        .company_header td {
            vertical-align: top;
        }

        .logo-block img {
            max-height: 27mm;
            width: auto;
        }

        .din_company_info {
            width: 100%;
            margin-bottom: 5mm;
        }

        .din_company_info td {
            vertical-align: top;
        }

        #din5008_report_main_address {
            width: 85mm;
            min-height: 45mm;
        }

        .colored_address {
            font-size: 7pt;
            color: #555;
            white-space: nowrap;
            overflow: hidden;
            margin-bottom: 1mm;
        }

        .company_invoice_line {
            border: none;
            border-top: 0.5pt solid #888;
            margin-bottom: 3mm;
        }

        .address-text {
            font-size: 9.5pt;
            line-height: 1.25;
            color: #222;
        }

        .meta-table {
            width: 100%;
        }

        .meta-table td {
            font-size: 8.5pt;
            padding: 0.3mm 0;
            line-height: 1.1;
        }

        .meta-label {
            color: #555;
            width: 50%;
        }

        .meta-value {
            font-weight: bold;
            text-align: right;
            width: 50%;
        }

        .address-title {
            font-size: 8.5pt;
            font-weight: 700;
        }

        h2 {
            font-size: 18pt;
            font-weight: bold;
            color: #111;
            margin: 6mm 0 4mm 0;
        }

        .items {
            width: 100%;
            margin-top: 4mm;
        }

        .items th {
            text-align: left;
            font-size: 8.5pt;
            font-weight: bold;
            padding: 2mm 0;
            border-bottom: 1pt solid #222;
        }

        .items td {
            padding: 2.5mm 0;
            vertical-align: top;
            font-size: 8.5pt;
        }

        .items .right { text-align: right; }

        .summary-table {
            width: 45%;
            float: right;
            margin-top: 6mm;
            border-top: 1pt solid #222;
            border-bottom: 1pt solid #222;
            text-align: right;
        }

        .summary-table td {
            padding: 1.5mm 0;
            font-size: 9pt;
        }

        .summary-total {
            font-weight: bold;
            border-top: 0.5pt solid #888;
            text-align: right;
        }

        .info-block {
            margin-top: 10mm;
            font-size: 8.5pt;
            color: #222;
            line-height: 1.4;
        }

        .clear { clear: both; }

        .footer {
            position: fixed;
            bottom: 0mm;
            padding: 8mm;
            left: 0;
            right: 0;
            font-size: 7.5pt;
            color: #555;
            border-top: 0.5pt solid #ccc;
            padding-top: 2mm;
        }

        .footer table td {
            vertical-align: top;
            width: 25%;
            padding: 0 1mm;
        }

        .footer ul {
            list-style: none;
            padding: 0;
        }

        .page_number {
            text-align: right;
            font-size: 7.5pt;
            margin-bottom: 1mm;
        }

        .footer td ul li {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>

@php
    $logoData = null;
    $logoMime = null;
    $logoPath = storage_path('app/public/logo.png');
    if (is_file($logoPath)) {
        $logoData = base64_encode(file_get_contents($logoPath));
        $logoMime = mime_content_type($logoPath);
    }
@endphp

<!-- Kopfzeile mit Logo -->
<table class="company_header">
    <tr>
        <td class="logo-block" style="text-align: right;">
            @if($logoData && $logoMime)
                <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="Logo">
            @endif
        </td>
    </tr>
</table>

<!-- Hauptblock: DIN 5008 Adressfenster & Infoblock -->
<table class="din_company_info">
    <tr>
        <td style="width: 60%;">
            <div id="din5008_report_main_address">
                <div class="colored_address">
                    {{ $tenant->company ?? $tenant->name }}
                    @if(!empty($tenant->street)) | {{ $tenant->street }} @endif
                    @if(!empty($tenant->zip) || !empty($tenant->city)) | {{ $tenant->zip }} {{ $tenant->city }} @endif
                    @if(!empty($tenant->country)) | {{ $tenant->country }} @endif
                </div>

                <hr class="company_invoice_line"/>

                <div class="brief-block">
                    @if($auftrag->kunde_firma)
                        {{ $auftrag->kunde_firma }}<br>
                    @endif
                    @if($auftrag->kunde_name)
                        {{ $auftrag->kunde_name }}<br>
                    @endif
                    @if($auftrag->kunde_strasse)
                        {{ $auftrag->kunde_strasse }}<br>
                    @endif
                    {{ $auftrag->kunde_plz }} {{ $auftrag->kunde_ort }}
                </div>
            </div>
        </td>
        <td style="width: 40%;">
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Auftragsnr.:</td>
                    <td class="meta-value">{{ $auftrag->auftragsnummer }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Kunde:</td>
                    <td class="meta-value">{{ $auftrag->kunde_firma ?? $auftrag->kunde_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Startdatum:</td>
                    <td class="meta-value">{{ $auftrag->startdatum?->format('d.m.Y') }}</td>
                </tr>
                @if($auftrag->enddatum)
                <tr>
                    <td class="meta-label">Enddatum:</td>
                    <td class="meta-value">{{ $auftrag->enddatum->format('d.m.Y') }}</td>
                </tr>
                @endif
                @if($auftrag->isWiederkehrend())
                <tr>
                    <td class="meta-label">Nächste Erstellung:</td>
                    <td class="meta-value">{{ $auftrag->naechste_erstellung?->format('d.m.Y') }}</td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>



<!-- Titel -->
<h2>Auftrag</h2>

<!-- Positionstabelle -->
<table class="items">
    <thead>
        <tr>
            <th style="width: 40%;">Beschreibung</th>
            <th style="width: 10%;" class="right">Menge</th>
            <th style="width: 12%;" class="right">Einzelpreis</th>
            @if($positions->contains(fn($p) => $p->rabatt > 0))
                <th style="width: 8%;" class="right">Rabatt</th>
            @endif
            <th style="width: 8%;" class="right">USt</th>
            <th style="width: 22%;" class="right">Gesamt netto</th>
        </tr>
    </thead>
    <tbody>
        @foreach($positions as $pos)
            <tr>
                <td>
                    {{ $pos->beschreibung }}
                    @if(!empty($pos->notizen))
                        <br><span style="font-size:7.5pt;color:#666;font-style:italic">{{ $pos->notizen }}</span>
                    @endif
                </td>
                <td class="right">{{ number_format($pos->menge, 2, ',', '.') }} {{ $pos->einheit }}</td>
                <td class="right">{{ number_format($pos->einzelpreis, 2, ',', '.') }} €</td>
                @if($positions->contains(fn($p) => $p->rabatt > 0))
                    <td class="right">{{ $pos->rabatt > 0 ? number_format($pos->rabatt, 0, ',', '.') . '%' : '—' }}</td>
                @endif
                <td class="right">{{ number_format($pos->steuersatz, 0, ',', '.') }}%</td>
                <td class="right">{{ number_format($pos->menge * $pos->einzelpreis * (1 - $pos->rabatt / 100), 2, ',', '.') }} €</td>
            </tr>
        @endforeach
    </tbody>
</table>

<!-- Summenblock -->
<table class="summary-table">
    <tr>
        <td>Nettobetrag</td>
        <td class="right">{{ number_format($auftrag->nettobetrag(), 2, ',', '.') }} €</td>
    </tr>
    @foreach($auftrag->steuerAufschluesselung() as $steuer)
    <tr>
        <td>MwSt {{ number_format($steuer['satz'], 0, ',', '.') }}%</td>
        <td class="right">{{ number_format($steuer['steuer'], 2, ',', '.') }} €</td>
    </tr>
    @endforeach
    <tr class="summary-total">
        <td><strong>Gesamtbetrag</strong></td>
        <td class="right"><strong>{{ number_format($auftrag->bruttobetrag(), 2, ',', '.') }} €</strong></td>
    </tr>
</table>

<div class="clear"></div>

@if(!empty($auftrag->notizen))
<div class="info-block">
    <strong>Hinweise:</strong> {!! nl2br(e($auftrag->notizen)) !!}
</div>
@endif

<!-- Fußzeile (DIN 5008 Style) -->
<div class="footer">
    <div class="page_number">
        Seite <span class="page">1</span> von <span class="topage">1</span>
    </div>
    <table>
        <tr>
            <td>
                <ul>
                    <li><strong>{{ $tenant->company }}</strong></li>
                    <li>{{ $tenant->street }}</li>
                    <li>{{ $tenant->zip }} {{ $tenant->city }}</li>
                </ul>
            </td>
            <td>
                <ul>
                    <li>{{ $tenant->webseiten ?? $tenant->webseite ?? $tenant->website }}</li>
                    <li>{{ $tenant->email }}</li>
                    @if(!empty($tenant->telefon)) <li>Tel: {{ $tenant->telefon }}</li> @endif
                </ul>
            </td>
            <td>
                <ul>
                    <li>USt-ID: {{ $tenant->ust_id ?? $tenant->vat_id }}</li>
                    @if(!empty($tenant->steuernummer)) <li>St.-Nr.: {{ $tenant->steuernummer }}</li> @endif
                    <li>HRB Nr: {{ $tenant->hrb }}</li>
                </ul>
            </td>
            <td>
                <ul>
                    <li>{{ $tenant->bank_name }}</li>
                    <li>IBAN: {{ $tenant->iban }}</li>
                </ul>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
