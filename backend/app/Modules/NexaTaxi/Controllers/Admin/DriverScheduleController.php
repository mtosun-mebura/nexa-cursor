<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Controllers\Admin\Concerns\AuthorizesTaxiPermissions;
use App\Modules\NexaTaxi\Models\DriverSchedule;
use App\Modules\NexaTaxi\Services\DriverScheduleService;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use Illuminate\Http\Request;

class DriverScheduleController extends Controller
{
    use AuthorizesTaxiPermissions, TenantFilter, UsesModuleDatabase;

    public function __construct(
        protected DriverScheduleService $schedules
    ) {}

    public function index(Request $request)
    {
        $this->authorizeOrPermission('rides.view');

        $conn = $this->moduleConnection();
        $this->schedules->ensureReady($conn);
        $companyId = (int) ($this->getTenantId() ?? 0);

        $users = collect();
        $agendaDrivers = $companyId > 0 ? $this->schedules->chauffeursForCompany($companyId) : collect();
        $agendaVehicles = $companyId > 0 ? $this->schedules->vehiclesForCompany($conn, $companyId) : collect();
        $calendarMode = 'driver_schedules';
        $canManage = auth()->user()->hasRole('super-admin') || auth()->user()->can('rides.update') || auth()->user()->can('rides.create');
        $superAdminNeedsTenant = auth()->user()->hasRole('super-admin') && $companyId <= 0;

        return view('admin.pages.agenda', compact(
            'users',
            'agendaDrivers',
            'agendaVehicles',
            'calendarMode',
            'canManage',
            'superAdminNeedsTenant',
        ));
    }

    public function events(Request $request)
    {
        $this->authorizeOrPermission('rides.view');

        $conn = $this->moduleConnection();
        $this->schedules->ensureReady($conn);
        $companyId = (int) ($this->getTenantId() ?? 0);

        return response()->json($this->schedules->agendaEvents(
            $conn,
            $companyId > 0 ? $companyId : null,
            (string) $request->get('start'),
            (string) $request->get('end'),
            $request->integer('driver_id') ?: null,
            $request->integer('vehicle_id') ?: null,
        ));
    }

    public function create(Request $request)
    {
        $this->authorizeOrPermission('rides.update');

        $conn = $this->moduleConnection();
        $this->schedules->ensureReady($conn);
        $companyId = (int) ($this->getTenantId() ?? 0);
        $superAdminNeedsTenant = auth()->user()->hasRole('super-admin') && $companyId <= 0;
        $chauffeurs = $companyId > 0 ? $this->schedules->chauffeursForCompany($companyId) : collect();
        $vehicles = $companyId > 0 ? $this->schedules->vehiclesForCompany($conn, $companyId) : collect();
        $defaultDate = parse_admin_date($request->input('date')) ?: now(ContractTransportTimezone::TIMEZONE)->toDateString();

        return view('taxi::admin.driver_schedules.create', compact(
            'chauffeurs',
            'vehicles',
            'superAdminNeedsTenant',
            'defaultDate',
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeOrPermission('rides.update');

        $request->merge([
            'date' => parse_admin_date($request->input('date')),
            'repeat_until' => parse_admin_date($request->input('repeat_until')),
        ]);

        $data = $request->validate([
            'driver_id' => ['required', 'integer'],
            'vehicle_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'repeat_weekly' => ['sometimes', 'boolean'],
            'repeat_until' => ['nullable', 'date'],
        ]);

        $companyId = (int) ($this->getTenantId() ?? 0);
        if ($companyId <= 0) {
            return redirect()->back()->withInput()->withErrors([
                'company_id' => 'Selecteer eerst een tenant om een chauffeur in te plannen.',
            ]);
        }

        $repeatWeekly = $request->boolean('repeat_weekly');
        $until = $repeatWeekly ? ($data['repeat_until'] ?? null) : null;

        $conn = $this->moduleConnection();
        $this->schedules->createShifts(
            $conn,
            $companyId,
            (int) $data['driver_id'],
            (int) $data['vehicle_id'],
            $data['date'],
            $data['start_time'],
            $data['end_time'],
            $data['notes'] ?? null,
            $repeatWeekly,
            $until,
            auth()->id(),
            $data['weekdays']
        );

        $message = $until
            ? 'Dienst is ingepland en herhaalt wekelijks tot de einddatum.'
            : 'Dienst is ingepland en herhaalt wekelijks tot je deze verwijdert of een einddatum zet.';

        return redirect()
            ->route('admin.taxi.driver_schedules.index', ['week' => $data['date']])
            ->with('success', $message);
    }

    public function edit(int $id)
    {
        $this->authorizeOrPermission('rides.update');

        $schedule = $this->findSchedule($id);
        $conn = $this->moduleConnection();
        $companyId = (int) $schedule->company_id;
        $chauffeurs = $this->schedules->chauffeursForCompany($companyId);
        $vehicles = $this->schedules->vehiclesForCompany($conn, $companyId, false);
        $start = ContractTransportTimezone::asAmsterdamWall($schedule->starts_at);
        $end = ContractTransportTimezone::asAmsterdamWall($schedule->ends_at);

        return view('taxi::admin.driver_schedules.edit', compact(
            'schedule',
            'chauffeurs',
            'vehicles',
            'start',
            'end',
        ));
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeOrPermission('rides.update');

        $schedule = $this->findSchedule($id);
        $request->merge([
            'date' => parse_admin_date($request->input('date')),
            'repeat_until' => parse_admin_date($request->input('repeat_until')),
        ]);

        $data = $request->validate([
            'driver_id' => ['required', 'integer'],
            'vehicle_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'repeat_weekly' => ['sometimes', 'boolean'],
            'repeat_until' => ['nullable', 'date'],
        ]);

        $repeatWeekly = $request->boolean('repeat_weekly');
        $until = $repeatWeekly ? ($data['repeat_until'] ?? null) : null;

        $this->schedules->updateShift(
            $schedule,
            $this->moduleConnection(),
            (int) $data['driver_id'],
            (int) $data['vehicle_id'],
            $data['date'],
            $data['start_time'],
            $data['end_time'],
            $data['notes'] ?? null,
            $data['weekdays'],
            $repeatWeekly,
            $until
        );

        return redirect()
            ->route('admin.taxi.driver_schedules.index', ['week' => $data['date']])
            ->with('success', 'Dienst is bijgewerkt.');
    }

    public function destroy(int $id)
    {
        $this->authorizeOrPermission('rides.update');

        $schedule = $this->findSchedule($id);
        $week = ContractTransportTimezone::asAmsterdamWall($schedule->starts_at)?->toDateString();
        $schedule->delete();

        return redirect()
            ->route('admin.taxi.driver_schedules.index', array_filter(['week' => $week]))
            ->with('success', 'Dienst is verwijderd.');
    }

    protected function findSchedule(int $id): DriverSchedule
    {
        $conn = $this->moduleConnection();
        $this->schedules->ensureReady($conn);
        $schedule = DriverSchedule::on($conn)->with('vehicle')->findOrFail($id);
        $companyId = (int) ($this->getTenantId() ?? 0);
        if ($companyId > 0 && (int) $schedule->company_id !== $companyId) {
            abort(404);
        }

        return $schedule;
    }
}
