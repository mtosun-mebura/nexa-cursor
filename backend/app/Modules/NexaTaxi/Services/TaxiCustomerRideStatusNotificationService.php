<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideRequestNotificationLog;
use App\Modules\NexaTaxi\Support\TaxiNotificationLogSchema;
use App\Services\WhatsAppBookingMessageComposer;
use App\Services\WhatsAppBusinessService;
use Illuminate\Support\Facades\Log;

class TaxiCustomerRideStatusNotificationService
{
    public const LOG_CONTEXT_PREFIX = 'customer_status';

    public function __construct(
        protected WhatsAppBookingMessageComposer $composer,
        protected WhatsAppBusinessService $whatsapp,
        protected TaxiRideNotificationLogService $notificationLogs
    ) {}

    /**
     * @param  array{
     *     driver_name?: string|null,
     *     driver_phone?: string|null,
     *     extra_lines?: list<string>,
     *     stopovers?: list<string>,
     *     return_at?: string|null,
     *     section_config?: array<string, mixed>
     * }  $context
     */
    public function notify(string $conn, RideRequest $ride, string $event, array $context = []): bool
    {
        if ($ride->exists) {
            $ride = $ride->fresh() ?? $ride;
        }

        if (! $this->composer->statusEventEnabled($event)) {
            return false;
        }

        $companyId = (int) ($ride->company_id ?? 0);
        $settingsCompanyId = $companyId > 0 ? $companyId : null;
        $rideId = (int) $ride->id;
        $logDetail = self::LOG_CONTEXT_PREFIX.':'.$event;

        // Herdispatch mag meerdere keren; overige events zijn éénmalig per rit.
        $idempotent = $event !== WhatsAppBookingMessageComposer::EVENT_REDISPATCHED;
        if ($idempotent && $this->alreadySent($conn, $rideId, $logDetail)) {
            return true;
        }

        $phone = trim((string) ($ride->customer_phone ?? ''));
        $customerName = trim((string) ($ride->customer_name ?: 'Klant'));

        if ($phone === '') {
            $this->notificationLogs->record(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_WHATSAPP,
                RideRequestNotificationLog::STATUS_SKIPPED,
                $customerName,
                null,
                null,
                $logDetail.': Geen klanttelefoon op de rit.'
            );

            return false;
        }

        if (! $this->whatsapp->isConfigured($settingsCompanyId)) {
            $this->notificationLogs->record(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_WHATSAPP,
                RideRequestNotificationLog::STATUS_SKIPPED,
                $customerName,
                $phone,
                null,
                $logDetail.': WhatsApp Business API niet geconfigureerd.'
            );

            return false;
        }

        $composed = $this->composer->composeStatus($ride, $event, $context, $settingsCompanyId);
        $templateName = $this->composer->statusTemplateName();
        $lang = $this->composer->statusTemplateLang();

        if ($templateName !== '') {
            $result = $this->whatsapp->sendTemplate(
                $phone,
                $templateName,
                $lang,
                $composed['template_params'],
                $settingsCompanyId
            );
        } else {
            $result = $this->whatsapp->sendText($phone, $composed['fallback_body'], $settingsCompanyId);
            if (! ($result['ok'] ?? false) && $this->whatsapp->isOutsideCustomerCareWindowError((string) ($result['error'] ?? ''))) {
                $result['error'] = trim(
                    (string) ($result['error'] ?? '')
                    .' Stel WHATSAPP_RIDE_STATUS_TEMPLATE in onder Algemene configuraties → WhatsApp.'
                );
            }
        }

        if ($result['ok'] ?? false) {
            $this->notificationLogs->record(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_WHATSAPP,
                RideRequestNotificationLog::STATUS_SENT,
                $customerName,
                $phone,
                null,
                $logDetail,
                [
                    'event' => $event,
                    'mode' => $templateName !== '' ? 'template:'.$templateName : 'text',
                    'status_label' => $composed['status_label'],
                ]
            );

            return true;
        }

        $error = (string) ($result['error'] ?? 'Onbekende fout');
        $this->notificationLogs->record(
            $conn,
            $rideId,
            RideRequestNotificationLog::CHANNEL_WHATSAPP,
            RideRequestNotificationLog::STATUS_FAILED,
            $customerName,
            $phone,
            null,
            $logDetail.': '.$error
        );
        Log::warning('WhatsApp rit-status naar klant mislukt.', [
            'ride_request_id' => $rideId,
            'company_id' => $companyId,
            'event' => $event,
            'error' => $error,
        ]);

        return false;
    }

    public function alreadySent(string $conn, int $rideId, string $logDetail): bool
    {
        if ($rideId <= 0 || ! TaxiNotificationLogSchema::tableExists($conn)) {
            return false;
        }

        return RideRequestNotificationLog::on($conn)
            ->where('ride_request_id', $rideId)
            ->where('channel', RideRequestNotificationLog::CHANNEL_WHATSAPP)
            ->where('status', RideRequestNotificationLog::STATUS_SENT)
            ->where('detail', $logDetail)
            ->exists();
    }
}
