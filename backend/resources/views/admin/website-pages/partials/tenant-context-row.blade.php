@php
    $ctx = $websiteTenantContext ?? ['visible' => false];
@endphp
@if(!empty($ctx['visible']))
    <tr>
        <td class="min-w-56 text-secondary-foreground font-normal align-top">Bedrijf (tenant)</td>
        <td class="min-w-48 w-full">
            @if(!empty($ctx['stored_company']))
                <p class="text-sm text-foreground mb-1 font-medium">{{ $ctx['stored_company']->name }}</p>
                <p class="text-xs text-muted-foreground mb-0">Deze pagina is aan dit bedrijf gekoppeld. De koppeling kan hier niet worden gewijzigd.</p>
            @elseif(!empty($ctx['show_company_dropdown']))
                <label for="website_page_company_id" class="text-sm font-medium text-foreground block mb-1">Kies bedrijf *</label>
                <select name="company_id"
                        id="website_page_company_id"
                        class="kt-input @error('company_id') border-destructive @enderror"
                        required>
                    <option value="" disabled {{ old('company_id') ? '' : 'selected' }}>— Selecteer een bedrijf —</option>
                    @foreach($ctx['companies'] ?? [] as $c)
                        <option value="{{ $c->id }}" {{ (string) old('company_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('company_id')
                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                @enderror
                <p class="text-xs text-muted-foreground mt-1 mb-0">Er is geen tenant gekozen in de sidebar. Elke pagina hoort bij precies één bedrijf.</p>
            @elseif(!empty($ctx['effective_company']))
                <p class="text-sm text-foreground mb-1 font-medium">{{ $ctx['effective_company']->name }}</p>
                <p class="text-xs text-muted-foreground mb-0">De actieve tenant in de sidebar wordt gebruikt. Bij opslaan wordt deze pagina aan dit bedrijf gekoppeld.</p>
            @else
                <p class="text-sm text-destructive mb-1 font-medium">Geen bedrijfscontext</p>
                <p class="text-xs text-muted-foreground mb-0">Kies een tenant in de sidebar of selecteer hierboven een bedrijf.</p>
            @endif
        </td>
    </tr>
@endif
