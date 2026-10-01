<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Support\IncidentCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminIncidentSettingsController extends Controller
{
    public function edit(): View
    {
        $this->ensureSuperAdmin();

        $notificationEmail = trim((string) GeneralSetting::get(
            IncidentCatalog::SETTING_NOTIFICATION_EMAIL,
            IncidentCatalog::DEFAULT_NOTIFICATION_EMAIL
        ));
        if ($notificationEmail === '') {
            $notificationEmail = IncidentCatalog::DEFAULT_NOTIFICATION_EMAIL;
        }

        return view('admin.incidents.settings', [
            'notificationEmail' => $notificationEmail,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'notification_email' => ['required', 'email', 'max:255'],
        ], [
            'notification_email.required' => 'E-mailadres is verplicht.',
            'notification_email.email' => 'Voer een geldig e-mailadres in.',
        ]);

        GeneralSetting::set(
            IncidentCatalog::SETTING_NOTIFICATION_EMAIL,
            strtolower(trim($validated['notification_email']))
        );

        return redirect()
            ->route('admin.incidents.settings.edit')
            ->with('success', 'Incident-e-mailinstellingen opgeslagen.');
    }

    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'Alleen super-admins hebben toegang tot incidentinstellingen.');
    }
}
