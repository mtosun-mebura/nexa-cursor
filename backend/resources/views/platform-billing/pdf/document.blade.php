<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>{{ $issuer['invoice_title'] ?? 'SaaS-factuur' }} {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 24px 24px 56px 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; margin: 0; padding: 0; }
        .page-body { padding: 0; }
        .header { width: 100%; margin-bottom: 28px; }
        .header td { vertical-align: top; }
        .logo img { max-height: 64px; max-width: 200px; }
        .company-block { text-align: right; font-size: 11px; line-height: 1.5; color: #475569; }
        .customer-block { text-align: left; font-size: 12px; line-height: 1.5; }
        .title { font-size: 22px; font-weight: bold; margin: 0 0 6px; color: #0f172a; text-align: left; }
        .meta { margin-bottom: 24px; width: 100%; }
        .meta td { padding: 4px 0; vertical-align: top; text-align: left; }
        .meta .label { color: #64748b; width: 130px; text-align: left; }
        .meta .value { text-align: left; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 12px; table-layout: fixed; }
        table.items th { background: #f1f5f9; text-align: left; padding: 8px 10px; font-size: 11px; vertical-align: top; line-height: 1.25; }
        table.items th.num, table.items td.num { text-align: right; }
        table.items th.col-qty { width: 11%; }
        table.items th.col-money { width: 18%; }
        table.items td { border-bottom: 1px solid #e2e8f0; padding: 8px 10px; vertical-align: top; line-height: 1.45; }
        table.totals { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.totals td { padding: 5px 0; vertical-align: top; }
        table.totals .spacer { width: 55%; }
        table.totals .amount { text-align: right; white-space: nowrap; }
        table.totals tr.grand td { font-weight: bold; font-size: 13px; border-top: 2px solid #0f172a; padding-top: 10px; }
        .footer { margin-top: 24px; font-size: 10px; color: #64748b; line-height: 1.5; }
        .pdf-page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 24px 16px;
            font-size: 10px;
            color: #64748b;
            line-height: 1.5;
            text-align: left;
        }
        .paid-banner { margin: 0 0 18px; padding: 10px 14px; background: #dcfce7; border: 2px solid #16a34a; color: #14532d; font-size: 13px; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
@php
    $fmt = fn (float $n) => number_format($n, 2, ',', '.');
    $fmtSigned = function (float $n) use ($fmt): string {
        if ($n < 0) {
            return '− € '.$fmt(abs($n));
        }

        return '€ '.$fmt($n);
    };
    $taxRateLabel = 'BTW ('.(fmod(round($taxRate, 2), 1) === 0.0 ? (int) round($taxRate).'%' : number_format($taxRate, 2, ',', '.').'%').')';
    $netAmount = (float) ($grossAmount ?? $invoice->amount);
    $isPaid = $invoice->isPaid();
@endphp
<div class="page-body">
<table class="header" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="logo" width="50%" style="text-align:left">
            @if(!empty($logoDataUri))
                <img src="{{ $logoDataUri }}" alt="Nexa">
            @else
                <strong style="font-size:18px;">{{ $issuer['name'] ?? config('app.name') }}</strong>
            @endif
        </td>
        <td class="company-block" width="50%" style="text-align:right">
            <strong>{{ $issuer['name'] ?? '' }}</strong><br>
            @if(!empty($issuer['address'])){{ $issuer['address'] }}<br>@endif
            @if(!empty($issuer['postal_code']) || !empty($issuer['city']))
                {{ trim(($issuer['postal_code'] ?? '').' '.($issuer['city'] ?? '')) }}<br>
            @endif
            @if(!empty($issuer['country'])){{ $issuer['country'] }}<br>@endif
            @if(!empty($issuer['email'])){{ $issuer['email'] }}<br>@endif
            @if(!empty($issuer['phone'])){{ $issuer['phone'] }}<br>@endif
            @if(!empty($issuer['vat_number']))BTW/KvK: {{ $issuer['vat_number'] }}@endif
            @if(!empty($issuer['bank_account']))<br>IBAN: {{ $issuer['bank_account'] }}@endif
        </td>
    </tr>
</table>

<p class="title">{{ $issuer['invoice_title'] ?? 'SaaS-factuur' }}</p>

@if($isPaid)
<div class="paid-banner">BETALING VOLDAAN</div>
@endif

<table class="meta" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td width="57%" style="text-align:left">
            <table cellpadding="0" cellspacing="0">
                <tr><td class="label">Factuurnummer</td><td class="value"><strong>{{ $invoice->invoice_number }}</strong></td></tr>
                <tr><td class="label">Periode</td><td class="value">{{ $invoice->billing_period }}</td></tr>
                <tr><td class="label">Factuurdatum</td><td class="value">{{ $invoice->invoice_date?->format('d-m-Y') }}</td></tr>
                @if(!$isPaid && $invoice->due_date)
                <tr><td class="label">Vervaldatum</td><td class="value">{{ $invoice->due_date->format('d-m-Y') }}</td></tr>
                @endif
            </table>
        </td>
        <td width="43%" class="customer-block" style="text-align:left; padding-left:28px;">
            <strong>Klant</strong><br>
            {{ $recipient['name'] ?? ($invoice->company?->name ?? '') }}<br>
            @if(!empty($recipient['contact_name'])){{ $recipient['contact_name'] }}<br>@endif
            @if(!empty($recipient['address'])){{ $recipient['address'] }}<br>@endif
            @if(!empty($recipient['postal_code']) || !empty($recipient['city']))
                {{ trim(($recipient['postal_code'] ?? '').' '.($recipient['city'] ?? '')) }}<br>
            @endif
            @if(!empty($recipient['country'])){{ $recipient['country'] }}<br>@endif
            @if(!empty($recipient['email'])){{ $recipient['email'] }}@endif
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>Omschrijving</th>
            <th class="num col-qty">Aantal</th>
            <th class="num col-money">Prijs<br>excl. BTW</th>
            <th class="num col-money">Totaal<br>excl. BTW</th>
        </tr>
    </thead>
    <tbody>
        @forelse($lineItems as $item)
        @php
            $isDiscount = ($item['type'] ?? '') === 'discount';
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $lineTotal = (float) ($item['total'] ?? 0);
        @endphp
        <tr>
            <td>{!! nl2br(e($item['description'] ?? '—')) !!}</td>
            <td class="num">{{ $item['quantity'] ?? 1 }}</td>
            <td class="num">{{ $fmtSigned($unitPrice) }}</td>
            <td class="num">{{ $fmtSigned($lineTotal) }}</td>
        </tr>
        @empty
        <tr><td colspan="4">Geen regels</td></tr>
        @endforelse
    </tbody>
</table>

<table class="totals" cellpadding="0" cellspacing="0">
    <tr>
        <td class="spacer"></td>
        <td>Bedrag excl. BTW</td>
        <td class="amount">€ {{ $fmt((float) ($netAmount ?? $invoice->amount)) }}</td>
    </tr>
    <tr>
        <td class="spacer"></td>
        <td>{{ $taxRateLabel }}</td>
        <td class="amount">€ {{ $fmt((float) $invoice->tax_amount) }}</td>
    </tr>
    <tr class="grand">
        <td class="spacer"></td>
        <td>Totaalbedrag incl. BTW</td>
        <td class="amount">€ {{ $fmt((float) $invoice->total_amount) }}</td>
    </tr>
</table>

@if(!empty($issuer['footer_text']))
<p class="footer">{{ $issuer['footer_text'] }}</p>
@endif
</div>

@if(!empty($paymentTermsText))
<div class="pdf-page-footer">{{ $paymentTermsText }}</div>
@endif
</body>
</html>
