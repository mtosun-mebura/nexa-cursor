<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Controllers\Admin\Concerns\AuthorizesTaxiPermissions;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportScheduleException;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use Illuminate\Http\Request;

class TransportScheduleExceptionController extends Controller
{
    use AuthorizesTaxiPermissions, TenantFilter, UsesModuleDatabase;

    public function index(Request $request)
    {
        $this->authorizeOrPermission('rides.view');

        $conn = $this->moduleConnection();
        $companyId = $this->getTenantId();

        $query = TransportScheduleException::on($conn)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('contract')
            ->orderByDesc('exception_date');

        if ($request->filled('from')) {
            $from = parse_admin_date($request->input('from'));
            if ($from) {
                $query->whereDate('exception_date', '>=', $from);
            }
        }
        if ($request->filled('to')) {
            $to = parse_admin_date($request->input('to'));
            if ($to) {
                $query->whereDate('exception_date', '<=', $to);
            }
        }

        $exceptions = $query->paginate(30)->withQueryString();

        $contracts = TransportContract::on($conn)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('taxi::admin.transport_schedule_exceptions.index', compact('exceptions', 'contracts'));
    }

    public function store(Request $request)
    {
        $this->authorizeOrPermission('rides.create');

        $request->merge([
            'exception_date' => parse_admin_date($request->input('exception_date')),
        ]);

        $data = $request->validate([
            'exception_date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:200'],
            'transport_contract_id' => ['nullable', 'integer'],
            'active' => ['boolean'],
        ]);

        $conn = $this->moduleConnection();
        $contractId = ! empty($data['transport_contract_id']) ? (int) $data['transport_contract_id'] : null;
        $companyId = $this->resolveCompanyId($conn, $contractId);

        if ($companyId <= 0) {
            return redirect()->back()->withInput()->withErrors([
                'company_id' => auth()->user()->hasRole('super-admin')
                    ? 'Selecteer eerst een tenant in de tenant-kiezer bovenaan (bij scope "Hele bedrijf"), of kies een abonnement.'
                    : 'Geen bedrijf gekoppeld aan uw account.',
            ]);
        }

        if ($contractId) {
            TransportContract::on($conn)
                ->where('company_id', $companyId)
                ->findOrFail($contractId);
        }

        TransportScheduleException::on($conn)->create([
            'company_id' => $companyId,
            'transport_contract_id' => $contractId,
            'exception_date' => $data['exception_date'],
            'name' => $data['name'],
            'active' => $request->boolean('active', true),
        ]);

        return back()->with('success', 'Uitzonderingsdag toegevoegd.');
    }

    private function resolveCompanyId(string $conn, ?int $contractId): int
    {
        $tenantId = $this->getTenantId();
        if ($tenantId) {
            return (int) $tenantId;
        }

        if ($contractId) {
            $contractCompanyId = TransportContract::on($conn)
                ->whereKey($contractId)
                ->value('company_id');

            if ($contractCompanyId) {
                return (int) $contractCompanyId;
            }
        }

        $userCompanyId = auth()->user()->company_id;

        return $userCompanyId ? (int) $userCompanyId : 0;
    }

    public function destroy(int $id)
    {
        $this->authorizeOrPermission('rides.delete');

        $conn = $this->moduleConnection();
        $companyId = $this->getTenantId();

        $exception = TransportScheduleException::on($conn)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);

        $exception->delete();

        return back()->with('success', 'Uitzonderingsdag verwijderd.');
    }
}
