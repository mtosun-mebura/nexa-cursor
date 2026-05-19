@php
    $branding = $branding ?? app(\App\Services\WebsiteBuilderService::class)->getSiteBranding();
    $logoHref = $logoHref ?? route('home');
    $logoAlt = $branding['site_name'] ?? config('app.name', 'Nexa');
    $logoSizePx = isset($logoSizePx)
        ? max(10, min(100, (int) $logoSizePx))
        : (int) ($branding['logo_size_px'] ?? app(\App\Services\WebsiteBuilderService::class)->resolveLogoSizePx());
    $logoImgStyle = 'height: '.$logoSizePx.'px';
    $logoImgClass = trim('w-auto max-w-[350px] object-contain '.($logoImgClassExtra ?? ''));
@endphp
<a href="{{ $logoHref }}" class="flex items-center {{ $logoLinkClass ?? '' }}" aria-label="{{ $logoAlt }}">
    @if(!empty($branding['logo_url']))
        @if(!empty($branding['logo_dark_url']))
            <img src="{{ $branding['logo_url'] }}" alt="{{ $logoAlt }}" class="fe-logo-light {{ $logoImgClass }}" style="{{ $logoImgStyle }}">
            <img src="{{ $branding['logo_dark_url'] }}" alt="{{ $logoAlt }}" class="fe-logo-dark {{ $logoImgClass }}" style="{{ $logoImgStyle }}">
        @else
            <img src="{{ $branding['logo_url'] }}" alt="{{ $logoAlt }}" class="{{ $logoImgClass }}" style="{{ $logoImgStyle }}">
        @endif
    @else
        <img src="{{ asset('images/nexa-logo.png') }}" alt="NEXA" class="{{ $logoImgClass }}" style="{{ $logoImgStyle }}">
    @endif
</a>
