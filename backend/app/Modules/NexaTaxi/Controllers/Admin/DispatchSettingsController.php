<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\TaxiNetworkPartnership;
use App\Modules\NexaTaxi\Support\TaxiDispatchAccess;
use App\Modules\NexaTaxi\Services\TaxiCustomerAcceptEmailTemplateService;
use App\Modules\NexaTaxi\Services\TaxiCustomerSmsService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiNetworkPartnershipService;
use App\Services\PaymentProviderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class DispatchSettingsController extends Controller
{
    public function __construct(
        protected TaxiDispatchSettingsService $dispatchSettings,
        protected PaymentProviderService $paymentProviders,
        protected TaxiCustomerSmsService $customerSms,
        protected TaxiCustomerAcceptEmailTemplateService $customerAcceptEmailTemplate,
        protected TaxiNetworkPartnershipService $networkPartnerships
    ) {}

    public function edit(): View
    {
        TaxiDispatchAccess::authorizeDispatchScreen();

        $companyId = GeneralSetting::resolveScopeCompanyId();
        $ttlSeconds = $this->dispatchSettings->offerTtlSeconds($companyId);
        $envDefault = (int) config('taxi-dispatch.offer_ttl_seconds', 300);
        $isSuperAdmin = TaxiDispatchAccess::isSuper(auth()->user());

        $inviteCode = null;
        $pendingIncoming = collect();
        $pendingOutgoing = collect();
        $acceptedPartners = collect();
        if ($companyId) {
            $inviteCode = $this->networkPartnerships->ensureActiveInviteCode($companyId, auth()->user());
            $pendingIncoming = $this->networkPartnerships->pendingIncomingForPartner($companyId);
            $pendingOutgoing = $this->networkPartnerships->pendingOutgoingAsOwner($companyId);
            $acceptedPartners = $this->networkPartnerships->acceptedAsOwner($companyId);
        }

        $marketplaceNetworkOnly = ! $isSuperAdmin && $this->isMarketplacePackageCompany($companyId);
        if ($marketplaceNetworkOnly && ! TaxiDispatchAccess::sees(auth()->user(), 'network')) {
            abort(403, __('Geen rechten voor chauffeur dispatch.'));
        }
        $marketplaceCanOwnNetwork = $this->networkPartnerships->companyCanInitiateNetworkPartnerships($companyId);

        return view('taxi::admin.dispatch-settings.edit', [
            'noTenantSelected' => $companyId === null,
            'marketplaceNetworkOnly' => $marketplaceNetworkOnly,
            'marketplaceCanOwnNetwork' => $marketplaceCanOwnNetwork,
            'offerTtlSeconds' => $ttlSeconds,
            'offerTtlMinutes' => (int) round($ttlSeconds / 60),
            'envDefaultSeconds' => $envDefault,
            'minMinutes' => (int) ceil(TaxiDispatchSettingsService::MIN_TTL_SECONDS / 60),
            'maxMinutes' => (int) floor(TaxiDispatchSettingsService::MAX_TTL_SECONDS / 60),
            'pastPickupGraceMinutes' => $this->dispatchSettings->pastPickupGraceMinutes($companyId),
            'envDefaultPastPickupGraceMinutes' => (int) config('taxi-dispatch.past_pickup_grace_minutes', 60),
            'minPastPickupGraceMinutes' => TaxiDispatchSettingsService::MIN_PAST_PICKUP_GRACE_MINUTES,
            'maxPastPickupGraceMinutes' => TaxiDispatchSettingsService::MAX_PAST_PICKUP_GRACE_MINUTES,
            'overdueAutoArchiveDays' => $this->dispatchSettings->overdueAutoArchiveDays($companyId),
            'envDefaultOverdueAutoArchiveDays' => (int) config('taxi-dispatch.overdue_auto_archive_days', 2),
            'minOverdueAutoArchiveDays' => TaxiDispatchSettingsService::MIN_OVERDUE_AUTO_ARCHIVE_DAYS,
            'maxOverdueAutoArchiveDays' => TaxiDispatchSettingsService::MAX_OVERDUE_AUTO_ARCHIVE_DAYS,
            'unacceptedAutoCancelMinutes' => $this->dispatchSettings->unacceptedAutoCancelMinutes($companyId),
            'envDefaultUnacceptedAutoCancelMinutes' => (int) config('taxi-dispatch.unaccepted_auto_cancel_minutes', 30),
            'minUnacceptedAutoCancelMinutes' => TaxiDispatchSettingsService::MIN_UNACCEPTED_AUTO_CANCEL_MINUTES,
            'maxUnacceptedAutoCancelMinutes' => TaxiDispatchSettingsService::MAX_UNACCEPTED_AUTO_CANCEL_MINUTES,
            'customerUnacceptedDecisionMinutes' => $this->dispatchSettings->customerUnacceptedDecisionMinutes($companyId),
            'envDefaultCustomerUnacceptedDecisionMinutes' => (int) config('taxi-dispatch.customer_unaccepted_decision_minutes', 30),
            'minCustomerUnacceptedDecisionMinutes' => TaxiDispatchSettingsService::MIN_CUSTOMER_UNACCEPTED_DECISION_MINUTES,
            'maxCustomerUnacceptedDecisionMinutes' => TaxiDispatchSettingsService::MAX_CUSTOMER_UNACCEPTED_DECISION_MINUTES,
            'bookingDriverEmailEnabled' => $this->dispatchSettings->bookingDriverEmailEnabled($companyId),
            'bookingCustomerEmailEnabled' => $this->dispatchSettings->bookingCustomerEmailEnabled($companyId),
            'paymentBookingEnabled' => $this->dispatchSettings->paymentBookingEnabled($companyId),
            'paymentDriverEnabled' => $this->dispatchSettings->paymentDriverEnabled($companyId),
            'mollieSummary' => $this->paymentProviders->mollieSummaryForCompany($companyId),
            'defaultTaxiWebhookUrl' => url('/api/taxi/webhooks/mollie'),
            'canManagePaymentProviders' => $isSuperAdmin
                || auth()->user()->can('view-payment-providers')
                || auth()->user()->can('edit-payment-providers'),
            'customerAcceptEnabled' => $this->dispatchSettings->customerAcceptNotificationEnabled($companyId),
            'customerAcceptEmailEnabled' => $this->dispatchSettings->customerAcceptEmailEnabled($companyId),
            'customerAcceptWhatsappEnabled' => $this->dispatchSettings->customerAcceptWhatsappEnabled($companyId),
            'customerAcceptSmsEnabled' => $this->dispatchSettings->customerAcceptSmsEnabled($companyId),
            'customerAcceptSmsProvider' => $this->dispatchSettings->customerAcceptSmsProvider($companyId),
            'whatsappStatusEventLabels' => TaxiDispatchSettingsService::customerWhatsappStatusEventLabels(),
            'customerWhatsappStatusEvents' => $this->dispatchSettings->customerWhatsappStatusEvents($companyId),
            'whatsappApiConfigured' => $this->dispatchSettings->whatsappApiConfigured($companyId),
            'smsProviderOptions' => TaxiDispatchSettingsService::smsProviderOptions(),
            'vonageConfigured' => $this->customerSms->isVonageConfigured(),
            'customerAcceptEmailEditUrl' => route('admin.taxi.dispatch_settings.customer_accept_email.edit'),
            'canEditEmailTemplatesModule' => $isSuperAdmin
                || auth()->user()->can('edit-email-templates'),
            'emailTemplateIndexUrl' => route('admin.email-templates.index', ['type' => 'taxi_ride_accepted']),
            'customerLoginCodeExpiresMinutes' => $this->dispatchSettings->customerLoginCodeExpiresMinutes($companyId),
            'minLoginCodeExpiresMinutes' => TaxiDispatchSettingsService::MIN_LOGIN_CODE_EXPIRES_MINUTES,
            'maxLoginCodeExpiresMinutes' => TaxiDispatchSettingsService::MAX_LOGIN_CODE_EXPIRES_MINUTES,
            'envDefaultLoginCodeExpiresMinutes' => (int) config('taxi-dispatch.customer_login_code_expires_minutes', 15),
            'customerLoginCodeEmailTemplateUrl' => route('admin.email-templates.index', ['type' => 'taxi_customer_login_code']),
            'networkEnabled' => $this->dispatchSettings->networkEnabled($companyId),
            'networkMode' => $this->dispatchSettings->networkMode($companyId),
            'networkModeOptions' => TaxiDispatchSettingsService::networkModeOptions(),
            'networkFallbackSeconds' => $this->dispatchSettings->networkFallbackSeconds($companyId),
            'networkMaxRadiusKm' => $this->dispatchSettings->networkMaxRadiusKm($companyId),
            'isSuperAdmin' => $isSuperAdmin,
            'networkManualPartnerCompanyIds' => implode(', ', $this->dispatchSettings->networkManualPartnerCompanyIds($companyId)),
            'networkPartnerCandidates' => $isSuperAdmin
                ? Company::query()
                    ->when($companyId, fn ($q) => $q->where('id', '!=', $companyId))
                    ->orderBy('name')
                    ->get(['id', 'name', 'is_active'])
                : collect(),
            'networkInviteCode' => $inviteCode,
            'networkPendingIncoming' => $pendingIncoming,
            'networkPendingOutgoing' => $pendingOutgoing,
            'networkAcceptedPartners' => $acceptedPartners,
            'dispatchSections' => TaxiDispatchAccess::visibleMap(auth()->user()),
        ]);
    }

    public function editCustomerAcceptEmail(): View
    {
        if (! TaxiDispatchAccess::sees(auth()->user(), 'customer_accept')) {
            abort(403, __('Geen rechten voor deze actie.'));
        }

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
        TaxiDispatchAccess::authorizeMutate('customer_accept');

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'html_content' => ['required', 'string'],
            'text_content' => ['nullable', 'string'],
            'uses_global_fallback' => ['nullable', 'in:0,1'],
        ], [
            'subject.required' => __('Vul een onderwerp in.'),
            'html_content.required' => __('Vul de HTML-inhoud van de e-mail in.'),
        ]);

        $companyId = GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null) {
            return $this->redirectNoTenant('admin.taxi.dispatch_settings.customer_accept_email.edit');
        }
        $usesGlobalFallback = $request->input('uses_global_fallback') === '1';

        $this->customerAcceptEmailTemplate->saveForCompany($companyId, $validated, $usesGlobalFallback);

        return redirect()
            ->route('admin.taxi.dispatch_settings.customer_accept_email.edit', ['saved' => 1])
            ->with('success', __('E-mailtekst voor rit geaccepteerd is opgeslagen.'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $fullSave = TaxiDispatchAccess::isSuper($user);
        if (! $fullSave) {
            TaxiDispatchAccess::authorizeDispatchScreen();
        }

        $companyId = GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null && ! auth()->user()?->hasRole('super-admin')) {
            return $this->redirectNoTenant('admin.taxi.dispatch_settings.edit');
        }

        if (! $fullSave && $this->isMarketplacePackageCompany($companyId)) {
            TaxiDispatchAccess::authorizeMutate('network');

            return $this->updateMarketplaceNetworkOnly($request, $companyId);
        }

        if (! $fullSave) {
            return $this->updateVisibleSections($request, $companyId);
        }

        $this->authorizeOrPermissionAny(['rides.update']);

        $minMinutes = (int) ceil(TaxiDispatchSettingsService::MIN_TTL_SECONDS / 60);
        $maxMinutes = (int) floor(TaxiDispatchSettingsService::MAX_TTL_SECONDS / 60);
        $minLoginCodeMinutes = TaxiDispatchSettingsService::MIN_LOGIN_CODE_EXPIRES_MINUTES;
        $maxLoginCodeMinutes = TaxiDispatchSettingsService::MAX_LOGIN_CODE_EXPIRES_MINUTES;
        $minGraceMinutes = TaxiDispatchSettingsService::MIN_PAST_PICKUP_GRACE_MINUTES;
        $maxGraceMinutes = TaxiDispatchSettingsService::MAX_PAST_PICKUP_GRACE_MINUTES;
        $minOverdueArchiveDays = TaxiDispatchSettingsService::MIN_OVERDUE_AUTO_ARCHIVE_DAYS;
        $maxOverdueArchiveDays = TaxiDispatchSettingsService::MAX_OVERDUE_AUTO_ARCHIVE_DAYS;
        $minAutoCancelMinutes = TaxiDispatchSettingsService::MIN_UNACCEPTED_AUTO_CANCEL_MINUTES;
        $maxAutoCancelMinutes = TaxiDispatchSettingsService::MAX_UNACCEPTED_AUTO_CANCEL_MINUTES;
        $minDecisionMinutes = TaxiDispatchSettingsService::MIN_CUSTOMER_UNACCEPTED_DECISION_MINUTES;
        $maxDecisionMinutes = TaxiDispatchSettingsService::MAX_CUSTOMER_UNACCEPTED_DECISION_MINUTES;
        $isSuperAdmin = auth()->user()->hasRole('super-admin');

        $rules = [
            'offer_ttl_minutes' => ['required', 'integer', 'min:'.$minMinutes, 'max:'.$maxMinutes],
            'past_pickup_grace_minutes' => ['required', 'integer', 'min:'.$minGraceMinutes, 'max:'.$maxGraceMinutes],
            'unaccepted_auto_cancel_minutes' => ['required', 'integer', 'min:'.$minAutoCancelMinutes, 'max:'.$maxAutoCancelMinutes],
            'customer_unaccepted_decision_minutes' => ['required', 'integer', 'min:'.$minDecisionMinutes, 'max:'.$maxDecisionMinutes],
            'customer_login_code_expires_minutes' => ['required', 'integer', 'min:'.$minLoginCodeMinutes, 'max:'.$maxLoginCodeMinutes],
            'booking_driver_email_enabled' => ['nullable', 'in:0,1'],
            'booking_customer_email_enabled' => ['nullable', 'in:0,1'],
            'payment_booking_enabled' => ['nullable', 'in:0,1'],
            'payment_driver_enabled' => ['nullable', 'in:0,1'],
            'customer_accept_enabled' => ['nullable', 'in:0,1'],
            'customer_accept_email_enabled' => ['nullable', 'in:0,1'],
            'customer_accept_whatsapp_enabled' => ['nullable', 'in:0,1'],
            'customer_accept_sms_enabled' => ['nullable', 'in:0,1'],
            'customer_accept_sms_provider' => ['nullable', 'string', 'in:off,demo,vonage'],
            'customer_whatsapp_status_events' => ['nullable', 'array'],
            'customer_whatsapp_status_events.*' => ['string', 'in:'.implode(',', array_keys(TaxiDispatchSettingsService::customerWhatsappStatusEventLabels()))],
            'network_enabled' => ['nullable', 'in:0,1'],
            'network_mode' => ['nullable', 'string', 'in:off,manual,auto'],
            'network_fallback_seconds' => ['nullable', 'integer', 'min:30', 'max:3600'],
            'network_max_radius_km' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
        if ($companyId !== null) {
            $rules['overdue_auto_archive_days'] = [
                'required',
                'integer',
                'min:'.$minOverdueArchiveDays,
                'max:'.$maxOverdueArchiveDays,
            ];
        }
        if ($isSuperAdmin) {
            $rules['network_manual_partner_company_ids'] = ['nullable', 'string', 'max:500'];
        }

        $validated = $request->validate($rules, [
            'offer_ttl_minutes.required' => __('Vul de acceptatietijd in.'),
            'offer_ttl_minutes.integer' => __('Acceptatietijd moet een heel getal zijn.'),
            'offer_ttl_minutes.min' => __('Acceptatietijd moet minimaal :min minuut zijn.', ['min' => $minMinutes]),
            'offer_ttl_minutes.max' => __('Acceptatietijd mag maximaal :max minuten zijn.', ['max' => $maxMinutes]),
            'past_pickup_grace_minutes.required' => __('Vul in hoe lang een verlopen ophaalmoment nog in Nieuwe ritaanvraag blijft.'),
            'past_pickup_grace_minutes.min' => __('Grace-interval moet minimaal :min minuten zijn.', ['min' => $minGraceMinutes]),
            'past_pickup_grace_minutes.max' => __('Grace-interval mag maximaal :max minuten zijn.', ['max' => $maxGraceMinutes]),
            'overdue_auto_archive_days.required' => __('Vul in na hoeveel verlopen dagen de rit automatisch gearchiveerd wordt.'),
            'overdue_auto_archive_days.min' => __('Auto-archief moet minimaal :min dagen zijn (0 = uit).', ['min' => $minOverdueArchiveDays]),
            'overdue_auto_archive_days.max' => __('Auto-archief mag maximaal :max dagen zijn.', ['max' => $maxOverdueArchiveDays]),
            'unaccepted_auto_cancel_minutes.required' => __('Vul in na hoeveel minuten de klant mag kiezen om te wachten of te annuleren.'),
            'unaccepted_auto_cancel_minutes.min' => __('Deze tijd moet minimaal :min minuten zijn (0 = uit).', ['min' => $minAutoCancelMinutes]),
            'unaccepted_auto_cancel_minutes.max' => __('Deze tijd mag maximaal :max minuten zijn.', ['max' => $maxAutoCancelMinutes]),
            'customer_unaccepted_decision_minutes.required' => __('Vul in hoe lang de klant mag reageren (wachten of annuleren).'),
            'customer_unaccepted_decision_minutes.min' => __('Reactietijd moet minimaal :min minuten zijn (0 = nooit automatisch annuleren).', ['min' => $minDecisionMinutes]),
            'customer_unaccepted_decision_minutes.max' => __('Reactietijd mag maximaal :max minuten zijn.', ['max' => $maxDecisionMinutes]),
            'customer_login_code_expires_minutes.required' => __('Vul de geldigheid van de inlogcode in.'),
            'customer_login_code_expires_minutes.min' => __('Geldigheid moet minimaal :min minuten zijn.', ['min' => $minLoginCodeMinutes]),
            'customer_login_code_expires_minutes.max' => __('Geldigheid mag maximaal :max minuten zijn.', ['max' => $maxLoginCodeMinutes]),
        ]);

        $seconds = $this->dispatchSettings->clampTtl((int) $validated['offer_ttl_minutes'] * 60);
        $this->dispatchSettings->setOfferTtlSeconds($seconds, $companyId);
        $this->dispatchSettings->setPastPickupGraceMinutes(
            (int) $validated['past_pickup_grace_minutes'],
            $companyId
        );
        if ($companyId !== null && array_key_exists('overdue_auto_archive_days', $validated)) {
            $this->dispatchSettings->setOverdueAutoArchiveDays(
                (int) $validated['overdue_auto_archive_days'],
                $companyId
            );
        }
        $this->dispatchSettings->setUnacceptedAutoCancelMinutes(
            (int) $validated['unaccepted_auto_cancel_minutes'],
            $companyId
        );
        $this->dispatchSettings->setCustomerUnacceptedDecisionMinutes(
            (int) $validated['customer_unaccepted_decision_minutes'],
            $companyId
        );
        $this->dispatchSettings->setCustomerLoginCodeExpiresMinutes(
            (int) $validated['customer_login_code_expires_minutes'],
            $companyId
        );
        $this->dispatchSettings->setBookingDriverEmailEnabled($request->boolean('booking_driver_email_enabled'), $companyId);
        $this->dispatchSettings->setBookingCustomerEmailEnabled($request->boolean('booking_customer_email_enabled'), $companyId);
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
        $this->dispatchSettings->setCustomerWhatsappStatusEvents(
            $request->input('customer_whatsapp_status_events', []),
            $companyId
        );

        $this->persistNetworkSettings($request, $validated, $companyId, $isSuperAdmin);

        $success = $companyId === null
            ? __('Nexa Suite dispatch-instellingen (marktplaats & network) zijn opgeslagen.')
            : __('Chauffeur dispatch is opgeslagen.');

        return redirect()
            ->route('admin.taxi.dispatch_settings.edit', ['saved' => 1])
            ->with('success', $success);
    }

    private function updateVisibleSections(Request $request, ?int $companyId): RedirectResponse
    {
        $user = auth()->user();
        $mutating = [];
        foreach (TaxiDispatchAccess::visibleMap($user) as $key => $visible) {
            if ($visible && TaxiDispatchAccess::canMutate($user, $key) && ! in_array($key, ['gps_vehicles', 'gps_settings'], true)) {
                $mutating[] = $key;
            }
        }
        if ($mutating === []) {
            abort(403, __('Geen rechten voor deze actie.'));
        }

        $rules = [];
        if (in_array('accept_timer', $mutating, true)) {
            $minMinutes = (int) ceil(TaxiDispatchSettingsService::MIN_TTL_SECONDS / 60);
            $maxMinutes = (int) floor(TaxiDispatchSettingsService::MAX_TTL_SECONDS / 60);
            $rules['offer_ttl_minutes'] = ['required', 'integer', 'min:'.$minMinutes, 'max:'.$maxMinutes];
            $rules['past_pickup_grace_minutes'] = ['required', 'integer', 'min:'.TaxiDispatchSettingsService::MIN_PAST_PICKUP_GRACE_MINUTES, 'max:'.TaxiDispatchSettingsService::MAX_PAST_PICKUP_GRACE_MINUTES];
            $rules['unaccepted_auto_cancel_minutes'] = ['required', 'integer', 'min:'.TaxiDispatchSettingsService::MIN_UNACCEPTED_AUTO_CANCEL_MINUTES, 'max:'.TaxiDispatchSettingsService::MAX_UNACCEPTED_AUTO_CANCEL_MINUTES];
            $rules['customer_unaccepted_decision_minutes'] = ['required', 'integer', 'min:'.TaxiDispatchSettingsService::MIN_CUSTOMER_UNACCEPTED_DECISION_MINUTES, 'max:'.TaxiDispatchSettingsService::MAX_CUSTOMER_UNACCEPTED_DECISION_MINUTES];
            if ($companyId !== null) {
                $rules['overdue_auto_archive_days'] = ['required', 'integer', 'min:'.TaxiDispatchSettingsService::MIN_OVERDUE_AUTO_ARCHIVE_DAYS, 'max:'.TaxiDispatchSettingsService::MAX_OVERDUE_AUTO_ARCHIVE_DAYS];
            }
        }
        if (in_array('customer_login', $mutating, true)) {
            $rules['customer_login_code_expires_minutes'] = ['required', 'integer', 'min:'.TaxiDispatchSettingsService::MIN_LOGIN_CODE_EXPIRES_MINUTES, 'max:'.TaxiDispatchSettingsService::MAX_LOGIN_CODE_EXPIRES_MINUTES];
        }
        if (in_array('booking_notifications', $mutating, true)) {
            $rules['booking_driver_email_enabled'] = ['nullable', 'in:0,1'];
            $rules['booking_customer_email_enabled'] = ['nullable', 'in:0,1'];
        }
        if (in_array('payments', $mutating, true)) {
            $rules['payment_booking_enabled'] = ['nullable', 'in:0,1'];
            $rules['payment_driver_enabled'] = ['nullable', 'in:0,1'];
        }
        if (in_array('customer_accept', $mutating, true)) {
            $rules['customer_accept_enabled'] = ['nullable', 'in:0,1'];
            $rules['customer_accept_email_enabled'] = ['nullable', 'in:0,1'];
            $rules['customer_accept_whatsapp_enabled'] = ['nullable', 'in:0,1'];
            $rules['customer_accept_sms_enabled'] = ['nullable', 'in:0,1'];
            $rules['customer_accept_sms_provider'] = ['nullable', 'string', 'in:off,demo,vonage'];
            $rules['customer_whatsapp_status_events'] = ['nullable', 'array'];
            $rules['customer_whatsapp_status_events.*'] = ['string', 'in:'.implode(',', array_keys(TaxiDispatchSettingsService::customerWhatsappStatusEventLabels()))];
        }
        if (in_array('network', $mutating, true)) {
            $rules['network_enabled'] = ['nullable', 'in:0,1'];
            $rules['network_mode'] = ['nullable', 'string', 'in:off,manual,auto'];
            $rules['network_fallback_seconds'] = ['nullable', 'integer', 'min:30', 'max:3600'];
            $rules['network_max_radius_km'] = ['nullable', 'integer', 'min:1', 'max:200'];
            if ($user?->hasRole('super-admin')) {
                $rules['network_manual_partner_company_ids'] = ['nullable', 'string', 'max:500'];
            }
        }

        $validated = $request->validate($rules);

        if (in_array('accept_timer', $mutating, true)) {
            $seconds = $this->dispatchSettings->clampTtl((int) $validated['offer_ttl_minutes'] * 60);
            $this->dispatchSettings->setOfferTtlSeconds($seconds, $companyId);
            $this->dispatchSettings->setPastPickupGraceMinutes((int) $validated['past_pickup_grace_minutes'], $companyId);
            if ($companyId !== null && array_key_exists('overdue_auto_archive_days', $validated)) {
                $this->dispatchSettings->setOverdueAutoArchiveDays((int) $validated['overdue_auto_archive_days'], $companyId);
            }
            $this->dispatchSettings->setUnacceptedAutoCancelMinutes((int) $validated['unaccepted_auto_cancel_minutes'], $companyId);
            $this->dispatchSettings->setCustomerUnacceptedDecisionMinutes((int) $validated['customer_unaccepted_decision_minutes'], $companyId);
        }
        if (in_array('customer_login', $mutating, true)) {
            $this->dispatchSettings->setCustomerLoginCodeExpiresMinutes((int) $validated['customer_login_code_expires_minutes'], $companyId);
        }
        if (in_array('booking_notifications', $mutating, true)) {
            $this->dispatchSettings->setBookingDriverEmailEnabled($request->boolean('booking_driver_email_enabled'), $companyId);
            $this->dispatchSettings->setBookingCustomerEmailEnabled($request->boolean('booking_customer_email_enabled'), $companyId);
        }
        if (in_array('payments', $mutating, true)) {
            $this->dispatchSettings->setPaymentBookingEnabled($request->boolean('payment_booking_enabled'), $companyId);
            $this->dispatchSettings->setPaymentDriverEnabled($request->boolean('payment_driver_enabled'), $companyId);
        }
        if (in_array('customer_accept', $mutating, true)) {
            $acceptEnabled = $request->boolean('customer_accept_enabled');
            $this->dispatchSettings->setCustomerAcceptNotificationEnabled($acceptEnabled, $companyId);
            $this->dispatchSettings->setCustomerAcceptEmailEnabled($acceptEnabled && $request->boolean('customer_accept_email_enabled'), $companyId);
            $this->dispatchSettings->setCustomerAcceptWhatsappEnabled($acceptEnabled && $request->boolean('customer_accept_whatsapp_enabled'), $companyId);
            $this->dispatchSettings->setCustomerAcceptSmsEnabled($acceptEnabled && $request->boolean('customer_accept_sms_enabled'), $companyId);
            $this->dispatchSettings->setCustomerAcceptSmsProvider(
                (string) ($validated['customer_accept_sms_provider'] ?? TaxiDispatchSettingsService::SMS_PROVIDER_OFF),
                $companyId
            );
            $this->dispatchSettings->setCustomerWhatsappStatusEvents($request->input('customer_whatsapp_status_events', []), $companyId);
        }
        if (in_array('network', $mutating, true)) {
            $this->persistNetworkSettings($request, $validated, $companyId, (bool) $user?->hasRole('super-admin'));
        }

        return redirect()
            ->route('admin.taxi.dispatch_settings.edit', ['saved' => 1])
            ->with('success', __('Chauffeur dispatch is opgeslagen.'));
    }

    private function updateMarketplaceNetworkOnly(Request $request, ?int $companyId): RedirectResponse
    {
        $isSuperAdmin = auth()->user()->hasRole('super-admin');
        $rules = [
            'network_enabled' => ['nullable', 'in:0,1'],
            'network_mode' => ['nullable', 'string', 'in:off,manual,auto'],
            'network_fallback_seconds' => ['nullable', 'integer', 'min:30', 'max:3600'],
            'network_max_radius_km' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
        if ($isSuperAdmin) {
            $rules['network_manual_partner_company_ids'] = ['nullable', 'string', 'max:500'];
        }

        $validated = $request->validate($rules);

        $mayOwn = $this->networkPartnerships->companyCanInitiateNetworkPartnerships($companyId);
        if (! $mayOwn && $request->boolean('network_enabled')) {
            return redirect()
                ->route('admin.taxi.dispatch_settings.edit')
                ->withErrors([
                    'network_enabled' => __('Marketplace heeft geen betaald maandabonnement. Schakel network niet in als owner; deel je invite-code zodat betaalde tenants jou als uitvoerder kunnen koppelen (NEXA-fee op die ritten). Een super-admin kan marketplace↔marketplace later aanzetten bij Configuraties → Nexa Suite (klant-app).'),
                ])
                ->withFragment('dispatch-nexa-network');
        }

        if (! $mayOwn) {
            $validated['network_mode'] = TaxiDispatchSettingsService::NETWORK_MODE_OFF;
            $request->merge(['network_enabled' => '0']);
        }
        $this->persistNetworkSettings($request, $validated, $companyId, $isSuperAdmin);

        return redirect()
            ->route('admin.taxi.dispatch_settings.edit', ['saved' => 1])
            ->with('success', __('NEXA Network-partnerinstellingen zijn opgeslagen.'));
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistNetworkSettings(Request $request, array $validated, ?int $companyId, bool $isSuperAdmin): void
    {
        $networkEnabled = $request->boolean('network_enabled');
        $networkMode = (string) ($validated['network_mode'] ?? TaxiDispatchSettingsService::NETWORK_MODE_OFF);
        if (! $networkEnabled) {
            $networkMode = TaxiDispatchSettingsService::NETWORK_MODE_OFF;
        }
        $this->dispatchSettings->setNetworkMode($networkMode, $companyId);
        $this->dispatchSettings->setNetworkFallbackSeconds(
            (int) ($validated['network_fallback_seconds'] ?? 120),
            $companyId
        );
        $this->dispatchSettings->setNetworkMaxRadiusKm(
            (int) ($validated['network_max_radius_km'] ?? 25),
            $companyId
        );

        if ($companyId !== null) {
            if ($isSuperAdmin) {
                $this->dispatchSettings->setNetworkManualPartnerCompanyIds(
                    (string) ($validated['network_manual_partner_company_ids'] ?? ''),
                    $companyId
                );
            }
            $this->networkPartnerships->syncOwnerPartnerIds($companyId);
        }
    }

    private function isMarketplacePackageCompany(?int $companyId): bool
    {
        if (! $companyId) {
            return false;
        }

        $company = Company::query()->find($companyId);

        return $company !== null
            && strcasecmp((string) ($company->package_key ?? ''), 'marketplace') === 0;
    }

    public function rotateNetworkInvite(Request $request): RedirectResponse
    {
        TaxiDispatchAccess::authorizeMutate('network');
        $companyId = GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null) {
            return $this->redirectNoTenant('admin.taxi.dispatch_settings.edit');
        }

        $this->networkPartnerships->rotateInviteCode($companyId, auth()->user());

        return redirect()
            ->route('admin.taxi.dispatch_settings.edit', ['saved' => 1])
            ->with('success', __('Nieuwe network invite-code aangemaakt. De oude code werkt niet meer.'))
            ->withFragment('dispatch-nexa-network');
    }

    public function updateNetworkInviteAutoAccept(Request $request): RedirectResponse
    {
        TaxiDispatchAccess::authorizeMutate('network');
        $companyId = GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null) {
            return $this->redirectNoTenant('admin.taxi.dispatch_settings.edit');
        }

        $this->networkPartnerships->setAutoAccept($companyId, $request->boolean('auto_accept'));

        return redirect()
            ->route('admin.taxi.dispatch_settings.edit', ['saved' => 1])
            ->with('success', __('Auto-accept voor invites is bijgewerkt.'))
            ->withFragment('dispatch-nexa-network');
    }

    public function redeemNetworkInvite(Request $request): RedirectResponse
    {
        TaxiDispatchAccess::authorizeMutate('network');
        $companyId = GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null) {
            return $this->redirectNoTenant('admin.taxi.dispatch_settings.edit');
        }

        if (! $this->networkPartnerships->companyCanInitiateNetworkPartnerships($companyId)) {
            return redirect()
                ->route('admin.taxi.dispatch_settings.edit')
                ->withErrors([
                    'invite_code' => __('Alleen met een betaald maandabonnement (Start, Pro of Business) kun je partners koppelen om network te gebruiken. Marketplace mag wel als uitvoerder gekoppeld worden (met NEXA-fee op die ritten).'),
                ])
                ->withInput()
                ->withFragment('dispatch-nexa-network');
        }

        $validated = $request->validate([
            'invite_code' => ['required', 'string', 'max:32'],
        ], [
            'invite_code.required' => __('Vul de invite-code van de partner in.'),
        ]);

        try {
            $result = $this->networkPartnerships->redeemInviteCode(
                $companyId,
                $validated['invite_code'],
                auth()->user()
            );
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('admin.taxi.dispatch_settings.edit')
                ->withErrors(['invite_code' => $e->getMessage()])
                ->withInput()
                ->withFragment('dispatch-nexa-network');
        }

        $message = $result['auto_accepted']
            ? __('Partner gekoppeld (auto-accept). Zij mogen nu jouw network-ritten uitvoeren als network aan staat.')
            : __('Koppelverzoek verstuurd. De partner moet het verzoek nog accepteren.');

        return redirect()
            ->route('admin.taxi.dispatch_settings.edit', ['saved' => 1])
            ->with('success', $message)
            ->withFragment('dispatch-nexa-network');
    }

    public function acceptNetworkPartnership(TaxiNetworkPartnership $partnership): RedirectResponse
    {
        return $this->actOnPartnership($partnership, 'accept');
    }

    public function declineNetworkPartnership(TaxiNetworkPartnership $partnership): RedirectResponse
    {
        return $this->actOnPartnership($partnership, 'decline');
    }

    public function revokeNetworkPartnership(TaxiNetworkPartnership $partnership): RedirectResponse
    {
        return $this->actOnPartnership($partnership, 'revoke');
    }

    private function actOnPartnership(TaxiNetworkPartnership $partnership, string $action): RedirectResponse
    {
        TaxiDispatchAccess::authorizeMutate('network');
        $companyId = GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null) {
            return $this->redirectNoTenant('admin.taxi.dispatch_settings.edit');
        }

        try {
            if ($action === 'accept') {
                $this->networkPartnerships->acceptPartnership($partnership, $companyId, auth()->user());
                $message = __('Partnerverzoek geaccepteerd.');
            } elseif ($action === 'decline') {
                $this->networkPartnerships->declinePartnership($partnership, $companyId, auth()->user());
                $message = __('Partnerverzoek afgewezen.');
            } else {
                $this->networkPartnerships->revokePartnership($partnership, $companyId, auth()->user());
                $message = __('Network-koppeling ingetrokken.');
            }
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('admin.taxi.dispatch_settings.edit')
                ->withErrors(['network_partnership' => $e->getMessage()])
                ->withFragment('dispatch-nexa-network');
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.taxi.dispatch_settings.edit')
                ->withErrors(['network_partnership' => __('Actie mislukt. Probeer het opnieuw.')])
                ->withFragment('dispatch-nexa-network');
        }

        return redirect()
            ->route('admin.taxi.dispatch_settings.edit', ['saved' => 1])
            ->with('success', $message)
            ->withFragment('dispatch-nexa-network');
    }

    /**
     * Chauffeur dispatch wordt per tenant (company_id) opgeslagen, of als Nexa Suite
     * platformdefault (company_id = null) voor marktplaats/network wanneer geen tenant
     * geselecteerd is (alleen super-admin).
     */
    private function redirectNoTenant(string $route): RedirectResponse
    {
        $message = auth()->user()?->hasRole('super-admin')
            ? __('Selecteer een tenant voor bedrijfsinstellingen, of bewerk zonder tenant de Nexa Suite-standaard (marktplaats & network).')
            : __('Geen bedrijf gekoppeld aan dit account.');

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
        abort(403, __('Geen rechten voor deze actie.'));
    }
}
