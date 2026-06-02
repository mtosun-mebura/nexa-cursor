<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\WebsitePage;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideDispatchService;
use App\Modules\NexaTaxi\Services\TaxiBookingNotificationService;
use App\Modules\NexaTaxi\Services\TaxiCustomerLoginCodeService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use Illuminate\Support\Facades\Log;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Services\ModuleDatabaseService;
use App\Services\NexaTaxiBookingPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class NexaTaxiBookingController extends Controller
{
    public function __construct(
        protected NexaTaxiBookingPricingService $pricing,
        protected ModuleDatabaseService $moduleDb
    ) {}

    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page_id' => 'nullable|integer',
            'section_key' => 'nullable|string|max:120',
            'module' => 'nullable|string|max:64',
            'distance_meters' => 'required|integer|min:0',
            'duration_seconds' => 'required|integer|min:0',
            'passengers' => 'required|integer|min:1|max:20',
            'return_trip' => 'nullable|boolean',
            'pickup_at' => 'nullable|date',
            'waiting_minutes' => 'nullable|numeric|min:0',
            'baggage' => 'nullable|array',
            'baggage.*' => 'nullable|integer|min:0|max:20',
            'special_baggage' => 'nullable|array',
            'special_baggage.*' => 'nullable|integer|min:0|max:20',
        ]);

        $resolved = $this->resolveSectionConfig(
            isset($data['page_id']) ? (int) $data['page_id'] : null,
            isset($data['section_key']) ? (string) $data['section_key'] : 'component:taxi.boekingsmodule',
            isset($data['module']) ? trim((string) $data['module']) : null
        );
        $quotes = $this->pricing->buildQuotes($resolved['config'], $data, $resolved['tenant_company_id']);

        $companyId = $resolved['tenant_company_id'] ?? null;
        $paymentOptions = app(TaxiDispatchSettingsService::class)
            ->paymentOptionsForTenant(is_numeric($companyId) ? (int) $companyId : null);

        return response()->json([
            'success' => true,
            'data' => $quotes,
            'payment' => $paymentOptions,
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page_id' => 'nullable|integer',
            'section_key' => 'nullable|string|max:120',
            'module' => 'nullable|string|max:64',
            'return_url' => 'nullable|string|max:2000',
            'selected_offer_id' => 'required|string|max:120',
            'distance_meters' => 'required|integer|min:0',
            'duration_seconds' => 'required|integer|min:0',
            'passengers' => 'required|integer|min:1|max:20',
            'return_trip' => 'nullable|boolean',
            'pickup_address' => 'required|string|max:500',
            'dropoff_address' => 'required|string|max:500',
            'pickup_at' => 'required|date',
            'pickup_lat' => 'nullable|numeric',
            'pickup_lng' => 'nullable|numeric',
            'dropoff_lat' => 'nullable|numeric',
            'dropoff_lng' => 'nullable|numeric',
            'remarks' => 'nullable|string|max:2000',
            'first_name' => 'required|string|min:2|max:100',
            'last_name' => 'required|string|min:2|max:100',
            'email' => 'nullable|email|max:255',
            'create_account' => 'nullable|boolean',
            'phone' => [
                'required',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! $this->isBookingPhoneValid(trim($value))) {
                        $fail('Het telefoonnummer is ongeldig.');
                    }
                },
            ],
            'baggage' => 'nullable|array',
            'baggage.*' => 'nullable|integer|min:0|max:20',
            'special_baggage' => 'nullable|array',
            'special_baggage.*' => 'nullable|integer|min:0|max:20',
            'stopovers' => 'nullable|array',
            'stopovers.*' => 'nullable|string|max:500',
            'return_at' => 'nullable|date',
            'payment_method' => 'nullable|string|in:booking,driver',
        ]);

        $resolved = $this->resolveSectionConfig(
            isset($data['page_id']) ? (int) $data['page_id'] : null,
            isset($data['section_key']) ? (string) $data['section_key'] : 'component:taxi.boekingsmodule',
            isset($data['module']) ? trim((string) $data['module']) : null
        );
        $sectionConfig = $resolved['config'];
        $quotes = $this->pricing->buildQuotes($sectionConfig, $data, $resolved['tenant_company_id']);
        $selected = collect($quotes['offers'] ?? [])->firstWhere('id', (string) $data['selected_offer_id']);
        if (! $selected) {
            return response()->json([
                'success' => false,
                'message' => 'De geselecteerde aanbieding is niet meer geldig.',
            ], 422);
        }

        $conn = $this->moduleDb->getModuleConnectionName('taxi');
        $customerName = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
        $vehicleId = isset($selected['vehicle_id']) && is_numeric($selected['vehicle_id'])
            ? (int) $selected['vehicle_id']
            : null;

        // Fallback: bij person-range kan vehicle_id ontbreken; kies dan een actief voertuig in die range.
        if ($vehicleId === null) {
            $personRange = isset($selected['person_range']) ? trim((string) $selected['person_range']) : '';
            $vehicleQuery = Vehicle::on($conn)->where('active', true);
            if ($personRange !== '') {
                $vehicleQuery->where('person_range', $personRange);
            }
            $fallbackVehicle = $vehicleQuery->orderBy('name')->first();
            if ($fallbackVehicle) {
                $vehicleId = (int) $fallbackVehicle->id;
            }
        }

        $resolvedVehicle = $vehicleId ? Vehicle::on($conn)->find($vehicleId) : null;
        $companyId = $resolvedVehicle?->company_id ? (int) $resolvedVehicle->company_id : null;
        if (($companyId === null || $companyId <= 0) && ! empty($resolved['tenant_company_id'])) {
            $companyId = (int) $resolved['tenant_company_id'];
        }

        $dispatchSettings = app(TaxiDispatchSettingsService::class);
        $email = trim((string) ($data['email'] ?? ''));
        if ($dispatchSettings->customerEmailRequiredForBooking($companyId) && $email === '' && ! auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'E-mailadres is verplicht.',
                'errors' => ['email' => ['E-mailadres is verplicht.']],
            ], 422);
        }

        // Als de gebruiker al is ingelogd, gebruiken we diens e-mail voor koppeling/validatie.
        if (auth()->check()) {
            $authEmail = trim((string) (auth()->user()->email ?? ''));
            if ($authEmail !== '') {
                $email = $authEmail;
            }
        }

        $existingUser = null;
        if ($email !== '') {
            $existingUser = User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                ->first();
        }

        // E-mail mag maar 1x bestaan: als het al bestaat en je bent niet (juist) ingelogd, dwing login af.
        if ($existingUser && (! auth()->check() || (int) auth()->id() !== (int) $existingUser->id)) {
            if (! auth()->check()) {
                $returnUrl = trim((string) ($data['return_url'] ?? ''));
                if ($returnUrl === '') {
                    // JSON call: fallback naar home
                    $returnUrl = url('/');
                }
                $resumeUrl = Str::contains($returnUrl, '?')
                    ? ($returnUrl.'&resume_booking=1')
                    : ($returnUrl.'?resume_booking=1');

                $request->session()->put('nexataxi.pending_booking', [
                    'created_at' => now()->toISOString(),
                    'email' => $email,
                    'company_id' => $companyId,
                    'payload' => $data,
                    'resume_url' => $resumeUrl,
                ]);

                return response()->json([
                    'success' => false,
                    'requires_login' => true,
                    'message' => 'Dit e-mailadres is al bekend. Log in om je boeking te bevestigen.',
                    'login_url' => route('login', ['intended' => $resumeUrl]),
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Dit e-mailadres is al in gebruik. Log in met dat account om te boeken.',
                'errors' => ['email' => ['Dit e-mailadres is al in gebruik. Log in met dat account om te boeken.']],
            ], 422);
        }

        $createdCustomer = null;
        if (! auth()->check() && ! $existingUser && ! empty($data['create_account']) && $email !== '') {
            $createdCustomer = User::query()->create([
                'first_name' => (string) ($data['first_name'] ?? ''),
                'last_name' => (string) ($data['last_name'] ?? ''),
                'email' => $email,
                'phone' => (string) ($data['phone'] ?? ''),
                'company_id' => $companyId,
                'password' => Str::random(40),
                'email_verified_at' => null,
                'is_active' => true,
            ]);

            $role = Role::firstOrCreate(['name' => 'klant', 'guard_name' => 'web']);
            $createdCustomer->assignRole($role, $companyId);

            $resumeUrl = trim((string) ($data['return_url'] ?? ''));
            if ($resumeUrl !== '') {
                $resumeUrl = Str::contains($resumeUrl, '?') ? ($resumeUrl.'&code_login=1') : ($resumeUrl.'?code_login=1');
            } else {
                $resumeUrl = url('/login?code_login=1');
            }

            // Link naar login met code (email alvast ingevuld)
            $loginUrl = route('login', ['code_login' => 1, 'email' => $email, 'intended' => $resumeUrl]);
            app(TaxiCustomerLoginCodeService::class)->issueAndSend($createdCustomer, $companyId, $loginUrl);
        }

        $paymentService = app(TaxiRidePaymentService::class);
        try {
            $paymentMethod = $paymentService->validatePaymentMethodChoice(
                isset($data['payment_method']) ? (string) $data['payment_method'] : null,
                $companyId
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        $payAtBooking = $paymentMethod === RideRequest::PAYMENT_METHOD_BOOKING;
        $initialStatus = $payAtBooking
            ? RideRequest::STATUS_PENDING_PAYMENT
            : RideRequest::STATUS_PENDING_DISPATCH;
        $paymentStatus = $payAtBooking
            ? RideRequest::PAYMENT_STATUS_PENDING
            : ($paymentMethod === RideRequest::PAYMENT_METHOD_DRIVER
                ? RideRequest::PAYMENT_STATUS_NOT_REQUIRED
                : null);
        $payload = [
            'step_data' => [
                'distance_meters' => $data['distance_meters'],
                'duration_seconds' => $data['duration_seconds'],
                'return_trip' => ! empty($data['return_trip']),
                'baggage' => $data['baggage'] ?? [],
                'special_baggage' => $data['special_baggage'] ?? [],
                'remarks' => $data['remarks'] ?? '',
            ],
            'pricing' => $quotes,
        ];

        $ride = RideRequest::on($conn)->create([
            'company_id' => $companyId,
            'vehicle_id' => $vehicleId,
            'driver_id' => null,
            'status' => $initialStatus,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'pickup_address' => $data['pickup_address'],
            'dropoff_address' => $data['dropoff_address'],
            'pickup_lat' => $data['pickup_lat'] ?? null,
            'pickup_lng' => $data['pickup_lng'] ?? null,
            'dropoff_lat' => $data['dropoff_lat'] ?? null,
            'dropoff_lng' => $data['dropoff_lng'] ?? null,
            'distance_meters' => $data['distance_meters'],
            'duration_seconds' => $data['duration_seconds'],
            'passengers' => $data['passengers'],
            'pickup_at' => $data['pickup_at'],
            'quoted_price' => $selected['price'] ?? null,
            'customer_name' => $customerName,
            'customer_email' => $email !== '' ? $email : null,
            'customer_user_id' => auth()->check()
                ? (int) auth()->id()
                : ($existingUser ? (int) $existingUser->id : ($createdCustomer ? (int) $createdCustomer->id : null)),
            'customer_phone' => $data['phone'],
            'customer_note' => $data['remarks'] ?? null,
            'quote_expires_at' => now()->addHours(12),
            'booking_payload' => $payload,
            'selected_offer_payload' => $selected,
        ]);

        // Succes: eventuele bewaarde boeking opruimen.
        $request->session()->forget('nexataxi.pending_booking');

        if ($companyId && $companyId > 0 && ! $payAtBooking) {
            try {
                app(RideDispatchService::class)->startDispatch($conn, $ride, $companyId);
            } catch (\Throwable $e) {
                Log::warning('Boeking opgeslagen, chauffeur-dispatch mislukt.', [
                    'ride_request_id' => $ride->id,
                    'company_id' => $companyId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $checkoutUrl = null;
        if ($payAtBooking) {
            try {
                $price = (float) ($selected['price'] ?? 0);
                if ($price < 0.01) {
                    throw new \RuntimeException('Ongeldig bedrag voor betaling.');
                }
                $paymentResult = $paymentService->createBookingPayment($conn, $ride, $price);
                $checkoutUrl = $paymentResult['checkout_url'];
            } catch (\Throwable $e) {
                Log::warning('Boeking opgeslagen, Mollie-betaling mislukt.', [
                    'ride_request_id' => $ride->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'De betaling kon niet worden gestart. Probeer het opnieuw of kies betalen in de taxi.',
                ], 422);
            }
        }

        if ($companyId && $companyId > 0 && ! $payAtBooking) {
            try {
                app(TaxiBookingNotificationService::class)->notifyNewRide($conn, $ride, [
                'stopovers' => array_values(array_filter(array_map(
                    fn ($s) => is_string($s) ? trim($s) : '',
                    $data['stopovers'] ?? []
                ))),
                'return_at' => $data['return_at'] ?? null,
                'section_config' => $sectionConfig,
            ]);
            } catch (\Throwable $e) {
                Log::warning('Boeking opgeslagen, notificaties mislukt.', [
                    'ride_request_id' => $ride->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $payAtBooking
                ? 'Je wordt doorgestuurd naar de betaling.'
                : ($sectionConfig['texts']['success_message'] ?? 'Bedankt! Je boeking is ontvangen.'),
            'ride_request_id' => $ride->id,
            'payment_required' => $payAtBooking,
            'checkout_url' => $checkoutUrl,
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $pending = $request->session()->get('nexataxi.pending_booking');
        if (! is_array($pending)) {
            return response()->json(['pending' => false]);
        }

        if (! auth()->check()) {
            return response()->json(['pending' => false], 401);
        }

        $pendingEmail = trim((string) ($pending['email'] ?? ''));
        $authEmail = trim((string) (auth()->user()->email ?? ''));
        if ($pendingEmail !== '' && $authEmail !== '' && mb_strtolower($pendingEmail) !== mb_strtolower($authEmail)) {
            return response()->json(['pending' => false], 403);
        }

        return response()->json([
            'pending' => true,
            'payload' => $pending['payload'] ?? null,
            'resume_url' => $pending['resume_url'] ?? null,
        ]);
    }

    /**
     * Proxy voor Nominatim address search (voorkomt CORS en 429 door server-side cache).
     */
    public function addressSearch(Request $request): JsonResponse
    {
        $q = $request->input('q', '');
        $q = is_string($q) ? trim($q) : '';
        if ($q === '') {
            return response()->json([]);
        }
        // Leeg of een wildcard (*/all/worldwide) = geen landbeperking -> wereldwijd zoeken (ook buiten NL).
        $countrycodes = $request->input('countrycodes', '');
        $countrycodes = is_string($countrycodes) ? strtolower(trim($countrycodes)) : '';
        if (in_array($countrycodes, ['*', 'all', 'worldwide', 'world'], true)) {
            $countrycodes = '';
        }
        $limit = (int) $request->input('limit', 8);
        $limit = max(1, min(20, $limit));
        $format = $request->input('format', 'jsonv2');
        $addressdetails = $request->input('addressdetails', '1');
        $dedupe = $request->input('dedupe', '1');
        $acceptLanguage = $request->input('accept-language', 'nl');

        $cacheKey = 'nominatim_search:'.md5($q.'|'.$countrycodes.'|'.$limit.'|'.$addressdetails);
        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($q, $countrycodes, $limit, $format, $addressdetails, $dedupe, $acceptLanguage) {
            $query = [
                'format' => $format,
                'addressdetails' => $addressdetails,
                'limit' => $limit,
                'dedupe' => $dedupe,
                'accept-language' => $acceptLanguage,
                'q' => $q,
            ];
            // Alleen beperken op land(en) als er expliciet een landcode is meegegeven; anders wereldwijd.
            if ($countrycodes !== '') {
                $query['countrycodes'] = $countrycodes;
            }
            $url = 'https://nominatim.openstreetmap.org/search?'.http_build_query($query);
            $response = Http::withHeaders([
                'User-Agent' => config('app.name', 'NexaTaxiBooking').'/1.0 (address search)',
            ])->timeout(8)->get($url);
            if (! $response->successful()) {
                return [];
            }
            $body = $response->json();

            return is_array($body) ? $body : [];
        });

        return response()->json($data);
    }

    /**
     * @return array{config: array, tenant_company_id: ?int}
     */
    private function resolveSectionConfig(?int $pageId, string $sectionKey, ?string $moduleName = null): array
    {
        $default = $this->pricing->getDefaultSectionConfig();
        if (! $pageId) {
            return ['config' => $default, 'tenant_company_id' => null];
        }
        $query = $moduleName && $this->moduleDb->supportsModuleDatabases()
            ? WebsitePage::on($this->moduleDb->getModuleConnectionName($moduleName))
            : WebsitePage::query();
        $page = $query->find($pageId);
        if (! $page) {
            return ['config' => $default, 'tenant_company_id' => null];
        }
        $tenantCompanyId = $page->company_id !== null && (int) $page->company_id > 0
            ? (int) $page->company_id
            : null;
        $homeSections = $page->getHomeSections();
        $raw = $homeSections[$sectionKey] ?? [];
        if (! is_array($raw)) {
            return ['config' => $default, 'tenant_company_id' => $tenantCompanyId];
        }

        return [
            'config' => $this->pricing->mergeSectionConfig($raw),
            'tenant_company_id' => $tenantCompanyId,
        ];
    }

    private function isBookingPhoneValid(string $value): bool
    {
        if ($value === '' || ! preg_match('/^[+.\d\s()\-]+$/', $value)) {
            return false;
        }
        $digits = preg_replace('/\D/', '', $value);
        if (str_starts_with($digits, '06')) {
            return (bool) preg_match('/^06\d{8}$/', $digits);
        }
        if ($this->isExplicitlyDutchBookingPhone($value, $digits)) {
            return $this->isValidDutchBookingPhoneDigits($digits);
        }

        return strlen($digits) >= 8 && strlen($digits) <= 15;
    }

    private function isExplicitlyDutchBookingPhone(string $value, string $digits): bool
    {
        $t = trim($value);
        if (str_starts_with($t, '+31')) {
            return true;
        }
        if (preg_match('/^0031/i', $t)) {
            return true;
        }
        if (str_starts_with($digits, '0031')) {
            return true;
        }
        if (preg_match('/^31\d{9}$/', $digits)) {
            return true;
        }

        return false;
    }

    private function isValidDutchBookingPhoneDigits(string $digits): bool
    {
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (preg_match('/^0[1-9]\d{8}$/', $digits)) {
            return true;
        }
        if (preg_match('/^31[1-9]\d{8}$/', $digits)) {
            return true;
        }

        return false;
    }
}
