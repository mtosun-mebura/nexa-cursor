<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Modules\NexaTaxi\Controllers\Admin\Concerns\AuthorizesTaxiPermissions;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\RideRequestMonthlyStatsService;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Modules\NexaTaxi\Support\TaxiNotificationLogSchema;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use App\Services\CompanyEntitlementService;
use App\Support\TenantPackageCapability;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransportRideController extends Controller
{
    use AuthorizesTaxiPermissions, TenantFilter, UsesModuleDatabase;

    public function index(Request $request): View
    {
        $this->authorizeOrPermission('rides.view');

        $packageDeniedMessage = $this->contractTransportDeniedMessage();
        if ($packageDeniedMessage !== null) {
            return view('taxi::admin.ride_requests.index', [
                'rideRequests' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15),
                'vehicles' => collect(),
                'statusLabels' => RideRequest::statusLabels(),
                'notificationLogTableExists' => false,
                'monthlyStats' => null,
                'packageDeniedMessage' => $packageDeniedMessage,
                'ridesIndexRoute' => 'admin.taxi.transport_rides.index',
                'ridesBulkDestroyRoute' => 'admin.taxi.transport_rides.bulk-destroy',
                'ridesPageTitle' => 'Contractritten',
                'ridesIsContractTransport' => true,
            ]);
        }

        $conn = $this->moduleConnection();
        app(TaxiContractvervoerSchemaService::class)->ensureTablesExist($conn);
        app(TaxiContractvervoerSchemaService::class)->ensureRideRequestContractColumns($conn);

        $query = RideRequest::on($conn)->with(['vehicle.company', 'driver', 'company']);
        $this->applyRideTenantScope($query);
        $query->contractRides()
            ->excludeHiddenArchivedContractCustomers($conn);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->integer('vehicle_id'));
        }
        if ($request->filled('from')) {
            $from = parse_admin_date($request->string('from'));
            if ($from) {
                $query->whereDate('pickup_at', '>=', $from);
            }
        }
        if ($request->filled('to')) {
            $to = parse_admin_date($request->string('to'));
            if ($to) {
                $query->whereDate('pickup_at', '<=', $to);
            }
        }

        $allowedPerPage = [10, 15, 25, 50];
        $perPage = (int) $request->integer('per_page', 15);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        if (TaxiNotificationLogSchema::tableExists($conn)) {
            $query->withCount('notificationLogs');
        }

        $rideRequests = $query->orderByDesc('pickup_at')->paginate($perPage)->withQueryString();

        $vehicles = Vehicle::on($conn);
        $this->applyTenantFilter($vehicles);
        $vehicles = $vehicles->orderBy('name')->get();

        $statusLabels = RideRequest::statusLabels();

        $statsMonth = parse_admin_month($request->input('stats_month')) ?? now()->format('Y-m');
        $monthStart = Carbon::createFromFormat('Y-m', $statsMonth)->startOfMonth();
        $monthlyStats = app(RideRequestMonthlyStatsService::class)->forMonth(
            $conn,
            $monthStart,
            function ($ridesQuery) use ($conn) {
                $this->applyRideTenantScope($ridesQuery);
                $ridesQuery->contractRides()
                    ->excludeHiddenArchivedContractCustomers($conn);
            }
        );

        return view('taxi::admin.ride_requests.index', [
            'rideRequests' => $rideRequests,
            'vehicles' => $vehicles,
            'statusLabels' => $statusLabels,
            'notificationLogTableExists' => TaxiNotificationLogSchema::tableExists($conn),
            'monthlyStats' => $monthlyStats,
            'ridesIndexRoute' => 'admin.taxi.transport_rides.index',
            'ridesBulkDestroyRoute' => 'admin.taxi.transport_rides.bulk-destroy',
            'ridesPageTitle' => 'Contractritten',
            'ridesIsContractTransport' => true,
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        $this->authorizeOrPermission('rides.delete');
        $this->assertContractTransportAllowed();

        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $conn = $this->moduleConnection();
        $ids = array_values(array_unique(array_map('intval', $data['ids'])));

        $query = RideRequest::on($conn)->whereIn('id', $ids)->contractRides();
        $this->applyRideTenantScope($query);

        $deleted = 0;
        foreach ($query->get() as $ride) {
            $ride->delete();
            $deleted++;
        }

        if ($deleted === 0) {
            return redirect()->route('admin.taxi.transport_rides.index')
                ->with('error', 'Geen ritten verwijderd.');
        }

        $message = $deleted === 1
            ? '1 rit succesvol verwijderd.'
            : $deleted.' ritten succesvol verwijderd.';

        return redirect()->route('admin.taxi.transport_rides.index')->with('success', $message);
    }

    private function applyRideTenantScope($query): void
    {
        if (auth()->user()->hasRole('super-admin') && session('selected_tenant')) {
            $tenantId = (int) session('selected_tenant');
            $query->where(function ($q) use ($tenantId) {
                $q->where('company_id', $tenantId)
                    ->orWhereHas('vehicle', fn ($v) => $v->where('company_id', $tenantId));
            });

            return;
        }

        if (! auth()->user()->hasRole('super-admin') && auth()->user()->company_id) {
            $companyId = (int) auth()->user()->company_id;
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhereHas('vehicle', fn ($v) => $v->where('company_id', $companyId));
            });
        }
    }

    private function assertContractTransportAllowed(): Company
    {
        $company = Company::query()->find($this->getTenantId());
        app(CompanyEntitlementService::class)->assertCanUseContractTransport($company);

        return $company;
    }

    private function contractTransportDeniedMessage(): ?string
    {
        $company = Company::query()->find($this->getTenantId());
        $entitlements = app(CompanyEntitlementService::class);
        if ($entitlements->allows($company, TenantPackageCapability::CONTRACT_TRANSPORT)) {
            return null;
        }

        return $entitlements->deniedMessage(TenantPackageCapability::CONTRACT_TRANSPORT, $company);
    }
}
