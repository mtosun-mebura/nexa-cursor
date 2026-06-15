<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Interview;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\ModuleDatabaseService;
use App\Services\ModuleManager;
use App\Support\UserAgendaColor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AgendaController extends Controller
{
    use TenantFilter;

    public function __construct(
        protected ModuleManager $moduleManager,
        protected ModuleDatabaseService $moduleDb
    ) {}

    public function index(Request $request)
    {
        $users = collect();
        if (auth()->user()->hasRole('super-admin')) {
            $tenantId = $this->getTenantId();

            $query = User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['super-admin', 'candidate']);
            });

            if ($tenantId) {
                $query->where('company_id', $tenantId);
            }

            $users = $query->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'agenda_color']);
        }

        return view('admin.pages.agenda', compact('users'));
    }

    public function events(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $selectedUserId = $request->get('user_id');

        $appointments = [];
        $rides = [];

        try {
            $appointments = $this->getAppointmentsForDateRange($start, $end, $selectedUserId);
        } catch (\Throwable $e) {
            \Log::warning('Admin agenda interviews skipped', ['error' => $e->getMessage()]);
        }

        try {
            $rides = $this->getRideEventsForDateRange($start, $end, $selectedUserId);
        } catch (\Throwable $e) {
            \Log::error('Admin agenda rides error', ['error' => $e->getMessage()]);
        }

        return response()->json(array_merge($appointments, $rides));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getAppointmentsForDateRange($start, $end, $selectedUserId = null): array
    {
        $user = Auth::user();

        if (! $user || ! $this->moduleManager->isActive('skillmatching') || ! Schema::hasTable('interviews')) {
            return [];
        }

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->endOfDay();

        $query = Interview::with(['match.candidate', 'match.vacancy', 'company'])
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$startDate, $endDate]);

        if ((! $user->hasRole('super-admin') || session('selected_tenant')) && ! $selectedUserId) {
            $query = $this->applyTenantFilter($query);
        }

        if ($selectedUserId && $user->hasRole('super-admin')) {
            $selectedUser = User::find($selectedUserId);
            if ($selectedUser) {
                $query->where(function ($q) use ($selectedUser) {
                    $q->where('interviewer_user_id', $selectedUser->id)
                        ->orWhere('user_id', $selectedUser->id)
                        ->orWhereHas('match.candidate', function ($candidateQuery) use ($selectedUser) {
                            $candidateQuery->where('email', $selectedUser->email);
                        });
                });
            }
        }

        $interviews = $query->get();
        $interviewerIds = $interviews->pluck('interviewer_user_id')->filter()->unique()->values();
        $interviewersById = User::query()
            ->whereIn('id', $interviewerIds)
            ->get(['id', 'first_name', 'last_name', 'agenda_color'])
            ->keyBy('id');

        $appointments = [];

        foreach ($interviews as $interview) {
            if (! $interview->match) {
                continue;
            }

            $candidate = $interview->match->candidate ?? null;
            $candidateName = 'Onbekend';
            if ($candidate) {
                $candidateName = trim(($candidate->first_name ?? '').' '.($candidate->last_name ?? ''));
                if ($candidateName === '') {
                    $candidateName = 'Onbekend';
                }
            }

            $candidateUser = null;
            $userPhotoToken = null;
            if ($candidate && $candidate->email) {
                $candidateUser = User::where('email', $candidate->email)->first();
                if ($candidateUser && method_exists($candidateUser, 'getPhotoToken')) {
                    try {
                        $userPhotoToken = $candidateUser->getPhotoToken();
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
            }

            $interviewer = $interview->interviewer_user_id
                ? $interviewersById->get((int) $interview->interviewer_user_id)
                : null;
            $eventColor = $interviewer
                ? UserAgendaColor::resolved($interviewer)
                : $this->getEventColor($interview->type ?? 'interview');

            try {
                $startTime = $interview->scheduled_at->format('Y-m-d\TH:i:s');
                $endTime = $interview->scheduled_at->copy()->addMinutes($interview->duration ?? 60)->format('Y-m-d\TH:i:s');

                $appointments[] = [
                    'id' => 'interview-'.$interview->id,
                    'title' => $this->getInterviewTitle($interview, $candidateName),
                    'start' => $startTime,
                    'end' => $endTime,
                    'color' => $eventColor,
                    'extendedProps' => [
                        'event_kind' => 'interview',
                        'candidate_id' => $candidate ? $candidate->id : null,
                        'candidate_name' => $candidateName,
                        'user_id' => $candidateUser ? $candidateUser->id : null,
                        'user_photo_token' => $userPhotoToken,
                        'location' => $interview->location ?? 'Locatie niet opgegeven',
                        'type' => $interview->type ?? 'interview',
                        'status' => $interview->status ?? 'scheduled',
                        'interviewer_name' => $interview->interviewer_name ?? 'Onbekend',
                        'interviewer_email' => $interview->interviewer_email ?? '',
                        'interviewer_user_id' => $interview->interviewer_user_id,
                        'agenda_color' => $eventColor,
                        'company_name' => $interview->company->name ?? 'Onbekend bedrijf',
                        'company_address' => $this->getCompanyAddress($interview->company),
                        'company_phone' => $interview->company->phone ?? '',
                        'vacancy_title' => $interview->match->vacancy->title ?? 'Onbekende functie',
                        'notes' => $interview->notes ?? '',
                        'feedback' => $interview->feedback ?? '',
                        'scheduled_at' => $interview->scheduled_at->format('d-m-Y H:i'),
                        'duration' => $interview->duration ?? 60,
                    ],
                ];
            } catch (\Exception $e) {
                \Log::error('Admin agenda - Error processing interview', [
                    'interview_id' => $interview->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $appointments;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getRideEventsForDateRange($start, $end, $selectedUserId = null): array
    {
        $user = Auth::user();
        if (! $user || ! $this->moduleManager->isActive('taxi')) {
            return [];
        }

        try {
            $conn = $this->moduleDb->getModuleConnectionName('taxi');
        } catch (\Throwable) {
            return [];
        }

        if (! Schema::connection($conn)->hasTable('ride_requests')) {
            return [];
        }

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->endOfDay();

        $query = RideRequest::on($conn)
            ->whereNotNull('pickup_at')
            ->whereBetween('pickup_at', [$startDate, $endDate])
            ->whereNotIn('status', [
                RideRequest::STATUS_CANCELLED,
                RideRequest::STATUS_DRAFT,
            ]);

        $this->applyRideTenantFilter($query, $user);

        if ($selectedUserId && $user->hasRole('super-admin')) {
            $query->where('driver_id', (int) $selectedUserId);
        }

        $rides = $query->orderBy('pickup_at')->get();
        if ($rides->isEmpty()) {
            return [];
        }

        $driverIds = $rides->pluck('driver_id')->filter()->unique()->values();
        $driversById = User::query()
            ->whereIn('id', $driverIds)
            ->get(['id', 'first_name', 'last_name', 'agenda_color', 'company_id'])
            ->keyBy('id');

        $companyIds = $rides->pluck('company_id')->filter()->unique()->values();
        $companiesById = Company::query()
            ->whereIn('id', $companyIds)
            ->get(['id', 'name', 'phone', 'street', 'house_number', 'house_number_extension', 'postal_code', 'city'])
            ->keyBy('id');

        $events = [];

        foreach ($rides as $ride) {
            $driver = $ride->driver_id ? $driversById->get((int) $ride->driver_id) : null;
            $driverName = $driver
                ? trim(($driver->first_name ?? '').' '.($driver->last_name ?? ''))
                : 'Geen chauffeur';
            if ($driverName === '') {
                $driverName = 'Geen chauffeur';
            }

            $rideColor = UserAgendaColor::forRide($driver, (string) $ride->status);
            $color = $rideColor['color'];
            $durationMinutes = max(15, (int) round(((int) ($ride->duration_seconds ?? 0)) / 60));
            if ($durationMinutes <= 15 && $ride->duration_seconds === null) {
                $durationMinutes = 60;
            }

            $company = $ride->company_id ? $companiesById->get((int) $ride->company_id) : null;
            $customerName = trim((string) ($ride->customer_name ?? '')) ?: 'Klant';
            $pickup = trim((string) ($ride->pickup_address ?? '')) ?: 'Ophalen onbekend';
            $dropoff = trim((string) ($ride->dropoff_address ?? '')) ?: 'Afzetten onbekend';
            $statusLabel = RideRequest::statusLabels()[$ride->status] ?? $ride->status;

            $events[] = [
                'id' => 'ride-'.$ride->id,
                'title' => 'Rit: '.$customerName.' ('.$driverName.')',
                'start' => $ride->pickup_at->format('Y-m-d\TH:i:s'),
                'end' => $ride->pickup_at->copy()->addMinutes($durationMinutes)->format('Y-m-d\TH:i:s'),
                'color' => $color,
                'extendedProps' => [
                    'event_kind' => 'ride',
                    'ride_id' => $ride->id,
                    'candidate_name' => $customerName,
                    'driver_id' => $ride->driver_id,
                    'driver_name' => $driverName,
                    'agenda_color' => UserAgendaColor::resolved($driver),
                    'color_state' => $rideColor['state'],
                    'ride_status' => (string) $ride->status,
                    'location' => $pickup.' → '.$dropoff,
                    'pickup_address' => $pickup,
                    'dropoff_address' => $dropoff,
                    'status' => $statusLabel,
                    'company_name' => $company->name ?? 'Onbekend bedrijf',
                    'company_phone' => $company->phone ?? '',
                    'company_address' => $this->getCompanyAddress($company),
                    'passengers' => (int) ($ride->passengers ?? 1),
                    'payment_status' => $ride->payment_status,
                    'scheduled_at' => $ride->pickup_at->format('d-m-Y H:i'),
                    'duration' => $durationMinutes,
                ],
            ];
        }

        return $events;
    }

    private function applyRideTenantFilter($query, User $user): void
    {
        if ($user->hasRole('super-admin')) {
            if (session('selected_tenant')) {
                $tenantId = (int) session('selected_tenant');
                $query->where(function ($q) use ($tenantId) {
                    $q->where('company_id', $tenantId)
                        ->orWhereHas('vehicle', fn ($v) => $v->where('company_id', $tenantId));
                });
            }

            return;
        }

        if ($user->company_id) {
            $companyId = (int) $user->company_id;
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhereHas('vehicle', fn ($v) => $v->where('company_id', $companyId));
            });
        }
    }

    private function getInterviewTitle($interview, $candidateName = null)
    {
        $type = $interview->type ?? 'interview';

        if ($candidateName === null) {
            $candidate = $interview->match->candidate ?? null;
            if ($candidate) {
                $candidateName = trim(($candidate->first_name ?? '').' '.($candidate->last_name ?? ''));
                if ($candidateName === '') {
                    $candidateName = 'Onbekend';
                }
            } else {
                $candidateName = 'Onbekend';
            }
        }

        $typeLabels = [
            'interview' => 'Interview',
            'meeting' => 'Meeting',
            'call' => 'Telefoongesprek',
            'assessment' => 'Assessment',
        ];

        $typeLabel = $typeLabels[$type] ?? 'Interview';

        return "{$typeLabel} met {$candidateName}";
    }

    private function getCompanyAddress($company)
    {
        if (! $company) {
            return 'Adres niet beschikbaar';
        }

        $streetLine = trim(
            ($company->street ?? '').' '.
            ($company->house_number ?? '').
            ($company->house_number_extension ? '-'.$company->house_number_extension : '')
        );

        $address = array_filter([
            $streetLine !== '' ? $streetLine : null,
            $company->postal_code ?? null,
            $company->city ?? null,
        ]);

        return implode(', ', $address) ?: 'Adres niet beschikbaar';
    }

    private function getEventColor($type)
    {
        $colors = [
            'interview' => '#3b82f6',
            'meeting' => '#10b981',
            'call' => '#f59e0b',
            'assessment' => '#ef4444',
        ];

        return $colors[$type] ?? '#6b7280';
    }
}
