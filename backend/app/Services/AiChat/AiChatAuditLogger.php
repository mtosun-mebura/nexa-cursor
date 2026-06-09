<?php

namespace App\Services\AiChat;

use App\DTO\AiChat\AiChatIntentResult;
use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatDataSource;
use App\Enums\AiChat\AiChatIntent;
use App\Models\AiChatAuditLog;
use Illuminate\Support\Facades\Log;

final class AiChatAuditLogger
{
    public function log(
        AiChatRequestContext $context,
        AiChatIntentResult $intent,
        string $message,
        AiChatDataSource $dataSource,
    ): void {
        try {
            AiChatAuditLog::query()->create([
                'company_id' => $context->companyId,
                'user_id' => $context->userId,
                'channel' => $context->channel->value,
                'intent' => $intent->intent->value,
                'is_admin' => $intent->isAdmin,
                'allow_live_data' => $intent->allowLiveData,
                'message' => mb_substr($message, 0, 4000),
                'data_source' => $dataSource->value,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AI chat audit log failed', [
                'company_id' => $context->companyId,
                'intent' => $intent->intent->value,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
