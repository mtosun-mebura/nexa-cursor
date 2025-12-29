@extends('admin.layouts.app')

@section('title', 'Nieuwe Vestiging')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuwe Vestiging
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.companies.locations.store', $company) }}" method="POST">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">
            @if($errors->any())
                <div class="kt-alert kt-alert-danger mb-5">
                    <i class="ki-filled ki-information-5 me-2"></i>
                    <div>
                        <strong>Er zijn fouten opgetreden:</strong>
                        <ul class="mb-0 mt-2 list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Vestiging Informatie -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Vestiging Informatie
                    </h3>
                    <div class="flex items-center gap-2">
                        <label class="kt-label">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
                                   name="is_main" 
                                   value="1"
                                   {{ old('is_main') ? 'checked' : '' }}/>
                            Hoofdkantoor
                        </label>
                        <span class="text-muted-foreground">|</span>
                        <label class="kt-label" for="is_active">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
                                   id="is_active"
                                   name="is_active" 
                                   value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}/>
                            Actief
                        </label>
                    </div>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Naam *
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="text" 
                                       class="kt-input @error('name') border-destructive @enderror" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       required>
                                @error('name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Postcode *
                            </td>
                            <td>
                                <input type="text" 
                                       id="postal_code"
                                       class="kt-input @error('postal_code') border-destructive @enderror" 
                                       name="postal_code" 
                                       value="{{ old('postal_code') }}"
                                       pattern="[1-9][0-9]{3}\s?[A-Za-z]{2}"
                                       placeholder="1234AB"
                                       maxlength="8"
                                       style="text-transform: uppercase; width: 12ch;"
                                       required>
                                <div class="text-xs text-muted-foreground mt-1">Nederlandse postcode (bijv. 1234AB)</div>
                                @error('postal_code')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Huisnummer *
                            </td>
                            <td>
                                <input type="text" 
                                       id="house_number"
                                       class="kt-input @error('house_number') border-destructive @enderror" 
                                       name="house_number" 
                                       value="{{ old('house_number') }}"
                                       style="width: 12ch;"
                                       required>
                                @error('house_number')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Adres
                            </td>
                            <td class="w-full">
                                <div class="flex gap-5 items-start">
                                    <div class="flex-1 flex flex-col gap-3">
                                        <div>
                                            <label class="text-xs text-muted-foreground mb-1 block">Straat</label>
                                            <input type="text" 
                                                   id="street"
                                                   class="kt-input @error('street') border-destructive @enderror" 
                                                   name="street" 
                                                   value="{{ old('street') }}"
                                                   readonly>
                                            @error('street')
                                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="text-xs text-muted-foreground mb-1 block">Plaats</label>
                                            <input type="text" 
                                                   id="city"
                                                   class="kt-input @error('city') border-destructive @enderror" 
                                                   name="city" 
                                                   value="{{ old('city') }}"
                                                   readonly>
                                            @error('city')
                                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="text-xs text-muted-foreground mb-1 block">Land</label>
                                            <input type="text" 
                                                   id="country"
                                                   class="kt-input @error('country') border-destructive @enderror" 
                                                   name="country" 
                                                   value="{{ old('country', 'Nederland') }}"
                                                   readonly>
                                            @error('country')
                                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="w-96 flex-shrink-0">
                                        <div class="rounded-xl w-full" id="address_map" style="height: 208px; min-height: 208px; border: 1px solid var(--input); background-color: #f0f0f0;"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr style="display: none;">
                            <td>
                                <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
                                <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Telefoon
                            </td>
                            <td>
                                <input type="tel" 
                                       class="kt-input @error('phone') border-destructive @enderror" 
                                       name="phone" 
                                       value="{{ old('phone') }}"
                                       pattern="(\+31|0)[1-9][0-9]{8}"
                                       placeholder="0612345678 of +31612345678"
                                       maxlength="13"
                                       style="width: 15ch;">
                                <div class="text-xs text-muted-foreground mt-1">Nederlands nummer (bijv. 0612345678 of +31612345678)</div>
                                @error('phone')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-green-600 mt-1 hidden location-phone-success">
                                    <i class="ki-filled ki-check-circle me-1"></i> Telefoonnummer is geldig
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                E-mail
                            </td>
                            <td>
                                <input type="email" 
                                       class="kt-input @error('email') border-destructive @enderror" 
                                       name="email" 
                                       value="{{ old('email') }}"
                                       autocomplete="email">
                                @error('email')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-green-600 mt-1 hidden location-email-success">
                                    <i class="ki-filled ki-check-circle me-1"></i> E-mailadres is geldig
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Vestiging Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    /* Remove all borders between table rows in show forms */
    .kt-table-border-dashed tbody tr {
        border-bottom: none !important;
    }
    /* Uniform row height for all table rows */
    .kt-table-border-dashed tbody tr,
    .kt-table-border-dashed tbody tr td {
        height: auto;
        min-height: 48px;
    }
    .kt-table-border-dashed tbody tr td {
        padding-top: 12px;
        padding-bottom: 12px;
        vertical-align: middle;
    }
</style>
@endpush

@push('scripts')
@if(!empty($googleMapsApiKey))
<script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&libraries=places"></script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure Cmd+A (Mac) and Ctrl+A (Windows/Linux) works in input fields
    // Use capture phase to run before other handlers that might block it
    document.addEventListener('keydown', function(e) {
        // Check if Cmd+A (Mac) or Ctrl+A (Windows/Linux) is pressed
        const isSelectAll = (e.metaKey || e.ctrlKey) && (e.key === 'a' || e.key === 'A');
        
        // Only allow if the target is an input, textarea, or contenteditable element
        const target = e.target;
        const isInputField = target && (
            target.tagName === 'INPUT' || 
            target.tagName === 'TEXTAREA' || 
            target.isContentEditable
        );
        
        if (isSelectAll && isInputField) {
            // Stop propagation to prevent other handlers from blocking it
            e.stopPropagation();
            // Don't prevent default - allow browser's default select all behavior
            // The browser will handle selecting all text in the input field
            return true;
        }
    }, true); // Use capture phase to ensure this runs before other handlers

    // OpenPostcode API integration
    const postalCodeInput = document.getElementById('postal_code');
    const houseNumberInput = document.getElementById('house_number');
    const streetInput = document.getElementById('street');
    const cityInput = document.getElementById('city');
    const countryInput = document.getElementById('country');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');

    let lookupTimeout;
    let googleMap = null;
    let googleMarker = null;

    // Initialize Google Maps
    @if(!empty($googleMapsApiKey))
    function initGoogleMap(lat, lng) {
        const mapElement = document.getElementById('address_map');
        if (!mapElement) {
            console.warn('Map element not found');
            return;
        }

        if (typeof google === 'undefined') {
            console.warn('Google Maps API not loaded');
            return;
        }

        const defaultLat = {{ $googleMapsCenterLat }};
        const defaultLng = {{ $googleMapsCenterLng }};
        const defaultZoom = {{ $googleMapsZoom }};

        const mapLat = lat || defaultLat;
        const mapLng = lng || defaultLng;

        googleMap = new google.maps.Map(mapElement, {
            center: { lat: mapLat, lng: mapLng },
            zoom: lat && lng ? 18 : defaultZoom,
            mapTypeId: '{{ $googleMapsType }}',
            mapTypeControl: false
        });

        if (lat && lng) {
            const address = streetInput ? streetInput.value : '';
            googleMarker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: googleMap,
                title: address || 'Locatie'
            });
            
            if (address) {
                const infoWindow = new google.maps.InfoWindow({
                    content: `<div style="padding: 8px; color: #000000;"><strong style="color: #000000;">${address}</strong></div>`
                });
                googleMarker.addListener('click', function() {
                    infoWindow.open(googleMap, googleMarker);
                });
                infoWindow.open(googleMap, googleMarker);
            }
        }
    }

    // Initialize map on page load - wait for Google Maps to load
    function waitForGoogleMaps() {
        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
            initGoogleMap();
        } else {
            setTimeout(waitForGoogleMaps, 100);
        }
    }
    
    waitForGoogleMaps();
    @endif

    function lookupAddress() {
        const postcode = postalCodeInput.value.trim().toUpperCase().replace(/\s+/g, '');
        const huisnummer = houseNumberInput.value.trim();

        // Validate postcode format
        if (!/^[1-9][0-9]{3}[A-Z]{2}$/.test(postcode)) {
            return;
        }

        if (!huisnummer) {
            return;
        }

        // Clear previous timeout
        clearTimeout(lookupTimeout);

        // Debounce API call
        lookupTimeout = setTimeout(function() {
            fetch('{{ route('admin.postcode.lookup') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    postcode: postcode,
                    huisnummer: huisnummer
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fill in the address fields
                    streetInput.value = data.street || '';
                    cityInput.value = data.city || '';
                    countryInput.value = data.country || 'Nederland';
                    
                    // Make fields editable if API didn't return data
                    if (!data.street || !data.city) {
                        streetInput.removeAttribute('readonly');
                        cityInput.removeAttribute('readonly');
                        countryInput.removeAttribute('readonly');
                    } else {
                        streetInput.setAttribute('readonly', 'readonly');
                        cityInput.setAttribute('readonly', 'readonly');
                        countryInput.setAttribute('readonly', 'readonly');
                    }

                    // Update map if we have coordinates
                    if (data.latitude && data.longitude) {
                        const lat = parseFloat(data.latitude);
                        const lng = parseFloat(data.longitude);
                        
                        latitudeInput.value = lat;
                        longitudeInput.value = lng;

                        @if(!empty($googleMapsApiKey))
                        // Update Google Map
                        if (typeof google !== 'undefined') {
                            if (!googleMap) {
                                initGoogleMap(lat, lng);
                            } else {
                                googleMap.setCenter({ lat: lat, lng: lng });
                                googleMap.setZoom(18);
                            }

                            // Remove existing marker
                            if (googleMarker) {
                                googleMarker.setMap(null);
                            }

                            // Add new marker
                            const address = `${data.street} ${data.house_number}, ${data.postal_code} ${data.city}`;
                            googleMarker = new google.maps.Marker({
                                position: { lat: lat, lng: lng },
                                map: googleMap,
                                title: address
                            });

                            // Add info window
                            const infoWindow = new google.maps.InfoWindow({
                                content: `<div style="padding: 8px; color: #000000;"><strong style="color: #000000;">${address}</strong></div>`
                            });
                            googleMarker.addListener('click', function() {
                                infoWindow.open(googleMap, googleMarker);
                            });
                            infoWindow.open(googleMap, googleMarker);
                        }
                        @endif
                    }
                } else {
                    // API failed - make fields editable
                    streetInput.removeAttribute('readonly');
                    cityInput.removeAttribute('readonly');
                    countryInput.removeAttribute('readonly');
                }
            })
            .catch(error => {
                console.error('Postcode lookup error:', error);
                // Make fields editable on error
                streetInput.removeAttribute('readonly');
                cityInput.removeAttribute('readonly');
                countryInput.removeAttribute('readonly');
            });
        }, 500);
    }

    // Listen for changes in postcode and house number
    postalCodeInput.addEventListener('input', lookupAddress);
    postalCodeInput.addEventListener('blur', lookupAddress);
    houseNumberInput.addEventListener('input', lookupAddress);
    houseNumberInput.addEventListener('blur', lookupAddress);

    // If fields are already filled, trigger lookup
    if (postalCodeInput.value && houseNumberInput.value) {
        lookupAddress();
    }
});
</script>
@endpush

@push('styles')
<style>
    #address_map {
        height: 208px;
        width: 100%;
        border-radius: 0.75rem;
        border: 1px solid var(--input);
    }
</style>
@endpush

@endsection

