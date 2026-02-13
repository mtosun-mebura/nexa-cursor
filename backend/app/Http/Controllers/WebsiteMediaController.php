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

        return response($content, 200, [
            'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            'Content-Length' => strlen($content),
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
