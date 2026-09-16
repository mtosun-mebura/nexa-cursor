<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Factuurregister {{ $period->label }}</title>
    <style>
        @page { margin: 18px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
        .header { width: 100%; margin-bottom: 12px; }
        .header td { vertical-align: top; }
        .logo img { max-height: 40px; max-width: 140px; }
        .meta { text-align: right; font-size: 9px; color: #475569; line-height: 1.45; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .sub { color: #64748b; margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; text-align: left; padding: 4px 6px; font-size: 8px; }
        td { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; }
        .num { text-align: right; white-space: nowrap; }
        tfoot td { font-weight: bold; border-top: 2px solid #0f172a; }
        .empty { color: #64748b; padding: 16px 0; }
        .cancelled { color: #b91c1c; }
    </style>
</head>
<body>
<table class="header">
    <tr>
        <td>
            @if(!empty($logoDataUri))
                <div class="logo"><img src="{{ $logoDataUri }}" alt=""></div>
            @endif
            <h1>Factuurregister</h1>
            <p class="sub">{{ $company->name }} · {{ $period->label }} ({{ $period->start->format('d-m-Y') }} t/m {{ $period->end->format('d-m-Y') }})</p>
        </td>
        <td class="meta">
            @if(!empty($settings->company_vat_number))BTW: {{ $settings->company_vat_number }}<br>@endif
            Gegenereerd: {{ $generatedAt }}
        </td>
    </tr>
</table>

@if($invoices->isEmpty())
    <p class="empty">Geen facturen in deze periode.</p>
@else
<table>
    <thead>
        <tr>
            <th>Nummer</th>
            <th>Datum</th>
            <th>Klant</th>
            <th>Type</th>
            <th>Status</th>
            <th>Betaald</th>
            <th class="num">Netto</th>
            <th class="num">BTW</th>
            <th class="num">Totaal</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoices as $invoice)
            <tr class="{{ $invoice->status === 'cancelled' ? 'cancelled' : '' }}">
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->invoice_date?->format('d-m-Y') }}</td>
                <td>{{ $invoice->customer_name ?: '—' }}</td>
                <td>
                    @switch($invoice->module)
                        @case('taxi') Rit @break
                        @case('taxi_contract') Contract @break
                        @case('customer') Klant @break
                        @default Factuur
                    @endswitch
                </td>
                <td>{{ $invoice->status }}</td>
                <td>{{ $invoice->paid_date?->format('d-m-Y') ?: '—' }}</td>
                <td class="num">€ {{ number_format((float) $invoice->amount, 2, ',', '.') }}</td>
                <td class="num">€ {{ number_format((float) $invoice->tax_amount, 2, ',', '.') }}</td>
                <td class="num">€ {{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6">Totaal ({{ $totals['count'] }} facturen)</td>
            <td class="num">€ {{ number_format($totals['net'], 2, ',', '.') }}</td>
            <td class="num">€ {{ number_format($totals['vat'], 2, ',', '.') }}</td>
            <td class="num">€ {{ number_format($totals['gross'], 2, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>
@endif
</body>
</html>
