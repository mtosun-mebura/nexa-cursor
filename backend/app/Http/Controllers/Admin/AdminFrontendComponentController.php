<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FrontendComponentService;
use App\Services\WebsiteBuilderService;
use Illuminate\View\View;
use Illuminate\Support\Collection;

/**
 * Overzicht van front-end componenten (alleen lezen; aanpassen alleen in code).
 * Toont alleen componenten van de actieve module.
 */
class AdminFrontendComponentController extends Controller
{
    public function __construct(
        protected FrontendComponentService $componentService,
        protected WebsiteBuilderService $websiteBuilder
    ) {}

    public function index(): View
    {
        $this->ensureSuperAdmin();
        $activeModuleName = $this->websiteBuilder->getActiveModuleName();
        $components = $this->componentService->availableForPage($activeModuleName);
        $grouped = $components->isEmpty()
            ? new Collection
            : $components->groupBy('module_name');
        return view('admin.frontend-components.index', compact('grouped', 'activeModuleName'));
    }

    protected function ensureSuperAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admins hebben toegang tot componenten.');
        }
    }
}
