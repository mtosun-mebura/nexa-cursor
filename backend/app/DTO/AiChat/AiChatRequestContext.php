<?php

namespace App\DTO\AiChat;

use App\Enums\AiChat\AiChatChannel;
use App\Models\User;

final readonly class AiChatRequestContext
{
    public function __construct(
        public int $companyId,
        public AiChatChannel $channel,
        public ?int $userId = null,
        public ?User $user = null,
        public ?string $sessionId = null,
        public string $module = 'taxi',
    ) {}

    public function isAdminChannel(): bool
    {
        return $this->channel === AiChatChannel::Admin;
    }

    /**
     * @return array{company_id: int, channel: string, user_id?: int, role?: string, module: string, session_id?: string}
     */
    public function toIdentityArray(): array
    {
        $payload = [
            'company_id' => $this->companyId,
            'channel' => $this->channel->value,
            'module' => $this->module,
        ];

        if ($this->userId !== null) {
            $payload['user_id'] = $this->userId;
            $payload['role'] = 'admin';
        }

        if ($this->sessionId !== null && $this->sessionId !== '') {
            $payload['session_id'] = $this->sessionId;
        }

        return $payload;
    }
}
