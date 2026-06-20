<table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
    <tr>
        <td class="min-w-56 text-secondary-foreground font-normal">Passagier <span class="text-danger">*</span></td>
        <td class="min-w-48 w-full">
            <select name="transport_passenger_id" id="individual_booking_passenger_id" class="kt-select w-full max-w-md" required>
                <option value="">— Selecteer passagier —</option>
                @foreach($passengers as $passenger)
                    <option value="{{ $passenger->id }}"
                        data-pickup-address="{{ $passenger->pickup_address }}"
                        data-pickup-lat="{{ $passenger->pickup_lat }}"
                        data-pickup-lng="{{ $passenger->pickup_lng }}"
                        @selected(old('transport_passenger_id', $booking->transport_passenger_id ?? '') == $passenger->id)>
                        {{ $passenger->full_name }}
                    </option>
                @endforeach
            </select>
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Ophaaladres <span class="text-danger">*</span></td>
        <td>
            @include('admin.partials.google-address-input', [
                'name' => 'pickup_address',
                'value' => old('pickup_address', $booking->pickup_address ?? ''),
                'latName' => 'pickup_lat',
                'lngName' => 'pickup_lng',
                'latValue' => old('pickup_lat', $booking->pickup_lat ?? ''),
                'lngValue' => old('pickup_lng', $booking->pickup_lng ?? ''),
                'required' => true,
                'placeholder' => 'Zoek ophaaladres...',
            ])
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Afzetadres <span class="text-danger">*</span></td>
        <td>
            @include('admin.partials.google-address-input', [
                'name' => 'dropoff_address',
                'value' => old('dropoff_address', $booking->dropoff_address ?? ''),
                'latName' => 'dropoff_lat',
                'lngName' => 'dropoff_lng',
                'latValue' => old('dropoff_lat', $booking->dropoff_lat ?? ''),
                'lngValue' => old('dropoff_lng', $booking->dropoff_lng ?? ''),
                'required' => true,
                'placeholder' => 'Zoek afzetadres...',
            ])
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Datum en tijd ophalen <span class="text-danger">*</span></td>
        <td>
            @php
                $pickupAtRaw = old('pickup_at');
                if ($pickupAtRaw === null && isset($booking) && $booking->pickup_at) {
                    $pickupAtRaw = $booking->pickup_at->format('Y-m-d H:i');
                }

                $pickupAtCarbon = null;
                $pickupAtDisplay = '';
                $pickupAtHidden = '';
                $pickupTime = old('pickup_time', '');

                if ($pickupAtRaw) {
                    try {
                        $pickupAtCarbon = \Carbon\Carbon::parse($pickupAtRaw);
                        $pickupAtDisplay = $pickupAtCarbon->format('d-m-Y');
                        $pickupAtHidden = $pickupAtCarbon->format('Y-m-d H:i');
                        if ($pickupTime === '') {
                            $pickupTime = $pickupAtCarbon->format('H:i');
                        }
                    } catch (\Exception $e) {
                    }
                }
            @endphp
            <div class="flex flex-col">
                <div class="flex flex-wrap items-center gap-2.5">
                    <div class="kt-input w-full max-w-[200px] @error('pickup_at') border-destructive @enderror">
                        <i class="ki-outline ki-calendar"></i>
                        <input class="grow"
                               id="individual_booking_pickup_at_display"
                               data-kt-date-picker="true"
                               data-kt-date-picker-input-mode="true"
                               data-kt-date-picker-position-to-input="left"
                               data-kt-date-picker-date-format="DD-MM-YYYY"
                               @if($pickupAtCarbon)
                               data-kt-date-picker-selected-dates='["{{ $pickupAtCarbon->format('Y-m-d') }}"]'
                               data-kt-date-picker-selected-month="{{ $pickupAtCarbon->format('n') - 1 }}"
                               data-kt-date-picker-selected-year="{{ $pickupAtCarbon->format('Y') }}"
                               @endif
                               placeholder="Selecteer datum"
                               readonly
                               type="text"
                               value="{{ $pickupAtDisplay }}"
                               required />
                        <input type="hidden"
                               name="pickup_at"
                               id="individual_booking_pickup_at_hidden"
                               value="{{ $pickupAtHidden }}" />
                    </div>
                    <div class="kt-input w-full max-w-[120px] @error('pickup_at') border-destructive @enderror">
                        <i class="ki-outline ki-time"></i>
                        <input type="time"
                               id="individual_booking_pickup_time"
                               class="grow"
                               value="{{ $pickupTime }}"
                               required>
                    </div>
                </div>
                @error('pickup_at')
                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                @enderror
                <small class="text-muted-foreground text-xs mt-1 block">Voer tijd in als hh:mm (bijv. 14:30)</small>
            </div>
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Chauffeur</td>
        <td>
            <select name="driver_id" class="kt-select w-full max-w-md">
                <option value="">— Geen vaste chauffeur —</option>
                @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}" @selected(old('driver_id', $booking->driver_id ?? '') == $driver->id)>
                        {{ $driver->first_name }} {{ $driver->last_name }}
                    </option>
                @endforeach
            </select>
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Voertuig</td>
        <td>
            <select name="vehicle_id" class="kt-select w-full max-w-md">
                <option value="">— Geen vast voertuig —</option>
                @foreach($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" @selected(old('vehicle_id', $booking->vehicle_id ?? '') == $vehicle->id)>
                        {{ $vehicle->name }}@if($vehicle->license_plate) — {{ $vehicle->license_plate }}@endif
                    </option>
                @endforeach
            </select>
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Ritprijs override (€)</td>
        <td>
            <div class="flex items-center gap-2 w-full max-w-md">
                <span class="shrink-0 text-sm font-medium text-foreground" aria-hidden="true">€</span>
                <input type="number" name="price_override" class="kt-input w-full min-w-0" step="0.01" min="0"
                    value="{{ old('price_override', $booking->price_override ?? '') }}"
                    placeholder="Leeg = abonnementsprijs per rit">
            </div>
        </td>
    </tr>
</table>

@push('styles')
<style>
    #individual_booking_pickup_time {
        color-scheme: light;
    }
    .dark #individual_booking_pickup_time {
        color-scheme: dark;
    }
    #individual_booking_pickup_time::-webkit-calendar-picker-indicator {
        display: none;
        -webkit-appearance: none;
        appearance: none;
    }
    .kt-input:has(#individual_booking_pickup_time) .ki-time {
        color: var(--kt-text-muted);
        opacity: 0.7;
    }
    .dark .kt-input:has(#individual_booking_pickup_time) .ki-time {
        color: var(--kt-text-muted);
        opacity: 0.8;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('individual_booking_passenger_id');
    if (select) {
        select.addEventListener('change', function () {
            var option = select.options[select.selectedIndex];
            if (!option || !option.dataset.pickupAddress) return;

            var addressInput = document.querySelector('[name="pickup_address"]');
            var latInput = document.querySelector('[name="pickup_lat"]');
            var lngInput = document.querySelector('[name="pickup_lng"]');

            if (addressInput && !addressInput.value) {
                addressInput.value = option.dataset.pickupAddress || '';
                addressInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (latInput && option.dataset.pickupLat) {
                latInput.value = option.dataset.pickupLat;
            }
            if (lngInput && option.dataset.pickupLng) {
                lngInput.value = option.dataset.pickupLng;
            }
        });
    }

    var dateInput = document.getElementById('individual_booking_pickup_at_display');
    var hiddenInput = document.getElementById('individual_booking_pickup_at_hidden');
    var timeInput = document.getElementById('individual_booking_pickup_time');

    function convertToISODate(displayDate) {
        if (!displayDate) return '';
        var parts = displayDate.split('-');
        if (parts.length !== 3) return displayDate;
        return parts[2] + '-' + parts[1] + '-' + parts[0];
    }

    function updateHiddenInput() {
        if (!hiddenInput || !dateInput) return;

        var dateValue = convertToISODate(dateInput.value.trim());
        if (!dateValue && hiddenInput.value.trim()) {
            dateValue = hiddenInput.value.trim().split(' ')[0];
        }

        var currentTime = timeInput ? timeInput.value.trim() : '';
        if (dateValue) {
            hiddenInput.value = currentTime ? (dateValue + ' ' + currentTime) : dateValue;
        } else {
            hiddenInput.value = '';
        }
    }

    if (dateInput) {
        var lastDateValue = dateInput.value;
        setInterval(function () {
            if (dateInput.value !== lastDateValue) {
                lastDateValue = dateInput.value;
                updateHiddenInput();
            }
        }, 200);
        dateInput.addEventListener('change', updateHiddenInput);
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('vc-date__btn') ||
                e.target.closest('.vc-date__btn') ||
                e.target.closest('.vc')) {
                setTimeout(function () {
                    if (dateInput.value !== lastDateValue) {
                        lastDateValue = dateInput.value;
                        updateHiddenInput();
                    }
                }, 100);
            }
        });
    }

    if (timeInput) {
        timeInput.addEventListener('change', updateHiddenInput);
        timeInput.addEventListener('input', updateHiddenInput);

        var timeInputWrapper = timeInput.closest('.kt-input');
        if (timeInputWrapper) {
            timeInputWrapper.addEventListener('click', function (e) {
                if (e.target !== timeInput) {
                    e.preventDefault();
                    timeInput.focus();
                    if (timeInput.showPicker) {
                        try { timeInput.showPicker(); } catch (err) { timeInput.focus(); }
                    }
                }
            });
        }
        timeInput.addEventListener('click', function () {
            if (timeInput.showPicker) {
                try { timeInput.showPicker(); } catch (err) {}
            }
        });
    }

    var form = dateInput ? dateInput.closest('form') : null;
    if (form) {
        form.addEventListener('submit', updateHiddenInput);
    }

    updateHiddenInput();
});
</script>
@endpush
