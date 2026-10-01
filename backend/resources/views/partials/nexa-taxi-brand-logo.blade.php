{{-- NEXA | TAXI lockup: transparant light + dark (zelfde patroon als nexa-brand-logo). --}}
@include('partials.nexa-brand-logo', [
    'class' => $class ?? 'h-10 w-auto mx-auto object-contain',
    'style' => $style ?? '',
    'alt' => $alt ?? 'NEXA Taxi',
    'theme' => $theme ?? 'auto',
    'lightSrc' => $lightSrc ?? '/images/nexa-taxi-logo.png',
    'darkSrc' => $darkSrc ?? '/images/nexa-taxi-logo-dark.png',
])
