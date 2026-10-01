<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\GeneralSetting;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\EnvService;
use App\Services\PaymentProviderService;
use App\Services\WhatsAppBookingMessageComposer;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Chauffeur-dispatch instellingen (per tenant via GeneralSetting, platformdefault
 * zonder company_id voor Nexa Suite marktplaats/network, daarna config/.env).
 */
class TaxiDispatchSettingsService
{
    /** @var array<string, mixed> */
    private array $requestCache = [];

    public const KEY_OFFER_TTL_SECONDS = 'taxi_dispatch_offer_ttl_seconds';

    public const KEY_PAST_PICKUP_GRACE_HOURS = 'taxi_dispatch_past_pickup_grace_hours';

    public const KEY_PAST_PICKUP_GRACE_MINUTES = 'taxi_dispatch_past_pickup_grace_minutes';

    public const KEY_UNACCEPTED_AUTO_CANCEL_MINUTES = 'taxi_dispatch_unaccepted_auto_cancel_minutes';

    public const KEY_CUSTOMER_UNACCEPTED_DECISION_MINUTES = 'taxi_dispatch_customer_unaccepted_decision_minutes';

    public const KEY_BOOKING_WHATSAPP_ENABLED = 'taxi_dispatch_booking_whatsapp_enabled';

    public const KEY_BOOKING_WHATSAPP_NUMBER = 'taxi_dispatch_booking_whatsapp_number';

    public const KEY_BOOKING_WHATSAPP_CLICK_TO_CHAT = 'taxi_dispatch_booking_whatsapp_click_to_chat';

    public const KEY_BOOKING_DRIVER_EMAIL_ENABLED = 'taxi_dispatch_booking_driver_email_enabled';

    public const KEY_BOOKING_CUSTOMER_EMAIL_ENABLED = 'taxi_dispatch_booking_customer_email_enabled';

    public const KEY_PAYMENT_BOOKING_ENABLED = 'taxi_dispatch_payment_booking_enabled';

    public const KEY_PAYMENT_DRIVER_ENABLED = 'taxi_dispatch_payment_driver_enabled';

    public const KEY_CUSTOMER_ACCEPT_ENABLED = 'taxi_dispatch_customer_accept_enabled';

    public const KEY_CUSTOMER_ACCEPT_EMAIL_ENABLED = 'taxi_dispatch_customer_accept_email_enabled';

    public const KEY_CUSTOMER_ACCEPT_WHATSAPP_ENABLED = 'taxi_dispatch_customer_accept_whatsapp_enabled';

    public const KEY_CUSTOMER_ACCEPT_SMS_ENABLED = 'taxi_dispatch_customer_accept_sms_enabled';

    public const KEY_CUSTOMER_ACCEPT_SMS_PROVIDER = 'taxi_dispatch_customer_accept_sms_provider';

    public const KEY_CUSTOMER_ACCEPT_PLAIN_MESSAGE = 'taxi_dispatch_customer_accept_plain_message';

    public const KEY_CUSTOMER_ACCEPT_WHATSAPP_TEMPLATE = 'taxi_dispatch_customer_accept_whatsapp_template';

    public const KEY_CUSTOMER_ACCEPT_WHATSAPP_TEMPLATE_LANG = 'taxi_dispatch_customer_accept_whatsapp_template_lang';

    public const KEY_CUSTOMER_WHATSAPP_STATUS_EVENTS = 'taxi_dispatch_customer_whatsapp_status_events';

    public const KEY_CUSTOMER_LOGIN_CODE_EXPIRES_MINUTES = 'taxi_dispatch_customer_login_code_expires_minutes';

    public const KEY_NETWORK_ENABLED = 'taxi_network_enabled';

    public const KEY_NETWORK_MODE = 'taxi_network_mode';

    public const KEY_NETWORK_FALLBACK_SECONDS = 'taxi_network_fallback_seconds';

    public const KEY_NETWORK_MAX_RADIUS_KM = 'taxi_network_max_radius_km';

    public const KEY_NETWORK_PARTNER_COMPANY_IDS = 'taxi_network_partner_company_ids';

    /** Super-admin handmatige IDs (gemerged met accepted invite-partnerships). */
    public const KEY_NETWORK_MANUAL_PARTNER_COMPANY_IDS = 'taxi_network_manual_partner_company_ids';

    public const NETWORK_MODE_OFF = 'off';

    public const NETWORK_MODE_MANUAL = 'manual';

    public const NETWORK_MODE_AUTO = 'auto';

    public const MIN_LOGIN_CODE_EXPIRES_MINUTES = 5;

    public const MAX_LOGIN_CODE_EXPIRES_MINUTES = 1440;

    public const SMS_PROVIDER_OFF = 'off';

    public const SMS_PROVIDER_DEMO = 'demo';

    public const SMS_PROVIDER_VONAGE = 'vonage';

    public const MIN_TTL_SECONDS = 15;

    public const MAX_TTL_SECONDS = 3600;

    public const MIN_PAST_PICKUP_GRACE_HOURS = 0;

    public const MAX_PAST_PICKUP_GRACE_HOURS = 72;

    public const MIN_PAST_PICKUP_GRACE_MINUTES = 0;

    public const MAX_PAST_PICKUP_GRACE_MINUTES = 4320; // 72 uur

