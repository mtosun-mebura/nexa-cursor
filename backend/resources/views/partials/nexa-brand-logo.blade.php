{{-- Standaard NEXA Suite lockup: transparant light + dark. Root-relatief = werkt op elk host/subdomein. --}}
@php
    $nexaLogoClass = $class ?? 'h-10 w-auto mx-auto object-contain';
    $nexaLogoStyle = $style ?? '';
    $nexaLogoAlt = $alt ?? 'NEXA Suite';
    $nexaLogoTheme = $theme ?? 'auto';
    $nexaLogoLightSrc = $lightSrc ?? '/images/nexa-logo.png';
    $nexaLogoDarkSrc = $darkSrc ?? '/images/nexa-logo-dark.png';
@endphp
@once
<style>
    .nexa-brand-lockup { display: inline-flex; align-items: center; max-width: 100%; }
    .nexa-brand-lockup > .logo-light { display: block; }
    .nexa-brand-lockup > .logo-dark { display: none; }
    html.dark .nexa-brand-lockup > .logo-light,
    body.dark .nexa-brand-lockup > .logo-light,
    .dark .nexa-brand-lockup > .logo-light { display: none; }
    html.dark .nexa-brand-lockup > .logo-dark,
    body.dark .nexa-brand-lockup > .logo-dark,
    .dark .nexa-brand-lockup > .logo-dark { display: block; }
</style>
@endonce
<span class="nexa-brand-lockup">
@if($nexaLogoTheme === 'dark')
    <img
        src="{{ $nexaLogoDarkSrc }}"
        alt="{{ $nexaLogoAlt }}"
        class="{{ $nexaLogoClass }}"
        @if($nexaLogoStyle !== '') style="{{ $nexaLogoStyle }}" @endif
        width="140"
        height="40"
        decoding="async"
    />
@elseif($nexaLogoTheme === 'light')
    <img
        src="{{ $nexaLogoLightSrc }}"
        alt="{{ $nexaLogoAlt }}"
        class="{{ $nexaLogoClass }}"
        @if($nexaLogoStyle !== '') style="{{ $nexaLogoStyle }}" @endif
        width="140"
        height="40"
        decoding="async"
    />
@else
    <img
        src="{{ $nexaLogoLightSrc }}"
        alt="{{ $nexaLogoAlt }}"
        class="logo-light dark:hidden {{ $nexaLogoClass }}"
        @if($nexaLogoStyle !== '') style="{{ $nexaLogoStyle }}" @endif
        width="140"
        height="40"
        decoding="async"
    />
    <img
        src="{{ $nexaLogoDarkSrc }}"
        alt="{{ $nexaLogoAlt }}"
        class="logo-dark hidden dark:block {{ $nexaLogoClass }}"
        @if($nexaLogoStyle !== '') style="{{ $nexaLogoStyle }}" @endif
        width="140"
        height="40"
        decoding="async"
    />
@endif
</span>
