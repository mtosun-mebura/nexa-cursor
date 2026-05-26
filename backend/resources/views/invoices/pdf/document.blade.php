<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Factuur {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 24px 24px 56px 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; margin: 0; padding: 0; }
        .page-body { padding: 0; }
        .header { width: 100%; margin-bottom: 28px; }
        .header td { vertical-align: top; }
        .logo img { max-height: 64px; max-width: 200px; }
        .company-block { text-align: right; font-size: 11px; line-height: 1.5; color: #475569; }
        .customer-block { text-align: right; font-size: 12px; line-height: 1.5; }
        .title { font-size: 22px; font-weight: bold; margin: 0 0 6px; color: #0f172a; text-align: left; }
        .meta { margin-bottom: 24px; width: 100%; }
        .meta td { padding: 4px 0; vertical-align: top; text-align: left; }
        .meta .label { color: #64748b; width: 130px; text-align: left; }
        .meta .value { text-align: left; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.items th { background: #f1f5f9; text-align: left; padding: 8px 10px; font-size: 11px; }
        table.items th.num { text-align: right; }
        table.items td { border-bottom: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; }
        table.items td.num-euro { text-align: right; width: 14px; white-space: nowrap; }
        table.items td.num-amount { text-align: right; width: 72px; white-space: nowrap; }
        table.totals { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.totals td { padding: 5px 0; vertical-align: top; }
        table.totals .totals-label { text-align: left; width: auto; }
        table.totals .totals-spacer { width: 55%; }
        table.totals .totals-euro { text-align: right; width: 16px; white-space: nowrap; }
        table.totals .totals-amount { text-align: right; width: 76px; white-space: nowrap; }
        table.totals tr.grand td { font-weight: bold; font-size: 13px; border-top: 2px solid #0f172a; padding-top: 10px; }
        .footer { margin-top: 32px; font-size: 10px; color: #64748b; line-height: 1.5; text-align: left; }
        .pdf-page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 0 24px 16px;
            font-size: 10px;
            color: #94a3b8;
            line-height: 1.5;
            text-align: left;
        }
    </style>
</head>
<body>
@php
    $taxRate = (float) ($details['tax_rate'] ?? 21);
    $taxRateLabel = 'BTW ('.(fmod(round($taxRate, 2), 1) === 0.0 ? (int) round($taxRate).'%' : number_format($taxRate, 2, ',', '.').'%').')';
    $fmt = fn (float $n) => number_format($n, 2, ',', '.');
    $paymentTermsDays = max(1, (int) ($paymentTermsDays ?? 30));
    $paymentTermsLabel = $paymentTermsDays === 1 ? 'dag' : 'dagen';
@endphp
<div class="page-body">
<table class="header" cellpadding="0" cellspacing="0">
    <tr>
        <td class="logo" width="50%" style="text-align:left">
            @if($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="Logo">
            @else
                <strong style="font-size:18px;">{{ $details['name'] ?? ($company->name ?? 'Factuur') }}</strong>
            @endif
        </td>
        <td class="company-block" width="50%" style="text-align:right">
            <strong>{{ $details['name'] ?? ($company->name ?? '') }}</strong><br>
            @if(!empty($details['address'])){{ $details['address'] }}<br>@endif
            @if(!empty($details['postal_code']) || !empty($details['city']))
                {{ trim(($details['postal_code'] ?? '').' '.($details['city'] ?? '')) }}<br>
            @endif
            @if(!empty($details['country'])){{ $details['country'] }}<br>@endif
            @if(!empty($details['email'])){{ $details['email'] }}<br>@endif
            @if(!empty($details['phone'])){{ $details['phone'] }}<br>@endif
            @if(!empty($details['vat_number']))BTW/KvK: {{ $details['vat_number'] }}@endif
        </td>
    </tr>
</table>

<p class="title">Factuur</p>

<table class="meta" cellpadding="0" cellspacing="0">
    <tr>
        <td width="50%" style="text-align:left">
            <table cellpadding="0" cellspacing="0">
                <tr><td class="label">Factuurnummer</td><td class="value"><strong>{{ $invoice->invoice_number }}</strong></td></tr>
                <tr><td class="label">Factuurdatum</td><td class="value">{{ $invoice->invoice_date?->format('d-m-Y') }}</td></tr>
                @if($invoice->due_date)
                <tr><td class="label">Vervaldatum</td><td class="value">{{ $invoice->due_date->format('d-m-Y') }}</td></tr>
                @endif
            </table>
        </td>
        <td width="50%" class="customer-block" style="text-align:right">
            @if($invoice->customer_name || $invoice->customer_email)
            <strong>Klant</strong><br>
            @if($invoice->customer_name){{ $invoice->customer_name }}<br>@endif
            @if($invoice->customer_email){{ $invoice->customer_email }}@endif
            @endif
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>Omschrijving</th>
            <th class="num" style="text-align:right">Aantal</th>
            <th class="num" colspan="2" style="text-align:right">Prijs excl. BTW</th>
            <th class="num" colspan="2" style="text-align:right">Totaal excl. BTW</th>
        </tr>
    </thead>
    <tbody>
        @forelse($lineItems as $item)
        <tr>
            <td>{{ $item['description'] ?? '—' }}</td>
            <td class="num-amount">{{ $item['quantity'] ?? 1 }}</td>
            <td class="num-euro">€</td>
            <td class="num-amount">{{ $fmt((float) ($item['unit_price'] ?? 0)) }}</td>
            <td class="num-euro">€</td>
            <td class="num-amount">{{ $fmt((float) ($item['total'] ?? 0)) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6">Geen regels</td>
        </tr>
        @endforelse
    </tbody>
</table>

<table class="totals" cellpadding="0" cellspacing="0">
    <tr>
        <td class="totals-spacer"></td>
        <td class="totals-label">Bedrag excl. BTW</td>
        <td class="totals-euro">€</td>
        <td class="totals-amount">{{ $fmt((float) $invoice->amount) }}</td>
    </tr>
    <tr>
        <td class="totals-spacer"></td>
        <td class="totals-label">{{ $taxRateLabel }}</td>
        <td class="totals-euro">€</td>
        <td class="totals-amount">{{ $fmt((float) $invoice->tax_amount) }}</td>
    </tr>
    <tr class="grand">
        <td class="totals-spacer"></td>
        <td class="totals-label">Totaalbedrag</td>
        <td class="totals-euro">€</td>
        <td class="totals-amount">{{ $fmt((float) $invoice->total_amount) }}</td>
    </tr>
</table>

@if(!empty($details['footer_text']))
<p class="footer">{{ $details['footer_text'] }}</p>
@endif
</div>

<div class="pdf-page-footer">
    Betaaltermijn: deze factuur dient binnen {{ $paymentTermsDays }} {{ $paymentTermsLabel }} na factuurdatum te worden betaald.
</div>
</body>
</html>
