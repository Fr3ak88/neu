<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Stornobeleg {{ $rechnung->rechnungsnummer }}</title>
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
            color: #a12c7b;
            margin: 6mm 0 2mm 0;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }

        .storno-badge {
            display: inline-block;
            background: #a12c7b;
            color: #fff;
            font-size: 10pt;
            font-weight: bold;
            padding: 2mm 5mm;
            border-radius: 3mm;
            margin-bottom: 4mm;
        }

        .storno-reference {
            font-size: 9pt;
            color: #555;
            margin-bottom: 4mm;
            padding: 3mm;
            background: #f5f5f5;
            border: 0.5pt solid #ddd;
            border-radius: 2mm;
        }

        .storno-reference strong {
            color: #222;
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
        .items .negative { color: #a12c7b; }

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

        .payment-qr {
            margin-top: 10mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .payment-details {
            width: 55%;
            font-size: 8.5pt;
            color: #222;
            line-height: 1.3;
        }

        .payment-details dt {
            font-weight: 700;
            margin-top: 2mm;
        }

        .payment-details dd {
            margin: 0 0 3mm 0;
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
                    {{ $tenant->company ?? 'Fritzler-Solution' }}
                    @if(!empty($tenant->street)) | {{ $tenant->street }} @endif
                    @if(!empty($tenant->zip) || !empty($tenant->city)) | {{ $tenant->zip }} {{ $tenant->city }} @endif
                    @if(!empty($tenant->country)) | {{ $tenant->country }} @endif
                </div>
                
                <hr class="company_invoice_line"/>

               <div class="brief-block">
                        @if($rechnung->kunde_firma)
                            {{ $rechnung->kunde_firma }}<br>
                        @endif
                        @if($rechnung->kunde_name)
                            {{ $rechnung->kunde_name }}<br>
                        @endif
                        @if($rechnung->kunde_strasse)
                            {{ $rechnung->kunde_strasse }}<br>
                        @endif
                        {{ $rechnung->kunde_plz }} {{ $rechnung->kunde_ort }}
                    </div>
            </div>
        </td>
        <td style="width: 40%;">
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Stornobeleg-Nr.:</td>
                    <td class="meta-value">{{ $rechnung->rechnungsnummer }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Original-Rechnung:</td>
                    <td class="meta-value">{{ $originalRechnung->rechnungsnummer }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Stornodatum:</td>
                    <td class="meta-value">{{ $rechnung->datum->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Originaldatum:</td>
                    <td class="meta-value">{{ $originalRechnung->datum->format('d.m.Y') }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>



<!-- Referenz auf Originalrechnung -->
<div class="storno-reference">
    <strong>Storno zu:</strong> {{ $originalRechnung->rechnungsnummer }} vom {{ $originalRechnung->datum->format('d.m.Y') }}<br>
    <strong>Betrag:</strong> {{ number_format($originalRechnung->bruttobetrag, 2, ',', '.') }} €
</div>

<!-- Titel -->
<h2>Stornobeleg</h2>

<!-- Positionstabelle -->
<table class="items">
    <thead>
        <tr>
            <th style="width: 40%;">Beschreibung</th>
            <th style="width: 10%;" class="right">Menge</th>
            <th style="width: 12%;" class="right">Einzelpreis</th>
            <th style="width: 8%;" class="right">USt</th>
            <th style="width: 22%;" class="right">Gesamt netto</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($positions) && count($positions) > 0)
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
                    <td class="right">{{ number_format($pos->steuersatz, 0, ',', '.') }}%</td>
                    <td class="right negative">{{ number_format($pos->nettobetrag, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>

<!-- Summenblock -->
<table class="summary-table">
    <tr>
        <td>Nettobetrag</td>
        <td class="right negative">{{ number_format($rechnung->nettobetrag ?? 0, 2, ',', '.') }} €</td>
    </tr>
    @foreach($rechnung->steuerAufschluesselung() as $steuer)
    <tr>
        <td>MwSt {{ number_format($steuer['satz'], 0, ',', '.') }}%</td>
        <td class="right negative">{{ number_format($steuer['steuer'], 2, ',', '.') }} €</td>
    </tr>
    @endforeach
    <tr class="summary-total">
        <td><strong>Stornobetrag</strong></td>
        <td class="right negative"><strong>{{ number_format($rechnung->bruttobetrag ?? 0, 2, ',', '.') }} €</strong></td>
    </tr>
</table>

<div class="clear"></div>

<!-- Fußzeile -->
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
                    <li>HRB Nr: {{ $tenant->hrb  }}</li>
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
