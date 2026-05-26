<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MatchService;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MatchController extends Controller
{
    protected MatchService $matchService;

    public function __construct(MatchService $matchService)
    {
        $this->matchService = $matchService;
    }

    /**
     * Get matches for a candidate (for n8n)
     * 
     * POST /api/matches
     * Body: { "candidate_id": 1, "limit": 3, "use_semantic": false }
     */
    public function getMatches(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_id' => 'required|integer|exists:candidates,id',
            'limit' => 'sometimes|integer|min:1|max:20',
            'use_semantic' => 'sometimes|boolean',
        ]);

        $candidateId = $validated['candidate_id'];
        $limit = $validated['limit'] ?? 3;
        $useSemantic = $validated['use_semantic'] ?? false;

        try {
            $matches = $this->matchService->getMatchesForN8n($candidateId, $limit, $useSemantic);

            return response()->json([
                'success' => true,
                'candidate_id' => $candidateId,
                'matches' => $matches,
                'count' => count($matches),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get matches: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get rule-based matches only
     * 
     * GET /api/matches/rule-based/{candidateId}
     */
    public function getRuleBasedMatches(int $candidateId, Request $request): JsonResponse
    {
        $limit = $request->input('limit', 20);

        // Validate candidate exists
        if (!Candidate::find($candidateId)) {
            return response()->json([
                'success' => false,
                'error' => 'Candidate not found',
            ], 404);
        }

        try {
            $matches = $this->matchService->getRuleBasedMatches($candidateId, $limit);

            return response()->json([
                'success' => true,
                'candidate_id' => $candidateId,
                'matches' => $matches->map(function ($match) {
                    return [
                        'id' => $match->id,
                        'title' => $match->title,
                        'description' => $match->description,
                        'location' => $match->location ?? $match->location_city,
                        'work_mode' => $match->work_mode,
                        'score' => $match->total_score ?? 0,
                        'req_overlap' => $match->req_overlap ?? 0,
                        'tool_overlap' => $match->tool_overlap ?? 0,
                        'exp_score' => $match->exp_score ?? 0,
                        'work_mode_score' => $match->work_mode_score ?? 0,
                    ];
                })->toArray(),
                'count' => $matches->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get rule-based matches: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get semantic matches only (requires embeddings)
     * 
     * GET /api/matches/semantic/{candidateId}
     */
    public function getSemanticMatches(int $candidateId, Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        // Validate candidate exists
        if (!Candidate::find($candidateId)) {
            return response()->json([
                'success' => false,
                'error' => 'Candidate not found',
            ], 404);
        }

        try {
            $matches = $this->matchService->getSemanticMatches($candidateId, $limit);

            if ($matches->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No embeddings found. Please ensure candidate and vacancy embeddings are generated.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'candidate_id' => $candidateId,
                'matches' => $matches->map(function ($match) {
                    return [
                        'id' => $match->id,
                        'title' => $match->title,
                        'description' => $match->description,
                        'location' => $match->location ?? $match->location_city,
                        'work_mode' => $match->work_mode,
                        'cosine_similarity' => $match->cosine_similarity ?? 0,
                    ];
                })->toArray(),
                'count' => $matches->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get semantic matches: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get hybrid matches (rule-based + semantic)
     * 
     * GET /api/matches/hybrid/{candidateId}
     */
    public function getHybridMatches(int $candidateId, Request $request): JsonResponse
    {
        $ruleBasedLimit = $request->input('rule_based_limit', 20);
        $finalLimit = $request->input('final_limit', 3);

        // Validate candidate exists
        if (!Candidate::find($candidateId)) {
            return response()->json([
                'success' => false,
                'error' => 'Candidate not found',
            ], 404);
        }

        try {
            $matches = $this->matchService->getHybridMatches($candidateId, $ruleBasedLimit, $finalLimit);

            return response()->json([
                'success' => true,
                'candidate_id' => $candidateId,
                'matches' => $matches->map(function ($match) {
                    return [
                        'id' => $match->id,
                        'title' => $match->title,
                        'description' => $match->description,
                        'location' => $match->location ?? $match->location_city,
                        'work_mode' => $match->work_mode,
                        'combined_score' => $match->combined_score ?? 0,
                        'rule_score' => $match->rule_score ?? 0,
                        'semantic_score' => $match->semantic_score ?? 0,
                    ];
                })->toArray(),
                'count' => $matches->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get hybrid matches: ' . $e->getMessage(),
            ], 500);
        }
    }
}
