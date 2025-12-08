@extends('admin.layouts.app')

@section('title', 'Nieuw Bedrijf')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuw Bedrijf
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.companies.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.companies.store') }}" method="POST" enctype="multipart/form-data">
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

            <!-- General Info -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Algemene Informatie
                    </h3>
                    <div class="flex items-center gap-2">
                        <label class="kt-label">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
                                   id="toggle-main-location-header"
                                   {{ old('has_main_location', false) ? 'checked' : '' }}/>
                            Hoofdkantoor
                        </label>
                        <span class="text-muted-foreground">|</span>
                        <label class="kt-label">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
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
                                Bedrijfsnaam *
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
                            <td class="text-secondary-foreground font-normal align-top">
                                Bedrijfslogo
                            </td>
                            <td>
                                <div class="flex flex-wrap sm:flex-nowrap gap-5 lg:gap-7.5 max-w-96 w-full">
                                    <img alt="Company Logo" class="h-[35px] mt-2 hidden" id="logo-preview"/>
                                    <div class="flex bg-center w-full p-5 lg:p-7 bg-no-repeat bg-[length:550px] border border-input rounded-xl border-dashed branding-bg" id="logo-upload-area">
                                        <div class="flex flex-col place-items-center place-content-center text-center rounded-xl w-full">
                                            <div class="flex items-center mb-2.5">
                                                <div class="relative size-11 shrink-0">
                                                    <svg class="w-full h-full stroke-primary/10 fill-light" fill="none" height="48" viewbox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z" fill=""></path>
                                                        <path d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z" stroke="" stroke-opacity="0.2"></path>
                                                    </svg>
                                                    <div class="absolute leading-none left-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4">
                                                        <i class="ki-filled ki-picture text-xl ps-px text-primary"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <a class="text-mono text-xs font-medium hover:text-primary mb-px cursor-pointer" id="logo-upload-link">
                                                Klik of Sleep & Drop
                                            </a>
                                            <span class="text-xs text-secondary-foreground text-nowrap">
                                                SVG, PNG, JPG (max. 800x400)
                                            </span>
                                        </div>
                                    </div>
                                    <input type="file" 
                                           name="logo" 
                                           id="logo-input" 
                                           accept="image/svg+xml,image/png,image/jpeg,image/jpg"
                                           class="hidden">
                                </div>
                                @error('logo')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                KVK Nummer
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('kvk_number') border-destructive @enderror" 
                                       name="kvk_number" 
                                       value="{{ old('kvk_number') }}"
                                       pattern="[0-9]{8}"
                                       placeholder="12345678"
                                       maxlength="8">
                                <div class="text-xs text-muted-foreground mt-1">8 cijfers (bijv. 12345678)</div>
                                @error('kvk_number')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="kvk_number_error"></div>
                                <div class="text-xs text-green-600 mt-1 hidden" id="kvk_number_success">
                                    <i class="ki-filled ki-check-circle me-1"></i> KVK nummer is geldig
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Branche
                            </td>
                            <td>
                                @php
                                    $currentIndustry = old('industry');
                                    $selectedBranch = $branches->firstWhere('name', $currentIndustry);
                                    $isOther = $currentIndustry && !$selectedBranch;
                                @endphp
                                <select class="kt-input @error('industry') border-destructive @enderror" 
                                        name="branch_select" 
                                        id="branch_select">
                                    <option value="">-- Selecteer branche --</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->name }}" 
                                                {{ $currentIndustry === $branch->name ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                    <option value="other" {{ $isOther ? 'selected' : '' }}>Anders</option>
                                </select>
                                <input type="text" 
                                       class="kt-input @error('industry') border-destructive @enderror mt-2 {{ $isOther ? '' : 'hidden' }}" 
                                       name="industry" 
                                       id="industry_custom"
                                       value="{{ $isOther ? $currentIndustry : '' }}"
                                       placeholder="Voer branche in">
                                @error('industry')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Bedrijfstype
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <input class="kt-checkbox" 
                                           type="checkbox" 
                                           id="is_intermediary" 
                                           name="is_intermediary" 
                                           value="1" 
                                           {{ old('is_intermediary') ? 'checked' : '' }}>
                                    <label for="is_intermediary" class="text-sm font-normal mb-0">
                                        Tussenpartij
                                    </label>
                                </div>
                                @error('is_intermediary')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Website
                            </td>
                            <td>
                                <input type="url" 
                                       class="kt-input @error('website') border-destructive @enderror" 
                                       name="website" 
                                       value="{{ old('website') }}"
                                       placeholder="https://">
                                @error('website')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Beschrijving
                            </td>
                            <td>
                                <textarea class="kt-input @error('description') border-destructive @enderror" 
                                          name="description" 
                                          rows="4">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Contact Informatie
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                E-mail *
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="email" 
                                       class="kt-input @error('email') border-destructive @enderror" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       required
                                       autocomplete="email">
                                @error('email')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="email_error"></div>
                                <div class="text-xs text-green-600 mt-1 hidden" id="email_success">
                                    <i class="ki-filled ki-check-circle me-1"></i> E-mailadres is geldig
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Telefoon
                            </td>
                            <td>
                                <input type="tel" 
                                       class="kt-input @error('phone') border-destructive @enderror" 
                                       name="phone" 
                                       value="{{ old('phone') }}"
                                       pattern="(\+31|0)[1-9][0-9]{8}"
                                       placeholder="0612345678 of +31612345678"
                                       maxlength="13">
                                <div class="text-xs text-muted-foreground mt-1">Nederlands nummer (bijv. 0612345678 of +31612345678)</div>
                                @error('phone')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="phone_error"></div>
                                <div class="text-xs text-green-600 mt-1 hidden" id="phone_success">
                                    <i class="ki-filled ki-check-circle me-1"></i> Telefoonnummer is geldig
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Straat
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('street') border-destructive @enderror" 
                                       name="street" 
                                       value="{{ old('street') }}">
                                @error('street')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Huisnummer
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('house_number') border-destructive @enderror" 
                                       name="house_number" 
                                       value="{{ old('house_number') }}">
                                @error('house_number')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Postcode
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('postal_code') border-destructive @enderror" 
                                       name="postal_code" 
                                       value="{{ old('postal_code') }}"
                                       pattern="[1-9][0-9]{3}\s?[A-Za-z]{2}"
                                       placeholder="1234AB"
                                       maxlength="7"
                                       style="text-transform: uppercase;">
                                <div class="text-xs text-muted-foreground mt-1">Nederlandse postcode (bijv. 1234AB)</div>
                                @error('postal_code')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="postal_code_error"></div>
                                <div class="text-xs text-green-600 mt-1 hidden" id="postal_code_success">
                                    <i class="ki-filled ki-check-circle me-1"></i> Postcode is geldig
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Plaats
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('city') border-destructive @enderror" 
                                       name="city" 
                                       value="{{ old('city') }}">
                                @error('city')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Land
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('country') border-destructive @enderror" 
                                       name="country" 
                                       value="{{ old('country', 'Nederland') }}">
                                @error('country')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Vestigingen -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Vestigingen
                    </h3>
                </div>
                <div class="kt-card-content">
                    <div id="locations-container">
                        <div class="location-item mb-5 p-5 rounded-lg" style="border: 1px solid var(--input) !important;">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-medium text-foreground">Vestiging 1</h4>
                                <div class="flex items-center gap-2">
                                    <label class="kt-label flex items-center gap-2">
                                        <input type="checkbox" 
                                               class="kt-checkbox" 
                                               name="locations[0][is_main]" 
                                               value="1"
                                               {{ old('locations.0.is_main') ? 'checked' : '' }}/>
                                        Hoofdkantoor
                                    </label>
                                    <label class="kt-label flex items-center gap-2">
                                        <input type="checkbox" 
                                               class="kt-checkbox" 
                                               name="locations[0][is_active]" 
                                               value="1"
                                               {{ old('locations.0.is_active', true) ? 'checked' : '' }}/>
                                        Actief
                                    </label>
                                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost remove-location-btn">
                                        <i class="ki-filled ki-cross"></i>
                                    </button>
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
                                                   class="kt-input @error('locations.0.name') border-destructive @enderror" 
                                                   name="locations[0][name]" 
                                                   value="{{ old('locations.0.name') }}" 
                                                   required>
                                            @error('locations.0.name')
                                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary-foreground font-normal">
                                            Straat
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   class="kt-input @error('locations.0.street') border-destructive @enderror" 
                                                   name="locations[0][street]" 
                                                   value="{{ old('locations.0.street') }}">
                                            @error('locations.0.street')
                                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary-foreground font-normal">
                                            Huisnummer
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   class="kt-input @error('locations.0.house_number') border-destructive @enderror" 
                                                   name="locations[0][house_number]" 
                                                   value="{{ old('locations.0.house_number') }}">
                                            @error('locations.0.house_number')
                                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary-foreground font-normal">
                                            Postcode
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   class="kt-input @error('locations.0.postal_code') border-destructive @enderror" 
                                                   name="locations[0][postal_code]" 
                                                   value="{{ old('locations.0.postal_code') }}"
                                                   pattern="[1-9][0-9]{3}\s?[A-Za-z]{2}"
                                                   placeholder="1234AB"
                                                   maxlength="7"
                                                   style="text-transform: uppercase;">
                                            <div class="text-xs text-muted-foreground mt-1">Nederlandse postcode (bijv. 1234AB)</div>
                                            @error('locations.0.postal_code')
                                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                            @enderror
                                            <div class="text-xs text-green-600 mt-1 hidden location-postal-code-success">
                                                <i class="ki-filled ki-check-circle me-1"></i> Postcode is geldig
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary-foreground font-normal">
                                            Plaats
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   class="kt-input @error('locations.0.city') border-destructive @enderror" 
                                                   name="locations[0][city]" 
                                                   value="{{ old('locations.0.city') }}">
                                            @error('locations.0.city')
                                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary-foreground font-normal">
                                            Land
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   class="kt-input @error('locations.0.country') border-destructive @enderror" 
                                                   name="locations[0][country]" 
                                                   value="{{ old('locations.0.country', 'Nederland') }}">
                                            @error('locations.0.country')
                                                <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary-foreground font-normal">
                                            Telefoon
                                        </td>
                                        <td>
                                            <input type="tel" 
                                                   class="kt-input @error('locations.0.phone') border-destructive @enderror" 
                                                   name="locations[0][phone]" 
                                                   value="{{ old('locations.0.phone') }}"
                                                   pattern="(\+31|0)[1-9][0-9]{8}"
                                                   placeholder="0612345678 of +31612345678"
                                                   maxlength="13">
                                            <div class="text-xs text-muted-foreground mt-1">Nederlands nummer (bijv. 0612345678 of +31612345678)</div>
                                            @error('locations.0.phone')
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
                                                   class="kt-input @error('locations.0.email') border-destructive @enderror" 
                                                   name="locations[0][email]" 
                                                   value="{{ old('locations.0.email') }}"
                                                   autocomplete="email">
                                            @error('locations.0.email')
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
                    </div>
                    <button type="button" id="add-location-btn" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-plus me-2"></i>
                        Extra Vestiging Toevoegen
                    </button>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.companies.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Bedrijf Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@endsection

@push('styles')
<style>
    /* Remove all borders between table rows in create forms */
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
    .kt-table-border-dashed tbody tr td.align-top {
        vertical-align: top !important;
        padding-top: 18px;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Main location header toggle
    const mainLocationHeaderToggle = document.getElementById('toggle-main-location-header');
    
    if (mainLocationHeaderToggle) {
        // Function to check if any location has is_main checked
        function checkMainLocationStatus() {
            const mainCheckboxes = document.querySelectorAll('input[name^="locations"][name$="[is_main]"]');
            let hasMain = false;
            mainCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    hasMain = true;
                }
            });
            mainLocationHeaderToggle.checked = hasMain;
        }
        
        // Function to set first location as main or unset all
        function toggleMainLocations(shouldSet) {
            const mainCheckboxes = document.querySelectorAll('input[name^="locations"][name$="[is_main]"]');
            if (shouldSet) {
                // Find first active location checkbox and set it as main
                const activeCheckboxes = document.querySelectorAll('input[name^="locations"][name$="[is_active]"]');
                let foundFirst = false;
                activeCheckboxes.forEach((activeCheckbox, index) => {
                    if (activeCheckbox.checked && !foundFirst) {
                        // Find corresponding main checkbox
                        const locationItem = activeCheckbox.closest('.location-item');
                        if (locationItem) {
                            const mainCheckbox = locationItem.querySelector('input[name^="locations"][name$="[is_main]"]');
                            if (mainCheckbox) {
                                mainCheckbox.checked = true;
                                foundFirst = true;
                            }
                        }
                    }
                });
                // If no active location found, set first location as main
                if (!foundFirst && mainCheckboxes.length > 0) {
                    mainCheckboxes[0].checked = true;
                }
            } else {
                // Uncheck all main location checkboxes
                mainCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            }
        }
        
        // Listen to header toggle changes
        mainLocationHeaderToggle.addEventListener('change', function() {
            toggleMainLocations(this.checked);
        });
        
        // Listen to individual location main checkboxes
        const locationsContainer = document.getElementById('locations-container');
        if (locationsContainer) {
            locationsContainer.addEventListener('change', function(e) {
                if (e.target.name && e.target.name.includes('[is_main]')) {
                    // If a main checkbox is checked, uncheck others
                    if (e.target.checked) {
                        const allMainCheckboxes = document.querySelectorAll('input[name^="locations"][name$="[is_main]"]');
                        allMainCheckboxes.forEach(checkbox => {
                            if (checkbox !== e.target) {
                                checkbox.checked = false;
                            }
                        });
                    }
                    checkMainLocationStatus();
                }
            });
        }
        
        // Initial check
        checkMainLocationStatus();
    }
    
    // Logo upload handling
    const logoInput = document.getElementById('logo-input');
    const logoUploadArea = document.getElementById('logo-upload-area');
    const logoUploadLink = document.getElementById('logo-upload-link');
    const logoPreview = document.getElementById('logo-preview');
    
    if (logoInput && logoUploadArea && logoUploadLink) {
        // Click to upload
        logoUploadLink.addEventListener('click', function(e) {
            e.preventDefault();
            logoInput.click();
        });
        
        logoUploadArea.addEventListener('click', function(e) {
            if (e.target === logoUploadArea || e.target.closest('#logo-upload-area')) {
                logoInput.click();
            }
        });
        
        // Drag and drop
        logoUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logoUploadArea.classList.add('border-primary');
        });
        
        logoUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logoUploadArea.classList.remove('border-primary');
        });
        
        logoUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            logoUploadArea.classList.remove('border-primary');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleLogoFile(files[0]);
            }
        });
        
        // File input change
        logoInput.addEventListener('change', function(e) {
            if (this.files && this.files.length > 0) {
                handleLogoFile(this.files[0]);
            }
        });
        
        function handleLogoFile(file) {
            // Validate file type
            const allowedTypes = ['image/svg+xml', 'image/png', 'image/jpeg', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                alert('Alleen SVG, PNG en JPG bestanden zijn toegestaan.');
                return;
            }
            
            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Het bestand mag maximaal 5MB groot zijn.');
                return;
            }
            
            // Create preview
            const reader = new FileReader();
            reader.onload = function(e) {
                logoPreview.src = e.target.result;
                logoPreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }
    
    // Branch dropdown handling
    const branchSelect = document.getElementById('branch_select');
    const industryCustom = document.getElementById('industry_custom');
    
    if (branchSelect && industryCustom) {
        branchSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                industryCustom.classList.remove('hidden');
                industryCustom.value = '';
                industryCustom.focus();
            } else if (this.value) {
                industryCustom.classList.add('hidden');
                industryCustom.value = this.value;
            } else {
                industryCustom.classList.add('hidden');
                industryCustom.value = '';
            }
        });
        
        // On form submit, set the industry value from branch_select if not "other"
        const form = branchSelect.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (branchSelect.value && branchSelect.value !== 'other') {
                    industryCustom.value = branchSelect.value;
                }
            });
        }
    }
    
    let locationCount = 1;
    const addLocationBtn = document.getElementById('add-location-btn');
    const locationsContainer = document.getElementById('locations-container');
    
    addLocationBtn.addEventListener('click', function() {
        const locationHtml = `
            <div class="location-item mb-5 p-5 rounded-lg" style="border: 1px solid var(--input) !important;">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-medium text-foreground">Vestiging ${locationCount + 1}</h4>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost remove-location-btn">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
                <div class="flex items-center justify-end gap-2 mb-4">
                    <label class="kt-label flex items-center gap-2">
                        <input type="checkbox" 
                               class="kt-checkbox" 
                               name="locations[${locationCount}][is_main]" 
                               value="1"/>
                        Hoofdkantoor
                    </label>
                    <label class="kt-label flex items-center gap-2">
                        <input type="checkbox" 
                               class="kt-checkbox" 
                               name="locations[${locationCount}][is_active]" 
                               value="1"
                               checked/>
                        Actief
                    </label>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Naam *
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="text" 
                                       class="kt-input" 
                                       name="locations[${locationCount}][name]" 
                                       required>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Straat
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input" 
                                       name="locations[${locationCount}][street]">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Huisnummer
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input" 
                                       name="locations[${locationCount}][house_number]">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Postcode
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input" 
                                       name="locations[${locationCount}][postal_code]"
                                       pattern="[1-9][0-9]{3}\s?[A-Za-z]{2}"
                                       placeholder="1234AB"
                                       maxlength="7"
                                       style="text-transform: uppercase;">
                                <div class="text-xs text-muted-foreground mt-1">Nederlandse postcode (bijv. 1234AB)</div>
                                <div class="text-xs text-green-600 mt-1 hidden location-postal-code-success">
                                    <i class="ki-filled ki-check-circle me-1"></i> Postcode is geldig
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Plaats
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input" 
                                       name="locations[${locationCount}][city]">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Land
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input" 
                                       name="locations[${locationCount}][country]" 
                                       value="Nederland">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Telefoon
                            </td>
                            <td>
                                <input type="tel" 
                                       class="kt-input" 
                                       name="locations[${locationCount}][phone]"
                                       pattern="(\+31|0)[1-9][0-9]{8}"
                                       placeholder="0612345678 of +31612345678"
                                       maxlength="13">
                                <div class="text-xs text-muted-foreground mt-1">Nederlands nummer (bijv. 0612345678 of +31612345678)</div>
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
                                       class="kt-input" 
                                       name="locations[${locationCount}][email]"
                                       autocomplete="email">
                                <div class="text-xs text-green-600 mt-1 hidden location-email-success">
                                    <i class="ki-filled ki-check-circle me-1"></i> E-mailadres is geldig
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        `;
        
        locationsContainer.insertAdjacentHTML('beforeend', locationHtml);
        locationCount++;
        
        // Add event listener to remove button
        const removeBtn = locationsContainer.querySelector('.location-item:last-child .remove-location-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                this.closest('.location-item').remove();
            });
        }
        
        // Add validation to newly added location fields
        const newLocationItem = locationsContainer.querySelector('.location-item:last-child');
        if (newLocationItem) {
            const phoneInput = newLocationItem.querySelector('input[name^="locations"][name$="[phone]"]');
            const postalCodeInput = newLocationItem.querySelector('input[name^="locations"][name$="[postal_code]"]');
            const emailInput = newLocationItem.querySelector('input[type="email"]');
            
            if (phoneInput) {
                attachPhoneValidation(phoneInput);
            }
            
            if (postalCodeInput) {
                attachPostalCodeValidation(postalCodeInput);
            }
            
            if (emailInput) {
                attachEmailValidation(emailInput);
            }
        }
    });
    
    // Add event listeners to all remove buttons (including dynamically added ones)
    locationsContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-location-btn')) {
            e.target.closest('.location-item').remove();
            // Update location numbers
            updateLocationNumbers();
            // Update main location header toggle status after removing location
            if (mainLocationHeaderToggle) {
                checkMainLocationStatus();
            }
        }
    });
    
    function updateLocationNumbers() {
        const locationItems = locationsContainer.querySelectorAll('.location-item');
        locationItems.forEach((item, index) => {
            const title = item.querySelector('h4');
            if (title) {
                title.textContent = `Vestiging ${index + 1}`;
            }
        });
    }
    
    // Real-time validation for all form fields
    const form = document.querySelector('form');
    if (form) {
        // KVK Number validation
        const kvkInput = document.querySelector('input[name="kvk_number"]');
        if (kvkInput) {
            kvkInput.addEventListener('input', function() {
                const value = this.value.replace(/\D/g, ''); // Remove non-digits
                this.value = value;
                validateKVK(this);
            });
            kvkInput.addEventListener('keypress', function() {
                setTimeout(() => validateKVK(this), 10);
            });
        }
        
        // Phone validation - attach to all phone inputs including initial ones
        function attachPhoneValidation(input) {
            input.addEventListener('input', function() {
                let value = this.value.replace(/\s/g, ''); // Remove spaces
                // Auto-format: add +31 if starts with 0 and has 10 digits
                if (value.startsWith('0') && value.length === 10) {
                    value = '+31' + value.substring(1);
                }
                this.value = value;
                validatePhone(this);
            });
            input.addEventListener('keypress', function() {
                setTimeout(() => {
                    let value = this.value.replace(/\s/g, '');
                    if (value.startsWith('0') && value.length === 10) {
                        value = '+31' + value.substring(1);
                        this.value = value;
                    }
                    validatePhone(this);
                }, 10);
            });
        }
        
        const phoneInputs = document.querySelectorAll('input[name="phone"], input[name^="locations"][name$="[phone]"]');
        phoneInputs.forEach(attachPhoneValidation);
        
        // Postal code validation - attach to all postal code inputs including initial ones
        function attachPostalCodeValidation(input) {
            input.addEventListener('input', function() {
                let value = this.value.replace(/\s/g, '').toUpperCase(); // Remove spaces, uppercase
                // Auto-format: add space after 4 digits
                if (value.length > 4) {
                    value = value.substring(0, 4) + ' ' + value.substring(4, 7);
                }
                this.value = value;
                validatePostalCode(this);
            });
            input.addEventListener('keypress', function() {
                setTimeout(() => {
                    let value = this.value.replace(/\s/g, '').toUpperCase();
                    if (value.length > 4) {
                        value = value.substring(0, 4) + ' ' + value.substring(4, 7);
                        this.value = value;
                    }
                    validatePostalCode(this);
                }, 10);
            });
        }
        
        const postalCodeInputs = document.querySelectorAll('input[name="postal_code"], input[name^="locations"][name$="[postal_code]"]');
        postalCodeInputs.forEach(attachPostalCodeValidation);
        
        // Email validation - attach to all email inputs including initial ones
        function attachEmailValidation(input) {
            input.addEventListener('input', function() {
                validateEmail(this);
            });
            input.addEventListener('keypress', function() {
                setTimeout(() => validateEmail(this), 10);
            });
        }
        
        const emailInputs = document.querySelectorAll('input[type="email"]');
        emailInputs.forEach(attachEmailValidation);
        
        // Website validation
        const websiteInput = document.querySelector('input[name="website"]');
        if (websiteInput) {
            websiteInput.addEventListener('input', function() {
                validateWebsite(this);
            });
            websiteInput.addEventListener('keypress', function() {
                setTimeout(() => validateWebsite(this), 10);
            });
        }
        
        // Form submission validation
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate all required fields
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-destructive');
                } else {
                    field.classList.remove('border-destructive');
                }
            });
            
            // Validate pattern fields
            const patternFields = form.querySelectorAll('[pattern]');
            patternFields.forEach(field => {
                if (field.value && !validatePattern(field)) {
                    isValid = false;
                    field.classList.add('border-destructive');
                } else if (field.value) {
                    field.classList.remove('border-destructive');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Controleer de ingevulde gegevens. Sommige velden zijn ongeldig.');
            }
        });
    }
    
    function validateKVK(input) {
        const value = input.value.replace(/\D/g, '');
        const errorDiv = document.getElementById('kvk_number_error');
        const successDiv = document.getElementById('kvk_number_success');
        
        // Remove all validation classes first
        input.classList.remove('border-destructive', 'border-green-500');
        
        if (!value) {
            // Empty field - no validation
            if (errorDiv) errorDiv.classList.add('hidden');
            if (successDiv) successDiv.classList.add('hidden');
            return true;
        }
        
        if (value.length !== 8) {
            input.classList.add('border-destructive');
            if (errorDiv) {
                errorDiv.textContent = 'KVK nummer moet 8 cijfers bevatten.';
                errorDiv.classList.remove('hidden');
            }
            if (successDiv) successDiv.classList.add('hidden');
            return false;
        } else {
            input.classList.add('border-green-500');
            if (errorDiv) errorDiv.classList.add('hidden');
            if (successDiv) successDiv.classList.remove('hidden');
            return true;
        }
    }
    
    function validatePhone(input) {
        const value = input.value.replace(/\s/g, '');
        const pattern = /^(\+31|0)[1-9][0-9]{8}$/;
        const isValid = !value || pattern.test(value);
        const fieldName = input.getAttribute('name');
        
        // Remove all validation classes first
        input.classList.remove('border-destructive', 'border-green-500');
        
        if (!value) {
            // Hide success messages for empty fields
            if (fieldName === 'phone') {
                const successDiv = document.getElementById('phone_success');
                if (successDiv) successDiv.classList.add('hidden');
            } else if (fieldName && fieldName.includes('locations') && fieldName.includes('phone')) {
                const locationItem = input.closest('.location-item');
                if (locationItem) {
                    const successDiv = locationItem.querySelector('.location-phone-success');
                    if (successDiv) successDiv.classList.add('hidden');
                }
            }
            return true; // Empty is valid (optional field)
        }
        
        if (!isValid) {
            input.classList.add('border-destructive');
            // Hide success messages
            if (fieldName === 'phone') {
                const successDiv = document.getElementById('phone_success');
                if (successDiv) successDiv.classList.add('hidden');
            } else if (fieldName && fieldName.includes('locations') && fieldName.includes('phone')) {
                const locationItem = input.closest('.location-item');
                if (locationItem) {
                    const successDiv = locationItem.querySelector('.location-phone-success');
                    if (successDiv) successDiv.classList.add('hidden');
                }
            }
        } else {
            input.classList.add('border-green-500');
            // Show success message
            if (fieldName === 'phone') {
                const successDiv = document.getElementById('phone_success');
                const errorDiv = document.getElementById('phone_error');
                if (successDiv) successDiv.classList.remove('hidden');
                if (errorDiv) errorDiv.classList.add('hidden');
            } else if (fieldName && fieldName.includes('locations') && fieldName.includes('phone')) {
                const locationItem = input.closest('.location-item');
                if (locationItem) {
                    let successDiv = locationItem.querySelector('.location-phone-success');
                    if (!successDiv) {
                        // Create success message if it doesn't exist
                        const td = input.closest('td');
                        if (td) {
                            successDiv = document.createElement('div');
                            successDiv.className = 'text-xs text-green-600 mt-1 location-phone-success';
                            successDiv.innerHTML = '<i class="ki-filled ki-check-circle me-1"></i> Telefoonnummer is geldig';
                            td.appendChild(successDiv);
                        }
                    }
                    if (successDiv) successDiv.classList.remove('hidden');
                }
            }
        }
        
        return isValid;
    }
    
    function validatePostalCode(input) {
        const value = input.value.replace(/\s/g, '').toUpperCase();
        const pattern = /^[1-9][0-9]{3}[A-Z]{2}$/;
        const isValid = !value || pattern.test(value);
        const fieldName = input.getAttribute('name');
        
        // Remove all validation classes first
        input.classList.remove('border-destructive', 'border-green-500');
        
        if (!value) {
            // Hide success messages for empty fields
            if (fieldName === 'postal_code') {
                const successDiv = document.getElementById('postal_code_success');
                if (successDiv) successDiv.classList.add('hidden');
            } else if (fieldName && fieldName.includes('locations') && fieldName.includes('postal_code')) {
                const locationItem = input.closest('.location-item');
                if (locationItem) {
                    const successDiv = locationItem.querySelector('.location-postal-code-success');
                    if (successDiv) successDiv.classList.add('hidden');
                }
            }
            return true; // Empty is valid (optional field)
        }
        
        if (!isValid) {
            input.classList.add('border-destructive');
            // Hide success messages
            if (fieldName === 'postal_code') {
                const successDiv = document.getElementById('postal_code_success');
                if (successDiv) successDiv.classList.add('hidden');
            } else if (fieldName && fieldName.includes('locations') && fieldName.includes('postal_code')) {
                const locationItem = input.closest('.location-item');
                if (locationItem) {
                    const successDiv = locationItem.querySelector('.location-postal-code-success');
                    if (successDiv) successDiv.classList.add('hidden');
                }
            }
        } else {
            input.classList.add('border-green-500');
            // Show success message
            if (fieldName === 'postal_code') {
                const successDiv = document.getElementById('postal_code_success');
                const errorDiv = document.getElementById('postal_code_error');
                if (successDiv) successDiv.classList.remove('hidden');
                if (errorDiv) errorDiv.classList.add('hidden');
            } else if (fieldName && fieldName.includes('locations') && fieldName.includes('postal_code')) {
                const locationItem = input.closest('.location-item');
                if (locationItem) {
                    let successDiv = locationItem.querySelector('.location-postal-code-success');
                    if (!successDiv) {
                        // Create success message if it doesn't exist
                        const td = input.closest('td');
                        if (td) {
                            successDiv = document.createElement('div');
                            successDiv.className = 'text-xs text-green-600 mt-1 location-postal-code-success';
                            successDiv.innerHTML = '<i class="ki-filled ki-check-circle me-1"></i> Postcode is geldig';
                            td.appendChild(successDiv);
                        }
                    }
                    if (successDiv) successDiv.classList.remove('hidden');
                }
            }
        }
        
        return isValid;
    }
    
    function validateEmail(input) {
        const value = input.value.trim();
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = !value || pattern.test(value);
        const fieldName = input.getAttribute('name');
        
        // Remove all validation classes first
        input.classList.remove('border-destructive', 'border-green-500');
        
        if (!value) {
            // Required fields should show error if empty
            if (input.hasAttribute('required')) {
                input.classList.add('border-destructive');
            }
            // Hide success messages for empty fields
            if (fieldName === 'email') {
                const successDiv = document.getElementById('email_success');
                if (successDiv) successDiv.classList.add('hidden');
            } else if (fieldName && fieldName.includes('locations') && fieldName.includes('email')) {
                const locationItem = input.closest('.location-item');
                if (locationItem) {
                    const successDiv = locationItem.querySelector('.location-email-success');
                    if (successDiv) successDiv.classList.add('hidden');
                }
            }
            return !input.hasAttribute('required');
        }
        
        if (!isValid) {
            input.classList.add('border-destructive');
            // Hide success messages
            if (fieldName === 'email') {
                const errorDiv = document.getElementById('email_error');
                const successDiv = document.getElementById('email_success');
                if (errorDiv) {
                    errorDiv.textContent = 'Voer een geldig e-mailadres in.';
                    errorDiv.classList.remove('hidden');
                }
                if (successDiv) successDiv.classList.add('hidden');
            } else if (fieldName && fieldName.includes('locations') && fieldName.includes('email')) {
                const locationItem = input.closest('.location-item');
                if (locationItem) {
                    const successDiv = locationItem.querySelector('.location-email-success');
                    if (successDiv) successDiv.classList.add('hidden');
                }
            }
        } else {
            input.classList.add('border-green-500');
            // Show success message
            if (fieldName === 'email') {
                const successDiv = document.getElementById('email_success');
                const errorDiv = document.getElementById('email_error');
                if (successDiv) successDiv.classList.remove('hidden');
                if (errorDiv) errorDiv.classList.add('hidden');
            } else if (fieldName && fieldName.includes('locations') && fieldName.includes('email')) {
                const locationItem = input.closest('.location-item');
                if (locationItem) {
                    let successDiv = locationItem.querySelector('.location-email-success');
                    if (!successDiv) {
                        // Create success message if it doesn't exist
                        const td = input.closest('td');
                        if (td) {
                            successDiv = document.createElement('div');
                            successDiv.className = 'text-xs text-green-600 mt-1 location-email-success';
                            successDiv.innerHTML = '<i class="ki-filled ki-check-circle me-1"></i> E-mailadres is geldig';
                            td.appendChild(successDiv);
                        }
                    }
                    if (successDiv) successDiv.classList.remove('hidden');
                }
            }
        }
        
        return isValid;
    }
    
    function validateWebsite(input) {
        const value = input.value.trim();
        
        // Remove all validation classes first
        input.classList.remove('border-destructive', 'border-green-500');
        
        if (!value) {
            return true; // Empty is valid (optional field)
        }
        
        try {
            const url = new URL(value);
            const isValid = url.protocol === 'http:' || url.protocol === 'https:';
            
            if (!isValid) {
                input.classList.add('border-destructive');
            } else {
                input.classList.add('border-green-500');
            }
            
            return isValid;
        } catch (e) {
            input.classList.add('border-destructive');
            return false;
        }
    }
    
    function validatePattern(input) {
        const pattern = new RegExp(input.getAttribute('pattern'));
        const value = input.value.replace(/\s/g, ''); // Remove spaces for validation
        return pattern.test(value);
    }
});
</script>
@endpush

