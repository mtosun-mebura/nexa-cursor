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
                @if(!empty($noTenantSelected))
                    Platformstandaard voor <strong>Nexa Suite</strong> (marktplaats &amp; network).
                    Tenants zonder eigen waarde gebruiken deze instellingen; met een tenant geselecteerd bewerk je alleen dat bedrijf.
                @else
                    Instellingen voor de chauffeur-app en meldingen bij nieuwe boekingen. Geldt per bedrijf (tenant).
                    Zonder eigen acceptatietijd wordt de Nexa Suite-/serverstandaard gebruikt (nu {{ (int) round($envDefaultSeconds / 60) }} min).
                @endif
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
        <div class="kt-alert kt-alert-primary mb-5 min-w-0" role="status">
            <div class="flex gap-3 min-w-0 w-full">
                <i class="ki-filled ki-information-2 text-lg shrink-0 mt-0.5" aria-hidden="true"></i>
                <div class="min-w-0 flex-1 space-y-1.5 leading-relaxed">
                    <p class="mb-0 text-sm sm:text-base font-medium break-words">
                        Geen tenant geselecteerd — je bewerkt de <strong>Nexa Suite</strong>-standaard voor marktplaats en network.
                    </p>
                    <p class="mb-0 text-sm opacity-90 break-words">
                        Partner-koppelingen (invite-codes) blijven per bedrijf; selecteer daarvoor een tenant.
                    </p>
                </div>
            </div>
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
                <colgroup>
                    <col class="w-56">
                    <col>
                </colgroup>
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
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Geen chauffeur: keuze aan klant (minuten)</td>
                    <td class="min-w-48 w-full pt-4">
                        <input
                            type="number"
                            name="unaccepted_auto_cancel_minutes"
                            id="unaccepted_auto_cancel_minutes"
                            class="kt-input w-64 @error('unaccepted_auto_cancel_minutes') border-destructive @enderror"
                            min="{{ $minUnacceptedAutoCancelMinutes }}"
                            max="{{ $maxUnacceptedAutoCancelMinutes }}"
                            step="1"
                            required
                            value="{{ old('unaccepted_auto_cancel_minutes', $unacceptedAutoCancelMinutes) }}"
                        >
                        <p class="text-xs text-muted-foreground mt-1">
                            Na deze tijd na het ophaalmoment (zonder geaccepteerde chauffeur) krijgt de klant
                            de keuze: blijven wachten of annuleren. Stelt een chauffeur een nieuw tijdstip voor,
                            dan telt die nieuwe tijd.
                            Standaard server: {{ $envDefaultUnacceptedAutoCancelMinutes }} minuten.
                            0 = uit. Tussen {{ $minUnacceptedAutoCancelMinutes }} en {{ $maxUnacceptedAutoCancelMinutes }} minuten.
                        </p>
                        @error('unaccepted_auto_cancel_minutes')
                            <div class="text-xs text-destructive mt-1">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr>
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">Geen reactie van klant: auto-annuleren (minuten)</td>
                    <td class="min-w-48 w-full pt-4">
                        <input
                            type="number"
                            name="customer_unaccepted_decision_minutes"
                            id="customer_unaccepted_decision_minutes"
                            class="kt-input w-64 @error('customer_unaccepted_decision_minutes') border-destructive @enderror"
                            min="{{ $minCustomerUnacceptedDecisionMinutes }}"
                            max="{{ $maxCustomerUnacceptedDecisionMinutes }}"
                            step="1"
                            required
                            value="{{ old('customer_unaccepted_decision_minutes', $customerUnacceptedDecisionMinutes) }}"
                        >
                        <p class="text-xs text-muted-foreground mt-1">
                            Als de klant na de keuze-prompt niet reageert, wordt de rit na deze tijd automatisch
                            geannuleerd (met terugstorting bij vooraf betalen). Kiest de klant “blijven wachten”,
                            dan blijft de rit open.
                            Standaard server: {{ $envDefaultCustomerUnacceptedDecisionMinutes }} minuten.
                            0 = nooit automatisch. Tussen {{ $minCustomerUnacceptedDecisionMinutes }} en {{ $maxCustomerUnacceptedDecisionMinutes }} minuten.
                        </p>
                        @error('customer_unaccepted_decision_minutes')
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
            <p class="text-xs text-muted-foreground leading-relaxed mx-5 mt-4 mb-2 pt-1">
                Klant-WhatsApp en API staan onder Algemene configuraties → WhatsApp Business API.
                Boekingsmelding naar het bedrijf: schakelaar bij Boekingssjablonen (platform) + WhatsApp-nummer bedrijf onder Instellingen → WhatsApp (tenant).
            </p>
            <div class="px-3 sm:px-5 pb-3 min-w-0">
            <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                <colgroup>
                    <col class="w-56">
                    <col>
                </colgroup>
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
                </tr>
                <tr class="customer-accept-channel-row">
                    <td class="min-w-56 text-secondary-foreground font-normal align-top pt-4">WhatsApp communicatie klant</td>
                    <td class="min-w-48 w-full pt-4">
                        @php
                            $selectedCustomerWhatsappStatusEvents = old('customer_whatsapp_status_events', $customerWhatsappStatusEvents ?? []);
                            if (! is_array($selectedCustomerWhatsappStatusEvents)) {
                                $selectedCustomerWhatsappStatusEvents = [];
                            }
                        @endphp
                        <p class="text-sm text-secondary-foreground mb-2">
                            Kies welke WhatsApp-statusberichten de klant ontvangt nadat een chauffeur de rit via dispatch heeft geaccepteerd.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-w-xl">
                            @foreach($whatsappStatusEventLabels as $eventKey => $eventLabel)
                                <label class="inline-flex items-start gap-2 text-sm text-secondary-foreground cursor-pointer">
                                    <input type="checkbox"
                                           class="kt-checkbox mt-0.5"
                                           name="customer_whatsapp_status_events[]"
                                           value="{{ $eventKey }}"
                                           @checked(in_array($eventKey, $selectedCustomerWhatsappStatusEvents, true))>
                                    <span>{{ $eventLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-muted-foreground mt-2 mb-0 max-w-xl">
                            Uitgevinkte statussen worden niet naar de klant gestuurd. <strong>Rit afgerond</strong> staat standaard uit.
                            Sjabloon: <code class="text-xs">rit_status_update</code> onder
                            <a href="{{ route('admin.settings.general.index') }}#whatsapp-status-templates" class="underline">Algemene configuraties → WhatsApp</a>.
                        </p>
                    </td>
                </tr>
                <tr class="customer-accept-channel-row">
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
                            <span class="text-sm text-secondary-foreground">Klant kan daarnaast direct via Mollie betalen na het bevestigen van de boeking</span>
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
                            <span class="text-sm text-secondary-foreground">Daarnaast QR-code via Mollie in de chauffeur-app</span>
                        </label>
                        <p class="text-xs text-muted-foreground mt-1">
                            Contant betalen is altijd beschikbaar. De rit wordt pas afgerond na betaling; daarna kan de chauffeur een factuur naar de klant sturen. Deze vinkjes voegen online betalen bij boeking of QR in de app toe. QR en boeking vereisen een actieve Mollie-provider voor dit bedrijf (zie hierboven).
                        </p>
                    </td>
                </tr>
            </table>
            </div>
            </div>
        </div>

        <div class="settings-collapsible-section settings-collapsible-card--collapsed" id="dispatch-nexa-network">
            @include('admin.settings.partials.collapsible-header', ['titleHtml' => 'NEXA Network'])
            <div class="settings-collapsible-body">
            <div class="px-3 sm:px-5 pb-3 min-w-0">
                <p class="text-sm text-muted-foreground pt-3 mb-0">
                    @if(!empty($noTenantSelected))
                        Platformstandaard voor Nexa Suite. Tenants zonder eigen network-instelling gebruiken deze waarden.
                        Partner-invite-codes en handmatige partner-IDs stel je in per tenant.
                    @else
                        Standaard uit. Bij network blijft de booking-owner (<code>company_id</code>) van dit bedrijf;
                        een partner-taxi rijdt als uitvoerder (<code>fulfilling_company_id</code>).
                        Partners koppelen via invite-code — geen zicht op andere tenants.
                    @endif
                </p>

                @error('network_partnership')
                    <div class="kt-alert kt-alert-danger mt-3 mb-0">{{ $message }}</div>
                @enderror
                @error('invite_code')
                    <div class="kt-alert kt-alert-danger mt-3 mb-0">{{ $message }}</div>
                @enderror

                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground wizard-onboarding-form-table w-full">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Network inschakelen</td>
                        <td class="min-w-48 w-full">
                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="network_enabled" value="0">
                                <input type="checkbox" class="kt-checkbox" name="network_enabled" id="network_enabled" value="1"
                                       {{ old('network_enabled', !empty($networkEnabled) ? '1' : '0') === '1' ? 'checked' : '' }}>
                                <span class="text-sm text-secondary-foreground">Partner-taxi’s mogen ritten uitvoeren zonder klant-eigenaarschap over te nemen</span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Modus</td>
                        <td class="min-w-48 w-full">
                            <select name="network_mode" class="kt-select admin-field-fit" data-kt-select="true">
                                @foreach($networkModeOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('network_mode', $networkMode ?? 'off') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-muted-foreground mt-1 mb-0">
                                Handmatig: chauffeur kiest in de app “Naar network”. Automatisch: partners krijgen ook ritten bij escalatie.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Fallback (seconden)</td>
                        <td class="min-w-48 w-full">
                            <input type="number" name="network_fallback_seconds" class="kt-input w-32" min="30" max="3600"
                                   value="{{ old('network_fallback_seconds', $networkFallbackSeconds ?? 120) }}">
                            <p class="text-xs text-muted-foreground mt-1 mb-0">
                                Alleen bij modus Automatisch: na zoveel seconden zonder acceptatie door de eigen vloot
                                krijgen network-partners (binnen de max. radius) ook offers.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Max. radius (km)</td>
                        <td class="min-w-48 w-full">
                            <input type="number" name="network_max_radius_km" class="kt-input w-32" min="1" max="200"
                                   value="{{ old('network_max_radius_km', $networkMaxRadiusKm ?? 25) }}">
                            <p class="text-xs text-muted-foreground mt-1 mb-0">
                                Partner-chauffeurs krijgen alleen een aanbod als hun live GPS binnen deze hemelsbrede
                                afstand van de ophaallocatie ligt. Zonder GPS of zonder pickup-coördinaten: geen network-offer.
                            </p>
                        </td>
                    </tr>
                </table>

                @if(!empty($isSuperAdmin) && empty($noTenantSelected))
                    @php
                        $manualPartnerSelected = collect(
                            preg_split('/[\s,;]+/', (string) old('network_manual_partner_company_ids', $networkManualPartnerCompanyIds ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: []
                        )->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values()->all();
                    @endphp
                    <div class="mt-5 rounded-lg border border-dashed border-border p-4 min-w-0" id="network-manual-partners-picker"
                         data-selected="{{ implode(',', $manualPartnerSelected) }}">
                        <h4 class="text-sm font-semibold text-foreground mb-1">Super-admin: handmatige partner-IDs</h4>
                        <p class="text-xs text-muted-foreground mb-3">
                            Alleen voor support/onboarding. Klik op een ID om te (de)selecteren — multi-select.
                            Wordt samengevoegd met geaccepteerde invite-partners. Tenants zien deze lijst niet.
                        </p>
                        <input type="hidden" name="network_manual_partner_company_ids" id="network_manual_partner_company_ids"
                               value="{{ implode(', ', $manualPartnerSelected) }}">

                        <div class="relative mb-3 max-w-md">
                            <i class="ki-filled ki-magnifier absolute start-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm pointer-events-none" aria-hidden="true"></i>
                            <input type="search" id="network-manual-partner-search" class="kt-input w-full ps-9"
                                   placeholder="Zoek op naam of ID…" autocomplete="off" aria-label="Zoek bedrijven">
                            <div id="network-manual-partner-suggest"
                                 class="hidden absolute z-20 mt-1 w-full max-h-48 overflow-y-auto rounded-lg border border-border bg-background shadow-lg"
                                 role="listbox"></div>
                        </div>

                        <p class="text-xs text-muted-foreground mb-2">
                            Geselecteerd: <span id="network-manual-partner-count" class="font-semibold text-foreground">{{ count($manualPartnerSelected) }}</span>
                        </p>

                        @if(($networkPartnerCandidates ?? collect())->isNotEmpty())
                            <ul id="network-manual-partner-list" class="mb-0 flex flex-col gap-1.5 list-none p-0 max-h-56 overflow-y-auto">
                                @foreach($networkPartnerCandidates as $candidate)
                                    @php $isSelected = in_array((int) $candidate->id, $manualPartnerSelected, true); @endphp
                                    <li class="network-manual-partner-item flex flex-wrap items-center gap-2 text-sm min-w-0 rounded-md px-2 py-1.5 border transition-colors
                                               {{ $isSelected ? 'border-emerald-600/40 bg-emerald-600/15' : 'border-transparent' }}"
                                        data-id="{{ $candidate->id }}"
                                        data-name="{{ strtolower($candidate->name) }}"
                                        data-label="{{ $candidate->name }}">
                                        <button type="button"
                                                class="network-manual-partner-id kt-btn kt-btn-sm font-mono shrink-0 {{ $isSelected ? 'bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700' : 'kt-btn-outline' }}"
                                                data-id="{{ $candidate->id }}"
                                                aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                                title="{{ $isSelected ? 'Deselecteren' : 'Selecteren' }}">
                                            {{ $candidate->id }}
                                        </button>
                                        <span class="min-w-0 truncate text-foreground">{{ $candidate->name }}</span>
                                        @unless($candidate->is_active)
                                            <span class="kt-badge kt-badge-sm kt-badge-danger">Inactief</span>
                                        @endunless
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-muted-foreground mb-0">Geen andere bedrijven gevonden.</p>
                        @endif
                    </div>
                @endif

                @if(empty($noTenantSelected))
                <p class="text-xs text-muted-foreground mt-4 mb-0">
                    Partners koppelen (invite-code) doe je in het blok <a href="#dispatch-nexa-network-partners" class="text-primary hover:underline">Network-partners</a> onder Opslaan.
                </p>
                @endif
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

    @if(empty($noTenantSelected))
        <div class="kt-card w-full min-w-0 mt-5" id="dispatch-nexa-network-partners">
            <div class="kt-card-header px-5 py-5">
                <h3 class="kt-card-title mb-0">Network-partners (invite)</h3>
            </div>
            <div class="kt-card-content p-5">
                @error('network_partnership')
                    <div class="kt-alert kt-alert-danger mb-4">{{ $message }}</div>
                @enderror
                @error('invite_code')
                    <div class="kt-alert kt-alert-danger mb-4">{{ $message }}</div>
                @enderror

                <div class="grid gap-4">
                    <div class="rounded-lg border border-border p-4 min-w-0">
                        <h4 class="text-sm font-semibold text-foreground mb-1">1. Jouw invite-code delen</h4>
                        <p class="text-xs text-muted-foreground mb-3">
                            Deel deze code met een ander taxibedrijf. Zij plakken hem bij stap 2.
                            Jij ziet hun verzoek onder “Inkomende verzoeken” (tenzij auto-accept aan staat).
                        </p>
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <code class="text-base font-mono font-semibold tracking-wider text-foreground px-3 py-2 rounded-md border border-border bg-muted/30">
                                {{ $networkInviteCode?->code ?? '—' }}
                            </code>
                            @if($networkInviteCode?->code)
                                <button type="button"
                                        class="admin-email-copy kt-btn kt-btn-sm kt-btn-outline"
                                        data-copy-text="{{ $networkInviteCode->code }}"
                                        title="Code kopiëren">
                                    <i class="ki-filled ki-copy me-1"></i> Kopiëren
                                </button>
                            @endif
                            <form method="POST" action="{{ route('admin.taxi.dispatch_settings.network.invite_rotate') }}" class="inline m-0">
                                @csrf
                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline"
                                        onclick="return confirm('Oude code wordt ongeldig. Doorgaan?')">
                                    Nieuwe code
                                </button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('admin.taxi.dispatch_settings.network.invite_auto_accept') }}" class="m-0">
                            @csrf
                            <input type="hidden" name="auto_accept" value="0">
                            <label class="inline-flex items-center gap-2 mb-0">
                                <input type="checkbox" class="kt-checkbox" name="auto_accept" value="1"
                                       {{ !empty($networkInviteCode?->auto_accept) ? 'checked' : '' }}
                                       onchange="this.form.submit()">
                                <span class="text-sm text-secondary-foreground">Auto-accept: verzoeken via mijn code direct goedkeuren</span>
                            </label>
                        </form>
                        @if($networkInviteCode?->expires_at)
                            <p class="text-xs text-muted-foreground mt-2 mb-0">
                                Geldig tot {{ $networkInviteCode->expires_at->format('d-m-Y H:i') }}.
                            </p>
                        @endif
                    </div>

                    <div class="rounded-lg border border-border p-4 min-w-0">
                        <h4 class="text-sm font-semibold text-foreground mb-1">2. Partner koppelen (hun code)</h4>
                        <p class="text-xs text-muted-foreground mb-3">
                            Plak de invite-code van de partner. Jij blijft owner; zij mogen jouw network-ritten uitvoeren na acceptatie.
                        </p>
                        <form method="POST" action="{{ route('admin.taxi.dispatch_settings.network.invite_redeem') }}" class="flex flex-wrap items-end gap-2 m-0">
                            @csrf
                            <div class="min-w-0">
                                <label class="text-xs text-muted-foreground mb-1 block" for="network_invite_code_input">Invite-code</label>
                                <input type="text" name="invite_code" id="network_invite_code_input"
                                       class="kt-input w-48 font-mono uppercase @error('invite_code') border-destructive @enderror"
                                       value="{{ old('invite_code') }}"
                                       placeholder="AB12CD34" autocomplete="off" maxlength="32">
                            </div>
                            <button type="submit" class="kt-btn kt-btn-primary h-[34px] min-h-[34px] px-4 text-sm leading-none">Verzoek versturen</button>
                        </form>
                    </div>

                    @if(($networkPendingIncoming ?? collect())->isNotEmpty())
                        <div class="rounded-lg border border-border p-4 min-w-0">
                            <h4 class="text-sm font-semibold text-foreground mb-2">Inkomende verzoeken</h4>
                            <p class="text-xs text-muted-foreground mb-3">Deze bedrijven willen jou als uitvoerder voor hun network-ritten.</p>
                            <ul class="list-none p-0 m-0 flex flex-col gap-2">
                                @foreach($networkPendingIncoming as $partnership)
                                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-border px-3 py-2">
                                        <span class="text-sm text-foreground font-medium">
                                            {{ $partnership->ownerCompany?->name ?? ('Bedrijf #'.$partnership->owner_company_id) }}
                                        </span>
                                        <div class="flex flex-wrap gap-2">
                                            <form method="POST" action="{{ route('admin.taxi.dispatch_settings.network.partnership_accept', $partnership) }}" class="m-0">
                                                @csrf
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-primary">Accepteren</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.taxi.dispatch_settings.network.partnership_decline', $partnership) }}" class="m-0">
                                                @csrf
                                                <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">Afwijzen</button>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(($networkPendingOutgoing ?? collect())->isNotEmpty())
                        <div class="rounded-lg border border-border p-4 min-w-0">
                            <h4 class="text-sm font-semibold text-foreground mb-2">Uitgaande verzoeken (wacht op acceptatie)</h4>
                            <ul class="list-none p-0 m-0 flex flex-col gap-2">
                                @foreach($networkPendingOutgoing as $partnership)
                                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-border px-3 py-2">
                                        <span class="text-sm text-foreground">
                                            {{ $partnership->partnerCompany?->name ?? ('Partner #'.$partnership->partner_company_id) }}
                                            <span class="text-xs text-muted-foreground">· in afwachting</span>
                                        </span>
                                        <form method="POST" action="{{ route('admin.taxi.dispatch_settings.network.partnership_revoke', $partnership) }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">Intrekken</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="rounded-lg border border-border p-4 min-w-0">
                        <h4 class="text-sm font-semibold text-foreground mb-2">Actieve partners</h4>
                        @if(($networkAcceptedPartners ?? collect())->isEmpty())
                            <p class="text-sm text-muted-foreground mb-0">Nog geen partners gekoppeld. Deel of plak een invite-code hierboven.</p>
                        @else
                            <ul class="list-none p-0 m-0 flex flex-col gap-2">
                                @foreach($networkAcceptedPartners as $partnership)
                                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-border px-3 py-2">
                                        <span class="text-sm text-foreground font-medium">
                                            {{ $partnership->partnerCompany?->name ?? ('Partner #'.$partnership->partner_company_id) }}
                                        </span>
                                        <form method="POST" action="{{ route('admin.taxi.dispatch_settings.network.partnership_revoke', $partnership) }}" class="m-0"
                                              onsubmit="return confirm('Koppeling intrekken?')">
                                            @csrf
                                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">Intrekken</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
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

    initNetworkManualPartnerPicker();
});

function initNetworkManualPartnerPicker() {
    var root = document.getElementById('network-manual-partners-picker');
    if (!root) return;

    var hidden = document.getElementById('network_manual_partner_company_ids');
    var search = document.getElementById('network-manual-partner-search');
    var suggest = document.getElementById('network-manual-partner-suggest');
    var countEl = document.getElementById('network-manual-partner-count');
    var items = Array.prototype.slice.call(root.querySelectorAll('.network-manual-partner-item'));

    var selected = new Set();
    (root.getAttribute('data-selected') || '').split(',').forEach(function (raw) {
        var id = parseInt(raw, 10);
        if (id > 0) selected.add(String(id));
    });

    function syncHidden() {
        var ids = Array.from(selected).map(Number).filter(function (n) { return n > 0; }).sort(function (a, b) { return a - b; });
        if (hidden) hidden.value = ids.join(', ');
        if (countEl) countEl.textContent = String(ids.length);
    }

    function paintItem(item) {
        var id = item.getAttribute('data-id');
        var btn = item.querySelector('.network-manual-partner-id');
        var on = selected.has(String(id));
        item.classList.toggle('border-emerald-600/40', on);
        item.classList.toggle('bg-emerald-600/15', on);
        item.classList.toggle('border-transparent', !on);
        if (btn) {
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.title = on ? 'Deselecteren' : 'Selecteren';
            btn.classList.toggle('bg-emerald-600', on);
            btn.classList.toggle('text-white', on);
            btn.classList.toggle('border-emerald-600', on);
            btn.classList.toggle('hover:bg-emerald-700', on);
            btn.classList.toggle('kt-btn-outline', !on);
        }
    }

    function toggleId(id) {
        id = String(id);
        if (selected.has(id)) selected.delete(id);
        else selected.add(id);
        syncHidden();
        items.forEach(function (item) {
            if (item.getAttribute('data-id') === id) paintItem(item);
        });
        renderSuggest(search ? search.value : '');
    }

    items.forEach(function (item) {
        paintItem(item);
        var btn = item.querySelector('.network-manual-partner-id');
        if (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                toggleId(btn.getAttribute('data-id'));
            });
        }
    });

    function matchesQuery(item, q) {
        if (!q) return true;
        var id = String(item.getAttribute('data-id') || '');
        var name = String(item.getAttribute('data-name') || '');
        return id.indexOf(q) !== -1 || name.indexOf(q) !== -1;
    }

    function filterList(q) {
        q = String(q || '').trim().toLowerCase();
        items.forEach(function (item) {
            item.classList.toggle('hidden', !matchesQuery(item, q));
        });
    }

    function renderSuggest(q) {
        if (!suggest) return;
        q = String(q || '').trim().toLowerCase();
        if (!q) {
            suggest.classList.add('hidden');
            suggest.innerHTML = '';
            return;
        }
        var hits = items.filter(function (item) { return matchesQuery(item, q); }).slice(0, 8);
        if (!hits.length) {
            suggest.classList.add('hidden');
            suggest.innerHTML = '';
            return;
        }
        suggest.innerHTML = hits.map(function (item) {
            var id = item.getAttribute('data-id');
            var label = item.getAttribute('data-label') || '';
            var on = selected.has(String(id));
            return '<button type="button" role="option" data-suggest-id="' + id + '"'
                + ' class="flex w-full items-center gap-2 px-3 py-2 text-sm text-start hover:bg-accent/50 '
                + (on ? 'bg-emerald-600/10' : '') + '">'
                + '<span class="font-mono font-semibold ' + (on ? 'text-emerald-600' : 'text-foreground') + '">' + id + '</span>'
                + '<span class="truncate text-foreground">' + label.replace(/</g, '&lt;') + '</span>'
                + (on ? '<span class="ms-auto text-xs text-emerald-600">gekozen</span>' : '')
                + '</button>';
        }).join('');
        suggest.classList.remove('hidden');
    }

    if (search) {
        search.addEventListener('input', function () {
            filterList(search.value);
            renderSuggest(search.value);
        });
        search.addEventListener('focus', function () {
            if (search.value.trim()) renderSuggest(search.value);
        });
        search.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                suggest.classList.add('hidden');
                search.blur();
            }
        });
    }

    if (suggest) {
        suggest.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-suggest-id]');
            if (!btn) return;
            e.preventDefault();
            toggleId(btn.getAttribute('data-suggest-id'));
            var id = btn.getAttribute('data-suggest-id');
            var row = root.querySelector('.network-manual-partner-item[data-id="' + id + '"]');
            if (row) {
                row.classList.remove('hidden');
                row.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!root.contains(e.target) && suggest) {
            suggest.classList.add('hidden');
        }
    });

    syncHidden();
}
</script>
@endpush
