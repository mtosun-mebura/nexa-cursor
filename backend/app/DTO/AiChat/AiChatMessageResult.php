<?php

namespace App\DTO\AiChat;

final readonly class AiChatMessageResult
{
    /**
     * @param  array{type: string, step: string, placeholder?: string, min?: string|int, max?: int}|null  $input
     */
    public function __construct(
        public string $reply,
        public ?array $input = null,
    ) {}

    /**
     * @return array{reply: string, input?: array<string, mixed>}
     */
    public function toArray(): array
    {
        $payload = ['reply' => $this->reply];

        if ($this->input !== null && $this->input !== []) {
            $payload['input'] = $this->input;
        }

        return $payload;
    }
}
