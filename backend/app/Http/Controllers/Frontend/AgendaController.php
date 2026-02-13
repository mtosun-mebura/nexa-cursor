<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Interview;
use App\Models\JobMatch;
use App\Models\Company;
use App\Models\Vacancy;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        // Check if user has permission to view agenda
        if (!auth()->user()->can('view-agenda')) {
            abort(403, 'Je hebt geen rechten om de agenda te bekijken.');
        }
        
        return view()->first(
            ['skillmatching::frontend.pages.agenda', 'frontend.pages.agenda']
        );
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
            
            \Log::info('Agenda events requested', ['start' => $start, 'end' => $end, 'user' => auth()->id()]);
            
            // Get appointments for the current user
            $appointments = $this->getAppointmentsForDateRange($start, $end);
            
            \Log::info('Agenda events response', ['count' => count($appointments)]);
            
            return response()->json($appointments);
        } catch (\Exception $e) {
            \Log::error('Agenda events error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch events'], 500);
        }
    }
    
    private function getAppointmentsForDateRange($start, $end)
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        // Parse date range (FullCalendar sends ISO strings)
        try {
            $startDate = $start ? Carbon::parse($start) : Carbon::now()->startOfMonth();
            $endDate = $end ? Carbon::parse($end) : Carbon::now()->endOfMonth()->endOfDay();
        } catch (\Exception $e) {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth()->endOfDay();
        }

        // Interviews for the logged-in user (candidate email = user email)
        $interviews = Interview::with(['match.candidate', 'match.vacancy', 'company'])
            ->whereHas('match.candidate', function($query) use ($user) {
                $query->where('email', $user->email);
            })
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->orderBy('scheduled_at')
            ->get();

        $appointments = [];

        foreach ($interviews as $interview) {
            // Format times without timezone conversion - use local server time
            $startTime = $interview->scheduled_at->format('Y-m-d\TH:i:s');
            $endTime = $interview->scheduled_at->copy()->addMinutes($interview->duration ?? 60)->format('Y-m-d\TH:i:s');
            
            $appointments[] = [
                'id' => $interview->id,
                'title' => $this->getInterviewTitle($interview),
                'start' => $startTime,
                'end' => $endTime,
                'color' => $this->getEventColor($interview->type ?? 'interview'),
                'extendedProps' => [
                    'candidate_name' => $interview->match->candidate->full_name ?? 'Onbekend',
                    'location' => $interview->location ?? 'Locatie niet opgegeven',
                    'type' => $interview->type ?? 'interview',
                    'status' => $interview->status ?? 'scheduled',
                    'interviewer_name' => $interview->interviewer_name ?? 'Onbekend',
                    'interviewer_email' => $interview->interviewer_email ?? '',
                    'company_name' => $interview->company->name ?? 'Onbekend bedrijf',
                    'company_address' => $this->getCompanyAddress($interview->company),
                    'company_street' => $this->getCompanyStreetLine($interview->company),
                    'company_postal_code' => $interview->company->postal_code ?? '',
                    'company_city' => $interview->company->city ?? '',
                    'company_phone' => $interview->company->phone ?? '',
                    'vacancy_title' => $interview->match->vacancy->title ?? 'Onbekende functie',
                    'notes' => $interview->notes ?? '',
                    'feedback' => $interview->feedback ?? ''
                ]
            ];
        }

        return $appointments;
    }

    /**
     * Title for frontend agenda: candidate sees vacancy + company, not "met [zelf]".
     */
    private function getInterviewTitle($interview)
    {
        $type = $interview->type ?? 'interview';
        $vacancyTitle = $interview->match && $interview->match->vacancy
            ? $interview->match->vacancy->title
            : 'Onbekende functie';
        $companyName = $interview->company ? $interview->company->name : 'Onbekend bedrijf';
        
        $typeLabels = [
            'interview' => 'Interview',
            'meeting' => 'Meeting',
            'call' => 'Telefoongesprek',
            'assessment' => 'Assessment'
        ];
        $typeLabel = $typeLabels[$type] ?? 'Interview';
        
        return "{$typeLabel} - {$vacancyTitle} bij {$companyName}";
    }

    private function getCompanyAddress($company)
    {
        if (!$company) {
            return 'Adres niet beschikbaar';
        }
        
        $streetLine = $this->getCompanyStreetLine($company);
        $parts = array_filter([$streetLine, $company->city ?? null, $company->postal_code ?? null]);
        return implode(', ', $parts) ?: 'Adres niet beschikbaar';
    }

    /** Straat + huisnummer (en toevoeging) op één regel voor weergave. */
    private function getCompanyStreetLine($company)
    {
        if (!$company) {
            return '';
        }
        $parts = array_filter([
            $company->street ?? '',
            trim(($company->house_number ?? '') . ' ' . ($company->house_number_extension ?? '')),
        ]);
        return implode(' ', $parts);
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