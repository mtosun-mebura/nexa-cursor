@php
    $kind = $kind ?? 'invoice';
    $mail = $mail ?? null;
    $banner = match ($kind) {
        'first' => '1e aanmaning',
        'second' => '2e aanmaning',
        default => null,
    };
    $bannerClass = $kind === 'second'
        ? 'nexa-werkwijze-banner nexa-werkwijze-banner--second'
        : 'nexa-werkwijze-banner nexa-werkwijze-banner--first';
@endphp

<div class="nexa-werkwijze-doc">
    @if(is_array($mail))
        <div class="nexa-werkwijze-mail">
            <div class="nexa-werkwijze-mail__meta">
                <div><span class="nexa-werkwijze-mail__label">Van</span> Nexa Suite</div>
                <div><span class="nexa-werkwijze-mail__label">Aan</span> {{ $preview['recipient']['email'] ?? 'facturatie@voorbeeld.taxi' }}</div>
                <div><span class="nexa-werkwijze-mail__label">Onderwerp</span> {{ $mail['subject'] }}</div>
            </div>
            <div class="nexa-werkwijze-mail__body">{{ $mail['body'] }}</div>
            <div class="nexa-werkwijze-mail__attach">
                Bijlage: saas-factuur-{{ preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($preview['invoice_number'] ?? 'voorbeeld')) }}.pdf
            </div>
        </div>
    @endif
    <div class="nexa-werkwijze-paper">
        @if($banner)
            <div class="{{ $bannerClass }}">{{ $banner }}</div>
        @endif
        @include('admin.platform-billing.tenants.partials.invoice-preview', ['preview' => $preview])
    </div>
</div>
