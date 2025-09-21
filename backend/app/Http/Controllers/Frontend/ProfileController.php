<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function show()
    {
        return view('frontend.pages.profile');
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        // Profile update logic will be implemented here
        return redirect()->route('profile')->with('success', 'Profiel bijgewerkt!');
    }
}
