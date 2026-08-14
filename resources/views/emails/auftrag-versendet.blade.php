<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auftrag {{ $auftrag->auftragsnummer }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #01696f; color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; opacity: 0.85; font-size: 14px; }
        .content { padding: 30px; }
        .greeting { font-size: 16px; margin-bottom: 20px; }
        .invoice-box { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 20px; margin: 20px 0; }
        .invoice-box table { width: 100%; border-collapse: collapse; }
        .invoice-box th { text-align: left; padding: 8px 0; border-bottom: 2px solid #dee2e6; font-size: 12px; color: #666; text-transform: uppercase; }
        .invoice-box td { padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .invoice-box td:last-child { text-align: right; font-weight: 500; }
        .totals { margin-top: 15px; text-align: right; }
        .totals .row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 14px; }
        .totals .row.muted { color: #666; }
        .totals .divider { height: 1px; background: #dee2e6; margin: 8px 0; }
        .totals .row.total { font-weight: 700; font-size: 16px; color: #01696f; }
        .footer { padding: 20px 30px; background: #f8f9fa; border-top: 1px solid #e9ecef; font-size: 12px; color: #666; text-align: center; }
        .note { font-size: 13px; color: #666; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Auftrag</h1>
            <p>{{ $auftrag->auftragsnummer }}</p>
        </div>

        <div class="content">
            <div class="greeting">
                Sehr geehrte/r {{ $auftrag->kunde_name ?? $auftrag->kunde_firma ?? 'Kunde' }},
            </div>

            <p>
                anbei erhalten Sie unseren Auftrag <strong>{{ $auftrag->auftragsnummer }}</strong>.
            </p>

            <div class="invoice-box">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Beschreibung</th>
                            <th style="text-align:right">Menge</th>
                            <th style="text-align:right">Netto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auftrag->positions as $pos)
                        <tr>
                            <td>{{ $pos->position }}</td>
                            <td>{{ $pos->beschreibung }}</td>
                            <td style="text-align:right">{{ number_format($pos->menge, 2, ',', '.') }} {{ $pos->einheit }}</td>
                            <td style="text-align:right">{{ number_format($pos->menge * $pos->einzelpreis * (1 - $pos->rabatt / 100), 2, ',', '.') }} €</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="totals">
                    <div class="row muted">
                        <span>Nettobetrag</span>
                        <span>{{ number_format($auftrag->nettobetrag(), 2, ',', '.') }} €</span>
                    </div>
                    @foreach($auftrag->steuerAufschluesselung() as $steuer)
                    <div class="row muted">
                        <span>MwSt {{ number_format($steuer['satz'], 0, ',', '.') }}%</span>
                        <span>{{ number_format($steuer['steuer'], 2, ',', '.') }} €</span>
                    </div>
                    @endforeach
                    <div class="divider"></div>
                    <div class="row total">
                        <span>Bruttobetrag</span>
                        <span>{{ number_format($auftrag->bruttobetrag(), 2, ',', '.') }} €</span>
                    </div>
                </div>
            </div>

            @if($auftrag->notizen)
            <div class="note">{{ $auftrag->notizen }}</div>
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
