<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Services\AiChat\AiChatMessageSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiChatbotSettingsController extends Controller
{
    public function edit(AiChatMessageSettingsService $messages): View
    {
        $this->authorizeOrPermissionAny(['ai_chatbot.update', 'rides.update', 'vehicles.update']);

        $companyId = GeneralSetting::resolveScopeCompanyId();

        return view('taxi::admin.ai_chatbot.settings', [
            'noTenantSelected' => $companyId === null,
            'messages' => $messages->all($companyId, 'taxi'),
            'defaults' => $messages->defaults('taxi'),
        ]);
    }

    public function update(Request $request, AiChatMessageSettingsService $messages): RedirectResponse
    {
        $this->authorizeOrPermissionAny(['ai_chatbot.update', 'rides.update', 'vehicles.update']);

        $companyId = GeneralSetting::resolveScopeCompanyId();
        if ($companyId === null) {
            return redirect()
                ->route('admin.taxi.ai_chatbot.settings.edit')
                ->with('error', 'Selecteer eerst een bedrijf om AI-chatbot instellingen op te slaan.');
        }

        $validated = $request->validate([
            'greeting' => 'nullable|string|max:2000',
            'title' => 'nullable|string|max:120',
            'subtitle' => 'nullable|string|max:200',
            'not_found_message' => 'nullable|string|max:2000',
            'unavailable_message' => 'nullable|string|max:2000',
            'live_data_denied_message' => 'nullable|string|max:2000',
        ]);

        $messages->save($validated, $companyId, 'taxi');

        return redirect()
            ->route('admin.taxi.ai_chatbot.settings.edit')
            ->with('success', 'AI-chatbot instellingen opgeslagen.');
    }

    private function authorizeOrPermissionAny(array $abilities): void
    {
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        foreach ($abilities as $ability) {
            if (auth()->user()->can($ability)) {
                return;
            }
        }
        abort(403, 'Geen rechten voor deze actie.');
    }
}
