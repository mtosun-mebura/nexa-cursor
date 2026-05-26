<?php

namespace App\Http\Controllers;

use App\Models\WebsiteMedia;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class WebsiteMediaController extends Controller
{
    private const DISK = 'local';

    /**
     * Serve encrypted website media by uuid (decrypt on the fly).
     * Publiek bereikbaar zodat frontend-pagina's afbeeldingen kunnen tonen.
     */
    public function serve(string $uuid): Response
    {
        $media = WebsiteMedia::where('uuid', $uuid)->first();

        if (!$media || !$media->encrypted_path) {
            abort(404);
        }

        $path = $media->encrypted_path;
        if (!Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        $encrypted = Storage::disk(self::DISK)->get($path);
        try {
            $content = Crypt::decrypt($encrypted);
        } catch (\Throwable) {
            abort(404);
        }

        $mimeType = $this->resolveMimeType($media->mime_type, $media->original_filename, $content);

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => strlen($content),
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function resolveMimeType(?string $storedMime, ?string $originalFilename, string $content): string
    {
        $storedMime = trim((string) $storedMime);
        if ($storedMime !== '' && $storedMime !== 'application/octet-stream' && str_starts_with($storedMime, 'image/')) {
            return $storedMime;
        }

        if (str_starts_with($content, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($content, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }
        if (str_starts_with($content, 'GIF87a') || str_starts_with($content, 'GIF89a')) {
            return 'image/gif';
        }
        if (strlen($content) >= 12 && substr($content, 0, 4) === 'RIFF' && substr($content, 8, 4) === 'WEBP') {
            return 'image/webp';
        }

        $name = strtolower((string) $originalFilename);
        if (preg_match('/\.jpe?g$/', $name)) {
            return 'image/jpeg';
        }
        if (str_ends_with($name, '.png')) {
            return 'image/png';
        }
        if (str_ends_with($name, '.gif')) {
            return 'image/gif';
        }
        if (str_ends_with($name, '.webp')) {
            return 'image/webp';
        }

        return $storedMime !== '' ? $storedMime : 'application/octet-stream';
    }
}
