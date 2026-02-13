<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FrontendComponentService;
use Illuminate\View\View;

/**
 * Overzicht van front-end componenten (alleen lezen; aanpassen alleen in code).
 */
class AdminFrontendComponentController extends Controller
{
    public function __construct(
        protected FrontendComponentService $componentService
    ) {}

    public function index(): View
    {
        $this->ensureSuperAdmin();
        $grouped = $this->componentService->groupedByModule();
        return view('admin.frontend-components.index', compact('grouped'));
    }

    protected function ensureSuperAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admins hebben toegang tot componenten.');
        }
    }
}
