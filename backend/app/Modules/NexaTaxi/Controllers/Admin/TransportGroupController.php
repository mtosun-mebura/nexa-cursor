<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportGroup;
use App\Modules\NexaTaxi\Models\TransportGroupMember;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\TransportRouteTemplate;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Modules\NexaTaxi\Services\TransportGroupRouteSyncService;
use App\Modules\NexaTaxi\Services\TransportRoutePlannerService;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransportGroupController extends Controller
{
    use UsesModuleDatabase;

    public function __construct(
        private readonly TransportGroupRouteSyncService $routeSync,
        private readonly TaxiDriverEligibilityService $driverEligibility,
    ) {}

    public function index(Request $request, int $customerId, int $contractId)
    {
        $this->authorizeOrPermission('rides.view');

        [$conn, $customer, $contract] = $this->resolveContract($customerId, $contractId);

        $query = TransportGroup::on($conn)->where('transport_contract_id', $contract->id);

        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('destination_address', 'like', "%{$s}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('active', $request->string('active') === '1');
        }

        $groups = $query->orderBy('name')->paginate(20)->withQueryString();

        $memberCounts = TransportGroupMember::on($conn)
            ->selectRaw('transport_group_id, count(*) as total')
            ->whereIn('transport_group_id', $groups->pluck('id'))
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now()->toDateString());
            })
            ->groupBy('transport_group_id')
            ->pluck('total', 'transport_group_id');

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_customers.contract_show', [$customerId, $contractId])
        );

        return view('taxi::admin.transport_groups.index', compact(
            'customer',
            'contract',
            'groups',
            'memberCounts',
            'backUrl'
        ));
    }

    public function create(Request $request, int $customerId, int $contractId)
    {
        $this->authorizeOrPermission('rides.create');

        [, $customer, $contract] = $this->resolveContract($customerId, $contractId);

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_groups.index', [$customerId, $contractId])
        );

        return view('taxi::admin.transport_groups.create', compact('customer', 'contract', 'backUrl'));
    }

    public function store(Request $request, int $customerId, int $contractId)
    {
        $this->authorizeOrPermission('rides.create');

        $data = $this->validateGroup($request);
        $data = $this->normalizeGroupDepartureFields($data);

        [$conn, , $contract] = $this->resolveContract($customerId, $contractId);

        $group = TransportGroup::on($conn)->create(array_merge($data, [
            'company_id' => $contract->company_id,
            'transport_contract_id' => $contract->id,
            'active' => $request->boolean('active', true),
            'has_return_trip' => $request->boolean('has_return_trip'),
        ]));

        $this->routeSync->syncDepartureFromGroup($conn, $group);
        if ($group->has_return_trip) {
            $this->routeSync->syncReturnRoute($conn, $group);
        }

        $showUrl = route('admin.taxi.transport_groups.show', [$customerId, $contractId, $group->id]);
        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_groups.index', [$customerId, $contractId])
        );
        if ($request->filled('return')) {
            $showUrl = transport_admin_url_with_return($showUrl, $backUrl);
        }

        return redirect($showUrl)
            ->with('success', 'Groep aangemaakt. Voeg nu passagiers toe.');
    }

    public function show(Request $request, int $customerId, int $contractId, int $groupId)
    {
        $this->authorizeOrPermission('rides.view');

        [$conn, $customer, $contract, $group] = $this->resolveGroup($customerId, $contractId, $groupId);

        $activeMembers = $this->activeMembersQuery($conn, $group->id)->get();

        $availablePassengers = $this->availablePassengersQuery($conn, $contract, $group)->get();
        $hasContractPassengers = $this->contractHasActivePassengers($conn, $contract);

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_groups.index', [$customerId, $contractId])
        );

        $routeTemplate = TransportRouteTemplate::on($conn)
            ->where('transport_group_id', $group->id)
            ->where('active', true)
            ->where(function ($q) {
                $q->where('direction', TransportRouteTemplate::DIRECTION_OUTBOUND)
                    ->orWhereNull('direction');
            })
            ->with(['stops.passenger', 'assignment.driver', 'assignment.vehicle'])
            ->first();

        $returnRouteTemplate = TransportRouteTemplate::on($conn)
            ->where('transport_group_id', $group->id)
            ->where('active', true)
            ->where('direction', TransportRouteTemplate::DIRECTION_RETURN)
            ->with(['stops.passenger'])
            ->first();

        $routeContext = array_merge(
            $this->loadRouteContext($routeTemplate),
            $this->loadReturnRouteContext($returnRouteTemplate)
        );
        extract($routeContext);

        return view('taxi::admin.transport_groups.show', compact(
            'customer',
            'contract',
            'group',
            'activeMembers',
            'availablePassengers',
            'hasContractPassengers',
            'backUrl',
            'routeTemplate',
            'returnRouteTemplate',
            'returnRouteStops',
            'routePickupStops',
            'routeDestinationStop',
            'routeDepartureTime',
            'routeDisplayDriver',
            'routeDisplayVehicle'
        ));
    }

    public function edit(Request $request, int $customerId, int $contractId, int $groupId)
    {
        $this->authorizeOrPermission('rides.update');

        [, $customer, $contract, $group] = $this->resolveGroup($customerId, $contractId, $groupId);

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_groups.show', [$customerId, $contractId, $groupId])
        );

        return view('taxi::admin.transport_groups.edit', compact('customer', 'contract', 'group', 'backUrl'));
    }

    public function update(Request $request, int $customerId, int $contractId, int $groupId)
    {
        $this->authorizeOrPermission('rides.update');

        $data = $this->validateGroup($request);
        $data = $this->normalizeGroupDepartureFields($data);

        [, , , $group] = $this->resolveGroup($customerId, $contractId, $groupId);

        $group->update(array_merge($data, [
            'active' => $request->boolean('active'),
            'has_return_trip' => $request->boolean('has_return_trip'),
        ]));

        $conn = $group->getConnectionName();
        $outboundFieldsChanged = $group->wasChanged([
            'departure_address',
            'departure_lat',
            'departure_lng',
            'destination_address',
            'destination_lat',
            'destination_lng',
            'destination_arrival_time',
        ]);
        $returnFieldsChanged = $group->wasChanged([
            'has_return_trip',
            'return_pickup_time',
            'return_boarding_delay_minutes',
        ]);
        $group = $group->fresh();

        if ($outboundFieldsChanged) {
            $routeResult = $this->routeSync->syncDepartureAndRecalculate($conn, $group);
            $routeMessage = $this->formatRouteSyncMessage($routeResult);
        } elseif ($returnFieldsChanged) {
            $this->routeSync->syncDepartureFromGroup($conn, $group);
            $routeResult = $this->routeSync->syncReturnRoute($conn, $group);
            $routeMessage = $this->formatRouteSyncMessage($routeResult);
        } else {
            $this->routeSync->syncDepartureFromGroup($conn, $group);
            $routeMessage = null;
        }

        $showUrl = route('admin.taxi.transport_groups.show', [
            $customerId,
            $contractId,
            $groupId,
            'tab' => 'gegevens',
        ]);
        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_groups.show', [
                $customerId,
                $contractId,
                $groupId,
                'tab' => 'gegevens',
            ])
        );
        if ($request->filled('return')) {
            $showUrl = transport_admin_url_with_return($showUrl, $backUrl);
        }

        $success = 'Groep opgeslagen.';
        if ($routeMessage) {
            $success .= ' '.$routeMessage;
        }

        return redirect($showUrl)
            ->with('success', $success);
    }

    public function destroy(Request $request, int $customerId, int $contractId, int $groupId)
    {
        $this->authorizeOrPermission('rides.delete');

        [, , , $group] = $this->resolveGroup($customerId, $contractId, $groupId);

        $group->update(['active' => false]);

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_groups.index', [$customerId, $contractId])
        );

        return redirect($backUrl)
            ->with('success', 'Groep gedeactiveerd.');
    }

    public function memberStore(Request $request, int $customerId, int $contractId, int $groupId)
    {
        $this->authorizeOrPermission('rides.update');

        $request->merge([
            'valid_from' => parse_admin_date($request->input('valid_from')),
        ]);

        $data = $request->validate([
            'transport_passenger_id' => ['required', 'array', 'min:1'],
            'transport_passenger_id.*' => ['integer', 'distinct'],
            'valid_from' => ['nullable', 'date'],
        ]);

        [$conn, $customer, $contract, $group] = $this->resolveGroup($customerId, $contractId, $groupId);

        $validFrom = $data['valid_from'] ?? now()->toDateString();
        $passengerIds = array_values(array_unique(array_map('intval', $data['transport_passenger_id'])));

        $addedNames = [];
        $skippedNames = [];

        foreach ($passengerIds as $passengerId) {
            $passenger = TransportPassenger::on($conn)
                ->where('transport_contract_id', $contract->id)
                ->where('active', true)
                ->find($passengerId);

            if (! $passenger) {
                continue;
            }

            $result = $this->addPassengerToGroup($conn, $group, $passenger, $validFrom);

            if ($result === 'skipped') {
                $skippedNames[] = $passenger->full_name;
            } else {
                $addedNames[] = $passenger->full_name;
            }
        }

        if ($addedNames === [] && $skippedNames !== []) {
            throw ValidationException::withMessages([
                'transport_passenger_id' => 'Geselecteerde passagiers zitten al in de groep.',
            ]);
        }

        if ($addedNames === []) {
            throw ValidationException::withMessages([
                'transport_passenger_id' => 'Selecteer minimaal één geldige passagier.',
            ]);
        }

        $success = count($addedNames) === 1
            ? $addedNames[0].' toegevoegd aan de groep.'
            : count($addedNames).' passagiers toegevoegd aan de groep.';

        if ($skippedNames !== []) {
            $success .= ' '.count($skippedNames).' overgeslagen (al lid).';
        }

        $routeMessage = $this->refreshRouteAfterMemberChange($conn, $group);
        if ($routeMessage) {
            $success .= ' '.$routeMessage;
        }

        return $this->memberChangeResponse($request, $conn, $customer, $contract, $group, $success);
    }

    /**
     * @return 'added'|'reactivated'|'skipped'
     */
    private function addPassengerToGroup(string $conn, TransportGroup $group, TransportPassenger $passenger, string $validFrom): string
    {
        $existing = TransportGroupMember::on($conn)
            ->where('transport_group_id', $group->id)
            ->where('transport_passenger_id', $passenger->id)
            ->first();

        if ($existing && $this->membershipIsActive($existing)) {
            return 'skipped';
        }

        if ($existing) {
            $existing->update([
                'valid_from' => $validFrom,
                'valid_until' => null,
            ]);

            return 'reactivated';
        }

        TransportGroupMember::on($conn)->create([
            'transport_group_id' => $group->id,
            'transport_passenger_id' => $passenger->id,
            'valid_from' => $validFrom,
        ]);

        return 'added';
    }

    public function memberRemove(Request $request, int $customerId, int $contractId, int $groupId, int $memberId)
    {
        $this->authorizeOrPermission('rides.update');

        [$conn, $customer, $contract, $group] = $this->resolveGroup($customerId, $contractId, $groupId);

        $member = TransportGroupMember::on($conn)
            ->where('transport_group_id', $group->id)
            ->findOrFail($memberId);

        if (! $this->membershipIsActive($member)) {
            return $this->memberChangeResponse(
                $request,
                $conn,
                $customer,
                $contract,
                $group,
                'Lidmaatschap was al beëindigd.'
            );
        }

        // valid_until is inclusief: einddatum = gisteren zodat lid direct uit actieve lijst verdwijnt.
        $member->update(['valid_until' => now()->subDay()->toDateString()]);

        $success = 'Passagier uit groep gehaald. Historie blijft bewaard.';
        $routeMessage = $this->refreshRouteAfterMemberChange($conn, $group);
        if ($routeMessage) {
            $success .= ' '.$routeMessage;
        }

        return $this->memberChangeResponse(
            $request,
            $conn,
            $customer,
            $contract,
            $group,
            $success
        );
    }

    /** @return array{0: string, 1: TransportCustomer, 2: TransportContract} */
    private function resolveContract(int $customerId, int $contractId): array
    {
        $conn = $this->moduleConnection();
        app(TaxiContractvervoerSchemaService::class)->ensureTransportGroupReturnTripColumns($conn);
        $customer = TransportCustomer::on($conn)->findOrFail($customerId);
        $contract = TransportContract::on($conn)
            ->where('transport_customer_id', $customerId)
            ->findOrFail($contractId);

        return [$conn, $customer, $contract];
    }

    /** @return array{0: string, 1: TransportCustomer, 2: TransportContract, 3: TransportGroup} */
    private function resolveGroup(int $customerId, int $contractId, int $groupId): array
    {
        [$conn, $customer, $contract] = $this->resolveContract($customerId, $contractId);

        $group = TransportGroup::on($conn)
            ->where('transport_contract_id', $contract->id)
            ->findOrFail($groupId);

        return [$conn, $customer, $contract, $group];
    }

    /** @return array<string, mixed> */
    private function normalizeGroupDepartureFields(array $data): array
    {
        $address = trim((string) ($data['departure_address'] ?? ''));
        if ($address === '') {
            $data['departure_address'] = null;
            $data['departure_lat'] = null;
            $data['departure_lng'] = null;
        } else {
            $data['departure_address'] = $address;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function validateGroup(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'departure_address' => ['nullable', 'string', 'max:500'],
            'departure_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'departure_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'destination_address' => ['required', 'string', 'max:500'],
            'destination_arrival_time' => ['required', 'date_format:H:i'],
            'destination_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'destination_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'has_return_trip' => ['sometimes', 'boolean'],
            'return_pickup_time' => ['nullable', 'date_format:H:i'],
            'return_boarding_delay_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($request->boolean('has_return_trip') && empty($data['return_pickup_time'])) {
            throw ValidationException::withMessages([
                'return_pickup_time' => 'Vul de ophaaltijd voor de terugweg in.',
            ]);
        }

        if (! $request->boolean('has_return_trip')) {
            $data['return_pickup_time'] = null;
        } elseif (! empty($data['return_pickup_time']) && strlen((string) $data['return_pickup_time']) === 5) {
            $data['return_pickup_time'] .= ':00';
        }

        if (! empty($data['destination_arrival_time']) && strlen((string) $data['destination_arrival_time']) === 5) {
            $data['destination_arrival_time'] .= ':00';
        }

        $data['return_boarding_delay_minutes'] = max(
            0,
            min(120, (int) ($data['return_boarding_delay_minutes'] ?? TransportGroup::DEFAULT_RETURN_BOARDING_DELAY_MINUTES))
        );

        return $data;
    }

    private function activeMembersQuery(string $conn, int $groupId)
    {
        $today = now()->toDateString();

        return TransportGroupMember::on($conn)
            ->where('transport_group_id', $groupId)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $today);
            })
            ->with(['passenger' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'pickup_address', 'phone')])
            ->orderBy('sort_hint')
            ->orderBy('id');
    }

    private function availablePassengersQuery(string $conn, TransportContract $contract, TransportGroup $group)
    {
        $activeMemberPassengerIds = $this->activeMembersQuery($conn, $group->id)->pluck('transport_passenger_id');

        $query = TransportPassenger::on($conn)
            ->where('transport_contract_id', $contract->id)
            ->where('active', true);

        if ($activeMemberPassengerIds->isNotEmpty()) {
            $query->whereNotIn('id', $activeMemberPassengerIds);
        }

        return $query->orderBy('last_name')->orderBy('first_name');
    }

    private function contractHasActivePassengers(string $conn, TransportContract $contract): bool
    {
        return TransportPassenger::on($conn)
            ->where('transport_contract_id', $contract->id)
            ->where('active', true)
            ->exists();
    }

    /** @return array{availablePassengers: \Illuminate\Support\Collection, hasContractPassengers: bool} */
    private function memberModalViewData(string $conn, TransportContract $contract, TransportGroup $group): array
    {
        return [
            'availablePassengers' => $this->availablePassengersQuery($conn, $contract, $group)->get(),
            'hasContractPassengers' => $this->contractHasActivePassengers($conn, $contract),
        ];
    }

    private function renderMemberModalBody(
        TransportCustomer $customer,
        TransportContract $contract,
        TransportGroup $group,
        array $modalData,
    ): string {
        return view('taxi::admin.transport_groups.partials.add-members-modal-body', array_merge(
            ['customer' => $customer, 'contract' => $contract, 'group' => $group],
            $modalData
        ))->render();
    }

    private function membershipIsActive(TransportGroupMember $member): bool
    {
        if ($member->valid_until === null) {
            return true;
        }

        return $member->valid_until >= now()->toDateString();
    }

    private function refreshRouteAfterMemberChange(string $conn, TransportGroup $group): ?string
    {
        $this->routeSync->syncDepartureFromGroup($conn, $group);

        return $this->recalculateGroupRouteAfterMemberChange($conn, $group, forceFullPlan: true);
    }

    /**
     * @return array{
     *   routeTemplate: TransportRouteTemplate|null,
     *   routePickupStops: \Illuminate\Support\Collection,
     *   routeDestinationStop: \App\Modules\NexaTaxi\Models\TransportRouteStop|null,
     *   routeDepartureTime: string|null,
     *   routeDisplayDriver: \App\Models\User|null,
     *   routeDisplayVehicle: \App\Modules\NexaTaxi\Models\Vehicle|null
     * }
     */
    private function loadRouteContext(?TransportRouteTemplate $routeTemplate): array
    {
        $routePickupStops = collect();
        $routeDestinationStop = null;
        $routeDepartureTime = null;
        $routeDisplayDriver = null;
        $routeDisplayVehicle = $routeTemplate?->assignment?->vehicle;

        if ($routeTemplate) {
            $routePickupStops = $routeTemplate->stops->where('stop_type', 'pickup')->values();
            $routeDestinationStop = $routeTemplate->stops->firstWhere('stop_type', 'destination');
            if ($routePickupStops->isNotEmpty()) {
                $routeDepartureTime = app(TransportRoutePlannerService::class)
                    ->estimateDepartureTimeForTemplate(
                        $routeTemplate,
                        $routePickupStops,
                        $routePickupStops->first()
                    );
            }

            $driver = $routeTemplate->assignment?->driver;
            if ($driver && $this->driverEligibility->isChauffeurForCompany(
                $driver,
                (int) $routeTemplate->company_id
            )) {
                $routeDisplayDriver = $driver;
            }
        }

        return compact(
            'routeTemplate',
            'routePickupStops',
            'routeDestinationStop',
            'routeDepartureTime',
            'routeDisplayDriver',
            'routeDisplayVehicle'
        );
    }

    /**
     * @return array{
     *   returnRouteTemplate: TransportRouteTemplate|null,
     *   returnRouteStops: \Illuminate\Support\Collection
     * }
     */
    private function loadReturnRouteContext(?TransportRouteTemplate $returnRouteTemplate): array
    {
        $returnRouteStops = collect();
        if ($returnRouteTemplate) {
            $returnRouteStops = $returnRouteTemplate->stops->sortBy('sequence')->values();
        }

        return compact('returnRouteTemplate', 'returnRouteStops');
    }

    /** @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse */
    private function memberChangeResponse(
        Request $request,
        string $conn,
        TransportCustomer $customer,
        TransportContract $contract,
        TransportGroup $group,
        string $successMessage,
    ) {
        if (! $request->expectsJson()) {
            return redirect()->route('admin.taxi.transport_groups.show', [
                $customer->id,
                $contract->id,
                $group->id,
                'tab' => 'leden',
            ])->with('success', $successMessage);
        }

        $activeMembers = $this->activeMembersQuery($conn, $group->id)->get();
        $routeTemplate = TransportRouteTemplate::on($conn)
            ->where('transport_group_id', $group->id)
            ->where('active', true)
            ->where(function ($q) {
                $q->where('direction', TransportRouteTemplate::DIRECTION_OUTBOUND)
                    ->orWhereNull('direction');
            })
            ->with(['stops.passenger', 'assignment.driver', 'assignment.vehicle'])
            ->first();
        $returnRouteTemplate = TransportRouteTemplate::on($conn)
            ->where('transport_group_id', $group->id)
            ->where('active', true)
            ->where('direction', TransportRouteTemplate::DIRECTION_RETURN)
            ->with(['stops.passenger'])
            ->first();
        $routeContext = array_merge(
            $this->loadRouteContext($routeTemplate),
            $this->loadReturnRouteContext($returnRouteTemplate)
        );
        $modalData = $this->memberModalViewData($conn, $contract, $group);

        return response()->json([
            'success' => $successMessage,
            'members_count' => $activeMembers->count(),
            'members_html' => view('taxi::admin.transport_groups.partials.members-table', [
                'customer' => $customer,
                'contract' => $contract,
                'group' => $group,
                'activeMembers' => $activeMembers,
            ])->render(),
            'route_html' => view('taxi::admin.transport_groups.partials.route-panel', array_merge(
                ['customer' => $customer, 'contract' => $contract, 'group' => $group],
                $routeContext
            ))->render(),
            'member_modal_html' => $this->renderMemberModalBody($customer, $contract, $group, $modalData),
            'passengers_picker_html' => view('taxi::admin.transport_groups.partials.add-members-passenger-picker', [
                'availablePassengers' => $modalData['availablePassengers'],
            ])->render(),
        ]);
    }

    private function recalculateGroupRouteAfterMemberChange(
        string $conn,
        TransportGroup $group,
        bool $forceFullPlan = false,
    ): ?string {
        $result = $this->routeSync->recalculateForGroup($conn, $group, $forceFullPlan);

        return $this->formatRouteSyncMessage($result);
    }

    /**
     * @param  array{recalculated: bool, warnings: list<string>, message: string|null}  $result
     */
    private function formatRouteSyncMessage(array $result): ?string
    {
        if (! ($result['recalculated'] ?? false)) {
            return null;
        }

        $message = $result['message'] ?? 'Route automatisch herberekend.';
        $warnings = $result['warnings'] ?? [];
        if ($warnings !== []) {
            $message .= ' '.implode(' ', $warnings);
        }

        return $message;
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
