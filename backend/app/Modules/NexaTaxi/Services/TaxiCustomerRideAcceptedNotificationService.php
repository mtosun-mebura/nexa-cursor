<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\InvoiceSetting;
use App\Models\TenantCustomerEmail;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideRequestNotificationLog;
use App\Services\CompanyEmailLogoService;
use App\Services\EmailTemplateService;
use App\Services\EnvService;
use App\Services\TenantCustomerMailService;
use App\Services\WhatsAppBookingMessageComposer;
use App\Services\WhatsAppBusinessService;
use App\Support\EmailCardHtml;
use Illuminate\Support\Facades\Log;

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

    public function notifyAfterRideAssigned(string $conn, RideRequest $ride, User $driver, array $options = []): void
    {
        if ($ride->exists) {
            $ride = $ride->fresh() ?? $ride;
        }
        if (! in_array($ride->status, [RideRequest::STATUS_ASSIGNED, RideRequest::STATUS_ACCEPTED], true)) {
            return;
        }

        $force = ! empty($options['force']);
        $companyId = (int) ($ride->company_id ?? 0);
        if (! $this->dispatchSettings->customerAcceptNotificationEnabled($companyId > 0 ? $companyId : null)) {
            return;
        }

        $variables = $this->buildVariables($ride, $driver, $companyId);

        $rideId = (int) $ride->id;

        if ($this->dispatchSettings->customerAcceptEmailEnabled($companyId > 0 ? $companyId : null)
            && ($force || ! $this->channelAlreadySent($conn, $rideId, RideRequestNotificationLog::CHANNEL_EMAIL))) {
            $this->sendCustomerEmail($conn, $ride, $companyId, $variables);
        }

        if ($this->dispatchSettings->customerAcceptWhatsappEnabled($companyId > 0 ? $companyId : null)
            && ($force || ! $this->channelAlreadySent($conn, $rideId, RideRequestNotificationLog::CHANNEL_WHATSAPP))) {
            $this->sendCustomerWhatsapp($conn, $ride, $companyId, $variables, $force);
        }

        if ($this->dispatchSettings->customerAcceptSmsEnabled($companyId > 0 ? $companyId : null)
            && ($force || ! $this->channelAlreadySent($conn, $rideId, RideRequestNotificationLog::CHANNEL_SMS))) {
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
            ? \App\Modules\NexaTaxi\Support\ContractTransportTimezone::asAmsterdamWall($ride->pickup_at)?->format('d-m-Y H:i')
            : '—';
        $pickupAt = $pickupAt ?: '—';

        $companyName = (string) ($settings->company_name ?? $company?->name ?? '');
        $companyPhone = (string) ($settings->company_phone ?? $company?->phone ?? '');
        $companyEmail = (string) ($settings->company_email ?? $company?->email ?? '');
        $companyIdForSite = (int) ($company?->id ?? $ride->company_id ?? 0);
        $companyWebsiteUrl = EmailCardHtml::primaryWebsiteUrlForCompany($companyIdForSite > 0 ? $companyIdForSite : null);
        $companyNameHtml = EmailCardHtml::companyNameHtml($companyName !== '' ? $companyName : 'ons', $companyWebsiteUrl);

        $vehicleLabel = '';
        $licensePlate = '';
        $vehicleId = (int) ($ride->vehicle_id ?? 0);
        if ($vehicleId > 0) {
            try {
                $vehicle = $ride->relationLoaded('vehicle')
                    ? $ride->vehicle
                    : \App\Modules\NexaTaxi\Models\Vehicle::on($ride->getConnectionName())->find($vehicleId);
                $vehicleLabel = $vehicle ? $vehicle->fleetLabel() : '';
                $licensePlate = $vehicle ? trim((string) ($vehicle->license_plate ?? '')) : '';
            } catch (\Throwable) {
                $vehicleLabel = '';
                $licensePlate = '';
            }
        }

        return [
            'CUSTOMER_NAME' => (string) ($ride->customer_name ?: 'klant'),
            'CUSTOMER_EMAIL' => (string) ($ride->customer_email ?? ''),
            'CUSTOMER_PHONE' => (string) ($ride->customer_phone ?? ''),
            'DRIVER_NAME' => $driverName,
            'DRIVER_PHONE' => (string) ($driver->phone ?? ''),
            'VEHICLE_LABEL' => $vehicleLabel,
            'LICENSE_PLATE' => $licensePlate,
            'PICKUP_AT' => $pickupAt,
            'PICKUP_ADDRESS' => \App\Modules\NexaTaxi\Support\RidePlaceName::formatPickup($ride),
            'DROPOFF_ADDRESS' => \App\Modules\NexaTaxi\Support\RidePlaceName::formatDropoff($ride),
            'RIDE_ID' => (string) $ride->id,
            'COMPANY_NAME' => $companyName,
            'COMPANY_NAME_HTML' => $companyNameHtml,
            'COMPANY_WEBSITE_URL' => $companyWebsiteUrl ?? '',
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
     * SMS-tekst volgens META_BODY_CUSTOMER_SMS.
     *
     * @param  array{driver_name?: string|null, remark?: string|null}  $context
     * @param  array<string, string>  $variables
     */
    protected function composeCustomerSmsText(
        RideRequest $ride,
        string $decisionLabel,
        ?int $companyId,
        array $context,
        array $variables
    ): string {
        $composed = app(WhatsAppBookingMessageComposer::class)->composeCustomerSms(
            $ride,
            $decisionLabel,
            $context,
            $companyId
        );

        $body = trim((string) ($composed['body'] ?? ''));

        return $body !== ''
            ? $body
            : $this->renderPlainMessage($companyId ?? 0, $variables);
    }

    /**
     * Zelfde tekst als Meta-statussjabloon (rit_status_update), voor WA-fallback zonder template.
     *
     * @param  array{driver_name?: string|null, driver_phone?: string|null, license_plate?: string|null, extra_lines?: list<string>}  $context
     * @param  array<string, string>  $variables
     */
    protected function composeStatusPlainText(
        RideRequest $ride,
        string $event,
        ?int $companyId,
        array $context,
        array $variables
    ): string {
        $composed = app(WhatsAppBookingMessageComposer::class)->composeStatus(
            $ride,
            $event,
            $context,
            $companyId
        );

        $body = trim((string) ($composed['fallback_body'] ?? ''));

        return $body !== ''
            ? $body
            : $this->renderPlainMessage($companyId ?? 0, $variables);
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

        $locale = \App\Modules\NexaTaxi\Support\CustomerMailLocale::forRide($ride);
        $vars = array_merge(
            $variables,
            $this->companyLogos->templateVariable(
                $companyId > 0 ? $companyId : null,
                $variables['COMPANY_NAME'] ?? null
            )
        );

        [$subject, $htmlContent, $textContent] = \App\Modules\NexaTaxi\Support\CustomerMailLocale::runWith(
            $locale,
            function () use ($companyId, $vars, $variables, $locale) {
                $template = app(TaxiCustomerAcceptEmailTemplateService::class)
                    ->resolveActiveTemplate($companyId > 0 ? $companyId : null);

                if ($locale === 'en') {
                    $subject = __('Uw taxirit is geaccepteerd – :company', ['company' => $variables['COMPANY_NAME'] ?? '']);
                    $companyNameHtml = $variables['COMPANY_NAME_HTML'] ?? e($variables['COMPANY_NAME'] ?? __('ons'));
                    $footer = '<p style="margin:20px 0 0;font-size:13px;color:#6b7280;text-align:center;line-height:1.6;">'
                        .e(__('Vragen? Neem contact op met')).' '.$companyNameHtml.'.'
                        .'</p>'.EmailCardHtml::poweredByFooter();
                    $htmlContent = EmailCardHtml::ensurePoweredByLink(
                        EmailCardHtml::wrap(
                            __('Rit geaccepteerd'),
                            __('Uw taxirit is geaccepteerd'),
                            '<p style="margin:0 0 16px;font-size:16px;">'.e(__('Beste :name,', ['name' => $variables['CUSTOMER_NAME'] ?? __('klant')])).'</p>'
                            .'<p style="margin:0 0 16px;font-size:15px;line-height:1.6;">'.__('Goed nieuws: uw rit is geaccepteerd door :driver.', ['driver' => '<strong>'.e($variables['DRIVER_NAME'] ?? '').'</strong>']).'</p>'
                            .'<table role="presentation" width="100%" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#e8eef5;border:1px solid #cbd5e1;border-radius:12px;margin:0 0 20px;">'
                            .'<tr><td style="padding:16px 18px;border-radius:12px;background-color:#e8eef5;">'
                            .'<p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>'.e(__('Ophaalmoment:')).'</strong> '.e($variables['PICKUP_AT'] ?? '—').'</p>'
                            .'<p style="margin:0 0 8px;font-size:15px;line-height:1.6;"><strong>'.e(__('Ophalen:')).'</strong> '.e($variables['PICKUP_ADDRESS'] ?? '—').'</p>'
                            .'<p style="margin:0;font-size:15px;line-height:1.6;"><strong>'.e(__('Afzetten:')).'</strong> '.e($variables['DROPOFF_ADDRESS'] ?? '—').'</p>'
                            .'</td></tr></table>'
                            .'<p style="margin:0;font-size:15px;line-height:1.6;">'.e(__('Met vriendelijke groet,')).'<br>'.e($variables['COMPANY_NAME'] ?? '').'</p>',
                            EmailCardHtml::companyLogoMarkup(),
                            $footer,
                        )
                    );
                    $textContent = __('Beste :name,', ['name' => $variables['CUSTOMER_NAME'] ?? __('klant')])."\n\n"
                        .__('Uw taxirit is geaccepteerd door :driver.', ['driver' => $variables['DRIVER_NAME'] ?? ''])."\n\n"
                        .__('Ophaalmoment:').' '.($variables['PICKUP_AT'] ?? '—')."\n"
                        .__('Ophalen:').' '.($variables['PICKUP_ADDRESS'] ?? '—')."\n"
                        .__('Afzetten:').' '.($variables['DROPOFF_ADDRESS'] ?? '—')."\n\n"
                        .__('Met vriendelijke groet,')."\n".($variables['COMPANY_NAME'] ?? '');

                    return [$subject, $htmlContent, $textContent];
                }

                if ($template) {
                    $subject = $this->emailTemplates->parseTemplateVariables($template->subject, $vars);
                    $htmlContent = EmailCardHtml::ensurePoweredByLink(
                        $this->emailTemplates->parseTemplateVariables($template->html_content, $vars)
                    );
                    $textContent = $template->text_content
                        ? $this->emailTemplates->parseTemplateVariables($template->text_content, $vars)
                        : strip_tags($htmlContent);
                    $textContent = trim(preg_replace("/\n{3,}/", "\n\n", str_replace("\n\n\n", "\n\n", $textContent)) ?? $textContent);
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

                return [$subject, $htmlContent, $textContent];
            }
        );

        $replyTo = trim($variables['COMPANY_EMAIL']);

        $record = app(TenantCustomerMailService::class)->send([
            'company_id' => $companyId > 0 ? $companyId : null,
            'type' => TenantCustomerEmail::TYPE_RIDE_ACCEPTED,
            'to_email' => $email,
            'to_name' => $variables['CUSTOMER_NAME'] ?? null,
            'subject' => $subject,
            'html' => $htmlContent,
            'text' => $textContent,
            'related_type' => 'ride_request',
            'related_id' => $rideId,
            'reply_to' => $replyTo !== '' ? $replyTo : null,
            'reply_to_name' => $variables['COMPANY_NAME'] ?? null,
        ]);

        if ($record->status === TenantCustomerEmail::STATUS_SENT) {
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_EMAIL,
                RideRequestNotificationLog::STATUS_SENT,
                $variables['CUSTOMER_NAME'],
                $email,
                (int) $ride->driver_id
            );

            return;
        }

        $this->logCustomer(
            $conn,
            $rideId,
            RideRequestNotificationLog::CHANNEL_EMAIL,
            RideRequestNotificationLog::STATUS_FAILED,
            $variables['CUSTOMER_NAME'],
            $email,
            (int) $ride->driver_id,
            $record->error_message
        );
        Log::warning('Klant-e-mail rit geaccepteerd mislukt.', [
            'ride_request_id' => $rideId,
            'error' => $record->error_message,
        ]);
    }

    /**
     * @param  array<string, string>  $variables
     */
    protected function sendCustomerWhatsapp(
        string $conn,
        RideRequest $ride,
        int $companyId,
        array $variables,
        bool $force = false
    ): void {
        $rideId = (int) $ride->id;

        // Marktplaats: status via de app. WhatsApp alleen bij een website-boeking.
        if ($ride->isNexaSuiteBooking()) {
            app(TaxiCustomerRideStatusNotificationService::class)->notify(
                $conn,
                $ride,
                WhatsAppBookingMessageComposer::EVENT_ACCEPTED,
                [
                    'driver_name' => $variables['DRIVER_NAME'] ?? null,
                    'driver_phone' => $variables['DRIVER_PHONE'] ?? null,
                    'license_plate' => $variables['LICENSE_PLATE'] ?? null,
                ],
                force: $force
            );

            return;
        }

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

        if (! $this->whatsapp->isConfigured($companyId > 0 ? $companyId : null)) {
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_WHATSAPP,
                RideRequestNotificationLog::STATUS_SKIPPED,
                $variables['CUSTOMER_NAME'],
                $phone,
                (int) $ride->driver_id,
                'WhatsApp Business API niet geconfigureerd (platform).'
            );

            return;
        }

        $settingsCompanyId = $companyId > 0 ? $companyId : null;

        if (! $this->dispatchSettings->customerWhatsappStatusEventEnabled(
            WhatsAppBookingMessageComposer::EVENT_ACCEPTED,
            $settingsCompanyId
        )) {
            return;
        }

        if (app(WhatsAppBookingMessageComposer::class)->statusTemplateName() !== '') {
            // Na eerdere afwijzing / nieuw ophaalmoment: forceer status-update zodat klant
            // weer een rit_status_update (accepted) met actuele tijd krijgt.
            $hadDeclineNotice = RideRequestNotificationLog::on($conn)
                ->where('ride_request_id', $rideId)
                ->where('channel', RideRequestNotificationLog::CHANNEL_WHATSAPP)
                ->where(function ($q) {
                    $q->where('detail', 'like', '%decline%')
                        ->orWhere('detail', 'like', '%:declined%');
                })
                ->exists();

            $ok = app(TaxiCustomerRideStatusNotificationService::class)->notify(
                $conn,
                $ride,
                WhatsAppBookingMessageComposer::EVENT_ACCEPTED,
                [
                    'driver_name' => $variables['DRIVER_NAME'] ?? null,
                    'driver_phone' => $variables['DRIVER_PHONE'] ?? null,
                    'license_plate' => $variables['LICENSE_PLATE'] ?? null,
                ],
                force: $force || $hadDeclineNotice
            );
            $this->logCustomer(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_WHATSAPP,
                $ok ? RideRequestNotificationLog::STATUS_SENT : RideRequestNotificationLog::STATUS_FAILED,
                $variables['CUSTOMER_NAME'],
                $phone,
                (int) $ride->driver_id,
                $ok ? null : 'Statussjabloon niet verzonden (controleer WHATSAPP_RIDE_STATUS_TEMPLATE / events).',
                ['mode' => 'status_template', 'decision' => 'accepted']
            );

            return;
        }

        $statusContext = [
            'driver_name' => $variables['DRIVER_NAME'] ?? null,
            'driver_phone' => $variables['DRIVER_PHONE'] ?? null,
            'license_plate' => $variables['LICENSE_PLATE'] ?? null,
        ];
        $body = $this->composeStatusPlainText(
            $ride,
            WhatsAppBookingMessageComposer::EVENT_ACCEPTED,
            $settingsCompanyId,
            $statusContext,
            $variables
        );
        $result = $this->whatsapp->sendText($phone, $body, $settingsCompanyId);

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
                ['mode' => 'text', 'decision' => 'accepted']
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
     * WhatsApp naar klant wanneer een chauffeur een aanbod afwijst (universeel statussjabloon).
     */
    public function notifyAfterOfferDeclined(string $conn, RideRequest $ride, User $driver, ?string $remark = null): void
    {
        if ($ride->exists) {
            $ride = $ride->fresh() ?? $ride;
        }

        $companyId = (int) ($ride->company_id ?? 0);
        $settingsCompanyId = $companyId > 0 ? $companyId : null;

        if (! $this->dispatchSettings->customerAcceptNotificationEnabled($settingsCompanyId)
            || ! $this->dispatchSettings->customerAcceptWhatsappEnabled($settingsCompanyId)
            || ! $this->dispatchSettings->customerWhatsappStatusEventEnabled(
                WhatsAppBookingMessageComposer::EVENT_DECLINED,
                $settingsCompanyId
            )) {
            return;
        }

        if (app(WhatsAppBookingMessageComposer::class)->statusTemplateName() === '') {
            return;
        }

        $variables = $this->buildVariables($ride, $driver, $companyId);
        $extraLines = [];
        $remark = trim((string) $remark);
        if ($remark !== '') {
            $extraLines[] = 'Opmerking: '.$remark;
        }

        $ok = app(TaxiCustomerRideStatusNotificationService::class)->notify(
            $conn,
            $ride,
            WhatsAppBookingMessageComposer::EVENT_DECLINED,
            [
                'driver_name' => $variables['DRIVER_NAME'] ?? null,
                'driver_phone' => $variables['DRIVER_PHONE'] ?? null,
                'license_plate' => $variables['LICENSE_PLATE'] ?? null,
                'extra_lines' => $extraLines,
            ],
            force: true
        );

        $this->logCustomer(
            $conn,
            (int) $ride->id,
            RideRequestNotificationLog::CHANNEL_WHATSAPP,
            $ok ? RideRequestNotificationLog::STATUS_SENT : RideRequestNotificationLog::STATUS_FAILED,
            $variables['CUSTOMER_NAME'],
            trim((string) ($ride->customer_phone ?? '')) ?: null,
            (int) $driver->id,
            $ok ? 'decline' : 'decline: statussjabloon niet verzonden.',
            ['mode' => 'status_template', 'decision' => 'declined']
        );
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
                'SMS-provider staat uit in Chauffeur dispatch.'
            );

            return;
        }

        $body = $this->composeCustomerSmsText(
            $ride,
            WhatsAppBookingMessageComposer::DECISION_ACCEPTED,
            $companyId > 0 ? $companyId : null,
            [
                'driver_name' => $variables['DRIVER_NAME'] ?? null,
                'remark' => null,
            ],
            $variables
        );
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
