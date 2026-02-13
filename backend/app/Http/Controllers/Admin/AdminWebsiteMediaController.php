<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminWebsiteMediaController extends Controller
{
    private const DISK = 'local';
    private const MEDIA_DIR = 'website_media';

    /** Max breedte voor carousel (snel laden, goede kwaliteit). */
    private const MAX_WIDTH = 1920;

    /** JPEG-kwaliteit na compressie (0–100). */
    private const JPEG_QUALITY = 85;

    /**
     * Upload een afbeelding; wordt gecomprimeerd en encrypted opgeslagen. Alleen Super Admin.
     */
    public function upload(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin();

        $request->validate([
            'file' => 'required|file|image|max:5120', // 5MB (in KB)
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $content = $this->resizeAndCompressImage($path, $file->getMimeType());
        $wasCompressed = $content !== null;

        if ($content === null) {
            $content = file_get_contents($path);
        }

        $encrypted = Crypt::encrypt($content);
        $uuid = (string) Str::uuid();
        $encryptedPath = self::MEDIA_DIR . '/' . $uuid . '.enc';

        $media = WebsiteMedia::create([
            'uuid' => $uuid,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $wasCompressed ? 'image/jpeg' : $file->getMimeType(),
            'encrypted_path' => $encryptedPath,
            'size' => \strlen($content),
        ]);

        Storage::disk(self::DISK)->put($encryptedPath, $encrypted);

        $serveUrl = route('website-media.serve', ['uuid' => $media->uuid]);

        return response()->json([
            'uuid' => $media->uuid,
            'url' => $serveUrl,
            'original_filename' => $media->original_filename,
        ]);
    }

    /**
     * Verklein en comprimeer afbeelding voor carousel (snel laden, goede kwaliteit).
     * Retourneert JPEG-binary of null bij falen (gebruik dan origineel).
     */
    private function resizeAndCompressImage(string $path, string $mimeType): ?string
    {
        if (!\function_exists('imagecreatefromstring')) {
            return null;
        }

        $blob = @file_get_contents($path);
        if ($blob === false) {
            return null;
        }

        $img = @imagecreatefromstring($blob);
        if ($img === false) {
            return null;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        if ($w < 1 || $h < 1) {
            imagedestroy($img);
            return null;
        }

        $maxW = self::MAX_WIDTH;
        $maxH = (int) round($maxW * 9 / 16); // carousel-achtige verhouding

        if ($w <= $maxW && $h <= $maxH) {
            $newW = $w;
            $newH = $h;
            $dst = $img;
        } else {
            $ratio = min($maxW / $w, $maxH / $h, 1.0);
            $newW = (int) round($w * $ratio);
            $newH = (int) round($h * $ratio);
            if ($newW < 1) {
                $newW = 1;
            }
            if ($newH < 1) {
                $newH = 1;
            }
            $dst = imagecreatetruecolor($newW, $newH);
            if ($dst === false) {
                imagedestroy($img);
                return null;
            }
            if (!imagecopyresampled($dst, $img, 0, 0, 0, 0, $newW, $newH, $w, $h)) {
                imagedestroy($img);
                imagedestroy($dst);
                return null;
            }
            imagedestroy($img);
        }

        ob_start();
        $result = imagejpeg($dst, null, self::JPEG_QUALITY);
        $content = ob_get_clean();
        if (isset($dst) && $dst !== $img) {
            imagedestroy($dst);
        }

        return $result && $content !== false ? $content : null;
    }

    protected function ensureSuperAdmin(): void
    {
        if (!auth()->check() || !auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admins hebben toegang tot website-media upload.');
        }
    }
}
