<?php

namespace App\Services;

use App\DTO\AiChat\AiChatWebhookPayload;
use App\Services\AiChat\AiChatAssistantOrchestrator;
use App\Services\AiChat\AiChatKnowledgeFallbackService;
use App\Services\AiChat\AiChatMessageSettingsService;
use App\Models\GeneralSetting;
use App\Models\WebsitePage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiChatAssistantService
{
    public function __construct(
        protected WebsiteBuilderService $websiteBuilder
    ) {}

    /**
     * @return array{module: string, endpoint: string, greeting: string, title: string, subtitle: string, storageKey: string}
     */
    public function frontendConfig(?string $moduleName = null): array
    {
        $module = strtolower(trim((string) ($moduleName ?? $this->websiteBuilder->resolvePublicFrontendModuleName() ?? '')));
        $isTaxi = $module === 'taxi';
        $settingsModule = $isTaxi ? 'taxi' : 'default';
        $companyId = GeneralSetting::resolveScopeCompanyId();
        $messages = app(AiChatMessageSettingsService::class);

        return [
            'module' => $isTaxi ? 'taxi' : 'default',
            'endpoint' => route('frontend.ai-chat.message'),
            'greeting' => $messages->greeting($companyId, $settingsModule),
            'title' => $messages->title($companyId, $settingsModule),
            'subtitle' => $messages->subtitle($companyId, $settingsModule),
            'storageKey' => $isTaxi ? 'ai-chat-messages-taxi' : 'ai-chat-messages',
        ];
    }

    public function webhookSettingKey(string $moduleName): string
    {
        return 'ai_chat_webhook_'.strtolower(trim($moduleName));
    }

    public function defaultWebhookUrlForModule(?string $moduleName): ?string
    {
        $module = strtolower(trim((string) $moduleName));

        if ($module !== '') {
            $defaults = config('services.ai_chat.module_defaults', []);
            if (is_array($defaults) && isset($defaults[$module])) {
                $fromDefaults = trim((string) $defaults[$module]);
                if ($fromDefaults !== '') {
                    return $fromDefaults;
                }
            }

            if ($module === 'taxi') {
                $fromConfig = trim((string) config('services.nexa_taxi.assistant_webhook_url', ''));
                if ($fromConfig !== '') {
                    return $fromConfig;
                }
            }
        }

        $generic = trim((string) config('services.ai_chat.webhook_url', ''));

        return $generic !== '' ? $generic : null;
    }

    public function webhookUrlForModule(?string $moduleName): ?string
    {
        $module = strtolower(trim((string) $moduleName));
        if ($module === '') {
            return $this->defaultWebhookUrlForModule(null);
        }

        $fromSetting = trim((string) GeneralSetting::get($this->webhookSettingKey($module), ''));
        if ($fromSetting !== '') {
            return $fromSetting;
        }

        if ($module === 'taxi') {
            $legacy = trim((string) GeneralSetting::get('ai_chat_nexa_taxi_webhook_url', ''));
            if ($legacy !== '') {
                return $legacy;
            }
        }

        return $this->defaultWebhookUrlForModule($module);
    }

    /**
     * @return array{company_id: int, message: string}
     *
     * @deprecated Gebruik AiChatWebhookPayload via AiChatAssistantOrchestrator.
     */
    public function buildWebhookPayload(string $message, ?int $companyId = null): array
    {
        $resolvedCompanyId = $companyId ?? GeneralSetting::resolveScopeCompanyId();
        if ($resolvedCompanyId === null) {
            throw new RuntimeException('Bedrijfscontext ontbreekt voor de AI-assistent.');
        }

        return [
            'company_id' => $resolvedCompanyId,
            'message' => trim($message),
        ];
    }

    public function sendWebhookPayload(AiChatWebhookPayload $payload): string
    {
        $module = $payload->context->module;
        $webhookUrl = $this->webhookUrlForModule($module);

        if ($webhookUrl === null || trim($webhookUrl) === '') {
            throw new RuntimeException('AI-chat webhook is niet geconfigureerd voor deze module.');
        }

        $response = Http::timeout(45)
            ->acceptJson()
            ->asJson()
            ->post($webhookUrl, $payload->toArray());

        if (! $response->successful()) {
            $knowledgeFallback = app(AiChatKnowledgeFallbackService::class)
                ->search($payload->message, $module);
            if ($knowledgeFallback !== null && trim($knowledgeFallback) !== '') {
                Log::info('AI chat gebruikte kennisbank-fallback na webhook HTTP-fout', [
                    'module' => $module,
                    'status' => $response->status(),
                    'message' => Str::limit($payload->message, 120),
                ]);

                return trim($knowledgeFallback);
            }

            Log::warning('AI chat webhook HTTP-fout', [
                'module' => $module,
                'url' => $webhookUrl,
                'status' => $response->status(),
                'body' => Str::limit((string) $response->body(), 500),
            ]);

            throw new RuntimeException('AI-assistent kon geen antwoord ophalen (HTTP '.$response->status().').');
        }

        $reply = $this->extractReplyText($response->json(), $response->body());
        if ($reply === null || trim($reply) === '') {
            $knowledgeFallback = app(AiChatKnowledgeFallbackService::class)
                ->search($payload->message, $module);
            if ($knowledgeFallback !== null && trim($knowledgeFallback) !== '') {
                Log::info('AI chat gebruikte kennisbank-fallback na leeg webhook-antwoord', [
                    'module' => $module,
                    'message' => Str::limit($payload->message, 120),
                ]);

                return trim($knowledgeFallback);
            }

            $fallback = $this->resolveWebsiteFallbackReply($payload->message, $payload->context->companyId);
            if ($fallback !== null && trim($fallback) !== '') {
                Log::info('AI chat gebruikte website-fallback na leeg webhook-antwoord', [
                    'module' => $module,
                    'message' => Str::limit($payload->message, 120),
                ]);

                return trim($fallback);
            }

            Log::warning('AI chat webhook gaf leeg antwoord', [
                'module' => $module,
                'url' => $webhookUrl,
                'body' => Str::limit((string) $response->body(), 500),
            ]);

            return app(AiChatMessageSettingsService::class)->notFoundMessage(
                $payload->context->companyId,
                $module,
            );
        }

        return trim($reply);
    }

    /**
     * @param  array<int, array{role: string, text: string}>  $history
     */
    public function send(string $message, array $history = [], ?string $moduleName = null, ?string $sessionId = null): string
    {
        $orchestrator = app(AiChatAssistantOrchestrator::class);
        $contextResolver = app(\App\Services\AiChat\AiChatContextResolver::class);

        $context = $contextResolver->forPublicRequest($moduleName, $sessionId);

        return $orchestrator->handle($context, $message);
    }

    /**
     * @param  mixed  $payload
     */
    public function extractReplyText(mixed $payload, ?string $rawBody = null): ?string
    {
        if (is_string($payload) && trim($payload) !== '') {
            return trim($payload);
        }

        if (! is_array($payload)) {
            return $this->plainBodyFallback($rawBody);
        }

        if (array_key_exists('answer', $payload)) {
            $formatted = $this->formatWebhookAnswer(
                $payload['answer'],
                isset($payload['count']) && is_numeric($payload['count']) ? (int) $payload['count'] : null
            );
            if ($formatted !== null && trim($formatted) !== '') {
                return $formatted;
            }
        }

        foreach (['output', 'reply', 'message', 'text', 'response', 'answer'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key]) && trim($payload[$key]) !== '') {
                return trim($payload[$key]);
            }
        }

        if (isset($payload['source']) && $payload['source'] === 'public_rates' && isset($payload['answer']) && is_string($payload['answer'])) {
            return trim($payload['answer']);
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            $nested = $this->extractReplyText($payload['data']);
            if ($nested !== null) {
                return $nested;
            }
        }

        if (isset($payload[0])) {
            $first = $payload[0];
            if (is_array($first) && isset($first['json']) && is_array($first['json'])) {
                $nested = $this->extractReplyText($first['json']);
                if ($nested !== null) {
                    return $nested;
                }
            }

            $nested = $this->extractReplyText($first);
            if ($nested !== null) {
                return $nested;
            }
        }

        return $this->plainBodyFallback($rawBody);
    }

    private function formatWebhookAnswer(mixed $answer, ?int $count = null): ?string
    {
        if (is_string($answer) && trim($answer) !== '') {
            return trim($answer);
        }

        if (! is_array($answer)) {
            return null;
        }

        if ($answer === []) {
            return $count === 0 ? 'Er zijn geen resultaten gevonden.' : null;
        }

        if ($this->looksLikeTaxiRideList($answer)) {
            return $this->formatTaxiRideList($answer, $count);
        }

        $textParts = [];
        foreach ($answer as $item) {
            if (is_string($item) && trim($item) !== '') {
                $textParts[] = trim($item);
                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            foreach (['text', 'message', 'output', 'reply', 'answer'] as $key) {
                if (isset($item[$key]) && is_string($item[$key]) && trim($item[$key]) !== '') {
                    $textParts[] = trim($item[$key]);
                    break;
                }
            }
        }

        if ($textParts !== []) {
            return implode("\n\n", $textParts);
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $answer
     */
    private function looksLikeTaxiRideList(array $answer): bool
    {
        foreach ($answer as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (isset($item['pickup_adres']) || isset($item['dropoff_adres']) || isset($item['klant_naam'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rides
     */
    private function formatTaxiRideList(array $rides, ?int $count = null): string
    {
        $total = $count ?? count($rides);

        if ($total === 0 || $rides === []) {
            return 'Er staan geen ritten gepland.';
        }

        $lines = [
            $total === 1
                ? 'Er staat 1 rit gepland:'
                : "Er staan {$total} ritten gepland:",
        ];

        foreach ($rides as $index => $ride) {
            if (! is_array($ride)) {
                continue;
            }

            $label = trim((string) ($ride['klant_naam'] ?? 'Rit '.($index + 1)));
            $pickup = trim((string) ($ride['pickup_adres'] ?? ''));
            $dropoff = trim((string) ($ride['dropoff_adres'] ?? ''));
            $pickupTime = $this->formatPickupTime(isset($ride['pickup_tijd']) ? (string) $ride['pickup_tijd'] : null);
            $status = trim((string) ($ride['status'] ?? ''));

            $entry = ($index + 1).'. '.$label;
            if ($pickup !== '') {
                $entry .= "\n   Van: {$pickup}";
            }
            if ($dropoff !== '') {
                $entry .= "\n   Naar: {$dropoff}";
            }
            if ($pickupTime !== null) {
                $entry .= "\n   Ophaaltijd: {$pickupTime}";
            }
            if ($status !== '') {
                $entry .= "\n   Status: {$status}";
            }

            $lines[] = $entry;
        }

        return implode("\n\n", $lines);
    }

    private function formatPickupTime(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)
                ->timezone(config('app.timezone', 'Europe/Amsterdam'))
                ->locale('nl')
                ->isoFormat('D MMM YYYY HH:mm');
        } catch (Throwable) {
            return trim($value);
        }
    }

    private function plainBodyFallback(?string $rawBody): ?string
    {
        if ($rawBody === null) {
            return null;
        }

        $trimmed = trim($rawBody);
        if ($trimmed === '' || Str::startsWith($trimmed, ['{', '['])) {
            return null;
        }

        return $trimmed;
    }

    private function resolveWebsiteFallbackReply(string $message, int $companyId): ?string
    {
        $keywords = $this->questionKeywords($message);
        if ($keywords === []) {
            return null;
        }

        $bestMatch = null;
        $bestScore = 0;

        foreach ($this->websiteContentChunksForCompany($companyId) as $chunk) {
            $score = $this->scoreWebsiteContentChunk($chunk, $keywords);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $chunk;
            }
        }

        if (! is_array($bestMatch) || $bestScore < 4) {
            return null;
        }

        return $this->formatWebsiteFallbackReply($message, $bestMatch);
    }

    /**
     * @return array<int, string>
     */
    private function questionKeywords(string $message): array
    {
        $normalized = mb_strtolower(trim($message));
        $normalized = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $normalized) ?? $normalized;
        $parts = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $stopWords = [
            'de', 'het', 'een', 'en', 'of', 'voor', 'naar', 'bij', 'ook', 'onze', 'uw', 'jij', 'jullie',
            'hebben', 'heeft', 'heb', 'kan', 'kunnen', 'wat', 'hoe', 'waar', 'wie', 'welke', 'welk',
            'zijn', 'is', 'ben', 'bent', 'mag', 'mogen', 'graag', 'alstublieft', 'alsjeblieft', 'nog',
            'meer', 'over', 'met', 'van', 'op', 'in', 'aan', 'er', 'die', 'dat', 'dit', 'deze', 'daar',
        ];

        $keywords = [];
        foreach ($parts as $part) {
            $part = trim($part, '-');
            if ($part === '' || mb_strlen($part) < 3) {
                continue;
            }
            if (in_array($part, $stopWords, true)) {
                continue;
            }
            $keywords[] = $part;
        }

        return array_values(array_unique($keywords));
    }

    /**
     * @return array<int, array{title: string, text: string}>
     */
    private function websiteContentChunksForCompany(int $companyId): array
    {
        $pages = WebsitePage::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get(['title', 'content', 'home_sections']);

        $chunks = [];
        foreach ($pages as $page) {
            $pageTitle = $this->plainText((string) ($page->title ?? ''));
            if ($pageTitle !== '') {
                $chunks[] = ['title' => $pageTitle, 'text' => $pageTitle];
            }

            $chunks = array_merge($chunks, $this->chunksFromHomeSections($page->home_sections));
            $chunks = array_merge($chunks, $this->chunksFromEditorContent($page->content));
        }

        return array_values(array_filter($chunks, function (array $chunk): bool {
            return ($chunk['title'] ?? '') !== '' || ($chunk['text'] ?? '') !== '';
        }));
    }

    /**
     * @return array<int, array{title: string, text: string}>
     */
    private function chunksFromHomeSections(mixed $sections): array
    {
        if (! is_array($sections)) {
            return [];
        }

        $chunks = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            if (isset($section['title'], $section['subtitle']) && is_string($section['subtitle'])) {
                $title = $this->plainText((string) $section['title']);
                $text = $this->plainText((string) $section['subtitle']);
                if ($title !== '' || $text !== '') {
                    $chunks[] = ['title' => $title, 'text' => trim($title."\n\n".$text)];
                }
            }

            if (isset($section['content']) && is_string($section['content'])) {
                $text = $this->plainText($section['content']);
                if ($text !== '') {
                    $chunks[] = ['title' => '', 'text' => $text];
                }
            }

            if (isset($section['items']) && is_array($section['items'])) {
                foreach ($section['items'] as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $title = $this->plainText((string) ($item['title'] ?? ''));
                    $description = $this->plainText((string) ($item['description'] ?? ''), true);
                    $rawTextField = (string) ($item['text'] ?? '');
                    $textField = $this->plainText($rawTextField, true);

                    if ($title === '' && $rawTextField !== '') {
                        $title = $this->extractTitleFromHtml($rawTextField) ?? $this->extractTitleFromPlainText($textField) ?? '';
                    }

                    if ($textField !== '' && $title !== '') {
                        $textField = $this->removeLeadingTitleFromText($textField, $title);
                    }

                    $text = $description !== '' ? $description : $textField;
                    if ($title === '' && $text === '') {
                        continue;
                    }

                    $chunks[] = [
                        'title' => $title,
                        'text' => $title !== '' && $text !== '' ? $title."\n\n".$text : ($text !== '' ? $text : $title),
                    ];
                }
            }
        }

        return $chunks;
    }

    /**
     * @return array<int, array{title: string, text: string}>
     */
    private function chunksFromEditorContent(mixed $content): array
    {
        if (! is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded) || ! isset($decoded['blocks']) || ! is_array($decoded['blocks'])) {
            $plain = $this->plainText($content);

            return $plain !== '' ? [['title' => '', 'text' => $plain]] : [];
        }

        $chunks = [];
        foreach ($decoded['blocks'] as $block) {
            if (! is_array($block) || ! isset($block['data']) || ! is_array($block['data'])) {
                continue;
            }

            $data = $block['data'];
            if (isset($data['text']) && is_string($data['text'])) {
                $text = $this->plainText($data['text']);
                if ($text !== '') {
                    $chunks[] = ['title' => '', 'text' => $text];
                }
            }

            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $title = $this->plainText((string) ($item['title'] ?? ''));
                    $description = $this->plainText((string) ($item['description'] ?? ''));
                    if ($title === '' && $description === '') {
                        continue;
                    }

                    $chunks[] = [
                        'title' => $title,
                        'text' => $title !== '' && $description !== '' ? $title."\n\n".$description : ($description !== '' ? $description : $title),
                    ];
                }
            }
        }

        return $chunks;
    }

    /**
     * @param  array{title: string, text: string}  $chunk
     * @param  array<int, string>  $keywords
     */
    private function scoreWebsiteContentChunk(array $chunk, array $keywords): int
    {
        $title = mb_strtolower($chunk['title'] ?? '');
        $text = mb_strtolower($chunk['text'] ?? '');
        $score = 0;

        foreach ($keywords as $keyword) {
            if ($title !== '' && str_contains($title, $keyword)) {
                $score += 12;
            }

            if ($text !== '' && str_contains($text, $keyword)) {
                $score += 6;
            }
        }

        return $score;
    }

    /**
     * @param  array{title: string, text: string}  $chunk
     */
    private function formatWebsiteFallbackReply(string $message, array $chunk): string
    {
        $title = trim($chunk['title'] ?? '');
        $text = trim($chunk['text'] ?? '');
        $body = $text !== '' ? $text : $title;

        if ($title !== '' && str_starts_with(mb_strtolower($body), mb_strtolower($title))) {
            $body = trim(mb_substr($body, mb_strlen($title)));
        }

        $body = Str::limit($body, 900, '…');

        if ($this->looksLikeYesNoQuestion($message) && $title !== '') {
            return trim("Ja, wij bieden {$title} aan.\n\n{$body}");
        }

        if ($title !== '' && $body !== '' && ! str_starts_with(mb_strtolower($body), mb_strtolower($title))) {
            return trim("{$title}\n\n{$body}");
        }

        return $body;
    }

    private function looksLikeYesNoQuestion(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));

        return (bool) preg_match('/\b(hebben|bieden|doen|verzorgen|is er|kan ik|kun je|kunnen jullie)\b/u', $normalized);
    }

    private function extractTitleFromPlainText(string $text): ?string
    {
        $firstLine = trim(strtok($text, "\n") ?: '');

        return $firstLine !== '' ? $firstLine : null;
    }

    private function extractTitleFromHtml(string $html): ?string
    {
        if (preg_match('/<strong[^>]*>(.*?)<\/strong>/is', $html, $matches) !== 1) {
            return null;
        }

        $title = $this->plainText((string) ($matches[1] ?? ''));

        return $title !== '' ? $title : null;
    }

    private function removeLeadingTitleFromText(string $text, string $title): string
    {
        $pattern = '/^'.preg_quote($title, '/').'(\s+|$)/iu';

        return trim(preg_replace($pattern, '', $text) ?? $text);
    }

    private function plainText(string $value, bool $preserveParagraphs = false): string
    {
        $value = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $value) ?? $value;
        $value = preg_replace('/<br\s*\/?>/i', "\n", $value) ?? $value;
        $value = preg_replace('/<\/li>/i', "\n", $value) ?? $value;
        $text = strip_tags($value);

        if ($preserveParagraphs) {
            $text = preg_replace("/[ \t]+/u", ' ', $text) ?? $text;
            $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

            return trim($text);
        }

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }
}
