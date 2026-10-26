<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RideDispatchService
{
    public function __construct(
        protected TaxiDriverEligibilityService $drivers,
        protected TaxiDriverInboxPushService $push,
        protected TaxiDispatchSettingsService $dispatchSettings
    ) {}

    /**
     * @param  bool  $enforcePickupRadius  true = alleen chauffeurs binnen radius van ophaallocatie (GPS)
     * @param  string  $radiusMode  network = owner network-radius; marketplace = booking marketplace radius
     */
    public function startDispatch(
        string $conn,
        RideRequest $ride,
        int $companyId,
        array $excludeDriverIds = [],
        bool $assignCompany = true,
        bool $enforcePickupRadius = false,
        string $radiusMode = 'network',
    ): void {
        if ($companyId <= 0) {
            return;
        }

        if (! TaxiDispatchSchema::tablesExist($conn)) {
            Log::warning('Taxi dispatch overgeslagen: migratie ontbreekt (driver_availability / ride_dispatch_offers).', [
                'connection' => $conn,
                'ride_request_id' => $ride->id,
            ]);

            return;
        }

        $ttl = $this->dispatchSettings->offerTtlSeconds($companyId);
        $batch = (int) config('taxi-dispatch.offer_batch_size', 8);
        $exclude = array_values(array_unique(array_map('intval', $excludeDriverIds)));

        $pickupLat = null;
        $pickupLng = null;
        $maxKm = null;
        if ($enforcePickupRadius) {
            $radius = $radiusMode === 'marketplace'
                ? $this->marketplaceRadiusFilter($ride)
                : $this->networkRadiusFilter($ride);
            if ($radius === null) {
                Log::info('Dispatch overgeslagen: geen bruikbare pickup-coördinaten of radius.', [
                    'ride_request_id' => $ride->id,
                    'company_id' => $companyId,
                    'radius_mode' => $radiusMode,
                ]);

                return;
            }
            [$pickupLat, $pickupLng, $maxKm] = $radius;
        }

        $driverIds = array_values(array_filter(
            $this->driverIdsForOffer(
                $conn,
                $companyId,
                $batch,
                $enforcePickupRadius,
                $pickupLat,
                $pickupLng,
                $maxKm
            ),
            fn (int $driverId) => ! in_array($driverId, $exclude, true)
        ));
        $driverIds = $this->driverIdsForPassengerCapacity($conn, $companyId, $ride, $driverIds);

        if ($driverIds === []) {
            if ($enforcePickupRadius) {
                Log::info('Dispatch: geen chauffeurs binnen radius.', [
                    'ride_request_id' => $ride->id,
                    'company_id' => $companyId,
                    'max_radius_km' => $maxKm,
                    'pickup_lat' => $pickupLat,
                    'pickup_lng' => $pickupLng,
                    'radius_mode' => $radiusMode,
                ]);
            }

            return;
        }

        DB::connection($conn)->transaction(function () use ($conn, $ride, $companyId, $driverIds, $ttl, $assignCompany) {
            $locked = RideRequest::on($conn)->whereKey($ride->id)->lockForUpdate()->first();
            if (! $locked || $locked->driver_id || $locked->hasPendingPickupProposal()) {
                return;
            }

            $now = now();
            $expires = $now->copy()->addSeconds($ttl);
            $wave = 1;

            foreach ($driverIds as $driverId) {
                if ($this->driverDeclinedRide($conn, (int) $ride->id, $driverId)) {
                    continue;
                }

                RideDispatchOffer::on($conn)->updateOrCreate(
                    [
                        'ride_request_id' => $ride->id,
                        'driver_id' => $driverId,
                    ],
                    [
                        'company_id' => $companyId,
                        'status' => RideDispatchOffer::STATUS_PENDING,
                        'wave' => $wave,
                        'offered_at' => $now,
                        'expires_at' => $expires,
                        'responded_at' => null,
                        'archived_at' => null,
                    ]
                );
            }

            $updates = [
                'status' => RideRequest::STATUS_OFFERED,
            ];
            if ($assignCompany && ! $locked->company_id) {
                $updates['company_id'] = $companyId;
            }
            $locked->update($updates);
        });

        $this->push->notifyDrivers($driverIds, (int) $ride->id);
        $this->pushExpoOffers($conn, $ride, $driverIds);
    }

    /**
     * @param  list<int>  $companyIds
     */
    public function startDispatchForCompanies(
        string $conn,
        RideRequest $ride,
        array $companyIds,
        bool $assignCompany = true
    ): void {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $companyIds),
            static fn (int $id): bool => $id > 0
        )));

        $seed = $ride->fresh() ?? $ride;
        if ($seed->isOwnTenantFleetRide()) {
            $ownerId = (int) $seed->company_id;
            if ($ownerId > 0) {
                $ids = [$ownerId];
            }
        }

        foreach ($ids as $companyId) {
            $current = $ride->fresh() ?? $ride;
            if ($current->driver_id) {
                return;
            }
            // Marktplaats (native app / NEXA Suite) en partners: alleen binnen de straal.
            // Eigen website/tenant: elke online chauffeur, zonder marktplaatsstraal.
            // Niemand online: fallback naar de chauffeur-rol.
            $limitToRadius = ! $current->isOwnTenantFleetRide()
                && (! $assignCompany || $this->rideRequiresMarketplaceRadius($current));
            $this->startDispatch(
                $conn,
                $current,
                $companyId,
                [],
                $assignCompany,
                $limitToRadius,
                $limitToRadius && $this->rideRequiresMarketplaceRadius($current) ? 'marketplace' : 'network'
            );
        }

        $fresh = $ride->fresh() ?? $ride;
        if (! $fresh->driver_id) {
            $this->dispatchOwnRideToNetworkIfFleetFull($conn, $fresh);
        }
    }

    /**
     * Offer a network ride to partner companies without assigning ownership (assignCompany=false).
     * Alleen partner-chauffeurs binnen max. radius (GPS) van de ophaallocatie.
     *
     * @param  list<int>|null  $partnerCompanyIds  null = use owner network settings
     */
    public function startNetworkPartnerDispatch(string $conn, RideRequest $ride, ?array $partnerCompanyIds = null): void
    {
        $ownerId = (int) ($ride->company_id ?? 0);
        if ($ownerId <= 0 || ! $this->dispatchSettings->networkEnabled($ownerId)) {
            return;
        }

        $mode = $this->dispatchSettings->networkMode($ownerId);
        if ($mode === TaxiDispatchSettingsService::NETWORK_MODE_OFF) {
            return;
        }

        $partners = $partnerCompanyIds ?? $this->dispatchSettings->networkPartnerCompanyIds($ownerId);
        $partners = array_values(array_filter(
            array_map('intval', $partners),
            static fn (int $id): bool => $id > 0 && $id !== $ownerId
        ));

        if ($partners === []) {
            return;
        }

        foreach ($partners as $companyId) {
            $current = $ride->fresh() ?? $ride;
            if ($current->driver_id) {
                return;
            }
            // Never let partner dispatch claim ownership; enforce network radius.
            $this->startDispatch($conn, $current, $companyId, [], false, true, 'network');
        }
    }

    public function expireStaleOffers(string $conn, ?int $rideId = null): int
    {
        if (! TaxiDispatchSchema::tablesExist($conn)) {
            return 0;
        }

        $query = RideDispatchOffer::on($conn)
            ->where('status', RideDispatchOffer::STATUS_PENDING)
            ->where('expires_at', '<=', now());

        if ($rideId) {
            $query->where('ride_request_id', $rideId);
        }

        $rideIds = (clone $query)->pluck('ride_request_id')->unique()->map(fn ($id) => (int) $id)->all();

        $count = $query->update([
            'status' => RideDispatchOffer::STATUS_EXPIRED,
            'responded_at' => now(),
        ]);

        foreach ($rideIds as $waitingRideId) {
            $this->escalateWaitingRide($conn, $waitingRideId);
        }

        return $count;
    }

    /**
     * Rit heeft nog geen chauffeur: verleng aanbod + waarschuw alle online chauffeurs (gedebounced).
     */
    public function escalateWaitingRide(string $conn, int $rideRequestId): void
    {
        if ($rideRequestId <= 0 || ! TaxiDispatchSchema::tablesExist($conn)) {
            return;
        }

        $debounceKey = 'taxi_waiting_escalation:'.$conn.':'.$rideRequestId;
        if (Cache::has($debounceKey)) {
            return;
        }
        Cache::put($debounceKey, 1, 45);

        $ride = RideRequest::on($conn)->find($rideRequestId);
        if (! $ride || $ride->driver_id) {
            return;
        }

        if (! in_array($ride->status, [RideRequest::STATUS_PENDING_DISPATCH, RideRequest::STATUS_OFFERED], true)) {
            return;
        }

        $companyIds = (int) $ride->company_id > 0
            ? [(int) $ride->company_id]
            : $ride->marketplaceCandidateCompanyIds();

        // Netwerk alleen als de eigen vloot geen vrij voertuig meer heeft (niet op tijd).
        $ownerId = (int) ($ride->company_id ?? 0);
        $partnerCompanyIds = [];
        if ($ownerId > 0
            && $ride->isOwnTenantFleetRide()
            && $this->dispatchSettings->networkMode($ownerId) === TaxiDispatchSettingsService::NETWORK_MODE_AUTO
            && ! $this->ownFleetHasFreeCapacity($conn, $ownerId)
        ) {
            foreach ($this->dispatchSettings->networkPartnerCompanyIds($ownerId) as $partnerId) {
                if ($partnerId !== $ownerId) {
                    $partnerCompanyIds[] = $partnerId;
                }
            }
        }

        $companyIds = array_values(array_unique(array_filter(
            array_map('intval', array_merge($companyIds, $partnerCompanyIds)),
            static fn (int $id): bool => $id > 0
        )));
        if ($companyIds === []) {
            return;
        }

        $pickupCutoffCompanyId = $companyIds[0];
        $pickupCutoff = $this->dispatchSettings->pickupQueueCutoffAt($pickupCutoffCompanyId);
        if ($ride->pickup_at && $ride->pickup_at->lt($pickupCutoff)) {
            return;
        }

        if ($this->allOffersDeclinedForRide($conn, $rideRequestId)) {
            return;
        }

        $now = now();
        $driverIdsToNotify = [];
        $partnerLookup = array_fill_keys($partnerCompanyIds, true);
        $partnerRadius = $partnerCompanyIds !== [] ? $this->networkRadiusFilter($ride) : null;
        $marketplaceRadius = $this->rideRequiresMarketplaceRadius($ride)
            ? $this->marketplaceRadiusFilter($ride)
            : null;

        foreach ($companyIds as $companyId) {
            $ttl = $this->dispatchSettings->offerTtlSeconds($companyId);
            $batch = (int) config('taxi-dispatch.offer_batch_size', 8);
            $isPartner = isset($partnerLookup[$companyId]);
            if ($isPartner && $partnerRadius === null) {
                continue;
            }

            $pickupLat = null;
            $pickupLng = null;
            $maxKm = null;
            $enforceRadius = false;
            if ($isPartner) {
                [$pickupLat, $pickupLng, $maxKm] = $partnerRadius;
                $enforceRadius = true;
            } elseif ($marketplaceRadius !== null) {
                [$pickupLat, $pickupLng, $maxKm] = $marketplaceRadius;
                $enforceRadius = true;
            }

            $driverIds = $this->driverIdsForOffer(
                $conn,
                $companyId,
                $batch,
                $enforceRadius,
                $pickupLat,
                $pickupLng,
                $maxKm,
            );
            $expires = $now->copy()->addSeconds($ttl);

            foreach ($driverIds as $driverId) {
                if ($this->driverDeclinedRide($conn, (int) $ride->id, $driverId)) {
                    continue;
                }

                RideDispatchOffer::on($conn)->updateOrCreate(
                    [
                        'ride_request_id' => $ride->id,
                        'driver_id' => $driverId,
                    ],
                    [
                        'company_id' => $companyId,
                        'status' => RideDispatchOffer::STATUS_PENDING,
                        'wave' => 1,
                        'offered_at' => $now,
                        'expires_at' => $expires,
                        'responded_at' => null,
                        'archived_at' => null,
                    ]
                );

                $driverIdsToNotify[] = $driverId;
            }
        }

        $driverIdsToNotify = array_values(array_unique($driverIdsToNotify));
        foreach ($driverIdsToNotify as $driverId) {
            $this->push->notifyDriver($driverId, (int) $ride->id);
        }
        if ($driverIdsToNotify !== []) {
            $this->pushExpoOffers($conn, $ride, $driverIdsToNotify);
        }

        if ($ride->status === RideRequest::STATUS_PENDING_DISPATCH && $driverIdsToNotify !== []) {
            $ride->update(['status' => RideRequest::STATUS_OFFERED]);
        }
    }

    /**
     * Zorg dat openstaande ritten een (vernieuwd) aanbod hebben voor deze chauffeur (inbox/polling).
     */
    public function syncPendingOffersForDriver(string $conn, int $companyId, int $driverId): void
    {
        if ($companyId <= 0 || $driverId <= 0 || ! TaxiDispatchSchema::tablesExist($conn)) {
            return;
        }

        $driver = \App\Models\User::query()->find($driverId);
        if (! $driver || ! $this->drivers->isChauffeurForCompany($driver, $companyId)) {
            return;
        }

        $ttl = $this->dispatchSettings->offerTtlSeconds($companyId);
        $pickupCutoff = $this->dispatchSettings->pickupQueueCutoffAt($companyId);
        $now = now();
        $expires = $now->copy()->addSeconds($ttl);

        $rides = RideRequest::on($conn)
            ->ownedOrUnclaimedMarketplaceForCompany($companyId)
            ->whereNull('driver_id')
            ->whereIn('status', [RideRequest::STATUS_PENDING_DISPATCH, RideRequest::STATUS_OFFERED])
            ->withoutPendingPickupProposal()
            ->dispatchPickupWithinQueueWindow($pickupCutoff)
            ->orderBy('pickup_at')
            ->limit(20)
            ->get();

        foreach ($rides as $ride) {
            if ($this->driverDeclinedRide($conn, (int) $ride->id, $driverId)) {
                continue;
            }

            if (! $this->driverFitsPassengerCapacity($conn, $companyId, $driverId, $ride)) {
                RideDispatchOffer::on($conn)
                    ->where('ride_request_id', $ride->id)
                    ->where('driver_id', $driverId)
                    ->where('status', RideDispatchOffer::STATUS_PENDING)
                    ->update([
                        'status' => RideDispatchOffer::STATUS_EXPIRED,
                        'responded_at' => $now,
                    ]);
                continue;
            }

            // Marktplaats (native app / NEXA Suite): alleen binnen de ingestelde straal.
            if ($this->rideRequiresMarketplaceRadius($ride)) {
                $radius = $this->marketplaceRadiusFilter($ride);
                if ($radius === null || ! $this->driverWithinPickupRadius($conn, $driverId, $radius)) {
                    continue;
                }
            }

            $hasActive = RideDispatchOffer::on($conn)
                ->where('ride_request_id', $ride->id)
                ->where('driver_id', $driverId)
                ->where('status', RideDispatchOffer::STATUS_PENDING)
                ->where('expires_at', '>', $now)
                ->exists();

            if ($hasActive) {
                continue;
            }

            RideDispatchOffer::on($conn)->updateOrCreate(
                [
                    'ride_request_id' => $ride->id,
                    'driver_id' => $driverId,
                ],
                [
                    'company_id' => $companyId,
                    'status' => RideDispatchOffer::STATUS_PENDING,
                    'wave' => 1,
                    'offered_at' => $now,
                    'expires_at' => $expires,
                    'responded_at' => null,
                    'archived_at' => null,
                ]
            );

            if ($ride->status === RideRequest::STATUS_PENDING_DISPATCH) {
                $ride->update(['status' => RideRequest::STATUS_OFFERED]);
            }

            $this->push->notifyDriver($driverId, (int) $ride->id);
            $this->pushExpoOffers($conn, $ride, [$driverId]);
        }
    }

    /**
     * Remote push voor een nieuw of aangevuld aanbod. Faalt nooit de rit.
     *
     * @param  list<int>  $driverIds
     */
    private function pushExpoOffers(string $conn, RideRequest $ride, array $driverIds): void
    {
        $driverIds = array_values(array_unique(array_filter(array_map('intval', $driverIds))));
        if ($driverIds === []) {
            return;
        }

        try {
            $offers = RideDispatchOffer::on($conn)
                ->where('ride_request_id', $ride->id)
                ->whereIn('driver_id', $driverIds)
                ->where('status', RideDispatchOffer::STATUS_PENDING)
                ->get(['id', 'driver_id']);
            $expo = app(DriverExpoPushService::class);
            $body = $expo->offerBody($ride);
            foreach ($offers as $offer) {
                $expo->sendNewOffer((int) $offer->driver_id, (int) $ride->id, (int) $offer->id, 'Nieuwe rit', $body);
            }
        } catch (\Throwable $e) {
            Log::warning('Expo-aanbodpush mislukt.', [
                'ride_request_id' => $ride->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verlopen aanbiedingen voor ritten buiten het pickup-grace-venster.
     */
    public function expireOffersForPastPickups(string $conn, int $companyId): int
    {
        if ($companyId <= 0 || ! TaxiDispatchSchema::tablesExist($conn)) {
            return 0;
        }

        $pickupCutoff = $this->dispatchSettings->pickupQueueCutoffAt($companyId);

        return RideDispatchOffer::on($conn)
            ->where('company_id', $companyId)
            ->where('status', RideDispatchOffer::STATUS_PENDING)
            ->whereHas('rideRequest', function ($q) use ($pickupCutoff) {
                $q->whereNull('driver_id')
                    ->whereNotNull('pickup_at')
                    ->where('pickup_at', '<', $pickupCutoff);
            })
            ->update([
                'status' => RideDispatchOffer::STATUS_EXPIRED,
                'responded_at' => now(),
            ]);
    }

    /**
     * Ritten zonder chauffeur waarbij elke chauffeur expliciet heeft afgewezen.
     *
     * @return array<int, array{ride_id: int, pickup_at: ?string, pickup_address: ?string, message: string}>
     */
    public function unclaimedRidesForCompany(string $conn, int $companyId): array
    {
        if ($companyId <= 0 || ! TaxiDispatchSchema::tablesExist($conn)) {
            return [];
        }

        $pickupCutoff = $this->dispatchSettings->pickupQueueCutoffAt($companyId);

        $rides = RideRequest::on($conn)
            ->ownedOrUnclaimedMarketplaceForCompany($companyId)
            ->whereNull('driver_id')
            ->whereIn('status', [RideRequest::STATUS_PENDING_DISPATCH, RideRequest::STATUS_OFFERED])
            ->withoutPendingPickupProposal()
            ->dispatchPickupWithinQueueWindow($pickupCutoff)
            ->get();

        $alerts = [];
        foreach ($rides as $ride) {
            if (! $this->allOffersDeclinedForRide($conn, (int) $ride->id)) {
                continue;
            }

            $alerts[] = [
                'ride_id' => (int) $ride->id,
                'pickup_at' => $ride->pickup_at?->toIso8601String(),
                'pickup_address' => $ride->pickup_address,
                'message' => 'Rit #'.$ride->id.': geen chauffeur heeft deze rit opgepakt. Iemand moet deze oppakken.',
            ];
        }

        return $alerts;
    }

    protected function driverDeclinedRide(string $conn, int $rideRequestId, int $driverId): bool
    {
        return RideDispatchOffer::on($conn)
            ->where('ride_request_id', $rideRequestId)
            ->where('driver_id', $driverId)
            ->where('status', RideDispatchOffer::STATUS_DECLINED)
            ->exists();
    }

    protected function allOffersDeclinedForRide(string $conn, int $rideRequestId): bool
    {
        $offers = RideDispatchOffer::on($conn)
            ->where('ride_request_id', $rideRequestId)
            ->get();

        if ($offers->isEmpty()) {
            return false;
        }

        $hasActionable = $offers->contains(function (RideDispatchOffer $offer) {
            return $offer->status === RideDispatchOffer::STATUS_PENDING
                && $offer->expires_at
                && $offer->expires_at->isFuture();
        });

        if ($hasActionable) {
            return false;
        }

        return $offers->every(fn (RideDispatchOffer $offer) => $offer->status === RideDispatchOffer::STATUS_DECLINED);
    }

    /**
     * Native-app- en andere marktplaatsritten: straal uit de boeking.
     * Eigen website/tenant: geen marktplaatsstraal.
     */
    protected function rideRequiresMarketplaceRadius(RideRequest $ride): bool
    {
        if ($ride->isOwnTenantFleetRide()) {
            return false;
        }

        if ($ride->isCustomerAppMarketplaceBooking() || $ride->isUnclaimedMarketplaceBooking()) {
            return true;
        }

        return $ride->isNexaSuiteBooking() && (int) ($ride->company_id ?? 0) <= 0;
    }

    /**
     * @return array{0: float, 1: float, 2: int}|null pickupLat, pickupLng, maxRadiusKm — null = niet bruikbaar
     */
    protected function networkRadiusFilter(RideRequest $ride): ?array
    {
        $ownerId = (int) ($ride->company_id ?? 0);
        if ($ownerId <= 0) {
            return null;
        }

        $lat = $ride->pickup_lat !== null ? (float) $ride->pickup_lat : null;
        $lng = $ride->pickup_lng !== null ? (float) $ride->pickup_lng : null;
        if ($lat === null || $lng === null) {
            Log::info('Network radius: rit zonder pickup-coördinaten — geen partner-offers.', [
                'ride_request_id' => $ride->id,
                'owner_company_id' => $ownerId,
            ]);

            return null;
        }

        return [$lat, $lng, $this->dispatchSettings->networkMaxRadiusKm($ownerId)];
    }

    /**
     * Marketplace-boeking: straal uit booking_payload (of standaard), rond ophaallocatie.
     *
     * @return array{0: float, 1: float, 2: int}|null
     */
    protected function marketplaceRadiusFilter(RideRequest $ride): ?array
    {
        $lat = $ride->pickup_lat !== null ? (float) $ride->pickup_lat : null;
        $lng = $ride->pickup_lng !== null ? (float) $ride->pickup_lng : null;
        if ($lat === null || $lng === null) {
            Log::info('Marketplace radius: rit zonder pickup-coördinaten — geen offers.', [
                'ride_request_id' => $ride->id,
            ]);

            return null;
        }

        $payload = $ride->booking_payload;
        $rawRadius = is_array($payload)
            ? ($payload['marketplace']['radius_km'] ?? null)
            : null;
        $radiusKm = \App\Services\NearestTaxiTenantResolver::normalizeRadiusKm($rawRadius);

        return [$lat, $lng, (int) max(1, (int) ceil($radiusKm))];
    }

    /**
     * @param  array{0: float, 1: float, 2: int}  $radius
     */
    protected function driverWithinPickupRadius(string $conn, int $driverId, array $radius): bool
    {
        if ($driverId <= 0 || ! TaxiDispatchSchema::driverAvailabilityExists($conn)) {
            return false;
        }

        [$pickupLat, $pickupLng, $maxKm] = $radius;
        $row = \App\Modules\NexaTaxi\Models\DriverAvailability::on($conn)
            ->where('driver_id', $driverId)
            ->where('is_online', true)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->first(['lat', 'lng']);
        if (! $row) {
            return false;
        }

        $km = $this->drivers->haversineKm(
            $pickupLat,
            $pickupLng,
            (float) $row->lat,
            (float) $row->lng
        );

        return $km <= (float) $maxKm;
    }

    /**
     * Eigen website-rit naar netwerkpartners alleen als elk eigen voertuig op een lopende rit zit.
     * Partners blijven binnen de al ingestelde network-straal.
     */
    protected function dispatchOwnRideToNetworkIfFleetFull(string $conn, RideRequest $ride): void
    {
        if (! $ride->isOwnTenantFleetRide() || $ride->driver_id) {
            return;
        }

        $ownerId = (int) $ride->company_id;
        if ($this->dispatchSettings->networkMode($ownerId) !== TaxiDispatchSettingsService::NETWORK_MODE_AUTO) {
            return;
        }

        if ($this->ownFleetHasFreeCapacity($conn, $ownerId)) {
            return;
        }

        $this->startNetworkPartnerDispatch($conn, $ride);
    }

    /**
     * Vrij = een actief eigen voertuig zonder lopende rit (status assigned die de chauffeur blokkeert).
     * Zonder voertuigregistratie: een online chauffeur die niet op zo'n rit zit.
     * Niemand online telt niet als "alles bezet"; de eigen push/rol-fallback blijft dan alleen.
     */
    protected function ownFleetHasFreeCapacity(string $conn, int $companyId): bool
    {
        if ($companyId <= 0) {
            return false;
        }

        $busyVehicleIds = [];
        $busyDriverIds = [];
        $hasFulfiller = Schema::connection($conn)->hasColumn('ride_requests', 'fulfilling_company_id');
        $assigned = RideRequest::on($conn)
            ->where('status', RideRequest::STATUS_ASSIGNED)
            ->where(function ($q) use ($companyId, $hasFulfiller) {
                $q->where('company_id', $companyId);
                if ($hasFulfiller) {
                    $q->orWhere('fulfilling_company_id', $companyId);
                }
            })
            ->get();

        foreach ($assigned as $ride) {
            if (! $ride->blocksDriverFromOtherRides()) {
                continue;
            }
            if ((int) ($ride->vehicle_id ?? 0) > 0) {
                $busyVehicleIds[] = (int) $ride->vehicle_id;
            }
            if ((int) ($ride->driver_id ?? 0) > 0) {
                $busyDriverIds[] = (int) $ride->driver_id;
            }
        }

        if (TaxiDispatchSchema::driverAvailabilityExists($conn)
            && Schema::connection($conn)->hasColumn('driver_availability', 'vehicle_id')
            && $busyDriverIds !== []
        ) {
            $held = \App\Modules\NexaTaxi\Models\DriverAvailability::on($conn)
                ->where('company_id', $companyId)
                ->whereIn('driver_id', $busyDriverIds)
                ->whereNotNull('vehicle_id')
                ->pluck('vehicle_id');
            foreach ($held as $vehicleId) {
                $busyVehicleIds[] = (int) $vehicleId;
            }
        }

        $busyVehicleIds = array_values(array_unique($busyVehicleIds));
        $busyDriverIds = array_values(array_unique($busyDriverIds));

        if (Schema::connection($conn)->hasTable('vehicles')) {
            $vehicles = Vehicle::on($conn)->where('company_id', $companyId);
            if (Schema::connection($conn)->hasColumn('vehicles', 'active')) {
                $vehicles->where('active', true);
            }
            $activeIds = $vehicles->pluck('id')->map(fn ($id) => (int) $id)->all();
            if ($activeIds !== []) {
                foreach ($activeIds as $vehicleId) {
                    if (! in_array($vehicleId, $busyVehicleIds, true)) {
                        return true;
                    }
                }

                return false;
            }
        }

        if (! TaxiDispatchSchema::driverAvailabilityExists($conn)) {
            return true;
        }

        $onlineIds = \App\Modules\NexaTaxi\Models\DriverAvailability::on($conn)
            ->where('company_id', $companyId)
            ->where('is_online', true)
            ->pluck('driver_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($onlineIds === []) {
            return true;
        }

        foreach ($onlineIds as $driverId) {
            if (! in_array($driverId, $busyDriverIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Eigen vloot: elke online chauffeur. Niemand online: rol-fallback (batch).
     * Straal (marktplaats/partner): alleen de batch binnen die straal.
     *
     * @return list<int>
     */
    protected function driverIdsForOffer(
        string $conn,
        int $companyId,
        int $batch,
        bool $enforcePickupRadius,
        ?float $pickupLat,
        ?float $pickupLng,
        ?int $maxKm,
    ): array {
        if ($enforcePickupRadius) {
            return $this->drivers->onlineDriverIdsForCompany(
                $conn,
                $companyId,
                $batch,
                $pickupLat,
                $pickupLng,
                $maxKm,
            );
        }

        $online = $this->drivers->onlineDriverIdsForCompany(
            $conn,
            $companyId,
            max($batch, 500),
            null,
            null,
            null,
            false,
        );
        if ($online !== []) {
            return $online;
        }

        return $this->drivers->onlineDriverIdsForCompany($conn, $companyId, $batch);
    }

    /**
     * 5 t/m 8 personen: alleen een busje. Meer dan 8 personen: alleen een bus.
     *
     * @param  list<int>  $driverIds
     * @return list<int>
     */
    private function driverIdsForPassengerCapacity(string $conn, int $companyId, RideRequest $ride, array $driverIds): array
    {
        if ($driverIds === [] || (int) ($ride->passengers ?? 1) <= 4) {
            return $driverIds;
        }

        return array_values(array_filter(
            $driverIds,
            fn (int $driverId) => $this->driverFitsPassengerCapacity($conn, $companyId, $driverId, $ride)
        ));
    }

    private function driverFitsPassengerCapacity(string $conn, int $companyId, int $driverId, RideRequest $ride): bool
    {
        if ((int) ($ride->passengers ?? 1) <= 4) {
            return true;
        }

        if (! Schema::connection($conn)->hasTable('driver_availability')
            || ! Schema::connection($conn)->hasColumn('driver_availability', 'vehicle_id')
            || ! Schema::connection($conn)->hasColumn('vehicles', 'person_range')) {
            return false;
        }

        $vehicleId = \App\Modules\NexaTaxi\Models\DriverAvailability::on($conn)
            ->where('company_id', $companyId)
            ->where('driver_id', $driverId)
            ->value('vehicle_id');

        if (! $vehicleId) {
            return false;
        }

        $range = Vehicle::on($conn)->whereKey((int) $vehicleId)->value('person_range');

        return Vehicle::rangeFitsPassengers((string) $range, (int) ($ride->passengers ?? 1));
    }
}
