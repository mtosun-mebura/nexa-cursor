@extends('admin.companies.wizard.layout')

@section('title', 'Stap 1 — Bedrijf & logo')

@section('wizard_content')
<form method="post" enctype="multipart/form-data" data-validate="true" novalidate action="{{ $company ? route('admin.companies.wizard.submit-step', [$company, 1]) : route('admin.companies.wizard.store-step1') }}">
    @csrf

    <x-error-card :errors="$errors" />

    <div class="kt-card min-w-full mb-6">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Bedrijfsgegevens & logo</h3>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Bedrijfsnaam *</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input @error('name') border-destructive @enderror" name="name" value="{{ old('name', $company->name ?? '') }}" required minlength="2" maxlength="255" @error('name') data-server-error="1" @enderror>
                        @error('name')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="name">{{ $message }}</div>@enderror
                    </td>
                </tr>
                @php
                    $wizardLogoMode = old('company_logo_mode', ($company && ! empty($company->logo_dark_blob)) ? 'light_dark' : 'single');
                    $hasWizLogo = $company && $company->logo_blob;
                    $hasWizLogoDark = $company && ! empty($company->logo_dark_blob);
                    $useWizLightDark = $wizardLogoMode === 'light_dark';
                    $wizLightUrl = $hasWizLogo ? route('admin.companies.logo', $company) : null;
                    $wizDarkUrl = ($hasWizLogo && $useWizLightDark && $hasWizLogoDark)
                        ? route('admin.companies.logo.dark', $company)
                        : $wizLightUrl;
                @endphp
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Logo</td>
                    <td class="min-w-48 w-full align-top">
                        <input type="hidden" name="company_logo_mode" id="company-wizard-logo-mode-input" value="{{ $wizardLogoMode }}">

                        <div class="mb-0">
                                <p class="text-sm text-muted-foreground mb-3">Het logo wordt gebruikt in de admin-sidebar en op de frontend (header en footer).</p>
                                <div class="flex flex-col gap-2 mb-4">
                                    <span class="text-sm text-muted-foreground">Eén logo voor beide modi</span>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <input type="checkbox" id="company-wizard-logo-mode-toggle" class="kt-switch kt-switch-sm" {{ $wizardLogoMode === 'light_dark' ? 'checked' : '' }} aria-label="Apart logo voor light en dark mode">
                                        <span class="text-sm text-muted-foreground">Apart logo voor light en dark mode</span>
                                    </div>
                                </div>

                                @if($wizLightUrl)
                                    <p class="text-sm font-medium text-muted-foreground mb-2">Zo ziet het logo eruit in de sidebar en op de frontend (wisselt mee met light/dark modus)</p>
                                    <div class="flex items-center gap-3 mb-4 p-3 rounded-lg border border-border bg-muted/30">
                                        <img alt="Logo light" class="logo-light w-auto max-w-[140px] object-contain dark:hidden" style="height: 35px;" src="{{ $wizLightUrl }}" id="company-wizard-settings-live-preview-light" />
                                        <img alt="Logo dark" class="logo-dark w-auto max-w-[140px] object-contain hidden dark:block" style="height: 35px;" src="{{ $wizDarkUrl }}" id="company-wizard-settings-live-preview-dark" />
                                    </div>
                                @endif

                                <p class="text-sm font-medium text-muted-foreground mb-2">Light mode (standaard)</p>
                                <div class="max-w-96 w-full">
                                    @include('admin.partials.image-upload-dropzone-inline', [
                                        'name' => 'logo',
                                        'inputId' => 'company-wizard-logo-input',
                                        'previewId' => 'company-wizard-logo-preview',
                                        'areaId' => 'company-wizard-logo-upload-area',
                                        'linkId' => 'company-wizard-logo-upload-link',
                                        'removeBtnId' => 'company-wizard-logo-remove',
                                        'existingUrl' => $hasWizLogo ? route('admin.companies.logo', $company) : null,
                                        'dropzoneKey' => 'light',
                                        'clientMsgId' => 'company-wizard-logo-client-msg',
                                        'livePreviewLightId' => 'company-wizard-settings-live-preview-light',
                                        'livePreviewDarkId' => 'company-wizard-settings-live-preview-dark',
                                        'logoModeInputId' => 'company-wizard-logo-mode-input',
                                    ])
                                </div>
                                <div id="company-wizard-logo-client-msg" class="text-xs mt-1 hidden" role="status" aria-live="polite"></div>
                                <p class="text-xs text-muted-foreground mt-1 mb-4">Ondersteunde formaten: JPEG, PNG, JPG, GIF, SVG (max. 2MB)</p>
                                @error('logo')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="logo">{{ $message }}</div>@enderror

                                <div id="company-wizard-logo-dark-block" class="{{ $wizardLogoMode === 'light_dark' ? '' : 'hidden' }}">
                                    <p class="text-sm font-medium text-muted-foreground mb-2">Dark mode</p>
                                    <p class="text-xs text-muted-foreground mb-2 max-w-xl">Optioneel. Wordt in de admin-sidebar getoond wanneer donker thema actief is. Laat leeg om overal het light mode-logo te gebruiken.</p>
                                    <div class="max-w-96 w-full">
                                        @include('admin.partials.image-upload-dropzone-inline', [
                                            'name' => 'logo_dark',
                                            'inputId' => 'company-wizard-logo-dark-input',
                                            'previewId' => 'company-wizard-logo-dark-preview',
                                            'areaId' => 'company-wizard-logo-dark-upload-area',
                                            'linkId' => 'company-wizard-logo-dark-upload-link',
                                            'removeBtnId' => 'company-wizard-logo-dark-remove',
                                            'existingUrl' => $hasWizLogoDark ? route('admin.companies.logo.dark', $company) : null,
                                            'dropzoneKey' => 'dark',
                                            'clientMsgId' => 'company-wizard-logo-dark-client-msg',
                                            'livePreviewDarkId' => 'company-wizard-settings-live-preview-dark',
                                        ])
                                    </div>
                                    <div id="company-wizard-logo-dark-client-msg" class="text-xs mt-1 hidden" role="status" aria-live="polite"></div>
                                    <p class="text-xs text-muted-foreground mt-1">Ondersteunde formaten: JPEG, PNG, JPG, GIF, SVG (max. 2MB)</p>
                                    @error('logo_dark')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="logo_dark">{{ $message }}</div>@enderror
                                </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Plaatje gebouw</td>
                    <td class="min-w-48 w-full">
                        @include('admin.partials.building-image-select', ['company' => $company ?? null])
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">KVK</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input @error('kvk_number') border-destructive @enderror" name="kvk_number" value="{{ old('kvk_number', $company->kvk_number ?? '') }}" maxlength="8" pattern="[0-9]{8}" inputmode="numeric" @error('kvk_number') data-server-error="1" @enderror>
                        @error('kvk_number')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="kvk_number">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Branche</td>
                    <td class="min-w-48 w-full">
                        @php
                            $currentIndustry = old('industry', $company->industry ?? '');
                            $selectedBranch = $branches->firstWhere('name', $currentIndustry);
                            $isOther = $currentIndustry && !$selectedBranch;
                        @endphp
                        <select class="kt-input" name="branch_select" id="branch_select">
                            <option value="">-- Selecteer --</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->name }}" {{ $currentIndustry === $branch->name ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                            <option value="other" {{ $isOther ? 'selected' : '' }}>Anders</option>
                        </select>
                        <input type="text" class="kt-input mt-2 {{ $isOther ? '' : 'hidden' }}" name="industry" id="industry_custom" value="{{ $isOther ? $currentIndustry : '' }}" placeholder="Branche">
                        @error('industry')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="industry">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Bedrijfstype</td>
                    <td class="min-w-48 w-full">
                        <label class="kt-label flex items-center gap-2">
                            <input type="checkbox" class="kt-checkbox" name="is_intermediary" value="1" {{ old('is_intermediary', $company->is_intermediary ?? false) ? 'checked' : '' }}>
                            Tussenpartij / Recruiter
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Website</td>
                    <td class="min-w-48 w-full">
                        <input type="url" class="kt-input @error('website') border-destructive @enderror" name="website" value="{{ old('website', $company->website ?? '') }}" placeholder="https://" @error('website') data-server-error="1" @enderror>
                        @error('website')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="website">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Beschrijving</td>
                    <td class="min-w-48 w-full">
                        <textarea class="kt-input" name="description" rows="3">{{ old('description', $company->description ?? '') }}</textarea>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Postcode *</td>
                    <td class="min-w-48 w-full">
                        <input type="text"
                            id="wizard_postal_code"
                            class="kt-input @error('postal_code') border-destructive @enderror"
                            name="postal_code"
                            value="{{ old('postal_code', $company->postal_code ?? '') }}"
                            pattern="[1-9][0-9]{3}\s?[A-Za-z]{2}"
                            placeholder="1234AB"
                            maxlength="7"
                            style="text-transform: uppercase;"
                            required
                            @error('postal_code') data-server-error="1" @enderror>
                        <div class="text-xs text-muted-foreground mt-1">Nederlandse postcode (bijv. 1234AB). Bij verlaten van het veld wordt het adres automatisch opgezocht.</div>
                        @error('postal_code')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="postal_code">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Huisnummer *</td>
                    <td class="min-w-48 w-full">
                        <input type="text"
                            id="wizard_house_number"
                            class="kt-input @error('house_number') border-destructive @enderror"
                            name="house_number"
                            value="{{ old('house_number', $company->house_number ?? '') }}"
                            required
                            maxlength="20"
                            minlength="1"
                            @error('house_number') data-server-error="1" @enderror>
                        <div class="text-xs text-muted-foreground mt-1">Bij verlaten van het veld worden straat en plaats automatisch ingevuld.</div>
                        @error('house_number')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="house_number">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Straat *</td>
                    <td class="min-w-48 w-full">
                        <input type="text"
                            id="wizard_street"
                            class="kt-input @error('street') border-destructive @enderror"
                            name="street"
                            value="{{ old('street', $company->street ?? '') }}"
                            readonly
                            required
                            minlength="2"
                            maxlength="255"
                            @error('street') data-server-error="1" @enderror>
                        <div id="wizard_street_lookup_loading" class="hidden items-center gap-2 text-xs text-muted-foreground mt-1.5" role="status" aria-live="polite" aria-busy="false">
                            <span class="wizard-postcode-spinner shrink-0" aria-hidden="true"></span>
                            <span class="wizard-street-loading-label">Adres zoeken…</span>
                        </div>
                        <div class="text-xs text-muted-foreground mt-1">Wordt automatisch ingevuld bij postcode + huisnummer. Bij geen resultaat worden de velden bewerkbaar.</div>
                        @error('street')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="street">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Plaats *</td>
                    <td class="min-w-48 w-full">
                        <input type="text"
                            id="wizard_city"
                            class="kt-input @error('city') border-destructive @enderror"
                            name="city"
                            value="{{ old('city', $company->city ?? '') }}"
                            readonly
                            required
                            minlength="2"
                            maxlength="255"
                            @error('city') data-server-error="1" @enderror>
                        @error('city')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="city">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Land</td>
                    <td class="min-w-48 w-full">
                        <input type="text"
                            id="wizard_country"
                            class="kt-input @error('country') border-destructive @enderror"
                            name="country"
                            value="{{ old('country', $company->country ?? 'Nederland') }}"
                            readonly
                            @error('country') data-server-error="1" @enderror>
                        @error('country')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="country">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Hoofdvestiging</td>
                    <td class="min-w-48 w-full">
                        <label class="kt-label flex items-center gap-2">
                            <input type="checkbox" class="kt-switch kt-switch-sm" name="is_main" value="1" {{ old('is_main', $company->is_main ?? true) ? 'checked' : '' }}>
                            Dit is de hoofdvestiging
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Actief</td>
                    <td class="min-w-48 w-full">
                        <label class="kt-label flex items-center gap-2">
                            <input type="checkbox" class="kt-switch kt-switch-sm" name="is_active" value="1" {{ old('is_active', $company->is_active ?? true) ? 'checked' : '' }}>
                            Bedrijf is actief
                        </label>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="kt-card min-w-full mb-6">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Contactpersoon</h3>
        </div>
        <p class="text-sm text-secondary-foreground px-6 pt-2 pb-3 mb-0">
            Gegevens van de persoon die we voor dit bedrijf als eerste aanspreekpunt gebruiken (e-mail en telefoon).
        </p>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Voornaam</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input @error('contact_first_name') border-destructive @enderror" name="contact_first_name" value="{{ old('contact_first_name', $company->contact_first_name ?? '') }}" maxlength="255" autocomplete="given-name" @error('contact_first_name') data-server-error="1" @enderror>
                        @error('contact_first_name')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="contact_first_name">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Achternaam</td>
                    <td class="min-w-48 w-full">
                        <input type="text" class="kt-input @error('contact_last_name') border-destructive @enderror" name="contact_last_name" value="{{ old('contact_last_name', $company->contact_last_name ?? '') }}" maxlength="255" autocomplete="family-name" @error('contact_last_name') data-server-error="1" @enderror>
                        @error('contact_last_name')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="contact_last_name">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">E-mail *</td>
                    <td class="min-w-48 w-full">
                        <input type="email"
                            class="kt-input @error('email') border-destructive @enderror"
                            name="email"
                            value="{{ old('email', $company->email ?? '') }}"
                            required
                            autocomplete="email"
                            @error('email') data-server-error="1" @enderror>
                        @error('email')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="email">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Telefoon *</td>
                    <td class="min-w-48 w-full">
                        <input type="tel"
                            class="kt-input @error('phone') border-destructive @enderror"
                            name="phone"
                            value="{{ old('phone', $company->phone ?? '') }}"
                            pattern="(\+31|0)[1-9][0-9]{8}"
                            placeholder="0612345678 of +31612345678"
                            maxlength="13"
                            required
                            @error('phone') data-server-error="1" @enderror>
                        <div class="text-xs text-muted-foreground mt-1">Nederlands nummer (bijv. 0612345678 of +31612345678)</div>
                        @error('phone')<div class="text-xs text-destructive mt-1" data-validation-error="1" data-validation-error-for="phone">{{ $message }}</div>@enderror
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 justify-end">
        <button type="submit" class="kt-btn kt-btn-primary">
            Volgende
            <i class="ki-filled ki-arrow-right ms-2"></i>
        </button>
    </div>
