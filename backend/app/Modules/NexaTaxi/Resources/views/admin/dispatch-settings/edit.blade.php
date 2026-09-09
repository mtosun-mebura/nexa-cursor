@extends('admin.layouts.app')

@include('admin.settings.partials.collapsible-section-assets')

@section('title', 'Chauffeur dispatch')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-3 pb-7.5">
        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-medium leading-none text-mono">
                Chauffeur dispatch
            </h1>
            <p class="text-sm text-muted-foreground mt-2 mb-0 leading-relaxed">
                Instellingen voor de chauffeur-app en meldingen bij nieuwe boekingen. Geldt per bedrijf (tenant).
                Zonder eigen acceptatietijd wordt de serverstandaard gebruikt (nu {{ (int) round($envDefaultSeconds / 60) }} min).
            </p>
        </div>
        <a href="{{ route('admin.taxi.ride_requests.index') }}" class="kt-btn kt-btn-outline shrink-0">
            <i class="ki-filled ki-arrow-left me-2"></i>
            Terug naar ritten
        </a>
    </div>

    @if($errors->has('tenant'))
        <div class="kt-alert kt-alert-warning mb-5">{{ $errors->first('tenant') }}</div>
    @endif

    @if(!empty($noTenantSelected))
        <div class="kt-alert kt-alert-warning mb-5">
            Selecteer eerst een tenant (bedrijf) in de zijbalk. Dispatch-instellingen worden per bedrijf opgeslagen;
            zonder geselecteerde tenant kunt u niet opslaan.
        </div>
    @endif

    <form action="{{ route('admin.taxi.dispatch_settings.update') }}" method="POST" class="grid gap-5 lg:gap-7.5 w-full min-w-0">
        @csrf
        @method('PUT')

        <div class="kt-card w-full min-w-0">
        <div id="dispatch-settings-collapsible-root">
        <div class="settings-collapsible-section settings-collapsible-card--collapsed" id="dispatch-accept-timer">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => 'Acceptatietimer'])
            <div class="settings-collapsible-body">
            <div class="px-3 sm:px-5 pb-3 min-w-0">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">Acceptatietijd (minuten)</td>
                    <td class="min-w-48 w-full">
                        <input
                            type="number"
                            name="offer_ttl_minutes"
                            id="offer_ttl_minutes"
                            class="kt-input w-full max-w-md @error('offer_ttl_minutes') border-destructive @enderror"
                            min="{{ $minMinutes }}"
                            max="{{ $maxMinutes }}"
                            step="1"
                            required
                            value="{{ old('offer_ttl_minutes', $offerTtlMinutes) }}"
                        >
                        <p class="text-xs text-muted-foreground mt-1">
                            Tussen {{ $minMinutes }} en {{ $maxMinutes }} minuten
                            ({{ $offerTtlSeconds }} seconden in de app-timer).
                        </p>
                        @error('offer_ttl_minutes')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Verlopen ophaalmoment in aanvragen (minuten)</td>
                    <td class="min-w-48 w-full pt-4">
                        <input
                            type="number"
                            name="past_pickup_grace_minutes"
                            id="past_pickup_grace_minutes"
                            class="kt-input w-full max-w-md @error('past_pickup_grace_minutes') border-destructive @enderror"
                            min="{{ $minPastPickupGraceMinutes }}"
                            max="{{ $maxPastPickupGraceMinutes }}"
                            step="1"
                            required
                            value="{{ old('past_pickup_grace_minutes', $pastPickupGraceMinutes) }}"
                        >
                        <p class="text-xs text-muted-foreground mt-1">
                            Zodra het ophaalmoment voorbij is, blijft de rit nog zo lang onder Nieuwe ritaanvraag
                            (met rode verlopen-banner). Daarna alleen onder Verlopen ritten.
                            Standaard server: {{ $envDefaultPastPickupGraceMinutes }} minuten.
                            Tussen {{ $minPastPickupGraceMinutes }} en {{ $maxPastPickupGraceMinutes }} minuten.
                        </p>
                        @error('past_pickup_grace_minutes')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
            </table>
            </div>
            </div>
        </div>

        <div class="settings-collapsible-section settings-collapsible-card--collapsed" id="dispatch-booking-notifications">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => 'Boekingsmeldingen'])
            <div class="settings-collapsible-body">
            <div class="px-3 sm:px-5 pb-3 min-w-0">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4" colspan="2">
                        <p class="text-xs text-muted-foreground mb-2">
                            Klant-WhatsApp en API staan onder Algemene configuraties → WhatsApp Business API.
                            Boekingsmelding naar het bedrijf: schakelaar bij Boekingssjablonen (platform) + WhatsApp-nummer bedrijf onder Instellingen → WhatsApp (tenant).
                        </p>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">E-mail naar chauffeurs</td>
                    <td class="min-w-48 w-full">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="booking_driver_email_enabled" value="0">
                            <input type="checkbox"
                                   class="kt-checkbox"
                                   name="booking_driver_email_enabled"
                                   value="1"
                                   {{ old('booking_driver_email_enabled', $bookingDriverEmailEnabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Stuur elke chauffeur een e-mail bij een nieuwe rit</span>
                        </label>
                        <p class="text-xs text-muted-foreground mt-1">
                            Verstuurd naar het e-mailadres van elk chauffeur-account binnen dit bedrijf.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal">E-mail naar klant</td>
                    <td class="min-w-48 w-full">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="booking_customer_email_enabled" value="0">
                            <input type="checkbox"
                                   class="kt-checkbox"
                                   name="booking_customer_email_enabled"
                                   value="1"
                                   {{ old('booking_customer_email_enabled', $bookingCustomerEmailEnabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Stuur de klant een bevestigingsmail direct na de boeking</span>
                        </label>
                        <p class="text-xs text-muted-foreground mt-1">
                            Vereist een geldig e-mailadres in het boekingsformulier.
                        </p>
                    </td>
                </tr>
            </table>
            </div>
            </div>
        </div>

        <div class="settings-collapsible-section settings-collapsible-card--collapsed" id="dispatch-mijn-taxi-login">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => 'Mijn Taxi – klant inlogcode'])
            <div class="settings-collapsible-body">
            <div class="px-3 sm:px-5 pb-3 min-w-0">
            <table class="kt-table kt-table-border-dashed text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Geldigheid inlogcode (minuten)</td>
                    <td class="min-w-48 w-full align-top pt-4">
                        <input
                            type="number"
                            name="customer_login_code_expires_minutes"
                            id="customer_login_code_expires_minutes"
                            class="kt-input w-full max-w-md @error('customer_login_code_expires_minutes') border-destructive @enderror"
                            min="{{ $minLoginCodeExpiresMinutes }}"
                            max="{{ $maxLoginCodeExpiresMinutes }}"
                            step="1"
                            required
                            value="{{ old('customer_login_code_expires_minutes', $customerLoginCodeExpiresMinutes) }}"
                        >
                        <p class="text-xs text-muted-foreground mt-1">
                            Tussen {{ $minLoginCodeExpiresMinutes }} en {{ $maxLoginCodeExpiresMinutes }} minuten.
                            Serverstandaard zonder tenant-waarde: {{ $envDefaultLoginCodeExpiresMinutes }} min.
                            In de e-mail wordt <code class="text-xs">{{ '{' }}{{ '{' }} CODE_EXPIRES_MINUTES {{ '}' }}{{ '}' }}</code> automatisch met dit getal ingevuld;
                            de code verloopt in de database na hetzelfde aantal minuten.
                        </p>
                        @error('customer_login_code_expires_minutes')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">E-mailtekst inlogcode</td>
                    <td class="min-w-48 w-full align-top pt-4">
                        <p class="text-sm text-secondary-foreground mb-2">
                            Onderwerp, opmaak en overige variabelen (naam, code, link) pas je aan in E-mail templates.
                        </p>
                        <a href="{{ $customerLoginCodeEmailTemplateUrl }}" class="kt-btn kt-btn-sm kt-btn-outline">E-mailtemplate inlogcode</a>
                    </td>
                </tr>
            </table>
            </div>
            </div>
        </div>

        <div class="settings-collapsible-section settings-collapsible-card--collapsed" id="dispatch-customer-accept">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => 'Klantmelding bij acceptatie'])
            <div class="settings-collapsible-body">
            <div class="px-3 sm:px-5 pb-3 min-w-0">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Meldingen aan klant</td>
                    <td class="min-w-48 w-full pt-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="customer_accept_enabled" value="0">
                            <input type="checkbox" class="kt-checkbox" name="customer_accept_enabled" value="1"
                                   id="customer_accept_enabled"
                                   {{ old('customer_accept_enabled', $customerAcceptEnabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Stuur melding wanneer een chauffeur de rit accepteert</span>
                        </label>
                        <p class="text-xs text-muted-foreground mt-2 mb-2">
                            Pas de e-mail aan die de klant ontvangt wanneer een chauffeur de rit accepteert (onderwerp, HTML, logo en variabelen).
                        </p>
                        <a href="{{ $customerAcceptEmailEditUrl }}" class="kt-btn kt-btn-sm kt-btn-outline">E-mailtekst aanpassen</a>
                        @if($canEditEmailTemplatesModule ?? false)
                            <span class="text-xs text-muted-foreground ms-2">of via
                                <a href="{{ $emailTemplateIndexUrl }}" class="text-primary underline">E-mail templates</a></span>
                        @endif
                    </td>
                </tr>
                <tr class="customer-accept-channel-row">
                    <td class="min-w-56 text-secondary-foreground font-normal">E-mail naar klant</td>
                    <td class="min-w-48 w-full">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="customer_accept_email_enabled" value="0">
                            <input type="checkbox" class="kt-checkbox customer-accept-channel" name="customer_accept_email_enabled" value="1"
                                   {{ old('customer_accept_email_enabled', $customerAcceptEmailEnabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Verstuur e-mail na chauffeursacceptatie (vereist klant-e-mail op de rit)</span>
                        </label>
                    </td>
                </tr>
                <tr class="customer-accept-channel-row">
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">WhatsApp naar klant</td>
                    <td class="min-w-48 w-full pt-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="customer_accept_whatsapp_enabled" value="0">
                            <input type="checkbox" class="kt-checkbox customer-accept-channel" name="customer_accept_whatsapp_enabled" value="1"
                                   {{ old('customer_accept_whatsapp_enabled', $customerAcceptWhatsappEnabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Verstuur WhatsApp (vereist klanttelefoon)</span>
                        </label>
                        @if(! $whatsappApiConfigured)
                            <p class="text-xs text-destructive mt-1">WhatsApp Business API is niet geconfigureerd op de server.</p>
                        @else
                            <p class="text-xs text-muted-foreground mt-1">
                                Gebruikt het statussjabloon (<code class="text-xs">rit_status_update</code>) onder
                                <a href="{{ route('admin.settings.general.index') }}#whatsapp-status-templates" class="underline">Algemene configuraties → WhatsApp</a>.
                            </p>
                        @endif
                    </td>
                </tr>                <tr class="customer-accept-channel-row">
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">SMS naar klant</td>
                    <td class="min-w-48 w-full pt-4">
                        <label class="inline-flex items-center gap-2 mb-2">
                            <input type="hidden" name="customer_accept_sms_enabled" value="0">
                            <input type="checkbox" class="kt-checkbox customer-accept-channel" name="customer_accept_sms_enabled" value="1"
                                   {{ old('customer_accept_sms_enabled', $customerAcceptSmsEnabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Verstuur SMS (vereist klanttelefoon)</span>
                        </label>
                        <label for="customer_accept_sms_provider" class="text-xs text-muted-foreground block mb-1">SMS-provider</label>
                        <select name="customer_accept_sms_provider" id="customer_accept_sms_provider" class="kt-select w-full max-w-md">
                            @foreach ($smsProviderOptions as $provider)
                                <option value="{{ $provider }}" {{ old('customer_accept_sms_provider', $customerAcceptSmsProvider) === $provider ? 'selected' : '' }}>
                                    {{ \App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService::smsProviderLabel($provider) }}
                                </option>
                            @endforeach
                        </select>
                        @if(! $vonageConfigured)
                            <p class="text-xs text-muted-foreground mt-1">
                                Vonage: zet <code class="text-xs">VONAGE_API_KEY</code>, <code class="text-xs">VONAGE_API_SECRET</code> en <code class="text-xs">VONAGE_FROM_NUMBER</code> in .env.
                                Demo logt alleen (gratis, voor test).
                            </p>
                        @endif
                    </td>
                </tr>
                <tr class="customer-accept-channel-row">
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Tekst SMS</td>
                    <td class="min-w-48 w-full pt-4">
                        <p class="text-xs text-muted-foreground mb-2 max-w-xl">
                            Vaste SMS-tekst bij acceptatie/afwijzing.
                            Variabelen: <code class="text-xs">@{{1}}</code> klant,
                            <code class="text-xs">@{{2}}</code> bedrijf,
                            <code class="text-xs">@{{3}}</code> status (Geaccepteerd/Geweigerd),
                            <code class="text-xs">@{{4}}</code> opmerking,
                            <code class="text-xs">@{{5}}</code> chauffeur,
                            <code class="text-xs">@{{6}}</code> ophaalmoment,
                            <code class="text-xs">@{{7}}</code> ophaaladres.
                        </p>
                        <pre class="kt-input w-full max-w-xl text-xs whitespace-pre-wrap break-words font-mono py-3 h-auto min-h-[8rem]">{{ \App\Services\WhatsAppBookingMessageComposer::META_BODY_CUSTOMER_SMS }}</pre>
                    </td>
                </tr>
            </table>
            </div>
            </div>
        </div>

        <div class="settings-collapsible-section settings-collapsible-card--collapsed" id="dispatch-payments">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => 'Betalingen (Mollie)'])
            <div class="settings-collapsible-body">
            <div class="px-3 sm:px-5 pb-3 min-w-0">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Mollie (betalingsprovider)</td>
                    <td class="min-w-48 w-full pt-4">
                        @if($mollieSummary['configured'] && $mollieSummary['provider'])
                            <p class="text-sm text-secondary-foreground mb-2">
                                <strong>{{ $mollieSummary['provider']->name }}</strong>
                                @if($mollieSummary['is_active'])
                                    <span class="text-green-600">· actief</span>
                                @else
                                    <span class="text-amber-600">· niet actief</span>
                                @endif
                                @if($mollieSummary['test_mode'])
                                    <span class="text-muted-foreground">· testmodus</span>
                                @endif
                            </p>
                            <p class="text-xs text-muted-foreground mb-1">
                                API-sleutel: <code class="text-xs">{{ $mollieSummary['api_key_preview'] }}</code>
                            </p>
                            <p class="text-xs text-muted-foreground mb-2 break-all">
                                Webhook: {{ $mollieSummary['webhook_url'] }}
                            </p>
                            @if(auth()->user()->hasRole('super-admin'))
                                <a href="{{ route('admin.settings.index') }}#mollie" class="kt-btn kt-btn-outline kt-btn-sm">
                                    Mollie-instellingen bewerken
                                </a>
                            @elseif($canManagePaymentProviders)
                                <a href="{{ route('admin.payment-providers.edit', $mollieSummary['provider']) }}" class="kt-btn kt-btn-outline kt-btn-sm">
                                    Mollie-instellingen bewerken
                                </a>
                            @endif
                        @else
                            <p class="text-sm text-secondary-foreground mb-2">
                                Er is nog geen actieve Mollie-omgeving voor dit bedrijf. Vul de API-sleutel van <strong>dit bedrijf</strong> in — chauffeur-betalingen komen dan op die Mollie-rekening.
                                @if(auth()->user()->hasRole('super-admin'))
                                    Super-admin: <strong>Configuraties → Mollie (tenant)</strong>.
                                @else
                                    Onder <strong>Betalingsproviders</strong>.
                                @endif
                            </p>
                            <p class="text-xs text-muted-foreground mb-2">
                                Aanbevolen webhook voor taxi-betalingen: <code class="text-xs break-all">{{ $defaultTaxiWebhookUrl }}</code>
                            </p>
                            <p class="text-xs text-muted-foreground mb-2">
                                Lokaal (<code>localhost</code> of <code>192.168.x.x</code>): Mollie kan die URL niet bereiken. Betalingen werken zonder webhook via terugkeer-URL en polling in de chauffeur-app. Voor webhooks: gebruik een tunnel (ngrok) en zet <code>TAXI_MOLLIE_WEBHOOK_URL</code> in <code>.env</code>.
                            </p>
                            @if(auth()->user()->hasRole('super-admin'))
                                <a href="{{ route('admin.settings.index') }}#mollie" class="kt-btn kt-btn-outline kt-btn-sm">
                                    Mollie instellen
                                </a>
                            @elseif($canManagePaymentProviders)
                                <a href="{{ route('admin.payment-providers.create') }}" class="kt-btn kt-btn-outline kt-btn-sm">
                                    Mollie-provider aanmaken
                                </a>
                            @endif
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Direct betalen bij boeking</td>
                    <td class="min-w-48 w-full pt-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="payment_booking_enabled" value="0">
                            <input type="checkbox"
                                   class="kt-checkbox"
                                   name="payment_booking_enabled"
                                   value="1"
                                   {{ old('payment_booking_enabled', $paymentBookingEnabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Klant betaalt direct via Mollie na het bevestigen van de boeking</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Betalen in chauffeur-app</td>
                    <td class="min-w-48 w-full pt-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="payment_driver_enabled" value="0">
                            <input type="checkbox"
                                   class="kt-checkbox"
                                   name="payment_driver_enabled"
                                   value="1"
                                   {{ old('payment_driver_enabled', $paymentDriverEnabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <span class="text-sm text-secondary-foreground">Chauffeur toont QR-code; rit afronden pas na betaling</span>
                        </label>
                        <p class="text-xs text-muted-foreground mt-1">
                            Als beide opties aan staan, kiest de klant bij de boeking. Vereist een actieve Mollie-provider voor dit bedrijf (zie hierboven).
                        </p>
                    </td>
                </tr>
            </table>
            </div>
            </div>
        </div>
        </div>
        </div>

        <div class="admin-form-actions flex flex-wrap items-center justify-end gap-2.5 w-full min-w-0">
            <a href="{{ route('admin.taxi.ride_requests.index') }}" class="kt-btn kt-btn-outline">Naar ritten</a>
            <button type="submit" class="kt-btn kt-btn-primary">Opslaan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var master = document.getElementById('customer_accept_enabled');
    var channels = document.querySelectorAll('.customer-accept-channel');
    function syncCustomerAcceptChannels() {
        var on = master && master.checked;
        channels.forEach(function (el) {
            el.disabled = !on;
        });
    }
    if (master) {
        master.addEventListener('change', syncCustomerAcceptChannels);
        syncCustomerAcceptChannels();
    }
});
</script>
@endpush
