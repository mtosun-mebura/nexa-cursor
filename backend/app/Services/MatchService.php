<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class MatchService
{
    /**
     * Get rule-based ranked vacancies for a candidate
     * Uses skills overlap, experience match, and work mode
     * 
     * @param int $candidateId
     * @param int $limit
     * @return Collection
     */
    public function getRuleBasedMatches(int $candidateId, int $limit = 20): Collection
    {
        $query = "
            WITH candidate AS (
                SELECT 
                    c.*, 
                    ct.top_skills, 
                    ct.tools_tech,
                    c.experience_years
                FROM candidates c
                LEFT JOIN candidate_texts ct ON ct.candidate_id = c.id
                WHERE c.id = ?
            ),
            ranked AS (
                SELECT
                    v.*,
                    -- Skills overlap score (required skills)
                    COALESCE(
                        array_length(
                            ARRAY(
                                SELECT UNNEST(v.required_skills::text[])
                                INTERSECT 
                                SELECT UNNEST(COALESCE((SELECT top_skills::text[] FROM candidate), ARRAY[]::text[]))
                            ),
                            1
                        ),
                        0
                    ) AS req_overlap,
                    -- Tools/tech overlap score
                    COALESCE(
                        array_length(
                            ARRAY(
                                SELECT UNNEST(v.tools_tech::text[])
                                INTERSECT 
                                SELECT UNNEST(COALESCE((SELECT tools_tech::text[] FROM candidate), ARRAY[]::text[]))
                            ),
                            1
                        ),
                        0
                    ) AS tool_overlap,
                    -- Experience match score (higher is better, max 10)
                    GREATEST(
                        0, 
                        10 - ABS(COALESCE(v.min_experience, 0) - COALESCE((SELECT experience_years FROM candidate), 0))
                    ) AS exp_score,
                    -- Work mode match (1 if match, 0 if not)
                    CASE 
                        WHEN v.work_mode = (SELECT work_mode FROM candidate) THEN 1
                        WHEN v.work_mode IS NULL OR (SELECT work_mode FROM candidate) IS NULL THEN 0.5
                        ELSE 0
                    END AS work_mode_score
                FROM vacancies v
                WHERE v.is_active = true
                AND (v.published_at IS NULL OR v.published_at <= NOW())
            )
            SELECT 
                *,
                (req_overlap * 3 + tool_overlap * 2 + exp_score + work_mode_score) AS total_score
            FROM ranked
            ORDER BY total_score DESC
            LIMIT ?
        ";

        $results = DB::select($query, [$candidateId, $limit]);
        
        return collect($results)->map(function ($item) {
            return (object) $item;
        });
    }

    /**
     * Get semantic similarity matches using pgvector embeddings
     * 
     * @param int $candidateId
     * @param int $limit
     * @return Collection
     */
    public function getSemanticMatches(int $candidateId, int $limit = 10): Collection
    {
        // Check if pgvector is available
        if (config('database.default') !== 'pgsql') {
            return collect([]);
        }

        $query = "
            SELECT 
                v.*,
                1 - (ve.embedding <=> ce.embedding) AS cosine_similarity
            FROM vacancies v
            INNER JOIN vacancy_embeddings ve ON ve.vacancy_id = v.id
            INNER JOIN candidate_embeddings ce ON ce.candidate_id = ?
            WHERE v.is_active = true
            AND (v.published_at IS NULL OR v.published_at <= NOW())
            ORDER BY cosine_similarity DESC
            LIMIT ?
        ";

        try {
            $results = DB::select($query, [$candidateId, $limit]);
            
            return collect($results)->map(function ($item) {
                return (object) $item;
            });
        } catch (\Exception $e) {
            // If embeddings don't exist or pgvector is not available, return empty
            return collect([]);
        }
    }

    /**
     * Hybrid matching: combines rule-based preselectie with semantic reranking
     * 
     * @param int $candidateId
     * @param int $ruleBasedLimit First get top N from rule-based
     * @param int $finalLimit Final top N after semantic reranking
     * @return Collection
     */
    public function getHybridMatches(int $candidateId, int $ruleBasedLimit = 20, int $finalLimit = 3): Collection
    {
        // Step 1: Get rule-based preselectie
        $ruleBasedMatches = $this->getRuleBasedMatches($candidateId, $ruleBasedLimit);
        
        if ($ruleBasedMatches->isEmpty()) {
            return collect([]);
        }

        // Step 2: Try to get semantic matches for the same vacancies
        $vacancyIds = $ruleBasedMatches->pluck('id')->toArray();
        $semanticScores = [];
        
        if (config('database.default') === 'pgsql' && !empty($vacancyIds)) {
            try {
                $semanticQuery = "
                    SELECT 
                        v.id,
                        1 - (ve.embedding <=> ce.embedding) AS cosine_similarity
                    FROM vacancies v
                    INNER JOIN vacancy_embeddings ve ON ve.vacancy_id = v.id
                    INNER JOIN candidate_embeddings ce ON ce.candidate_id = ?
                    WHERE v.id = ANY(?)
                ";
                
                $semanticResults = DB::select($semanticQuery, [
                    $candidateId,
                    '{' . implode(',', $vacancyIds) . '}'
                ]);
                
                foreach ($semanticResults as $result) {
                    $semanticScores[$result->id] = $result->cosine_similarity;
                }
            } catch (\Exception $e) {
                // If embeddings don't exist, continue without semantic scores
            }
        }

        // Step 3: Combine scores in PHP
        $combined = $ruleBasedMatches->map(function ($match) use ($semanticScores) {
            $ruleScore = $match->total_score ?? 0;
            $semanticScore = $semanticScores[$match->id] ?? 0;
            
            // Normalize rule score (assuming max ~50-60) and semantic score (0-1)
            // Weight: 60% rule-based, 40% semantic
            $normalizedRuleScore = min($ruleScore / 50, 1.0); // Normalize to 0-1
            $combinedScore = ($normalizedRuleScore * 0.6) + ($semanticScore * 0.4);
            
            $match->rule_score = $ruleScore;
            $match->semantic_score = $semanticScore;
            $match->combined_score = $combinedScore;
            
            return $match;
        })->sortByDesc('combined_score')->take($finalLimit);

        return $combined->values();
    }


    /**
     * Simple matching for n8n: returns top matches with scores
     * 
     * @param int $candidateId
     * @param int $limit
     * @param bool $useSemantic
     * @return array
     */
    public function getMatchesForN8n(int $candidateId, int $limit = 3, bool $useSemantic = false): array
    {
        if ($useSemantic) {
            $matches = $this->getHybridMatches($candidateId, 20, $limit);
        } else {
            $matches = $this->getRuleBasedMatches($candidateId, $limit);
        }

        return $matches->map(function ($match) {
            return [
                'id' => $match->id,
                'title' => $match->title,
                'description' => $match->description,
                'location' => $match->location ?? $match->location_city,
                'work_mode' => $match->work_mode,
                'score' => $match->total_score ?? $match->combined_score ?? 0,
                'semantic_score' => $match->cosine_similarity ?? null,
                'rule_score' => $match->rule_score ?? $match->total_score ?? null,
            ];
        })->toArray();
    }
}

