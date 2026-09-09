@extends('admin.layouts.app')

@section('title', 'NEXA facturatie-instellingen')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-col gap-5 pb-7.5">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <h1 class="text-xl font-medium leading-none text-mono">
                NEXA facturatie-instellingen
            </h1>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.platform-billing.invoices.index') }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.platform-billing.settings.update') }}" data-validate="true">
        @csrf
        @method('PUT')

        <div class="grid gap-5 lg:gap-7.5">
            <x-error-card :errors="$errors" />

            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Automatische facturatie</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Facturatiedag (1–28) <span class="text-destructive">*</span></td>
                            <td class="min-w-48 w-full">
                                <input type="number" name="billing_day" min="1" max="28" class="kt-input @error('billing_day') border-destructive @enderror" style="width: 13ch;" value="{{ old('billing_day', $settings->billing_day) }}" required>
                                @error('billing_day')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Tijd (HH:MM) <span class="text-destructive">*</span></td>
                            <td>
                                <input type="time" name="billing_time" class="kt-input @error('billing_time') border-destructive @enderror" value="{{ old('billing_time', substr($settings->billing_time, 0, 5)) }}" required>
                                @error('billing_time')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Afzender e-mail</td>
                            <td>
                                <input type="email" name="sender_email" class="kt-input w-full @error('sender_email') border-destructive @enderror" value="{{ old('sender_email', $settings->sender_email) }}">
                                <div class="text-xs text-muted-foreground mt-1">Voor verzending van NEXA-facturen</div>
                                @error('sender_email')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Factuurnummer</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Prefix <span class="text-destructive">*</span></td>
                            <td class="min-w-48 w-full">
                                <input type="text" name="invoice_number_prefix" class="kt-input w-full @error('invoice_number_prefix') border-destructive @enderror" value="{{ old('invoice_number_prefix', $settings->invoice_number_prefix) }}" required>
                                <div class="text-xs text-muted-foreground mt-1">Bijv. SAAS</div>
                                @error('invoice_number_prefix')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Formaat <span class="text-destructive">*</span></td>
                            <td>
                                <input type="text" name="invoice_number_format" class="kt-input w-full @error('invoice_number_format') border-destructive @enderror" value="{{ old('invoice_number_format', $settings->invoice_number_format) }}" required>
                                <div class="text-xs text-muted-foreground mt-1">{prefix}, {year}, {number}</div>
                                @error('invoice_number_format')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Volgende nummer <span class="text-destructive">*</span></td>
                            <td>
                                <input type="number" name="next_invoice_number" min="1" class="kt-input @error('next_invoice_number') border-destructive @enderror" style="width: 13ch;" value="{{ old('next_invoice_number', $settings->next_invoice_number) }}" required>
                                @error('next_invoice_number')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Huidig jaar <span class="text-destructive">*</span></td>
                            <td>
                                <input type="number" name="current_year" min="2020" max="2100" class="kt-input @error('current_year') border-destructive @enderror" style="width: 13ch;" value="{{ old('current_year', $settings->suggestedCurrentYear()) }}" required>
                                @error('current_year')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Factuurtitel op PDF</td>
                            <td>
                                <input type="text" name="invoice_title" class="kt-input w-full @error('invoice_title') border-destructive @enderror" value="{{ old('invoice_title', $settings->invoice_title) }}">
                                @error('invoice_title')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="kt-card min-w-full">
                <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                    <h3 class="kt-card-title mb-0">Betaaltermijn, aanmaningen & BTW</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Standaard betaaltermijn (dagen) <span class="text-destructive">*</span></td>
                            <td class="min-w-48 w-full">
                                <input type="number" name="payment_terms_days" min="1" max="365" class="kt-input @error('payment_terms_days') border-destructive @enderror" style="width: 13ch;" value="{{ old('payment_terms_days', $settings->payment_terms_days) }}" required>
                                <div class="text-xs text-muted-foreground mt-1">Standaard voor nieuwe NEXA-facturen; per factuur aanpasbaar. Bepaalt de vervaldatum. Dagelijks wordt bij Mollie gecontroleerd of er alsnog is betaald.</div>
                                @error('payment_terms_days')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">1e aanmaning (dagen) <span class="text-destructive">*</span></td>
                            <td class="min-w-48 w-full">
                                <input type="number" name="dunning_first_interval_days" min="1" max="365" class="kt-input @error('dunning_first_interval_days') border-destructive @enderror" style="width: 13ch;" value="{{ old('dunning_first_interval_days', $settings->dunning_first_interval_days ?: 1) }}" required>
                                <div class="text-xs text-muted-foreground mt-1">Aantal dagen na de vervaldatum tot de 1e aanmaning.</div>
                                @error('dunning_first_interval_days')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">2e aanmaning (dagen) <span class="text-destructive">*</span></td>
                            <td class="min-w-48 w-full">
                                <input type="number" name="dunning_interval_days" min="1" max="365" class="kt-input @error('dunning_interval_days') border-destructive @enderror" style="width: 13ch;" value="{{ old('dunning_interval_days', $settings->dunning_interval_days ?: $settings->payment_terms_days ?: 14) }}" required>
                                <div class="text-xs text-muted-foreground mt-1">Aantal dagen na de 1e aanmaning tot de 2e aanmaning. Na de 2e aanmaning geldt dezelfde termijn tot blokkade van de tenant (boekingen of volledig, per tenant ingesteld).</div>
                                @error('dunning_interval_days')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">BTW % <span class="text-destructive">*</span></td>
                            <td>
                                <input type="number" name="tax_rate_percent" class="kt-input @error('tax_rate_percent') border-destructive @enderror" style="width: 13ch;" value="{{ (int) old('tax_rate_percent', $settings->tax_rate_percent) }}" step="1" min="0" max="100" required>
                                @error('tax_rate_percent')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Betaaltermijntekst (PDF)</td>
                            <td>
                                <textarea name="invoice_payment_terms_text" rows="4" class="kt-input w-full @error('invoice_payment_terms_text') border-destructive @enderror" placeholder="{{ \App\Models\PlatformBillingSetting::DEFAULT_PAYMENT_TERMS_TEXT }}">{{ old('invoice_payment_terms_text', $settings->invoice_payment_terms_text) }}</textarea>
                                <div class="text-xs text-muted-foreground mt-1">Gebruik {dagen} en {dagen_label}. Leeg = standaardtekst.</div>
                                @error('invoice_payment_terms_text')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Bedrijfsgegevens (Nexa / platform op factuur)</h3>
                </div>
                <div class="kt-card-table kt-scrollable-x-auto pb-3">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top">Bedrijfsnaam</td>
                            <td class="min-w-48 w-full">
                                <input type="text" name="company_name" class="kt-input w-full" value="{{ old('company_name', $settings->company_name ?: $settings->sender_name) }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Postcode</td>
                            <td>
                                <input type="text"
                                       id="platform_billing_postal_code"
                                       name="company_postal_code"
                                       class="kt-input max-w-xs @error('company_postal_code') border-destructive @enderror"
                                       value="{{ old('company_postal_code', $settings->company_postal_code) }}"
                                       pattern="[1-9][0-9]{3}\s?[A-Za-z]{2}"
                                       placeholder="1234AB"
                                       maxlength="7"
                                       style="text-transform: uppercase;">
                                <div class="text-xs text-muted-foreground mt-1">Nederlandse postcode (bijv. 1234AB). Bij verlaten van het veld wordt het adres automatisch opgezocht.</div>
                                @error('company_postal_code')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Huisnummer</td>
                            <td>
                                <input type="text"
                                       id="platform_billing_house_number"
                                       name="company_house_number"
                                       class="kt-input max-w-xs @error('company_house_number') border-destructive @enderror"
                                       value="{{ old('company_house_number', $settings->company_house_number) }}">
                                <div class="text-xs text-muted-foreground mt-1">Bij verlaten van het veld worden straat en plaats automatisch ingevuld.</div>
                                @include('admin.partials.postcode-lookup-status', ['id' => 'platform_billing_house_lookup_loading'])
                                @error('company_house_number')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Straat</td>
                            <td>
                                @php
                                    $hasPlatformAddress = trim(old('company_address', $settings->company_address ?? '').old('company_city', $settings->company_city ?? '')) !== '';
                                @endphp
                                <input type="text"
                                       id="platform_billing_street"
                                       name="company_address"
                                       class="kt-input w-full @error('company_address') border-destructive @enderror"
                                       value="{{ old('company_address', $settings->company_address) }}"
                                       @if($hasPlatformAddress) readonly @endif>
                                <div class="text-xs text-muted-foreground mt-1">Wordt automatisch ingevuld bij postcode + huisnummer. Bij geen resultaat zijn de velden bewerkbaar.</div>
                                @error('company_address')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Plaats</td>
                            <td>
                                <input type="text"
                                       id="platform_billing_city"
                                       name="company_city"
                                       class="kt-input w-full max-w-xs @error('company_city') border-destructive @enderror"
                                       value="{{ old('company_city', $settings->company_city) }}"
                                       @if($hasPlatformAddress) readonly @endif>
                                @error('company_city')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Land</td>
                            <td>
                                <input type="text"
                                       id="platform_billing_country"
                                       name="company_country"
                                       class="kt-input w-full max-w-xs @error('company_country') border-destructive @enderror"
                                       value="{{ old('company_country', $settings->company_country) }}"
                                       @if($hasPlatformAddress) readonly @endif>
                                @error('company_country')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">BTW/KvK-nummer</td>
                            <td>
                                <input type="text" name="company_vat_number" class="kt-input w-full max-w-md" value="{{ old('company_vat_number', $settings->company_vat_number) }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">E-mail</td>
                            <td>
                                <input type="email" name="company_email" class="kt-input w-full" value="{{ old('company_email', $settings->company_email) }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Telefoon</td>
                            <td>
                                <input type="text" name="company_phone" class="kt-input w-full max-w-md" value="{{ old('company_phone', $settings->company_phone) }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Bankrekening (IBAN)</td>
                            <td>
                                <input type="text" name="bank_account" class="kt-input w-full max-w-md" value="{{ old('bank_account', $settings->bank_account) }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Afzender naam (e-mail)</td>
                            <td>
                                <input type="text" name="sender_name" class="kt-input w-full" value="{{ old('sender_name', $settings->sender_name) }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal align-top">Footer op factuur</td>
                            <td>
                                <textarea name="invoice_footer" rows="3" class="kt-input w-full">{{ old('invoice_footer', $settings->invoice_footer) }}</textarea>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="kt-card w-full min-w-0">
                <div class="kt-card-header px-5 py-5">
                    <h3 class="kt-card-title mb-0">Mollie (NEXA-incasso)</h3>
                </div>
                <div class="kt-card-table pb-3 min-w-0">
                    <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full platform-billing-mollie-table">
                        <colgroup>
                            <col class="platform-billing-mollie-table__label">
                            <col>
                        </colgroup>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top whitespace-nowrap">Status</td>
                            <td class="min-w-0 w-full">
                                @if($mollieConfigured ?? false)
                                    <span class="kt-badge kt-badge-sm kt-badge-success">Geconfigureerd</span>
                                @else
                                    <span class="kt-badge kt-badge-sm kt-badge-warning">Niet geconfigureerd</span>
                                @endif
                                @if($mollieFromEnvFallback ?? false)
                                    <div class="text-xs text-muted-foreground mt-1 min-w-0 whitespace-normal break-words">
                                        Tijdelijk via <code>.env</code> (<code>PLATFORM_MOLLIE_API_KEY</code>). Sla hier een sleutel op om dat te vervangen.
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top whitespace-nowrap">API-sleutel</td>
                            <td class="min-w-0">
                                @if(!empty($mollieApiKeyMasked))
                                    <div class="text-xs text-muted-foreground mb-1 min-w-0 whitespace-normal break-words">
                                        Huidige sleutel: <code class="break-all">{{ $mollieApiKeyMasked }}</code> (veilig versleuteld opgeslagen)
                                    </div>
                                @endif
                                <div class="relative w-full max-w-md">
                                    <input type="password"
                                           name="mollie_api_key"
                                           class="kt-input w-full @error('mollie_api_key') border-destructive @enderror"
                                           value=""
                                           autocomplete="new-password"
                                           placeholder="{{ !empty($mollieApiKeyMasked) ? 'Leeg laten om huidige sleutel te behouden' : 'test_… of live_…' }}">
                                </div>
                                <div class="text-xs text-muted-foreground mt-1">
                                    Platform-Mollie-account voor SEPA-incasso en betaallinks naar tenants. Niet de tenant-betalingsprovider.
                                </div>
                                @error('mollie_api_key')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                                @if(!empty($mollieApiKeyMasked))
                                    <label class="kt-label flex items-center gap-2 mt-2">
                                        <input type="checkbox" name="clear_mollie_api_key" value="1" class="kt-checkbox">
                                        <span class="text-sm">API-sleutel verwijderen</span>
                                    </label>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="min-w-56 text-secondary-foreground font-normal align-top whitespace-nowrap">Webhook-URL</td>
                            <td class="min-w-0">
                                <input type="url"
                                       name="mollie_webhook_url"
                                       class="kt-input w-full min-w-0 @error('mollie_webhook_url') border-destructive @enderror"
                                       value="{{ old('mollie_webhook_url', $settings->mollie_webhook_url) }}"
                                       placeholder="{{ $defaultPlatformWebhookUrl ?? url('/api/platform/webhooks/mollie') }}">
                                <div class="text-xs text-muted-foreground mt-1 min-w-0 whitespace-normal">
                                    Publieke URL voor Mollie-statusupdates. Leeg laten = standaard in productie.
                                    <span class="block mt-1">
                                        Standaard:
                                        <code class="break-all">{{ $defaultPlatformWebhookUrl ?? url('/api/platform/webhooks/mollie') }}</code>
                                    </span>
                                </div>
                                @error('mollie_webhook_url')
                                    <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.platform-billing.invoices.index') }}" class="kt-btn kt-btn-outline">
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
@endsection

@push('styles')
<style>
    #content .platform-billing-mollie-table {
        table-layout: auto;
        width: 100%;
    }

    #content .platform-billing-mollie-table__label,
    #content .platform-billing-mollie-table td:first-child {
        width: 14rem;
        min-width: 14rem;
        white-space: nowrap;
        overflow-wrap: normal;
        word-break: normal;
    }

    #content .platform-billing-mollie-table td:last-child {
        min-width: 0;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/admin-postcode-lookup.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.bindAdminPostcodeLookup === 'function') {
            window.bindAdminPostcodeLookup({
                postcode: 'platform_billing_postal_code',
                huisnummer: 'platform_billing_house_number',
                street: 'platform_billing_street',
                city: 'platform_billing_city',
                country: 'platform_billing_country',
                loading: ['platform_billing_house_lookup_loading'],
                url: @json(route('admin.postcode.lookup'))
            });
        }
    });
</script>
@endpush
