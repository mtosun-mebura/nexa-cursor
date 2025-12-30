@extends('admin.layouts.app')

@section('title', 'Factuur Bewerken')

@section('content')
<div class="kt-container-fixed">
    <!-- Page Title -->
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Factuur Bewerken: {{ $invoice->invoice_number }}
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Bewerk factuurdetails
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left text-base me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.invoices.update', $invoice->id) }}">
        @csrf
        @method('PUT')
        
        <div class="grid lg:grid-cols-2 gap-5 lg:gap-7.5">
            <!-- Basic Information -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Basis Informatie
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Bedrijf <span class="text-destructive">*</span>
                            </td>
                            <td class="min-w-48 w-full">
                                <select class="kt-select" name="company_id" id="company_id" required>
                                    <option value="">Selecteer bedrijf</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id', $invoice->company_id) == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Match (optioneel)
                            </td>
                            <td class="min-w-48 w-full">
                                <select class="kt-select" name="job_match_id" id="job_match_id">
                                    <option value="">Selecteer een kandidaat</option>
                                    @foreach($jobMatches as $match)
                                        <option value="{{ $match->id }}" {{ old('job_match_id', $invoice->job_match_id) == $match->id ? 'selected' : '' }}>
                                            Match #{{ $match->id }} - {{ $match->company->name ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('job_match_id')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Factuurdatum <span class="text-destructive">*</span>
                            </td>
                            <td class="min-w-48 w-full">
                                <div class="kt-input w-64 @error('invoice_date') border-destructive @enderror">
                                    <i class="ki-outline ki-calendar"></i>
                                    <input class="grow" 
                                           name="invoice_date" 
                                           id="invoice_date"
                                           value="{{ old('invoice_date', $invoice->invoice_date->format('d-m-Y')) }}"
                                           data-kt-date-picker="true" 
                                           data-kt-date-picker-input-mode="true" 
                                           data-kt-date-picker-position-to-input="left"
                                           data-kt-date-picker-format="dd-MM-yyyy"
                                           placeholder="Selecteer datum" 
                                           readonly 
                                           type="text"
                                           required/>
                                </div>
                                @error('invoice_date')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Vervaldatum <span class="text-destructive">*</span>
                            </td>
                            <td class="min-w-48 w-full align-top">
                                <div class="flex items-center gap-2">
                                    <span id="due_date_display" class="text-sm font-medium text-foreground">
                                        {{ $invoice->due_date->format('d M Y') }}
                                    </span>
                                    <input type="hidden" 
                                           name="due_date" 
                                           id="due_date"
                                           value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}"
                                           required/>
                                </div>
                                <div class="text-xs text-muted-foreground/60 mt-1">
                                    Wordt automatisch berekend op basis van factuurdatum + {{ $settings->payment_terms_days ?? 30 }} dagen
                                </div>
                                @error('due_date')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Status <span class="text-destructive">*</span>
                            </td>
                            <td class="min-w-48 w-full">
                                <select class="kt-select" name="status" id="status" required>
                                    <option value="draft" {{ old('status', $invoice->status) == 'draft' ? 'selected' : '' }}>Concept</option>
                                    <option value="in_progress" {{ old('status', $invoice->status) == 'in_progress' ? 'selected' : '' }}>In behandeling</option>
                                    <option value="sent" {{ old('status', $invoice->status) == 'sent' ? 'selected' : '' }}>Verzonden</option>
                                    <option value="paid" {{ old('status', $invoice->status) == 'paid' ? 'selected' : '' }}>Betaald</option>
                                    <option value="cancelled" {{ old('status', $invoice->status) == 'cancelled' ? 'selected' : '' }}>Geannuleerd</option>
                                </select>
                                @error('status')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Amount Information -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Bedrag Informatie
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Bedrag (excl. BTW) <span class="text-destructive">*</span>
                            </td>
                            <td class="min-w-48 w-full">
                                <input class="kt-input @error('amount') border-destructive @enderror" 
                                       type="number" 
                                       name="amount" 
                                       id="amount"
                                       value="{{ old('amount', $invoice->amount) }}"
                                       step="0.01"
                                       min="0"
                                       required>
                                @error('amount')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                BTW Percentage
                            </td>
                            <td class="min-w-48 w-full">
                                @php
                                    $currentTaxRate = $invoice->amount > 0 ? ($invoice->tax_amount / $invoice->amount) * 100 : ($settings->default_tax_rate ?? 21);
                                    $currentTaxRate = (int)round($currentTaxRate);
                                @endphp
                                <input class="kt-input" 
                                       type="number" 
                                       name="tax_rate" 
                                       id="tax_rate"
                                       value="{{ old('tax_rate', $currentTaxRate) }}"
                                       step="1"
                                       min="0"
                                       max="100">
                                <div class="text-xs text-secondary-foreground mt-1">Standaard: {{ (int)($settings->default_tax_rate ?? 21) }}%</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                BTW Bedrag
                            </td>
                            <td class="min-w-48 w-full">
                                <input class="kt-input" 
                                       type="text" 
                                       name="tax_amount_display" 
                                       id="tax_amount"
                                       value="{{ old('tax_amount', '€ ' . number_format($invoice->tax_amount, 2, ',', '.')) }}"
                                       readonly>
                                <input type="hidden" name="tax_amount" id="tax_amount_hidden" value="{{ old('tax_amount', $invoice->tax_amount) }}">
                                @error('tax_amount')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Totaal Bedrag (incl. BTW) <span class="text-destructive">*</span>
                            </td>
                            <td class="min-w-48 w-full">
                                <input class="kt-input font-semibold @error('total_amount') border-destructive @enderror" 
                                       type="text" 
                                       name="total_amount_display" 
                                       id="total_amount"
                                       value="{{ old('total_amount', '€ ' . number_format($invoice->total_amount, 2, ',', '.')) }}"
                                       required
                                       readonly>
                                <input type="hidden" name="total_amount" id="total_amount_hidden" value="{{ old('total_amount', $invoice->total_amount) }}">
                                @error('total_amount')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Valuta <span class="text-destructive">*</span>
                            </td>
                            <td class="min-w-48 w-full">
                                <select class="kt-select" name="currency" id="currency" required>
                                    <option value="EUR" {{ old('currency', $invoice->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                                    <option value="USD" {{ old('currency', $invoice->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                                </select>
                                @error('currency')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Omschrijving <span class="text-destructive">*</span>
                            </td>
                            <td class="min-w-48 w-full">
                                @php
                                    $lineItemDescription = 'Match fee';
                                    if ($invoice->line_items && count($invoice->line_items) > 0) {
                                        $lineItemDescription = $invoice->line_items[0]['description'] ?? 'Match fee';
                                    }
                                @endphp
                                <input class="kt-input @error('line_item_description') border-destructive @enderror" 
                                       type="text" 
                                       name="line_item_description" 
                                       id="line_item_description"
                                       value="{{ old('line_item_description', $lineItemDescription) }}"
                                       placeholder="Match fee"
                                       required>
                                @error('line_item_description')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Deelfactuur
                            </td>
                            <td class="min-w-48 w-full">
                                <div class="flex items-center gap-2">
                                    <input type="hidden" name="is_partial" value="0" id="is_partial_hidden">
                                    <label class="kt-label flex items-center">
                                        <input type="checkbox" 
                                               class="kt-switch kt-switch-sm" 
                                               name="is_partial" 
                                               id="is_partial"
                                               value="1"
                                               {{ old('is_partial', $invoice->is_partial) ? 'checked' : '' }}>
                                        <span class="ms-2">Deelfactuur</span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr id="partial-fields-row" class="{{ old('is_partial', $invoice->is_partial) ? '' : 'hidden' }}">
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Ouder Factuurnummer
                            </td>
                            <td class="min-w-48 w-full">
                                <input class="kt-input" 
                                       type="text" 
                                       name="parent_invoice_number" 
                                       id="parent_invoice_number"
                                       value="{{ old('parent_invoice_number', $invoice->parent_invoice_number) }}">
                            </td>
                        </tr>
                        <tr id="partial-number-row" class="{{ old('is_partial', $invoice->is_partial) ? '' : 'hidden' }}">
                            <td class="min-w-56 text-secondary-foreground font-normal">
                                Deelnummer
                            </td>
                            <td class="min-w-48 w-full">
                                <input class="kt-input" 
                                       type="number" 
                                       name="partial_number" 
                                       id="partial_number"
                                       value="{{ old('partial_number', $invoice->partial_number ?? 1) }}"
                                       min="1">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Notes -->
            <div class="kt-card min-w-full lg:col-span-2">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Opmerkingen
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Opmerkingen
                            </td>
                            <td class="min-w-48 w-full">
                                <textarea class="kt-input @error('notes') border-destructive @enderror" 
                                          name="notes" 
                                          id="notes"
                                          rows="4"
                                          placeholder="Optionele opmerkingen...">{{ old('notes', $invoice->notes) }}</textarea>
                                @error('notes')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 mt-5">
            <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="kt-btn kt-btn-outline">
                Annuleren
            </a>
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check text-base me-2"></i>
                Opslaan
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function() {
    'use strict';
    
    // Wacht tot DOM volledig geladen is
    function initInvoiceCalculation() {
        const isPartialCheckbox = document.getElementById('is_partial');
        const isPartialHidden = document.getElementById('is_partial_hidden');
        
        if (isPartialCheckbox) {
            // Update hidden input when checkbox changes
            isPartialCheckbox.addEventListener('change', function() {
                const partialFieldsRow = document.getElementById('partial-fields-row');
                const partialNumberRow = document.getElementById('partial-number-row');
                
                // Update hidden input: if checked, remove it so checkbox value is used; if unchecked, keep hidden input
                if (this.checked) {
                    if (isPartialHidden) isPartialHidden.disabled = true;
                    if (partialFieldsRow) partialFieldsRow.classList.remove('hidden');
                    if (partialNumberRow) partialNumberRow.classList.remove('hidden');
                } else {
                    if (isPartialHidden) isPartialHidden.disabled = false;
                    if (partialFieldsRow) partialFieldsRow.classList.add('hidden');
                    if (partialNumberRow) partialNumberRow.classList.add('hidden');
                }
            });
            
            // Initialize on page load
            if (isPartialCheckbox.checked) {
                if (isPartialHidden) isPartialHidden.disabled = true;
            }
        }

        // Haal elementen op
        const amountInput = document.getElementById('amount');
        const taxRateInput = document.getElementById('tax_rate');
        const taxAmountDisplay = document.getElementById('tax_amount');
        const totalAmountDisplay = document.getElementById('total_amount');
        const taxAmountHidden = document.getElementById('tax_amount_hidden');
        const totalAmountHidden = document.getElementById('total_amount_hidden');

        // Controleer of alle elementen bestaan
        if (!amountInput || !taxRateInput || !taxAmountDisplay || !totalAmountDisplay || !taxAmountHidden || !totalAmountHidden) {
            console.error('Invoice calculation: Not all required elements found', {
                amountInput: !!amountInput,
                taxRateInput: !!taxRateInput,
                taxAmountDisplay: !!taxAmountDisplay,
                totalAmountDisplay: !!totalAmountDisplay,
                taxAmountHidden: !!taxAmountHidden,
                totalAmountHidden: !!totalAmountHidden
            });
            return;
        }

        // Functie om bedragen te formatteren
        function formatCurrency(amount) {
            if (isNaN(amount) || amount === null || amount === undefined) {
                return '€ 0,00';
            }
            return '€ ' + amount.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // Functie om berekening uit te voeren
        function calculateInvoice() {
            const amount = parseFloat(amountInput.value) || 0;
            const taxRate = parseFloat(taxRateInput.value) || 0;
            
            // Bereken BTW bedrag
            const taxAmount = (amount * taxRate) / 100;
            
            // Bereken totaal bedrag (bedrag + BTW)
            const totalAmount = amount + taxAmount;

            // Update display velden
            taxAmountDisplay.value = formatCurrency(taxAmount);
            totalAmountDisplay.value = formatCurrency(totalAmount);
            
            // Update hidden velden (voor form submission)
            taxAmountHidden.value = taxAmount.toFixed(2);
            totalAmountHidden.value = totalAmount.toFixed(2);
            
            // Visuele feedback
            if (taxAmount > 0) {
                taxAmountDisplay.style.fontWeight = '500';
                taxAmountDisplay.style.color = '#059669';
            } else {
                taxAmountDisplay.style.fontWeight = 'normal';
                taxAmountDisplay.style.color = '';
            }
            
            if (totalAmount > 0) {
                totalAmountDisplay.style.fontWeight = '600';
                totalAmountDisplay.style.color = '#2563eb';
            } else {
                totalAmountDisplay.style.fontWeight = 'normal';
                totalAmountDisplay.style.color = '';
            }
        }

        // Event listeners voor real-time berekening
        amountInput.addEventListener('input', calculateInvoice);
        amountInput.addEventListener('change', calculateInvoice);
        amountInput.addEventListener('paste', function() {
            setTimeout(calculateInvoice, 10);
        });
        
        taxRateInput.addEventListener('input', calculateInvoice);
        taxRateInput.addEventListener('change', calculateInvoice);
        taxRateInput.addEventListener('paste', function() {
            setTimeout(calculateInvoice, 10);
        });
        
        // Initial calculation
        calculateInvoice();
        
        // Auto-calculate due date based on invoice date
        const invoiceDateInput = document.getElementById('invoice_date');
        const dueDateInput = document.getElementById('due_date');
        const dueDateDisplay = document.getElementById('due_date_display');
        const paymentTermsDays = {{ $settings->payment_terms_days ?? 30 }};
        
        function formatDateForDisplay(date) {
            const months = ['Jan', 'Feb', 'Mrt', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'];
            const day = String(date.getDate()).padStart(2, '0');
            const month = months[date.getMonth()];
            const year = date.getFullYear();
            return `${day} ${month} ${year}`;
        }
        
        function calculateDueDate() {
            if (!invoiceDateInput || !dueDateInput || !dueDateDisplay) {
                return;
            }
            
            const invoiceDateValue = invoiceDateInput.value;
            if (!invoiceDateValue) {
                dueDateInput.value = '';
                dueDateDisplay.textContent = '--';
                return;
            }
            
            // Parse the date (format: dd-MM-YYYY or YYYY-MM-DD)
            let invoiceDate;
            if (invoiceDateValue.includes('-') && invoiceDateValue.split('-')[0].length === 2) {
                // Format: dd-MM-YYYY
                const parts = invoiceDateValue.split('-');
                invoiceDate = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
            } else {
                // Format: YYYY-MM-DD (fallback)
                invoiceDate = new Date(invoiceDateValue + 'T00:00:00');
            }
            
            if (isNaN(invoiceDate.getTime())) {
                return;
            }
            
            // Add payment terms days
            const dueDate = new Date(invoiceDate);
            dueDate.setDate(dueDate.getDate() + paymentTermsDays);
            
            // Format as YYYY-MM-DD for hidden input
            const year = dueDate.getFullYear();
            const month = String(dueDate.getMonth() + 1).padStart(2, '0');
            const day = String(dueDate.getDate()).padStart(2, '0');
            dueDateInput.value = `${year}-${month}-${day}`;
            
            // Format for display (d M Y format)
            dueDateDisplay.textContent = formatDateForDisplay(dueDate);
        }
        
        if (invoiceDateInput && dueDateInput) {
            // Function to check and calculate due date
            function checkAndCalculateDueDate() {
                // Small delay to ensure date picker has updated the value
                setTimeout(function() {
                    if (invoiceDateInput.value) {
                        calculateDueDate();
                    }
                }, 100);
            }
            
            // Listen for various events that might be triggered by date picker
            invoiceDateInput.addEventListener('change', checkAndCalculateDueDate);
            invoiceDateInput.addEventListener('input', checkAndCalculateDueDate);
            invoiceDateInput.addEventListener('blur', checkAndCalculateDueDate);
            
            // Use MutationObserver to detect when the input value changes (for date picker)
            const observer = new MutationObserver(function() {
                checkAndCalculateDueDate();
            });
            
            observer.observe(invoiceDateInput, {
                attributes: true,
                attributeFilter: ['value'],
                childList: false,
                subtree: false
            });
            
            // Also poll for changes (fallback for date picker)
            let lastValue = invoiceDateInput.value;
            setInterval(function() {
                if (invoiceDateInput.value !== lastValue) {
                    lastValue = invoiceDateInput.value;
                    checkAndCalculateDueDate();
                }
            }, 500);
            
            // Calculate on page load if invoice date is already set
            if (invoiceDateInput.value) {
                calculateDueDate();
            }
        }
    }
    
    // Load matches for company
    function loadMatchesForCompany(companyId, selectedMatchId = null) {
        const matchSelect = document.getElementById('job_match_id');
        if (!matchSelect || !companyId) {
            console.log('loadMatchesForCompany: matchSelect or companyId missing', { matchSelect: !!matchSelect, companyId });
            return;
        }
        
        console.log('Loading matches for company:', companyId);
        
        // Clear existing options except the first one
        while (matchSelect.options.length > 1) {
            matchSelect.remove(1);
        }
        
        // Show loading state
        const loadingOption = document.createElement('option');
        loadingOption.value = '';
        loadingOption.textContent = 'Laden...';
        loadingOption.disabled = true;
        matchSelect.appendChild(loadingOption);
        
        // Fetch matches for company
        const url = `{{ route('admin.invoices.matches-for-company') }}?company_id=${companyId}`;
        console.log('Fetching from:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(matches => {
            console.log('Matches received:', matches);
            
            // Clear loading option and existing options
            while (matchSelect.options.length > 1) {
                matchSelect.remove(1);
            }
            
            // Set default option text based on whether matches were found
            const defaultOption = matchSelect.options[0];
            if (defaultOption) {
                if (matches && matches.length > 0) {
                    defaultOption.textContent = 'Selecteer een kandidaat';
                } else {
                    defaultOption.textContent = 'Geen aangenomen match gevonden';
                }
            }
            
            // Add matches to select
            if (matches && matches.length > 0) {
                matches.forEach(match => {
                    const option = document.createElement('option');
                    option.value = match.id;
                    option.textContent = match.text;
                    if (selectedMatchId && match.id == selectedMatchId) {
                        option.selected = true;
                    }
                    matchSelect.appendChild(option);
                });
            }
            
            // Reinitialize KTSelect if it exists
            const selectWrapper = matchSelect.closest('.kt-select-wrapper');
            if (selectWrapper && window.KTSelect && typeof window.KTSelect.init === 'function') {
                try {
                    // Destroy existing instance if any
                    const instance = window.KTSelect.getInstance(matchSelect);
                    if (instance) {
                        instance.destroy();
                    }
                    // Reinitialize
                    window.KTSelect.init(matchSelect);
                    console.log('KTSelect reinitialized');
                } catch (e) {
                    console.warn('KTSelect init error:', e);
                }
            } else {
                // Fallback: trigger change event to update display
                matchSelect.dispatchEvent(new Event('change'));
            }
        })
        .catch(error => {
            console.error('Error loading matches:', error);
            while (matchSelect.options.length > 1) {
                matchSelect.remove(1);
            }
            // Ensure default option text is set
            const defaultOption = matchSelect.options[0];
            if (defaultOption) {
                defaultOption.textContent = 'Geen aangenomen match gevonden';
            }
        });
    }
    
    // Initialize company change handler
    function initCompanyMatchLoader() {
        // Find the native select element (KTSelect wraps it)
        const companySelect = document.getElementById('company_id');
        const matchSelect = document.getElementById('job_match_id');
        const savedMatchId = {{ old('job_match_id', $invoice->job_match_id ?? 'null') }};
        
        console.log('initCompanyMatchLoader called', { companySelect: !!companySelect, matchSelect: !!matchSelect, savedMatchId });
        
        if (companySelect) {
            // Load matches when company changes - listen on the native select
            companySelect.addEventListener('change', function() {
                console.log('Company changed to:', this.value);
                const companyId = this.value;
                if (companyId) {
                    loadMatchesForCompany(companyId);
                } else {
                    // Clear matches if no company selected
                    if (matchSelect) {
                        while (matchSelect.options.length > 1) {
                            matchSelect.remove(1);
                        }
                        // Set default option text
                        const defaultOption = matchSelect.options[0];
                        if (defaultOption) {
                            defaultOption.textContent = 'Geen aangenomen match gevonden';
                        }
                        // Reinitialize KTSelect
                        const selectWrapper = matchSelect.closest('.kt-select-wrapper');
                        if (selectWrapper && window.KTSelect && typeof window.KTSelect.init === 'function') {
                            try {
                                const instance = window.KTSelect.getInstance(matchSelect);
                                if (instance) {
                                    instance.destroy();
                                }
                                window.KTSelect.init(matchSelect);
                            } catch (e) {
                                console.warn('KTSelect init error:', e);
                            }
                        }
                    }
                }
            });
            
            // Also listen on the KTSelect display element if it exists
            const companySelectWrapper = companySelect.closest('.kt-select-wrapper');
            if (companySelectWrapper) {
                const companySelectDisplay = companySelectWrapper.querySelector('.kt-select-display');
                if (companySelectDisplay) {
                    companySelectDisplay.addEventListener('click', function() {
                        // Wait for selection to complete
                        setTimeout(function() {
                            const companyId = companySelect.value;
                            if (companyId) {
                                loadMatchesForCompany(companyId);
                            }
                        }, 100);
                    });
                }
            }
            
            // Load matches on page load if company is already selected
            const currentCompanyId = companySelect.value;
            console.log('Current company ID on load:', currentCompanyId);
            if (currentCompanyId) {
                // Wait a bit for KTSelect to initialize first
                setTimeout(function() {
                    loadMatchesForCompany(currentCompanyId, savedMatchId);
                }, 500);
            }
        } else {
            console.error('Company select not found');
        }
    }
    
    // Initialiseer direct als DOM al geladen is, anders wacht op DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initInvoiceCalculation();
            initCompanyMatchLoader();
        });
    } else {
        // DOM is al geladen, maar wacht even om zeker te zijn dat alle scripts geladen zijn
        setTimeout(function() {
            initInvoiceCalculation();
            initCompanyMatchLoader();
        }, 50);
    }
})();
</script>
@endpush
@endsection

