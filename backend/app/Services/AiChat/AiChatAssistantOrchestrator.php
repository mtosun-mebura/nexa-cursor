<?php

namespace App\Services\AiChat;

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
    ) {}

    public function handle(AiChatRequestContext $context, string $message): string
    {
        $intentResult = $this->intentService->classify($message, $context);

        if ($intentResult->intent->requiresLiveData() && ! $intentResult->allowLiveData) {
            $this->auditLogger->log($context, $intentResult, $message, AiChatDataSource::Denied);

            return app(AiChatMessageSettingsService::class)->liveDataDeniedMessage(
                $context->companyId,
                $context->module ?? 'taxi',
            );
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
            default => AiChatDataSource::Rag,
        };

        $this->auditLogger->log($context, $intentResult, $message, $dataSource);

        return $this->assistantService->sendWebhookPayload($payload);
    }
}
