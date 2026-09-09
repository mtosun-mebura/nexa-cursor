<?php

namespace App\Services\AiChat;

use App\DTO\AiChat\AiChatIntentResult;
use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatIntent;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Beantwoordt live-data vragen lokaal (zonder n8n), met dezelfde queries als de SQL-gateway.
 */
final class AiChatLiveAnswerService
{
    public function __construct(
        private readonly AiChatLiveQueryService $liveQuery,
        private readonly AiChatAdminSqlFormatter $adminFormatter,
        private readonly AiChatOwnRideFormatter $ownRideFormatter,
        private readonly AiChatPublicRatesFormatter $ratesFormatter,
    ) {}

    public function reply(AiChatRequestContext $context, AiChatIntentResult $intentResult): string
    {
        $intent = $intentResult->intent;
        $claims = [
            'company_id' => $context->companyId,
            'channel' => $context->channel->value,
            'user_id' => $context->userId,
            'intent' => $intent->value,
            'allow_live_data' => $intentResult->allowLiveData,
            'allow_public_rates' => $intentResult->allowPublicRates,
            'exp' => now()->addMinutes(5)->timestamp,
            'query_hint' => $intentResult->queryHint,
            'response_mode' => $intentResult->responseMode->value,
        ];

        if ($context->companyId <= 0 && ! $intent->allowsCompanyIdZero()) {
            return 'Selecteer eerst een tenant of noem de bedrijfsnaam, bijvoorbeeld: hoeveel omzet heeft Taxi Tosun deze maand?';
        }

        try {
            $result = $this->liveQuery->execute($intent, $claims);
        } catch (Throwable $e) {
            Log::warning('AI chat lokale live query mislukt', [
                'intent' => $intent->value,
                'company_id' => $context->companyId,
                'error' => $e->getMessage(),
            ]);

            return 'Ik kon de live gegevens nu niet ophalen. Probeer het zo meteen opnieuw.';
        }

        if ($intent === AiChatIntent::Tarieven) {
            return $this->ratesFormatter->format($result['rows'] ?? []);
        }

        if ($intent === AiChatIntent::MijnRit) {
            return $this->ownRideFormatter->format($result['rows'] ?? [], $intentResult->queryHint);
        }

        return $this->adminFormatter->format(
            $intent,
            $result,
            $intentResult->responseMode->value,
        );
    }
}
