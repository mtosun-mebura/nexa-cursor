<table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
    <tr>
        <td class="min-w-56 text-secondary-foreground font-normal">Naam <span class="text-danger">*</span></td>
        <td class="min-w-48 w-full">
            <input type="text" name="name" value="{{ old('name', $group->name ?? '') }}" class="kt-input w-full" required maxlength="200" placeholder="bijv. Ochtendgroep A">
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Vertrekadres</td>
        <td>
            @include('admin.partials.google-address-input', [
                'name' => 'departure_address',
                'value' => old('departure_address', $group->departure_address ?? ''),
                'latName' => 'departure_lat',
                'lngName' => 'departure_lng',
                'latValue' => old('departure_lat', $group->departure_lat ?? ''),
                'lngValue' => old('departure_lng', $group->departure_lng ?? ''),
                'required' => false,
                'placeholder' => 'Zoek vertrekadres (depot)...',
                'hint' => 'Optioneel. Laat leeg om te starten bij de eerste ophaalstop.',
            ])
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Eindlocatie (school) <span class="text-danger">*</span></td>
        <td>
            @include('admin.partials.google-address-input', [
                'name' => 'destination_address',
                'value' => old('destination_address', $group->destination_address ?? ''),
                'latName' => 'destination_lat',
                'lngName' => 'destination_lng',
                'latValue' => old('destination_lat', $group->destination_lat ?? ''),
                'lngValue' => old('destination_lng', $group->destination_lng ?? ''),
                'required' => true,
                'placeholder' => 'Zoek schooladres...',
                'hint' => 'Kies een suggestie; coördinaten worden opgeslagen voor routeplanning.',
            ])
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Aankomsttijd eindlocatie <span class="text-danger">*</span></td>
        <td>
            @php
                $arrival = old('destination_arrival_time', $group->destination_arrival_time ?? '08:00');
                if (is_string($arrival) && strlen($arrival) > 5) {
                    $arrival = substr($arrival, 0, 5);
                }
            @endphp
            <input type="time" name="destination_arrival_time" value="{{ $arrival }}" class="kt-input w-40" required>
            <p class="text-xs text-muted-foreground mt-1">Bijv. 08:00 — gebruikt voor routeberekening (volgende stap).</p>
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Status</td>
        <td>
            <input type="hidden" name="active" value="0">
            <label class="kt-label flex items-center gap-2 mb-0" for="group_active">
                <input type="checkbox" name="active" id="group_active" value="1" class="kt-switch kt-switch-sm shrink-0" @checked(old('active', $group->active ?? true))>
                <span class="text-sm text-muted-foreground">Actief</span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal align-top pt-3">Notities</td>
        <td>
            <textarea name="notes" rows="3" class="kt-textarea w-full" placeholder="Optionele opmerkingen voor planners...">{{ old('notes', $group->notes ?? '') }}</textarea>
        </td>
    </tr>
</table>
