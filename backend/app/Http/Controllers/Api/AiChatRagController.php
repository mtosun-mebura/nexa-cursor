<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiChat\AiChatRagSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RAG-zoekgateway voor n8n (schema-aware module connection).
 */
class AiChatRagController extends Controller
{
    public function search(Request $request, AiChatRagSearchService $ragSearchService): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:200',
            'message' => 'nullable|string|max:4000',
            'module' => 'nullable|string|max:50',
        ]);

        $module = strtolower(trim((string) ($validated['module'] ?? 'taxi')));
        $message = isset($validated['message']) ? (string) $validated['message'] : null;
        $keyword = trim((string) $validated['keyword']);

        $rows = $ragSearchService->search($keyword, $message, $module);

        return response()->json([
            'success' => true,
            'source' => 'rag',
            'count' => count($rows),
            'rows' => $rows,
            'answer' => $ragSearchService->formatAnswer($rows),
        ]);
    }
}
