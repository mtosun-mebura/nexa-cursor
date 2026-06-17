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
                $pickupAtValue = old('pickup_at');
                if ($pickupAtValue === null && isset($booking) && $booking->pickup_at) {
                    $pickupAtValue = $booking->pickup_at->format('Y-m-d\TH:i');
                }
            @endphp
            <input type="datetime-local" name="pickup_at" class="kt-input w-full max-w-md" value="{{ $pickupAtValue }}" required>
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

@push('scripts')
<script>
(function () {
    var select = document.getElementById('individual_booking_passenger_id');
    if (!select) return;

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
})();
</script>
@endpush
