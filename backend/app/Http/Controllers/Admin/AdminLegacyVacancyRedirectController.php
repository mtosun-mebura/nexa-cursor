<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Oude URL /admin/vacancies → module-route, of dashboard als Skillmatching uit staat.
 */
class AdminLegacyVacancyRedirectController extends Controller
{
    public function __invoke(Request $request, ?string $path = null): RedirectResponse
    {
        if (! Route::has('admin.skillmatching.vacancies.index')) {
            return redirect()->route('admin.dashboard');
        }

        $target = rtrim(route('admin.skillmatching.vacancies.index'), '/');
        if (is_string($path) && $path !== '') {
            $target .= '/'.$path;
        }

        $query = $request->getQueryString();
        if (is_string($query) && $query !== '') {
            $target .= '?'.$query;
        }

        return redirect()->to($target);
    }
}
