<?php

namespace App\Services\AiChat;

use App\Services\ModuleDatabaseService;
use Illuminate\Support\Facades\DB;

/**
 * Lokale fallback op de Nexa Taxi kennisbank wanneer n8n geen antwoord geeft.
 */
final class AiChatKnowledgeFallbackService
{
    public function __construct(
        private readonly ModuleDatabaseService $moduleDatabaseService,
        private readonly AiChatRichTextFormatter $richTextFormatter,
    ) {}

    public function search(string $message, ?string $module = 'taxi'): ?string
    {
        if (strtolower(trim((string) $module)) !== 'taxi') {
            return null;
        }

        $connection = $this->resolveTaxiConnection();
        if ($connection === null) {
            return null;
        }

        if (! $this->tableExists($connection, 'knowledge_documents')) {
            return null;
        }

        $keyword = $this->extractSearchKeyword($message);
        if ($keyword === '') {
            return null;
        }

        $category = $this->categoryForKeyword($keyword);
        $query = DB::connection($connection)->table('knowledge_documents');
        $like = '%'.$keyword.'%';

        if ($category !== null) {
            $query->where('category', $category);
        } else {
            $query->where(function ($builder) use ($like): void {
                $builder->where('title', 'ilike', $like)
                    ->orWhere('content', 'ilike', $like)
                    ->orWhere('category', 'ilike', $like);
            });
        }

        $rows = $query
            ->orderByDesc('created_at')
            ->limit($category !== null ? 2 : 3)
            ->get(['title', 'content', 'category']);

        if ($rows->isEmpty() && $category !== null) {
            $rows = DB::connection($connection)
                ->table('knowledge_documents')
                ->where(function ($builder) use ($like): void {
                    $builder->where('title', 'ilike', $like)
                        ->orWhere('content', 'ilike', $like)
                        ->orWhere('category', 'ilike', $like);
                })
                ->orderByDesc('created_at')
                ->limit(3)
                ->get(['title', 'content', 'category']);
        }

        if ($rows->isEmpty()) {
            return null;
        }

        /** @var list<array{title: mixed, content: mixed, category: mixed}> $documents */
        $documents = $rows->map(fn ($row) => (array) $row)->all();

        return $this->formatAnswer($documents);
    }

    /**
     * @param  list<array{title: mixed, content: mixed, category: mixed}>  $documents
     */
    private function formatAnswer(array $documents): string
    {
        $parts = [];
        foreach ($documents as $index => $document) {
            $title = $this->richTextFormatter->htmlToChatText((string) ($document['title'] ?? ''));
            $content = $this->richTextFormatter->htmlToChatText((string) ($document['content'] ?? ''));

            if ($title === '' && $content === '') {
                continue;
            }

            $block = ($index + 1).'. '.($title !== '' ? $title : 'Kennisbank');
            if ($content !== '' && ! str_starts_with(mb_strtolower($content), mb_strtolower($title))) {
                $block .= "\n".$content;
            } elseif ($content !== '' && $title === '') {
                $block = ($index + 1).'. '.$content;
            }

            $parts[] = $block;
        }

        if ($parts === []) {
            return '';
        }

        return "Ik heb dit gevonden in de Nexa Taxi kennisbank:\n\n".implode("\n\n", $parts);
    }

    private function extractSearchKeyword(string $message): string
    {
        $cleaned = mb_strtolower(trim($message));
        $cleaned = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned;

        $priority = [
            'contact', 'telefoon', 'email', 'mail', 'bereikbaar',
            'ziekenhuis', 'luchthaven', 'rolstoel', 'schiphol', 'airport',
            'vervoer', 'tarief', 'tarieven',
        ];

        foreach ($priority as $term) {
            if (str_contains($cleaned, $term)) {
                return $term;
            }
        }

        $words = preg_split('/\s+/u', $cleaned, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            if (mb_strlen($word) >= 4) {
                return $word;
            }
        }

        return trim($cleaned);
    }

    private function categoryForKeyword(string $keyword): ?string
    {
        return match ($keyword) {
            'contact', 'telefoon', 'email', 'mail', 'bereikbaar' => 'contact',
            default => null,
        };
    }

    private function tableExists(string $connection, string $table): bool
    {
        return DB::connection($connection)->getSchemaBuilder()->hasTable($table);
    }

    private function resolveTaxiConnection(): ?string
    {
        $connection = $this->moduleDatabaseService->getModuleConnectionName('taxi');

        if (! is_array(config("database.connections.{$connection}"))) {
            try {
                $this->moduleDatabaseService->registerConnection('taxi');
            } catch (\Throwable) {
                return null;
            }
        }

        return is_array(config("database.connections.{$connection}")) ? $connection : null;
    }
}
