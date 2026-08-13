<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Modules\NexaTaxi\Support\TaxiNotificationLogSchema;
use App\Modules\NexaTaxi\Models\RideRequestNotificationLog;
use App\Services\WhatsAppBookingMessageComposer;
use App\Services\WhatsAppBusinessService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Chauffeur stelt nieuw ophaalmoment voor; klant accepteert/weigert via WhatsApp-knoppen.
 */
class TaxiPickupProposalService
{
    public const LOG_CONTEXT = 'pickup_proposal';

    public const BUTTON_ACCEPT = 'pickup_accept';

    public const BUTTON_DECLINE = 'pickup_decline';

    public function __construct(
        protected WhatsAppBusinessService $whatsapp,
        protected WhatsAppBookingMessageComposer $composer,
        protected TaxiRideNotificationLogService $notificationLogs,
        protected TaxiDriverInboxPushService $driverPush,
    ) {}

    public function proposeNewPickup(string $conn, User $driver, int $rideId, string $pickupAtIso): RideRequest
    {
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);

        $fresh = \Illuminate\Support\Facades\DB::connection($conn)->transaction(function () use ($conn, $driver, $rideId, $pickupAtIso) {
            $ride = RideRequest::on($conn)->whereKey($rideId)->lockForUpdate()->first();
            if (! $ride || (int) $ride->driver_id !== (int) $driver->id) {
                throw ValidationException::withMessages(['ride' => ['Rit niet gevonden.']]);
            }

            if ($ride->status !== RideRequest::STATUS_ACCEPTED) {
                throw ValidationException::withMessages(['ride' => ['Alleen geaccepteerde ritten kunnen een nieuw tijdstip krijgen.']]);
            }

            if ($ride->isContractRide()) {
                throw ValidationException::withMessages(['ride' => ['Contractritten gebruiken geen ophaalvoorstel.']]);
            }

            $instant = Carbon::parse($pickupAtIso);
            if ($instant->lte(now())) {
                throw ValidationException::withMessages(['pickup_at' => ['Kies een ophaalmoment in de toekomst.']]);
            }

            $amsterdam = $instant->copy()->timezone(ContractTransportTimezone::TIMEZONE);
            $proposalAt = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $amsterdam->format('Y-m-d H:i:s'),
                'UTC'
            );

            $ride->update([
                'pickup_proposal_at' => $proposalAt,
                'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
                'pickup_proposal_customer_remark' => null,
                'pickup_proposal_sent_at' => now(),
                'pickup_proposal_responded_at' => null,
            ]);

            return $ride->fresh() ?? $ride;
        });

        $this->sendProposalWhatsapp($conn, $fresh, $driver);

        return $fresh;
    }

    public function sendProposalWhatsapp(string $conn, RideRequest $ride, ?User $driver = null): bool
    {
        $companyId = (int) ($ride->company_id ?? 0);
        $settingsCompanyId = $companyId > 0 ? $companyId : null;
        $phone = trim((string) ($ride->customer_phone ?? ''));
        $template = $this->composer->pickupProposalTemplateName();

        if ($phone === '' || $template === '') {
            $this->logProposal(
                $conn,
                (int) $ride->id,
                RideRequestNotificationLog::STATUS_SKIPPED,
                $phone !== '' ? $phone : null,
                $driver?->id,
                $template === ''
                    ? 'Geen ophaalvoorstel-template (WHATSAPP_PICKUP_PROPOSAL_TEMPLATE).'
                    : 'Geen klanttelefoon.'
            );

            return false;
        }

        if (! $this->whatsapp->isConfigured($settingsCompanyId)) {
            $this->logProposal(
                $conn,
                (int) $ride->id,
                RideRequestNotificationLog::STATUS_SKIPPED,
                $phone,
                $driver?->id,
                'WhatsApp Business API niet geconfigureerd.'
            );

            return false;
        }

        $params = $this->composer->pickupProposalBodyParameters($ride, $driver);
        $result = $this->whatsapp->sendTemplate(
            $phone,
            $template,
            $this->composer->pickupProposalTemplateLanguage(),
            $params,
            $settingsCompanyId
        );

        $ok = (bool) ($result['ok'] ?? false);
        $this->logProposal(
            $conn,
            (int) $ride->id,
            $ok ? RideRequestNotificationLog::STATUS_SENT : RideRequestNotificationLog::STATUS_FAILED,
            $phone,
            $driver?->id,
            $ok ? null : (string) ($result['error'] ?? 'Verzenden mislukt'),
            [
                'mode' => 'template:'.$template,
                'proposal_at' => ContractTransportTimezone::toDriverIso8601($ride->pickup_proposal_at),
            ]
        );

        return $ok;
    }

    /**
     * Verwerk knopantwoord of vrije tekst van de klant.
     *
     * @param  array<string, mixed>  $message
     */
    public function handleInboundCustomerMessage(string $conn, array $message): bool
    {
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);

        $from = (string) ($message['from'] ?? '');
        if ($from === '') {
            return false;
        }

        $ride = $this->findPendingProposalRideByPhone($conn, $from);
        if (! $ride) {
            return false;
        }

        $button = $this->extractButtonPayload($message);
        if ($button !== null) {
            if ($this->isAcceptPayload($button)) {
                $this->acceptProposal($conn, $ride);

                return true;
            }
            if ($this->isDeclinePayload($button)) {
                $this->declineProposal($conn, $ride);

                return true;
            }
        }

        $text = trim((string) data_get($message, 'text.body', ''));
        if ($text !== '') {
            $this->attachCustomerRemark($conn, $ride, $text);

            return true;
        }

        return false;
    }

    public function acceptProposal(string $conn, RideRequest $ride): RideRequest
    {
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);

        $fresh = \Illuminate\Support\Facades\DB::connection($conn)->transaction(function () use ($conn, $ride) {
            $locked = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->first() ?? $ride;

            if ($locked->pickup_proposal_status !== RideRequest::PICKUP_PROPOSAL_PENDING || ! $locked->pickup_proposal_at) {
                return $locked;
            }

            $locked->update([
                'pickup_at' => $locked->pickup_proposal_at,
                'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_ACCEPTED,
                'pickup_proposal_responded_at' => now(),
            ]);

            return $locked->fresh() ?? $locked;
        });

        $this->alertDriver($fresh, 'accepted');

        return $fresh;
    }

    public function declineProposal(string $conn, RideRequest $ride, ?string $remark = null): RideRequest
    {
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);

        $fresh = \Illuminate\Support\Facades\DB::connection($conn)->transaction(function () use ($conn, $ride, $remark) {
            $locked = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->first() ?? $ride;

            if ($locked->pickup_proposal_status !== RideRequest::PICKUP_PROPOSAL_PENDING) {
                return $locked;
            }

            $updates = [
                'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_DECLINED,
                'pickup_proposal_responded_at' => now(),
            ];
            if ($remark !== null && trim($remark) !== '') {
                $updates['pickup_proposal_customer_remark'] = mb_substr(trim($remark), 0, 1000);
            }

            $locked->update($updates);

            return $locked->fresh() ?? $locked;
        });

        $this->alertDriver($fresh, 'declined');

        return $fresh;
    }

    public function attachCustomerRemark(string $conn, RideRequest $ride, string $remark): RideRequest
    {
        $remark = mb_substr(trim($remark), 0, 1000);
        if ($remark === '') {
            return $ride;
        }

        $ride->update(['pickup_proposal_customer_remark' => $remark]);
        $fresh = $ride->fresh() ?? $ride;

        if ($fresh->pickup_proposal_status === RideRequest::PICKUP_PROPOSAL_PENDING) {
            // Opmerking vóór knop: bewaar, chauffeur ziet het na poll; geen statuswissel.
            $this->alertDriver($fresh, 'remark');
        } elseif ($fresh->pickup_proposal_status === RideRequest::PICKUP_PROPOSAL_DECLINED) {
            $this->alertDriver($fresh, 'remark');
        }

        return $fresh;
    }

    private function findPendingProposalRideByPhone(string $conn, string $waFrom): ?RideRequest
    {
        $digits = preg_replace('/\D+/', '', $waFrom) ?: '';
        if ($digits === '') {
            return null;
        }

        $candidates = RideRequest::on($conn)
            ->where('status', RideRequest::STATUS_ACCEPTED)
            ->where('pickup_proposal_status', RideRequest::PICKUP_PROPOSAL_PENDING)
            ->whereNotNull('customer_phone')
            ->orderByDesc('pickup_proposal_sent_at')
            ->limit(25)
            ->get();

        foreach ($candidates as $ride) {
            $rideDigits = preg_replace('/\D+/', '', (string) $ride->customer_phone) ?: '';
            if ($rideDigits === '') {
                continue;
            }
            if ($rideDigits === $digits
                || str_ends_with($digits, $rideDigits)
                || str_ends_with($rideDigits, $digits)) {
                return $ride;
            }
        }

        // Ook recent geweigerd: opmerking nagestuurd.
        $declined = RideRequest::on($conn)
            ->where('status', RideRequest::STATUS_ACCEPTED)
            ->where('pickup_proposal_status', RideRequest::PICKUP_PROPOSAL_DECLINED)
            ->whereNotNull('customer_phone')
            ->where('pickup_proposal_responded_at', '>=', now()->subHours(6))
            ->orderByDesc('pickup_proposal_responded_at')
            ->limit(10)
            ->get();

        foreach ($declined as $ride) {
            $rideDigits = preg_replace('/\D+/', '', (string) $ride->customer_phone) ?: '';
            if ($rideDigits !== '' && ($rideDigits === $digits || str_ends_with($digits, $rideDigits) || str_ends_with($rideDigits, $digits))) {
                return $ride;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function extractButtonPayload(array $message): ?string
    {
        $interactive = data_get($message, 'interactive.button_reply.id')
            ?? data_get($message, 'interactive.button_reply.title')
            ?? data_get($message, 'button.payload')
            ?? data_get($message, 'button.text');

        if (! is_string($interactive) || trim($interactive) === '') {
            return null;
        }

        return trim($interactive);
    }

    private function isAcceptPayload(string $payload): bool
    {
        $p = mb_strtolower($payload);

        return $p === self::BUTTON_ACCEPT
            || str_contains($p, 'pickup_accept')
            || $p === 'accepteren'
            || str_contains($p, 'accept');
    }

    private function isDeclinePayload(string $payload): bool
    {
        $p = mb_strtolower($payload);

        return $p === self::BUTTON_DECLINE
            || str_contains($p, 'pickup_decline')
            || $p === 'weigeren'
            || str_contains($p, 'decline')
            || str_contains($p, 'weig');
    }

    private function alertDriver(RideRequest $ride, string $decision): void
    {
        $driverId = (int) ($ride->driver_id ?? 0);
        if ($driverId <= 0) {
            return;
        }

        $this->driverPush->notifyDriver($driverId, (int) $ride->id);

        $label = match ($decision) {
            'accepted' => 'Klant heeft het nieuwe ophaalmoment geaccepteerd.',
            'declined' => 'Klant heeft het nieuwe ophaalmoment geweigerd.',
            'remark' => 'Klant stuurde een opmerking over het ophaalvoorstel.',
            default => 'Update op ophaalvoorstel.',
        };

        $remark = trim((string) ($ride->pickup_proposal_customer_remark ?? ''));
        if ($remark !== '') {
            $label .= ' Opmerking: '.$remark;
        }

        Cache::put(
            'taxi_driver_pickup_proposal_alert:'.$driverId,
            [
                'ride_id' => (int) $ride->id,
                'decision' => $decision,
                'message' => $label,
                'proposal_status' => $ride->pickup_proposal_status,
                'remark' => $remark !== '' ? $remark : null,
            ],
            now()->addMinutes(30)
        );

        Log::info('Pickup proposal driver alert.', [
            'ride_id' => $ride->id,
            'driver_id' => $driverId,
            'decision' => $decision,
        ]);
    }

    private function logProposal(
        string $conn,
        int $rideId,
        string $status,
        ?string $phone,
        ?int $driverId,
        ?string $error = null,
        array $meta = []
    ): void {
        if ($rideId <= 0 || ! TaxiNotificationLogSchema::tableExists($conn)) {
            return;
        }

        $this->notificationLogs->record(
            $conn,
            $rideId,
            RideRequestNotificationLog::CHANNEL_WHATSAPP,
            $status,
            null,
            $phone,
            $driverId,
            $error ? self::LOG_CONTEXT.': '.$error : self::LOG_CONTEXT,
            array_merge(['context' => self::LOG_CONTEXT], $meta)
        );
    }
}
