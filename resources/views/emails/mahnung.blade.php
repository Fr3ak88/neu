<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mahnung — Rechnung {{ $rechnung->rechnungsnummer }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #c0392b; color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; opacity: 0.85; font-size: 14px; }
        .content { padding: 30px; }
        .greeting { font-size: 16px; margin-bottom: 20px; }
        .alert { background: #fdf2f2; border: 1px solid #f5c6cb; border-radius: 6px; padding: 15px; margin: 20px 0; color: #721c24; }
        .invoice-box { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 20px; margin: 20px 0; }
        .totals { margin-top: 15px; text-align: right; }
        .totals .row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px; }
        .totals .row.muted { color: #666; }
        .totals .divider { height: 1px; background: #dee2e6; margin: 8px 0; }
        .totals .row.total { font-weight: 700; font-size: 16px; color: #c0392b; }
        .footer { padding: 20px 30px; background: #f8f9fa; border-top: 1px solid #e9ecef; font-size: 12px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Mahnung {{ $rechnung->mahnungen_count + 1 }}</h1>
            <p>Rechnung {{ $rechnung->rechnungsnummer }}</p>
        </div>

        <div class="content">
            <div class="greeting">
                Sehr geehrte/r {{ $rechnung->kunde_name ?? $rechnung->kunde_firma ?? 'Kunde' }},
            </div>

            <div class="alert">
                <strong>Aufmerksamkeit:</strong> Die folgende Rechnung ist seit
                <strong>{{ $rechnung->daysOverdue() }} Tag(en)</strong> überfällig.
                Der fällige Betrag wird hiermit freundlich, aber nachdrücklich eingefordert.
            </div>

            <p>
                Rechnungsnummer: <strong>{{ $rechnung->rechnungsnummer }}</strong><br>
                Rechnungsdatum: {{ $rechnung->datum->format('d.m.Y') }}<br>
                Fälligkeitsdatum: {{ $rechnung->faelligkeitsdatum->format('d.m.Y') }}
            </p>

            @if($customText)
            <div class="invoice-box">
                {!! nl2br(e($customText)) !!}
            </div>
            @endif

            <div class="invoice-box">
                <div class="totals">
                    <div class="row muted">
                        <span>Nettobetrag</span>
                        <span>{{ number_format($rechnung->nettobetrag, 2, ',', '.') }} €</span>
                    </div>
                    @foreach($rechnung->steuerAufschluesselung() as $steuer)
                    <div class="row muted">
                        <span>MwSt {{ number_format($steuer['satz'], 0, ',', '.') }}%</span>
                        <span>{{ number_format($steuer['steuer'], 2, ',', '.') }} €</span>
                    </div>
                    @endforeach
                    <div class="divider"></div>
                    <div class="row total">
                        <span>Gesamtbetrag</span>
                        <span>{{ number_format($rechnung->bruttobetrag, 2, ',', '.') }} €</span>
                    </div>
                </div>
            </div>

            <p>
                Bitte überweisen Sie den Gesamtbetrag innerhalb von 7 Tagen auf das unten angegebene Konto
                unter Angabe der Rechnungsnummer <strong>{{ $rechnung->rechnungsnummer }}</strong> als Verwendungszweck.
            </p>

            @if($rechnung->iban)
            <div class="invoice-box">
                <strong>Bankverbindung</strong><br>
                @if($rechnung->bank_name){{ $rechnung->bank_name }}<br>@endif
                IBAN: {{ $rechnung->iban }}<br>
                @if($rechnung->bic)BIC: {{ $rechnung->bic }}<br>@endif
                Verwendungszweck: {{ $rechnung->rechnungsnummer }}
            </div>
            @endif
        </div>

        <div class="footer">
            {{ $tenant->company ?? $tenant->name }}<br>
            {{ $tenant->street }}, {{ $tenant->zip }} {{ $tenant->city }}<br>
            @if($tenant->ust_id)USt-IdNr.: {{ $tenant->ust_id }}<br>@endif
            @if($tenant->steuernummer)St-Nr.: {{ $tenant->steuernummer }}@endif
        </div>
    </div>
</body>
</html>