    /** 0 = automatische annulering uit */
    public const MIN_UNACCEPTED_AUTO_CANCEL_MINUTES = 0;

    public const MAX_UNACCEPTED_AUTO_CANCEL_MINUTES = 1440; // 24 uur

    /** 0 = geen auto-annulering na de klantprompt */
    public const MIN_CUSTOMER_UNACCEPTED_DECISION_MINUTES = 0;

    public const MAX_CUSTOMER_UNACCEPTED_DECISION_MINUTES = 180;

    public function __construct(
        protected EnvService $env,
        protected PaymentProviderService $paymentProviders
    ) {}

    public function offerTtlSeconds(?int $companyId = null): int
    {
        $cacheKey = 'ttl:'.(int) ($companyId ?? 0);
        if (array_key_exists($cacheKey, $this->requestCache)) {
            return (int) $this->requestCache[$cacheKey];
        }

        $default = (int) config('taxi-dispatch.offer_ttl_seconds', 300);
        $raw = GeneralSetting::get(self::KEY_OFFER_TTL_SECONDS, null, $companyId);

        $ttl = ($raw === null || $raw === '')
            ? $this->clampTtl($default)
            : $this->clampTtl((int) $raw);

        return $this->requestCache[$cacheKey] = $ttl;
    }

    public function setOfferTtlSeconds(int $seconds, ?int $companyId = null): void
    {
        GeneralSetting::set(
            self::KEY_OFFER_TTL_SECONDS,
            (string) $this->clampTtl($seconds),
            $companyId
        );
    }

    public function clampTtl(int $seconds): int
    {
        return max(self::MIN_TTL_SECONDS, min(self::MAX_TTL_SECONDS, $seconds));
    }

    public function pastPickupGraceMinutes(?int $companyId = null): int
    {
        $default = (int) config('taxi-dispatch.past_pickup_grace_minutes', 60);
        $rawMinutes = GeneralSetting::get(self::KEY_PAST_PICKUP_GRACE_MINUTES, null, $companyId);
        if ($rawMinutes !== null && $rawMinutes !== '') {
            return $this->clampPastPickupGraceMinutes((int) $rawMinutes);
        }

        $rawHours = GeneralSetting::get(self::KEY_PAST_PICKUP_GRACE_HOURS, null, $companyId);
        if ($rawHours !== null && $rawHours !== '') {
            return $this->clampPastPickupGraceMinutes(((int) $rawHours) * 60);
        }

        return $this->clampPastPickupGraceMinutes($default);
    }

    public function setPastPickupGraceMinutes(int $minutes, ?int $companyId = null): void
    {
        GeneralSetting::set(
            self::KEY_PAST_PICKUP_GRACE_MINUTES,
            (string) $this->clampPastPickupGraceMinutes($minutes),
            $companyId
        );
    }

    /** @deprecated Gebruik pastPickupGraceMinutes() */
    public function pastPickupGraceHours(?int $companyId = null): int
    {
        return (int) round($this->pastPickupGraceMinutes($companyId) / 60);
    }

    /** @deprecated Gebruik setPastPickupGraceMinutes() */
    public function setPastPickupGraceHours(int $hours, ?int $companyId = null): void
    {
        $this->setPastPickupGraceMinutes($this->clampPastPickupGraceHours($hours) * 60, $companyId);
    }

    public function clampPastPickupGraceHours(int $hours): int
    {
        return max(self::MIN_PAST_PICKUP_GRACE_HOURS, min(self::MAX_PAST_PICKUP_GRACE_HOURS, $hours));
    }

    public function clampPastPickupGraceMinutes(int $minutes): int
    {
        return max(self::MIN_PAST_PICKUP_GRACE_MINUTES, min(self::MAX_PAST_PICKUP_GRACE_MINUTES, $minutes));
    }

    public function unacceptedAutoCancelMinutes(?int $companyId = null): int
    {
        $default = (int) config('taxi-dispatch.unaccepted_auto_cancel_minutes', 30);
        $raw = GeneralSetting::get(self::KEY_UNACCEPTED_AUTO_CANCEL_MINUTES, null, $companyId);
        if ($raw === null || $raw === '') {
            return $this->clampUnacceptedAutoCancelMinutes($default);
        }

        return $this->clampUnacceptedAutoCancelMinutes((int) $raw);
    }

    /**
     * Eerste tenant-override in de lijst; anders serverdefault.
     *
     * @param  list<int>  $companyIds
     */
    public function unacceptedAutoCancelMinutesForCompanies(array $companyIds, ?int $fallbackCompanyId = null): int
    {
        foreach ($companyIds as $cid) {
            $cid = (int) $cid;
            if ($cid <= 0) {
                continue;
            }
            $raw = $this->companyScopedSetting(self::KEY_UNACCEPTED_AUTO_CANCEL_MINUTES, $cid);
            if ($raw !== null && $raw !== '') {
                return $this->clampUnacceptedAutoCancelMinutes((int) $raw);
            }
        }

        return $this->unacceptedAutoCancelMinutes($fallbackCompanyId);
    }

