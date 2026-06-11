<?php

namespace App\Services\AiChat;

use App\DTO\AiChat\AiChatMessageResult;
use App\DTO\AiChat\AiChatRequestContext;
use App\DTO\AiChat\AiChatWebhookPayload;
use App\Enums\AiChat\AiChatDataSource;
use App\Enums\AiChat\AiChatIntent;
use App\Services\AiChatAssistantService;

final class AiChatAssistantOrchestrator
{
    public function __construct(
        private readonly AiChatIntentService $intentService,
        private readonly AiChatSqlTokenService $sqlTokenService,
        private readonly AiChatAuditLogger $auditLogger,
        private readonly AiChatAssistantService $assistantService,
        private readonly AiChatAccessService $accessService,
        private readonly AiChatMessageSettingsService $messageSettings,
        private readonly AiChatQuoteConversationService $quoteConversation,
    ) {}

    public function handle(
        AiChatRequestContext $context,
        string $message,
        ?array $quoteAddress = null,
        ?array $quoteBaggage = null,
    ): AiChatMessageResult {
        if ($this->quoteConversation->hasActiveSession($context)) {
            $result = $this->quoteConversation->handle($context, $message, $quoteAddress, $quoteBaggage);
            $this->auditLogger->log(
                $context,
                $this->quoteConversation->intentResultForAudit(),
                $message,
                AiChatDataSource::Quote,
            );

            return $result;
        }

        $intentResult = $this->intentService->classify($message, $context);

        if ($intentResult->intent === AiChatIntent::RitOfferte) {
            $result = $this->quoteConversation->handle($context, $message, $quoteAddress, $quoteBaggage);
            $this->auditLogger->log($context, $intentResult, $message, AiChatDataSource::Quote);

            return $result;
        }

        if ($intentResult->allowPublicRates && ! $context->isPublicChannel()) {
            $this->auditLogger->log($context, $intentResult, $message, AiChatDataSource::Denied);

            return new AiChatMessageResult($this->messageSettings->publicRatesDeniedMessage(
                $context->channel,
                $context->companyId,
                $context->module ?? 'taxi',
            ));
        }

        if ($intentResult->intent->requiresLiveData() && ! $intentResult->allowLiveData) {
            $this->auditLogger->log($context, $intentResult, $message, AiChatDataSource::Denied);

            if ($intentResult->intent === AiChatIntent::MijnRit) {
                if ($this->accessService->userMayQueryLiveData($context->user)) {
                    return new AiChatMessageResult($this->messageSettings->liveDataDeniedMessage(
                        $context->companyId,
                        $context->module ?? 'taxi',
                    ));
                }

                return new AiChatMessageResult($this->messageSettings->ownRideDeniedMessage(
                    $context->user,
                    $context->companyId,
                    $context->module ?? 'taxi',
                ));
            }

            return new AiChatMessageResult($this->messageSettings->liveDataDeniedMessage(
                $context->companyId,
                $context->module ?? 'taxi',
            ));
        }

        $sqlToken = $this->sqlTokenService->issue($context, $intentResult);

        $payload = new AiChatWebhookPayload(
            context: $context,
            message: $message,
            intent: $intentResult,
            sqlToken: $sqlToken,
        );

        $dataSource = match (true) {
            $intentResult->intent === AiChatIntent::Tarieven => AiChatDataSource::PublicRates,
            $intentResult->allowLiveData => AiChatDataSource::Sql,
            $intentResult->intent->usesRag() => AiChatDataSource::Rag,
            default => AiChatDataSource::Rag,
        };

        $this->auditLogger->log($context, $intentResult, $message, $dataSource);

        return new AiChatMessageResult($this->assistantService->sendWebhookPayload($payload));
    }
}
