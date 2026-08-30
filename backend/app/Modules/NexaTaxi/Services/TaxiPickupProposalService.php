<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideRequestNotificationLog;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Modules\NexaTaxi\Support\TaxiNotificationLogSchema;
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
                'pickup_proposal_whatsapp_wamid' => null,
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

        $ok = false;

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
        } elseif (! $this->whatsapp->isConfigured($settingsCompanyId)) {
            $this->logProposal(
                $conn,
                (int) $ride->id,
                RideRequestNotificationLog::STATUS_SKIPPED,
                $phone,
                $driver?->id,
                'WhatsApp Business API niet geconfigureerd.'
            );
        } else {
            $params = $this->composer->pickupProposalBodyParameters($ride, $driver);
            $result = $this->whatsapp->sendTemplate(
                $phone,
                $template,
                $this->composer->pickupProposalTemplateLanguage(),
                $params,
                $settingsCompanyId
            );

            $ok = (bool) ($result['ok'] ?? false);
            $wamid = is_string($result['wamid'] ?? null) ? trim((string) $result['wamid']) : '';
            if ($ok && $wamid !== '') {
                RideRequest::on($conn)->whereKey($ride->id)->update([
                    'pickup_proposal_whatsapp_wamid' => $wamid,
                ]);
                $ride->pickup_proposal_whatsapp_wamid = $wamid;
            }

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
                    'wamid' => $wamid !== '' ? $wamid : null,
                ]
            );
        }

        app(WhatsAppPickupProposalMockService::class)->attachMockWamidIfMissing($conn, $ride);

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

        $ride = $this->findOpenProposalRide($conn, $message, $from);
        if (! $ride) {
            Log::info('Pickup proposal WhatsApp-antwoord: geen openstaande rit voor dit bericht.', [
                'from' => $from,
                'type' => $message['type'] ?? null,
                'context_id' => $this->extractContextWamid($message),
            ]);

            return false;
        }

        $decision = $this->extractDecision($message);
        if ($decision === 'accept') {
            $this->acceptProposal($conn, $ride);

            return true;
        }
        if ($decision === 'decline') {
            $this->declineProposal($conn, $ride);

            return true;
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

        return $this->reopenIfUnassignedAfterCustomerReply($conn, $fresh, true);
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

        return $this->reopenIfUnassignedAfterCustomerReply($conn, $fresh, false);
    }

    private function reopenIfUnassignedAfterCustomerReply(string $conn, RideRequest $ride, bool $customerAccepted): RideRequest
    {
        if ($ride->driver_id) {
            return $ride;
        }

        return app(RideClaimService::class)->reopenAfterArchivedPickupProposal($conn, $ride, $customerAccepted);
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

    /**
     * Eerst context.id (wamid van het voorstel), daarna telefoon als fallback.
     *
     * @param  array<string, mixed>  $message
     */
    private function findOpenProposalRide(string $conn, array $message, string $from): ?RideRequest
    {
        $wamid = $this->extractContextWamid($message);
        if ($wamid === null) {
            return $this->findPendingProposalRideByPhone($conn, $from);
        }

        $matched = $this->findRideByProposalWamid($conn, $wamid);
        if ($matched === null) {
            Log::info('Pickup proposal WhatsApp-antwoord: context.id onbekend, val terug op telefoon.', [
                'from' => $from,
                'context_id' => $wamid,
            ]);

            return $this->findPendingProposalRideByPhone($conn, $from);
        }

        if (! $this->isOpenForCustomerReply($matched)) {
            Log::info('Pickup proposal WhatsApp-antwoord: context.id hoort bij een rit die niet meer openstaat.', [
                'from' => $from,
                'context_id' => $wamid,
                'ride_id' => $matched->id,
                'proposal_status' => $matched->pickup_proposal_status,
            ]);

            return null;
        }

        return $matched;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function extractContextWamid(array $message): ?string
    {
        $id = data_get($message, 'context.id');
        if (! is_string($id)) {
            return null;
        }

        $id = trim($id);

        return $id !== '' ? $id : null;
    }

    private function findRideByProposalWamid(string $conn, string $wamid): ?RideRequest
    {
        return RideRequest::on($conn)
            ->where('pickup_proposal_whatsapp_wamid', $wamid)
            ->first();
    }

    private function isOpenForCustomerReply(RideRequest $ride): bool
    {
        if ($ride->pickup_proposal_status === RideRequest::PICKUP_PROPOSAL_PENDING
            && in_array($ride->status, [
                RideRequest::STATUS_ACCEPTED,
                RideRequest::STATUS_PENDING_DISPATCH,
                RideRequest::STATUS_OFFERED,
            ], true)) {
            return true;
        }

        if ($ride->pickup_proposal_status === RideRequest::PICKUP_PROPOSAL_DECLINED
            && $ride->status === RideRequest::STATUS_ACCEPTED
            && $ride->pickup_proposal_responded_at
            && $ride->pickup_proposal_responded_at->gte(now()->subHours(6))) {
            return true;
        }

        return false;
    }

    private function findPendingProposalRideByPhone(string $conn, string $waFrom): ?RideRequest
    {
        $digits = preg_replace('/\D+/', '', $waFrom) ?: '';
        if ($digits === '') {
            return null;
        }

        $candidates = RideRequest::on($conn)
            ->where('pickup_proposal_status', RideRequest::PICKUP_PROPOSAL_PENDING)
            ->whereIn('status', [
                RideRequest::STATUS_ACCEPTED,
                RideRequest::STATUS_PENDING_DISPATCH,
                RideRequest::STATUS_OFFERED,
            ])
            ->whereNotNull('customer_phone')
            ->orderByDesc('pickup_proposal_sent_at')
            ->limit(25)
            ->get();

        foreach ($candidates as $ride) {
            if ($this->phonesMatch($waFrom, (string) $ride->customer_phone)) {
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
            if ($this->phonesMatch($waFrom, (string) $ride->customer_phone)) {
                return $ride;
            }
        }

        return null;
    }

    /**
     * WhatsApp stuurt 316…; in de rit staat vaak 06…. Zelfde normalisatie als bij verzenden.
     */
    private function phonesMatch(string $waFrom, string $stored): bool
    {
        $a = $this->whatsapp->normalizeRecipientForApi($waFrom);
        $b = $this->whatsapp->normalizeRecipientForApi($stored);
        if (is_string($a) && is_string($b) && $a !== '' && $a === $b) {
            return true;
        }

        $aDigits = preg_replace('/\D+/', '', $a ?? $waFrom) ?: '';
        $bDigits = preg_replace('/\D+/', '', $b ?? $stored) ?: '';
        if ($aDigits === '' || $bDigits === '') {
            return false;
        }
        if ($aDigits === $bDigits) {
            return true;
        }

        $aTail = substr($aDigits, -8);
        $bTail = substr($bDigits, -8);

        return strlen($aDigits) >= 8 && strlen($bDigits) >= 8 && $aTail !== '' && $aTail === $bTail;
    }

    /**
     * Quick Reply komt binnen als knop (interactive/button) of als gewone tekst "Accepteren".
     * Meta vult vaak geen aparte payload in: dan is de knoptekst het antwoord.
     *
     * @param  array<string, mixed>  $message
     */
    private function extractDecision(array $message): ?string
    {
        $numericFallback = null;

        foreach ($this->decisionCandidateStrings($message) as $candidate) {
            $decision = $this->payloadToDecision($candidate);
            if ($decision === null) {
                continue;
            }
            if (preg_match('/^\d+$/', $candidate) === 1) {
                $numericFallback ??= $decision;

                continue;
            }

            return $decision;
        }

        return $numericFallback;
    }

    /**
     * @param  array<string, mixed>  $message
     * @return list<string>
     */
    private function decisionCandidateStrings(array $message): array
    {
        $raw = [
            data_get($message, 'interactive.button_reply.id'),
            data_get($message, 'interactive.button_reply.title'),
            data_get($message, 'button.payload'),
            data_get($message, 'button.text'),
            data_get($message, 'text.body'),
        ];

        $out = [];
        foreach ($raw as $candidate) {
            if (is_int($candidate) || is_float($candidate)) {
                $candidate = (string) $candidate;
            }
            if (! is_string($candidate)) {
                continue;
            }
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }
            $out[] = $candidate;
        }

        return $out;
    }

    private function payloadToDecision(string $payload): ?string
    {
        $p = mb_strtolower(trim($payload));
        if ($p === '') {
            return null;
        }

        if ($this->isDeclinePayload($p)) {
            return 'decline';
        }
        if ($this->isAcceptPayload($p)) {
            return 'accept';
        }
        // Meta stuurt soms alleen de knopindex (eerste knop = Accepteren).
        if ($p === '0') {
            return 'accept';
        }
        if ($p === '1') {
            return 'decline';
        }

        return $this->decisionFromFreeText($payload);
    }

    private function decisionFromFreeText(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        $firstLine = trim(strtok(str_replace(["\r\n", "\r"], "\n", $text), "\n") ?: $text);
        $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', $firstLine) ?? $firstLine);
        $normalized = trim($normalized, " \t\"'«»“”");

        $decline = ['weigeren', 'decline', 'pickup_decline', 'nee'];
        $accept = ['accepteren', 'accept', 'pickup_accept', 'ja', 'akkoord'];

        if (in_array($normalized, $decline, true)) {
            return 'decline';
        }
        if (in_array($normalized, $accept, true)) {
            return 'accept';
        }

        return null;
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
