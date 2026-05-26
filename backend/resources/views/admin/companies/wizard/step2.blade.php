@extends('admin.companies.wizard.layout')

@section('title', 'Stap 2 — Vestigingen')

@section('wizard_content')
<form method="post" action="{{ route('admin.companies.wizard.submit-step', [$company, 2]) }}">
    @csrf
    <x-error-card :errors="$errors" />

    <div class="kt-card min-w-full mb-6">
        <div class="kt-card-header flex flex-wrap items-center justify-between gap-3">
            <h3 class="kt-card-title">Eerste vestiging (optioneel)</h3>
        </div>
        <p class="text-sm text-secondary-foreground px-6 pt-2 pb-3 mb-0">
            Voeg een vestiging toe of sla over en vul dit later in via het bedrijfsdetail.
            Als je in stap 1 <strong>Hoofdvestiging</strong> hebt aangevinkt, wordt de eerste vestiging die je hier opslaat automatisch als hoofdvestiging gemarkeerd.
        </p>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Naam vestiging</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input @error('locations.0.name') border-destructive @enderror" name="locations[0][name]" value="{{ old('locations.0.name') }}" @error('locations.0.name') data-server-error="1" @enderror>
                        @error('locations.0.name')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="locations[0][name]">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Straat</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input @error('locations.0.street') border-destructive @enderror" name="locations[0][street]" value="{{ old('locations.0.street') }}" @error('locations.0.street') data-server-error="1" @enderror>
                        @error('locations.0.street')<div class="text-xs text-destructive mt-1" data-validation-error="1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Huisnummer</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input @error('locations.0.house_number') border-destructive @enderror" name="locations[0][house_number]" value="{{ old('locations.0.house_number') }}" @error('locations.0.house_number') data-server-error="1" @enderror>
                        @error('locations.0.house_number')<div class="text-xs text-destructive mt-1" data-validation-error="1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Postcode</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input @error('locations.0.postal_code') border-destructive @enderror" name="locations[0][postal_code]" value="{{ old('locations.0.postal_code') }}" @error('locations.0.postal_code') data-server-error="1" @enderror>
                        @error('locations.0.postal_code')<div class="text-xs text-destructive mt-1" data-validation-error="1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Plaats</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input @error('locations.0.city') border-destructive @enderror" name="locations[0][city]" value="{{ old('locations.0.city') }}" @error('locations.0.city') data-server-error="1" @enderror>
                        @error('locations.0.city')<div class="text-xs text-destructive mt-1" data-validation-error="1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Land</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input @error('locations.0.country') border-destructive @enderror" name="locations[0][country]" value="{{ old('locations.0.country', 'Nederland') }}" @error('locations.0.country') data-server-error="1" @enderror>
                        @error('locations.0.country')<div class="text-xs text-destructive mt-1" data-validation-error="1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Actief</td>
                    <td class="min-w-48 w-full">
                        <label class="kt-label flex items-center gap-2">
                            <input type="checkbox" class="kt-switch kt-switch-sm" name="locations[0][is_active]" value="1" {{ old('locations.0.is_active', true) ? 'checked' : '' }}>
                            Vestiging is actief
                        </label>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <x-wizard.footer-actions :current-step="$currentStep" :company="$company">
        <button type="submit" name="skip_locations" value="1" class="kt-btn kt-btn-outline">
            Overslaan
        </button>
        <button type="submit" class="kt-btn kt-btn-primary">
            Volgende
            <i class="ki-filled ki-arrow-right ms-2"></i>
        </button>
    </x-wizard.footer-actions>
</form>
@endsection
