<?php

use App\Http\Controllers\Api\AiChatRagController;
use App\Http\Controllers\Api\AiChatSqlController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| n8n / machine-to-machine (geen web-sessie, geen tenant-host middleware)
|--------------------------------------------------------------------------
*/

Route::get('/integrations/n8n/ai-chat/live-query/health', function () {
    return response()->json([
        'status' => 'ok',
        'endpoint' => 'POST /integrations/n8n/ai-chat/live-query',
    ]);
})->name('integrations.n8n.ai-chat.live-query.health');

Route::post('/integrations/n8n/ai-chat/live-query', [AiChatSqlController::class, 'execute'])
    ->middleware('throttle:60,1')
    ->name('integrations.n8n.ai-chat.live-query');

Route::get('/integrations/n8n/ai-chat/rag-search/health', function () {
    return response()->json([
        'status' => 'ok',
        'endpoint' => 'POST /integrations/n8n/ai-chat/rag-search',
    ]);
})->name('integrations.n8n.ai-chat.rag-search.health');

Route::post('/integrations/n8n/ai-chat/rag-search', [AiChatRagController::class, 'search'])
    ->middleware('throttle:60,1')
    ->name('integrations.n8n.ai-chat.rag-search');
