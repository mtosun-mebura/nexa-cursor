<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiChatSendMessageRequest;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Services\AiChat\AiChatAssistantOrchestrator;
use App\Services\AiChat\AiChatContextResolver;
use App\Services\AiChat\AiChatMessageSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * AI-chat voor ingelogde klanten in Mijn Taxi (eigen ritten alleen via dit kanaal).
 */
class TaxiPortalAiChatController extends Controller
{
    public function sendMessage(
        AiChatSendMessageRequest $request,
        AiChatAssistantOrchestrator $orchestrator,
        AiChatContextResolver $contextResolver,
    ): JsonResponse {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user !== null, 401);

        $validated = $request->validated();
        $quoteAddress = $request->quoteAddress();
        $quoteBaggage = $request->quoteBaggage();

        try {
            $context = $contextResolver->forMijnTaxiRequest(
                $user,
                $validated['module'] ?? 'taxi',
                $validated['sessionId'] ?? null,
            );

            $result = $orchestrator->handle(
                $context,
                $validated['message'],
                $quoteAddress,
                $quoteBaggage,
            );

            return response()->json(array_merge(['success' => true], $result->toArray()));
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::warning('Mijn Taxi AI chat request failed', [
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