    /**
     * Minuten voor klantkeuze-prompt, met marketplace-snapshot / kandidaten.
     */
    public function unacceptedAutoCancelMinutesForRide(RideRequest $ride, ?int $companyId = null): int
    {
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $snap = $payload['dispatch_timers']['unaccepted_auto_cancel_minutes'] ?? null;
        if ($snap !== null && $snap !== '') {
            return $this->clampUnacceptedAutoCancelMinutes((int) $snap);
        }

        return $this->unacceptedAutoCancelMinutesForCompanies(
            $this->dispatchSettingsCompanyIdsForRide($ride, $companyId),
            $companyId
        );
    }

    public function customerUnacceptedDecisionMinutes(?int $companyId = null): int
    {
        $default = (int) config('taxi-dispatch.customer_unaccepted_decision_minutes', 30);
        $raw = GeneralSetting::get(self::KEY_CUSTOMER_UNACCEPTED_DECISION_MINUTES, null, $companyId);
        if ($raw === null || $raw === '') {
            return $this->clampCustomerUnacceptedDecisionMinutes($default);
        }

        return $this->clampCustomerUnacceptedDecisionMinutes((int) $raw);
    }

    /**
     * @param  list<int>  $companyIds
     */
    public function customerUnacceptedDecisionMinutesForCompanies(array $companyIds, ?int $fallbackCompanyId = null): int
    {
        foreach ($companyIds as $cid) {
            $cid = (int) $cid;
            if ($cid <= 0) {
                continue;
            }
            $raw = $this->companyScopedSetting(self::KEY_CUSTOMER_UNACCEPTED_DECISION_MINUTES, $cid);
            if ($raw !== null && $raw !== '') {
                return $this->clampCustomerUnacceptedDecisionMinutes((int) $raw);
            }
        }

        return $this->customerUnacceptedDecisionMinutes($fallbackCompanyId);
    }

    public function customerUnacceptedDecisionMinutesForRide(RideRequest $ride, ?int $companyId = null): int
    {
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $snap = $payload['dispatch_timers']['customer_unaccepted_decision_minutes'] ?? null;
        if ($snap !== null && $snap !== '') {
            return $this->clampCustomerUnacceptedDecisionMinutes((int) $snap);
        }

        return $this->customerUnacceptedDecisionMinutesForCompanies(
            $this->dispatchSettingsCompanyIdsForRide($ride, $companyId),
            $companyId
        );
    }

