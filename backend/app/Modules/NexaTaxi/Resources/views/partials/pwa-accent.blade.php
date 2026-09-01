{{-- Gedeelde themakleur (accent) voor chauffeur- en contract-app. --}}
@php
    $section = $section ?? 'all';
    $palettes = \App\Modules\NexaTaxi\Support\PwaAccent::palettes();
    $defaultAccent = \App\Modules\NexaTaxi\Support\PwaAccent::DEFAULT;
@endphp

@if(in_array($section, ['boot', 'all'], true))
<script>
(function () {
    var allowed = @json(\App\Modules\NexaTaxi\Support\PwaAccent::KEYS);
    try {
        var stored = localStorage.getItem('nexa-taxi-pwa-accent');
        var accent = allowed.indexOf(stored) >= 0 ? stored : @json($defaultAccent);
        document.documentElement.setAttribute('data-accent', accent);
    } catch (e) {
        document.documentElement.setAttribute('data-accent', @json($defaultAccent));
    }
})();
</script>
@endif

@if(in_array($section, ['styles', 'all'], true))
<style>
    html {
        --orange: #f97316;
        --orange-hover: #ea580c;
        --accent-rgb: 249, 115, 22;
        --accent-on: #ffffff;
        --accent-muted: color-mix(in srgb, var(--orange) 58%, white);
        --accent-ink: color-mix(in srgb, var(--orange) 72%, #1c1917);
        --accent-light-bg: color-mix(in srgb, var(--orange) 12%, white);
        --accent-light-border: color-mix(in srgb, var(--orange) 48%, white);
        --accent-light-ink: color-mix(in srgb, var(--orange) 62%, #431407);
    }
@foreach($palettes as $key => $palette)
    html[data-accent="{{ $key }}"] {
        --orange: {{ $palette['hex'] }};
        --orange-hover: {{ $palette['hover'] }};
        --accent-rgb: {{ $palette['rgb'] }};
        --accent-on: {{ $palette['on'] }};
    }
@endforeach
    html[data-theme="light"] {
        --accent-muted: color-mix(in srgb, var(--orange) 78%, #7c2d12);
        --accent-ink: color-mix(in srgb, var(--orange) 55%, #431407);
    }

    .pwa-accent-picker {
        margin: 0.35rem 0 1rem;
        padding-top: 0.85rem;
        border-top: 1px solid var(--line, rgba(255,255,255,0.08));
    }
    .pwa-accent-picker__label {
        margin: 0 0 0.55rem;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: var(--muted);
    }
    .pwa-accent-picker__swatches {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
    }
    .pwa-accent-swatch {
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 999px;
        border: 2px solid transparent;
        padding: 0;
        cursor: pointer;
        box-shadow: 0 0 0 1px rgba(255,255,255,0.12);
        -webkit-tap-highlight-color: transparent;
        background-clip: padding-box;
    }
    html[data-theme="light"] .pwa-accent-swatch {
        box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.12);
    }
    .pwa-accent-swatch:focus-visible {
        outline: 2px solid var(--orange);
        outline-offset: 3px;
    }
    .pwa-accent-swatch.is-selected,
    .pwa-accent-swatch[aria-pressed="true"] {
        box-shadow: 0 0 0 2px var(--bg, #121214), 0 0 0 4px var(--orange);
    }
</style>
@endif

@if(in_array($section, ['picker', 'all'], true))
<div class="pwa-accent-picker" role="group" aria-label="Themakleur">
    <p class="pwa-accent-picker__label">Themakleur</p>
    <div class="pwa-accent-picker__swatches">
        @foreach($palettes as $key => $palette)
            <button
                type="button"
                class="pwa-accent-swatch"
                data-pwa-accent="{{ $key }}"
                style="background: {{ $palette['hex'] }}"
                aria-label="{{ $palette['label'] }}"
                title="{{ $palette['label'] }}"
                aria-pressed="false"
            ></button>
        @endforeach
    </div>
</div>
@endif
