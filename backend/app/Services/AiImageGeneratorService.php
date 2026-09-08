<?php

namespace App\Services;

use App\Models\AiGeneratedImage;
use App\Models\WebsiteMedia;
use Illuminate\Http\Client\Response;
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
     * Optioneel: een bestaand galerijplaatje als visuele bron (logo/stijl) meenemen.
     *
     * @throws RuntimeException als OpenAI niet geconfigureerd is of de aanroep mislukt.
     */
    public function generate(string $prompt, ?int $userId = null, ?AiGeneratedImage $source = null): AiGeneratedImage
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

        try {
            $binary = $source !== null
                ? $this->requestEdit($apiKey, $model, $prompt, $source)
                : $this->requestGeneration($apiKey, $model, $prompt);

            return $this->storeGeneratedImage($binary, $prompt, $userId);
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

    private function requestGeneration(string $apiKey, string $model, string $prompt): string
    {
        $payload = [
            'model' => $model,
            'prompt' => mb_substr($prompt, 0, 3500),
            'n' => 1,
            'size' => (string) config('services.openai.image_size', '1024x1024'),
        ];
        if ($model === 'dall-e-3' || str_starts_with($model, 'gpt-image')) {
            $payload['quality'] = (string) config('services.openai.image_quality', 'high');
        }

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/images/generations', $payload);

        return $this->binaryFromOpenAiResponse($response, 'genereren');
    }

    private function requestEdit(string $apiKey, string $model, string $prompt, AiGeneratedImage $source): string
    {
        if (! str_starts_with($model, 'gpt-image') && $model !== 'dall-e-2') {
            throw new RuntimeException('Aanpassen van een bestaand plaatje vereist gpt-image-1.');
        }

        $binary = $this->decryptSourceBinary($source);
        $media = WebsiteMedia::query()->where('uuid', $source->website_media_uuid)->first();
        $filename = $media?->original_filename ?: 'source.png';
        $mime = is_string($media?->mime_type) && str_starts_with($media->mime_type, 'image/')
            ? $media->mime_type
            : 'image/png';

        $fields = [
            'model' => $model,
            'prompt' => mb_substr($prompt, 0, 3500),
            'n' => '1',
            'size' => (string) config('services.openai.image_size', '1024x1024'),
        ];
        if (str_starts_with($model, 'gpt-image')) {
            $fields['quality'] = (string) config('services.openai.image_quality', 'high');
        }

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->attach('image', $binary, $filename, ['Content-Type' => $mime])
            ->post('https://api.openai.com/v1/images/edits', $fields);

        return $this->binaryFromOpenAiResponse($response, 'aanpassen');
    }

    private function decryptSourceBinary(AiGeneratedImage $source): string
    {
        $media = WebsiteMedia::query()->where('uuid', $source->website_media_uuid)->first();
        if (! $media || ! $media->encrypted_path || ! Storage::disk('local')->exists($media->encrypted_path)) {
            throw new RuntimeException('Het bronplaatje is niet meer beschikbaar.');
        }

        try {
            $decrypted = Crypt::decrypt(Storage::disk('local')->get($media->encrypted_path));
        } catch (Throwable) {
            throw new RuntimeException('Het bronplaatje kon niet worden gelezen.');
        }

        if (! is_string($decrypted) || $decrypted === '') {
            throw new RuntimeException('Het bronplaatje kon niet worden gelezen.');
        }

        return $decrypted;
    }

    private function binaryFromOpenAiResponse(Response $response, string $action): string
    {
        if (! $response->successful()) {
            Log::warning('AI image generator: OpenAI HTTP-fout', [
                'action' => $action,
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 500),
            ]);
            throw new RuntimeException('OpenAI kon geen afbeelding '.$action.' (HTTP '.$response->status().').');
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

        return $binary;
    }

    private function storeGeneratedImage(string $binary, string $prompt, ?int $userId): AiGeneratedImage
    {
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
    }
}
