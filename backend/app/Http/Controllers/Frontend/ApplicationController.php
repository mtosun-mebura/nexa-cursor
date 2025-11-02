<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    /**
     * Display a listing of the applications.
     */
    public function index()
    {
        return view('frontend.pages.applications');
    }

    /**
     * Show the application details.
     */
    public function show($id)
    {
        // Voor nu gebruiken we een mock ID, later kan dit gekoppeld worden aan echte data
        $applicationId = $id;
        
        return view('frontend.pages.applications.show', [
            'applicationId' => $applicationId
        ]);
    }

    /**
     * Show the application status.
     */
    public function status($id)
    {
        // Voor nu gebruiken we een mock ID, later kan dit gekoppeld worden aan echte data
        $applicationId = $id;
        
        return view('frontend.pages.applications.status', [
            'applicationId' => $applicationId
        ]);
    }
}

