<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\Company;
use Illuminate\Http\Request;

class AdminInterviewController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te bekijken.');
        }
        
        $query = Interview::with(['match.vacancy.company', 'company']);
        
        // Filter op bedrijf voor non-super-admin gebruikers
        if (!auth()->user()->hasRole('super-admin')) {
            $query->where('company_id', auth()->user()->company_id);
        }
        
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
        
        $perPage = $request->get('per_page', 15);
        $interviews = $query->paginate($perPage);
        
        return view('admin.interviews.index', compact('interviews'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews aan te maken.');
        }
        
        // Haal alleen bedrijven en matches van het eigen bedrijf op (tenzij super-admin)
        if (auth()->user()->hasRole('super-admin')) {
            $companies = Company::all();
            $matches = \App\Models\JobMatch::with(['user', 'vacancy.company'])->get();
        } else {
            $companies = Company::where('id', auth()->user()->company_id)->get();
            $matches = \App\Models\JobMatch::with(['user', 'vacancy.company'])
                ->whereHas('vacancy', function($q) {
                    $q->where('company_id', auth()->user()->company_id);
                })->get();
        }
        
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
        
        // Check of het interview bij het bedrijf van de gebruiker hoort
        if (!auth()->user()->hasRole('super-admin')) {
            if ($interview->company_id !== auth()->user()->company_id) {
                abort(403, 'Je hebt geen rechten om dit interview te bekijken.');
            }
        }
        
        $interview->load(['match.vacancy.company', 'company']);
        return view('admin.interviews.show', compact('interview'));
    }

    public function edit(Interview $interview)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te bewerken.');
        }
        
        // Check of het interview bij het bedrijf van de gebruiker hoort
        if (!auth()->user()->hasRole('super-admin')) {
            if ($interview->company_id !== auth()->user()->company_id) {
                abort(403, 'Je hebt geen rechten om dit interview te bewerken.');
            }
        }
        
        $companies = auth()->user()->hasRole('super-admin') ? Company::all() : Company::where('id', auth()->user()->company_id)->get();
        $interview->load(['match.vacancy.company', 'company']);
        return view('admin.interviews.edit', compact('interview', 'companies'));
    }

    public function update(Request $request, Interview $interview)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-interviews')) {
            abort(403, 'Je hebt geen rechten om interviews te bewerken.');
        }
        
        // Check of het interview bij het bedrijf van de gebruiker hoort
        if (!auth()->user()->hasRole('super-admin')) {
            if ($interview->company_id !== auth()->user()->company_id) {
                abort(403, 'Je hebt geen rechten om dit interview te bewerken.');
            }
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
        
        // Check of het interview bij het bedrijf van de gebruiker hoort
        if (!auth()->user()->hasRole('super-admin')) {
            if ($interview->company_id !== auth()->user()->company_id) {
                abort(403, 'Je hebt geen rechten om dit interview te verwijderen.');
            }
        }
        
        $interview->delete();
        return redirect()->route('admin.interviews.index')->with('success', 'Interview succesvol verwijderd.');
    }
}
