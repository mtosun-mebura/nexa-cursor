<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Modules\NexaTaxi\Services\TaxiCustomerAcceptEmailTemplateService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiCustomerSmsService;
use App\Services\PaymentProviderService;
use App\Services\WhatsAppBusinessService;
use App\Support\DutchPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DispatchSettingsController extends Controller
{
    public function __construct(
        protected TaxiDispatchSettingsService $dispatchSettings,
        protected WhatsAppBusinessService $whatsapp,
        protected PaymentProviderService $paymentProviders,
        protected TaxiCustomerSmsService $customerSms,
        protected TaxiCustomerAcceptEmailTemplateService $customerAcceptEmailTemplate
    ) {}

    public function edit(): View
    {
        $this->authorizeOrPermissionAny(['rides.view', 'rides.update']);

        $companyId = GeneralSetting::resolveScopeCompanyId();
        $ttlSeconds = $this->dispatchSettings->offerTtlSeconds($companyId);
        $envDefault = (int) config('taxi-dispatch.offer_ttl_seconds', 300);
        $storedWhatsappNumber = trim((string) GeneralSetting::get(
            TaxiDispatchSettingsService::KEY_BOOKING_WHATSAPP_NUMBER,
            null,
            $companyId
        ));
        $displayWhatsappNumber = $storedWhatsappNumber !== ''
            ? $storedWhatsappNumber
            : $this->dispatchSettings->envFallbackWhatsappNumber();

        return view('taxi::admin.dispatch-settings.edit', [
            'noTenantSelected' => $companyId === null,
            'offerTtlSeconds' => $ttlSeconds,
            'offerTtlMinutes' => (int) round($ttlSeconds / 60),
            'envDefaultSeconds' => $envDefault,
            'minMinutes' => (int) ceil(TaxiDispatchSettingsService::MIN_TTL_SECONDS / 60),
            'maxMinutes' => (int) floor(TaxiDispatchSettingsService::MAX_TTL_SECONDS / 60),
            'bookingWhatsappEnabled' => $this->dispatchSettings->bookingWhatsappEnabled($companyId),
            'bookingWhatsappClickToChat' => $this->dispatchSettings->bookingWhatsappClickToChatEnabled($companyId),
            'bookingDriverEmailEnabled' => $this->dispatchSettings->bookingDriverEmailEnabled($companyId),
            'bookingCustomerEmailEnabled' => $this->dispatchSettings->bookingCustomerEmailEnabled($companyId),
            'bookingWhatsappNumber' => $displayWhatsappNumber,
            'hasStoredWhatsappNumber' => $storedWhatsappNumber !== '',
            'whatsappApiConfigured' => $this->whatsapp->isConfigured(),
            'envFallbackWhatsappNumber' => $this->dispatchSettings->envFallbackWhatsappNumber(),
            'paymentBookingEnabled' => $this->dispatchSettings->paymentBookingEnabled($companyId),
            'paymentDriverEnabled' => $this->dispatchSettings->paymentDriverEnabled($companyId),
            'mollieSummary' => $this->paymentProviders->mollieSummaryForCompany($companyId),
            'defaultTaxiWebhookUrl' => url('/api/taxi/webhooks/mollie'),
            'canManagePaymentProviders' => auth()->user()->hasRole('super-admin')
                || auth()->user()->can('view-payment-providers')
                || auth()->user()->can('edit-payment-providers'),
            'customerAcceptEnabled' => $this->dispatchSettings->customerAcceptNotificationEnabled($companyId),
            'customerAcceptEmailEnabled' => $this->dispatchSettings->customerAcceptEmailEnabled($companyId),
            'customerAcceptWhatsappEnabled' => $this->dispatchSettings->customerAcceptWhatsappEnabled($companyId),
            'customerAcceptSmsEnabled' => $this->dispatchSettings->customerAcceptSmsEnabled($companyId),
            'customerAcceptSmsProvider' => $this->dispatchSettings->customerAcceptSmsProvider($companyId),
            'customerAcceptPlainMessage' => $this->dispatchSettings->customerAcceptPlainMessage($companyId),
            'customerAcceptWhatsappTemplate' => $this->dispatchSettings->customerAcceptWhatsappTemplateName($companyId),
            'customerAcceptWhatsappTemplateLang' => $this->dispatchSettings->customerAcceptWhatsappTemplateLanguage($companyId),
            'smsProviderOptions' => TaxiDispatchSettingsService::smsProviderOptions(),
            'vonageConfigured' => $this->customerSms->isVonageConfigured(),
            'customerAcceptEmailEditUrl' => route('admin.taxi.dispatch_settings.customer_accept_email.edit'),
            'canEditEmailTemplatesModule' => auth()->user()->hasRole('super-admin')
                || auth()->user()->can('edit-email-templates'),
            'emailTemplateIndexUrl' => route('admin.email-templates.index', ['type' => 'taxi_ride_accepted']),
            'customerLoginCodeExpiresMinutes' => $this->dispatchSettings->customerLoginCodeExpiresMinutes($companyId),
            'minLoginCodeExpiresMinutes' => TaxiDispatchSettingsService::MIN_LOGIN_CODE_EXPIRES_MINUTES,
            'maxLoginCodeExpiresMinutes' => TaxiDispatchSettingsService::MAX_LOGIN_CODE_EXPIRES_MINUTES,
            'envDefaultLoginCodeExpiresMinutes' => (int) config('taxi-dispatch.customer_login_code_expires_minutes', 15),
            'customerLoginCodeEmailTemplateUrl' => route('admin.email-templates.index', ['type' => 'taxi_customer_login_code']),
        ]);
    }

    public function editCustomerAcceptEmail(): View
    {
        $this->authorizeOrPermissionAny(['rides.view', 'rides.update']);

        $companyId = GeneralSetting::resolveScopeCompanyId();
        $resolved = $this->customerAcceptEmailTemplate->templateForEditing($companyId);

        return view('taxi::admin.dispatch-settings.customer-accept-email', [
            'template' => $resolved['template'],
            'usesGlobalFallback' => $resolved['usesGlobalFallback'],
            'companyId' => $companyId,
            'variableLabels' => TaxiCustomerAcceptEmailTemplateService::variableLabels(),
            'dispatchSettingsUrl' => route('admin.taxi.dispatch_settings.edit'),
            'canEditEmailTemplatesModule' => auth()->user()->hasRole('super-admin')
                || auth()->user()->can('edit-email-templates'),
        ]);
    }

    public function updateCustomerAcceptEmail(Request $request): RedirectResponse
    {
        $this->authorizeOrPermissionAny(['rides.update']);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'html_content' => ['required', 'string'],
            'text_content' => ['nullable', 'string'],
            'uses_global_fallback' => ['nullable', 'in:0,1'],
        ], [
            'subject.required' => 'Vul een onderwerp in.',
            'html_content.required' => 'Vul de HTML-inhoud van de e-mail in.',
        ]);

        $companyId = GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null) {
            return $this->redirectNoTenant('admin.taxi.dispatch_settings.customer_accept_email.edit');
        }
        $usesGlobalFallback = $request->input('uses_global_fallback') === '1';

        $this->customerAcceptEmailTemplate->saveForCompany($companyId, $validated, $usesGlobalFallback);

        return redirect()
            ->route('admin.taxi.dispatch_settings.customer_accept_email.edit', ['saved' => 1])
            ->with('success', 'E-mailtekst voor rit geaccepteerd is opgeslagen.');
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeOrPermissionAny(['rides.update']);

        $minMinutes = (int) ceil(TaxiDispatchSettingsService::MIN_TTL_SECONDS / 60);
        $maxMinutes = (int) floor(TaxiDispatchSettingsService::MAX_TTL_SECONDS / 60);
        $minLoginCodeMinutes = TaxiDispatchSettingsService::MIN_LOGIN_CODE_EXPIRES_MINUTES;
        $maxLoginCodeMinutes = TaxiDispatchSettingsService::MAX_LOGIN_CODE_EXPIRES_MINUTES;

        $validated = $request->validate([
            'offer_ttl_minutes' => ['required', 'integer', 'min:'.$minMinutes, 'max:'.$maxMinutes],
            'customer_login_code_expires_minutes' => ['required', 'integer', 'min:'.$minLoginCodeMinutes, 'max:'.$maxLoginCodeMinutes],
            'booking_whatsapp_enabled' => ['nullable', 'in:0,1'],
            'booking_whatsapp_click_to_chat' => ['nullable', 'in:0,1'],
            'booking_driver_email_enabled' => ['nullable', 'in:0,1'],
            'booking_customer_email_enabled' => ['nullable', 'in:0,1'],
            'booking_whatsapp_number' => ['nullable', 'string', 'max:50'],
            'payment_booking_enabled' => ['nullable', 'in:0,1'],
            'payment_driver_enabled' => ['nullable', 'in:0,1'],
            'customer_accept_enabled' => ['nullable', 'in:0,1'],
            'customer_accept_email_enabled' => ['nullable', 'in:0,1'],
            'customer_accept_whatsapp_enabled' => ['nullable', 'in:0,1'],
            'customer_accept_sms_enabled' => ['nullable', 'in:0,1'],
            'customer_accept_sms_provider' => ['nullable', 'string', 'in:off,demo,vonage'],
            'customer_accept_plain_message' => ['nullable', 'string', 'max:4000'],
            'customer_accept_whatsapp_template' => ['nullable', 'string', 'max:120'],
            'customer_accept_whatsapp_template_lang' => ['nullable', 'string', 'max:12'],
        ], [
            'offer_ttl_minutes.required' => 'Vul de acceptatietijd in.',
            'offer_ttl_minutes.integer' => 'Acceptatietijd moet een heel getal zijn.',
            'offer_ttl_minutes.min' => 'Acceptatietijd moet minimaal '.$minMinutes.' minuut zijn.',
            'offer_ttl_minutes.max' => 'Acceptatietijd mag maximaal '.$maxMinutes.' minuten zijn.',
            'customer_login_code_expires_minutes.required' => 'Vul de geldigheid van de inlogcode in.',
            'customer_login_code_expires_minutes.min' => 'Geldigheid moet minimaal '.$minLoginCodeMinutes.' minuten zijn.',
            'customer_login_code_expires_minutes.max' => 'Geldigheid mag maximaal '.$maxLoginCodeMinutes.' minuten zijn.',
        ]);

        $companyId = GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null) {
            return $this->redirectNoTenant('admin.taxi.dispatch_settings.edit');
        }
        $normalizedWhatsappNumber = DutchPhoneNumber::normalizeOptionalNlToInternational(
            trim((string) ($validated['booking_whatsapp_number'] ?? ''))
        );
        if ($normalizedWhatsappNumber === null) {
            return redirect()
                ->route('admin.taxi.dispatch_settings.edit')
                ->withErrors(['booking_whatsapp_number' => 'Telefoonnummer moet een geldig Nederlands nummer zijn (bijv. 0612345678 of +31612345678).'])
                ->withInput();
        }

        $seconds = $this->dispatchSettings->clampTtl((int) $validated['offer_ttl_minutes'] * 60);
        $this->dispatchSettings->setOfferTtlSeconds($seconds, $companyId);
        $this->dispatchSettings->setCustomerLoginCodeExpiresMinutes(
            (int) $validated['customer_login_code_expires_minutes'],
            $companyId
        );
        $this->dispatchSettings->setBookingWhatsappEnabled($request->boolean('booking_whatsapp_enabled'), $companyId);
        $this->dispatchSettings->setBookingWhatsappClickToChatEnabled($request->boolean('booking_whatsapp_click_to_chat'), $companyId);
        $this->dispatchSettings->setBookingDriverEmailEnabled($request->boolean('booking_driver_email_enabled'), $companyId);
        $this->dispatchSettings->setBookingCustomerEmailEnabled($request->boolean('booking_customer_email_enabled'), $companyId);
        $this->dispatchSettings->setBookingWhatsappNumber((string) $normalizedWhatsappNumber, $companyId);
        $this->dispatchSettings->setPaymentBookingEnabled($request->boolean('payment_booking_enabled'), $companyId);
        $this->dispatchSettings->setPaymentDriverEnabled($request->boolean('payment_driver_enabled'), $companyId);

        $acceptEnabled = $request->boolean('customer_accept_enabled');
        $this->dispatchSettings->setCustomerAcceptNotificationEnabled($acceptEnabled, $companyId);
        $this->dispatchSettings->setCustomerAcceptEmailEnabled($acceptEnabled && $request->boolean('customer_accept_email_enabled'), $companyId);
        $this->dispatchSettings->setCustomerAcceptWhatsappEnabled($acceptEnabled && $request->boolean('customer_accept_whatsapp_enabled'), $companyId);
        $this->dispatchSettings->setCustomerAcceptSmsEnabled($acceptEnabled && $request->boolean('customer_accept_sms_enabled'), $companyId);
        $this->dispatchSettings->setCustomerAcceptSmsProvider(
            (string) ($validated['customer_accept_sms_provider'] ?? TaxiDispatchSettingsService::SMS_PROVIDER_OFF),
            $companyId
        );
        if (array_key_exists('customer_accept_plain_message', $validated)) {
            $this->dispatchSettings->setCustomerAcceptPlainMessage((string) $validated['customer_accept_plain_message'], $companyId);
        }
        $this->dispatchSettings->setCustomerAcceptWhatsappTemplateName(
            (string) ($validated['customer_accept_whatsapp_template'] ?? ''),
            $companyId
        );
        $this->dispatchSettings->setCustomerAcceptWhatsappTemplateLanguage(
            (string) ($validated['customer_accept_whatsapp_template_lang'] ?? 'nl'),
            $companyId
        );

        return redirect()
            ->route('admin.taxi.dispatch_settings.edit', ['saved' => 1])
            ->with('success', 'Dispatch-instellingen zijn opgeslagen.');
    }

    /**
     * Dispatch-instellingen worden per tenant (company_id) opgeslagen. Zonder tenant-context
     * (bijv. super-admin op het hoofddomein zonder geselecteerde tenant) kan er niet worden
     * opgeslagen: redirect met een duidelijke melding i.p.v. een 500-fout.
     */
    private function redirectNoTenant(string $route): RedirectResponse
    {
        $message = auth()->user()?->hasRole('super-admin')
            ? 'Selecteer eerst een tenant (bedrijf) in de zijbalk om de dispatch-instellingen te bewerken.'
            : 'Geen bedrijf gekoppeld aan dit account.';

        return redirect()->route($route)->withErrors(['tenant' => $message])->withInput();
    }

    private function authorizeOrPermissionAny(array $abilities): void
    {
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        foreach ($abilities as $ability) {
            if (auth()->user()->can($ability)) {
                return;
            }
        }
        abort(403, 'Geen rechten voor deze actie.');
    }
}
