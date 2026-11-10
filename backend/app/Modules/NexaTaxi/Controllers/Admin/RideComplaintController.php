<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\TaxiRideComplaint;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\TaxiDriverBlockService;
use App\Modules\NexaTaxi\Services\TaxiRideComplaintService;
use App\Modules\NexaTaxi\Support\TaxiDriverComplaintSchema;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use App\Support\Admin\AdminTenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RideComplaintController extends Controller
{
    use TenantFilter, UsesModuleDatabase;

    public function index(Request $request)
    {
        $this->assertCanViewComplaints();

        $conn = $this->moduleConnection();
        TaxiDriverComplaintSchema::ensure($conn);

        $query = TaxiRideComplaint::on($conn)->orderByDesc('id');
        // Super-admin zonder tenant (of met lege company_id-filter): alle klachten.
        // Filter/queryparam of zijbalk-tenant beperkt optioneel tot één bedrijf.
        $filterCompanyId = $this->complaintFilterCompanyId($request);
        if ($filterCompanyId) {
            $query->where('company_id', $filterCompanyId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('customer_email', 'like', "%{$s}%")
                    ->orWhere('subject', 'like', "%{$s}%");
            });
        }

        $complaints = $query->paginate((int) $request->get('per_page', 20))->withQueryString();

        $companyIds = $complaints->getCollection()->pluck('company_id')->unique()->filter()->all();
        $driverIds = $complaints->getCollection()->pluck('driver_id')->unique()->filter()->all();
        $vehicleIds = $complaints->getCollection()->pluck('vehicle_id')->unique()->filter()->all();

        $companies = Company::query()->whereIn('id', $companyIds)->get()->keyBy('id');
        $drivers = User::query()->whereIn('id', $driverIds)->get()->keyBy('id');
        $vehicles = Vehicle::on($conn)->whereIn('id', $vehicleIds)->get()->keyBy('id');

        $allCompanies = auth()->user()->hasRole('super-admin')
            ? Company::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $pulse = app(TaxiRideComplaintService::class)->adminPulse($conn, $filterCompanyId);

        return view('taxi::admin.complaints.index', [
            'complaints' => $complaints,
            'companies' => $companies,
            'drivers' => $drivers,
            'vehicles' => $vehicles,
            'allCompanies' => $allCompanies,
            'statusLabels' => TaxiRideComplaint::statusLabels(),
            'categoryLabels' => TaxiRideComplaint::CATEGORIES,
            'complaintsLatestId' => $pulse['latest_id'],
        ]);
    }

    public function pulse(Request $request, TaxiRideComplaintService $complaints): JsonResponse
    {
        $this->assertCanViewComplaints();

        $conn = $this->moduleConnection();
        $filterCompanyId = $this->complaintFilterCompanyId($request);

        return response()->json(array_merge(
            ['success' => true],
            $complaints->adminPulse($conn, $filterCompanyId)
        ));
    }

    public function show(int $complaint, TaxiRideComplaintService $complaints)
    {
        $this->assertCanViewComplaints();
        $conn = $this->moduleConnection();
        TaxiDriverComplaintSchema::ensure($conn);
        $row = TaxiRideComplaint::on($conn)->findOrFail($complaint);
        $this->assertComplaintTenant($row);

        return view('taxi::admin.complaints.show', [
            'complaint' => $row,
            'detail' => $complaints->detailPayload($conn, $row),
            'statusLabels' => TaxiRideComplaint::statusLabels(),
        ]);
    }

    public function updateStatus(Request $request, int $complaint, TaxiRideComplaintService $complaints)
    {
        $this->assertCanManageComplaints();
        $conn = $this->moduleConnection();
        $row = TaxiRideComplaint::on($conn)->findOrFail($complaint);
        $this->assertComplaintTenant($row);

        $data = $request->validate([
            'status' => 'required|string|in:'.implode(',', array_keys(TaxiRideComplaint::statusLabels())),
            'admin_notes' => 'nullable|string|max:4000',
        ]);

        $complaints->updateStatus(
            $conn,
            $row,
            $data['status'],
            $data['admin_notes'] ?? null,
            (int) auth()->id()
        );

        return redirect()
            ->route('admin.taxi.complaints.show', $complaint)
            ->with('success', __('Status bijgewerkt.'));
    }

    public function sendMessage(Request $request, int $complaint, TaxiRideComplaintService $complaints)
    {
        $this->assertCanManageComplaints();
        $conn = $this->moduleConnection();
        $row = TaxiRideComplaint::on($conn)->findOrFail($complaint);
        $this->assertComplaintTenant($row);

        $data = $request->validate([
            'title' => 'required|string|max:160',
            'body' => 'required|string|max:4000',
        ]);

        $complaints->sendDriverMessage(
            $conn,
            $row,
            $data['title'],
            $data['body'],
            (int) auth()->id()
        );

        return redirect()
            ->route('admin.taxi.complaints.show', $complaint)
            ->with('success', __('Bericht naar de chauffeur gestuurd.'));
    }

    public function blockDriver(Request $request, int $complaint, TaxiRideComplaintService $complaints)
    {
        $this->assertCanManageComplaints();
        $conn = $this->moduleConnection();
        $row = TaxiRideComplaint::on($conn)->findOrFail($complaint);
        $this->assertComplaintTenant($row);

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $complaints->blockDriverFromComplaint(
            $conn,
            $row,
            (int) auth()->id(),
            $data['reason'] ?? null
        );

        return redirect()
            ->route('admin.taxi.complaints.show', $complaint)
            ->with('success', __('Chauffeur is geblokkeerd en uitgelogd uit de app.'));
    }

    public function blocked(Request $request, TaxiDriverBlockService $blocks)
    {
        $this->assertCanViewComplaints();
        $conn = $this->moduleConnection();
        TaxiDriverComplaintSchema::ensure($conn);
        $filterCompanyId = auth()->user()?->hasRole('super-admin')
            ? app(AdminTenantScope::class)->optionalFilterTenantId($request, 'company_id')
            : $this->getTenantId();
        $items = $blocks->listBlocked($conn, $filterCompanyId ? (int) $filterCompanyId : null);
        $allCompanies = auth()->user()?->hasRole('super-admin')
            ? Company::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('taxi::admin.complaints.blocked', [
            'items' => $items,
            'allCompanies' => $allCompanies,
        ]);
    }

    public function unblock(Request $request, int $driver, TaxiDriverBlockService $blocks)
    {
        $this->assertCanManageComplaints();
        $conn = $this->moduleConnection();
        $blocks->unblock($conn, $driver);

        return redirect()
            ->route('admin.taxi.complaints.blocked')
            ->with('success', __('Chauffeur is gedeblokkeerd.'));
    }

    protected function complaintFilterCompanyId(?Request $request = null): ?int
    {
        if (auth()->user()?->hasRole('super-admin')) {
            return app(AdminTenantScope::class)->optionalFilterTenantId($request, 'company_id');
        }

        $tenantId = $this->getTenantId();

        return $tenantId ? (int) $tenantId : null;
    }

    protected function assertCanViewComplaints(): void
    {
        $user = auth()->user();
        if ($user?->hasRole('super-admin') || $user?->can('complaints.view') || $user?->can('complaints.manage')) {
            return;
        }
        abort(403, __('Geen toegang tot klachten.'));
    }

    protected function assertCanManageComplaints(): void
    {
        $user = auth()->user();
        if ($user?->hasRole('super-admin') || $user?->can('complaints.manage')) {
            return;
        }
        abort(403, __('Geen rechten om klachten te beheren.'));
    }

    protected function assertComplaintTenant(TaxiRideComplaint $complaint): void
    {
        $tenantId = $this->getTenantId();
        if ($tenantId && (int) $complaint->company_id !== (int) $tenantId) {
            abort(404);
        }
    }
}
