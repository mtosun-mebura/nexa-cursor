<table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
    <tr>
        <td class="min-w-56 text-secondary-foreground font-normal">Voornaam <span class="text-danger">*</span></td>
        <td class="min-w-48 w-full">
            <input type="text" name="first_name" value="{{ old('first_name', $passenger->first_name ?? '') }}" class="kt-input w-full" required maxlength="100">
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Achternaam <span class="text-danger">*</span></td>
        <td>
            <input type="text" name="last_name" value="{{ old('last_name', $passenger->last_name ?? '') }}" class="kt-input w-full" required maxlength="100">
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Telefoonnummer</td>
        <td>
            <input type="text" name="phone" value="{{ old('phone', $passenger->phone ?? '') }}" class="kt-input w-full" maxlength="50">
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Ophaaladres <span class="text-danger">*</span></td>
        <td>
            @include('admin.partials.google-address-input', [
                'name' => 'pickup_address',
                'value' => old('pickup_address', $passenger->pickup_address ?? ''),
                'latName' => 'pickup_lat',
                'lngName' => 'pickup_lng',
                'latValue' => old('pickup_lat', $passenger->pickup_lat ?? ''),
                'lngValue' => old('pickup_lng', $passenger->pickup_lng ?? ''),
                'required' => true,
                'placeholder' => 'Zoek adres...',
                'hint' => 'Kies een suggestie; coördinaten worden automatisch opgeslagen voor routeplanning.',
            ])
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal">Status</td>
        <td>
            <input type="hidden" name="active" value="0">
            <label class="kt-label flex items-center gap-2 mb-0" for="passenger_active">
                <input type="checkbox" name="active" id="passenger_active" value="1" class="kt-switch kt-switch-sm shrink-0" @checked(old('active', $passenger->active ?? true))>
                <span class="text-sm text-muted-foreground">Actief</span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="text-secondary-foreground font-normal align-top pt-3">Notities</td>
        <td>
            <textarea name="notes" rows="3" class="kt-textarea w-full" placeholder="Bijv. rolstoel, begeleider, opmerkingen voor chauffeur...">{{ old('notes', $passenger->notes ?? '') }}</textarea>
        </td>
    </tr>
</table>