    /**
     * Alleen tenant-rij (geen platform-fallback), zodat we echte tenant-overrides vinden.
     */
    protected function companyScopedSetting(string $key, int $companyId): mixed
    {
        if ($companyId <= 0) {
            return null;
        }

        try {
            $setting = GeneralSetting::query()
                ->where('key', $key)
                ->where('company_id', $companyId)
                ->first();

            return $setting?->value;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<int>
     */
    protected function dispatchSettingsCompanyIdsForRide(RideRequest $ride, ?int $preferredCompanyId = null): array
    {
        $ids = [];
        $push = static function (int $id) use (&$ids): void {
            if ($id > 0 && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        };

        $push((int) ($preferredCompanyId ?? 0));
        $push((int) ($ride->fulfilling_company_id ?? 0));
        $push((int) ($ride->company_id ?? 0));

        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $marketplace = is_array($payload['marketplace'] ?? null) ? $payload['marketplace'] : [];
        $push((int) ($marketplace['settings_company_id'] ?? 0));
        $push((int) ($marketplace['company_id'] ?? 0));
        // Geen candidate_company_ids: timers komen van de settings-tenant of de
        // Nexa Suite-platformdefault (company_id = null), niet van een willekeurige kandidaat.

        return $ids;
    }

    public function setUnacceptedAutoCancelMinutes(int $minutes, ?int $companyId = null): void
    {
        GeneralSetting::set(
            self::KEY_UNACCEPTED_AUTO_CANCEL_MINUTES,
            (string) $this->clampUnacceptedAutoCancelMinutes($minutes),
            $companyId
        );
    }

    public function clampUnacceptedAutoCancelMinutes(int $minutes): int
    {
        return max(
            self::MIN_UNACCEPTED_AUTO_CANCEL_MINUTES,
            min(self::MAX_UNACCEPTED_AUTO_CANCEL_MINUTES, $minutes)
        );
    }

    public function setCustomerUnacceptedDecisionMinutes(int $minutes, ?int $companyId = null): void
    {
        GeneralSetting::set(
            self::KEY_CUSTOMER_UNACCEPTED_DECISION_MINUTES,
            (string) $this->clampCustomerUnacceptedDecisionMinutes($minutes),
            $companyId
        );
    }

    public function clampCustomerUnacceptedDecisionMinutes(int $minutes): int
    {
        return max(
            self::MIN_CUSTOMER_UNACCEPTED_DECISION_MINUTES,
            min(self::MAX_CUSTOMER_UNACCEPTED_DECISION_MINUTES, $minutes)
        );
    }

    /**
     * Ophaalmoment dat geldt voor grace / auto-annuleren.
     * Bij een openstaand voorstel van de chauffeur telt de nieuwe tijd, niet de oude.
     */
    public function effectiveDispatchDueAt(RideRequest $ride): ?CarbonInterface
    {
        $connection = $ride->getConnectionName();
        if (is_string($connection) && $connection !== '') {
            TaxiDispatchSchema::ensurePickupProposalColumns($connection);
        }

        if ($ride->pickup_proposal_status === RideRequest::PICKUP_PROPOSAL_PENDING
            && $ride->pickup_proposal_at) {
            return ContractTransportTimezone::asAmsterdamWall($ride->pickup_proposal_at);
        }

        return $this->scheduledRideDueAt($ride);
    }

    /**
     * Moment waarop een niet-geaccepteerde rit automatisch mag worden geannuleerd.
     * Null = auto-annulering uit of geen ophaalmoment.
     */
    public function unacceptedAutoCancelAt(RideRequest $ride, ?int $companyId = null): ?CarbonInterface
    {
        $companyId = $companyId ?? ((int) ($ride->company_id ?? 0) > 0 ? (int) $ride->company_id : null);
        $minutes = $this->unacceptedAutoCancelMinutesForRide($ride, $companyId);
        if ($minutes <= 0) {
            return null;
        }

        $dueAt = $this->effectiveDispatchDueAt($ride);
        if (! $dueAt) {
            return null;
        }

        return $dueAt->copy()->addMinutes($minutes);
    }

    /**
     * Ritten met pickup_at vóór dit moment vallen uit de chauffeur-wachtrij.
     * Binding is naïef UTC met Amsterdam-wallclock-cijfers (matcht DB-opslag).
     */
    public function pickupQueueCutoffAt(?int $companyId = null, ?CarbonInterface $now = null): CarbonInterface
    {
        $minutes = $this->pastPickupGraceMinutes($companyId);
        $base = $now
            ? Carbon::parse($now)->timezone(ContractTransportTimezone::TIMEZONE)
            : now(ContractTransportTimezone::TIMEZONE);

        return ContractTransportTimezone::naiveUtcForWallClockQuery(
            $base->copy()->subMinutes($minutes)
        );
    }

    /**
     * Openstaande aanvraag: ophaalmoment is voorbij (Amsterdam wall-clock).
     */
    public function offerPickupIsPast(RideRequest $ride, ?CarbonInterface $now = null): bool
    {
        $dueAt = $this->effectiveDispatchDueAt($ride);
        if (! $dueAt) {
            return false;
        }

        $base = $now
            ? Carbon::parse($now)->timezone(ContractTransportTimezone::TIMEZONE)
            : now(ContractTransportTimezone::TIMEZONE);

        return $dueAt->lte($base);
    }

    /**
     * Geaccepteerde rit is verlopen: ophaalmoment + acceptatietijd is voorbij zonder start.
     */
    public function scheduledRideIsOverdue(RideRequest $ride, ?int $companyId = null, ?CarbonInterface $now = null): bool
    {
        $companyId = $companyId ?? (int) ($ride->company_id ?? 0);
        $ttl = $this->offerTtlSeconds($companyId > 0 ? $companyId : null);
        $base = $now
            ? Carbon::parse($now)->timezone(ContractTransportTimezone::TIMEZONE)
            : now(ContractTransportTimezone::TIMEZONE);
        $dueAt = $this->effectiveDispatchDueAt($ride);

        if (! $dueAt) {
            return false;
        }

        return $dueAt->copy()->addSeconds($ttl)->lte($base);
    }

    private function scheduledRideDueAt(RideRequest $ride): ?CarbonInterface
    {
        if ($ride->isContractRide()) {
            $schedule = app(ContractOccurrenceGeneratorService::class)
                ->schedulePayloadForRide($ride->getConnectionName(), $ride);

            if (! empty($schedule['destination_arrival_at'])) {
                return Carbon::parse($schedule['destination_arrival_at'])->timezone(ContractTransportTimezone::TIMEZONE);
            }

            if (! empty($schedule['departure_at'])) {
                return Carbon::parse($schedule['departure_at'])->timezone(ContractTransportTimezone::TIMEZONE);
            }
        }

        if ($ride->isReturnTrip() && $ride->hasOutboundCompleted()) {
            return ContractTransportTimezone::asAmsterdamWall($ride->effectivePickupAt());
        }

        return ContractTransportTimezone::asAmsterdamWall($ride->pickup_at);
    }

    public function bookingWhatsappEnabled(?int $companyId = null): bool
    {
        // Token + phone number ID gezet → altijd API-verzending (geen wa.me-popup).
        if ($this->whatsappApiConfigured($companyId)) {
            return true;
        }

        $stored = GeneralSetting::get(self::KEY_BOOKING_WHATSAPP_ENABLED, null, $companyId);
        if ($stored !== null && $stored !== '') {
            return $stored === '1';
        }

        return $this->defaultBookingWhatsappEnabled();
    }

    public function setBookingWhatsappEnabled(bool $enabled, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_BOOKING_WHATSAPP_ENABLED, $enabled ? '1' : '0', $companyId);
    }

    public function bookingWhatsappNumber(?int $companyId = null): string
    {
        // Click-to-chat / wa.me: WHATSAPP_CLICK_TO_CHAT_NUMBER (of widget-nummer).
        return $this->envFallbackWhatsappNumber($companyId);
    }

    public function setBookingWhatsappNumber(string $number, ?int $companyId = null): void
    {
        // Legacy no-op: nummer hoort bij WHATSAPP_CLICK_TO_CHAT_NUMBER.
    }

    /**
     * Platform-schakelaar: WhatsApp naar het bedrijf bij elke boeking.
     */
    public function companyBookingWhatsappNotifyEnabled(?int $companyId = null): bool
    {
        return GeneralSetting::get('WHATSAPP_COMPANY_BOOKING_NOTIFY_ENABLED', '0') === '1';
    }

    /**
     * Tenant-nummer voor bedrijfsboekingsmeldingen (leeg = niet versturen).
     */
    public function companyBookingWhatsappNotifyNumber(?int $companyId = null): string
    {
        return trim((string) $this->env->get('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER', '', $companyId));
    }

    public function bookingWhatsappClickToChatEnabled(?int $companyId = null): bool
    {
        // Cloud API-token aanwezig → nooit click-to-chat / wa.me popup.
        if ($this->whatsappApiConfigured($companyId) || $this->whatsappApiTokenPresent($companyId)) {
            return false;
        }

        return $this->clickToChatMasterEnabled($companyId);
    }

    public function whatsappApiTokenPresent(?int $companyId = null): bool
    {
        return app(\App\Services\WhatsAppBusinessService::class)->hasApiToken($companyId);
    }

    public function setBookingWhatsappClickToChatEnabled(bool $enabled, ?int $companyId = null): void
    {
        // Legacy no-op: schakelaar staat onder WHATSAPP_CLICK_TO_CHAT_ENABLED.
    }

    public function bookingDriverEmailEnabled(?int $companyId = null): bool
    {
        $stored = GeneralSetting::get(self::KEY_BOOKING_DRIVER_EMAIL_ENABLED, null, $companyId);
        if ($stored !== null && $stored !== '') {
            return $stored === '1';
        }

        return true;
    }

    public function setBookingDriverEmailEnabled(bool $enabled, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_BOOKING_DRIVER_EMAIL_ENABLED, $enabled ? '1' : '0', $companyId);
    }

    public function bookingCustomerEmailEnabled(?int $companyId = null): bool
    {
        $stored = GeneralSetting::get(self::KEY_BOOKING_CUSTOMER_EMAIL_ENABLED, null, $companyId);
        if ($stored !== null && $stored !== '') {
            return $stored === '1';
        }

        return true;
    }

    public function setBookingCustomerEmailEnabled(bool $enabled, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_BOOKING_CUSTOMER_EMAIL_ENABLED, $enabled ? '1' : '0', $companyId);
    }

