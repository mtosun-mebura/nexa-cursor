<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CentralWelcomePageService;
use Illuminate\Http\RedirectResponse;

/**
 * Admin-menu "Welkom" → dezelfde website builder als andere frontend-pagina's (secties, slepen, TinyMCE).
 */
class AdminWelcomePageController extends Controller
{
    public function __construct(
        protected CentralWelcomePageService $centralWelcomePage
    ) {}

    public function edit(): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $page = $this->centralWelcomePage->ensurePageExists();

        return redirect()->route('admin.website-pages.edit', $page);
    }

    /**
     * @deprecated Gebruik website-pagina editor; alleen voor oude GeneralSetting JSON fallback op /.
     */
    public static function getWelcomeContent(): array
    {
        $json = \App\Models\GeneralSetting::get('welcome_page_content');
        if (! $json) {
            return [];
        }
        $decoded = json_decode($json, true);

        return is_array($decoded) ? array_filter($decoded, fn ($v) => $v !== '') : [];
    }

    private function ensureSuperAdmin(): void
    {
        if (! auth()->user()?->hasRole('super-admin')) {
            abort(403);
        }
    }
}
