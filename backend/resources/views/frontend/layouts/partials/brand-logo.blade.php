@php
    $branding = $branding ?? app(\App\Services\WebsiteBuilderService::class)->getSiteBranding();
    $websiteBuilder = app(\App\Services\WebsiteBuilderService::class);
    $logoHrefRaw = $logoHref ?? route('home');
    $logoHref = ($logoHrefTenantAware ?? true)
        ? \App\Support\Tenancy\TenantFrontendUrl::for($logoHrefRaw)
        : $logoHrefRaw;
    $logoAlt = $branding['logo_alt'] ?? $branding['site_name'] ?? config('app.name', 'Nexa');
    $logoSizePx = isset($logoSizePx)
        ? max(10, min(100, (int) $logoSizePx))
        : (int) ($branding['logo_size_px'] ?? $websiteBuilder->resolveLogoSizePx());
    $logoPaddingLeftPx = isset($logoPaddingLeftPx)
        ? max(0, min(48, (int) $logoPaddingLeftPx))
        : (int) ($branding['logo_padding_left_px'] ?? $websiteBuilder->resolveLogoPaddingLeftPx());
    // Keep logo always fitting in header on mobile; leave space for header buttons.
    $logoImgStyle = '--logo-h-max: '.$logoSizePx.'px; height: clamp(1.375rem, 4vw + 0.5rem, var(--logo-h-max)); width: auto; max-width: min(58vw, 18rem, calc(var(--logo-h-max) * 7));';
    if ($logoPaddingLeftPx > 0) {
        $logoImgStyle .= ' margin-left: '.$logoPaddingLeftPx.'px;';
    }
    $logoInHeader = ($logoPlacement ?? null) === 'header';
    $logoImgClass = trim('fe-brand-logo-img w-auto object-contain '.($logoInHeader ? 'fe-brand-logo-img--header ' : '').($logoImgClassExtra ?? ''));
    $logoUrl = ! empty($branding['logo_url']) ? $websiteBuilder->storageUrlToDisplayUrl($branding['logo_url']) : '';
    $logoDarkUrl = ! empty($branding['logo_dark_url']) ? $websiteBuilder->storageUrlToDisplayUrl($branding['logo_dark_url']) : '';
@endphp
@if($logoInHeader)
    @once
        <style>
            /* Mobiele header: iets hoger + groter logo zodat wapen/tekst leesbaar blijft. */
            @media (max-width: 767.98px) {
                .fe-site-header-bar.h-16 {
                    height: 4.75rem; /* ~76px */
                }
                .fe-brand-logo-img.fe-brand-logo-img--header {
                    height: clamp(3.5rem, 11vw + 1.5rem, 4.35rem) !important;
                    max-width: min(68vw, 16rem) !important;
                }
            }
        </style>
    @endonce
@endif
<a href="{{ $logoHref }}" class="flex items-center {{ $logoLinkClass ?? '' }}" aria-label="{{ $logoAlt }}">
    @if($logoUrl !== '')
        @if($logoDarkUrl !== '')
            <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" class="fe-logo-light {{ $logoImgClass }}" style="{{ $logoImgStyle }}">
            <img src="{{ $logoDarkUrl }}" alt="{{ $logoAlt }}" class="fe-logo-dark {{ $logoImgClass }}" style="{{ $logoImgStyle }}">
        @else
            <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" class="{{ $logoImgClass }}" style="{{ $logoImgStyle }}">
        @endif
    @else
        @include('partials.nexa-brand-logo', [
            'class' => $logoImgClass,
            'style' => $logoImgStyle,
            'alt' => 'NEXA Suite',
        ])
    @endif
</a>
