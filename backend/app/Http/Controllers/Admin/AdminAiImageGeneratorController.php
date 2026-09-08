<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGeneratedImage;
use App\Services\AiImageGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class AdminAiImageGeneratorController extends Controller
{
    public function __construct(
        protected AiImageGeneratorService $service
    ) {}

    public function index(): View
    {
        $images = AiGeneratedImage::query()
            ->latest()
            ->paginate(24);

        return view('admin.ai-images.index', [
            'images' => $images,
            'openAiConfigured' => is_string(config('services.openai.api_key')) && trim((string) config('services.openai.api_key')) !== '',
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:3500'],
            'source_uuid' => ['nullable', 'uuid', 'exists:ai_generated_images,website_media_uuid'],
        ]);

        @set_time_limit(180);

        $source = null;
        if (! empty($validated['source_uuid'])) {
            $source = AiGeneratedImage::query()
                ->where('website_media_uuid', $validated['source_uuid'])
                ->first();
        }

        try {
            $image = $this->service->generate($validated['prompt'], $request->user()?->id, $source);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'id' => $image->id,
            'uuid' => $image->website_media_uuid,
            'url' => '/website-media/'.$image->website_media_uuid,
            'prompt' => $image->prompt,
            'created_at' => $image->created_at?->toIso8601String(),
        ]);
    }

    public function destroy(AiGeneratedImage $aiGeneratedImage): JsonResponse
    {
        $this->service->delete($aiGeneratedImage);

        return response()->json(['success' => true]);
    }
}
