<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Interview;
use App\Models\JobMatch;
use App\Models\Company;
use App\Models\Vacancy;

class AgendaController extends Controller
{
    use TenantFilter;

    public function index(Request $request)
    {
        // Check if user has permission to view agenda
        if (!auth()->user()->can('view-agenda')) {
            abort(403, 'Je hebt geen rechten om de agenda te bekijken.');
        }
        
        return view('admin.pages.agenda');
    }
    
    public function events(Request $request)
    {
        // Check if user has permission to view agenda
        if (!auth()->user()->can('view-agenda')) {
            abort(403, 'Je hebt geen rechten om de agenda te bekijken.');
        }
        
        try {
            $start = $request->get('start');
            $end = $request->get('end');
            
            \Log::info('Admin agenda events requested', ['start' => $start, 'end' => $end, 'user' => auth()->id()]);
            
            // Get appointments for the current user with tenant filtering
            $appointments = $this->getAppointmentsForDateRange($start, $end);
            
            \Log::info('Admin agenda events response', ['count' => count($appointments)]);
            
            return response()->json($appointments);
        } catch (\Exception $e) {
            \Log::error('Admin agenda events error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch events'], 500);
        }
    }
    
    private function getAppointmentsForDateRange($start, $end)
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        // Build query with tenant filtering
        $query = Interview::with(['match.candidate', 'match.vacancy', 'company'])
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$start, $end]);

        // Apply tenant filtering
        $query = $this->applyTenantFilter($query);

        $interviews = $query->get();

        $appointments = [];

        foreach ($interviews as $interview) {
            $candidate = $interview->match->candidate ?? null;
            $candidateName = 'Onbekend';
            if ($candidate) {
                $candidateName = trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? ''));
                if (empty($candidateName)) {
                    $candidateName = 'Onbekend';
                }
            }
            
            $appointments[] = [
                'id' => $interview->id,
                'title' => $this->getInterviewTitle($interview, $candidateName),
                'start' => $interview->scheduled_at->toISOString(),
                'end' => $interview->scheduled_at->copy()->addMinutes($interview->duration ?? 60)->toISOString(),
                'color' => $this->getEventColor($interview->type ?? 'interview'),
                'extendedProps' => [
                    'candidate_id' => $candidate ? $candidate->id : null,
                    'candidate_name' => $candidateName,
                    'location' => $interview->location ?? 'Locatie niet opgegeven',
                    'type' => $interview->type ?? 'interview',
                    'status' => $interview->status ?? 'scheduled',
                    'interviewer_name' => $interview->interviewer_name ?? 'Onbekend',
                    'interviewer_email' => $interview->interviewer_email ?? '',
                    'company_name' => $interview->company->name ?? 'Onbekend bedrijf',
                    'company_address' => $this->getCompanyAddress($interview->company),
                    'company_phone' => $interview->company->phone ?? '',
                    'vacancy_title' => $interview->match->vacancy->title ?? 'Onbekende functie',
                    'notes' => $interview->notes ?? '',
                    'feedback' => $interview->feedback ?? '',
                    'scheduled_at' => $interview->scheduled_at->format('d-m-Y H:i'),
                    'duration' => $interview->duration ?? 60
                ]
            ];
        }

        return $appointments;
    }

    private function getInterviewTitle($interview, $candidateName = null)
    {
        $type = $interview->type ?? 'interview';
        
        if ($candidateName === null) {
            $candidate = $interview->match->candidate ?? null;
            if ($candidate) {
                $candidateName = trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? ''));
                if (empty($candidateName)) {
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
            'assessment' => 'Assessment'
        ];
        
        $typeLabel = $typeLabels[$type] ?? 'Interview';
        
        return "{$typeLabel} met {$candidateName}";
    }

    private function getCompanyAddress($company)
    {
        if (!$company) {
            return 'Adres niet beschikbaar';
        }
        
        $address = [];
        if ($company->address) $address[] = $company->address;
        if ($company->city) $address[] = $company->city;
        if ($company->postal_code) $address[] = $company->postal_code;
        
        return implode(', ', $address) ?: 'Adres niet beschikbaar';
    }
    
    private function getEventColor($type)
    {
        $colors = [
            'interview' => '#3b82f6',    // Blue
            'meeting' => '#10b981',      // Green
            'call' => '#f59e0b',         // Yellow
            'assessment' => '#ef4444'    // Red
        ];
        
        return $colors[$type] ?? '#6b7280'; // Default gray
    }
}
