@php
    $branding = $branding ?? app(\App\Services\WebsiteBuilderService::class)->getSiteBranding();
    $websiteBuilder = app(\App\Services\WebsiteBuilderService::class);
    $logoHrefRaw = $logoHref ?? route('home');
    $logoHref = ($logoHrefTenantAware ?? true)
        ? \App\Support\Tenancy\TenantFrontendUrl::for($logoHrefRaw)
        : $logoHrefRaw;
    $logoAlt = $branding['site_name'] ?? config('app.name', 'Nexa');
    $logoSizePx = isset($logoSizePx)
        ? max(10, min(100, (int) $logoSizePx))
        : (int) ($branding['logo_size_px'] ?? $websiteBuilder->resolveLogoSizePx());
    $logoImgStyle = '--logo-h-max: '.$logoSizePx.'px; height: clamp(1.5rem, 4.5vw + 0.625rem, var(--logo-h-max)); width: auto; max-width: min(72vw, 20rem, calc(var(--logo-h-max) * 8));';
    $logoImgClass = trim('fe-brand-logo-img w-auto object-contain '.($logoImgClassExtra ?? ''));
    $logoUrl = ! empty($branding['logo_url']) ? $websiteBuilder->storageUrlToDisplayUrl($branding['logo_url']) : '';
    $logoDarkUrl = ! empty($branding['logo_dark_url']) ? $websiteBuilder->storageUrlToDisplayUrl($branding['logo_dark_url']) : '';
@endphp
<a href="{{ $logoHref }}" class="flex items-center {{ $logoLinkClass ?? '' }}" aria-label="{{ $logoAlt }}">
    @if($logoUrl !== '')
        @if($logoDarkUrl !== '')
            <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" class="fe-logo-light {{ $logoImgClass }}" style="{{ $logoImgStyle }}">
            <img src="{{ $logoDarkUrl }}" alt="{{ $logoAlt }}" class="fe-logo-dark {{ $logoImgClass }}" style="{{ $logoImgStyle }}">
        @else
            <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" class="{{ $logoImgClass }}" style="{{ $logoImgStyle }}">
        @endif
    @else
        <img src="{{ asset('images/nexa-logo.png') }}" alt="NEXA" class="{{ $logoImgClass }}" style="{{ $logoImgStyle }}">
    @endif
</a>
