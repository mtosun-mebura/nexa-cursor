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

    public const KEY_CUSTOMER_ACCEPT_ENABLED = 'taxi_dispatch_customer_accept_enabled';

    public const KEY_CUSTOMER_ACCEPT_EMAIL_ENABLED = 'taxi_dispatch_customer_accept_email_enabled';

    public const KEY_CUSTOMER_ACCEPT_WHATSAPP_ENABLED = 'taxi_dispatch_customer_accept_whatsapp_enabled';

    public const KEY_CUSTOMER_ACCEPT_SMS_ENABLED = 'taxi_dispatch_customer_accept_sms_enabled';

    public const KEY_CUSTOMER_ACCEPT_SMS_PROVIDER = 'taxi_dispatch_customer_accept_sms_provider';

    public const KEY_CUSTOMER_ACCEPT_PLAIN_MESSAGE = 'taxi_dispatch_customer_accept_plain_message';

    public const KEY_CUSTOMER_ACCEPT_WHATSAPP_TEMPLATE = 'taxi_dispatch_customer_accept_whatsapp_template';

    public const KEY_CUSTOMER_ACCEPT_WHATSAPP_TEMPLATE_LANG = 'taxi_dispatch_customer_accept_whatsapp_template_lang';

    public const SMS_PROVIDER_OFF = 'off';

    public const SMS_PROVIDER_DEMO = 'demo';

    public const SMS_PROVIDER_VONAGE = 'vonage';

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
        $stored = GeneralSetting::get(self::KEY_CUSTOMER_ACCEPT_WHATSAPP_ENABLED, null, $companyId);

        return $stored === '1';
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
}
