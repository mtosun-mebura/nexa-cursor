<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Modules\NexaTaxi\Support\TaxiCustomerAppSchema;
use App\Services\ModuleDatabaseService;
use App\Services\NearestTaxiTenantResolver;
use App\Services\NexaTaxiBookingPricingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CustomerMarketplaceBookingService
{
    public function __construct(
        protected ModuleDatabaseService $moduleDb,
        protected NearestTaxiTenantResolver $nearestResolver,
        protected NexaTaxiBookingPricingService $pricing,
        protected TaxiRidePaymentService $paymentService,
        protected CustomerRideLiveStatusService $liveStatus,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     ride: RideRequest,
     *     track_token: string,
     *     offer: array<string, mixed>,
     *     marketplace: array<string, mixed>,
     *     checkout_url: ?string,
     *     payment_required: bool,
     *     payment: array{booking: bool, driver: bool, mollie_configured: bool}
     * }
     */
    public function book(array $data, ?User $customer = null): array
    {
        $conn = $this->moduleDb->getModuleConnectionName('taxi');
        TaxiCustomerAppSchema::ensureTrackTokenColumn($conn);

        $lat = (float) $data['pickup_lat'];
        $lng = (float) $data['pickup_lng'];
        $radiusKm = NearestTaxiTenantResolver::normalizeRadiusKm(
            $data['marketplace_radius_km'] ?? null
        );

        $matches = $this->nearestResolver->resolveNearby(
            $lat,
            $lng,
            NearestTaxiTenantResolver::MARKETPLACE_MAX_TENANTS,
            $radiusKm
        );
        if ($matches === []) {
            throw ValidationException::withMessages([
                'pickup_address' => ['Er is momenteel geen taxicentrale beschikbaar in de buurt van deze ophaallocatie.'],
            ]);
        }

        $candidates = [];
        $candidateIds = [];
        foreach ($matches as $match) {
            $company = $match['company'];
            $candidateIds[] = (int) $company->id;
            $candidates[] = [
                'company_id' => (int) $company->id,
                'company_name' => $company->name,
                'distance_km' => $match['distance_km'],
            ];
        }
        $nearest = $candidates[0];
        $settingsCompanyId = (int) $nearest['company_id'];
        $paymentOptions = app(TaxiDispatchSettingsService::class)
            ->paymentOptionsForTenant($settingsCompanyId > 0 ? $settingsCompanyId : null);

        $sectionConfig = $this->pricing->getDefaultSectionConfig();
        $sectionConfig['logic']['offer_display_mode'] = 'person_range';

        $baggage = $this->normalizeBaggageMap($data['baggage'] ?? []);
        $specialBaggage = $this->normalizeBaggageMap($data['special_baggage'] ?? []);
        $pickupAt = $this->normalizePickupAtWallClock($data['pickup_at'] ?? null);

        $quoteInput = [
            'distance_meters' => (int) $data['distance_meters'],
            'duration_seconds' => (int) $data['duration_seconds'],
            'passengers' => (int) $data['passengers'],
            'return_trip' => ! empty($data['return_trip']),
            'pickup_at' => $pickupAt,
            'pickup_lat' => $lat,
            'pickup_lng' => $lng,
            'baggage' => $baggage,
            'special_baggage' => $specialBaggage,
        ];

        $quotes = $this->pricing->buildQuotes($sectionConfig, $quoteInput, null);
        $offers = $quotes['offers'] ?? [];
        if ($offers === []) {
            throw ValidationException::withMessages([
                'passengers' => ['Geen passende aanbieding gevonden voor dit aantal personen.'],
            ]);
        }

        $selectedOfferId = isset($data['selected_offer_id']) ? (string) $data['selected_offer_id'] : '';
        $selected = $selectedOfferId !== ''
            ? collect($offers)->firstWhere('id', $selectedOfferId)
            : ($offers[0] ?? null);
        if (! $selected) {
            throw ValidationException::withMessages([
                'selected_offer_id' => ['De geselecteerde aanbieding is niet meer geldig.'],
            ]);
        }

        if (! $paymentOptions['booking']) {
            throw ValidationException::withMessages([
                'payment_method' => ['Online betalen is momenteel niet beschikbaar. Probeer het later opnieuw.'],
            ]);
        }

        $customerName = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
        if ($customerName === '' && $customer) {
            $customerName = trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''));
        }

        $customerEmail = $this->resolveEmail($data, $customer);
        if ($customerEmail === null || $customerEmail === '') {
            throw ValidationException::withMessages([
                'email' => ['Vul een e-mailadres in voor de online betaling.'],
            ]);
        }

        $marketplace = [
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'label' => 'NEXA Suite',
            'company_id' => $nearest['company_id'],
            'company_name' => $nearest['company_name'],
            'distance_km' => $nearest['distance_km'],
            'candidate_company_ids' => $candidateIds,
            'candidates' => $candidates,
            'radius_km' => $radiusKm,
            'channel' => 'customer_app',
            'settings_company_id' => $settingsCompanyId,
        ];

        $dispatchSettings = app(TaxiDispatchSettingsService::class);
        $dispatchTimers = [
            'unaccepted_auto_cancel_minutes' => $dispatchSettings->unacceptedAutoCancelMinutes(
                $settingsCompanyId > 0 ? $settingsCompanyId : null
            ),
            'customer_unaccepted_decision_minutes' => $dispatchSettings->customerUnacceptedDecisionMinutes(
                $settingsCompanyId > 0 ? $settingsCompanyId : null
            ),
        ];

        $returnUrl = isset($data['return_url']) ? trim((string) $data['return_url']) : '';
        $payload = [
            'channel' => RideRequest::SOURCE_NEXA_SUITE,
            'marketplace' => $marketplace,
            'dispatch_timers' => $dispatchTimers,
            'step_data' => [
                'distance_meters' => (int) $data['distance_meters'],
                'duration_seconds' => (int) $data['duration_seconds'],
                'return_trip' => ! empty($data['return_trip']),
                'remarks' => $data['remarks'] ?? '',
                'baggage' => $baggage,
                'special_baggage' => $specialBaggage,
                'app' => 'customer',
            ],
            'pricing' => $quotes,
        ];

        $rideData = [
            'company_id' => null,
            'vehicle_id' => null,
            'driver_id' => null,
            'status' => RideRequest::STATUS_PENDING_PAYMENT,
            'payment_method' => RideRequest::PAYMENT_METHOD_BOOKING,
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
            'pickup_address' => (string) $data['pickup_address'],
            'dropoff_address' => (string) $data['dropoff_address'],
            'pickup_lat' => $lat,
            'pickup_lng' => $lng,
            'dropoff_lat' => $data['dropoff_lat'] ?? null,
            'dropoff_lng' => $data['dropoff_lng'] ?? null,
            'distance_meters' => (int) $data['distance_meters'],
            'duration_seconds' => (int) $data['duration_seconds'],
            'passengers' => (int) $data['passengers'],
            'pickup_at' => $pickupAt,
            'quoted_price' => $selected['price'] ?? null,
            'customer_name' => $customerName !== '' ? $customerName : 'Klant',
            'customer_email' => $customerEmail,
            'customer_phone' => (string) $data['phone'],
            'customer_note' => $data['remarks'] ?? null,
            'quote_expires_at' => now()->addHours(12),
            'booking_payload' => $payload,
            'selected_offer_payload' => $selected,
        ];

        if (Schema::connection($conn)->hasColumn('ride_requests', 'source')) {
            $rideData['source'] = RideRequest::SOURCE_NEXA_SUITE;
        }
        if (Schema::connection($conn)->hasColumn('ride_requests', 'customer_user_id') && $customer) {
            $rideData['customer_user_id'] = (int) $customer->id;
        }

        $ride = RideRequest::on($conn)->create($rideData);
        $trackToken = $this->liveStatus->issueTrackToken($ride);

        $checkoutUrl = null;
        $appReturn = $returnUrl !== ''
            ? $returnUrl
            : url('/taxi/klant');
        $separator = str_contains($appReturn, '?') ? '&' : '?';
        $appReturn .= $separator.'boeking=betaald&token='.urlencode($trackToken);

        $payload['payment_return'] = [
            'return_url' => $appReturn,
            'message' => 'Betaling ontvangen. We zoeken een taxi in de buurt.',
            'channel' => 'customer_app',
            'track_token' => $trackToken,
        ];
        $ride->update(['booking_payload' => $payload]);

        $price = (float) ($selected['price'] ?? 0);
        if ($price < 0.01) {
            throw ValidationException::withMessages([
                'payment' => ['Ongeldig bedrag voor betaling.'],
            ]);
        }

        try {
            $paymentResult = $this->paymentService->createBookingPayment($conn, $ride->fresh() ?? $ride, $price);
            $checkoutUrl = $paymentResult['checkout_url'];
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'payment' => ['De betaling kon niet worden gestart. Probeer het opnieuw.'],
            ]);
        }

        return [
            'ride' => $ride->fresh() ?? $ride,
            'track_token' => $trackToken,
            'offer' => $selected,
            'marketplace' => $marketplace,
            'checkout_url' => $checkoutUrl,
            'payment_required' => true,
            'payment' => [
                'booking' => true,
                'driver' => false,
                'mollie_configured' => (bool) $paymentOptions['mollie_configured'],
            ],
        ];
    }

    /**
     * Opnieuw een Mollie-betaallink maken voor een openstaande klant-app boeking.
     *
     * @return array{ride: RideRequest, track_token: string, checkout_url: string}
     */
    public function retryBookingPayment(RideRequest $ride, ?string $returnUrl = null): array
    {
        $conn = $ride->getConnectionName() ?: $this->moduleDb->getModuleConnectionName('taxi');
        TaxiCustomerAppSchema::ensureTrackTokenColumn($conn);
        $ride = RideRequest::on($conn)->find($ride->id) ?? $ride;

        if ($ride->status !== RideRequest::STATUS_PENDING_PAYMENT) {
            throw ValidationException::withMessages([
                'payment' => ['Deze rit wacht niet meer op betaling.'],
            ]);
        }
        if ($ride->payment_status === RideRequest::PAYMENT_STATUS_PAID) {
            throw ValidationException::withMessages([
                'payment' => ['Deze rit is al betaald.'],
            ]);
        }

        $price = (float) ($ride->quoted_price ?? $ride->final_price ?? 0);
        if ($price < 0.01) {
            throw ValidationException::withMessages([
                'payment' => ['Ongeldig bedrag voor betaling.'],
            ]);
        }

        $trackToken = (string) ($ride->customer_track_token ?? '');
        if ($trackToken === '') {
            $trackToken = $this->liveStatus->issueTrackToken($ride);
            $ride = $ride->fresh() ?? $ride;
        }

        $appReturn = is_string($returnUrl) ? trim($returnUrl) : '';
        if ($appReturn === '') {
            $appReturn = url('/taxi/klant');
        }
        $separator = str_contains($appReturn, '?') ? '&' : '?';
        if (! str_contains($appReturn, 'token=')) {
            $appReturn .= $separator.'token='.urlencode($trackToken);
            $separator = '&';
        }
        if (! str_contains($appReturn, 'boeking=')) {
            $appReturn .= $separator.'boeking=betaald';
        }

        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $payload['payment_return'] = array_merge(
            is_array($payload['payment_return'] ?? null) ? $payload['payment_return'] : [],
            [
                'return_url' => $appReturn,
                'message' => 'Betaling ontvangen. We zoeken een taxi in de buurt.',
                'channel' => 'customer_app',
                'track_token' => $trackToken,
            ]
        );
        $ride->update(['booking_payload' => $payload]);

        $paymentResult = $this->paymentService->createBookingPayment($conn, $ride->fresh() ?? $ride, $price);

        return [
            'ride' => $ride->fresh() ?? $ride,
            'track_token' => $trackToken,
            'checkout_url' => (string) $paymentResult['checkout_url'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{offers: list<array<string, mixed>>, marketplace: array<string, mixed>}
     */
    public function quote(array $data): array
    {
        $lat = (float) $data['pickup_lat'];
        $lng = (float) $data['pickup_lng'];
        $radiusKm = NearestTaxiTenantResolver::normalizeRadiusKm(
            $data['marketplace_radius_km'] ?? null
        );

        $matches = $this->nearestResolver->resolveNearby(
            $lat,
            $lng,
            NearestTaxiTenantResolver::MARKETPLACE_MAX_TENANTS,
            $radiusKm
        );
        if ($matches === []) {
            throw ValidationException::withMessages([
                'pickup_address' => ['Er is momenteel geen taxicentrale beschikbaar in de buurt van deze ophaallocatie.'],
            ]);
        }

        $candidates = [];
        $candidateIds = [];
        foreach ($matches as $match) {
            $company = $match['company'];
            $candidateIds[] = (int) $company->id;
            $candidates[] = [
                'company_id' => (int) $company->id,
                'company_name' => $company->name,
                'distance_km' => round((float) $match['distance_km'], 1),
            ];
        }

        $sectionConfig = $this->pricing->getDefaultSectionConfig();
        $sectionConfig['logic']['offer_display_mode'] = 'person_range';
        $quotes = $this->pricing->buildQuotes($sectionConfig, [
            'distance_meters' => (int) $data['distance_meters'],
            'duration_seconds' => (int) $data['duration_seconds'],
            'passengers' => (int) $data['passengers'],
            'return_trip' => ! empty($data['return_trip']),
            'pickup_at' => ! empty($data['pickup_at'])
                ? $this->normalizePickupAtWallClock($data['pickup_at'])
                : null,
            'pickup_lat' => $lat,
            'pickup_lng' => $lng,
            'baggage' => $this->normalizeBaggageMap($data['baggage'] ?? []),
            'special_baggage' => $this->normalizeBaggageMap($data['special_baggage'] ?? []),
        ], null);

        $paymentOptions = app(TaxiDispatchSettingsService::class)->paymentOptionsForTenant(
            $candidateIds[0] ?? null
        );

        return [
            'offers' => array_values($quotes['offers'] ?? []),
            'marketplace' => [
                'radius_km' => $radiusKm,
                'candidate_count' => count($candidateIds),
                'candidates' => $candidates,
            ],
            'payment' => [
                'booking' => (bool) ($paymentOptions['booking'] ?? false),
                'driver' => false,
                'mollie_configured' => (bool) ($paymentOptions['mollie_configured'] ?? false),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveEmail(array $data, ?User $customer): ?string
    {
        if ($customer && trim((string) ($customer->email ?? '')) !== '') {
            return trim((string) $customer->email);
        }
        $email = trim((string) ($data['email'] ?? ''));

        return $email !== '' ? $email : null;
    }

    /**
     * Sla ophaaltijd op als Europe/Amsterdam wall-clock (naive datetime).
     * ISO met Z/offset → omzetten naar Amsterdam; naïeve lokale string → digits behouden.
     */
    protected function normalizePickupAtWallClock(mixed $value): string
    {
        $raw = is_string($value) ? trim($value) : '';
        if ($raw === '') {
            throw ValidationException::withMessages([
                'pickup_at' => ['Kies een ophaaltijd.'],
            ]);
        }

        $hasOffset = (bool) preg_match('/[zZ]|[+-]\d{2}:?\d{2}$/', $raw);
        $isNaive = (bool) preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/', $raw);

        if ($isNaive && ! $hasOffset) {
            $normalized = str_replace('T', ' ', $raw);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
                $normalized .= ':00';
            }

            return $normalized;
        }

        try {
            return Carbon::parse($raw)
                ->timezone(ContractTransportTimezone::TIMEZONE)
                ->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'pickup_at' => ['Ongeldige ophaaltijd.'],
            ]);
        }
    }

    /**
     * @param  mixed  $raw
     * @return array<string, int>
     */
    protected function normalizeBaggageMap(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $key => $qty) {
            $count = (int) $qty;
            if ($count <= 0) {
                continue;
            }
            $out[(string) $key] = min(20, $count);
        }

        return $out;
    }
}
