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
                                <option value="{{ $company->id }}" {{ old('company_id', $invoice->company_id) == $company->id ? 'selected' : '' }}>
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
                                <option value="{{ $match->id }}" {{ old('job_match_id', $invoice->job_match_id) == $match->id ? 'selected' : '' }}>
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
                        <input class="kt-input" 
                               type="date" 
                               name="invoice_date" 
                               id="invoice_date"
                               value="{{ old('invoice_date', $invoice->invoice_date->format('Y-m-d')) }}"
                               required>
                        @error('invoice_date')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="due_date">
                            Vervaldatum <span class="text-destructive">*</span>
                        </label>
                        <input class="kt-input" 
                               type="date" 
                               name="due_date" 
                               id="due_date"
                               value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}"
                               required>
                        @error('due_date')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="status">
                            Status <span class="text-destructive">*</span>
                        </label>
                        <select class="kt-select" name="status" id="status" required>
                            <option value="draft" {{ old('status', $invoice->status) == 'draft' ? 'selected' : '' }}>Concept</option>
                            <option value="in_progress" {{ old('status', $invoice->status) == 'in_progress' ? 'selected' : '' }}>In behandeling</option>
                            <option value="sent" {{ old('status', $invoice->status) == 'sent' ? 'selected' : '' }}>Verzonden</option>
                            <option value="paid" {{ old('status', $invoice->status) == 'paid' ? 'selected' : '' }}>Betaald</option>
                            <option value="cancelled" {{ old('status', $invoice->status) == 'cancelled' ? 'selected' : '' }}>Geannuleerd</option>
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
                        <input class="kt-input" 
                               type="number" 
                               name="amount" 
                               id="amount"
                               value="{{ old('amount', $invoice->amount) }}"
                               step="0.01"
                               min="0"
                               required>
                        @error('amount')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="tax_rate">
                            BTW Percentage
                        </label>
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
                               value="{{ old('tax_amount', '€ ' . number_format($invoice->tax_amount, 2, ',', '.')) }}"
                               readonly>
                        <input type="hidden" name="tax_amount" id="tax_amount_hidden" value="{{ old('tax_amount', $invoice->tax_amount) }}">
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
                               value="{{ old('total_amount', '€ ' . number_format($invoice->total_amount, 2, ',', '.')) }}"
                               required
                               readonly>
                        <input type="hidden" name="total_amount" id="total_amount_hidden" value="{{ old('total_amount', $invoice->total_amount) }}">
                        @error('total_amount')
                            <span class="text-sm text-destructive">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="currency">
                            Valuta <span class="text-destructive">*</span>
                        </label>
                        <select class="kt-select" name="currency" id="currency" required>
                            <option value="EUR" {{ old('currency', $invoice->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="USD" {{ old('currency', $invoice->currency) == 'USD' ? 'selected' : '' }}>USD</option>
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
                               {{ old('is_partial', $invoice->is_partial) ? 'checked' : '' }}>
                        <label class="kt-checkbox-label" for="is_partial">
                            Deelfactuur
                        </label>
                    </div>
                    
                    <div id="partial-fields" class="{{ old('is_partial', $invoice->is_partial) ? '' : 'hidden' }} flex flex-col gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="parent_invoice_number">
                                Ouder Factuurnummer
                            </label>
                            <input class="kt-input" 
                                   type="text" 
                                   name="parent_invoice_number" 
                                   id="parent_invoice_number"
                                   value="{{ old('parent_invoice_number', $invoice->parent_invoice_number) }}">
                        </div>
                        
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="partial_number">
                                Deelnummer
                            </label>
                            <input class="kt-input" 
                                   type="number" 
                                   name="partial_number" 
                                   id="partial_number"
                                   value="{{ old('partial_number', $invoice->partial_number ?? 1) }}"
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
                              placeholder="Optionele opmerkingen...">{{ old('notes', $invoice->notes) }}</textarea>
                    @error('notes')
                        <span class="text-sm text-destructive">{{ $message }}</span>
                    @enderror
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
        const partialFields = document.getElementById('partial-fields');
        
        if (isPartialCheckbox) {
            isPartialCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    partialFields.classList.remove('hidden');
                } else {
                    partialFields.classList.add('hidden');
                }
            });
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
    }
    
    // Initialiseer direct als DOM al geladen is, anders wacht op DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInvoiceCalculation);
    } else {
        // DOM is al geladen, maar wacht even om zeker te zijn dat alle scripts geladen zijn
        setTimeout(initInvoiceCalculation, 50);
    }
})();
</script>
@endpush
@endsection

