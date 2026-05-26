<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\InvoiceSetting;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideRequestNotificationLog;
use App\Services\CompanyEmailLogoService;
use App\Services\EmailTemplateService;
use App\Services\EnvService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TaxiCustomerRideAcceptedNotificationService
{
    public const EMAIL_TEMPLATE_TYPE = 'taxi_ride_accepted';

    public const LOG_CONTEXT = 'customer_accept';

    public function __construct(
        protected TaxiDispatchSettingsService $dispatchSettings,
        protected TaxiRideNotificationLogService $notificationLogs,
        protected EnvService $env,
        protected EmailTemplateService $emailTemplates,
        protected WhatsAppBusinessService $whatsapp,
        protected TaxiCustomerSmsService $sms,
        protected CompanyEmailLogoService $companyLogos
    ) {}

    public function notifyAfterRideAssigned(string $conn, RideRequest $ride, User $driver): void
    {
        if ($ride->exists) {
            $ride = $ride->fresh() ?? $ride;
        }
        if (! in_array($ride->status, [RideRequest::STATUS_ASSIGNED, RideRequest::STATUS_ACCEPTED], true)) {
            return;
        }

        $companyId = (int) ($ride->company_id ?? 0);
        if (! $this->dispatchSettings->customerAcceptNotificationEnabled($companyId > 0 ? $companyId : null)) {
            return;
        }

        $variables = $this->buildVariables($ride, $driver, $companyId);

        $rideId = (int) $ride->id;

        if ($this->dispatchSettings->customerAcceptEmailEnabled($companyId > 0 ? $companyId : null)
            && ! $this->channelAlreadySent($conn, $rideId, RideRequestNotificationLog::CHANNEL_EMAIL)) {
            $this->sendCustomerEmail($conn, $ride, $companyId, $variables);
        }

        if ($this->dispatchSettings->customerAcceptWhatsappEnabled($companyId > 0 ? $companyId : null)
            && ! $this->channelAlreadySent($conn, $rideId, RideRequestNotificationLog::CHANNEL_WHATSAPP)) {
            $this->sendCustomerWhatsapp($conn, $ride, $companyId, $variables);
        }

        if ($this->dispatchSettings->customerAcceptSmsEnabled($companyId > 0 ? $companyId : null)
            && ! $this->channelAlreadySent($conn, $rideId, RideRequestNotificationLog::CHANNEL_SMS)) {
            $this->sendCustomerSms($conn, $ride, $companyId, $variables);
        }
    }

    public function channelAlreadySent(string $conn, int $rideId, string $channel): bool
    {
        if ($rideId <= 0 || ! \App\Modules\NexaTaxi\Support\TaxiNotificationLogSchema::tableExists($conn)) {
            return false;
        }

        return RideRequestNotificationLog::on($conn)
            ->where('ride_request_id', $rideId)
            ->where('channel', $channel)
            ->where('status', RideRequestNotificationLog::STATUS_SENT)
            ->where('detail', self::LOG_CONTEXT)
            ->exists();
    }

    /**
     * @return array<string, string>
     */
    public function buildVariables(RideRequest $ride, User $driver, int $companyId): array
    {
        $company = $companyId > 0 ? Company::find($companyId) : null;
        $settings = $companyId > 0
            ? InvoiceSetting::getSettingsForCompany($companyId)
            : InvoiceSetting::getSettings();

        $driverName = trim(($driver->first_name ?? '').' '.($driver->last_name ?? ''));
        if ($driverName === '') {
            $driverName = 'uw chauffeur';
        }

        $pickupAt = $ride->pickup_at
            ? $ride->pickup_at->timezone(config('app.timezone', 'Europe/Amsterdam'))->format('d-m-Y H:i')
            : '—';

        $companyName = (string) ($settings->company_name ?? $company?->name ?? '');
        $companyPhone = (string) ($settings->company_phone ?? $company?->phone ?? '');
        $companyEmail = (string) ($settings->company_email ?? $company?->email ?? '');

        return [
            'CUSTOMER_NAME' => (string) ($ride->customer_name ?: 'klant'),
            'CUSTOMER_EMAIL' => (string) ($ride->customer_email ?? ''),
            'CUSTOMER_PHONE' => (string) ($ride->customer_phone ?? ''),
            'DRIVER_NAME' => $driverName,
            'DRIVER_PHONE' => (string) ($driver->phone ?? ''),
            'PICKUP_AT' => $pickupAt,
            'PICKUP_ADDRESS' => (string) ($ride->pickup_address ?: '—'),
            'DROPOFF_ADDRESS' => (string) ($ride->dropoff_address ?: '—'),
            'RIDE_ID' => (string) $ride->id,
            'COMPANY_NAME' => $companyName,
            'COMPANY_PHONE' => $companyPhone,
            'COMPANY_EMAIL' => $companyEmail,
            'COMPANY_ADDRESS' => trim(
                ($settings->company_address ?? '')."\n"
                .($settings->company_postal_code ?? '').' '.($settings->company_city ?? '')
            ),
        ];
    }

    /**
     * @param  array<string, string>  $variables
     */
    protected function renderPlainMessage(int $companyId, array $variables): string
    {
        $template = $this->dispatchSettings->customerAcceptPlainMessage($companyId > 0 ? $companyId : null);
        $out = $template;
        foreach ($variables as $key => $value) {
            $out = str_replace('{{'.$key.'}}', $value, $out);
            $out = str_replace('{{ '.$key.' }}', $value, $out);
        }

        return trim($out);
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    protected function logCustomer(
        string $conn,
        int $rideId,
        string $channel,
        string $status,
        ?string $recipientName = null,
        ?string $recipientAddress = null,
        ?int $driverId = null,
        ?string $reason = null,
        ?array $meta = null
    ): void {
        $detail = self::LOG_CONTEXT;
        if ($reason !== null && $reason !== '') {
            $detail .= ': '.$reason;
        }
        $this->notificationLogs->record(
            $conn,
            $rideId,
            $channel,
            $status,
            $recipientName,
            $recipientAddress,
            $driverId,
            $detail,
            $meta
        );
    }

    /**
     * @param  array<string, string>  $variables
     */
    protected function sendCustomerEmail(string $conn, RideRequest $ride, int $companyId, array $variables): void
    {
        $rideId = (int) $ride->id;
        $email = trim((string) ($ride->customer_email ?? ''));
        if ($email === '') {
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_EMAIL,
                RideRequestNotificationLog::STATUS_SKIPPED,
                'Klant',
                null,
                (int) $ride->driver_id,
                'Geen klant-e-mailadres op de rit.'
            );

            return;
        }

        $template = EmailTemplate::query()
            ->where('type', self::EMAIL_TEMPLATE_TYPE)
            ->where('is_active', true)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id');
                if ($companyId > 0) {
                    $q->orWhere('company_id', $companyId);
                }
            })
            ->orderByDesc('company_id')
            ->first();

        $vars = array_merge(
            $variables,
            $this->companyLogos->templateVariable(
                $companyId > 0 ? $companyId : null,
                $variables['COMPANY_NAME'] ?? null
            )
        );

        if ($template) {
            $subject = $this->emailTemplates->parseTemplateVariables($template->subject, $vars);
            $htmlContent = $this->emailTemplates->parseTemplateVariables($template->html_content, $vars);
            $textContent = $template->text_content
                ? $this->emailTemplates->parseTemplateVariables($template->text_content, $vars)
                : strip_tags($htmlContent);
        } else {
            $subject = 'Uw taxirit is geaccepteerd – '.$variables['COMPANY_NAME'];
            $htmlContent = '<p>Beste '.e($variables['CUSTOMER_NAME']).',</p>'
                .'<p>Uw taxirit is geaccepteerd door <strong>'.e($variables['DRIVER_NAME']).'</strong>.</p>'
                .'<p><strong>Ophaalmoment:</strong> '.e($variables['PICKUP_AT']).'<br>'
                .'<strong>Ophalen:</strong> '.e($variables['PICKUP_ADDRESS']).'<br>'
                .'<strong>Afzetten:</strong> '.e($variables['DROPOFF_ADDRESS']).'</p>'
                .'<p>Met vriendelijke groet,<br>'.e($variables['COMPANY_NAME']).'</p>';
            $textContent = $this->renderPlainMessage($companyId, $variables);
        }

        $this->env->applyMailConfigToRuntime();
        $from = $this->env->resolveMailFromHeaders();
        $replyTo = trim($variables['COMPANY_EMAIL']);

        try {
            Mail::send([], [], function ($message) use (
                $email,
                $variables,
                $subject,
                $htmlContent,
                $textContent,
                $from,
                $replyTo,
                $companyId
            ) {
                $htmlBody = $this->companyLogos->embedInHtml(
                    $htmlContent,
                    $message,
                    $companyId > 0 ? $companyId : null,
                    $variables['COMPANY_NAME'] ?? null
                );

                $message->to($email, $variables['CUSTOMER_NAME'])
                    ->subject($subject)
                    ->from($from['from_address'], $from['from_name'])
                    ->html($htmlBody)
                    ->text($textContent);

                if ($replyTo !== '') {
                    $message->replyTo($replyTo, $variables['COMPANY_NAME']);
                }

                if ($from['smtp_username'] !== '') {
                    try {
                        $symfonyMessage = $message->getSymfonyMessage();
                        $symfonyMessage->getHeaders()->remove('Sender');
                        $symfonyMessage->getHeaders()->addMailboxHeader('Sender', $from['smtp_username']);
                    } catch (\Throwable) {
                        // optioneel
                    }
                }
            });

            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_EMAIL,
                RideRequestNotificationLog::STATUS_SENT,
                $variables['CUSTOMER_NAME'],
                $email,
                (int) $ride->driver_id
            );
        } catch (\Throwable $e) {
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_EMAIL,
                RideRequestNotificationLog::STATUS_FAILED,
                $variables['CUSTOMER_NAME'],
                $email,
                (int) $ride->driver_id,
                $e->getMessage()
            );
            Log::warning('Klant-e-mail rit geaccepteerd mislukt.', [
                'ride_request_id' => $rideId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, string>  $variables
     */
    protected function sendCustomerWhatsapp(string $conn, RideRequest $ride, int $companyId, array $variables): void
    {
        $rideId = (int) $ride->id;
        $phone = trim((string) ($ride->customer_phone ?? ''));
        if ($phone === '') {
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_WHATSAPP,
                RideRequestNotificationLog::STATUS_SKIPPED,
                $variables['CUSTOMER_NAME'],
                null,
                (int) $ride->driver_id,
                'Geen klanttelefoon op de rit.'
            );

            return;
        }

        if (! $this->whatsapp->isConfigured()) {
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_WHATSAPP,
                RideRequestNotificationLog::STATUS_SKIPPED,
                $variables['CUSTOMER_NAME'],
                $phone,
                (int) $ride->driver_id,
                'WhatsApp Business API niet geconfigureerd.'
            );

            return;
        }

        $templateName = $this->dispatchSettings->customerAcceptWhatsappTemplateName($companyId > 0 ? $companyId : null);
        $lang = $this->dispatchSettings->customerAcceptWhatsappTemplateLanguage($companyId > 0 ? $companyId : null);

        if ($templateName !== '') {
            $result = $this->whatsapp->sendTemplate(
                $phone,
                $templateName,
                $lang,
                [
                    $variables['CUSTOMER_NAME'],
                    $variables['DRIVER_NAME'],
                    $variables['PICKUP_AT'],
                    $variables['PICKUP_ADDRESS'],
                ]
            );
        } else {
            $body = $this->renderPlainMessage($companyId, $variables);
            $result = $this->whatsapp->sendText($phone, $body);
        }

        if ($result['ok'] ?? false) {
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_WHATSAPP,
                RideRequestNotificationLog::STATUS_SENT,
                $variables['CUSTOMER_NAME'],
                $phone,
                (int) $ride->driver_id,
                null,
                ['mode' => $templateName !== '' ? 'template:'.$templateName : 'text']
            );
        } else {
            $error = (string) ($result['error'] ?? 'Onbekende fout');
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_WHATSAPP,
                RideRequestNotificationLog::STATUS_FAILED,
                $variables['CUSTOMER_NAME'],
                $phone,
                (int) $ride->driver_id,
                $error
            );
        }
    }

    /**
     * @param  array<string, string>  $variables
     */
    protected function sendCustomerSms(string $conn, RideRequest $ride, int $companyId, array $variables): void
    {
        $rideId = (int) $ride->id;
        $phone = trim((string) ($ride->customer_phone ?? ''));
        $provider = $this->dispatchSettings->customerAcceptSmsProvider($companyId > 0 ? $companyId : null);

        if ($phone === '') {
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_SMS,
                RideRequestNotificationLog::STATUS_SKIPPED,
                $variables['CUSTOMER_NAME'],
                null,
                (int) $ride->driver_id,
                'Geen klanttelefoon op de rit.'
            );

            return;
        }

        if ($provider === TaxiDispatchSettingsService::SMS_PROVIDER_OFF) {
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_SMS,
                RideRequestNotificationLog::STATUS_SKIPPED,
                $variables['CUSTOMER_NAME'],
                $phone,
                (int) $ride->driver_id,
                'SMS-provider staat uit in dispatch-instellingen.'
            );

            return;
        }

        $body = $this->renderPlainMessage($companyId, $variables);
        $result = $this->sms->send($provider, $phone, $body);

        if ($result['ok'] ?? false) {
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_SMS,
                RideRequestNotificationLog::STATUS_SENT,
                $variables['CUSTOMER_NAME'],
                $phone,
                (int) $ride->driver_id,
                null,
                ['provider' => $provider.(! empty($result['demo']) ? ' (demo)' : '')]
            );
        } else {
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_SMS,
                RideRequestNotificationLog::STATUS_FAILED,
                $variables['CUSTOMER_NAME'],
                $phone,
                (int) $ride->driver_id,
                (string) ($result['error'] ?? 'Onbekende fout')
            );
        }
    }
}
