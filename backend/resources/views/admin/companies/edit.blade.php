@extends('admin.layouts.app')

@section('title', __('Bedrijf Bewerken'))

@section('content')
@include('admin.settings.partials.collapsible-section-assets')

<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            {{ __('Bedrijf Bewerken') }}
        </h1>
        <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            {{ __('Terug') }}
        </a>
    </div>

    @include('admin.companies.wizard.partials.tabs', [
        'company' => $company,
        'currentStep' => $currentStep ?? 1,
        'maxReachable' => $maxReachable ?? \App\Http\Controllers\Admin\AdminCompanyWizardController::TOTAL_STEPS,
        'wizardSteps' => $wizardSteps ?? \App\Http\Controllers\Admin\AdminCompanyWizardController::stepMeta(),
        'wizardBrowse' => $wizardBrowse ?? true,
    ])

    <form action="{{ route('admin.companies.update', $company) }}" method="POST" enctype="multipart/form-data" data-validate="true" novalidate>
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- General Info -->
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">
                        {{ __('Algemene Informatie') }}
                    </h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                {{ __('Bedrijfsnaam *') }}
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="text" 
                                       class="kt-input @error('name') border-destructive @enderror" 
                                       name="name" 
                                       value="{{ old('name', $company->name) }}" 
                                       required>
                                @error('name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        @php
                            $formLogoMode = old('company_logo_mode', ! empty($company->logo_dark_blob) ? 'light_dark' : 'single');
                            $hasFormLogo = (bool) $company->logo_blob;
                            $hasFormLogoDark = ! empty($company->logo_dark_blob);
                            $useFormLightDark = $formLogoMode === 'light_dark';
                            $formLightUrl = $hasFormLogo ? $company->adminLogoLightUrl() : null;
                            $formDarkUrl = ($hasFormLogo && $useFormLightDark && $hasFormLogoDark)
                                ? $company->adminLogoDarkUrl()
                                : $formLightUrl;
                        @endphp
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">{{ __('Logo') }}</td>
                            <td class="min-w-48 w-full align-top">
                                <input type="hidden" name="company_logo_mode" id="company-form-logo-mode-input" value="{{ $formLogoMode }}">

                                <div id="company-edit-logo-collapsible-root">
                                <div class="settings-collapsible-section settings-collapsible-card--collapsed" id="company-edit-logo-collapsible">
                                    <div class="settings-collapsible-header">
                                        <button type="button" class="settings-collapsible-toggle flex w-full items-center justify-between gap-3 border-0 bg-transparent p-0 text-start cursor-pointer min-h-[2.25rem]" aria-expanded="false">
                                            <span class="flex items-center gap-3 flex-1 min-w-0">
                                                @if($formLightUrl)
                                                    <img alt="{{ __('Logo') }}" class="w-auto max-w-[130px] object-contain" style="height: 28px;" src="{{ $formLightUrl }}">
                                                @else
                                                    <span class="text-sm text-muted-foreground">{{ __('Geen logo geüpload') }}</span>
                                                @endif
                                            </span>
                                            <span class="settings-collapsible-chevron shrink-0 self-center text-muted-foreground" aria-hidden="true">
                                                <i class="ki-filled ki-down settings-collapsible-icon-down text-base" aria-hidden="true"></i>
                                                <i class="ki-filled ki-up settings-collapsible-icon-up text-base" aria-hidden="true"></i>
                                            </span>
                                        </button>
                                    </div>
                                    <div class="settings-collapsible-body pt-3">
                                    <p class="text-sm text-muted-foreground mb-3">{{ __('Het logo wordt gebruikt in de admin-sidebar en op de frontend (header en footer).') }}</p>
                                    <div class="flex flex-col gap-2 mb-4">
                                        <span class="text-sm text-muted-foreground wizard-onboarding-form-table w-full">{{ __('Eén logo voor beide modi') }}</span>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <input type="checkbox" id="company-form-logo-mode-toggle" class="kt-switch kt-switch-sm" {{ $formLogoMode === 'light_dark' ? 'checked' : '' }} aria-label="{{ __('Apart logo voor light en dark mode') }}">
                                            <span class="text-sm text-muted-foreground wizard-onboarding-form-table w-full">{{ __('Apart logo voor light en dark mode') }}</span>
                                        </div>
                                    </div>

                                    @if($formLightUrl)
                                        <p class="text-sm font-medium text-muted-foreground mb-2">{{ __('Zo ziet het logo eruit in de sidebar en op de frontend (wisselt mee met light/dark modus)') }}</p>
                                        <div class="flex flex-wrap items-center gap-3 mb-4 p-3 min-w-0">
                                            <img alt="{{ __('Logo light') }}" class="logo-light w-auto max-w-[140px] object-contain dark:hidden" style="height: 35px;" src="{{ $formLightUrl }}" id="company-form-live-preview-light" />
                                            <img alt="{{ __('Logo dark') }}" class="logo-dark w-auto max-w-[140px] object-contain hidden dark:block" style="height: 35px;" src="{{ $formDarkUrl }}" id="company-form-live-preview-dark" />
                                        </div>
                                    @endif

                                    <p class="text-sm font-medium text-muted-foreground mb-2">{{ __('Light mode (standaard)') }}</p>
                                    <div class="w-full max-w-md">
                                        @include('admin.partials.image-upload-dropzone-inline', [
                                            'name' => 'logo',
                                            'inputId' => 'company-form-logo-input',
                                            'previewId' => 'company-form-logo-preview',
                                            'areaId' => 'company-form-logo-upload-area',
                                            'linkId' => 'company-form-logo-upload-link',
                                            'removeBtnId' => 'company-form-logo-remove',
                                            'existingUrl' => $company->logo_blob ? $company->adminLogoLightUrl() : null,
                                            'dropzoneKey' => 'light',
                                            'clientMsgId' => 'company-form-logo-client-msg',
                                            'hintLine' => __('SVG, PNG, JPG (max. 5MB)'),
                                            'maxFileBytes' => 5 * 1024 * 1024,
                                            'livePreviewLightId' => 'company-form-live-preview-light',
                                            'livePreviewDarkId' => 'company-form-live-preview-dark',
                                            'logoModeInputId' => 'company-form-logo-mode-input',
                                        ])
                                    </div>
                                    <div id="company-form-logo-client-msg" class="text-xs mt-1 hidden" role="status" aria-live="polite"></div>
                                    <input type="hidden" name="logo_path" value="{{ old('logo_path', $company->logo_path) }}" id="logo-path-input">
                                    <p class="text-xs text-muted-foreground mt-1 mb-4">{{ __('Ondersteunde formaten: JPEG, PNG, JPG, GIF, SVG (max. 5MB)') }}</p>
                                    @error('logo')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror

                                    <div id="company-form-logo-dark-block" class="{{ $formLogoMode === 'light_dark' ? '' : 'hidden' }}">
                                        <p class="text-sm font-medium text-muted-foreground mb-2">{{ __('Dark mode') }}</p>
                                        <p class="text-xs text-muted-foreground mb-2 max-w-xl">{{ __('Optioneel. Wordt in de admin-sidebar getoond wanneer donker thema actief is. Laat leeg om overal het light mode-logo te gebruiken.') }}</p>
                                        <div class="w-full max-w-md">
                                            @include('admin.partials.image-upload-dropzone-inline', [
                                                'name' => 'logo_dark',
                                                'inputId' => 'company-form-logo-dark-input',
                                                'previewId' => 'company-form-logo-dark-preview',
                                                'areaId' => 'company-form-logo-dark-upload-area',
                                                'linkId' => 'company-form-logo-dark-upload-link',
                                                'removeBtnId' => 'company-form-logo-dark-remove',
                                                'existingUrl' => $company->logo_dark_blob ? $company->adminLogoDarkUrl() : null,
                                                'dropzoneKey' => 'dark',
                                                'clientMsgId' => 'company-form-logo-dark-client-msg',
                                                'hintLine' => __('SVG, PNG, JPG (max. 5MB)'),
                                                'maxFileBytes' => 5 * 1024 * 1024,
                                                'livePreviewDarkId' => 'company-form-live-preview-dark',
                                            ])
                                        </div>
                                        <div id="company-form-logo-dark-client-msg" class="text-xs mt-1 hidden" role="status" aria-live="polite"></div>
                                        <p class="text-xs text-muted-foreground mt-1">{{ __('Ondersteunde formaten: JPEG, PNG, JPG, GIF, SVG (max. 5MB)') }}</p>
                                        @error('logo_dark')
                                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mt-5 pt-4 border-t border-border">
                                        <p class="text-sm font-medium text-muted-foreground mb-2">{{ __('Favicon (website)') }}</p>
                                        <p class="text-xs text-muted-foreground mb-3 max-w-xl">
                                            {{ __('Zichtbaar in het browsertabblad wanneer bezoekers de website van deze tenant openen. Zonder eigen favicon wordt het NEXA Suite-icoon gebruikt.') }}
                                        </p>
                                        <input type="hidden" name="remove_favicon" id="company-form-remove-favicon" value="0">
                                        <div class="w-full max-w-md">
                                            @include('admin.partials.image-upload-dropzone-inline', [
                                                'name' => 'favicon',
                                                'inputId' => 'company-form-favicon-input',
                                                'previewId' => 'company-form-favicon-preview',
                                                'areaId' => 'company-form-favicon-upload-area',
                                                'linkId' => 'company-form-favicon-upload-link',
                                                'removeBtnId' => 'company-form-favicon-remove',
                                                'existingUrl' => $company->favicon_blob ? $company->adminFaviconUrl() : null,
                                                'dropzoneKey' => 'favicon',
                                                'clientMsgId' => 'company-form-favicon-client-msg',
                                                'hintLine' => __('ICO, PNG, JPG, SVG (max. 2MB)'),
                                                'maxFileBytes' => 2 * 1024 * 1024,
                                                'accept' => 'image/x-icon,image/vnd.microsoft.icon,image/png,image/jpeg,image/jpg,image/svg+xml,image/gif,image/webp,.ico',
                                                'removeFlagId' => 'company-form-remove-favicon',
                                            ])
                                        </div>
                                        <div id="company-form-favicon-client-msg" class="text-xs mt-1 hidden" role="status" aria-live="polite"></div>
                                        @error('favicon')
                                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    </div>
                                </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Plaatje gebouw') }}
                            </td>
                            <td>
                                @include('admin.partials.building-image-select', ['company' => $company])
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('KVK Nummer') }}
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('kvk_number') border-destructive @enderror" 
                                       name="kvk_number" 
                                       value="{{ old('kvk_number', $company->kvk_number) }}"
                                       pattern="[0-9]{8}"
                                       placeholder="12345678"
                                       maxlength="8">
                                <div class="text-xs text-muted-foreground mt-1">{{ __('8 cijfers (bijv. 12345678)') }}</div>
                                @error('kvk_number')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="kvk_number_error"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Branche') }}
                            </td>
                            <td>
                                @php
                                    $currentIndustry = old('industry', $company->industry);
                                    $selectedBranch = $branches->firstWhere('name', $currentIndustry);
                                    $isOther = $currentIndustry && !$selectedBranch;
                                @endphp
                                <select class="kt-input @error('industry') border-destructive @enderror" 
                                        name="branch_select" 
                                        id="branch_select">
                                    <option value="">{{ __('-- Selecteer branche --') }}</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->name }}" 
                                                {{ $currentIndustry === $branch->name ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                    <option value="other" {{ $isOther ? 'selected' : '' }}>{{ __('Anders') }}</option>
                                </select>
                                <input type="text" 
                                       class="kt-input @error('industry') border-destructive @enderror mt-2 {{ $isOther ? '' : 'hidden' }}" 
                                       name="industry" 
                                       id="industry_custom"
                                       value="{{ $isOther ? $currentIndustry : '' }}"
                                       placeholder="{{ __('Voer branche in') }}">
                                @error('industry')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                {{ __('Bedrijfstype') }}
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <input class="kt-checkbox" 
                                           type="checkbox" 
                                           id="is_intermediary" 
                                           name="is_intermediary" 
                                           value="1" 
                                           {{ old('is_intermediary', $company->is_intermediary) ? 'checked' : '' }}>
                                    <label for="is_intermediary" class="text-sm font-normal mb-0">
                                        {{ __('Tussenpartij / Recruiter') }}
                                    </label>
                                </div>
                                @error('is_intermediary')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Hoofdkantoor (wizard)') }}
                            </td>
                            <td>
                                <label class="kt-label flex items-center gap-2 mb-0">
                                    <input type="checkbox" name="is_main" value="1" class="kt-switch kt-switch-sm" {{ old('is_main', $company->is_main) ? 'checked' : '' }}>
                                    <span class="text-sm text-muted-foreground wizard-onboarding-form-table w-full">{{ __('Dit bedrijf gebruikt het adres uit stap Bedrijf als hoofdvestiging (zoals in de tenant-wizard).') }}</span>
                                </label>
                                @error('is_main')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Actief') }}
                            </td>
                            <td>
                                <label class="kt-label flex items-center gap-2 mb-0">
                                    <input type="checkbox" name="is_active" value="1" class="kt-switch kt-switch-sm" {{ old('is_active', $company->is_active) ? 'checked' : '' }}>
                                    <span class="text-sm text-muted-foreground wizard-onboarding-form-table w-full">{{ __('Bedrijf is actief in het systeem.') }}</span>
                                </label>
                                @error('is_active')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Nexa Suite-boekingen accepteren') }}
                            </td>
                            <td>
                                <input type="hidden" name="accepts_nexa_suite_bookings" value="0">
                                <label class="kt-label flex items-center gap-2 mb-0" for="accepts_nexa_suite_bookings">
                                    <input type="checkbox"
                                           name="accepts_nexa_suite_bookings"
                                           id="accepts_nexa_suite_bookings"
                                           value="1"
                                           class="kt-switch kt-switch-sm shrink-0"
                                           {{ old('accepts_nexa_suite_bookings', $company->accepts_nexa_suite_bookings ?? true) ? 'checked' : '' }}>
                                    <span class="text-sm text-muted-foreground">{{ __('Ontvang ritten vanaf nexasuite.nl / de marktplaats (bij een online chauffeur binnen de straal).') }}</span>
                                </label>
                                @error('accepts_nexa_suite_bookings')
                                    <p class="text-sm text-destructive mt-1 mb-0 laravel-inline-error"
                                       data-laravel-field="accepts_nexa_suite_bookings"
                                       data-laravel-message="{{ $message }}">{{ $message }}</p>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Website') }}
                            </td>
                            <td>
                                <input type="url" 
                                       class="kt-input @error('website') border-destructive @enderror" 
                                       name="website" 
                                       value="{{ old('website', $company->website) }}"
                                       placeholder="{{ __('https://') }}">
                                @error('website')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Beschrijving') }}
                            </td>
                            <td>
                                <textarea class="kt-input @error('description') border-destructive @enderror" 
                                          name="description" 
                                          rows="4">{{ old('description', $company->description) }}</textarea>
                                @error('description')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">
                        {{ __('Contact Informatie') }}
                    </h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                {{ __('E-mail *') }}
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="email" 
                                       class="kt-input @error('email') border-destructive @enderror" 
                                       name="email" 
                                       value="{{ old('email', $company->email) }}" 
                                       required
                                       autocomplete="email">
                                @error('email')
                                    <div class="text-xs text-destructive mt-1 laravel-inline-error" data-laravel-field="email" data-laravel-message="{{ $message }}" role="alert">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="email_error"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Telefoon *') }}
                            </td>
                            <td>
                                <input type="tel" 
                                       class="kt-input @error('phone') border-destructive @enderror" 
                                       name="phone" 
                                       value="{{ old('phone', $company->phone) }}"
                                       required
                                       pattern="(\+31|0)[1-9][0-9]{8}"
                                       placeholder="{{ __('0612345678 of +31612345678') }}"
                                       maxlength="13">
                                <div class="text-xs text-muted-foreground mt-1">{{ __('Nederlands nummer (bijv. 0612345678 of +31612345678)') }}</div>
                                @error('phone')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="phone_error"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Contactpersoon voornaam') }}
                            </td>
                            <td>
                                <input type="text" class="kt-input @error('contact_first_name') border-destructive @enderror" name="contact_first_name" value="{{ old('contact_first_name', $company->contact_first_name) }}" maxlength="255" autocomplete="given-name">
                                @error('contact_first_name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Contactpersoon tussenvoegsel') }}
                            </td>
                            <td>
                                <input type="text" class="kt-input @error('contact_middle_name') border-destructive @enderror" name="contact_middle_name" value="{{ old('contact_middle_name', $company->contact_middle_name) }}" maxlength="255">
                                @error('contact_middle_name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Contactpersoon achternaam') }}
                            </td>
                            <td>
                                <input type="text" class="kt-input @error('contact_last_name') border-destructive @enderror" name="contact_last_name" value="{{ old('contact_last_name', $company->contact_last_name) }}" maxlength="255" autocomplete="family-name">
                                @error('contact_last_name')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                {{ __('Postcode *') }}
                            </td>
                            <td>
                                <input type="text" 
                                       id="postal_code"
                                       class="kt-input @error('postal_code') border-destructive @enderror" 
                                       name="postal_code" 
                                       value="{{ old('postal_code', $company->postal_code) }}"
                                       required
                                       pattern="[1-9][0-9]{3}\s?[A-Za-z]{2}"
                                       placeholder="{{ __('1234AB') }}"
                                       maxlength="7"
                                       style="text-transform: uppercase;">
                                <div class="text-xs text-muted-foreground mt-1">{{ __('Nederlandse postcode (bijv. 1234AB). Bij verlaten van het veld wordt het adres automatisch opgezocht.') }}</div>
                                @error('postal_code')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="postal_code_error"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                {{ __('Huisnummer *') }}
                            </td>
                            <td>
                                <input type="text" 
                                       id="house_number"
                                       class="kt-input @error('house_number') border-destructive @enderror" 
                                       name="house_number" 
                                       required
                                       value="{{ old('house_number', $company->house_number) }}">
                                <div class="text-xs text-muted-foreground mt-1">{{ __('Bij verlaten van het veld wordt straat en plaats automatisch ingevuld.') }}</div>
                                @include('admin.partials.postcode-lookup-status', ['id' => 'house_number_lookup_loading'])
                                @error('house_number')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                {{ __('Straat *') }}
                            </td>
                            <td>
                                @php
                                    $hasAddress = trim(old('street', $company->street ?? '') . old('city', $company->city ?? '')) !== '';
                                @endphp
                                <input type="text" 
                                       id="street"
                                       class="kt-input @error('street') border-destructive @enderror" 
                                       name="street" 
                                       required
                                       value="{{ old('street', $company->street) }}"
                                       @if($hasAddress) readonly @endif>
                                <div class="text-xs text-muted-foreground mt-1">{{ __('Wordt automatisch ingevuld bij postcode + huisnummer. Bij geen resultaat worden de velden bewerkbaar.') }}</div>
                                @error('street')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                {{ __('Plaats *') }}
                            </td>
                            <td>
                                <input type="text" 
                                       id="city"
                                       class="kt-input @error('city') border-destructive @enderror" 
                                       name="city" 
                                       required
                                       value="{{ old('city', $company->city) }}"
                                       @if($hasAddress) readonly @endif>
                                @error('city')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                {{ __('Land') }}
                            </td>
                            <td>
                                <input type="text" 
                                       id="country"
                                       class="kt-input @error('country') border-destructive @enderror" 
                                       name="country" 
                                       value="{{ old('country', $company->country) }}"
                                       @if($hasAddress) readonly @endif>
                                @error('country')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    </div>
                </div>
            </div>

            @if(auth()->user()?->isSuperAdmin())
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5">
                    <h3 class="kt-card-title mb-0">{{ __('Pakket') }}</h3>
                </div>
                <div class="kt-card-content flex flex-col gap-4 p-5">
                    <p class="text-sm text-secondary-foreground mb-0 leading-relaxed">
                        {!! __('Dit pakket en de aanvullende modules zijn leidend voor Tenant-abonnementen en de maandfactuur. Functies stel je in bij Systeem → :link.', ['link' => '<a href="'.e(route('admin.nexa-pricing.edit')).'" class="text-primary underline underline-offset-2">'.e(__('Paketten')).'</a>']) !!}
                    </p>
                    <div class="min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">{{ __('Pakket') }}</td>
                            <td class="min-w-48 w-full">
                                <select name="package_key" class="kt-input @error('package_key') border-destructive @enderror">
                                    <option value="">{{ __('— Geen pakket (geen extra beperking) —') }}</option>
                                    @foreach($nexaPackages ?? [] as $packageKey => $packageName)
                                        <option value="{{ $packageKey }}" {{ (string) old('package_key', $company->package_key) === (string) $packageKey ? 'selected' : '' }}>
                                            {{ $packageName }} ({{ $packageKey }})
                                        </option>
                                    @endforeach
                                </select>
                                @if(empty($nexaPackages))
                                    <p class="text-xs text-muted-foreground mt-2 mb-0">{!! __('Er zijn nog geen pakketten. Ga naar :link.', ['link' => '<a href="'.e(route('admin.nexa-pricing.edit')).'" class="text-primary underline">'.e(__('Paketten')).'</a>']) !!}</p>
                                @endif
                                @error('package_key')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        @include('admin.companies.partials.package-addons', ['company' => $company])
                    </table>
                    </div>
                </div>
            </div>
            @endif

            @can('edit-companies')
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">{{ __('Website-thema') }}</h3>
                </div>
                <div class="kt-card-content flex flex-col gap-4">
                    <p class="text-sm text-secondary-foreground mb-0 leading-relaxed">
                        {{ __('Bepaalt het uiterlijk van de tenant-website en wordt automatisch gebruikt bij nieuwe website-pagina\'s voor dit bedrijf. Alleen gepubliceerde thema\'s zijn kiesbaar (Frontend Thema\'s → Activeren).') }}
                    </p>
                    <div class="min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">{{ __('Thema') }}</td>
                            <td class="min-w-48 w-full">
                                <select name="frontend_theme_id" class="kt-input @error('frontend_theme_id') border-destructive @enderror">
                                    <option value="">{{ __('— Geen thema —') }}</option>
                                    @foreach($publishedFrontendThemes ?? [] as $theme)
                                        <option value="{{ $theme->id }}" {{ (string) old('frontend_theme_id', $company->frontend_theme_id) === (string) $theme->id ? 'selected' : '' }}>
                                            {{ $theme->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if(($publishedFrontendThemes ?? collect())->isEmpty())
                                    <p class="text-xs text-muted-foreground mt-2 mb-0">{!! __('Er is nog geen thema gepubliceerd. Ga naar :link en klik op Activeren.', ['link' => '<a href="'.e(route('admin.frontend-themes.index')).'" class="text-primary underline">'.e(__('Frontend Thema\'s')).'</a>']) !!}</p>
                                @endif
                                @error('frontend_theme_id')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                    </div>
                </div>
            </div>

            @if(auth()->user()?->hasRole('super-admin'))
            <div class="kt-card w-full min-w-0 @if($errors->has('module_ids') || $errors->has('module_ids.*')) border border-destructive @endif" id="company-modules" data-required-checkbox-group="module_ids[]">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">{{ __('Modules voor deze tenant') }}</h3>
                </div>
                <div class="kt-card-content flex flex-col gap-4">
                    <p class="text-sm text-secondary-foreground mb-0 leading-relaxed">
                        {{ __('Zelfde keuze als in de tenant-wizard (stap 4). Niet-geïnstalleerde of niet-actieve modules worden bij opslaan geïnstalleerd en geactiveerd waar mogelijk.') }}
                    </p>
                <input type="hidden" name="apply_module_sync" value="1">
                @php
                    $selectedModuleIds = [];
                    $oldModuleIdsState = old('module_ids_state');
                    if (is_string($oldModuleIdsState) && $oldModuleIdsState !== '') {
                        $selectedModuleIds = collect(explode(',', $oldModuleIdsState))
                            ->map(static fn($id) => (int) trim((string) $id))
                            ->filter(static fn($id) => $id > 0)
                            ->values()
                            ->all();
                    } elseif (old('module_ids') !== null) {
                        $selectedModuleIds = collect((array) old('module_ids', []))
                            ->map(static fn($id) => (int) $id)
                            ->filter(static fn($id) => $id > 0)
                            ->values()
                            ->all();
                    } else {
                        $selectedModuleIds = $company->modules->pluck('id')
                            ->map(static fn($id) => (int) $id)
                            ->values()
                            ->all();
                    }
                @endphp
                <input type="hidden" name="module_ids_state" id="module_ids_state" value="{{ implode(',', $selectedModuleIds) }}">
                @if(($allModules ?? collect())->isEmpty())
                        <p class="text-sm text-muted-foreground mb-0 rounded-lg border border-dashed border-input px-4 py-3">
                            {!! __('Er zijn nog geen modules in de database. Registreer modules via :link.', ['link' => '<a href="'.e(route('admin.modules.index')).'" class="font-medium text-primary underline underline-offset-2 hover:text-primary/90">'.e(__('Modules')).'</a>']) !!}
                        </p>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach($allModules as $mod)
                            <label class="flex items-center gap-3 rounded-xl border border-input bg-muted/15 px-4 py-3.5 cursor-pointer min-w-0">
                                <input type="checkbox"
                                       class="kt-switch kt-switch-sm shrink-0"
                                       data-checkbox-group="module_ids[]"
                                       name="module_ids[]"
                                       value="{{ $mod->id }}"
                                       {{ in_array((int) $mod->id, $selectedModuleIds, true) ? 'checked' : '' }}>
                                <span class="flex flex-col gap-1 min-w-0 flex-1">
                                    <span class="font-semibold text-sm text-foreground leading-snug">{{ $mod->display_name }}</span>
                                    <span class="text-xs text-secondary-foreground">{{ $mod->name }}</span>
                                    @if($mod->installed && $mod->active)
                                        <span class="kt-badge kt-badge-sm kt-badge-success w-fit">{{ __('Actief') }}</span>
                                    @elseif($mod->installed)
                                        <span class="kt-badge kt-badge-sm kt-badge-warning w-fit">{{ __('Geïnstalleerd') }}</span>
                                    @else
                                        <span class="kt-badge kt-badge-sm kt-badge-outline w-fit">{{ __('Niet geïnstalleerd') }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div id="module-validation-wrapper" class="hidden">
                        <div class="field-feedback text-xs text-destructive mt-1" data-field="module_ids[]">
                            {{ __('Selecteer minimaal één module.') }}
                        </div>
                    </div>
                    @error('module_ids')
                        <div class="text-xs text-destructive">{{ $message }}</div>
                    @enderror
                    @if($errors->has('module_ids.*'))
                        <div class="text-xs text-destructive">{{ $errors->first('module_ids.*') }}</div>
                    @endif
                @endif
                </div>
            </div>
            @endif
            @endcan
        </div>

        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0">
            <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-cross me-2"></i>
                {{ __('Annuleren') }}
            </a>
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check me-2"></i>
                {{ __('Wijzigingen Opslaan') }}
            </button>
        </div>
    </form>
</div>

@endsection

@push('styles')
<style>
    #company-modules label:has(.kt-switch) {
        cursor: pointer;
    }
    #company-modules .kt-switch {
        pointer-events: auto !important;
        z-index: 1;
        position: relative;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script src="{{ asset('js/admin-postcode-lookup.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.bindAdminPostcodeLookup === 'function') {
            window.bindAdminPostcodeLookup({
                postcode: 'postal_code',
                huisnummer: 'house_number',
                street: 'street',
                city: 'city',
                country: 'country',
                loading: ['house_number_lookup_loading'],
                url: @json(route('admin.postcode.lookup'))
            });
        }

        @include('admin.partials.logo-dropzone-init-inner')

        (function() {
            var modeToggle = document.getElementById('company-form-logo-mode-toggle');
            var modeInput = document.getElementById('company-form-logo-mode-input');
            var darkBlock = document.getElementById('company-form-logo-dark-block');
            if (modeToggle && modeInput && darkBlock) {
                modeToggle.addEventListener('change', function() {
                    var isLightDark = modeToggle.checked;
                    modeInput.value = isLightDark ? 'light_dark' : 'single';
                    darkBlock.classList.toggle('hidden', !isLightDark);
                    if (!isLightDark) {
                        var darkInput = document.getElementById('company-form-logo-dark-input');
                        if (darkInput) darkInput.value = '';
                        var liveLight = document.getElementById('company-form-live-preview-light');
                        var liveDark = document.getElementById('company-form-live-preview-dark');
                        if (liveLight && liveDark && liveLight.src) {
                            liveDark.src = liveLight.src;
                        }
                        if (typeof window.syncAdminLogoVisibility === 'function') {
                            window.syncAdminLogoVisibility();
                        }
                    }
                });
            }
        })();

        (function() {
            var moduleCheckboxes = document.querySelectorAll('input[name="module_ids[]"]');
            var moduleStateInput = document.getElementById('module_ids_state');
            if (!moduleStateInput || moduleCheckboxes.length === 0) return;

            function syncModuleState() {
                var selected = Array.from(moduleCheckboxes)
                    .filter(function(checkbox) { return checkbox.checked; })
                    .map(function(checkbox) { return checkbox.value; });
                moduleStateInput.value = selected.join(',');
            }

            moduleCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', syncModuleState);
            });

            var form = moduleStateInput.closest('form');
            if (form) {
                form.addEventListener('submit', syncModuleState);
            }

            syncModuleState();
        })();

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
        // Live inline validatie verloopt via assets/js/form-validation.js (zelfde patroon als admin/users/create).
});
</script>
@endpush

