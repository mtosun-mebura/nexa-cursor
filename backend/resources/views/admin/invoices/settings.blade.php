@extends('admin.layouts.app')

@section('title', 'Factuurinstellingen')

@section('content')

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Factuurinstellingen
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.invoices.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert" id="success-alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.invoices.settings.update') }}" data-validate="true">
        @csrf
        
        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- Factuurnummer Instellingen -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Factuurnummer Instellingen
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Prefix
                            </td>
                            <td class="min-w-48 w-full">
                                <input class="kt-input @error('invoice_number_prefix') border-destructive @enderror" 
                                       type="text" 
                                       name="invoice_number_prefix" 
                                       id="invoice_number_prefix"
                                       value="{{ old('invoice_number_prefix', $settings->invoice_number_prefix) }}"
                                       required>
                                <div class="text-xs text-muted-foreground mt-1">Bijv: NX</div>
                                @error('invoice_number_prefix')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Formaat
                            </td>
                            <td>
                                <input class="kt-input @error('invoice_number_format') border-destructive @enderror" 
                                       type="text" 
                                       name="invoice_number_format" 
                                       id="invoice_number_format"
                                       value="{{ old('invoice_number_format', $settings->invoice_number_format) }}"
                                       required>
                                <div class="text-xs text-muted-foreground mt-1">Gebruik: {prefix}, {year}, {number}</div>
                                @error('invoice_number_format')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Volgende Factuurnummer
                            </td>
                            <td>
                                <input class="kt-input @error('next_invoice_number') border-destructive @enderror" 
                                       type="number" 
                                       name="next_invoice_number" 
                                       id="next_invoice_number"
                                       value="{{ old('next_invoice_number', $settings->next_invoice_number) }}"
                                       min="1"
                                       style="width: 13ch;"
                                       required>
                                @error('next_invoice_number')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Huidig Jaar
                            </td>
                            <td>
                                <input class="kt-input @error('current_year') border-destructive @enderror" 
                                       type="number" 
                                       name="current_year" 
                                       id="current_year"
                                       value="{{ old('current_year', $settings->current_year) }}"
                                       min="2020"
                                       max="2100"
                                       style="width: 13ch;"
                                       required>
                                @error('current_year')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Betaaltermijn -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Betaaltermijn
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Betaaltermijn (dagen)
                            </td>
                            <td class="min-w-48 w-full">
                                <input class="kt-input @error('payment_terms_days') border-destructive @enderror" 
                                       type="number" 
                                       name="payment_terms_days" 
                                       id="payment_terms_days"
                                       value="{{ old('payment_terms_days', $settings->payment_terms_days) }}"
                                       min="1"
                                       max="365"
                                       style="width: 13ch;"
                                       required>
                                <div class="text-xs text-muted-foreground mt-1">Aantal dagen dat klanten hebben om te betalen</div>
                                @error('payment_terms_days')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Standaard BTW Percentage
                            </td>
                            <td>
                                <input class="kt-input @error('default_tax_rate') border-destructive @enderror" 
                                       type="number" 
                                       name="default_tax_rate" 
                                       id="default_tax_rate"
                                       value="{{ old('default_tax_rate', (int)($settings->default_tax_rate ?? 21)) }}"
                                       min="0"
                                       max="100"
                                       step="1"
                                       style="width: 13ch;"
                                       required>
                                <div class="text-xs text-muted-foreground mt-1">Bijv: 21 voor 21%</div>
                                @error('default_tax_rate')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Standaard Bedrag (excl. BTW)
                            </td>
                            <td>
                                <input class="kt-input @error('default_amount') border-destructive @enderror" 
                                       type="number" 
                                       name="default_amount" 
                                       id="default_amount"
                                       value="{{ old('default_amount', $settings->default_amount) }}"
                                       min="0"
                                       step="any"
                                       style="width: 13ch;">
                                <div class="text-xs text-muted-foreground mt-1">Standaard bedrag dat wordt voorgesteld bij het aanmaken van een nieuwe factuur (bijv: 10000 of 10000.00)</div>
                                @error('default_amount')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Bedrijfsgegevens -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Bedrijfsgegevens
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Bedrijf
                            </td>
                            <td class="min-w-48 w-full">
                                <div class="kt-select-wrapper">
                                    <select class="kt-select @error('company_id') border-destructive @enderror" 
                                            name="company_id" 
                                            id="company_id"
                                            data-kt-select="true">
                                        <option value="">Selecteer bedrijf</option>
                                        @foreach($companies as $company)
                                            @php
                                                $hasMainAddress = $company->street || $company->city;
                                                $hasLocations = $company->locations && $company->locations->count() > 0;
                                            @endphp
                                            
                                            @if($hasMainAddress || $hasLocations)
                                                <option value="{{ $company->id }}" 
                                                        data-company-name="{{ $company->name }}"
                                                        data-company-id="{{ $company->id }}"
                                                        data-has-locations="{{ $hasLocations ? '1' : '0' }}"
                                                        data-address="{{ trim(($company->street ?? '') . ' ' . ($company->house_number ?? '') . ($company->house_number_extension ? '-' . $company->house_number_extension : '')) }}"
                                                        data-city="{{ $company->city ?? '' }}"
                                                        data-postal-code="{{ $company->postal_code ?? '' }}"
                                                        data-country="{{ $company->country ?? '' }}"
                                                        data-email="{{ $company->email ?? '' }}"
                                                        data-phone="{{ $company->phone ?? '' }}"
                                                        data-vat-number="{{ $company->kvk_number ?? '' }}"
                                                        {{ old('company_id', $settings->company_id ?? '') == $company->id ? 'selected' : '' }}>
                                                    {{ $company->name }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                @error('company_id')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr id="location-row" style="display: none;">
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Vestiging
                            </td>
                            <td class="min-w-48 w-full">
                                <div class="kt-select-wrapper">
                                    <select class="kt-select @error('location_id') border-destructive @enderror" 
                                            name="location_id" 
                                            id="location_id"
                                            data-kt-select="true">
                                        <option value="">Selecteer vestiging</option>
                                    </select>
                                </div>
                                @error('location_id')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        
                        {{-- Hidden fields to store the selected data --}}
                        <input type="hidden" name="company_name" id="company_name" value="{{ old('company_name', $settings->company_name) }}">
                        <input type="hidden" name="company_address" id="company_address" value="{{ old('company_address', $settings->company_address) }}">
                        <input type="hidden" name="company_city" id="company_city" value="{{ old('company_city', $settings->company_city) }}">
                        <input type="hidden" name="company_postal_code" id="company_postal_code" value="{{ old('company_postal_code', $settings->company_postal_code) }}">
                        <input type="hidden" name="company_country" id="company_country" value="{{ old('company_country', $settings->company_country) }}">
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                BTW Nummer
                            </td>
                            <td>
                                <input class="kt-input @error('company_vat_number') border-destructive @enderror" 
                                       type="text" 
                                       name="company_vat_number" 
                                       id="company_vat_number"
                                       value="{{ old('company_vat_number', $settings->company_vat_number) }}">
                                @error('company_vat_number')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                E-mail
                            </td>
                            <td>
                                <input class="kt-input @error('company_email') border-destructive @enderror" 
                                       type="email" 
                                       name="company_email" 
                                       id="company_email"
                                       value="{{ old('company_email', $settings->company_email) }}">
                                @error('company_email')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Telefoon
                            </td>
                            <td>
                                <input class="kt-input @error('company_phone') border-destructive @enderror" 
                                       type="text" 
                                       name="company_phone" 
                                       id="company_phone"
                                       value="{{ old('company_phone', $settings->company_phone) }}">
                                @error('company_phone')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Bankrekening
                            </td>
                            <td>
                                <input class="kt-input @error('bank_account') border-destructive @enderror" 
                                       type="text" 
                                       name="bank_account" 
                                       id="bank_account"
                                       value="{{ old('bank_account', $settings->bank_account) }}">
                                @error('bank_account')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Footer Tekst
                            </td>
                            <td>
                                <textarea class="kt-input @error('invoice_footer_text') border-destructive @enderror" 
                                          name="invoice_footer_text" 
                                          id="invoice_footer_text"
                                          rows="4">{{ old('invoice_footer_text', $settings->invoice_footer_text) }}</textarea>
                                @error('invoice_footer_text')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Acties -->
            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.invoices.index') }}" class="kt-btn kt-btn-outline">
                    <i class="ki-filled ki-cross me-2"></i>
                    Annuleren
                </a>
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check me-2"></i>
                    Opslaan
                </button>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    /* Zorg dat select dropdowns buiten de card kunnen renderen */
    .kt-card-table {
        overflow: visible !important;
    }
    .kt-card {
        overflow: visible !important;
    }
    .kt-select-dropdown {
        z-index: 1000 !important;
    }
    .kt-select-options {
        z-index: 1001 !important;
    }
    
    /* Zorg dat alleen de actieve dropdown bovenop ligt */
    .kt-select-wrapper .kt-select-dropdown.open {
        z-index: 1002 !important;
    }
    
    /* Sluit andere dropdowns visueel */
    .kt-select-wrapper:not(:has(.kt-select-dropdown.open)) .kt-select-dropdown {
        z-index: 1000 !important;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss success alert after 5 seconds
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.opacity = '0';
            successAlert.style.transition = 'opacity 0.3s';
            setTimeout(function() {
                successAlert.remove();
            }, 300);
        }, 5000);
    }
    
    // Companies data from PHP
    const companiesData = @json($companiesData);
    
    const companySelect = document.getElementById('company_id');
    const locationSelect = document.getElementById('location_id');
    const locationRow = document.getElementById('location-row');
    
    // Close other dropdowns when one opens
    function closeOtherDropdowns(currentSelect) {
        const allSelects = [companySelect, locationSelect].filter(s => s && s !== currentSelect);
        allSelects.forEach(function(select) {
            if (!select) return;
            
            // Find the dropdown wrapper
            const wrapper = select.closest('.kt-select-wrapper');
            if (wrapper) {
                const dropdown = wrapper.querySelector('.kt-select-dropdown');
                if (dropdown) {
                    // Remove open class and hide
                    dropdown.classList.remove('open');
                    dropdown.style.display = 'none';
                    dropdown.style.opacity = '0';
                    dropdown.style.visibility = 'hidden';
                }
                
                // Also remove any active/open state from the select element
                const selectElement = wrapper.querySelector('select');
                if (selectElement) {
                    selectElement.classList.remove('open', 'active');
                    selectElement.blur();
                }
            }
        });
    }
    
    // Function to handle dropdown opening
    function handleDropdownOpen(selectElement) {
        closeOtherDropdowns(selectElement);
    }
    
    // Add event listeners to close other dropdowns when one opens
    if (companySelect) {
        companySelect.addEventListener('focus', function() {
            handleDropdownOpen(this);
        });
        companySelect.addEventListener('click', function(e) {
            handleDropdownOpen(this);
        });
        companySelect.addEventListener('mousedown', function() {
            handleDropdownOpen(this);
        });
    }
    
    if (locationSelect) {
        locationSelect.addEventListener('focus', function() {
            handleDropdownOpen(this);
        });
        locationSelect.addEventListener('click', function(e) {
            handleDropdownOpen(this);
        });
        locationSelect.addEventListener('mousedown', function() {
            handleDropdownOpen(this);
        });
    }
    
    // Also listen for dropdown open events using MutationObserver
    function observeDropdowns() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    if (target.classList && target.classList.contains('kt-select-dropdown')) {
                        if (target.classList.contains('open')) {
                            // Find which select this dropdown belongs to
                            const wrapper = target.closest('.kt-select-wrapper');
                            if (wrapper) {
                                const select = wrapper.querySelector('select');
                                if (select) {
                                    closeOtherDropdowns(select);
                                }
                            }
                        }
                    }
                }
            });
        });
        
        // Observe both select wrappers
        [companySelect, locationSelect].forEach(function(select) {
            if (select) {
                const wrapper = select.closest('.kt-select-wrapper');
                if (wrapper) {
                    observer.observe(wrapper, {
                        attributes: true,
                        attributeFilter: ['class'],
                        subtree: true,
                        childList: false
                    });
                }
            }
        });
    }
    
    // Start observing after a short delay to ensure DOM is ready
    setTimeout(observeDropdowns, 100);
    
    // Close all dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const companyWrapper = companySelect ? companySelect.closest('.kt-select-wrapper') : null;
        const locationWrapper = locationSelect ? locationSelect.closest('.kt-select-wrapper') : null;
        
        const clickedInsideCompany = companyWrapper && companyWrapper.contains(e.target);
        const clickedInsideLocation = locationWrapper && locationWrapper.contains(e.target);
        
        if (!clickedInsideCompany && !clickedInsideLocation) {
            // Clicked outside both dropdowns, close them
            if (companySelect) {
                const wrapper = companySelect.closest('.kt-select-wrapper');
                if (wrapper) {
                    const dropdown = wrapper.querySelector('.kt-select-dropdown');
                    if (dropdown) {
                        dropdown.classList.remove('open');
                        dropdown.style.display = 'none';
                    }
                }
            }
            if (locationSelect) {
                const wrapper = locationSelect.closest('.kt-select-wrapper');
                if (wrapper) {
                    const dropdown = wrapper.querySelector('.kt-select-dropdown');
                    if (dropdown) {
                        dropdown.classList.remove('open');
                        dropdown.style.display = 'none';
                    }
                }
            }
        }
    });
    
    // Function to fill form fields with company or location data
    function fillFormFields(data) {
        document.getElementById('company_name').value = data.companyName || '';
        document.getElementById('company_address').value = data.address || '';
        document.getElementById('company_city').value = data.city || '';
        document.getElementById('company_postal_code').value = data.postalCode || '';
        document.getElementById('company_country').value = data.country || '';
        document.getElementById('company_vat_number').value = data.vatNumber || '';
        document.getElementById('company_email').value = data.email || '';
        document.getElementById('company_phone').value = data.phone || '';
    }
    
    // Function to clear form fields
    function clearFormFields() {
        document.getElementById('company_name').value = '';
        document.getElementById('company_address').value = '';
        document.getElementById('company_city').value = '';
        document.getElementById('company_postal_code').value = '';
        document.getElementById('company_country').value = '';
        document.getElementById('company_vat_number').value = '';
        document.getElementById('company_email').value = '';
        document.getElementById('company_phone').value = '';
    }
    
    // Handle company selection
    if (companySelect) {
        companySelect.addEventListener('change', function() {
            const companyId = this.value;
            const locationRow = document.getElementById('location-row');
            const locationSelect = document.getElementById('location_id');
            
            // Clear location select
            if (locationSelect) {
                locationSelect.innerHTML = '<option value="">Selecteer vestiging</option>';
            }
            
            if (!companyId) {
                // Hide location row and clear all fields
                if (locationRow) {
                    locationRow.style.display = 'none';
                }
                clearFormFields();
                return;
            }
            
            const company = companiesData[companyId];
            if (!company) {
                clearFormFields();
                return;
            }
            
            // Show/hide location row based on whether company has locations
            if (company.locations && company.locations.length > 0) {
                // Show location row and populate options
                if (locationRow) {
                    locationRow.style.display = '';
                }
                
                // Populate location options
                if (locationSelect) {
                    locationSelect.innerHTML = '<option value="">Selecteer vestiging</option>';
                    
                    // Add main address as first option
                    const mainAddress = (company.street || '') + ' ' + (company.house_number || '') + (company.house_number_extension ? '-' + company.house_number_extension : '');
                    const mainAddressLabel = mainAddress.trim() + (company.city ? ', ' + company.city : '') + (company.postal_code ? ' ' + company.postal_code : '');
                    
                    const mainOption = document.createElement('option');
                    mainOption.value = 'main';
                    mainOption.textContent = 'Hoofdvestiging' + (mainAddressLabel.trim() ? ' - ' + mainAddressLabel.trim() : '');
                    mainOption.dataset.companyName = company.name;
                    mainOption.dataset.address = mainAddress.trim();
                    mainOption.dataset.city = company.city || '';
                    mainOption.dataset.postalCode = company.postal_code || '';
                    mainOption.dataset.country = company.country || '';
                    mainOption.dataset.email = company.email || '';
                    mainOption.dataset.phone = company.phone || '';
                    mainOption.dataset.vatNumber = company.kvk_number || '';
                    locationSelect.appendChild(mainOption);
                    
                    // Add location options
                    company.locations.forEach(function(location) {
                        const locAddress = (location.street || '') + ' ' + (location.house_number || '') + (location.house_number_extension ? '-' + location.house_number_extension : '');
                        const locAddressLabel = locAddress.trim() + (location.city ? ', ' + location.city : '') + (location.postal_code ? ' ' + location.postal_code : '');
                        
                        const option = document.createElement('option');
                        option.value = location.id;
                        option.textContent = location.name + (locAddressLabel.trim() ? ' - ' + locAddressLabel.trim() : '');
                        option.dataset.companyName = company.name;
                        option.dataset.address = locAddress.trim();
                        option.dataset.city = location.city || '';
                        option.dataset.postalCode = location.postal_code || '';
                        option.dataset.country = location.country || '';
                        option.dataset.email = location.email || company.email || '';
                        option.dataset.phone = location.phone || company.phone || '';
                        option.dataset.vatNumber = company.kvk_number || '';
                        locationSelect.appendChild(option);
                    });
                }
            } else {
                // Hide location row and fill with company data directly
                if (locationRow) {
                    locationRow.style.display = 'none';
                }
                
                const mainAddress = (company.street || '') + ' ' + (company.house_number || '') + (company.house_number_extension ? '-' + company.house_number_extension : '');
                fillFormFields({
                    companyName: company.name,
                    address: mainAddress.trim(),
                    city: company.city || '',
                    postalCode: company.postal_code || '',
                    country: company.country || '',
                    email: company.email || '',
                    phone: company.phone || '',
                    vatNumber: company.kvk_number || ''
                });
            }
        });
        
        // Handle location selection
        if (locationSelect) {
            locationSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                
                if (selectedOption && selectedOption.value) {
                    fillFormFields({
                        companyName: selectedOption.dataset.companyName || '',
                        address: selectedOption.dataset.address || '',
                        city: selectedOption.dataset.city || '',
                        postalCode: selectedOption.dataset.postalCode || '',
                        country: selectedOption.dataset.country || '',
                        email: selectedOption.dataset.email || '',
                        phone: selectedOption.dataset.phone || '',
                        vatNumber: selectedOption.dataset.vatNumber || ''
                    });
                } else {
                    // If no location selected, use company data
                    const companyId = companySelect.value;
                    if (companyId) {
                        const company = companiesData[companyId];
                        if (company) {
                            const mainAddress = (company.street || '') + ' ' + (company.house_number || '') + (company.house_number_extension ? '-' + company.house_number_extension : '');
                            fillFormFields({
                                companyName: company.name,
                                address: mainAddress.trim(),
                                city: company.city || '',
                                postalCode: company.postal_code || '',
                                country: company.country || '',
                                email: company.email || '',
                                phone: company.phone || '',
                                vatNumber: company.kvk_number || ''
                            });
                        }
                    }
                }
            });
        }
        
        // Trigger change event on page load if there's a selected value
        if (companySelect.value) {
            companySelect.dispatchEvent(new Event('change'));
        }
    }
});
</script>
@endpush
@endsection
