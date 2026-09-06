<?php

namespace App\Services\AiWebsite\Providers;

interface AiProviderInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function generateStructured(string $promptVersion, array $payload, string $systemPrompt): ?array;
}