</form>

<style>
    @keyframes wizard-postcode-spin {
        to { transform: rotate(360deg); }
    }
    .wizard-postcode-spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid var(--border, #e5e7eb);
        border-top-color: var(--primary, #3b82f6);
        border-radius: 9999px;
        animation: wizard-postcode-spin 0.65s linear infinite;
    }
    .dark .wizard-postcode-spinner {
        border-color: rgba(255, 255, 255, 0.2);
        border-top-color: var(--primary, #60a5fa);
    }
    #wizard_street_lookup_loading:not(.hidden) {
        display: flex;
    }
</style>

<script>
document.getElementById('branch_select')?.addEventListener('change', function() {
    const custom = document.getElementById('industry_custom');
    if (!custom) return;
    custom.classList.toggle('hidden', this.value !== 'other');
});

@include('admin.partials.logo-dropzone-init-inner')

(function() {
    var modeToggle = document.getElementById('company-wizard-logo-mode-toggle');
    var modeInput = document.getElementById('company-wizard-logo-mode-input');
    var darkBlock = document.getElementById('company-wizard-logo-dark-block');
    if (modeToggle && modeInput && darkBlock) {
        modeToggle.addEventListener('change', function() {
            var isLightDark = modeToggle.checked;
            modeInput.value = isLightDark ? 'light_dark' : 'single';
            darkBlock.classList.toggle('hidden', !isLightDark);
            if (!isLightDark) {
                var darkInput = document.getElementById('company-wizard-logo-dark-input');
                if (darkInput) darkInput.value = '';
                var liveLight = document.getElementById('company-wizard-settings-live-preview-light');
                var liveDark = document.getElementById('company-wizard-settings-live-preview-dark');
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
    var postalCodeInput = document.getElementById('wizard_postal_code');
    var houseNumberInput = document.getElementById('wizard_house_number');
    var streetInput = document.getElementById('wizard_street');
    var cityInput = document.getElementById('wizard_city');
    var countryInput = document.getElementById('wizard_country');
    if (!postalCodeInput || !houseNumberInput || !streetInput || !cityInput) return;
    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (!csrf) return;

    var lookupTimeout;
    var streetLoadingEl = document.getElementById('wizard_street_lookup_loading');

    function setStreetLookupLoading(on) {
        if (!streetLoadingEl) return;
        streetLoadingEl.classList.toggle('hidden', !on);
        streetLoadingEl.setAttribute('aria-busy', on ? 'true' : 'false');
    }

    function lookupContactAddress() {
        clearTimeout(lookupTimeout);
        setStreetLookupLoading(false);

        var postcode = postalCodeInput.value.trim().toUpperCase().replace(/\s+/g, '');
        var huisnummer = houseNumberInput.value.trim();
        if (!/^[1-9][0-9]{3}[A-Z]{2}$/.test(postcode) || !huisnummer) return;

        lookupTimeout = setTimeout(function() {
            setStreetLookupLoading(true);
            fetch(@json(route('admin.postcode.lookup')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf.getAttribute('content')
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
                    streetInput.dispatchEvent(new Event('input', { bubbles: true }));
                    cityInput.dispatchEvent(new Event('input', { bubbles: true }));
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
            })
            .finally(function() {
                setStreetLookupLoading(false);
            });
        }, 300);
    }

    postalCodeInput.addEventListener('blur', lookupContactAddress);
    houseNumberInput.addEventListener('blur', lookupContactAddress);
})();
</script>

@push('scripts')
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
@endpush
@endsection
