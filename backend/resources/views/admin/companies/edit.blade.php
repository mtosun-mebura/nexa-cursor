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
                        @can('edit-companies')
                        <form action="{{ route('admin.companies.toggle-main-location', $company) }}" method="POST" id="toggle-main-location-form">
                            @csrf
                        <label class="kt-label">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
                                   id="toggle-main-location-checkbox"
                                   {{ $company->is_main || $company->mainLocation ? 'checked' : '' }}/>
                            Hoofdkantoor
                        </label>
                        </form>
                        @else
                        <label class="kt-label">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
                                   {{ $company->mainLocation ? 'checked' : '' }}
                                   disabled/>
                            Hoofdkantoor
                        </label>
                        @endcan
                        <span class="text-muted-foreground">|</span>
                        <label class="kt-label">
                            <input type="checkbox" 
                                   class="kt-switch kt-switch-sm" 
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', $company->is_active) ? 'checked' : '' }}/>
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
                                       value="{{ old('name', $company->name) }}" 
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
                                    <img alt="Company Logo" class="h-[35px] mt-2 {{ $company->logo_blob ? '' : 'hidden' }}" src="{{ $company->logo_blob ? route('admin.companies.logo', $company) : '' }}" id="logo-preview"/>
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
                                    <input type="hidden" name="logo_path" value="{{ old('logo_path', $company->logo_path) }}" id="logo-path-input">
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
                                Telefoon
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
                            <td class="text-secondary-foreground font-normal">
                                Straat
                            </td>
                            <td>
                                <input type="text" 
                                       class="kt-input @error('street') border-destructive @enderror" 
                                       name="street" 
                                       value="{{ old('street', $company->street) }}">
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
                                       value="{{ old('house_number', $company->house_number) }}">
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
                                       value="{{ old('postal_code', $company->postal_code) }}"
                                       pattern="[1-9][0-9]{3}\s?[A-Za-z]{2}"
                                       placeholder="1234AB"
                                       maxlength="7"
                                       style="text-transform: uppercase;">
                                <div class="text-xs text-muted-foreground mt-1">Nederlandse postcode (bijv. 1234AB)</div>
                                @error('postal_code')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                <div class="text-xs text-destructive mt-1 hidden" id="postal_code_error"></div>
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
                                       value="{{ old('city', $company->city) }}">
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
                                       value="{{ old('country', $company->country) }}">
                                @error('country')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.companies.show', $company) }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Wijzigingen Opslaan
                </button>
            </div>
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
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Main location toggle
        const mainLocationCheckbox = document.getElementById('toggle-main-location-checkbox');
        const mainLocationForm = document.getElementById('toggle-main-location-form');
        
        if (mainLocationCheckbox && mainLocationForm) {
            mainLocationCheckbox.addEventListener('change', function(e) {
                e.preventDefault();
                
                const formData = new FormData(mainLocationForm);
                const url = mainLocationForm.action;
                const originalChecked = this.checked;
                
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || 'Network response was not ok');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.message) {
                        console.log(data.message);
                        // Update checkbox state based on response
                        if (data.has_main_location !== undefined) {
                            this.checked = data.has_main_location;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert checkbox state on error
                    this.checked = !originalChecked;
                    alert(error.message || 'Er is een fout opgetreden bij het wijzigen van het hoofdkantoor.');
                });
            });
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
                
                // Create preview immediately
                const reader = new FileReader();
                reader.onload = function(e) {
                    logoPreview.src = e.target.result;
                    logoPreview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
                
                // Upload logo immediately via AJAX
                const formData = new FormData();
                formData.append('logo', file);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                
                fetch('{{ route("admin.companies.upload-logo", $company) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || 'Network response was not ok');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && logoPreview) {
                        // Update preview with server URL (add timestamp to force refresh)
                        logoPreview.src = data.logo_url + '?t=' + new Date().getTime();
                        logoPreview.classList.remove('hidden');
                        console.log('Logo succesvol geüpload.');
                    } else {
                        alert(data.message || 'Er is een fout opgetreden bij het uploaden van het logo.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(error.message || 'Er is een fout opgetreden bij het uploaden van het logo.');
                    // Keep the preview even if upload fails
                });
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

