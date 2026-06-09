<?php

namespace App\Http\Controllers\Api;

use App\DTO\AiChat\AiChatIntentResult;
use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatChannel;
use App\Enums\AiChat\AiChatDataSource;
use App\Enums\AiChat\AiChatIntent;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\AiChat\AiChatAuditLogger;
use App\Services\AiChat\AiChatLiveQueryService;
use App\Services\AiChat\AiChatPublicRatesFormatter;
use App\Services\AiChat\AiChatSqlGuardService;
use App\Services\AiChat\AiChatSqlTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Beveiligde SQL-gateway voor n8n.
 * Alleen vooraf gedefinieerde intents; geen vrije SQL vanuit n8n.
 */
class AiChatSqlController extends Controller
{
    public function execute(
        Request $request,
        AiChatSqlTokenService $tokenService,
        AiChatSqlGuardService $sqlGuard,
        AiChatLiveQueryService $liveQueryService,
        AiChatPublicRatesFormatter $ratesFormatter,
        AiChatAuditLogger $auditLogger,
    ): JsonResponse {
        $validated = $request->validate([
            'intent' => ['required', 'string', Rule::enum(AiChatIntent::class)],
            'sql_token' => 'required|string',
            'company_id' => 'required|integer|min:1',
        ]);

        $intent = AiChatIntent::from($validated['intent']);

        if ($intent === AiChatIntent::Faq) {
            return response()->json([
                'success' => false,
                'error' => 'FAQ-intent mag geen live SQL gebruiken.',
            ], 422);
        }

        try {
            $claims = $tokenService->validate($validated['sql_token'], $intent);
            $sqlGuard->assertMayExecute($claims, $intent);
            $sqlGuard->assertCompanyMatches($claims, (int) $validated['company_id']);

            $result = $liveQueryService->execute($intent, $claims);

            $dataSource = $intent === AiChatIntent::Tarieven
                ? AiChatDataSource::PublicRates
                : AiChatDataSource::Sql;

            $auditLogger->log(
                new AiChatRequestContext(
                    companyId: (int) $claims['company_id'],
                    channel: $claims['allow_live_data'] ? AiChatChannel::Admin : AiChatChannel::Public,
                    userId: $claims['user_id'],
                ),
                new AiChatIntentResult(
                    intent: $intent,
                    isAdmin: $claims['allow_live_data'],
                    allowLiveData: $claims['allow_live_data'],
                    allowPublicRates: $claims['allow_public_rates'],
                ),
                '[n8n live sql] '.$intent->value,
                $dataSource,
            );

            if ($intent === AiChatIntent::Tarieven) {
                $company = Company::query()->find((int) $claims['company_id']);
                $answer = $ratesFormatter->format(
                    $result['rows'],
                    $company?->name,
                );

                return response()->json([
                    'success' => true,
                    'intent' => $intent->value,
                    'answer' => $answer,
                    'source' => 'public_rates',
                    'count' => $result['count'],
                    'rows' => $result['rows'],
                ]);
            }

            return response()->json([
                'success' => true,
                'intent' => $intent->value,
                'source' => 'sql',
                'count' => $result['count'],
                'rows' => $result['rows'],
            ]);
        } catch (Throwable $e) {
            Log::warning('AI chat SQL gateway blocked request', [
                'intent' => $validated['intent'],
                'company_id' => $validated['company_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 403);
        }
    }
}
