@php
    $value = $value ?? '';
    $inputId = $inputId ?? 'vehicle-license-plate';
    $hasError = $errors->has('license_plate');
@endphp
{{-- Geel NL-kenteken (zelfde sidecode-logica als mobile-app/src/dutchPlate.ts). Opslag: uppercase mét streepjes. --}}
<div class="nl-plate-field @error('license_plate') nl-plate-field--invalid @enderror">
    {{-- class "relative": form-validation.js gebruikt dit als wrapper i.p.v. de input te verplaatsen --}}
    <div class="nl-plate relative">
        <span class="nl-plate__eu" aria-hidden="true">
            <span class="nl-plate__stars">★★★</span>
            <span class="nl-plate__nl">NL</span>
        </span>
        <input type="text"
               id="{{ $inputId }}"
               name="license_plate"
               class="nl-plate__input @error('license_plate') border-destructive @enderror"
               value="{{ $value }}"
               placeholder="AB-12-CD"
               maxlength="8"
               autocomplete="off"
               spellcheck="false"
               autocapitalize="characters"
               required
               data-dutch-plate="true"
               aria-label="{{ __('Kenteken') }}"
               aria-invalid="{{ $hasError ? 'true' : 'false' }}"
               @error('license_plate') data-server-error="1" @enderror>
    </div>
</div>
<div class="field-feedback text-sm mt-1" data-field="license_plate" aria-live="polite"></div>
@error('license_plate')
    <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error"
       data-laravel-field="license_plate"
       data-laravel-message="{{ $message }}"
       data-validation-error="1"
       data-validation-error-for="license_plate">{{ $message }}</p>
@enderror

@once
@push('styles')
<style>
/* NL kenteken-invoer — vaste geel/blauw (leesbaar in light én dark admin) */
.nl-plate-field {
    display: inline-block;
    max-width: 100%;
}
.nl-plate {
    display: inline-flex;
    align-items: stretch;
    height: 3rem;
    max-width: 100%;
    border-radius: 0.5rem;
    overflow: hidden;
    background: #F5C400;
    border: 1px solid #1A1A1A;
    box-sizing: border-box;
    position: relative;
}
.nl-plate > .validation-icon-wrapper {
    display: none !important;
}
.nl-plate-field--invalid .nl-plate,
.nl-plate:has(.nl-plate__input[aria-invalid="true"]) {
    border-color: var(--destructive, #dc2626);
    box-shadow: 0 0 0 1px var(--destructive, #dc2626);
}
.nl-plate__eu {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.05rem;
    width: 2.25rem;
    flex-shrink: 0;
    background: #003399;
    color: #fff;
    line-height: 1;
    user-select: none;
}
.nl-plate__stars {
    color: #F5C400;
    font-size: 0.5rem;
    letter-spacing: 0.05em;
}
.nl-plate__nl {
    font-size: 0.8125rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: #fff;
}
.nl-plate__input {
    flex: 1 1 auto;
    min-width: 9rem;
    width: 11rem;
    max-width: 100%;
    border: 0 !important;
    outline: none !important;
    box-shadow: none !important;
    background: transparent !important;
    color: #1A1A1A !important;
    font-size: 1.25rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-align: center;
    text-transform: uppercase;
    padding: 0 0.5rem !important;
    line-height: 1;
    caret-color: #1A1A1A;
}
.nl-plate__input::placeholder {
    color: rgba(26, 26, 26, 0.35);
    font-weight: 700;
    letter-spacing: 0.06em;
}
.nl-plate__input:focus {
    outline: none !important;
    box-shadow: none !important;
}
.nl-plate:focus-within {
    outline: 2px solid color-mix(in srgb, #003399 55%, transparent);
    outline-offset: 2px;
}
</style>
@endpush
@push('scripts')
<script src="{{ asset('assets/js/dutch-plate.js') }}"></script>
@endpush
@endonce
