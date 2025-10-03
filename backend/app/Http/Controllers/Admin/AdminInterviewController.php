<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Interview;
use App\Models\Company;
use Illuminate\Http\Request;

class AdminInterviewController extends Controller
{
    use TenantFilter;
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te bekijken.');
        }
        
        $query = Interview::with(['match.vacancy.company', 'company']);
        
        // Apply tenant filtering
        $query = $this->applyTenantFilter($query);
        
        // Filter op status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter op bedrijf (alleen voor super-admin)
        if ($request->filled('company') && auth()->user()->hasRole('super-admin')) {
            $query->where('company_id', $request->company);
        }
        
        // Filter op type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Sortering
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('order', 'desc');
        
        // Valideer sorteer veld
        $allowedSortFields = ['id', 'match_id', 'company_id', 'scheduled_at', 'location', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        
        // Speciale behandeling voor verschillende sorteervelden
        if ($sortField === 'vacancy_id') {
            $query->join('matches', 'interviews.match_id', '=', 'matches.id')
                  ->orderBy('matches.vacancy_id', $sortDirection)
                  ->select('interviews.*'); // Zorg ervoor dat alleen interview kolommen worden geselecteerd
        } elseif ($sortField === 'status') {
            // Sorteer op status met logische volgorde: Niet gepland, Gepland, Afgelopen
            $query->orderByRaw("
                CASE 
                    WHEN scheduled_at IS NULL THEN 1
                    WHEN scheduled_at > NOW() THEN 2
                    WHEN scheduled_at <= NOW() THEN 3
                END " . $sortDirection
            );
        } else {
            $query->orderBy($sortField, $sortDirection);
        }
        
        $perPage = $request->get('per_page', 25);
        $interviews = $query->paginate($perPage)->withQueryString();
        
        return view('admin.interviews.index', compact('interviews'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews aan te maken.');
        }
        
        // Haal bedrijven en matches op basis van tenant filtering
        $companyQuery = Company::query();
        $companyQuery = $this->applyTenantFilter($companyQuery);
        $companies = $companyQuery->get();
        
        $matchQuery = \App\Models\JobMatch::with(['user', 'vacancy.company']);
        if (!auth()->user()->hasRole('super-admin') || session('selected_tenant')) {
            $tenantId = $this->getTenantId();
            $matchQuery->whereHas('vacancy', function($q) use ($tenantId) {
                $q->where('company_id', $tenantId);
            });
        }
        $matches = $matchQuery->get();
        
        return view('admin.interviews.create', compact('companies', 'matches'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews aan te maken.');
        }
        
        $request->validate([
            'match_id' => 'required|exists:matches,id',
            'company_id' => 'required|exists:companies,id',
            'type' => 'required|in:phone,video,onsite,assessment,final',
            'scheduled_at' => 'required|date',
            'duration' => 'nullable|integer|min:15|max:480',
            'status' => 'required|in:scheduled,confirmed,completed,cancelled,rescheduled',
            'location' => 'nullable|string|max:255',
            'interviewer_name' => 'nullable|string|max:255',
            'interviewer_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'feedback' => 'nullable|string',
        ]);

        // Voor non-super-admin gebruikers: controleer of het bedrijf en match bij hun bedrijf horen
        if (!auth()->user()->hasRole('super-admin')) {
            if ($request->company_id != auth()->user()->company_id) {
                abort(403, 'Je kunt alleen interviews aanmaken voor je eigen bedrijf.');
            }
            
            $match = \App\Models\JobMatch::findOrFail($request->match_id);
            if ($match->vacancy->company_id !== auth()->user()->company_id) {
                abort(403, 'Je kunt alleen interviews aanmaken voor matches van je eigen bedrijf.');
            }
        }

        Interview::create($request->all());
        return redirect()->route('admin.interviews.index')->with('success', 'Interview succesvol aangemaakt.');
    }

    public function show(Interview $interview)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te bekijken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($interview)) {
            abort(403, 'Je hebt geen toegang tot dit interview.');
        }
        
        $interview->load(['match.vacancy.company', 'company']);
        return view('admin.interviews.show', compact('interview'));
    }

    public function edit(Interview $interview)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($interview)) {
            abort(403, 'Je hebt geen toegang tot dit interview.');
        }
        
        $companyQuery = Company::query();
        $companyQuery = $this->applyTenantFilter($companyQuery);
        $companies = $companyQuery->get();
        $interview->load(['match.vacancy.company', 'company']);
        return view('admin.interviews.edit', compact('interview', 'companies'));
    }

    public function update(Request $request, Interview $interview)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te bewerken.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($interview)) {
            abort(403, 'Je hebt geen toegang tot dit interview.');
        }
        
        $request->validate([
            'match_id' => 'required|exists:matches,id',
            'type' => 'required|in:phone,video,onsite,assessment,final',
            'scheduled_at' => 'required|date',
            'duration' => 'nullable|integer|min:15|max:480',
            'status' => 'required|in:scheduled,confirmed,completed,cancelled,rescheduled',
            'location' => 'nullable|string|max:255',
            'interviewer_name' => 'nullable|string|max:255',
            'interviewer_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'feedback' => 'nullable|string',
        ]);

        $interview->update($request->all());
        return redirect()->route('admin.interviews.index')->with('success', 'Interview succesvol bijgewerkt.');
    }

    public function destroy(Interview $interview)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te verwijderen.');
        }
        
        // Check if user can access this resource
        if (!$this->canAccessResource($interview)) {
            abort(403, 'Je hebt geen toegang tot dit interview.');
        }
        
        $interview->delete();
        return redirect()->route('admin.interviews.index')->with('success', 'Interview succesvol verwijderd.');
    }
}
