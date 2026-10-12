<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideRequestNotificationLog;
use App\Modules\NexaTaxi\Support\TaxiNotificationLogSchema;
use App\Services\WhatsAppBookingMessageComposer;
use App\Services\WhatsAppBusinessService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TaxiCustomerRideStatusNotificationService
{
    public const LOG_CONTEXT_PREFIX = 'customer_status';

    public function __construct(
        protected WhatsAppBookingMessageComposer $composer,
        protected WhatsAppBusinessService $whatsapp,
        protected TaxiRideNotificationLogService $notificationLogs,
        protected TaxiDispatchSettingsService $dispatchSettings
    ) {}

    /**
     * @param  array{
     *     driver_name?: string|null,
     *     driver_phone?: string|null,
     *     license_plate?: string|null,
     *     extra_lines?: list<string>,
     *     stopovers?: list<string>,
     *     return_at?: string|null,
     *     section_config?: array<string, mixed>
     * }  $context
     */
    public function notify(string $conn, RideRequest $ride, string $event, array $context = [], bool $force = false): bool
    {
        if ($ride->exists) {
            $ride = $ride->fresh() ?? $ride;
        }

        $companyId = (int) ($ride->company_id ?? 0);
        $settingsCompanyId = $companyId > 0 ? $companyId : null;

        if (! $this->dispatchSettings->customerWhatsappStatusEventEnabled($event, $settingsCompanyId)) {
            return false;
        }
        $rideId = (int) $ride->id;
        $logDetail = self::LOG_CONTEXT_PREFIX.':'.$event;

        // Herdispatch en afwijzing mogen meerdere keren; overige events zijn éénmalig per rit.
        $idempotent = ! in_array($event, [
            WhatsAppBookingMessageComposer::EVENT_REDISPATCHED,
            WhatsAppBookingMessageComposer::EVENT_DECLINED,
        ], true);
        if ($idempotent && ! $force && $this->alreadySent($conn, $rideId, $logDetail)) {
            return true;
        }

        $composed = $this->composer->composeStatus($ride, $event, $context, $settingsCompanyId);
        $customerName = trim((string) ($ride->customer_name ?: 'Klant'));
        $phone = trim((string) ($ride->customer_phone ?? ''));

        // App-tracking: status alleen in de app (timeline + lokale push), niet via WhatsApp.
        if ($this->rideUsesAppTracking($conn, $ride)) {
            $this->notificationLogs->record(
                $conn,
                $rideId,
                RideRequestNotificationLog::CHANNEL_APP,
                RideRequestNotificationLog::STATUS_SENT,
                $customerName,
                null,
                null,
                $logDetail,
                [
                    'event' => $event,
                    'mode' => 'app',
                    'status_label' => $composed['status_label'],
                    'body' => $composed['fallback_body'] ?? null,
                ]
            );

            return true;
        }

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
            ->whereIn('channel', [
                RideRequestNotificationLog::CHANNEL_WHATSAPP,
                RideRequestNotificationLog::CHANNEL_APP,
            ])
            ->where('status', RideRequestNotificationLog::STATUS_SENT)
            ->where('detail', $logDetail)
            ->exists();
    }

    /**
     * @return list<array{event: string, label: string, at: string|null, channel: string}>
     */
    public function statusTimeline(string $conn, int $rideId): array
    {
        if ($rideId <= 0 || ! TaxiNotificationLogSchema::tableExists($conn)) {
            return [];
        }

        $prefix = self::LOG_CONTEXT_PREFIX.':';
        $rows = RideRequestNotificationLog::on($conn)
            ->where('ride_request_id', $rideId)
            ->where('status', RideRequestNotificationLog::STATUS_SENT)
            ->whereIn('channel', [
                RideRequestNotificationLog::CHANNEL_APP,
                RideRequestNotificationLog::CHANNEL_WHATSAPP,
            ])
            ->where('detail', 'like', $prefix.'%')
            ->orderBy('id')
            ->get(['channel', 'detail', 'meta', 'created_at']);

        $labels = WhatsAppBookingMessageComposer::statusEventLabels();
        $out = [];
        foreach ($rows as $row) {
            $detail = (string) ($row->detail ?? '');
            $event = str_starts_with($detail, $prefix)
                ? substr($detail, strlen($prefix))
                : (string) (data_get($row->meta, 'event') ?? '');
            $event = trim(explode(':', $event, 2)[0]);
            if ($event === '') {
                continue;
            }
            $label = (string) (data_get($row->meta, 'status_label') ?: ($labels[$event] ?? $event));
            $out[] = [
                'event' => $event,
                'label' => $label,
                'at' => $row->created_at?->toIso8601String(),
                'channel' => (string) $row->channel,
            ];
        }

        return $out;
    }

    protected function rideUsesAppTracking(string $conn, RideRequest $ride): bool
    {
        $token = trim((string) ($ride->customer_track_token ?? ''));
        if ($token !== '') {
            return true;
        }

        if (! Schema::connection($conn)->hasColumn('ride_requests', 'customer_track_token')) {
            return false;
        }

        $fresh = RideRequest::on($conn)->whereKey($ride->id)->value('customer_track_token');

        return is_string($fresh) && trim($fresh) !== '';
    }
}
