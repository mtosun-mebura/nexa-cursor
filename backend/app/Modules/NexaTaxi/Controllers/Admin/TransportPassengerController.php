<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportGroup;
use App\Modules\NexaTaxi\Models\TransportGroupMember;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use Illuminate\Http\Request;

class TransportPassengerController extends Controller
{
    use UsesModuleDatabase;

    public function index(Request $request, int $customerId, int $contractId)
    {
        $this->authorizeOrPermission('rides.view');

        [$conn, $customer, $contract] = $this->resolveContract($customerId, $contractId);

        $query = TransportPassenger::on($conn)
            ->where('transport_contract_id', $contract->id);

        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('pickup_address', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('active', $request->string('active') === '1');
        }

        $passengers = $query->orderBy('last_name')->orderBy('first_name')->paginate(20)->withQueryString();

        $activeCount = TransportPassenger::on($conn)
            ->where('transport_contract_id', $contract->id)
            ->where('active', true)
            ->count();

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_customers.contract_show', [$customerId, $contractId])
        );

        return view('taxi::admin.transport_passengers.index', compact(
            'customer',
            'contract',
            'passengers',
            'activeCount',
            'backUrl'
        ));
    }

    public function create(Request $request, int $customerId, int $contractId)
    {
        $this->authorizeOrPermission('rides.create');

        [, $customer, $contract] = $this->resolveContract($customerId, $contractId);

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_passengers.index', [$customerId, $contractId])
        );

        return view('taxi::admin.transport_passengers.create', compact('customer', 'contract', 'backUrl'));
    }

    public function store(Request $request, int $customerId, int $contractId)
    {
        $this->authorizeOrPermission('rides.create');

        $data = $this->validatePassenger($request);

        [$conn, , $contract] = $this->resolveContract($customerId, $contractId);

        TransportPassenger::on($conn)->create(array_merge($data, [
            'company_id' => $contract->company_id,
            'transport_contract_id' => $contract->id,
            'active' => $request->boolean('active', true),
        ]));

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_passengers.index', [$customerId, $contractId])
        );

        return redirect($backUrl)
            ->with('success', 'Passagier aangemaakt.');
    }

    public function edit(Request $request, int $customerId, int $contractId, int $passengerId)
    {
        $this->authorizeOrPermission('rides.update');

        [$conn, $customer, $contract, $passenger] = $this->resolvePassenger($customerId, $contractId, $passengerId);

        $groupNames = $this->activeGroupNamesForPassenger($conn, $passenger->id);

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_passengers.index', [$customerId, $contractId])
        );

        return view('taxi::admin.transport_passengers.edit', compact(
            'customer',
            'contract',
            'passenger',
            'groupNames',
            'backUrl'
        ));
    }

    public function update(Request $request, int $customerId, int $contractId, int $passengerId)
    {
        $this->authorizeOrPermission('rides.update');

        $data = $this->validatePassenger($request);

        [, , , $passenger] = $this->resolvePassenger($customerId, $contractId, $passengerId);

        $passenger->update(array_merge($data, [
            'active' => $request->boolean('active'),
        ]));

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_passengers.index', [$customerId, $contractId])
        );

        return redirect($backUrl)
            ->with('success', 'Passagier opgeslagen.');
    }

    public function destroy(Request $request, int $customerId, int $contractId, int $passengerId)
    {
        $this->authorizeOrPermission('rides.delete');

        [, , , $passenger] = $this->resolvePassenger($customerId, $contractId, $passengerId);

        $passenger->update(['active' => false]);

        $backUrl = transport_admin_back_url(
            $request,
            route('admin.taxi.transport_passengers.index', [$customerId, $contractId])
        );

        return redirect($backUrl)
            ->with('success', 'Passagier gedeactiveerd.');
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

    /** @return array{0: string, 1: TransportCustomer, 2: TransportContract, 3: TransportPassenger} */
    private function resolvePassenger(int $customerId, int $contractId, int $passengerId): array
    {
        [$conn, $customer, $contract] = $this->resolveContract($customerId, $contractId);

        $passenger = TransportPassenger::on($conn)
            ->where('transport_contract_id', $contract->id)
            ->findOrFail($passengerId);

        return [$conn, $customer, $contract, $passenger];
    }

    /** @return array<string, mixed> */
    private function validatePassenger(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'pickup_address' => ['required', 'string', 'max:500'],
            'pickup_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string'],
        ]);
    }

  private function activeGroupNamesForPassenger(string $conn, int $passengerId): array
    {
        $today = now()->toDateString();

        $groupIds = TransportGroupMember::on($conn)
            ->where('transport_passenger_id', $passengerId)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $today);
            })
            ->pluck('transport_group_id');

        if ($groupIds->isEmpty()) {
            return [];
        }

        return TransportGroup::on($conn)
            ->whereIn('id', $groupIds)
            ->where('active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
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
