@extends('admin.companies.wizard.layout')

@section('title', 'Stap 9 — WhatsApp & Mollie')

@section('wizard_content')
@include('admin.settings.partials.collapsible-section-assets')
<form method="post" action="{{ route('admin.companies.wizard.submit-step', [$company, 9]) }}">
    @csrf
    <x-error-card :errors="$errors" />

    <div id="wizard-integrations-collapsible-root">
    @if(! empty($canConfigureWhatsapp))
    <div class="kt-card min-w-full mb-6 settings-collapsible-card" id="wizard-whatsapp">
        @include('admin.settings.partials.collapsible-header', [
            'titleHtml' => 'WhatsApp (tenant)',
            'headerClass' => 'px-5 py-5',
            'headerExtraHtml' => ! empty($whatsappPlatformConfigured)
                ? '<span class="text-sm font-medium text-emerald-700 dark:text-emerald-300 leading-none">Platform-API is actief.</span>'
                : '',
        ])
        <div class="settings-collapsible-body">
        <div class="kt-card-content p-5 lg:p-6">
            <p class="text-sm text-secondary-foreground mb-0">
                Widget, click-to-chat en het nummer voor boekingsmeldingen van deze tenant.
                De WhatsApp Business API zelf staat onder Algemene configuraties.
            </p>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">WhatsApp Direct</td>
                    <td class="min-w-48 w-full">
                        <input type="hidden" name="WHATSAPP_CLICK_TO_CHAT_ENABLED" value="0">
                        <label class="kt-label flex items-center gap-2 mb-0">
                            <input type="checkbox" class="kt-switch kt-switch-sm" name="WHATSAPP_CLICK_TO_CHAT_ENABLED" value="1" {{ old('WHATSAPP_CLICK_TO_CHAT_ENABLED', $whatsappSettings['WHATSAPP_CLICK_TO_CHAT_ENABLED'] ?? '0') === '1' ? 'checked' : '' }} @if(!empty($whatsappPlatformConfigured)) disabled @endif>
                            Fallback zonder Business API
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">WhatsApp-nummer (Direct)</td>
                    <td class="min-w-48 w-full">
                        <input type="tel" class="kt-input @error('WHATSAPP_CLICK_TO_CHAT_NUMBER') border-destructive @enderror" name="WHATSAPP_CLICK_TO_CHAT_NUMBER" value="{{ old('WHATSAPP_CLICK_TO_CHAT_NUMBER', $whatsappSettings['WHATSAPP_CLICK_TO_CHAT_NUMBER'] ?? '') }}" placeholder="0612345678 of +31612345678">
                        @error('WHATSAPP_CLICK_TO_CHAT_NUMBER')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Nummer bedrijf (boekingen)</td>
                    <td class="min-w-48 w-full">
                        <input type="tel" class="kt-input @error('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER') border-destructive @enderror" name="WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER" value="{{ old('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER', $whatsappSettings['WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER'] ?? '') }}" placeholder="0612345678 of +31612345678">
                        @error('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Widget op website</td>
                    <td class="min-w-48 w-full">
                        <input type="hidden" name="WHATSAPP_WIDGET_ENABLED" value="0">
                        <label class="kt-label flex items-center gap-2 mb-0">
                            <input type="checkbox" class="kt-switch kt-switch-sm" name="WHATSAPP_WIDGET_ENABLED" value="1" {{ old('WHATSAPP_WIDGET_ENABLED', $whatsappSettings['WHATSAPP_WIDGET_ENABLED'] ?? '0') === '1' ? 'checked' : '' }}>
                            WhatsApp-icoon rechtsonder tonen
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Widget-nummer</td>
                    <td class="min-w-48 w-full">
                        <input type="tel" class="kt-input @error('WHATSAPP_WIDGET_PHONE') border-destructive @enderror" name="WHATSAPP_WIDGET_PHONE" value="{{ old('WHATSAPP_WIDGET_PHONE', $whatsappSettings['WHATSAPP_WIDGET_PHONE'] ?? '') }}" placeholder="0612345678 of +31612345678">
                        @error('WHATSAPP_WIDGET_PHONE')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top">Standaardbericht widget</td>
                    <td class="min-w-48 w-full">
                        <textarea rows="2" class="kt-input pt-1" name="WHATSAPP_WIDGET_DEFAULT_MESSAGE">{{ old('WHATSAPP_WIDGET_DEFAULT_MESSAGE', $whatsappSettings['WHATSAPP_WIDGET_DEFAULT_MESSAGE'] ?? '') }}</textarea>
                    </td>
                </tr>
            </table>
        </div>
        </div>
    </div>
    @else
    <div class="kt-card min-w-full mb-6 overflow-hidden">
        <div class="kt-card-header px-5 py-5">
            <h3 class="kt-card-title mb-0">WhatsApp (tenant)</h3>
        </div>
        <div class="kt-card-content p-5">
            <div class="rounded-xl border border-red-500 bg-primary/5 px-4 py-4">
                <p class="text-sm text-foreground font-medium mb-1">WhatsApp-configuratie is afgeschermd</p>
                <p class="text-sm text-secondary-foreground mb-0">{{ \App\Services\TenantConfigAccessService::DENIED_MESSAGE }}</p>
            </div>
        </div>
    </div>
    @endif

    @if(! empty($canConfigureMollie))
    <div class="kt-card min-w-full mb-6 settings-collapsible-card" id="wizard-mollie">
        @include('admin.settings.partials.collapsible-header', [
            'titleHtml' => 'Mollie (tenant)',
            'headerClass' => 'px-5 py-5',
        ])
        <div class="settings-collapsible-body">
        <div class="kt-card-content p-5 lg:p-6">
            <p class="text-sm text-secondary-foreground mb-0">
                Eigen Mollie-omgeving van deze tenant. Betalingen gaan naar de rekening van het taxibedrijf, niet naar Nexa.
            </p>
            @if(isset($molliePackageAllowed) && ! $molliePackageAllowed)
                <div class="kt-alert kt-alert-warning text-sm mt-4 mb-0" role="alert">
                    {{ $molliePackageDeniedMessage ?? 'Betalen via Mollie zit niet in het pakket van deze tenant.' }}
                </div>
            @endif
        </div>
        <div class="kt-card-table kt-scrollable-x-auto pb-3">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Status</td>
                    <td class="min-w-48 w-full">
                        @if(!empty($mollieSummary['configured']) && !empty($mollieSummary['is_active']))
                            <span class="text-sm text-emerald-700 dark:text-emerald-300">Actief{{ !empty($mollieSummary['test_mode']) ? ' (testmodus)' : '' }}</span>
                        @elseif(!empty($mollieSummary['provider']))
                            <span class="text-sm text-amber-700 dark:text-amber-300">Opgeslagen, maar niet actief</span>
                        @else
                            <span class="text-sm text-muted-foreground">Nog niet ingesteld</span>
                        @endif
                        @if(!empty($mollieSummary['api_key_preview']))
                            <div class="text-xs text-muted-foreground mt-1">Huidige sleutel: <code class="text-xs">{{ $mollieSummary['api_key_preview'] }}</code></div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Mollie API-sleutel</td>
                    <td class="min-w-48 w-full">
                        <input type="password" class="kt-input @error('mollie_api_key') border-destructive @enderror" name="mollie_api_key" value="{{ old('mollie_api_key') }}" autocomplete="new-password" placeholder="{{ !empty($mollieSummary['configured']) ? 'Leeg laten om te behouden' : 'test_… of live_…' }}" @if(empty($molliePackageAllowed)) disabled @endif>
                        @error('mollie_api_key')<div class="text-xs text-destructive mt-1">{{ $message }}</div>@enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Actief</td>
                    <td class="min-w-48 w-full">
                        <input type="hidden" name="mollie_is_active" value="0">
                        <label class="kt-label flex items-center gap-2 mb-0">
                            <input type="checkbox" class="kt-switch kt-switch-sm" name="mollie_is_active" value="1" {{ old('mollie_is_active', !empty($mollieSummary['is_active']) ? '1' : '0') === '1' ? 'checked' : '' }} @if(empty($molliePackageAllowed)) disabled @endif>
                            Mollie gebruiken voor betalingen van deze tenant
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Testmodus</td>
                    <td class="min-w-48 w-full">
                        <input type="hidden" name="mollie_test_mode" value="0">
                        <label class="kt-label flex items-center gap-2 mb-0">
                            <input type="checkbox" class="kt-switch kt-switch-sm" name="mollie_test_mode" value="1" {{ old('mollie_test_mode', !empty($mollieSummary['test_mode']) ? '1' : '0') === '1' ? 'checked' : '' }} @if(empty($molliePackageAllowed)) disabled @endif>
                            Testomgeving (test_-sleutel)
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Chauffeur-app</td>
                    <td class="min-w-48 w-full">
                        <input type="hidden" name="mollie_driver_payments" value="0">
                        <label class="kt-label flex items-center gap-2 mb-0">
                            <input type="checkbox" class="kt-switch kt-switch-sm" name="mollie_driver_payments" value="1" {{ old('mollie_driver_payments', !empty($mollieDriverPaymentsEnabled) ? '1' : '0') === '1' ? 'checked' : '' }} @if(empty($molliePackageAllowed)) disabled @endif>
                            QR-betaling in de chauffeur-app
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Websiteboeking</td>
                    <td class="min-w-48 w-full">
                        <input type="hidden" name="mollie_booking_payments" value="0">
                        <label class="kt-label flex items-center gap-2 mb-0">
                            <input type="checkbox" class="kt-switch kt-switch-sm" name="mollie_booking_payments" value="1" {{ old('mollie_booking_payments', !empty($mollieBookingPaymentsEnabled) ? '1' : '0') === '1' ? 'checked' : '' }} @if(empty($molliePackageAllowed)) disabled @endif>
                            Direct betalen na websiteboeking
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Webhook-URL</td>
                    <td class="min-w-48 w-full">
                        <input type="url" class="kt-input" name="mollie_webhook_url" value="{{ old('mollie_webhook_url', $mollieSummary['webhook_url'] ?? '') }}" placeholder="{{ $defaultTaxiWebhookUrl ?? url('/api/taxi/webhooks/mollie') }}" @if(empty($molliePackageAllowed)) disabled @endif>
                    </td>
                </tr>
            </table>
        </div>
        </div>
    </div>
    @else
    <div class="kt-card min-w-full mb-6 overflow-hidden">
        <div class="kt-card-header px-5 py-5">
            <h3 class="kt-card-title mb-0">Mollie (tenant)</h3>
        </div>
        <div class="kt-card-content p-5">
            <div class="rounded-xl border border-red-500 bg-primary/5 px-4 py-4">
                <p class="text-sm text-foreground font-medium mb-1">Mollie-configuratie is afgeschermd</p>
                <p class="text-sm text-secondary-foreground mb-0">{{ \App\Services\TenantConfigAccessService::DENIED_MESSAGE }}</p>
            </div>
        </div>
    </div>
    @endif
    </div>

    <x-wizard.footer-actions :current-step="$currentStep" :company="$company">
        <button type="submit" name="skip_config" value="1" class="kt-btn kt-btn-outline">
            Overslaan
        </button>
        <button type="submit" class="kt-btn kt-btn-primary">
            Volgende
            <i class="ki-filled ki-arrow-right ms-2"></i>
        </button>
    </x-wizard.footer-actions>
</form>
@endsection
