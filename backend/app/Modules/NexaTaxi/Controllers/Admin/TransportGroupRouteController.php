<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\TransportAssignment;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportGroup;
use App\Modules\NexaTaxi\Models\TransportGroupMember;
use App\Modules\NexaTaxi\Models\TransportRouteStop;
use App\Modules\NexaTaxi\Models\TransportRouteTemplate;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\ContractOccurrenceGeneratorService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Modules\NexaTaxi\Services\TransportRoutePlannerService;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransportGroupRouteController extends Controller
{
    use UsesModuleDatabase;

    public function __construct(
        private readonly TransportRoutePlannerService $routePlanner,
        private readonly TaxiDriverEligibilityService $driverEligibility,
        private readonly ContractOccurrenceGeneratorService $occurrenceGenerator,
    ) {}

    public function edit(Request $request, int $customerId, int $contractId, int $groupId)
    {
        $this->authorizeOrPermission('rides.view');

        $context = $this->resolveRouteContext($customerId, $contractId, $groupId);
        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_groups.show', [$customerId, $contractId, $groupId])
        );

        return view('taxi::admin.transport_groups.route', array_merge($context, compact('backUrl')));
    }

    public function updateSettings(Request $request, int $customerId, int $contractId, int $groupId)
    {
        $this->authorizeOrPermission('rides.update');

        $context = $this->resolveRouteContext($customerId, $contractId, $groupId);
        $conn = $context['conn'];
        /** @var TransportRouteTemplate $template */
        $template = $context['template'];

        if ($template->route_locked) {
            throw ValidationException::withMessages([
                'route_locked' => 'Route is vastgezet. Ontgrendel eerst om instellingen te wijzigen.',
            ]);
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:200'],
            'recurrence_days' => ['required', 'array', 'min:1'],
            'recurrence_days.*' => ['integer', 'between:1,7'],
            'driver_start_mode' => ['required', Rule::in([
                TransportRouteTemplate::DRIVER_START_DEPOT,
                TransportRouteTemplate::DRIVER_START_FIRST_STOP,
            ])],
            'driver_start_address' => ['nullable', 'string', 'max:500'],
            'driver_start_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'driver_start_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'buffer_seconds' => ['required', 'integer', 'min:0', 'max:900'],
        ]);

        if ($data['driver_start_mode'] === TransportRouteTemplate::DRIVER_START_DEPOT
            && empty($data['driver_start_address'])) {
            throw ValidationException::withMessages([
                'driver_start_address' => 'Vul een depotadres in of kies start bij eerste stop.',
            ]);
        }

        $template->update([
            'label' => $data['label'],
            'recurrence_days' => array_values(array_unique(array_map('intval', $data['recurrence_days']))),
            'driver_start_mode' => $data['driver_start_mode'],
            'driver_start_address' => $data['driver_start_address'] ?? null,
            'driver_start_lat' => $data['driver_start_lat'] ?? null,
            'driver_start_lng' => $data['driver_start_lng'] ?? null,
            'buffer_seconds' => (int) $data['buffer_seconds'],
        ]);

        return redirect()
            ->route('admin.taxi.transport_groups.route.edit', [$customerId, $contractId, $groupId])
            ->with('success', 'Route-instellingen opgeslagen.');
    }

    public function calculate(Request $request, int $customerId, int $contractId, int $groupId)
    {
        $this->authorizeOrPermission('rides.update');

        $context = $this->resolveRouteContext($customerId, $contractId, $groupId);
        $conn = $context['conn'];
        /** @var TransportGroup $group */
        $group = $context['group'];
        /** @var TransportRouteTemplate $template */
        $template = $context['template'];

        if ($template->route_locked) {
            $pickups = $template->stops
                ->where('stop_type', TransportRouteStop::STOP_TYPE_PICKUP)
                ->values()
                ->map(fn (TransportRouteStop $stop) => [
                    'stop_type' => $stop->stop_type,
                    'transport_passenger_id' => $stop->transport_passenger_id,
                    'passenger_name' => $stop->passenger?->full_name,
                    'address' => $stop->address,
                    'lat' => $stop->lat !== null ? (float) $stop->lat : null,
                    'lng' => $stop->lng !== null ? (float) $stop->lng : null,
                    'sequence' => $stop->sequence,
                ])
                ->all();

            $result = $this->routePlanner->recalculateTimesForOrder($group, $template, $pickups);
        } else {
            $result = $this->routePlanner->planRoute($group, $template, $context['activeMembers']);
        }

        $this->persistStops($conn, $template, $result['stops']);
        $this->occurrenceGenerator->resyncScheduleTimesForRouteTemplate($conn, (int) $template->id);

        $redirect = redirect()
            ->route('admin.taxi.transport_groups.route.edit', [$customerId, $contractId, $groupId])
            ->with('success', 'Route berekend en opgeslagen.');

        if ($result['departure_time'] !== null) {
            $redirect->with('route_departure_time', substr($result['departure_time'], 0, 5));
        }

        if ($result['warnings'] !== []) {
            $redirect->with('route_warnings', $result['warnings']);
        }

        return $redirect;
    }

    public function updateStops(Request $request, int $customerId, int $contractId, int $groupId)
    {
        $this->authorizeOrPermission('rides.update');

        $context = $this->resolveRouteContext($customerId, $contractId, $groupId);
        $conn = $context['conn'];
        /** @var TransportGroup $group */
        $group = $context['group'];
        /** @var TransportRouteTemplate $template */
        $template = $context['template'];

        $data = $request->validate([
            'stop_order' => ['required', 'array', 'min:1'],
            'stop_order.*' => ['integer'],
        ]);

        $existingPickups = $template->stops
            ->where('stop_type', TransportRouteStop::STOP_TYPE_PICKUP)
            ->keyBy('id');

        $orderedPickups = [];
        foreach ($data['stop_order'] as $stopId) {
            $stop = $existingPickups->get((int) $stopId);
            if (! $stop) {
                continue;
            }
            $orderedPickups[] = [
                'stop_type' => $stop->stop_type,
                'transport_passenger_id' => $stop->transport_passenger_id,
                'passenger_name' => $stop->passenger?->full_name,
                'address' => $stop->address,
                'lat' => $stop->lat !== null ? (float) $stop->lat : null,
                'lng' => $stop->lng !== null ? (float) $stop->lng : null,
                'sequence' => $stop->sequence,
            ];
        }

        if ($orderedPickups === []) {
            throw ValidationException::withMessages([
                'stop_order' => 'Geen geldige stops geselecteerd.',
            ]);
        }

        $result = $this->routePlanner->recalculateTimesForOrder($group, $template, $orderedPickups);
        $this->persistStops($conn, $template, $result['stops']);
        $this->occurrenceGenerator->resyncScheduleTimesForRouteTemplate($conn, (int) $template->id);

        $redirect = redirect()
            ->route('admin.taxi.transport_groups.route.edit', [$customerId, $contractId, $groupId])
            ->with('success', $template->route_locked
                ? 'Tijden herberekend.'
                : 'Volgorde opgeslagen en tijden herberekend.');

        if ($result['departure_time'] !== null) {
            $redirect->with('route_departure_time', substr($result['departure_time'], 0, 5));
        }

        return $redirect;
    }

    public function toggleLock(Request $request, int $customerId, int $contractId, int $groupId)
    {
        $this->authorizeOrPermission('rides.update');

        $context = $this->resolveRouteContext($customerId, $contractId, $groupId);
        /** @var TransportRouteTemplate $template */
        $template = $context['template'];

        if (! $template->route_locked && $template->stops->where('stop_type', TransportRouteStop::STOP_TYPE_PICKUP)->isEmpty()) {
            throw ValidationException::withMessages([
                'route' => 'Bereken eerst een route voordat u deze vastzet.',
            ]);
        }

        $template->update(['route_locked' => ! $template->route_locked]);

        return redirect()
            ->route('admin.taxi.transport_groups.route.edit', [$customerId, $contractId, $groupId])
            ->with('success', $template->route_locked ? 'Route vastgezet.' : 'Route ontgrendeld.');
    }

    public function updateAssignment(Request $request, int $customerId, int $contractId, int $groupId)
    {
        $this->authorizeOrPermission('rides.update');

        $context = $this->resolveRouteContext($customerId, $contractId, $groupId);
        $conn = $context['conn'];
        /** @var TransportRouteTemplate $template */
        $template = $context['template'];

        $data = $request->validate([
            'driver_id' => ['nullable', 'integer'],
            'vehicle_id' => ['nullable', 'integer'],
        ]);

        $driverId = ! empty($data['driver_id']) ? (int) $data['driver_id'] : null;
        $vehicleId = ! empty($data['vehicle_id']) ? (int) $data['vehicle_id'] : null;

        if ($driverId) {
            $isDriver = $this->driverEligibility->buildChauffeurQuery((int) $template->company_id)
                ->where('users.id', $driverId)
                ->exists();
            if (! $isDriver) {
                throw ValidationException::withMessages(['driver_id' => 'Ongeldige chauffeur voor dit bedrijf.']);
            }
        }

        if ($vehicleId) {
            $vehicleExists = Vehicle::on($conn)
                ->where('company_id', $template->company_id)
                ->where('id', $vehicleId)
                ->exists();
            if (! $vehicleExists) {
                throw ValidationException::withMessages(['vehicle_id' => 'Ongeldig voertuig voor dit bedrijf.']);
            }
        }

        DB::connection($conn)->transaction(function () use ($conn, $template, $driverId, $vehicleId) {
            TransportAssignment::on($conn)
                ->where('assignable_type', TransportRouteTemplate::ASSIGNABLE_TYPE)
                ->where('assignable_id', $template->id)
                ->update(['active' => false]);

            if ($driverId || $vehicleId) {
                TransportAssignment::on($conn)->create([
                    'company_id' => $template->company_id,
                    'assignable_type' => TransportRouteTemplate::ASSIGNABLE_TYPE,
                    'assignable_id' => $template->id,
                    'driver_id' => $driverId,
                    'vehicle_id' => $vehicleId,
                    'active' => true,
                ]);
            }
        });

        $this->occurrenceGenerator->generateForRouteTemplate($conn, (int) $template->id);
        $this->occurrenceGenerator->syncRouteTemplateAssignment($conn, (int) $template->id, $driverId, $vehicleId);
        $this->occurrenceGenerator->resyncScheduleTimesForRouteTemplate($conn, (int) $template->id);

        return redirect()
            ->route('admin.taxi.transport_groups.route.edit', [$customerId, $contractId, $groupId])
            ->with('success', 'Chauffeur en voertuig opgeslagen.');
    }

    /**
     * @param  list<array<string, mixed>>  $stops
     */
    private function persistStops(string $conn, TransportRouteTemplate $template, array $stops): void
    {
        DB::connection($conn)->transaction(function () use ($conn, $template, $stops) {
            TransportRouteStop::on($conn)
                ->where('transport_route_template_id', $template->id)
                ->delete();

            foreach ($stops as $stop) {
                TransportRouteStop::on($conn)->create([
                    'transport_route_template_id' => $template->id,
                    'sequence' => (int) $stop['sequence'],
                    'stop_type' => $stop['stop_type'],
                    'transport_passenger_id' => $stop['transport_passenger_id'] ?? null,
                    'address' => $stop['address'],
                    'lat' => $stop['lat'] ?? null,
                    'lng' => $stop['lng'] ?? null,
                    'planned_at_time' => strlen((string) $stop['planned_at_time']) === 5
                        ? $stop['planned_at_time'].':00'
                        : $stop['planned_at_time'],
                ]);
            }
        });

        $template->unsetRelation('stops');
        $template->load(['stops.passenger']);
    }

    /**
     * @return array{
     *   conn: string,
     *   customer: TransportCustomer,
     *   contract: TransportContract,
     *   group: TransportGroup,
     *   template: TransportRouteTemplate,
     *   activeMembers: \Illuminate\Support\Collection,
     *   drivers: \Illuminate\Support\Collection,
     *   vehicles: \Illuminate\Support\Collection,
     *   assignment: TransportAssignment|null
     * }
     */
    private function resolveRouteContext(int $customerId, int $contractId, int $groupId): array
    {
        $conn = $this->moduleConnection();
        $customer = TransportCustomer::on($conn)->findOrFail($customerId);
        $contract = TransportContract::on($conn)
            ->where('transport_customer_id', $customerId)
            ->findOrFail($contractId);
        $group = TransportGroup::on($conn)
            ->where('transport_contract_id', $contract->id)
            ->findOrFail($groupId);

        $template = TransportRouteTemplate::on($conn)
            ->where('transport_group_id', $group->id)
            ->where('active', true)
            ->with(['stops.passenger', 'assignment'])
            ->first();

        if (! $template) {
            $template = TransportRouteTemplate::on($conn)->create([
                'company_id' => $group->company_id,
                'transport_group_id' => $group->id,
                'label' => $group->name.' route',
                'recurrence_days' => TransportRouteTemplate::defaultRecurrenceDays(),
                'driver_start_mode' => TransportRouteTemplate::DRIVER_START_DEPOT,
                'buffer_seconds' => 120,
                'route_locked' => false,
                'active' => true,
            ]);
            $template->load(['stops.passenger', 'assignment']);
        }

        $today = now()->toDateString();
        $activeMembers = TransportGroupMember::on($conn)
            ->where('transport_group_id', $group->id)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $today);
            })
            ->with(['passenger'])
            ->orderBy('sort_hint')
            ->orderBy('id')
            ->get();

        $drivers = $this->driverEligibility
            ->buildChauffeurQuery((int) $group->company_id)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        $vehicles = Vehicle::on($conn)
            ->where('company_id', $group->company_id)
            ->orderBy('name')
            ->get(['id', 'name', 'license_plate']);

        return [
            'conn' => $conn,
            'customer' => $customer,
            'contract' => $contract,
            'group' => $group,
            'template' => $template,
            'activeMembers' => $activeMembers,
            'drivers' => $drivers,
            'vehicles' => $vehicles,
            'assignment' => $template->assignment,
        ];
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
