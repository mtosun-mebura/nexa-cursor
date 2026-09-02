{{-- Standaard NEXA Suite lockup: transparant light + dark. Root-relatief = werkt op elk host/subdomein. --}}
@php
    $nexaLogoClass = $class ?? 'h-10 w-auto mx-auto object-contain';
    $nexaLogoStyle = $style ?? '';
    $nexaLogoAlt = $alt ?? 'NEXA Suite';
    $nexaLogoTheme = $theme ?? 'auto';
    $nexaLogoLightSrc = $lightSrc ?? '/images/nexa-logo.png';
    $nexaLogoDarkSrc = $darkSrc ?? '/images/nexa-logo-dark.png';
@endphp
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
