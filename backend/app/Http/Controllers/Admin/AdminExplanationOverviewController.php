<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminExplanationCatalog;
use Illuminate\Contracts\View\View;

class AdminExplanationOverviewController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return view('admin.explanation-overview.index', [
            'groups' => AdminExplanationCatalog::groups(),
        ]);
    }
}
