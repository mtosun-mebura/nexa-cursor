@php
    $alt = is_string($alt ?? null) ? $alt : '';
@endphp
<div class="carousel-slide-alt-input-wrap relative flex-1 min-w-0">
    <input type="text"
           name="home_sections[{{ $sectionKey }}][items][{{ $idx }}][alt]"
           value="{{ $alt }}"
           placeholder="Bijv. Comfortabel, betrouwbaar en altijd op tijd"
           class="kt-input carousel-slide-alt-input w-full min-w-0 text-sm pe-9">
    <button type="button"
            class="carousel-slide-alt-clear kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost absolute end-1 top-1/2 z-[2] -translate-y-1/2 {{ trim($alt) !== '' ? '' : 'hidden' }}"
            aria-label="Tekst wissen"
            tabindex="-1"
            title="Tekst wissen">
        <i class="ki-filled ki-cross text-sm"></i>
    </button>
</div>
