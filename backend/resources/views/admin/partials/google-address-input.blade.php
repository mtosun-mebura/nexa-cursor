@php
    $mapsKey = trim((string) ($googleMapsApiKey ?? config('maps.api_key') ?? app(\App\Services\EnvService::class)->getGoogleMapsApiKey() ?? ''));
    $addressSearchUrl = \Illuminate\Support\Facades\Route::has('nexataxi.booking.address-search')
        ? route('nexataxi.booking.address-search')
        : url('/nexa-taxi/booking/address-search');
    $inputId = $id ?? ($name . '_address_input');
    $latName = $latName ?? null;
    $lngName = $lngName ?? null;
@endphp

<div class="admin-address-autocomplete relative w-full"
     data-admin-address-autocomplete
     data-google-maps-key="{{ $mapsKey }}"
     data-address-search-url="{{ $addressSearchUrl }}">
    <input type="text"
           id="{{ $inputId }}"
           name="{{ $name }}"
           value="{{ $value ?? '' }}"
           class="kt-input w-full @error($name) border-destructive @enderror"
           @if(!empty($required)) required @endif
           maxlength="{{ $maxlength ?? 500 }}"
           placeholder="{{ $placeholder ?? 'Zoek adres...' }}"
           autocomplete="off"
           autocorrect="off"
           autocapitalize="none"
           spellcheck="false"
           data-admin-address-input>
    @if($latName)
        <input type="hidden" name="{{ $latName }}" value="{{ $latValue ?? '' }}" data-admin-address-lat>
    @endif
    @if($lngName)
        <input type="hidden" name="{{ $lngName }}" value="{{ $lngValue ?? '' }}" data-admin-address-lng>
    @endif
    <div class="admin-address-suggestions hidden" data-admin-address-suggestions role="listbox" aria-label="Adresuggesties"></div>
    @if(!empty($hint))
        <p class="text-xs text-muted-foreground mt-1">{{ $hint }}</p>
    @endif
    @error($name)
        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
    @enderror
</div>
