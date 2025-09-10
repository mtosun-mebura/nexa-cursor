<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobMatch;
use Illuminate\Http\Request;

class AdminMatchController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-matches')) {
            abort(403, 'Je hebt geen rechten om matches te bekijken.');
        }
        
        $query = \App\Models\JobMatch::with(['user', 'vacancy.company']);
        
        // Filter op bedrijf voor non-super-admin gebruikers
        if (!auth()->user()->hasRole('super-admin')) {
            $query->whereHas('vacancy', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });
        }
        
        // Filter op status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter op bedrijf (alleen voor super-admin)
        if ($request->filled('company') && auth()->user()->hasRole('super-admin')) {
            $query->whereHas('vacancy', function($q) use ($request) {
                $q->where('company_id', $request->company);
            });
        }
        
        // Filter op score
        if ($request->filled('score')) {
            switch ($request->score) {
                case 'high':
                    $query->where('match_score', '>=', 80);
                    break;
                case 'medium':
                    $query->whereBetween('match_score', [60, 79]);
                    break;
                case 'low':
                    $query->where('match_score', '<', 60);
                    break;
            }
        }
        
        $perPage = $request->get('per_page', 15);
        $matches = $query->paginate($perPage);
        
        return view('admin.matches.index', compact('matches'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-matches')) {
            abort(403, 'Je hebt geen rechten om matches aan te maken.');
        }
        
        // Haal alleen gebruikers en vacatures van het eigen bedrijf op (tenzij super-admin)
        if (auth()->user()->hasRole('super-admin')) {
            $users = \App\Models\User::all();
            $vacancies = \App\Models\Vacancy::all();
        } else {
            $users = \App\Models\User::where('company_id', auth()->user()->company_id)->get();
            $vacancies = \App\Models\Vacancy::where('company_id', auth()->user()->company_id)->get();
        }
        
        return view('admin.matches.create', compact('users', 'vacancies'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-matches')) {
            abort(403, 'Je hebt geen rechten om matches aan te maken.');
        }
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'vacancy_id' => 'required|exists:vacancies,id',
            'match_score' => 'nullable|numeric|between:0,100',
            'status' => 'required|in:pending,accepted,rejected,interview_scheduled,hired',
            'ai_recommendation' => 'nullable|in:strong_match,good_match,moderate_match,weak_match,not_recommended',
            'application_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'ai_analysis' => 'nullable|string',
        ]);

        // Voor non-super-admin gebruikers: controleer of de vacature bij hun bedrijf hoort
        if (!auth()->user()->hasRole('super-admin')) {
            $vacancy = \App\Models\Vacancy::findOrFail($request->vacancy_id);
            if ($vacancy->company_id !== auth()->user()->company_id) {
                abort(403, 'Je kunt alleen matches aanmaken voor vacatures van je eigen bedrijf.');
            }
        }

        \App\Models\JobMatch::create($request->all());
        return redirect()->route('admin.matches.index')->with('success', 'Match succesvol aangemaakt.');
    }

    public function show(\App\Models\JobMatch $match)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-matches')) {
            abort(403, 'Je hebt geen rechten om matches te bekijken.');
        }
        
        // Check of de match bij het bedrijf van de gebruiker hoort
        if (!auth()->user()->hasRole('super-admin')) {
            if ($match->vacancy->company_id !== auth()->user()->company_id) {
                abort(403, 'Je hebt geen rechten om deze match te bekijken.');
            }
        }
        
        $match->load(['user', 'vacancy.company']);
        return view('admin.matches.show', compact('match'));
    }

    public function edit(\App\Models\JobMatch $match)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-matches')) {
            abort(403, 'Je hebt geen rechten om matches te bewerken.');
        }
        
        // Check of de match bij het bedrijf van de gebruiker hoort
        if (!auth()->user()->hasRole('super-admin')) {
            if ($match->vacancy->company_id !== auth()->user()->company_id) {
                abort(403, 'Je hebt geen rechten om deze match te bewerken.');
            }
        }
        
        $match->load(['user', 'vacancy.company']);
        return view('admin.matches.edit', compact('match'));
    }

    public function update(Request $request, \App\Models\JobMatch $match)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-matches')) {
            abort(403, 'Je hebt geen rechten om matches te bewerken.');
        }
        
        // Check of de match bij het bedrijf van de gebruiker hoort
        if (!auth()->user()->hasRole('super-admin')) {
            if ($match->vacancy->company_id !== auth()->user()->company_id) {
                abort(403, 'Je hebt geen rechten om deze match te bewerken.');
            }
        }
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'vacancy_id' => 'required|exists:vacancies,id',
            'match_score' => 'nullable|numeric|between:0,100',
            'status' => 'required|in:pending,accepted,rejected,interview_scheduled,hired',
            'ai_recommendation' => 'nullable|in:strong_match,good_match,moderate_match,weak_match,not_recommended',
            'application_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'ai_analysis' => 'nullable|string',
        ]);

        $match->update($request->all());
        return redirect()->route('admin.matches.index')->with('success', 'Match succesvol bijgewerkt.');
    }

    public function destroy(\App\Models\JobMatch $match)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-matches')) {
            abort(403, 'Je hebt geen rechten om matches te verwijderen.');
        }
        
        // Check of de match bij het bedrijf van de gebruiker hoort
        if (!auth()->user()->hasRole('super-admin')) {
            if ($match->vacancy->company_id !== auth()->user()->company_id) {
                abort(403, 'Je hebt geen rechten om deze match te verwijderen.');
            }
        }
        
        $match->delete();
        return redirect()->route('admin.matches.index')->with('success', 'Match succesvol verwijderd.');
    }
}