    public function customerEmailRequiredForBooking(?int $companyId = null): bool
    {
        return $this->bookingCustomerEmailEnabled($companyId)
            || $this->customerAcceptEmailEnabled($companyId);
    }

    public function defaultBookingWhatsappEnabled(): bool
    {
        return false;
    }

    /**
     * Admin-instelling: WhatsApp Direct / click-to-chat (Instellingen → WhatsApp).
     */
    private function clickToChatMasterEnabled(?int $companyId = null): bool
    {
        return GeneralSetting::get('WHATSAPP_CLICK_TO_CHAT_ENABLED', '0', $companyId) === '1';
    }

    public function envFallbackWhatsappNumber(?int $companyId = null): string
    {
        $number = trim((string) $this->env->get('WHATSAPP_CLICK_TO_CHAT_NUMBER', '', $companyId));
        if ($number !== '') {
            return $number;
        }

        return trim((string) $this->env->get('WHATSAPP_WIDGET_PHONE', '', $companyId));
    }

    public function paymentBookingEnabled(?int $companyId = null): bool
    {
        $stored = GeneralSetting::get(self::KEY_PAYMENT_BOOKING_ENABLED, null, $companyId);
        if ($stored !== null && $stored !== '') {
            return $stored === '1';
        }

        return false;
    }

