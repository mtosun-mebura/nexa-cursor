<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideRequestNotificationLog;
use App\Services\EnvService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TaxiBookingNotificationService
{
    public const LOG_CONTEXT_CUSTOMER_BOOKING = 'customer_booking';

    public function __construct(
        protected EnvService $env,
        protected WhatsAppBusinessService $whatsapp,
        protected TaxiBookingSummaryText $summaryText,
        protected TaxiDriverEligibilityService $drivers,
        protected TaxiDispatchSettingsService $dispatchSettings,
        protected TaxiRideNotificationLogService $notificationLogs
    ) {}

    /**
     * @param  array{stopovers?: list<string>, return_at?: string|null, section_config?: array<string, mixed>, settings_company_id?: int|null}  $context
     */
    public function notifyNewRide(string $conn, RideRequest $ride, array $context = []): void
    {
        $companyId = (int) ($ride->company_id ?? 0);
        $summary = $this->summaryText->build($ride, $context);

        $settingsCompanyId = $this->resolveSettingsCompanyId(
            $companyId > 0 ? $companyId : null,
            isset($context['settings_company_id']) ? (int) $context['settings_company_id'] : null
        );

        $this->sendDispatchWhatsapp($conn, $ride, $settingsCompanyId, $summary);
        $this->sendDriverEmails($conn, $companyId, $ride, $summary, $settingsCompanyId);
        $this->sendCustomerBookingEmail($conn, $ride, $summary, $settingsCompanyId);
    }

    protected function resolveSettingsCompanyId(?int $companyId, ?int $fallbackCompanyId = null): ?int
    {
        if ($companyId !== null && $companyId > 0) {
            return $companyId;
        }

        if ($fallbackCompanyId !== null && $fallbackCompanyId > 0) {
            return $fallbackCompanyId;
        }

        if (app()->bound('resolved_tenant_id')) {
            $resolvedTenantId = (int) app('resolved_tenant_id');
            if ($resolvedTenantId > 0) {
                return $resolvedTenantId;
            }
        }

        return null;
    }

    public function whatsappAutoSendEnabled(?int $companyId = null): bool
    {
        if (! $this->whatsapp->isConfigured()) {
            return false;
        }

        if (! $this->dispatchSettings->bookingWhatsappEnabled($companyId)) {
            return false;
        }

        return $this->dispatchSettings->bookingWhatsappNumber($companyId) !== '';
    }

    public function whatsappClientClickToChatEnabled(?int $companyId = null): bool
    {
        if ($this->whatsappAutoSendEnabled($companyId)) {
            return false;
        }

        if (! $this->dispatchSettings->bookingWhatsappClickToChatEnabled($companyId)) {
            return false;
        }

        return $this->dispatchSettings->bookingWhatsappNumber($companyId) !== '';
    }

    private function sendDispatchWhatsapp(string $conn, RideRequest $ride, ?int $companyId, string $summary): void
    {
        $rideId = (int) $ride->id;

        if (! $this->dispatchSettings->bookingWhatsappEnabled($companyId)) {
            $this->notificationLogs->recordWhatsappSkipped(
                $conn,
                $rideId,
                'WhatsApp bij boeking staat uit in chauffeur-dispatch.'
            );

            return;
        }

        if (! $this->whatsapp->isConfigured()) {
            $this->notificationLogs->recordWhatsappSkipped(
                $conn,
                $rideId,
                'WhatsApp Business API is niet geconfigureerd op de server.'
            );

            return;
        }

        $recipient = $this->dispatchSettings->bookingWhatsappNumber($companyId);
        if ($recipient === '') {
            $this->notificationLogs->recordWhatsappSkipped(
                $conn,
                $rideId,
                'Geen WhatsApp-ontvangernummer ingesteld.'
            );
            Log::warning('WhatsApp boeking: geen ontvangernummer geconfigureerd.', [
                'company_id' => $companyId,
                'ride_request_id' => $rideId,
            ]);

            return;
        }

        $result = $this->whatsapp->sendText($recipient, $summary);
        if ($result['ok'] ?? false) {
            $this->notificationLogs->recordWhatsappSent($conn, $rideId, $recipient, $result['meta'] ?? null);
        } else {
            $error = (string) ($result['error'] ?? 'Onbekende fout');
            $this->notificationLogs->recordWhatsappFailed($conn, $rideId, $recipient, $error);
            Log::warning('WhatsApp boeking: bericht niet verzonden.', [
                'ride_request_id' => $rideId,
                'error' => $error,
            ]);
        }
    }

    private function sendDriverEmails(
        string $conn,
        int $companyId,
        RideRequest $ride,
        string $summary,
        ?int $settingsCompanyId
    ): void {
        $rideId = (int) $ride->id;

        if ($companyId <= 0) {
            $this->notificationLogs->recordDriverEmailsSkipped(
                $conn,
                $rideId,
                'Geen bedrijf gekoppeld aan deze rit.'
            );

            return;
        }

        if (! $this->dispatchSettings->bookingDriverEmailEnabled($settingsCompanyId)) {
            $this->notificationLogs->recordDriverEmailsSkipped(
                $conn,
                $rideId,
                'E-mail naar chauffeurs staat uit in chauffeur-dispatch.'
            );

            return;
        }

        $recipients = $this->drivers
            ->buildChauffeurQuery($companyId)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'email', 'first_name', 'last_name']);

        if ($recipients->isEmpty()) {
            $this->notificationLogs->recordDriverEmailsSkipped(
                $conn,
                $rideId,
                'Geen chauffeurs met een geldig e-mailadres gevonden.'
            );

            return;
        }

        $this->env->applyMailConfigToRuntime();
        $from = $this->env->resolveMailFromHeaders();
        $fromAddress = $from['from_address'];
        $fromName = $from['from_name'];
        $smtpUsername = $from['smtp_username'];
        $subject = 'Nieuwe taxirit #'.$ride->id;
        $pickupAt = $ride->pickup_at
            ? $ride->pickup_at->timezone(config('app.timezone', 'Europe/Amsterdam'))->format('d-m-Y H:i')
            : '—';

        foreach ($recipients as $driver) {
            $email = trim((string) $driver->email);
            $driverName = trim(($driver->first_name ?? '').' '.($driver->last_name ?? ''));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->notificationLogs->recordDriverEmailFailed(
                    $conn,
                    $rideId,
                    (int) $driver->id,
                    $driverName !== '' ? $driverName : 'Chauffeur #'.$driver->id,
                    $email,
                    'Ongeldig e-mailadres.'
                );

                continue;
            }

            try {
                Mail::send('emails.taxi-ride-request-driver', [
                    'driver_name' => $driverName,
                    'ride_id' => $ride->id,
                    'pickup_at' => $pickupAt,
                    'pickup_address' => $ride->pickup_address,
                    'dropoff_address' => $ride->dropoff_address,
                    'customer_name' => $ride->customer_name,
                    'customer_phone' => $ride->customer_phone,
                    'customer_email' => $ride->customer_email,
                    'quoted_price' => $ride->quoted_price,
                    'summary_text' => $summary,
                ], function ($mailMessage) use ($email, $driverName, $subject, $fromAddress, $fromName, $smtpUsername, $ride) {
                    $mailMessage->to($email, $driverName)
                        ->subject($subject)
                        ->from($fromAddress, $fromName);

                    if ($ride->customer_email) {
                        $mailMessage->replyTo($ride->customer_email, (string) ($ride->customer_name ?: ''));
                    }

                    if ($smtpUsername !== '') {
                        try {
                            $symfonyMessage = $mailMessage->getSymfonyMessage();
                            $symfonyMessage->getHeaders()->remove('Sender');
                            $symfonyMessage->getHeaders()->addMailboxHeader('Sender', $smtpUsername);
                        } catch (\Throwable) {
                            // Sender header is optioneel
                        }
                    }
                });

                $this->notificationLogs->recordDriverEmailSent(
                    $conn,
                    $rideId,
                    (int) $driver->id,
                    $driverName !== '' ? $driverName : 'Chauffeur #'.$driver->id,
                    $email
                );
            } catch (\Throwable $e) {
                $this->notificationLogs->recordDriverEmailFailed(
                    $conn,
                    $rideId,
                    (int) $driver->id,
                    $driverName !== '' ? $driverName : 'Chauffeur #'.$driver->id,
                    $email,
                    $e->getMessage()
                );
                Log::warning('Chauffeur-e-mail rit niet verzonden.', [
                    'ride_request_id' => $rideId,
                    'driver_id' => $driver->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendCustomerBookingEmail(
        string $conn,
        RideRequest $ride,
        string $summary,
        ?int $settingsCompanyId
    ): void {
        $rideId = (int) $ride->id;

        if (! $this->dispatchSettings->bookingCustomerEmailEnabled($settingsCompanyId)) {
            $this->notificationLogs->record(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_EMAIL,
                RideRequestNotificationLog::STATUS_SKIPPED,
                'Klant',
                null,
                null,
                self::LOG_CONTEXT_CUSTOMER_BOOKING.': E-mail naar klant bij boeking staat uit in chauffeur-dispatch.'
            );

            return;
        }

        $email = trim((string) ($ride->customer_email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->notificationLogs->record(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_EMAIL,
                RideRequestNotificationLog::STATUS_SKIPPED,
                (string) ($ride->customer_name ?: 'Klant'),
                $email !== '' ? $email : null,
                null,
                self::LOG_CONTEXT_CUSTOMER_BOOKING.': Geen geldig klant-e-mailadres op de rit.'
            );

            return;
        }

        $subject = 'Bevestiging van uw taxiboeking #'.$ride->id;
        $pickupAt = $ride->pickup_at
            ? $ride->pickup_at->timezone(config('app.timezone', 'Europe/Amsterdam'))->format('d-m-Y H:i')
            : '—';
        $customerName = trim((string) ($ride->customer_name ?: ''));

        if (! $this->env->isMailDeliverableToInbox($settingsCompanyId)) {
            $this->notificationLogs->record(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_EMAIL,
                RideRequestNotificationLog::STATUS_SKIPPED,
                $customerName !== '' ? $customerName : 'Klant',
                $email,
                null,
                self::LOG_CONTEXT_CUSTOMER_BOOKING.': Geen bruikbare SMTP-configuratie voor deze tenant.'
            );
            Log::warning('Klant-e-mail boeking niet verstuurd: geen bruikbare SMTP.', [
                'ride_request_id' => $rideId,
                'company_id' => $settingsCompanyId,
            ]);

            return;
        }

        $this->env->applyMailConfigToRuntime($settingsCompanyId);
        $from = $this->env->resolveMailFromHeaders($settingsCompanyId);
        $fromAddress = $from['from_address'];
        $fromName = $from['from_name'];
        $smtpUsername = $from['smtp_username'];

        try {
            Mail::send('emails.taxi-ride-booking-customer', [
                'customer_name' => $customerName,
                'ride_id' => $ride->id,
                'pickup_at' => $pickupAt,
                'pickup_address' => $ride->pickup_address,
                'dropoff_address' => $ride->dropoff_address,
                'quoted_price' => $ride->quoted_price,
                'summary_text' => $summary,
                'portal_login_url' => route('login', [
                    'code_login' => 1,
                    'intended' => route('taxi.portal.dashboard'),
                ]),
            ], function ($mailMessage) use ($email, $customerName, $subject, $fromAddress, $fromName, $smtpUsername) {
                $mailMessage->to($email, $customerName !== '' ? $customerName : null)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);

                if ($smtpUsername !== '') {
                    try {
                        $symfonyMessage = $mailMessage->getSymfonyMessage();
                        $symfonyMessage->getHeaders()->remove('Sender');
                        $symfonyMessage->getHeaders()->addMailboxHeader('Sender', $smtpUsername);
                    } catch (\Throwable) {
                        // Sender header is optioneel
                    }
                }
            });

            $this->notificationLogs->record(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_EMAIL,
                RideRequestNotificationLog::STATUS_SENT,
                $customerName !== '' ? $customerName : 'Klant',
                $email,
                null,
                self::LOG_CONTEXT_CUSTOMER_BOOKING
            );
        } catch (\Throwable $e) {
            $this->notificationLogs->record(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_EMAIL,
                RideRequestNotificationLog::STATUS_FAILED,
                $customerName !== '' ? $customerName : 'Klant',
                $email,
                null,
                self::LOG_CONTEXT_CUSTOMER_BOOKING.': '.$e->getMessage()
            );
            Log::warning('Klant-e-mail boeking niet verzonden.', [
                'ride_request_id' => $rideId,
                'error' => $e->getMessage(),
            ]);
        }
    }

}
