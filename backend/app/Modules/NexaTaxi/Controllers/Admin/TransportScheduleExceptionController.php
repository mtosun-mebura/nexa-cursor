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
        $companyId = $this->getTenantId();

        if (! empty($data['transport_contract_id'])) {
            TransportContract::on($conn)
                ->where('company_id', $companyId)
                ->findOrFail((int) $data['transport_contract_id']);
        }

        TransportScheduleException::on($conn)->create([
            'company_id' => $companyId,
            'transport_contract_id' => $data['transport_contract_id'] ?: null,
            'exception_date' => $data['exception_date'],
            'name' => $data['name'],
            'active' => $request->boolean('active', true),
        ]);

        return back()->with('success', 'Uitzonderingsdag toegevoegd.');
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
