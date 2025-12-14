@extends('admin.layouts.app')

@section('title', 'Nieuwe Factuur')

@section('content')
<div class="kt-container-fixed">
    <!-- Page Title -->
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuwe Factuur
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Maak een nieuwe factuur aan
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.invoices.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left text-base me-2"></i>
                Annuleren
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.invoices.store') }}">
        @csrf
        
        <div class="grid lg:grid-cols-2 gap-5 lg:gap-7.5">
            <!-- Basic Information -->
            <div class="kt-card">
                <div class="kt-kt-card-header">
                    <h3 class="kt-kt-card-title">
                        Basis Informatie
                    </h3>
                </div>
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5">
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="company_id">
                            Bedrijf <span class="text-destructive">*</span>
                        </label>
                        <select class="kt-select" name="company_id" id="company_id" required>
                            <option value="">Selecteer bedrijf</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="job_match_id">
                            Match (optioneel)
                        </label>
                        <select class="kt-select" name="job_match_id" id="job_match_id">
                            <option value="">Geen match</option>
                            @foreach($jobMatches as $match)
                                <option value="{{ $match->id }}" {{ old('job_match_id') == $match->id ? 'selected' : '' }}>
                                    Match #{{ $match->id }} - {{ $match->company->name ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        @error('job_match_id')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="invoice_date">
                            Factuurdatum <span class="text-destructive">*</span>
                        </label>
                        <!--begin::Input with Calendar-->
                        <div class="kt-input w-64 @error('invoice_date') border-destructive @enderror">
                            <i class="ki-outline ki-calendar"></i>
                            <input class="grow" 
                                   name="invoice_date" 
                                   id="invoice_date"
                                   value="{{ old('invoice_date', date('Y-m-d')) }}"
                                   data-kt-date-picker="true" 
                                   data-kt-date-picker-input-mode="true" 
                                   data-kt-date-picker-position-to-input="left"
                                   data-kt-date-picker-format="yyyy-MM-dd"
                                   placeholder="Selecteer datum" 
                                   readonly 
                                   type="text"
                                   required/>
                        </div>
                        @error('invoice_date')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                        <!--end::Input with Calendar-->
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="due_date">
                            Vervaldatum <span class="text-destructive">*</span>
                        </label>
                        <!--begin::Input with Calendar-->
                        <div class="kt-input w-64 @error('due_date') border-destructive @enderror">
                            <i class="ki-outline ki-calendar"></i>
                            <input class="grow" 
                                   name="due_date" 
                                   id="due_date"
                                   value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}"
                                   data-kt-date-picker="true" 
                                   data-kt-date-picker-input-mode="true" 
                                   data-kt-date-picker-position-to-input="left"
                                   data-kt-date-picker-format="yyyy-MM-dd"
                                   placeholder="Selecteer datum" 
                                   readonly 
                                   type="text"
                                   required/>
                        </div>
                        @error('due_date')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                        <!--end::Input with Calendar-->
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="status">
                            Status <span class="text-destructive">*</span>
                        </label>
                        <select class="kt-select" name="status" id="status" required>
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Concept</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In behandeling</option>
                            <option value="sent" {{ old('status') == 'sent' ? 'selected' : '' }}>Verzonden</option>
                            <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>Betaald</option>
                        </select>
                        @error('status')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Amount Information -->
            <div class="kt-card">
                <div class="kt-kt-card-header">
                    <h3 class="kt-kt-card-title">
                        Bedrag Informatie
                    </h3>
                </div>
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5">
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="amount">
                            Bedrag (excl. BTW) <span class="text-destructive">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input class="kt-input" 
                                   type="number" 
                                   name="amount" 
                                   id="amount"
                                   value="{{ old('amount', $settings->default_amount ?? '') }}"
                                   step="0.01"
                                   min="0"
                                   required>
                            @if($settings->default_amount)
                            <button type="button" 
                                    class="kt-btn kt-btn-sm kt-btn-outline" 
                                    id="use-default-amount"
                                    title="Gebruik standaard bedrag: €{{ number_format($settings->default_amount, 2, ',', '.') }}">
                                <i class="ki-filled ki-arrow-down text-base"></i>
                            </button>
                            @endif
                        </div>
                        @if($settings->default_amount)
                        <span class="text-xs text-secondary-foreground">Standaard bedrag: €{{ number_format($settings->default_amount, 2, ',', '.') }}</span>
                        @endif
                        @error('amount')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="tax_rate">
                            BTW Percentage
                        </label>
                        <input class="kt-input" 
                               type="number" 
                               name="tax_rate" 
                               id="tax_rate"
                               value="{{ old('tax_rate', (int)($settings->default_tax_rate ?? 21)) }}"
                               step="1"
                               min="0"
                               max="100">
                        <span class="text-xs text-secondary-foreground">Standaard: {{ (int)($settings->default_tax_rate ?? 21) }}%</span>
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="tax_amount">
                            BTW Bedrag
                        </label>
                        <input class="kt-input" 
                               type="text" 
                               name="tax_amount_display" 
                               id="tax_amount"
                               value="€ 0,00"
                               readonly>
                        <input type="hidden" name="tax_amount" id="tax_amount_hidden" value="0.00">
                        @error('tax_amount')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="total_amount">
                            Totaal Bedrag (incl. BTW) <span class="text-destructive">*</span>
                        </label>
                        <input class="kt-input font-semibold" 
                               type="text" 
                               name="total_amount_display" 
                               id="total_amount"
                               value="€ 0,00"
                               required
                               readonly>
                        <input type="hidden" name="total_amount" id="total_amount_hidden" value="0.00">
                        @error('total_amount')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="currency">
                            Valuta <span class="text-destructive">*</span>
                        </label>
                        <select class="kt-select" name="currency" id="currency" required>
                            <option value="EUR" {{ old('currency', 'EUR') == 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                        </select>
                        @error('currency')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <input class="kt-checkbox" 
                               type="checkbox" 
                               name="is_partial" 
                               id="is_partial"
                               value="1"
                               {{ old('is_partial') ? 'checked' : '' }}>
                        <label class="kt-checkbox-label" for="is_partial">
                            Deelfactuur
                        </label>
                    </div>
                    
                    <div id="partial-fields" class="hidden flex flex-col gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="parent_invoice_number">
                                Ouder Factuurnummer
                            </label>
                            <input class="kt-input" 
                                   type="text" 
                                   name="parent_invoice_number" 
                                   id="parent_invoice_number"
                                   value="{{ old('parent_invoice_number') }}">
                        </div>
                        
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="partial_number">
                                Deelnummer
                            </label>
                            <input class="kt-input" 
                                   type="number" 
                                   name="partial_number" 
                                   id="partial_number"
                                   value="{{ old('partial_number', 1) }}"
                                   min="1">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="kt-card lg:col-span-2">
                <div class="kt-kt-card-header">
                    <h3 class="kt-kt-card-title">
                        Opmerkingen
                    </h3>
                </div>
                <div class="kt-card-content p-5 lg:p-7.5">
                    <textarea class="kt-input" 
                              name="notes" 
                              id="notes"
                              rows="4"
                              placeholder="Optionele opmerkingen...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <span class="text-sm text-destructive">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 mt-5">
            <a href="{{ route('admin.invoices.index') }}" class="kt-btn kt-btn-outline">
                Annuleren
            </a>
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check text-base me-2"></i>
                Factuur Aanmaken
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
console.log('Invoice calculation script loaded!');
(function() {
    'use strict';
    
    console.log('Invoice calculation IIFE started');
    
    // Wacht tot DOM volledig geladen is
    function initInvoiceCalculation() {
        console.log('initInvoiceCalculation called');
        const isPartialCheckbox = document.getElementById('is_partial');
        const partialFields = document.getElementById('partial-fields');
        
        if (isPartialCheckbox && partialFields) {
            isPartialCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    partialFields.classList.remove('hidden');
                } else {
                    partialFields.classList.add('hidden');
                }
            });
            
            if (isPartialCheckbox.checked) {
                partialFields.classList.remove('hidden');
            }
        }

        // Haal elementen op
        const amountInput = document.getElementById('amount');
        const taxRateInput = document.getElementById('tax_rate');
        const taxAmountDisplay = document.getElementById('tax_amount');
        const totalAmountDisplay = document.getElementById('total_amount');
        const taxAmountHidden = document.getElementById('tax_amount_hidden');
        const totalAmountHidden = document.getElementById('total_amount_hidden');
        const useDefaultBtn = document.getElementById('use-default-amount');

        // Controleer of alle elementen bestaan
        console.log('Checking elements:', {
            amountInput: !!amountInput,
            taxRateInput: !!taxRateInput,
            taxAmountDisplay: !!taxAmountDisplay,
            totalAmountDisplay: !!totalAmountDisplay,
            taxAmountHidden: !!taxAmountHidden,
            totalAmountHidden: !!totalAmountHidden
        });
        
        if (!amountInput || !taxRateInput || !taxAmountDisplay || !totalAmountDisplay || !taxAmountHidden || !totalAmountHidden) {
            console.error('Invoice calculation: Not all required elements found');
            return;
        }
        
        console.log('All elements found, initializing calculation');

        // Functie om bedragen te formatteren
        function formatCurrency(amount) {
            if (isNaN(amount) || amount === null || amount === undefined) {
                return '€ 0,00';
            }
            return '€ ' + amount.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // Functie om berekening uit te voeren
        function calculateInvoice() {
            console.log('calculateInvoice called');
            const amount = parseFloat(amountInput.value) || 0;
            const taxRate = parseFloat(taxRateInput.value) || 0;
            
            console.log('Values:', { amount, taxRate });
            
            // Bereken BTW bedrag
            const taxAmount = (amount * taxRate) / 100;
            
            // Bereken totaal bedrag (bedrag + BTW)
            const totalAmount = amount + taxAmount;

            console.log('Calculated:', { taxAmount, totalAmount });

            // Update display velden
            taxAmountDisplay.value = formatCurrency(taxAmount);
            totalAmountDisplay.value = formatCurrency(totalAmount);
            
            console.log('Updated display fields:', {
                taxAmount: taxAmountDisplay.value,
                totalAmount: totalAmountDisplay.value
            });
            
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

        // Default amount button
        if (useDefaultBtn) {
            useDefaultBtn.addEventListener('click', function() {
                amountInput.value = {{ $settings->default_amount ?? 0 }};
                calculateInvoice();
            });
        }
        
        // Set default amount on page load if not set
        if (!amountInput.value && {{ $settings->default_amount ?? 0 }}) {
            amountInput.value = {{ $settings->default_amount ?? 0 }};
        }
        
        // Initial calculation
        console.log('Running initial calculation');
        calculateInvoice();
        
        console.log('Invoice calculation initialized successfully');
    }
    
    // Initialiseer direct als DOM al geladen is, anders wacht op DOMContentLoaded
    console.log('Document readyState:', document.readyState);
    if (document.readyState === 'loading') {
        console.log('Waiting for DOMContentLoaded');
        document.addEventListener('DOMContentLoaded', initInvoiceCalculation);
    } else {
        console.log('DOM already loaded, initializing after delay');
        // DOM is al geladen, maar wacht even om zeker te zijn dat alle scripts geladen zijn
        setTimeout(initInvoiceCalculation, 50);
    }
})();
</script>
@endpush
@endsection

