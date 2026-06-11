<?php

namespace App\Services\AiChat;

use App\Services\ModuleDatabaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Zoekt in de Nexa Taxi kennisbank via de module-database connection (schema-aware).
 */
final class AiChatRagSearchService
{
    public function __construct(
        private readonly ModuleDatabaseService $moduleDatabaseService,
        private readonly AiChatRichTextFormatter $richTextFormatter,
    ) {}

    /**
     * @return list<array{title: string, category: ?string, content: string}>
     */
    public function search(string $keyword, ?string $message = null, string $module = 'taxi'): array
    {
        if (strtolower(trim($module)) !== 'taxi') {
            return [];
        }

        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        $connection = $this->resolveTaxiConnection();
        if ($connection === null) {
            return [];
        }

        if (! Schema::connection($connection)->hasTable('knowledge_documents')) {
            Log::warning('AI chat RAG: knowledge_documents ontbreekt op module connection', [
                'connection' => $connection,
            ]);

            return [];
        }

        $like = '%'.$keyword.'%';
        $includeVoorwaarden = $this->messageAsksForVoorwaarden($message ?? '');
        $category = $this->categoryForKeyword($keyword);

        $query = DB::connection($connection)
            ->table('knowledge_documents as d')
            ->select(['d.title', 'd.category', 'd.content']);

        if ($category !== null) {
            $query->where('d.category', $category);
        } else {
            $query->where(function ($builder) use ($like, $connection): void {
                $isPgsql = DB::connection($connection)->getDriverName() === 'pgsql';

                if ($isPgsql) {
                    $builder->where('d.title', 'ilike', $like)
                        ->orWhere('d.content', 'ilike', $like)
                        ->orWhere('d.category', 'ilike', $like);
                } else {
                    $needle = mb_strtolower($like);
                    $builder->whereRaw('LOWER(d.title) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(d.content) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(d.category) LIKE ?', [$needle]);
                }

                if (Schema::connection($connection)->hasTable('knowledge_chunks')) {
                    $builder->orWhereExists(function ($sub) use ($like, $connection, $isPgsql): void {
                        $sub->from('knowledge_chunks as c')
                            ->selectRaw('1')
                            ->whereColumn('c.document_id', 'd.id');

                        if ($isPgsql) {
                            $sub->where('c.chunk_text', 'ilike', $like);
                        } else {
                            $sub->whereRaw('LOWER(c.chunk_text) LIKE ?', [mb_strtolower($like)]);
                        }
                    });
                }
            });
        }

        if (! $includeVoorwaarden) {
            $query->where(function ($builder): void {
                $builder->whereNull('d.category')
                    ->orWhereRaw('LOWER(TRIM(d.category)) <> ?', ['voorwaarden']);
            })->whereRaw('LOWER(d.title) NOT LIKE ?', ['%algemene voorwaarden%']);
        }

        return $query
            ->orderByDesc('d.created_at')
            ->limit($category !== null ? 2 : 5)
            ->get()
            ->map(function ($row): array {
                return [
                    'title' => trim((string) ($row->title ?? '')),
                    'category' => isset($row->category) ? trim((string) $row->category) : null,
                    'content' => trim((string) ($row->content ?? '')),
                ];
            })
            ->filter(fn (array $row) => $row['title'] !== '' || $row['content'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  list<array{title: string, category: ?string, content: string}>  $rows
     */
    public function formatAnswer(array $rows): string
    {
        if ($rows === []) {
            return 'Ik kon hierover niets vinden in de Nexa Taxi kennisbank.';
        }

        $parts = [];
        foreach ($rows as $index => $row) {
            $title = $this->richTextFormatter->htmlToChatText($row['title']);
            $content = $this->richTextFormatter->htmlToChatText($row['content']);

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
            return 'Ik kon hierover niets vinden in de Nexa Taxi kennisbank.';
        }

        return "Ik heb dit gevonden in de Nexa Taxi kennisbank:\n\n".implode("\n\n", $parts);
    }

    public function searchFromMessage(string $message, string $module = 'taxi'): ?string
    {
        $keyword = $this->extractSearchKeyword($message);
        if ($keyword === '') {
            return null;
        }

        $rows = $this->search($keyword, $message, $module);
        if ($rows === []) {
            return null;
        }

        return $this->formatAnswer($rows);
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

    private function messageAsksForVoorwaarden(string $message): bool
    {
        $text = mb_strtolower(trim($message));

        return str_contains($text, 'voorwaarden')
            || str_contains($text, 'annuleringsvoorwaarde')
            || str_contains($text, 'algemene voorwaarden');
    }

    private function resolveTaxiConnection(): ?string
    {
        try {
            $this->moduleDatabaseService->ensureModuleStorageReady('taxi');
        } catch (\Throwable $e) {
            Log::warning('AI chat RAG: module storage niet gereed', ['error' => $e->getMessage()]);

            return null;
        }

        $connection = $this->moduleDatabaseService->getModuleConnectionName('taxi');

        return is_array(config("database.connections.{$connection}")) ? $connection : null;
    }
}