    public function setPaymentBookingEnabled(bool $enabled, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_PAYMENT_BOOKING_ENABLED, $enabled ? '1' : '0', $companyId);
    }

    public function paymentDriverEnabled(?int $companyId = null): bool
    {
        $stored = GeneralSetting::get(self::KEY_PAYMENT_DRIVER_ENABLED, null, $companyId);
        if ($stored !== null && $stored !== '') {
            return $stored === '1';
        }

        return false;
    }

    public function setPaymentDriverEnabled(bool $enabled, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_PAYMENT_DRIVER_ENABLED, $enabled ? '1' : '0', $companyId);
    }

    public function hasMollieConfigured(?int $companyId = null): bool
    {
        return $this->paymentProviders->isMollieConfiguredForCompany($companyId);
    }

    /**
     * @return array{booking: bool, driver: bool, cash: bool, mollie_configured: bool, mollie_package_allowed: bool}
     */
    public function paymentOptionsForTenant(?int $companyId = null): array
    {
        $mollieAllowed = app(\App\Services\CompanyEntitlementService::class)
            ->allowsCompanyId($companyId, \App\Support\TenantPackageCapability::MOLLIE_PAYMENTS);
        $mollieConfigured = $mollieAllowed && $this->hasMollieConfigured($companyId);

        return [
            'booking' => $mollieConfigured && $this->paymentBookingEnabled($companyId),
            'driver' => $mollieAllowed && $this->paymentDriverEnabled($companyId),
            'cash' => true,
            'mollie_configured' => $mollieConfigured,
            'mollie_package_allowed' => $mollieAllowed,
        ];
    }

    public function customerAcceptNotificationEnabled(?int $companyId = null): bool
    {
        $stored = GeneralSetting::get(self::KEY_CUSTOMER_ACCEPT_ENABLED, null, $companyId);
        if ($stored !== null && $stored !== '') {
            return $stored === '1';
        }

        return true;
    }

    public function setCustomerAcceptNotificationEnabled(bool $enabled, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_CUSTOMER_ACCEPT_ENABLED, $enabled ? '1' : '0', $companyId);
    }

    public function customerAcceptEmailEnabled(?int $companyId = null): bool
    {
        if (! $this->customerAcceptNotificationEnabled($companyId)) {
            return false;
        }
        $stored = GeneralSetting::get(self::KEY_CUSTOMER_ACCEPT_EMAIL_ENABLED, null, $companyId);

        return $stored === null || $stored === '' || $stored === '1';
    }

    public function setCustomerAcceptEmailEnabled(bool $enabled, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_CUSTOMER_ACCEPT_EMAIL_ENABLED, $enabled ? '1' : '0', $companyId);
    }

    public function customerAcceptWhatsappEnabled(?int $companyId = null): bool
    {
        if (! $this->customerAcceptNotificationEnabled($companyId)) {
            return false;
        }

        // Token gezet → klant altijd via Cloud API informeren (o.a. rit geaccepteerd).
        if ($this->whatsappApiConfigured($companyId)) {
            return true;
        }

        $stored = GeneralSetting::get(self::KEY_CUSTOMER_ACCEPT_WHATSAPP_ENABLED, null, $companyId);

        return $stored === '1';
    }

    public function whatsappApiConfigured(?int $companyId = null): bool
    {
        return app(\App\Services\WhatsAppBusinessService::class)->isConfigured($companyId);
    }

    public function setCustomerAcceptWhatsappEnabled(bool $enabled, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_CUSTOMER_ACCEPT_WHATSAPP_ENABLED, $enabled ? '1' : '0', $companyId);
    }

    public function customerAcceptSmsEnabled(?int $companyId = null): bool
    {
        if (! $this->customerAcceptNotificationEnabled($companyId)) {
            return false;
        }
        $stored = GeneralSetting::get(self::KEY_CUSTOMER_ACCEPT_SMS_ENABLED, null, $companyId);

        return $stored === '1';
    }

    public function setCustomerAcceptSmsEnabled(bool $enabled, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_CUSTOMER_ACCEPT_SMS_ENABLED, $enabled ? '1' : '0', $companyId);
    }

    public function customerAcceptSmsProvider(?int $companyId = null): string
    {
        $stored = trim((string) GeneralSetting::get(self::KEY_CUSTOMER_ACCEPT_SMS_PROVIDER, null, $companyId));
        if (in_array($stored, [self::SMS_PROVIDER_DEMO, self::SMS_PROVIDER_VONAGE], true)) {
            return $stored;
        }

        return self::SMS_PROVIDER_OFF;
    }

    public function setCustomerAcceptSmsProvider(string $provider, ?int $companyId = null): void
    {
        $provider = in_array($provider, [self::SMS_PROVIDER_OFF, self::SMS_PROVIDER_DEMO, self::SMS_PROVIDER_VONAGE], true)
            ? $provider
            : self::SMS_PROVIDER_OFF;
        GeneralSetting::set(self::KEY_CUSTOMER_ACCEPT_SMS_PROVIDER, $provider, $companyId);
    }

    public function customerAcceptPlainMessage(?int $companyId = null): string
    {
        $stored = trim((string) GeneralSetting::get(self::KEY_CUSTOMER_ACCEPT_PLAIN_MESSAGE, null, $companyId));
        if ($stored !== '') {
            return $stored;
        }

        return "Beste {{CUSTOMER_NAME}},\n\n"
            ."Uw taxirit is geaccepteerd.\n"
            ."Chauffeur: {{DRIVER_NAME}}\n"
            ."Ophaalmoment: {{PICKUP_AT}}\n"
            ."Ophalen: {{PICKUP_ADDRESS}}\n"
            ."Afzetten: {{DROPOFF_ADDRESS}}\n\n"
            ."Met vriendelijke groet,\n{{COMPANY_NAME}}";
    }

    public function setCustomerAcceptPlainMessage(string $message, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_CUSTOMER_ACCEPT_PLAIN_MESSAGE, trim($message), $companyId);
    }

    public function customerAcceptWhatsappTemplateName(?int $companyId = null): string
    {
        return trim((string) GeneralSetting::get(self::KEY_CUSTOMER_ACCEPT_WHATSAPP_TEMPLATE, null, $companyId));
    }

    public function setCustomerAcceptWhatsappTemplateName(string $name, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_CUSTOMER_ACCEPT_WHATSAPP_TEMPLATE, trim($name), $companyId);
    }

    public function customerAcceptWhatsappTemplateLanguage(?int $companyId = null): string
    {
        $lang = trim((string) GeneralSetting::get(self::KEY_CUSTOMER_ACCEPT_WHATSAPP_TEMPLATE_LANG, null, $companyId));

        return $lang !== '' ? $lang : 'nl';
    }

    public function setCustomerAcceptWhatsappTemplateLanguage(string $language, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_CUSTOMER_ACCEPT_WHATSAPP_TEMPLATE_LANG, trim($language) ?: 'nl', $companyId);
    }

    /**
     * @return array<string, string> event => NL-label
     */
    public static function customerWhatsappStatusEventLabels(): array
    {
        return WhatsAppBookingMessageComposer::statusEventLabels();
    }

    /**
     * WhatsApp-statusberichten naar de klant. Tenant-instelling gaat voor; anders platform/default.
     * Rit afgerond staat standaard uit.
     *
     * @return list<string>
     */
    public function customerWhatsappStatusEvents(?int $companyId = null): array
    {
        $stored = GeneralSetting::get(self::KEY_CUSTOMER_WHATSAPP_STATUS_EVENTS, null, $companyId);
        if ($stored !== null && $stored !== '') {
            return $this->normalizeCustomerWhatsappStatusEvents($stored, allowEmpty: true);
        }

        $fallback = $this->normalizeCustomerWhatsappStatusEvents(
            app(WhatsAppBookingMessageComposer::class)->selectedStatusEvents(),
            allowEmpty: false
        );

        return array_values(array_filter(
            $fallback,
            fn (string $event): bool => $event !== WhatsAppBookingMessageComposer::EVENT_COMPLETED
        ));
    }

    public function customerWhatsappStatusEventEnabled(string $event, ?int $companyId = null): bool
    {
        return in_array($event, $this->customerWhatsappStatusEvents($companyId), true);
    }

    /**
     * @param  list<string>|string  $events
     */
    public function setCustomerWhatsappStatusEvents(array|string $events, ?int $companyId = null): void
    {
        $normalized = $this->normalizeCustomerWhatsappStatusEvents($events, allowEmpty: true);
        GeneralSetting::set(
            self::KEY_CUSTOMER_WHATSAPP_STATUS_EVENTS,
            json_encode(array_values($normalized), JSON_UNESCAPED_UNICODE),
            $companyId
        );
    }

    /**
     * @param  mixed  $raw
     * @return list<string>
     */
    public function normalizeCustomerWhatsappStatusEvents(mixed $raw, bool $allowEmpty = false): array
    {
        $available = array_keys(self::customerWhatsappStatusEventLabels());
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                $decoded = preg_split('/\s*,\s*/', $raw) ?: [];
            }
            $raw = $decoded;
        }
        if (! is_array($raw)) {
            $raw = [];
        }

        $events = [];
        foreach ($raw as $key) {
            $key = is_string($key) ? trim($key) : '';
            if ($key !== '' && in_array($key, $available, true)) {
                $events[] = $key;
            }
        }
        $events = array_values(array_unique($events));

        if ($events === [] && ! $allowEmpty) {
            return WhatsAppBookingMessageComposer::defaultStatusEvents();
        }

        return $events;
    }

    /**
     * @return list<string>
     */
    public static function smsProviderOptions(): array
    {
        return [
            self::SMS_PROVIDER_OFF,
            self::SMS_PROVIDER_DEMO,
            self::SMS_PROVIDER_VONAGE,
        ];
    }

    public static function smsProviderLabel(string $provider): string
    {
        return match ($provider) {
            self::SMS_PROVIDER_DEMO => 'Demo (alleen log, gratis)',
            self::SMS_PROVIDER_VONAGE => 'Vonage (betaald, via server .env)',
            default => 'Uit',
        };
    }

    public function customerLoginCodeExpiresMinutes(?int $companyId = null): int
    {
        $default = (int) config('taxi-dispatch.customer_login_code_expires_minutes', 15);
        $raw = GeneralSetting::get(self::KEY_CUSTOMER_LOGIN_CODE_EXPIRES_MINUTES, null, $companyId);

        if ($raw === null || $raw === '') {
            return $this->clampLoginCodeExpiresMinutes($default);
        }

        return $this->clampLoginCodeExpiresMinutes((int) $raw);
    }

    public function setCustomerLoginCodeExpiresMinutes(int $minutes, ?int $companyId = null): void
    {
        GeneralSetting::set(
            self::KEY_CUSTOMER_LOGIN_CODE_EXPIRES_MINUTES,
            (string) $this->clampLoginCodeExpiresMinutes($minutes),
            $companyId
        );
    }

    public function clampLoginCodeExpiresMinutes(int $minutes): int
    {
        return max(self::MIN_LOGIN_CODE_EXPIRES_MINUTES, min(self::MAX_LOGIN_CODE_EXPIRES_MINUTES, $minutes));
    }

    /**
     * Network is deny-by-default: unset / false = off.
     */
    public function networkEnabled(?int $companyId = null): bool
    {
        $stored = GeneralSetting::get(self::KEY_NETWORK_ENABLED, null, $companyId);
        if ($stored === null || $stored === '') {
            return (bool) config('taxi-dispatch.network_enabled', false);
        }

        return filter_var($stored, FILTER_VALIDATE_BOOL);
    }

    public function setNetworkEnabled(bool $enabled, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_NETWORK_ENABLED, $enabled ? '1' : '0', $companyId);
    }

    public function networkMode(?int $companyId = null): string
    {
        if (! $this->networkEnabled($companyId)) {
            return self::NETWORK_MODE_OFF;
        }

        $stored = strtolower(trim((string) GeneralSetting::get(self::KEY_NETWORK_MODE, null, $companyId)));
        if (in_array($stored, [self::NETWORK_MODE_MANUAL, self::NETWORK_MODE_AUTO], true)) {
            return $stored;
        }

        $fallback = strtolower((string) config('taxi-dispatch.network_mode', self::NETWORK_MODE_OFF));

        return in_array($fallback, [self::NETWORK_MODE_MANUAL, self::NETWORK_MODE_AUTO], true)
            ? $fallback
            : self::NETWORK_MODE_OFF;
    }

    public function setNetworkMode(string $mode, ?int $companyId = null): void
    {
        $mode = strtolower(trim($mode));
        if (! in_array($mode, [self::NETWORK_MODE_OFF, self::NETWORK_MODE_MANUAL, self::NETWORK_MODE_AUTO], true)) {
            $mode = self::NETWORK_MODE_OFF;
        }
        GeneralSetting::set(self::KEY_NETWORK_MODE, $mode, $companyId);
        if ($mode === self::NETWORK_MODE_OFF) {
            $this->setNetworkEnabled(false, $companyId);
        } else {
            $this->setNetworkEnabled(true, $companyId);
        }
    }

    public function networkFallbackSeconds(?int $companyId = null): int
    {
        $default = (int) config('taxi-dispatch.network_fallback_seconds', 120);
        $raw = GeneralSetting::get(self::KEY_NETWORK_FALLBACK_SECONDS, null, $companyId);
        $seconds = ($raw === null || $raw === '') ? $default : (int) $raw;

        return max(30, min(3600, $seconds));
    }

    public function setNetworkFallbackSeconds(int $seconds, ?int $companyId = null): void
    {
        GeneralSetting::set(
            self::KEY_NETWORK_FALLBACK_SECONDS,
            (string) max(30, min(3600, $seconds)),
            $companyId
        );
    }

    public function networkMaxRadiusKm(?int $companyId = null): int
    {
        $default = (int) config('taxi-dispatch.network_max_radius_km', 25);
        $raw = GeneralSetting::get(self::KEY_NETWORK_MAX_RADIUS_KM, null, $companyId);
        $km = ($raw === null || $raw === '') ? $default : (int) $raw;

        return max(1, min(200, $km));
    }

    public function setNetworkMaxRadiusKm(int $km, ?int $companyId = null): void
    {
        GeneralSetting::set(
            self::KEY_NETWORK_MAX_RADIUS_KM,
            (string) max(1, min(200, $km)),
            $companyId
        );
    }

    /**
     * Partner tenant IDs allowed to fulfil this owner's network rides.
     * Effective list = accepted invite-partnerships + optional super-admin manual IDs.
     *
     * @return list<int>
     */
    public function networkPartnerCompanyIds(?int $companyId = null): array
    {
        return $this->parsePartnerIdList(
            GeneralSetting::get(self::KEY_NETWORK_PARTNER_COMPANY_IDS, null, $companyId)
        );
    }

    /**
     * Super-admin-only manual partner IDs (not visible as a directory to tenants).
     *
     * @return list<int>
     */
    public function networkManualPartnerCompanyIds(?int $companyId = null): array
    {
        return $this->parsePartnerIdList(
            GeneralSetting::get(self::KEY_NETWORK_MANUAL_PARTNER_COMPANY_IDS, null, $companyId)
        );
    }

    /**
     * @param  list<int|string>|string  $ids
     */
    public function setNetworkManualPartnerCompanyIds(array|string $ids, ?int $companyId = null): void
    {
        $clean = $this->normalizePartnerIdList($ids);

        GeneralSetting::set(
            self::KEY_NETWORK_MANUAL_PARTNER_COMPANY_IDS,
            json_encode($clean, JSON_THROW_ON_ERROR),
            $companyId
        );
    }

    /**
     * @param  list<int|string>|string  $ids
     */
    public function setNetworkPartnerCompanyIds(array|string $ids, ?int $companyId = null): void
    {
        $clean = $this->normalizePartnerIdList($ids);

        GeneralSetting::set(
            self::KEY_NETWORK_PARTNER_COMPANY_IDS,
            json_encode($clean, JSON_THROW_ON_ERROR),
            $companyId
        );
    }

    /**
     * @return list<int>
     */
    private function parsePartnerIdList(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_array($raw)) {
            $ids = $raw;
        } else {
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $ids = $decoded;
            } else {
                $ids = preg_split('/[\s,;]+/', (string) $raw) ?: [];
            }
        }

        return $this->normalizePartnerIdList($ids);
    }

    /**
     * @param  list<int|string>|string  $ids
     * @return list<int>
     */
    private function normalizePartnerIdList(array|string $ids): array
    {
        if (is_string($ids)) {
            $ids = preg_split('/[\s,;]+/', $ids) ?: [];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));
    }

    /**
     * @return array<string, string>
     */
    public static function networkModeOptions(): array
    {
        return [
            self::NETWORK_MODE_OFF => 'Uit',
            self::NETWORK_MODE_MANUAL => 'Handmatig (chauffeur stuurt naar partners)',
            self::NETWORK_MODE_AUTO => 'Automatisch (fallback naar partners)',
        ];
    }
}
