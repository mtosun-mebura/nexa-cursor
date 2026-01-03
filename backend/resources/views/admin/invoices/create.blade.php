@extends('admin.layouts.app')

@section('title', 'Nieuwe Factuur')

@section('content')

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
</style>
@endpush

<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                Nieuwe Factuur
            </h1>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.invoices.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.invoices.store') }}" data-validate="true">
        @csrf
        
        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <!-- Basis Informatie -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Basis Informatie
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Bedrijf *
                            </td>
                            <td class="min-w-48 w-full">
                                <select class="kt-select @error('company_id') border-destructive @enderror" 
                                        name="company_id" 
                                        id="company_id" 
                                        data-kt-select="true"
                                        required>
                                    <option value="">Selecteer bedrijf</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
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
                            <td class="text-secondary-foreground font-normal align-top">
                                Match (optioneel)
                            </td>
                            <td>
                                <select class="kt-select @error('job_match_id') border-destructive @enderror" 
                                        name="job_match_id" 
                                        id="job_match_id"
                                        data-kt-select="true">
                                    <option value="">Geen match</option>
                                    @foreach($jobMatches as $match)
                                        <option value="{{ $match->id }}" {{ old('job_match_id') == $match->id ? 'selected' : '' }}>
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
                            <td class="text-secondary-foreground font-normal align-top">
                                Factuurdatum *
                            </td>
                            <td>
                                <div class="kt-input w-64 @error('invoice_date') border-destructive @enderror">
                                    <i class="ki-outline ki-calendar"></i>
                                    <input class="grow" 
                                           name="invoice_date" 
                                           id="invoice_date"
                                           value="{{ old('invoice_date', date('Y-m-d')) }}"
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
                            <td class="text-secondary-foreground font-normal align-top">
                                Vervaldatum *
                            </td>
                            <td>
                                <div class="kt-input w-64 @error('due_date') border-destructive @enderror">
                                    <i class="ki-outline ki-calendar"></i>
                                    <input class="grow" 
                                           name="due_date" 
                                           id="due_date"
                                           value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}"
                                           data-kt-date-picker="true" 
                                           data-kt-date-picker-input-mode="true" 
                                           data-kt-date-picker-position-to-input="left"
                                           data-kt-date-picker-format="dd-MM-yyyy"
                                           placeholder="Selecteer datum" 
                                           readonly 
                                           type="text"
                                           required/>
                                </div>
                                @error('due_date')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Status *
                            </td>
                            <td>
                                <select class="kt-select @error('status') border-destructive @enderror" 
                                        name="status" 
                                        id="status"
                                        data-kt-select="true"
                                        required>
                                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Concept</option>
                                    <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In behandeling</option>
                                    <option value="sent" {{ old('status') == 'sent' ? 'selected' : '' }}>Verzonden</option>
                                    <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>Betaald</option>
                                </select>
                                @error('status')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Bedrag Informatie -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Bedrag Informatie
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Bedrag (excl. BTW) *
                            </td>
                            <td class="min-w-48 w-full">
                                <div class="flex gap-2">
                                    <input class="kt-input @error('amount') border-destructive @enderror" 
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
                                <div class="text-xs text-muted-foreground mt-1">Standaard bedrag: €{{ number_format($settings->default_amount, 2, ',', '.') }}</div>
                                @endif
                                @error('amount')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                BTW Percentage
                            </td>
                            <td>
                                <input class="kt-input @error('tax_rate') border-destructive @enderror" 
                                       type="number" 
                                       name="tax_rate" 
                                       id="tax_rate"
                                       value="{{ old('tax_rate', (int)($settings->default_tax_rate ?? 21)) }}"
                                       step="1"
                                       min="0"
                                       max="100">
                                <div class="text-xs text-muted-foreground mt-1">Standaard: {{ (int)($settings->default_tax_rate ?? 21) }}%</div>
                                @error('tax_rate')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                BTW Bedrag
                            </td>
                            <td>
                                <input class="kt-input" 
                                       type="text" 
                                       name="tax_amount_display" 
                                       id="tax_amount"
                                       value="€ 0,00"
                                       readonly>
                                <input type="hidden" name="tax_amount" id="tax_amount_hidden" value="0.00">
                                @error('tax_amount')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Totaal Bedrag (incl. BTW) *
                            </td>
                            <td>
                                <input class="kt-input font-semibold" 
                                       type="text" 
                                       name="total_amount_display" 
                                       id="total_amount"
                                       value="€ 0,00"
                                       required
                                       readonly>
                                <input type="hidden" name="total_amount" id="total_amount_hidden" value="0.00">
                                @error('total_amount')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Valuta *
                            </td>
                            <td>
                                <select class="kt-select @error('currency') border-destructive @enderror" 
                                        name="currency" 
                                        id="currency"
                                        data-kt-select="true"
                                        required>
                                    <option value="EUR" {{ old('currency', 'EUR') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                    <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                                </select>
                                @error('currency')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Deelfactuur
                            </td>
                            <td>
                                <label class="kt-label flex items-center">
                                    <input class="kt-checkbox" 
                                           type="checkbox" 
                                           name="is_partial" 
                                           id="is_partial"
                                           value="1"
                                           {{ old('is_partial') ? 'checked' : '' }}>
                                    <span class="ms-2">Deelfactuur</span>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Deelfactuur Velden -->
            <div id="partial-fields" class="kt-card min-w-full hidden">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Deelfactuur Informatie
                    </h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">
                                Ouder Factuurnummer
                            </td>
                            <td class="min-w-48 w-full">
                                <input class="kt-input @error('parent_invoice_number') border-destructive @enderror" 
                                       type="text" 
                                       name="parent_invoice_number" 
                                       id="parent_invoice_number"
                                       value="{{ old('parent_invoice_number') }}">
                                @error('parent_invoice_number')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">
                                Deelnummer
                            </td>
                            <td>
                                <input class="kt-input @error('partial_number') border-destructive @enderror" 
                                       type="number" 
                                       name="partial_number" 
                                       id="partial_number"
                                       value="{{ old('partial_number', 1) }}"
                                       min="1">
                                @error('partial_number')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Opmerkingen -->
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Opmerkingen
                    </h3>
                </div>
                <div class="kt-card-content pb-3">
                    <textarea class="kt-input @error('notes') border-destructive @enderror" 
                              name="notes" 
                              id="notes"
                              rows="4"
                              placeholder="Optionele opmerkingen...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                    @enderror
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
                    Factuur Aanmaken
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle partial fields
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

    // Invoice calculation
    const amountInput = document.getElementById('amount');
    const taxRateInput = document.getElementById('tax_rate');
    const taxAmountDisplay = document.getElementById('tax_amount');
    const totalAmountDisplay = document.getElementById('total_amount');
    const taxAmountHidden = document.getElementById('tax_amount_hidden');
    const totalAmountHidden = document.getElementById('total_amount_hidden');
    const useDefaultBtn = document.getElementById('use-default-amount');

    if (!amountInput || !taxRateInput || !taxAmountDisplay || !totalAmountDisplay || !taxAmountHidden || !totalAmountHidden) {
        console.error('Invoice calculation: Not all required elements found');
        return;
    }

    // Format currency
    function formatCurrency(amount) {
        if (isNaN(amount) || amount === null || amount === undefined) {
            return '€ 0,00';
        }
        return '€ ' + amount.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Calculate invoice
    function calculateInvoice() {
        const amount = parseFloat(amountInput.value) || 0;
        const taxRate = parseFloat(taxRateInput.value) || 0;
        
        // Calculate tax amount
        const taxAmount = (amount * taxRate) / 100;
        
        // Calculate total amount
        const totalAmount = amount + taxAmount;

        // Update display fields
        taxAmountDisplay.value = formatCurrency(taxAmount);
        totalAmountDisplay.value = formatCurrency(totalAmount);
        
        // Update hidden fields
        taxAmountHidden.value = taxAmount.toFixed(2);
        totalAmountHidden.value = totalAmount.toFixed(2);
        
        // Visual feedback
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

    // Event listeners
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
    calculateInvoice();
});
</script>
@endpush
@endsection
