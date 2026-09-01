<?php

namespace App\Modules\NexaTaxi\Services;

use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\ModuleDatabaseService;
use App\Services\WhatsAppBookingMessageComposer;
use App\Support\DutchPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class WhatsAppPickupProposalMockService
{
    public const NOTE_MARKER = '[whatsapp-mock]';

    public const MOCK_PHONE = '0611111111';

    public const MOCK_DRIVER_EMAIL = 'whatsapp-mock-driver@nexa.test';

    public const HIDDEN_PAYLOAD_KEY = 'whatsapp_pickup_proposal_mock_hidden';

    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected WhatsAppWebhookController $webhook,
        protected TaxiPickupProposalService $pickupProposals,
    ) {}

    public function inboundMockAllowed(): bool
    {
        $flag = config('whatsapp.inbound_mock_enabled');
        if (is_bool($flag)) {
            return $flag;
        }
        if (is_string($flag) && $flag !== '') {
            $normalized = strtolower(trim($flag));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        if (! app()->environment('production')) {
            return true;
        }

        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $liveHosts = config('whatsapp.live_webhook_hosts', ['nexasuite.nl', 'www.nexasuite.nl']);
        if (! is_array($liveHosts)) {
            $liveHosts = ['nexasuite.nl', 'www.nexasuite.nl'];
        }

        return $host === '' || ! in_array($host, $liveHosts, true);
    }

    public function environmentLabel(): string
    {
        if (! $this->inboundMockAllowed()) {
            return 'Productie';
        }

        if (app()->environment('local', 'testing', 'development')) {
            return 'Lokaal';
        }

        return 'Test';
    }

    public function taxiConnection(): string
    {
        try {
            $this->moduleDb->registerConnection('taxi');
        } catch (\Throwable) {
            // Connection kan al geregistreerd zijn.
        }

        return $this->moduleDb->getModuleConnectionName('taxi');
    }

    /**
     * @return Collection<int, RideRequest>
     */
    public function listMockRides(): Collection
    {
        $conn = $this->taxiConnection();
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);

        $rides = RideRequest::on($conn)
            ->where(function ($q) {
                $q->where('customer_note', 'like', '%'.self::NOTE_MARKER.'%')
                    ->orWhere(function ($q2) {
                        $q2->where('pickup_proposal_status', RideRequest::PICKUP_PROPOSAL_PENDING)
                            ->whereNotNull('pickup_proposal_at');
                    })
                    ->orWhere(function ($q2) {
                        $q2->whereIn('pickup_proposal_status', [
                            RideRequest::PICKUP_PROPOSAL_ACCEPTED,
                            RideRequest::PICKUP_PROPOSAL_DECLINED,
                        ])
                            ->where('pickup_proposal_responded_at', '>=', now()->subDays(2));
                    });
            })
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        return $rides
            ->reject(fn (RideRequest $ride) => $this->isHiddenFromMockList($ride))
            ->sort(function (RideRequest $a, RideRequest $b) {
                $aSent = $a->pickup_proposal_sent_at?->getTimestamp() ?? 0;
                $bSent = $b->pickup_proposal_sent_at?->getTimestamp() ?? 0;
                if ($aSent !== $bSent) {
                    return $bSent <=> $aSent;
                }

                return (int) $b->id <=> (int) $a->id;
            })
            ->take(40)
            ->values();
    }

    /**
     * @return array{rides: list<array<string, mixed>>, has_seeded_rides: bool, fingerprint: string}
     */
    public function listMockRidesPayload(): array
    {
        $rides = $this->listMockRides();
        $serialized = $rides->map(fn (RideRequest $ride) => $this->serializeMockRide($ride))->values()->all();

        return [
            'rides' => $serialized,
            'has_seeded_rides' => $rides->contains(fn (RideRequest $ride) => $this->isMockRide($ride)),
            'fingerprint' => implode('~', array_column($serialized, 'fingerprint')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMockRide(RideRequest $ride): array
    {
        $status = $ride->pickup_proposal_status;
        $proposalLabels = RideRequest::pickupProposalStatusLabels();
        $rideLabels = RideRequest::statusLabels();

        return [
            'id' => (int) $ride->id,
            'status' => (string) $ride->status,
            'status_label' => $rideLabels[$ride->status] ?? (string) $ride->status,
            'source' => $this->isMockRide($ride) ? 'testdata' : 'driver',
            'customer_name' => (string) ($ride->customer_name ?? ''),
            'customer_phone' => (string) ($ride->customer_phone ?? ''),
            'proposal_status' => $status,
            'proposal_label' => $proposalLabels[$status] ?? ($status ?: '—'),
            'pending' => $ride->hasPendingPickupProposal(),
            'declined' => $ride->hasDeclinedPickupProposal(),
            'wamid' => trim((string) ($ride->pickup_proposal_whatsapp_wamid ?? '')),
            'remark' => trim((string) ($ride->pickup_proposal_customer_remark ?? '')),
            'message_preview' => $this->messagePreview($ride),
            'sent_at' => $ride->pickup_proposal_sent_at?->toIso8601String(),
            'fingerprint' => implode('|', [
                (string) $ride->id,
                (string) $status,
                (string) ($ride->pickup_proposal_whatsapp_wamid ?? ''),
                (string) ($ride->pickup_proposal_customer_remark ?? ''),
                (string) ($ride->pickup_proposal_sent_at?->getTimestamp() ?? 0),
            ]),
        ];
    }

    public function messagePreview(RideRequest $ride): string
    {
        $driver = null;
        if ($ride->driver_id) {
            $driver = User::query()->find($ride->driver_id);
        }

        try {
            return app(WhatsAppBookingMessageComposer::class)
                ->pickupProposalPreviewForRide($ride, $driver);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @param  list<int>  $ids
     * @return array{deleted: int, hidden: int}
     */
    public function removeFromMockList(array $ids): array
    {
        $this->assertMockAllowed();

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id) => $id > 0)));
        $deleted = 0;
        $hidden = 0;
        if ($ids === []) {
            return compact('deleted', 'hidden');
        }

        $conn = $this->taxiConnection();
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);

        foreach ($ids as $id) {
            $ride = RideRequest::on($conn)->whereKey($id)->first();
            if (! $ride || $this->isHiddenFromMockList($ride) || ! $this->appearsOnMockList($ride)) {
                continue;
            }

            if ($this->isMockRide($ride)) {
                RideDispatchOffer::on($conn)->where('ride_request_id', $ride->id)->delete();
                $ride->delete();
                $deleted++;

                continue;
            }

            $this->hideFromMockList($conn, $ride);
            $hidden++;
        }

        return compact('deleted', 'hidden');
    }

    public function attachMockWamidIfMissing(string $conn, RideRequest $ride): string
    {
        $this->revealHiddenRide($conn, $ride);

        $existing = trim((string) ($ride->pickup_proposal_whatsapp_wamid ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        if (! $this->inboundMockAllowed()) {
            return '';
        }

        $wamid = 'wamid.NEXA-MOCK-DRIVER-'.$ride->id.'-'.Str::lower(Str::random(8));
        RideRequest::on($conn)->whereKey($ride->id)->update([
            'pickup_proposal_whatsapp_wamid' => $wamid,
        ]);
        $ride->pickup_proposal_whatsapp_wamid = $wamid;

        return $wamid;
    }

    /**
     * @return list<RideRequest>
     */
    public function seedMockRides(?int $companyId): array
    {
        $this->assertMockAllowed();

        if ($companyId === null || $companyId <= 0) {
            throw ValidationException::withMessages([
                'company' => ['Kies links in de zijbalk een tenant om testdata aan te maken.'],
            ]);
        }

        $company = Company::query()->find($companyId);
        if (! $company) {
            throw ValidationException::withMessages([
                'company' => ['Bedrijf niet gevonden.'],
            ]);
        }

        $conn = $this->taxiConnection();
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);
        TaxiDispatchSchema::ensureOfferArchiveColumn($conn);

        $driver = $this->resolveMockDriver($companyId);
        $batch = Str::lower(Str::random(6));
        $phone = self::MOCK_PHONE;

        $older = $this->createMockRide($conn, $companyId, $driver, $phone, $batch, 'A', now()->addDay(), now()->subMinutes(5));
        $newer = $this->createMockRide($conn, $companyId, $driver, $phone, $batch, 'B', now()->addDays(2), now());

        return [$older, $newer];
    }

    /**
     * @return array{handled: bool, ride: RideRequest, before: ?string, after: ?string}
     */
    public function simulateReply(int $rideId, string $action): array
    {
        $this->assertMockAllowed();

        $action = strtolower(trim($action));
        if (! in_array($action, ['accept', 'decline', 'remark'], true)) {
            throw ValidationException::withMessages([
                'action' => ['Ongeldige actie.'],
            ]);
        }

        $conn = $this->taxiConnection();
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);

        $ride = RideRequest::on($conn)->whereKey($rideId)->first();
        if (! $ride || ! $this->isSimulatableRide($ride)) {
            throw ValidationException::withMessages([
                'ride' => ['Geen testdata-rit of openstaand chauffeur-voorstel gevonden.'],
            ]);
        }

        $this->attachMockWamidIfMissing($conn, $ride);

        $before = $ride->pickup_proposal_status;
        $payload = $this->metaWebhookPayload($ride, $action);
        $httpRequest = Request::create('/api/whatsapp/webhook', 'POST', $payload);
        $this->webhook->handle($httpRequest, $this->moduleDb, $this->pickupProposals);

        $fresh = RideRequest::on($conn)->whereKey($ride->id)->first() ?? $ride;

        return [
            'handled' => $fresh->pickup_proposal_status !== $before
                || ($action === 'remark' && trim((string) $fresh->pickup_proposal_customer_remark) !== ''),
            'ride' => $fresh,
            'before' => $before,
            'after' => $fresh->pickup_proposal_status,
        ];
    }

    public function clearMockRides(): int
    {
        $this->assertMockAllowed();

        $conn = $this->taxiConnection();
        TaxiDispatchSchema::ensurePickupProposalColumns($conn);

        $ids = RideRequest::on($conn)
            ->where('customer_note', 'like', '%'.self::NOTE_MARKER.'%')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        RideDispatchOffer::on($conn)->whereIn('ride_request_id', $ids)->delete();
        RideRequest::on($conn)->whereIn('id', $ids)->delete();

        return $ids->count();
    }

    public function isMockRide(RideRequest $ride): bool
    {
        return str_contains((string) $ride->customer_note, self::NOTE_MARKER);
    }

    public function isHiddenFromMockList(RideRequest $ride): bool
    {
        $payload = $ride->booking_payload;

        return is_array($payload) && ! empty($payload[self::HIDDEN_PAYLOAD_KEY]);
    }

    private function appearsOnMockList(RideRequest $ride): bool
    {
        if ($this->isMockRide($ride)) {
            return true;
        }

        if ($ride->hasPendingPickupProposal() && $ride->pickup_proposal_at) {
            return true;
        }

        return $ride->hasDeclinedPickupProposal()
            || $ride->pickup_proposal_status === RideRequest::PICKUP_PROPOSAL_ACCEPTED;
    }

    private function hideFromMockList(string $conn, RideRequest $ride): void
    {
        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $payload[self::HIDDEN_PAYLOAD_KEY] = true;
        RideRequest::on($conn)->whereKey($ride->id)->update(['booking_payload' => $payload]);
        $ride->booking_payload = $payload;
    }

    private function revealHiddenRide(string $conn, RideRequest $ride): void
    {
        if (! $this->isHiddenFromMockList($ride)) {
            return;
        }

        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        unset($payload[self::HIDDEN_PAYLOAD_KEY]);
        RideRequest::on($conn)->whereKey($ride->id)->update(['booking_payload' => $payload]);
        $ride->booking_payload = $payload;
    }

    public function isSimulatableRide(RideRequest $ride): bool
    {
        if ($this->isMockRide($ride)) {
            return true;
        }

        if ($ride->hasPendingPickupProposal() && $ride->pickup_proposal_at) {
            return true;
        }

        return $ride->hasDeclinedPickupProposal()
            && $ride->pickup_proposal_responded_at
            && $ride->pickup_proposal_responded_at->gte(now()->subHours(6));
    }

    public function assertMockAllowed(): void
    {
        if (! $this->inboundMockAllowed()) {
            throw ValidationException::withMessages([
                'environment' => ['Op productie pakt de echte WhatsApp-webhook antwoorden op. Mocken is hier uitgeschakeld.'],
            ]);
        }
    }

    private function resolveMockDriver(int $companyId): User
    {
        $existing = User::query()->where('email', self::MOCK_DRIVER_EMAIL)->first();
        if ($existing) {
            if ((int) $existing->company_id !== $companyId) {
                $existing->forceFill(['company_id' => $companyId])->save();
            }

            return $existing;
        }

        $driver = User::query()->create([
            'first_name' => 'Mock',
            'last_name' => 'Chauffeur',
            'email' => self::MOCK_DRIVER_EMAIL,
            'password' => bcrypt(Str::random(24)),
            'company_id' => $companyId,
            'is_active' => true,
        ]);

        Role::findOrCreate('chauffeur', 'web');
        $driver->assignRole('chauffeur');

        return $driver;
    }

    private function createMockRide(
        string $conn,
        int $companyId,
        User $driver,
        string $phone,
        string $batch,
        string $label,
        \DateTimeInterface $proposalAt,
        \DateTimeInterface $sentAt,
    ): RideRequest {
        $wamid = 'wamid.NEXA-MOCK-'.$label.'-'.$batch;
        $ride = RideRequest::on($conn)->create([
            'company_id' => $companyId,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'source' => RideRequest::SOURCE_MANUAL,
            'pickup_address' => 'Mock ophaal '.$label.', Amsterdam',
            'dropoff_address' => 'Mock afzet '.$label.', Utrecht',
            'passengers' => 1,
            'pickup_at' => now('UTC')->subHours(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => \Illuminate\Support\Carbon::parse($proposalAt)->timezone('UTC')->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => \Illuminate\Support\Carbon::parse($sentAt),
            'pickup_proposal_whatsapp_wamid' => $wamid,
            'quoted_price' => 25.00,
            'customer_name' => 'Mock Klant '.$label,
            'customer_phone' => $phone,
            'customer_note' => self::NOTE_MARKER.' testdata '.$label.' (zelfde nummer, andere wamid).',
            'booking_payload' => [
                'whatsapp_pickup_proposal_mock' => true,
                'batch' => $batch,
                'label' => $label,
            ],
        ]);

        RideDispatchOffer::on($conn)->create([
            'ride_request_id' => $ride->id,
            'company_id' => $companyId,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_ACCEPTED,
            'offered_at' => now()->subHours(3),
            'expires_at' => now()->subHours(2),
            'responded_at' => now()->subHour(),
        ]);

        return $ride->fresh() ?? $ride;
    }

    /**
     * @return array<string, mixed>
     */
    private function metaWebhookPayload(RideRequest $ride, string $action): array
    {
        $from = $this->webhookFromPhone($ride);
        $wamid = trim((string) ($ride->pickup_proposal_whatsapp_wamid ?? ''));
        $message = [
            'from' => $from,
            'id' => 'wamid.NEXA-MOCK-IN-'.Str::lower(Str::random(8)),
            'timestamp' => (string) time(),
        ];
        if ($wamid !== '') {
            $message['context'] = [
                'from' => '15550000000',
                'id' => $wamid,
            ];
        }

        if ($action === 'remark') {
            $message['type'] = 'text';
            $message['text'] = ['body' => 'Graag 10 minuten later'];
        } else {
            $message['type'] = 'button';
            $message['button'] = [
                'payload' => '',
                'text' => $action === 'decline' ? 'Weigeren' : 'Accepteren',
            ];
        }

        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'nexa-mock',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '15550000000',
                            'phone_number_id' => 'mock',
                        ],
                        'contacts' => [[
                            'profile' => ['name' => (string) $ride->customer_name],
                            'wa_id' => $from,
                        ]],
                        'messages' => [$message],
                    ],
                ]],
            ]],
        ];
    }

    private function webhookFromPhone(RideRequest $ride): string
    {
        $stored = trim((string) ($ride->customer_phone ?? ''));
        if ($stored !== '') {
            $normalized = DutchPhoneNumber::normalizeOptionalNlToInternational($stored);
            if (is_string($normalized) && $normalized !== '') {
                return ltrim($normalized, '+');
            }
            $digits = preg_replace('/\D+/', '', $stored) ?: '';
            if ($digits !== '') {
                return $digits;
            }
        }

        return '31611111111';
    }
}
