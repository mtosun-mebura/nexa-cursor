<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\Company;
use Illuminate\Http\Request;

class AdminInterviewController extends Controller
{
    public function index()
    {
        $interviews = Interview::with(['match', 'company'])->paginate(10);
        return view('admin.interviews.index', compact('interviews'));
    }

    public function create()
    {
        $companies = Company::all();
        return view('admin.interviews.create', compact('companies'));
    }

    public function store(Request $request)
    {
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

        Interview::create($request->all());
        return redirect()->route('admin.interviews.index')->with('success', 'Interview succesvol aangemaakt.');
    }

    public function show(Interview $interview)
    {
        return view('admin.interviews.show', compact('interview'));
    }

    public function edit(Interview $interview)
    {
        $companies = Company::all();
        return view('admin.interviews.edit', compact('interview', 'companies'));
    }

    public function update(Request $request, Interview $interview)
    {
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
        $interview->delete();
        return redirect()->route('admin.interviews.index')->with('success', 'Interview succesvol verwijderd.');
    }
}
