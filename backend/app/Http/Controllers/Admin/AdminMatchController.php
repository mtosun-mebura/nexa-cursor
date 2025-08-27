<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobMatch;
use Illuminate\Http\Request;

class AdminMatchController extends Controller
{
    public function index()
    {
        $matches = \App\Models\JobMatch::with(['user', 'vacancy'])->paginate(10);
        return view('admin.matches.index', compact('matches'));
    }

    public function create()
    {
        return view('admin.matches.create');
    }

    public function store(Request $request)
    {
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

        \App\Models\JobMatch::create($request->all());
        return redirect()->route('admin.matches.index')->with('success', 'Match succesvol aangemaakt.');
    }

    public function show(\App\Models\JobMatch $match)
    {
        return view('admin.matches.show', compact('match'));
    }

    public function edit(\App\Models\JobMatch $match)
    {
        return view('admin.matches.edit', compact('match'));
    }

    public function update(Request $request, \App\Models\JobMatch $match)
    {
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
        $match->delete();
        return redirect()->route('admin.matches.index')->with('success', 'Match succesvol verwijderd.');
    }
}
