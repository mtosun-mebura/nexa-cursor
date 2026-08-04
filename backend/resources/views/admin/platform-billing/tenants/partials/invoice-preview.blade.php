@php
    $fmt = fn (float $n) => number_format($n, 2, ',', '.');
    $fmtSigned = function (float $n) use ($fmt): string {
        if ($n < 0) {
            return '− € '.$fmt(abs($n));
        }

        return '€ '.$fmt($n);
    };
    $taxRate = (float) ($preview['tax_rate'] ?? 21);
    $taxRateLabel = 'BTW ('.(fmod(round($taxRate, 2), 1) === 0.0 ? (int) round($taxRate).'%' : number_format($taxRate, 2, ',', '.').'%').')';
    $issuer = $preview['issuer'] ?? [];
    $recipient = $preview['recipient'] ?? [];
    $netAmount = (float) ($preview['amount'] ?? 0);
    $invoiceTitle = trim((string) ($issuer['invoice_title'] ?? 'SaaS-factuur'));
    $logo = \App\Support\AdminLogo::displayUrls();
@endphp

<div class="platform-billing-invoice-preview min-w-0 max-w-full">
    <div class="platform-billing-invoice-preview__header">
        <div class="platform-billing-invoice-preview__logo">
            @if($logo['source'] === 'default')
                <img src="{{ $logo['light_url'] }}" alt="{{ $logo['alt'] }}" class="platform-billing-invoice-preview__logo-image">
            @else
                <img src="{{ $logo['light_url'] }}" alt="{{ $logo['alt'] }}" class="platform-billing-invoice-preview__logo-image logo-light dark:hidden">
                <img src="{{ $logo['dark_url'] }}" alt="{{ $logo['alt'] }}" class="platform-billing-invoice-preview__logo-image logo-dark hidden dark:block">
            @endif
        </div>
        <div class="platform-billing-invoice-preview__issuer">
            @if(!empty($issuer['name']))<strong>{{ $issuer['name'] }}</strong><br>@endif
            @if(!empty($issuer['address'])){{ $issuer['address'] }}<br>@endif
            @if(!empty($issuer['postal_code']) || !empty($issuer['city']))
                {{ trim(($issuer['postal_code'] ?? '').' '.($issuer['city'] ?? '')) }}<br>
            @endif
            @if(!empty($issuer['country'])){{ $issuer['country'] }}<br>@endif
            @if(!empty($issuer['email'])){{ $issuer['email'] }}<br>@endif
            @if(!empty($issuer['phone'])){{ $issuer['phone'] }}<br>@endif
            @if(!empty($issuer['vat_number']))BTW/KvK: {{ $issuer['vat_number'] }}@endif
        </div>
    </div>

    <h2 class="platform-billing-invoice-preview__title">{{ $invoiceTitle }}</h2>

    <div class="platform-billing-invoice-preview__meta">
        <div class="platform-billing-invoice-preview__meta-left">
            <div class="platform-billing-invoice-preview__meta-row">
                <span class="platform-billing-invoice-preview__meta-label">Factuurnummer</span>
                <span class="platform-billing-invoice-preview__meta-value"><strong>{{ $preview['invoice_number'] ?? '—' }}</strong></span>
            </div>
            <div class="platform-billing-invoice-preview__meta-row">
                <span class="platform-billing-invoice-preview__meta-label">Factuurdatum</span>
                <span class="platform-billing-invoice-preview__meta-value">{{ isset($preview['invoice_date']) ? \Illuminate\Support\Carbon::parse($preview['invoice_date'])->format('d-m-Y') : '—' }}</span>
            </div>
            <div class="platform-billing-invoice-preview__meta-row">
                <span class="platform-billing-invoice-preview__meta-label">Vervaldatum</span>
                <span class="platform-billing-invoice-preview__meta-value">{{ isset($preview['due_date']) ? \Illuminate\Support\Carbon::parse($preview['due_date'])->format('d-m-Y') : '—' }}</span>
            </div>
            <div class="platform-billing-invoice-preview__meta-row">
                <span class="platform-billing-invoice-preview__meta-label">Periode</span>
                <span class="platform-billing-invoice-preview__meta-value">{{ $preview['billing_period'] ?? '—' }}</span>
            </div>
        </div>
        <div class="platform-billing-invoice-preview__meta-right">
            <strong>Klant</strong><br>
            <span class="platform-billing-invoice-preview__customer-name">{{ $recipient['name'] ?? '—' }}</span>
            @if(!empty($recipient['contact_name']))<br>{{ $recipient['contact_name'] }}@endif
            @if(!empty($recipient['address']))<br>{{ $recipient['address'] }}@endif
            @if(!empty($recipient['postal_code']) || !empty($recipient['city']))
                <br>{{ trim(($recipient['postal_code'] ?? '').' '.($recipient['city'] ?? '')) }}
            @endif
            @if(!empty($recipient['email']))<br>{{ $recipient['email'] }}@endif
        </div>
    </div>

    @if(($preview['extra_lines_one_time'] ?? false) && !empty($preview['extra_lines_applied_at']))
        <div class="kt-alert kt-alert-warning py-3 mb-5" role="status">
            Eenmalige extra regels zijn al op een eerdere factuur toegepast ({{ $preview['extra_lines_applied_at']->format('d-m-Y H:i') }}).
            Pas de regelselectie aan om opnieuw eenmalige regels te plannen.
        </div>
    @elseif(($preview['extra_lines_one_time'] ?? false) && ($preview['includes_extra_lines'] ?? false))
        <div class="kt-alert kt-alert-info py-3 mb-5" role="status">
            Extra regels staan op <strong>eenmalig</strong> en worden meegenomen op de volgende factuur.
        </div>
    @endif

    <div class="platform-billing-invoice-preview__table-wrap min-w-0">
        <table class="kt-table kt-table-border align-middle text-sm w-full admin-keep-table-layout platform-billing-invoice-preview__table">
            <colgroup>
                <col class="platform-billing-invoice-preview__col-description">
                <col class="platform-billing-invoice-preview__col-qty">
                <col class="platform-billing-invoice-preview__col-money">
                <col class="platform-billing-invoice-preview__col-money">
            </colgroup>
            <thead>
                <tr>
                    <th class="text-secondary-foreground font-normal text-left platform-billing-invoice-preview__description">Omschrijving</th>
                    <th class="text-secondary-foreground font-normal text-right platform-billing-invoice-preview__num">Aantal</th>
                    <th class="text-secondary-foreground font-normal text-right platform-billing-invoice-preview__money-header">Prijs<br>excl. BTW</th>
                    <th class="text-secondary-foreground font-normal text-right platform-billing-invoice-preview__money-header">Totaal<br>excl. BTW</th>
                </tr>
            </thead>
            <tbody>
            @forelse($preview['line_items'] ?? [] as $item)
                @php
                    $isDiscount = ($item['type'] ?? '') === 'discount';
                    $unitPrice = (float) ($item['unit_price'] ?? 0);
                    $lineTotal = (float) ($item['total'] ?? 0);
                @endphp
                <tr @class(['platform-billing-invoice-preview__row--discount' => $isDiscount])>
                    <td class="platform-billing-invoice-preview__description @if($isDiscount) text-destructive @endif">{{ $item['description'] ?? '—' }}</td>
                    <td class="text-right tabular-nums platform-billing-invoice-preview__num">{{ $item['quantity'] ?? 1 }}</td>
                    <td class="text-right tabular-nums platform-billing-invoice-preview__money @if($isDiscount) text-destructive @endif">{{ $fmtSigned($unitPrice) }}</td>
                    <td class="text-right tabular-nums platform-billing-invoice-preview__money @if($isDiscount) text-destructive @endif">{{ $fmtSigned($lineTotal) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-secondary-foreground">Geen factuurregels</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="platform-billing-invoice-preview__totals">
        <div class="platform-billing-invoice-preview__totals-inner">
            <div class="platform-billing-invoice-preview__totals-row">
                <span class="text-secondary-foreground">Bedrag excl. BTW</span>
                <span class="tabular-nums whitespace-nowrap">€ {{ $fmt($netAmount) }}</span>
            </div>
            <div class="platform-billing-invoice-preview__totals-row">
                <span class="text-secondary-foreground">{{ $taxRateLabel }}</span>
                <span class="tabular-nums whitespace-nowrap">€ {{ $fmt((float) ($preview['tax_amount'] ?? 0)) }}</span>
            </div>
            <div class="platform-billing-invoice-preview__totals-row platform-billing-invoice-preview__totals-row--grand">
                <span>Totaalbedrag incl. BTW</span>
                <span class="tabular-nums whitespace-nowrap">€ {{ $fmt((float) ($preview['total_amount'] ?? 0)) }}</span>
            </div>
        </div>
    </div>

    @if(!empty($preview['notes']))
        <div class="platform-billing-invoice-preview__notes">
            <p class="font-medium mb-2">Notities</p>
            <p class="text-secondary-foreground whitespace-pre-line break-words">{{ $preview['notes'] }}</p>
        </div>
    @endif

    @if(!empty($preview['payment_terms_text']))
        <div class="platform-billing-invoice-preview__payment-terms">
            {{ $preview['payment_terms_text'] }}
        </div>
    @endif
</div>
