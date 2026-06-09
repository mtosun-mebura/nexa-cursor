<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Services\AiChat\AiChatAccessService;
use App\Services\AiChat\AiChatAssistantOrchestrator;
use App\Services\AiChat\AiChatContextResolver;
use App\Services\AiChat\AiChatMessageSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminAiChatController extends Controller
{
    public function sendMessage(
        Request $request,
        AiChatAssistantOrchestrator $orchestrator,
        AiChatContextResolver $contextResolver,
        AiChatAccessService $accessService,
    ): JsonResponse {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if (! $accessService->userMayQueryLiveData($user)) {
            abort(403, 'Geen rechten voor de admin AI-assistent.');
        }

        $validated = $request->validate([
            'message' => 'required|string|max:4000',
            'history' => 'nullable|array|max:20',
            'history.*.role' => 'required_with:history|in:user,assistant,ai',
            'history.*.text' => 'required_with:history|string|max:4000',
            'module' => 'nullable|string|max:50',
            'sessionId' => 'nullable|string|max:120',
        ]);

        try {
            $context = $contextResolver->forAdminRequest(
                $user,
                $validated['module'] ?? 'taxi',
                $validated['sessionId'] ?? null,
            );

            $reply = $orchestrator->handle(
                $context,
                $validated['message'],
            );

            return response()->json([
                'success' => true,
                'reply' => $reply,
            ]);
        } catch (Throwable $e) {
            Log::warning('Admin AI chat request failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            $messages = app(AiChatMessageSettingsService::class);

            return response()->json([
                'success' => false,
                'error' => $messages->unavailableMessage(
                    GeneralSetting::resolveScopeCompanyId(),
                    $validated['module'] ?? 'taxi',
                ),
            ], 502);
        }
    }
}
