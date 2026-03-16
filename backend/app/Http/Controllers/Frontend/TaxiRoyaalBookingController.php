<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\WebsitePage;
use App\Modules\TaxiRoyaal\Models\RideRequest;
use App\Modules\TaxiRoyaal\Models\Vehicle;
use App\Services\ModuleDatabaseService;
use App\Services\TaxiRoyaalBookingPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TaxiRoyaalBookingController extends Controller
{
    public function __construct(
        protected TaxiRoyaalBookingPricingService $pricing,
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

        $sectionConfig = $this->resolveSectionConfig(
            isset($data['page_id']) ? (int) $data['page_id'] : null,
            isset($data['section_key']) ? (string) $data['section_key'] : 'component:taxiroyaal.boekingsmodule',
            isset($data['module']) ? trim((string) $data['module']) : null
        );
        $quotes = $this->pricing->buildQuotes($sectionConfig, $data);

        return response()->json([
            'success' => true,
            'data' => $quotes,
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page_id' => 'nullable|integer',
            'section_key' => 'nullable|string|max:120',
            'module' => 'nullable|string|max:64',
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
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:50',
            'baggage' => 'nullable|array',
            'baggage.*' => 'nullable|integer|min:0|max:20',
            'special_baggage' => 'nullable|array',
            'special_baggage.*' => 'nullable|integer|min:0|max:20',
        ]);

        $sectionConfig = $this->resolveSectionConfig(
            isset($data['page_id']) ? (int) $data['page_id'] : null,
            isset($data['section_key']) ? (string) $data['section_key'] : 'component:taxiroyaal.boekingsmodule',
            isset($data['module']) ? trim((string) $data['module']) : null
        );
        $quotes = $this->pricing->buildQuotes($sectionConfig, $data);
        $selected = collect($quotes['offers'] ?? [])->firstWhere('id', (string) $data['selected_offer_id']);
        if (! $selected) {
            return response()->json([
                'success' => false,
                'message' => 'De geselecteerde aanbieding is niet meer geldig.',
            ], 422);
        }

        $conn = $this->moduleDb->getModuleConnectionName('taxiroyaal');
        $customerName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
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
        $payload = [
            'step_data' => [
                'distance_meters' => $data['distance_meters'],
                'duration_seconds' => $data['duration_seconds'],
                'return_trip' => !empty($data['return_trip']),
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
            'status' => RideRequest::STATUS_QUOTED,
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
            'customer_email' => $data['email'] ?? null,
            'customer_phone' => $data['phone'],
            'customer_note' => $data['remarks'] ?? null,
            'quote_expires_at' => now()->addHours(12),
            'booking_payload' => $payload,
            'selected_offer_payload' => $selected,
        ]);

        return response()->json([
            'success' => true,
            'message' => $sectionConfig['texts']['success_message'] ?? 'Bedankt! Je boeking is ontvangen.',
            'ride_request_id' => $ride->id,
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
        $countrycodes = $request->input('countrycodes', 'nl');
        $countrycodes = is_string($countrycodes) ? strtolower(trim($countrycodes)) : 'nl';
        $limit = (int) $request->input('limit', 8);
        $limit = max(1, min(20, $limit));
        $format = $request->input('format', 'jsonv2');
        $addressdetails = $request->input('addressdetails', '1');
        $dedupe = $request->input('dedupe', '1');
        $acceptLanguage = $request->input('accept-language', 'nl');

        $cacheKey = 'nominatim_search:' . md5($q . '|' . $countrycodes . '|' . $limit . '|' . $addressdetails);
        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($q, $countrycodes, $limit, $format, $addressdetails, $dedupe, $acceptLanguage) {
            $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
                'format' => $format,
                'addressdetails' => $addressdetails,
                'limit' => $limit,
                'dedupe' => $dedupe,
                'countrycodes' => $countrycodes,
                'accept-language' => $acceptLanguage,
                'q' => $q,
            ]);
            $response = Http::withHeaders([
                'User-Agent' => config('app.name', 'NexaTaxiBooking') . '/1.0 (address search)',
            ])->timeout(8)->get($url);
            if (! $response->successful()) {
                return [];
            }
            $body = $response->json();
            return is_array($body) ? $body : [];
        });

        return response()->json($data);
    }

    private function resolveSectionConfig(?int $pageId, string $sectionKey, ?string $moduleName = null): array
    {
        $default = $this->pricing->getDefaultSectionConfig();
        if (! $pageId) {
            return $default;
        }
        $query = $moduleName && $this->moduleDb->supportsModuleDatabases()
            ? WebsitePage::on($this->moduleDb->getModuleConnectionName($moduleName))
            : WebsitePage::query();
        $page = $query->find($pageId);
        if (! $page) {
            return $default;
        }
        $homeSections = $page->getHomeSections();
        $raw = $homeSections[$sectionKey] ?? [];
        if (! is_array($raw)) {
            return $default;
        }

        return $this->pricing->mergeSectionConfig($raw);
    }
}

