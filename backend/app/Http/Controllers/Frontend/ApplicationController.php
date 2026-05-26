<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    /**
     * Display a listing of the user's applications.
     */
    public function index()
    {
        $applications = Application::where('user_id', auth()->id())
            ->with(['vacancy.company'])
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total' => $applications->count(),
            'in_progress' => $applications->whereIn('status', ['initiated', 'submitted'])->count(),
            'interview' => $applications->where('status', 'interview')->count(),
            'offer' => $applications->where('status', 'offer')->count(),
            'rejected' => $applications->where('status', 'rejected')->count(),
        ];

        return view('frontend.pages.applications', compact('applications', 'stats'));
    }

    /**
     * Show the application details.
     */
    public function show($id)
    {
        $application = Application::where('user_id', auth()->id())
            ->with(['vacancy.company'])
            ->findOrFail($id);

        return view('frontend.pages.applications.show', compact('application'));
    }

    /**
     * Show the application status.
     */
    public function status($id)
    {
        $application = Application::where('user_id', auth()->id())
            ->with(['vacancy.company'])
            ->findOrFail($id);

        return view('frontend.pages.applications.status', compact('application'));
    }
}




