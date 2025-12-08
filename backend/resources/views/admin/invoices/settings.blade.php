@extends('admin.layouts.app')

@section('title', 'Factuurinstellingen')

@section('content')
<div class="kt-container-fixed">
    <!-- Page Title -->
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Factuurinstellingen
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Configureer factuurnummer, betaaltermijn en bedrijfsgegevens
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.invoices.settings.update') }}">
        @csrf
        
        <div class="grid lg:grid-cols-2 gap-5 lg:gap-7.5">
            <!-- Factuurnummer Instellingen -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Factuurnummer Instellingen
                    </h3>
                </div>
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5">
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="invoice_number_prefix">
                            Prefix
                        </label>
                        <input class="kt-input" 
                               type="text" 
                               name="invoice_number_prefix" 
                               id="invoice_number_prefix"
                               value="{{ old('invoice_number_prefix', $settings->invoice_number_prefix) }}"
                               required>
                        <span class="text-xs text-secondary-foreground">Bijv: NX</span>
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="invoice_number_format">
                            Formaat
                        </label>
                        <input class="kt-input" 
                               type="text" 
                               name="invoice_number_format" 
                               id="invoice_number_format"
                               value="{{ old('invoice_number_format', $settings->invoice_number_format) }}"
                               required>
                        <span class="text-xs text-secondary-foreground">Gebruik: {prefix}, {year}, {number}</span>
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="next_invoice_number">
                            Volgende Factuurnummer
                        </label>
                        <input class="kt-input" 
                               type="number" 
                               name="next_invoice_number" 
                               id="next_invoice_number"
                               value="{{ old('next_invoice_number', $settings->next_invoice_number) }}"
                               min="1"
                               required>
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="current_year">
                            Huidig Jaar
                        </label>
                        <input class="kt-input" 
                               type="number" 
                               name="current_year" 
                               id="current_year"
                               value="{{ old('current_year', $settings->current_year) }}"
                               min="2020"
                               max="2100"
                               required>
                    </div>
                </div>
            </div>

            <!-- Betaaltermijn -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Betaaltermijn
                    </h3>
                </div>
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5">
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="payment_terms_days">
                            Betaaltermijn (dagen)
                        </label>
                        <input class="kt-input" 
                               type="number" 
                               name="payment_terms_days" 
                               id="payment_terms_days"
                               value="{{ old('payment_terms_days', $settings->payment_terms_days) }}"
                               min="1"
                               max="365"
                               required>
                        <span class="text-xs text-secondary-foreground">Aantal dagen dat klanten hebben om te betalen</span>
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="default_tax_rate">
                            Standaard BTW Percentage
                        </label>
                        <input class="kt-input" 
                               type="number" 
                               name="default_tax_rate" 
                               id="default_tax_rate"
                               value="{{ old('default_tax_rate', (int)($settings->default_tax_rate ?? 21)) }}"
                               min="0"
                               max="100"
                               step="1"
                               required>
                        <span class="text-xs text-secondary-foreground">Bijv: 21 voor 21%</span>
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="default_amount">
                            Standaard Bedrag (excl. BTW)
                        </label>
                        <input class="kt-input" 
                               type="number" 
                               name="default_amount" 
                               id="default_amount"
                               value="{{ old('default_amount', $settings->default_amount) }}"
                               min="0"
                               step="any">
                        <span class="text-xs text-secondary-foreground">Standaard bedrag dat wordt voorgesteld bij het aanmaken van een nieuwe factuur (bijv: 10000 of 10000.00)</span>
                    </div>
                </div>
            </div>

            <!-- Bedrijfsgegevens -->
            <div class="kt-card lg:col-span-2">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">
                        Bedrijfsgegevens
                    </h3>
                </div>
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5">
                    <div class="grid lg:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="company_name">
                                Bedrijfsnaam
                            </label>
                            <input class="kt-input" 
                                   type="text" 
                                   name="company_name" 
                                   id="company_name"
                                   value="{{ old('company_name', $settings->company_name) }}">
                        </div>
                        
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="company_vat_number">
                                BTW Nummer
                            </label>
                            <input class="kt-input" 
                                   type="text" 
                                   name="company_vat_number" 
                                   id="company_vat_number"
                                   value="{{ old('company_vat_number', $settings->company_vat_number) }}">
                        </div>
                        
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="company_address">
                                Adres
                            </label>
                            <input class="kt-input" 
                                   type="text" 
                                   name="company_address" 
                                   id="company_address"
                                   value="{{ old('company_address', $settings->company_address) }}">
                        </div>
                        
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="company_city">
                                Stad
                            </label>
                            <input class="kt-input" 
                                   type="text" 
                                   name="company_city" 
                                   id="company_city"
                                   value="{{ old('company_city', $settings->company_city) }}">
                        </div>
                        
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="company_postal_code">
                                Postcode
                            </label>
                            <input class="kt-input" 
                                   type="text" 
                                   name="company_postal_code" 
                                   id="company_postal_code"
                                   value="{{ old('company_postal_code', $settings->company_postal_code) }}">
                        </div>
                        
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="company_country">
                                Land
                            </label>
                            <input class="kt-input" 
                                   type="text" 
                                   name="company_country" 
                                   id="company_country"
                                   value="{{ old('company_country', $settings->company_country) }}">
                        </div>
                        
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="company_email">
                                E-mail
                            </label>
                            <input class="kt-input" 
                                   type="email" 
                                   name="company_email" 
                                   id="company_email"
                                   value="{{ old('company_email', $settings->company_email) }}">
                        </div>
                        
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="company_phone">
                                Telefoon
                            </label>
                            <input class="kt-input" 
                                   type="text" 
                                   name="company_phone" 
                                   id="company_phone"
                                   value="{{ old('company_phone', $settings->company_phone) }}">
                        </div>
                        
                        <div class="flex flex-col gap-1">
                            <label class="kt-form-label font-normal text-mono" for="bank_account">
                                Bankrekening
                            </label>
                            <input class="kt-input" 
                                   type="text" 
                                   name="bank_account" 
                                   id="bank_account"
                                   value="{{ old('bank_account', $settings->bank_account) }}">
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="invoice_footer_text">
                            Footer Tekst
                        </label>
                        <textarea class="kt-input" 
                                  name="invoice_footer_text" 
                                  id="invoice_footer_text"
                                  rows="4">{{ old('invoice_footer_text', $settings->invoice_footer_text) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 mt-5">
            <a href="{{ route('admin.invoices.index') }}" class="kt-btn kt-btn-outline">
                Annuleren
            </a>
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check text-base me-2"></i>
                Opslaan
            </button>
        </div>
    </form>
</div>
@endsection

