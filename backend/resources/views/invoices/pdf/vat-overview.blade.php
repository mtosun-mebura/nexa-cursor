<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>BTW-overzicht {{ $period->label }}</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        .header { width: 100%; margin-bottom: 18px; }
        .header td { vertical-align: top; }
        .logo img { max-height: 48px; max-width: 160px; }
        .meta { text-align: right; font-size: 10px; color: #475569; line-height: 1.5; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .sub { color: #64748b; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 10px; }
        td { border-bottom: 1px solid #e2e8f0; padding: 6px 8px; }
        .num { text-align: right; white-space: nowrap; }
        tfoot td { font-weight: bold; border-top: 2px solid #0f172a; }
        .note { margin-top: 18px; font-size: 10px; color: #64748b; line-height: 1.5; }
        .empty { color: #64748b; padding: 16px 0; }
    </style>
</head>
<body>
<table class="header">
    <tr>
        <td>
            @if(!empty($logoDataUri))
                <div class="logo"><img src="{{ $logoDataUri }}" alt=""></div>
            @endif
            <h1>BTW-overzicht</h1>
            <p class="sub">{{ $company->name }} · {{ $period->label }} ({{ $period->start->format('d-m-Y') }} t/m {{ $period->end->format('d-m-Y') }})</p>
        </td>
        <td class="meta">
            @if(!empty($settings->company_vat_number))BTW: {{ $settings->company_vat_number }}<br>@endif
            Gegenereerd: {{ $generatedAt }}
        </td>
    </tr>
</table>

@if($totals['count'] === 0)
    <p class="empty">Geen facturen in deze periode.</p>
@else
<table>
    <thead>
        <tr>
            <th>BTW-tarief</th>
            <th class="num">Aantal</th>
            <th class="num">Grondslag</th>
            <th class="num">BTW</th>
            <th class="num">Incl. BTW</th>
        </tr>
    </thead>
    <tbody>
        @foreach($groups as $group)
            <tr>
                <td>{{ number_format($group['rate'], 1, ',', '.') }}%</td>
                <td class="num">{{ $group['count'] }}</td>
                <td class="num">€ {{ number_format($group['net'], 2, ',', '.') }}</td>
                <td class="num">€ {{ number_format($group['vat'], 2, ',', '.') }}</td>
                <td class="num">€ {{ number_format($group['gross'], 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>Totaal</td>
            <td class="num">{{ $totals['count'] }}</td>
            <td class="num">€ {{ number_format($totals['net'], 2, ',', '.') }}</td>
            <td class="num">€ {{ number_format($totals['vat'], 2, ',', '.') }}</td>
            <td class="num">€ {{ number_format($totals['gross'], 2, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>
<p class="note">
    Overzicht op basis van factuurdatum. Conceptfacturen en geannuleerde facturen zijn niet meegenomen.
    Dit document is een hulpmiddel bij de aangifte omzetbelasting; controleer de bedragen met de administratie.
</p>
@endif
</body>
</html>
