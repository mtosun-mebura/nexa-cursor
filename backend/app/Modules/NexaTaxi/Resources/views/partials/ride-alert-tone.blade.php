{{-- Ritgeluid bij nieuwe aanvraag, instelbaar onder chauffeur-profiel. --}}
@php
    $section = $section ?? 'all';
    $tones = \App\Modules\NexaTaxi\Support\RideAlertTone::options();
@endphp

@if(in_array($section, ['styles', 'all'], true))
<style>
    .ride-tone-picker {
        margin: 0.35rem 0 1rem;
        padding-top: 0.85rem;
        border-top: 1px solid var(--line, rgba(255,255,255,0.08));
    }
    .ride-tone-picker__label {
        margin: 0 0 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: var(--muted);
    }
    .ride-tone-picker__hint {
        margin: 0 0 0.65rem;
        font-size: 0.8125rem;
        line-height: 1.4;
        color: var(--muted);
    }
    .ride-tone-picker__options {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }
    .ride-tone-option {
        appearance: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.25rem;
        padding: 0.4rem 0.85rem;
        border-radius: 999px;
        border: 1px solid var(--line, rgba(255,255,255,0.12));
        background: var(--card-elevated, var(--card));
        color: var(--text);
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1.2;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
    }
    .ride-tone-option:focus-visible {
        outline: 2px solid var(--orange);
        outline-offset: 3px;
    }
    .ride-tone-option.is-selected,
    .ride-tone-option[aria-pressed="true"] {
        background: var(--orange);
        border-color: var(--orange);
        color: var(--accent-on, #fff);
    }
    html[data-theme="light"] .ride-tone-option {
        border-color: var(--line, rgba(15, 23, 42, 0.12));
        background: var(--card);
    }
</style>
@endif

@if(in_array($section, ['picker', 'all'], true))
<div class="ride-tone-picker" role="group" aria-label="Ritgeluid">
    <p class="ride-tone-picker__label">Ritgeluid</p>
    <p class="ride-tone-picker__hint">Kies het geluid bij een nieuwe rit. Tik om te beluisteren.</p>
    <div class="ride-tone-picker__options">
        @foreach($tones as $key => $tone)
            <button
                type="button"
                class="ride-tone-option"
                data-ride-tone="{{ $key }}"
                aria-pressed="false"
                title="{{ $tone['hint'] }}"
            >{{ $tone['label'] }}</button>
        @endforeach
    </div>
</div>
@endif
