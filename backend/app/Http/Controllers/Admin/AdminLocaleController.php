<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class AdminLocaleController extends Controller
{
    public const LOCALES = ['nl', 'en'];

    public function switch(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, self::LOCALES, true), 404);

        session(['locale' => $locale]);

        return redirect()->back(fallback: route('admin.dashboard'));
    }
}
