<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiChatSendMessageRequest;
use App\Services\AiChat\AiChatAssistantOrchestrator;
use App\Services\AiChat\AiChatContextResolver;
use App\Services\AiChat\AiChatMessageSettingsService;
use App\Models\GeneralSetting;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiChatController extends Controller
{
    public function sendMessage(AiChatSendMessageRequest $request, WebsiteBuilderService $websiteBuilder): JsonResponse
    {
        $validated = $request->validated();
        $quoteAddress = $request->quoteAddress();
        $quoteBaggage = $request->quoteBaggage();

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

            $result = app(AiChatAssistantOrchestrator::class)->handle(
                $context,
                $validated['message'],
                $quoteAddress,
                $quoteBaggage,
            );

            return response()->json(array_merge(['success' => true], $result->toArray()));
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
