<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportIndividualBooking;
use App\Modules\NexaTaxi\Models\TransportOccurrence;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\ContractOccurrenceGeneratorService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransportIndividualBookingController extends Controller
{
    use UsesModuleDatabase;

    public function __construct(
        private readonly TaxiDriverEligibilityService $driverEligibility,
        private readonly ContractOccurrenceGeneratorService $occurrenceGenerator,
    ) {}

    public function index(Request $request, int $customerId, int $contractId)
    {
        $this->authorizeOrPermission('rides.view');

        [$conn, $customer, $contract] = $this->resolveContract($customerId, $contractId);

        $query = TransportIndividualBooking::on($conn)
            ->where('transport_contract_id', $contract->id)
            ->with(['passenger']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            $query->where(function ($q) use ($s) {
                $q->where('pickup_address', 'like', "%{$s}%")
                    ->orWhere('dropoff_address', 'like', "%{$s}%")
                    ->orWhereHas('passenger', function ($pq) use ($s) {
                        $pq->where('first_name', 'like', "%{$s}%")
                            ->orWhere('last_name', 'like', "%{$s}%");
                    });
            });
        }

        $bookings = $query->orderByDesc('pickup_at')->paginate(20)->withQueryString();

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_customers.contract_show', [$customerId, $contractId])
        );

        return view('taxi::admin.transport_individual_bookings.index', compact(
            'customer',
            'contract',
            'bookings',
            'backUrl'
        ));
    }

    public function create(Request $request, int $customerId, int $contractId)
    {
        $this->authorizeOrPermission('rides.create');

        [$conn, $customer, $contract] = $this->resolveContract($customerId, $contractId);
        $context = $this->formContext($conn, $contract);

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_individual_bookings.index', [$customerId, $contractId])
        );

        return view('taxi::admin.transport_individual_bookings.create', array_merge(
            compact('customer', 'contract', 'backUrl'),
            $context
        ));
    }

    public function store(Request $request, int $customerId, int $contractId)
    {
        $this->authorizeOrPermission('rides.create');

        $data = $this->validateBooking($request);

        [$conn, , $contract] = $this->resolveContract($customerId, $contractId);
        $this->assertPassengerOnContract($conn, $contract->id, (int) $data['transport_passenger_id']);
        $this->assertDriverAndVehicle($conn, $contract->company_id, $data);

        $booking = TransportIndividualBooking::on($conn)->create(array_merge($data, [
            'company_id' => $contract->company_id,
            'transport_contract_id' => $contract->id,
            'status' => TransportIndividualBooking::STATUS_PLANNED,
        ]));

        $this->occurrenceGenerator->syncIndividualBookingOccurrenceAndRide(
            $conn,
            $booking->fresh(['passenger', 'contract'])
        );

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_individual_bookings.index', [$customerId, $contractId])
        );

        return redirect($backUrl)->with('success', 'Individuele contractrit gepland; chauffeurs-rit is gesynchroniseerd.');
    }

    public function edit(Request $request, int $customerId, int $contractId, int $bookingId)
    {
        $this->authorizeOrPermission('rides.update');

        [$conn, $customer, $contract, $booking] = $this->resolveBooking($customerId, $contractId, $bookingId);
        $context = $this->formContext($conn, $contract);
        $hasOccurrence = TransportOccurrence::on($conn)
            ->where('transport_individual_booking_id', $booking->id)
            ->exists();

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_individual_bookings.index', [$customerId, $contractId])
        );

        return view('taxi::admin.transport_individual_bookings.edit', array_merge(
            compact('customer', 'contract', 'booking', 'hasOccurrence', 'backUrl'),
            $context
        ));
    }

    public function update(Request $request, int $customerId, int $contractId, int $bookingId)
    {
        $this->authorizeOrPermission('rides.update');

        [$conn, , $contract, $booking] = $this->resolveBooking($customerId, $contractId, $bookingId);

        if ($booking->status === TransportIndividualBooking::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'status' => 'Geannuleerde ritten kunnen niet meer worden bewerkt.',
            ]);
        }

        $data = $this->validateBooking($request);
        $this->assertPassengerOnContract($booking->getConnectionName(), $contract->id, (int) $data['transport_passenger_id']);
        $this->assertDriverAndVehicle($booking->getConnectionName(), $contract->company_id, $data);

        $booking->update($data);

        $this->occurrenceGenerator->syncIndividualBookingOccurrenceAndRide(
            $conn,
            $booking->fresh(['passenger', 'contract'])
        );

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_individual_bookings.index', [$customerId, $contractId])
        );

        return redirect($backUrl)->with('success', 'Individuele contractrit opgeslagen; chauffeurs-rit is gesynchroniseerd.');
    }

    public function destroy(int $customerId, int $contractId, int $bookingId)
    {
        $this->authorizeOrPermission('rides.delete');

        [, , , $booking] = $this->resolveBooking($customerId, $contractId, $bookingId);

        if ($booking->status === TransportIndividualBooking::STATUS_CANCELLED) {
            return redirect()
                ->route('admin.taxi.transport_individual_bookings.index', [$customerId, $contractId])
                ->with('success', 'Rit was al geannuleerd.');
        }

        $conn = $booking->getConnectionName();
        $booking->update(['status' => TransportIndividualBooking::STATUS_CANCELLED]);
        $this->occurrenceGenerator->cancelIndividualBookingOccurrenceAndRide($conn, $booking->fresh());

        return redirect()
            ->route('admin.taxi.transport_individual_bookings.index', [$customerId, $contractId])
            ->with('success', 'Individuele contractrit geannuleerd.');
    }

    /** @return array<string, mixed> */
    private function validateBooking(Request $request): array
    {
        $data = $request->validate([
            'transport_passenger_id' => ['required', 'integer'],
            'pickup_address' => ['required', 'string', 'max:500'],
            'pickup_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'dropoff_address' => ['required', 'string', 'max:500'],
            'dropoff_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'dropoff_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'pickup_at' => ['required', 'date'],
            'driver_id' => ['nullable', 'integer'],
            'vehicle_id' => ['nullable', 'integer'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['driver_id'] = ! empty($data['driver_id']) ? (int) $data['driver_id'] : null;
        $data['vehicle_id'] = ! empty($data['vehicle_id']) ? (int) $data['vehicle_id'] : null;
        $data['price_override'] = isset($data['price_override']) && $data['price_override'] !== ''
            ? $data['price_override']
            : null;

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    private function assertDriverAndVehicle(string $conn, int $companyId, array $data): void
    {
        if (! empty($data['driver_id'])) {
            $valid = $this->driverEligibility->buildChauffeurQuery($companyId)
                ->where('users.id', $data['driver_id'])
                ->exists();
            if (! $valid) {
                throw ValidationException::withMessages(['driver_id' => 'Ongeldige chauffeur.']);
            }
        }

        if (! empty($data['vehicle_id'])) {
            $valid = Vehicle::on($conn)
                ->where('company_id', $companyId)
                ->where('id', $data['vehicle_id'])
                ->exists();
            if (! $valid) {
                throw ValidationException::withMessages(['vehicle_id' => 'Ongeldig voertuig.']);
            }
        }
    }

    private function assertPassengerOnContract(string $conn, int $contractId, int $passengerId): void
    {
        $exists = TransportPassenger::on($conn)
            ->where('transport_contract_id', $contractId)
            ->where('active', true)
            ->whereKey($passengerId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'transport_passenger_id' => 'Selecteer een actieve passagier van dit abonnement.',
            ]);
        }
    }

    /** @return array{passengers: \Illuminate\Support\Collection, drivers: \Illuminate\Support\Collection, vehicles: \Illuminate\Support\Collection} */
    private function formContext(string $conn, TransportContract $contract): array
    {
        return [
            'passengers' => TransportPassenger::on($conn)
                ->where('transport_contract_id', $contract->id)
                ->where('active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'pickup_address', 'pickup_lat', 'pickup_lng', 'phone']),
            'drivers' => $this->driverEligibility
                ->buildChauffeurQuery((int) $contract->company_id)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
            'vehicles' => Vehicle::on($conn)
                ->where('company_id', $contract->company_id)
                ->orderBy('name')
                ->get(['id', 'name', 'license_plate']),
        ];
    }

    /** @return array{0: string, 1: TransportCustomer, 2: TransportContract} */
    private function resolveContract(int $customerId, int $contractId): array
    {
        $conn = $this->moduleConnection();
        $customer = TransportCustomer::on($conn)->findOrFail($customerId);
        $contract = TransportContract::on($conn)
            ->where('transport_customer_id', $customerId)
            ->findOrFail($contractId);

        return [$conn, $customer, $contract];
    }

    /** @return array{0: string, 1: TransportCustomer, 2: TransportContract, 3: TransportIndividualBooking} */
    private function resolveBooking(int $customerId, int $contractId, int $bookingId): array
    {
        [$conn, $customer, $contract] = $this->resolveContract($customerId, $contractId);

        $booking = TransportIndividualBooking::on($conn)
            ->where('transport_contract_id', $contract->id)
            ->with('passenger')
            ->findOrFail($bookingId);

        return [$conn, $customer, $contract, $booking];
    }

    private function authorizeOrPermission(string $ability): void
    {
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        if (! auth()->user()->can($ability)) {
            abort(403, 'Geen rechten voor deze actie.');
        }
    }
}
