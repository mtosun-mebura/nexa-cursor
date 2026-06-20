<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Controllers\Admin\Concerns\AuthorizesTaxiPermissions;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportOccurrence;
use App\Modules\NexaTaxi\Models\TransportScheduleException;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransportPlanningController extends Controller
{
    use AuthorizesTaxiPermissions, TenantFilter, UsesModuleDatabase;

    public function index(Request $request)
    {
        $this->authorizeOrPermission('rides.view');

        $conn = $this->moduleConnection();
        $companyId = $this->getTenantId();

        $weekStart = $this->resolveWeekStart($request->string('week')->toString());
        $weekEnd = $weekStart->copy()->addDays(6);

        $contracts = TransportContract::on($conn)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $contractFilter = $request->integer('contract_id') ?: null;

        $occurrenceQuery = TransportOccurrence::on($conn)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('scheduled_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->with([
                'rideRequest.driver',
                'contract',
                'routeTemplate.group',
                'routeTemplate.assignment.driver',
                'individualBooking.passenger',
            ])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_at');

        if ($contractFilter) {
            $occurrenceQuery->where('transport_contract_id', $contractFilter);
        }

        $occurrences = $occurrenceQuery->get();

        $exceptions = TransportScheduleException::on($conn)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('active', true)
            ->whereBetween('exception_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('exception_date')
            ->get();

        $days = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $occurrences, $exceptions) {
            $date = $weekStart->copy()->addDays($offset);

            return [
                'date' => $date,
                'label' => $date->locale('nl')->translatedFormat('D d-m'),
                'occurrences' => $occurrences->filter(
                    fn (TransportOccurrence $item) => $item->scheduled_date?->toDateString() === $date->toDateString()
                )->values(),
                'exceptions' => $exceptions->filter(
                    fn (TransportScheduleException $item) => $item->exception_date?->toDateString() === $date->toDateString()
                )->values(),
            ];
        });

        $prevWeek = $weekStart->copy()->subWeek()->format('Y-m-d');
        $nextWeek = $weekStart->copy()->addWeek()->format('Y-m-d');

        return view('taxi::admin.transport_planning.index', compact(
            'weekStart',
            'weekEnd',
            'days',
            'contracts',
            'contractFilter',
            'prevWeek',
            'nextWeek',
        ));
    }

    protected function resolveWeekStart(string $weekParam): Carbon
    {
        $parsed = $weekParam !== '' ? parse_admin_date($weekParam) : null;

        if ($parsed && preg_match('/^\d{4}-\d{2}-\d{2}$/', $parsed)) {
            $date = Carbon::createFromFormat('Y-m-d', $parsed, ContractTransportTimezone::TIMEZONE)->startOfDay();
        } else {
            $date = now(ContractTransportTimezone::TIMEZONE)->startOfWeek(Carbon::MONDAY);
        }

        return $date->startOfDay();
    }
}
