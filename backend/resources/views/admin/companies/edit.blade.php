@extends('admin.layouts.app')

@section('title', 'Bedrijf Bewerken')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Bedrijf Bewerken
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form action="{{ route('admin.companies.update', $company) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="is_active" id="is_active_hidden" value="{{ old('is_active', $company->is_active) ? '1' : '0' }}">

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- General Info -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Algemene Informatie
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
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
                                        <span class="text-sm text-muted-foreground">Eén logo voor beide modi</span>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <input type="checkbox" id="company-form-logo-mode-toggle" class="kt-switch kt-switch-sm" {{ $formLogoMode === 'light_dark' ? 'checked' : '' }} aria-label="Apart logo voor light en dark mode">
                                            <span class="text-sm text-muted-foreground">Apart logo voor light en dark mode</span>
                                        </div>
                                    </div>

                                    @if($formLightUrl)
                                        <p class="text-sm font-medium text-muted-foreground mb-2">Zo ziet het logo eruit in de sidebar en op de frontend (wisselt mee met light/dark modus)</p>
                                        <div class="flex items-center gap-3 mb-4 p-3 rounded-lg border border-border bg-muted/30">
                                            <img alt="Logo light" class="logo-light w-auto max-w-[140px] object-contain dark:hidden" style="height: 35px;" src="{{ $formLightUrl }}" id="company-form-live-preview-light" />
                                            <img alt="Logo dark" class="logo-dark w-auto max-w-[140px] object-contain hidden dark:block" style="height: 35px;" src="{{ $formDarkUrl }}" id="company-form-live-preview-dark" />
                                        </div>
                                    @endif

                                    <p class="text-sm font-medium text-muted-foreground mb-2">Light mode (standaard)</p>
                                    <div class="max-w-96 w-full">
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
                                        <div class="max-w-96 w-full">
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
                                Postcode *
                            </td>
                            <td>
                                <input type="text" 
                                       id="postal_code"
                                       class="kt-input @error('postal_code') border-destructive @enderror" 
                                       name="postal_code" 
                                       value="{{ old('postal_code', $company->postal_code) }}"
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

        <div class="flex items-center justify-end gap-2.5 mt-5">
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
    /* Remove all borders between table rows in edit forms */
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
    /* Ensure kt-switch checkboxes are clickable */
    .kt-switch {
        pointer-events: auto !important;
        z-index: 1;
        position: relative;
    }
    .kt-label {
        cursor: pointer;
    }
    .wizard-onboarding-form-table tbody tr { border-bottom: none !important; }
    .wizard-onboarding-form-table tbody tr,
    .wizard-onboarding-form-table tbody tr td { height: auto; min-height: 48px; }
    .wizard-onboarding-form-table tbody tr td { padding-top: 12px; padding-bottom: 12px; vertical-align: middle; }
    .wizard-onboarding-form-table tbody tr td.align-top { vertical-align: top !important; padding-top: 18px; }
</style>
@endpush

@push('scripts')
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
            kvkInput.addEventListener('blur', function() {
                validateKVK(this);
            });
        }
        
        // Phone validation
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                let value = this.value.replace(/\s/g, ''); // Remove spaces
                // Auto-format: add +31 if starts with 0 and has 10 digits
                if (value.startsWith('0') && value.length === 10) {
                    value = '+31' + value.substring(1);
                }
                this.value = value;
                validatePhone(this);
            });
            phoneInput.addEventListener('blur', function() {
                validatePhone(this);
            });
        }
        
        // Postal code validation
        const postalCodeInput = document.querySelector('input[name="postal_code"]');
        if (postalCodeInput) {
            postalCodeInput.addEventListener('input', function() {
                let value = this.value.replace(/\s/g, '').toUpperCase(); // Remove spaces, uppercase
                // Auto-format: add space after 4 digits
                if (value.length > 4) {
                    value = value.substring(0, 4) + ' ' + value.substring(4, 7);
                }
                this.value = value;
                validatePostalCode(this);
            });
            postalCodeInput.addEventListener('blur', function() {
                validatePostalCode(this);
            });
        }
        
        // Email validation
        const emailInput = document.querySelector('input[type="email"]');
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                validateEmail(this);
            });
        }
        
        // Website validation
        const websiteInput = document.querySelector('input[name="website"]');
        if (websiteInput) {
            websiteInput.addEventListener('blur', function() {
                validateWebsite(this);
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
        
        if (value && value.length !== 8) {
            input.classList.add('border-destructive');
            if (errorDiv) {
                errorDiv.textContent = 'KVK nummer moet 8 cijfers bevatten.';
                errorDiv.classList.remove('hidden');
            }
            return false;
        } else {
            input.classList.remove('border-destructive');
            if (errorDiv) {
                errorDiv.classList.add('hidden');
            }
            return true;
        }
    }
    
    function validatePhone(input) {
        const value = input.value.replace(/\s/g, '');
        const pattern = /^(\+31|0)[1-9][0-9]{8}$/;
        const isValid = !value || pattern.test(value);
        
        if (!isValid && value) {
            input.classList.add('border-destructive');
        } else {
            input.classList.remove('border-destructive');
        }
        
        return isValid;
    }
    
    function validatePostalCode(input) {
        const value = input.value.replace(/\s/g, '').toUpperCase();
        const pattern = /^[1-9][0-9]{3}[A-Z]{2}$/;
        const isValid = !value || pattern.test(value);
        
        if (!isValid && value) {
            input.classList.add('border-destructive');
        } else {
            input.classList.remove('border-destructive');
        }
        
        return isValid;
    }
    
    function validateEmail(input) {
        const value = input.value.trim();
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = !value || pattern.test(value);
        
        if (!isValid && value) {
            input.classList.add('border-destructive');
        } else {
            input.classList.remove('border-destructive');
        }
        
        return isValid;
    }
    
    function validateWebsite(input) {
        const value = input.value.trim();
        if (!value) return true;
        
        try {
            const url = new URL(value);
            const isValid = url.protocol === 'http:' || url.protocol === 'https:';
            
            if (!isValid) {
                input.classList.add('border-destructive');
            } else {
                input.classList.remove('border-destructive');
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

