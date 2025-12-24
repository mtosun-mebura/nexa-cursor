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
        
        return view('frontend.pages.agenda');
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

        // Get interviews for the logged-in user
        // Note: This assumes candidate email matches user email - may need refactoring
        $interviews = Interview::with(['match.candidate', 'match.vacancy', 'company'])
            ->whereHas('match.candidate', function($query) use ($user) {
                $query->where('email', $user->email);
            })
            ->whereBetween('scheduled_at', [$start, $end])
            ->get();

        $appointments = [];

        foreach ($interviews as $interview) {
            $appointments[] = [
                'id' => $interview->id,
                'title' => $this->getInterviewTitle($interview),
                'start' => $interview->scheduled_at->toISOString(),
                'end' => $interview->scheduled_at->copy()->addMinutes($interview->duration ?? 60)->toISOString(),
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
                    'company_phone' => $interview->company->phone ?? '',
                    'vacancy_title' => $interview->match->vacancy->title ?? 'Onbekende functie',
                    'notes' => $interview->notes ?? '',
                    'feedback' => $interview->feedback ?? ''
                ]
            ];
        }

        return $appointments;
    }

    private function getInterviewTitle($interview)
    {
        $type = $interview->type ?? 'interview';
        $candidateName = $interview->match->candidate->full_name ?? 'Onbekend';
        
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