@extends('admin.layouts.app')

@section('title', 'Bedrijf Bewerken')

@section('content')

<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">
            Bedrijf Bewerken
        </h1>
        <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug
        </a>
    </div>

    <form action="{{ route('admin.companies.update', $company) }}" method="POST" enctype="multipart/form-data" data-validate="true" novalidate>
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            @if(session('error'))
                <div class="kt-alert kt-alert-danger" role="alert">
                    <i class="ki-filled ki-cross-circle me-2"></i>
                    {{ session('error') }}
                </div>
            @endif

            <!-- General Info -->
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">
                        Algemene Informatie
                    </h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Bedrijfsnaam *
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
                            $formLightUrl = $hasFormLogo ? route('admin.companies.logo', $company) : null;
                            $formDarkUrl = ($hasFormLogo && $useFormLightDark && $hasFormLogoDark)
                                ? route('admin.companies.logo.dark', $company)
                                : $formLightUrl;
                        @endphp
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Logo</td>
                            <td class="min-w-48 w-full align-top">
                                <input type="hidden" name="company_logo_mode" id="company-form-logo-mode-input" value="{{ $formLogoMode }}">

                                <div class="mb-0">
                                    <p class="text-sm text-muted-foreground mb-3">Het logo wordt gebruikt in de admin-sidebar en op de frontend (header en footer).</p>
                                    <div class="flex flex-col gap-2 mb-4">
                                        <span class="text-sm text-muted-foreground wizard-onboarding-form-table w-full">Eén logo voor beide modi</span>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <input type="checkbox" id="company-form-logo-mode-toggle" class="kt-switch kt-switch-sm" {{ $formLogoMode === 'light_dark' ? 'checked' : '' }} aria-label="Apart logo voor light en dark mode">
                                            <span class="text-sm text-muted-foreground wizard-onboarding-form-table w-full">Apart logo voor light en dark mode</span>
                                        </div>
                                    </div>

                                    @if($formLightUrl)
                                        <p class="text-sm font-medium text-muted-foreground mb-2">Zo ziet het logo eruit in de sidebar en op de frontend (wisselt mee met light/dark modus)</p>
                                        <div class="flex flex-wrap items-center gap-3 mb-4 p-3 rounded-lg border border-border bg-muted/30 min-w-0">
                                            <img alt="Logo light" class="logo-light w-auto max-w-[140px] object-contain dark:hidden" style="height: 35px;" src="{{ $formLightUrl }}" id="company-form-live-preview-light" />
                                            <img alt="Logo dark" class="logo-dark w-auto max-w-[140px] object-contain hidden dark:block" style="height: 35px;" src="{{ $formDarkUrl }}" id="company-form-live-preview-dark" />
                                        </div>
                                    @endif

                                    <p class="text-sm font-medium text-muted-foreground mb-2">Light mode (standaard)</p>
                                    <div class="w-full max-w-md">
                                        @include('admin.partials.image-upload-dropzone-inline', [
                                            'name' => 'logo',
                                            'inputId' => 'company-form-logo-input',
                                            'previewId' => 'company-form-logo-preview',
                                            'areaId' => 'company-form-logo-upload-area',
                                            'linkId' => 'company-form-logo-upload-link',
                                            'removeBtnId' => 'company-form-logo-remove',
                                            'existingUrl' => $company->logo_blob ? route('admin.companies.logo', $company) : null,
                                            'dropzoneKey' => 'light',
                                            'clientMsgId' => 'company-form-logo-client-msg',
                                            'hintLine' => 'SVG, PNG, JPG (max. 5MB)',
                                            'maxFileBytes' => 5 * 1024 * 1024,
                                            'livePreviewLightId' => 'company-form-live-preview-light',
                                            'livePreviewDarkId' => 'company-form-live-preview-dark',
                                            'logoModeInputId' => 'company-form-logo-mode-input',
                                        ])
                                    </div>
                                    <div id="company-form-logo-client-msg" class="text-xs mt-1 hidden" role="status" aria-live="polite"></div>
                                    <input type="hidden" name="logo_path" value="{{ old('logo_path', $company->logo_path) }}" id="logo-path-input">
                                    <p class="text-xs text-muted-foreground mt-1 mb-4">Ondersteunde formaten: JPEG, PNG, JPG, GIF, SVG (max. 5MB)</p>
                                    @error('logo')
                                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                    @enderror

                                    <div id="company-form-logo-dark-block" class="{{ $formLogoMode === 'light_dark' ? '' : 'hidden' }}">
                                        <p class="text-sm font-medium text-muted-foreground mb-2">Dark mode</p>
                                        <p class="text-xs text-muted-foreground mb-2 max-w-xl">Optioneel. Wordt in de admin-sidebar getoond wanneer donker thema actief is. Laat leeg om overal het light mode-logo te gebruiken.</p>
                                        <div class="w-full max-w-md">
                                            @include('admin.partials.image-upload-dropzone-inline', [
                                                'name' => 'logo_dark',
                                                'inputId' => 'company-form-logo-dark-input',
                                                'previewId' => 'company-form-logo-dark-preview',
                                                'areaId' => 'company-form-logo-dark-upload-area',
                                                'linkId' => 'company-form-logo-dark-upload-link',
                                                'removeBtnId' => 'company-form-logo-dark-remove',
                                                'existingUrl' => $company->logo_dark_blob ? route('admin.companies.logo.dark', $company) : null,
                                                'dropzoneKey' => 'dark',
                                                'clientMsgId' => 'company-form-logo-dark-client-msg',
                                                'hintLine' => 'SVG, PNG, JPG (max. 5MB)',
                                                'maxFileBytes' => 5 * 1024 * 1024,
                                                'livePreviewDarkId' => 'company-form-live-preview-dark',
                                            ])
                                        </div>
                                        <div id="company-form-logo-dark-client-msg" class="text-xs mt-1 hidden" role="status" aria-live="polite"></div>
                                        <p class="text-xs text-muted-foreground mt-1">Ondersteunde formaten: JPEG, PNG, JPG, GIF, SVG (max. 5MB)</p>
                                        @error('logo_dark')
                                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Plaatje gebouw
                            </td>
                            <td>
                                @include('admin.partials.building-image-select', ['company' => $company])
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
                                       value="{{ old('kvk_number', $company->kvk_number) }}"
                                       pattern="[0-9]{8}"
                                       placeholder="12345678"
                                       maxlength="8">
                                <div class="text-xs text-muted-foreground mt-1">8 cijfers (bijv. 12345678)</div>
                                @error('kvk_number')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="kvk_number_error"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Branche
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
                                           {{ old('is_intermediary', $company->is_intermediary) ? 'checked' : '' }}>
                                    <label for="is_intermediary" class="text-sm font-normal mb-0">
                                        Tussenpartij / Recruiter
                                    </label>
                                </div>
                                @error('is_intermediary')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Hoofdkantoor (wizard)
                            </td>
                            <td>
                                <label class="kt-label flex items-center gap-2 mb-0">
                                    <input type="checkbox" name="is_main" value="1" class="kt-switch kt-switch-sm" {{ old('is_main', $company->is_main) ? 'checked' : '' }}>
                                    <span class="text-sm text-muted-foreground wizard-onboarding-form-table w-full">Dit bedrijf gebruikt het adres uit stap Bedrijf als hoofdvestiging (zoals in de tenant-wizard).</span>
                                </label>
                                @error('is_main')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Actief
                            </td>
                            <td>
                                <label class="kt-label flex items-center gap-2 mb-0">
                                    <input type="checkbox" name="is_active" value="1" class="kt-switch kt-switch-sm" {{ old('is_active', $company->is_active) ? 'checked' : '' }}>
                                    <span class="text-sm text-muted-foreground wizard-onboarding-form-table w-full">Bedrijf is actief in het systeem.</span>
                                </label>
                                @error('is_active')
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
                                       value="{{ old('website', $company->website) }}"
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
                        Contact Informatie
                    </h3>
                </div>
                <div class="kt-card-content p-0">
                    <div class="px-3 sm:px-5 pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                E-mail *
                            </td>
                            <td class="min-w-48 w-full">
                                <input type="email" 
                                       class="kt-input @error('email') border-destructive @enderror" 
                                       name="email" 
                                       value="{{ old('email', $company->email) }}" 
                                       required
                                       autocomplete="email">
                                @error('email')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="email_error"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Telefoon *
                            </td>
                            <td>
                                <input type="tel" 
                                       class="kt-input @error('phone') border-destructive @enderror" 
                                       name="phone" 
                                       value="{{ old('phone', $company->phone) }}"
                                       required
                                       pattern="(\+31|0)[1-9][0-9]{8}"
                                       placeholder="0612345678 of +31612345678"
                                       maxlength="13">
                                <div class="text-xs text-muted-foreground mt-1">Nederlands nummer (bijv. 0612345678 of +31612345678)</div>
                                @error('phone')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="phone_error"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Contactpersoon voornaam
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
                                Contactpersoon tussenvoegsel
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
                                Contactpersoon achternaam
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
                                Postcode *
                            </td>
                            <td>
                                <input type="text" 
                                       id="postal_code"
                                       class="kt-input @error('postal_code') border-destructive @enderror" 
                                       name="postal_code" 
                                       value="{{ old('postal_code', $company->postal_code) }}"
                                       required
                                       pattern="[1-9][0-9]{3}\s?[A-Za-z]{2}"
                                       placeholder="1234AB"
                                       maxlength="7"
                                       style="text-transform: uppercase;">
                                <div class="text-xs text-muted-foreground mt-1">Nederlandse postcode (bijv. 1234AB). Bij verlaten van het veld wordt het adres automatisch opgezocht.</div>
                                @error('postal_code')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="postal_code_error"></div>
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
                                       required
                                       value="{{ old('house_number', $company->house_number) }}">
                                <div class="text-xs text-muted-foreground mt-1">Bij verlaten van het veld wordt straat en plaats automatisch ingevuld.</div>
                                @error('house_number')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Straat *
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
                                <div class="text-xs text-muted-foreground mt-1">Wordt automatisch ingevuld bij postcode + huisnummer. Bij geen resultaat worden de velden bewerkbaar.</div>
                                @error('street')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">
                                Plaats *
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
                                Land
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

            @can('edit-companies')
            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Website-thema</h3>
                </div>
                <div class="kt-card-content flex flex-col gap-4">
                    <p class="text-sm text-secondary-foreground mb-0 leading-relaxed">
                        Bepaalt het uiterlijk van de tenant-website en wordt automatisch gebruikt bij nieuwe website-pagina's voor dit bedrijf. Alleen gepubliceerde thema's zijn kiesbaar (Frontend Thema's → Activeren).
                    </p>
                    <div class="min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Thema</td>
                            <td class="min-w-48 w-full">
                                <select name="frontend_theme_id" class="kt-input @error('frontend_theme_id') border-destructive @enderror">
                                    <option value="">— Geen thema —</option>
                                    @foreach($publishedFrontendThemes ?? [] as $theme)
                                        <option value="{{ $theme->id }}" {{ (string) old('frontend_theme_id', $company->frontend_theme_id) === (string) $theme->id ? 'selected' : '' }}>
                                            {{ $theme->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if(($publishedFrontendThemes ?? collect())->isEmpty())
                                    <p class="text-xs text-muted-foreground mt-2 mb-0">Er is nog geen thema gepubliceerd. Ga naar <a href="{{ route('admin.frontend-themes.index') }}" class="text-primary underline">Frontend Thema's</a> en klik op Activeren.</p>
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

            <div class="kt-card w-full min-w-0 @if($errors->has('module_ids') || $errors->has('module_ids.*')) border border-destructive @endif" id="company-modules" data-required-checkbox-group="module_ids[]">
                <div class="kt-card-header">
                    <h3 class="kt-card-title mb-0">Modules voor deze tenant</h3>
                </div>
                <div class="kt-card-content flex flex-col gap-4">
                    <p class="text-sm text-secondary-foreground mb-0 leading-relaxed">
                        Zelfde keuze als in de tenant-wizard (stap 4). Niet-geïnstalleerde of niet-actieve modules worden bij opslaan geïnstalleerd en geactiveerd waar mogelijk.
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
                            Er zijn nog geen modules in de database. Registreer modules via <a href="{{ route('admin.modules.index') }}" class="font-medium text-primary underline underline-offset-2 hover:text-primary/90">Modules</a>.
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
                                        <span class="kt-badge kt-badge-sm kt-badge-success w-fit">Actief</span>
                                    @elseif($mod->installed)
                                        <span class="kt-badge kt-badge-sm kt-badge-warning w-fit">Geïnstalleerd</span>
                                    @else
                                        <span class="kt-badge kt-badge-sm kt-badge-outline w-fit">Niet geïnstalleerd</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div id="module-validation-wrapper" class="hidden">
                        <div class="field-feedback text-xs text-destructive mt-1" data-field="module_ids[]">
                            Selecteer minimaal één module.
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
            @endcan
        </div>

        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 mt-5 w-full min-w-0">
            <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-cross me-2"></i>
                Annuleren
            </a>
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check me-2"></i>
                Wijzigingen Opslaan
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Contact address: postcode + huisnummer lookup on blur
        (function() {
            const postalCodeInput = document.getElementById('postal_code');
            const houseNumberInput = document.getElementById('house_number');
            const streetInput = document.getElementById('street');
            const cityInput = document.getElementById('city');
            const countryInput = document.getElementById('country');
            if (!postalCodeInput || !houseNumberInput || !streetInput || !cityInput) return;

            let lookupTimeout;
            function lookupContactAddress() {
                const postcode = postalCodeInput.value.trim().toUpperCase().replace(/\s+/g, '');
                const huisnummer = houseNumberInput.value.trim();
                if (!/^[1-9][0-9]{3}[A-Z]{2}$/.test(postcode) || !huisnummer) return;

                clearTimeout(lookupTimeout);
                lookupTimeout = setTimeout(function() {
                    fetch('{{ route('admin.postcode.lookup') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ postcode: postcode, huisnummer: huisnummer })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            streetInput.value = data.street || '';
                            cityInput.value = data.city || '';
                            if (countryInput) countryInput.value = data.country || 'Nederland';
                            streetInput.setAttribute('readonly', 'readonly');
                            cityInput.setAttribute('readonly', 'readonly');
                            if (countryInput) countryInput.setAttribute('readonly', 'readonly');

                            // Als velden automatisch zijn ingevuld via postcodecheck:
                            // direct als geldig markeren (groene vink) en foutmeldingen verbergen.
                            var validator = postalCodeInput.closest('form')?._formValidator;
                            [streetInput, cityInput, countryInput].forEach(function(field) {
                                if (!field) return;
                                field.dataset.userInteracted = 'true';
                                field.dispatchEvent(new Event('input', { bubbles: true }));
                                if (validator && typeof validator.validateField === 'function') {
                                    validator.validateField(field, null, true);
                                }
                            });
                        } else {
                            streetInput.removeAttribute('readonly');
                            cityInput.removeAttribute('readonly');
                            if (countryInput) countryInput.removeAttribute('readonly');
                        }
                    })
                    .catch(function() {
                        streetInput.removeAttribute('readonly');
                        cityInput.removeAttribute('readonly');
                        if (countryInput) countryInput.removeAttribute('readonly');
                    });
                }, 300);
            }

            postalCodeInput.addEventListener('blur', lookupContactAddress);
            houseNumberInput.addEventListener('blur', lookupContactAddress);
        })();

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

