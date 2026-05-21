<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\GeneralSetting;
use App\Services\EnvService;
use App\Services\PaymentProviderService;

/**
 * Chauffeur-dispatch instellingen (per tenant via GeneralSetting, fallback naar config/.env).
 */
class TaxiDispatchSettingsService
{
    public const KEY_OFFER_TTL_SECONDS = 'taxi_dispatch_offer_ttl_seconds';

    public const KEY_BOOKING_WHATSAPP_ENABLED = 'taxi_dispatch_booking_whatsapp_enabled';

    public const KEY_BOOKING_WHATSAPP_NUMBER = 'taxi_dispatch_booking_whatsapp_number';

    public const KEY_BOOKING_WHATSAPP_CLICK_TO_CHAT = 'taxi_dispatch_booking_whatsapp_click_to_chat';

    public const KEY_BOOKING_DRIVER_EMAIL_ENABLED = 'taxi_dispatch_booking_driver_email_enabled';

    public const KEY_PAYMENT_BOOKING_ENABLED = 'taxi_dispatch_payment_booking_enabled';

    public const KEY_PAYMENT_DRIVER_ENABLED = 'taxi_dispatch_payment_driver_enabled';

    public const MIN_TTL_SECONDS = 15;

    public const MAX_TTL_SECONDS = 3600;

    public function __construct(
        protected EnvService $env,
        protected PaymentProviderService $paymentProviders
    ) {}

    public function offerTtlSeconds(?int $companyId = null): int
    {
        $default = (int) config('taxi-dispatch.offer_ttl_seconds', 300);
        $raw = GeneralSetting::get(self::KEY_OFFER_TTL_SECONDS, null, $companyId);

        if ($raw === null || $raw === '') {
            return $this->clampTtl($default);
        }

        return $this->clampTtl((int) $raw);
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

    public function bookingWhatsappEnabled(?int $companyId = null): bool
    {
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
        $stored = trim((string) GeneralSetting::get(self::KEY_BOOKING_WHATSAPP_NUMBER, null, $companyId));
        if ($stored !== '') {
            return $stored;
        }

        return $this->envFallbackWhatsappNumber();
    }

    public function setBookingWhatsappNumber(string $number, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_BOOKING_WHATSAPP_NUMBER, trim($number), $companyId);
    }

    public function bookingWhatsappClickToChatEnabled(?int $companyId = null): bool
    {
        $stored = GeneralSetting::get(self::KEY_BOOKING_WHATSAPP_CLICK_TO_CHAT, null, $companyId);
        if ($stored !== null && $stored !== '') {
            return $stored === '1';
        }

        return (string) $this->env->get('WHATSAPP_CLICK_TO_CHAT_ENABLED', '0') === '1';
    }

    public function setBookingWhatsappClickToChatEnabled(bool $enabled, ?int $companyId = null): void
    {
        GeneralSetting::set(self::KEY_BOOKING_WHATSAPP_CLICK_TO_CHAT, $enabled ? '1' : '0', $companyId);
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

    public function defaultBookingWhatsappEnabled(): bool
    {
        return $this->envFallbackWhatsappNumber() !== '';
    }

    public function envFallbackWhatsappNumber(): string
    {
        $number = trim((string) $this->env->get('WHATSAPP_CLICK_TO_CHAT_NUMBER', ''));
        if ($number !== '') {
            return $number;
        }

        return trim((string) $this->env->get('WHATSAPP_WIDGET_PHONE', ''));
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
     * @return array{booking: bool, driver: bool, mollie_configured: bool}
     */
    public function paymentOptionsForTenant(?int $companyId = null): array
    {
        $mollieConfigured = $this->hasMollieConfigured($companyId);

        return [
            'booking' => $mollieConfigured && $this->paymentBookingEnabled($companyId),
            'driver' => $mollieConfigured && $this->paymentDriverEnabled($companyId),
            'mollie_configured' => $mollieConfigured,
        ];
    }
}
