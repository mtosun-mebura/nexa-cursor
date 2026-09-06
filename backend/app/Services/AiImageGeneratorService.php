<?php

namespace App\Services;

use App\Models\AiGeneratedImage;
use App\Models\WebsiteMedia;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiImageGeneratorService
{
    /**
     * Genereer een afbeelding via OpenAI en sla 'm versleuteld op, gekoppeld aan de prompt.
     *
     * @throws RuntimeException als OpenAI niet geconfigureerd is of de aanroep mislukt.
     */
    public function generate(string $prompt, ?int $userId = null): AiGeneratedImage
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new RuntimeException('Geef een omschrijving van de gewenste afbeelding.');
        }

        $apiKey = config('services.openai.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('OpenAI is niet geconfigureerd (OPENAI_API_KEY ontbreekt).');
        }

        $model = (string) config('services.openai.image_model', 'gpt-image-1');
        $payload = [
            'model' => $model,
            'prompt' => mb_substr($prompt, 0, 3500),
            'n' => 1,
            'size' => (string) config('services.openai.image_size', '1024x1024'),
        ];
        if ($model === 'dall-e-3' || str_starts_with($model, 'gpt-image')) {
            $payload['quality'] = (string) config('services.openai.image_quality', 'high');
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(120)
                ->post('https://api.openai.com/v1/images/generations', $payload);

            if (! $response->successful()) {
                Log::warning('AI image generator: OpenAI HTTP-fout', [
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 500),
                ]);
                throw new RuntimeException('OpenAI kon geen afbeelding genereren (HTTP '.$response->status().').');
            }

            $b64 = $response->json('data.0.b64_json');
            $remoteUrl = $response->json('data.0.url');
            $binary = null;
            if (is_string($b64) && $b64 !== '') {
                $decoded = base64_decode($b64, true);
                $binary = $decoded !== false ? $decoded : null;
            } elseif (is_string($remoteUrl) && $remoteUrl !== '') {
                $download = Http::timeout(60)->get($remoteUrl);
                if ($download->successful()) {
                    $binary = $download->body();
                }
            }

            if ($binary === null || $binary === '') {
                throw new RuntimeException('OpenAI gaf geen bruikbare afbeelding terug.');
            }

            $uuid = (string) Str::uuid();
            $encryptedPath = 'website_media/'.$uuid.'.enc';

            $media = WebsiteMedia::query()->create([
                'uuid' => $uuid,
                'original_filename' => 'ai-'.Str::slug(mb_substr($prompt, 0, 40)).'.png',
                'mime_type' => 'image/png',
                'encrypted_path' => $encryptedPath,
                'size' => strlen($binary),
            ]);
            Storage::disk('local')->put($encryptedPath, Crypt::encrypt($binary));

            return AiGeneratedImage::query()->create([
                'website_media_uuid' => $media->uuid,
                'prompt' => $prompt,
                'created_by' => $userId,
            ]);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning('AI image generator: uitzondering', ['error' => $e->getMessage()]);
            throw new RuntimeException('Er ging iets mis bij het genereren van de afbeelding.');
        }
    }

    /**
     * Verwijder een gegenereerde afbeelding: rij + gekoppelde media + versleuteld bestand.
     */
    public function delete(AiGeneratedImage $image): void
    {
        $media = WebsiteMedia::where('uuid', $image->website_media_uuid)->first();
        if ($media) {
            if ($media->encrypted_path && Storage::disk('local')->exists($media->encrypted_path)) {
                Storage::disk('local')->delete($media->encrypted_path);
            }
            $media->delete();
        }
        $image->delete();
    }
}
