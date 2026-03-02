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
            isset($data['section_key']) ? (string) $data['section_key'] : 'component:taxiroyaal.boekingsmodule'
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
            isset($data['section_key']) ? (string) $data['section_key'] : 'component:taxiroyaal.boekingsmodule'
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

    private function resolveSectionConfig(?int $pageId, string $sectionKey): array
    {
        $default = $this->pricing->getDefaultSectionConfig();
        if (! $pageId) {
            return $default;
        }
        $page = WebsitePage::query()->find($pageId);
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

