<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\AiChat\AiChatAssistantOrchestrator;
use App\Services\AiChat\AiChatContextResolver;
use App\Services\AiChat\AiChatMessageSettingsService;
use App\Models\GeneralSetting;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiChatController extends Controller
{
    public function sendMessage(Request $request, WebsiteBuilderService $websiteBuilder): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:4000',
            'history' => 'nullable|array|max:20',
            'history.*.role' => 'required_with:history|in:user,assistant,ai',
            'history.*.text' => 'required_with:history|string|max:4000',
            'module' => 'nullable|string|max:50',
            'sessionId' => 'nullable|string|max:120',
        ]);

        $module = $validated['module'] ?? $websiteBuilder->resolvePublicFrontendModuleName();
        $history = collect($validated['history'] ?? [])
            ->map(fn (array $item): array => [
                'role' => $item['role'] === 'ai' ? 'assistant' : $item['role'],
                'text' => $item['text'],
            ])
            ->values()
            ->all();

        try {
            $context = app(AiChatContextResolver::class)->forPublicRequest(
                $validated['module'] ?? $websiteBuilder->resolvePublicFrontendModuleName(),
                $validated['sessionId'] ?? null,
            );

            $reply = app(AiChatAssistantOrchestrator::class)->handle(
                $context,
                $validated['message'],
            );

            return response()->json([
                'success' => true,
                'reply' => $reply,
            ]);
        } catch (Throwable $e) {
            Log::warning('AI chat assistant request failed', [
                'module' => $module,
                'message' => $e->getMessage(),
            ]);

            $messages = app(AiChatMessageSettingsService::class);

            return response()->json([
                'success' => false,
                'error' => $messages->unavailableMessage(
                    GeneralSetting::resolveScopeCompanyId(),
                    is_string($module) ? $module : 'taxi',
                ),
            ], 502);
        }
    }
}
