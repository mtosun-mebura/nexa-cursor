<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Maakt van een voertuigfoto een PNG met transparante achtergrond.
 *
 * Volgorde (driver=auto):
 * 1. rembg HTTP (REMBG_URL / Docker-sidecar)
 * 2. rembg CLI (REMBG_BIN)
 * 3. rembg Docker one-shot (REMBG_DOCKER_IMAGE)
 * 4. remove.bg API (REMOVEBG_API_KEY) als fallback
 */
class VehiclePhotoCutoutService
{
    /**
     * @return array{file: UploadedFile, cutout: bool, driver: ?string}
     */
    public function process(UploadedFile $file): array
    {
        $driver = strtolower(trim((string) config('services.vehicle_cutout.driver', 'auto')));
        if ($driver === 'off' || $driver === 'none') {
            return ['file' => $file, 'cutout' => false, 'driver' => null];
        }

        $realPath = $file->getRealPath();
        if (! is_string($realPath) || $realPath === '' || ! is_readable($realPath)) {
            return ['file' => $file, 'cutout' => false, 'driver' => null];
        }

        $attempts = match ($driver) {
            'rembg' => ['rembg'],
            'removebg' => ['removebg'],
            default => ['rembg', 'removebg'],
        };

        foreach ($attempts as $attempt) {
            $result = $attempt === 'rembg'
                ? $this->viaRembg($file, $realPath)
                : $this->viaRemoveBg($file, $realPath);

            if ($result !== null) {
                return $result;
            }
        }

        return ['file' => $file, 'cutout' => false, 'driver' => null];
    }

    public function enabled(): bool
    {
        $driver = strtolower(trim((string) config('services.vehicle_cutout.driver', 'auto')));
        if ($driver === 'off' || $driver === 'none') {
            return false;
        }

        if ($driver === 'removebg') {
            return $this->removeBgKey() !== '';
        }

        return $this->rembgUrl() !== ''
            || $this->rembgBinAvailable()
            || $this->rembgDockerEnabled()
            || ($driver === 'auto' && $this->removeBgKey() !== '');
    }

    /**
     * @return array{file: UploadedFile, cutout: bool, driver: string}|null
     */
    protected function viaRembg(UploadedFile $file, string $realPath): ?array
    {
        $png = $this->viaRembgHttp($realPath)
            ?? $this->viaRembgCli($realPath)
            ?? $this->viaRembgDocker($realPath);

        if ($png === null) {
            return null;
        }

        return [
            'file' => $this->toUploadedPng($file, $png),
            'cutout' => true,
            'driver' => 'rembg',
        ];
    }

    protected function viaRembgHttp(string $realPath): ?string
    {
        $url = $this->rembgUrl();
        if ($url === '') {
            return null;
        }

        $endpoint = rtrim($url, '/').'/api/remove';
        $model = $this->rembgModel();

        try {
            $response = Http::timeout((int) config('services.vehicle_cutout.rembg.timeout', 90))
                ->attach('file', fopen($realPath, 'r'), basename($realPath))
                ->post($endpoint, [
                    'model' => $model,
                ]);

            if (! $response->successful()) {
                Log::warning('rembg HTTP cutout failed', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 300),
                ]);

                return null;
            }

            return $this->writeTempPng($response->body());
        } catch (\Throwable $e) {
            Log::warning('rembg HTTP cutout exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    protected function viaRembgCli(string $realPath): ?string
    {
        if (! $this->rembgBinAvailable()) {
            return null;
        }

        $bin = (string) config('services.vehicle_cutout.rembg.bin', 'rembg');
        $model = $this->rembgModel();
        $out = $this->tempPngPath();
        if ($out === null) {
            return null;
        }

        try {
            $result = Process::timeout((int) config('services.vehicle_cutout.rembg.timeout', 90))
                ->run([$bin, 'i', '-m', $model, $realPath, $out]);

            if (! $result->successful() || ! is_readable($out) || filesize($out) < 32) {
                Log::warning('rembg CLI cutout failed', [
                    'exit' => $result->exitCode(),
                    'err' => Str::limit($result->errorOutput(), 300),
                ]);
                @unlink($out);

                return null;
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('rembg CLI cutout exception', ['message' => $e->getMessage()]);
            @unlink($out);

            return null;
        }
    }

    protected function viaRembgDocker(string $realPath): ?string
    {
        if (! $this->rembgDockerEnabled()) {
            return null;
        }

        $image = (string) config('services.vehicle_cutout.rembg.docker_image', 'danielgatis/rembg');
        $model = $this->rembgModel();
        $workDir = sys_get_temp_dir().'/nexa-rembg-'.Str::random(8);
        if (! @mkdir($workDir, 0700, true) && ! is_dir($workDir)) {
            return null;
        }

        $inName = 'input'.(pathinfo($realPath, PATHINFO_EXTENSION) ? '.'.pathinfo($realPath, PATHINFO_EXTENSION) : '.jpg');
        $outName = 'output.png';
        $inPath = $workDir.'/'.$inName;
        $outPath = $workDir.'/'.$outName;

        try {
            if (! @copy($realPath, $inPath)) {
                return null;
            }

            $result = Process::timeout((int) config('services.vehicle_cutout.rembg.timeout', 180))
                ->run([
                    'docker', 'run', '--rm',
                    '-v', $workDir.':/data',
                    $image,
                    'i', '-m', $model,
                    '/data/'.$inName,
                    '/data/'.$outName,
                ]);

            if (! $result->successful() || ! is_readable($outPath) || filesize($outPath) < 32) {
                Log::warning('rembg Docker cutout failed', [
                    'exit' => $result->exitCode(),
                    'err' => Str::limit($result->errorOutput(), 300),
                ]);

                return null;
            }

            $final = $this->tempPngPath();
            if ($final === null || ! @rename($outPath, $final)) {
                $final = $this->writeTempPng((string) file_get_contents($outPath));
            }

            return $final;
        } catch (\Throwable $e) {
            Log::warning('rembg Docker cutout exception', ['message' => $e->getMessage()]);

            return null;
        } finally {
            @unlink($inPath);
            @unlink($outPath);
            @rmdir($workDir);
        }
    }

    /**
     * @return array{file: UploadedFile, cutout: bool, driver: string}|null
     */
    protected function viaRemoveBg(UploadedFile $file, string $realPath): ?array
    {
        $key = $this->removeBgKey();
        if ($key === '') {
            return null;
        }

        try {
            $response = Http::timeout(45)
                ->withHeaders(['X-Api-Key' => $key])
                ->attach(
                    'image_file',
                    fopen($realPath, 'r'),
                    $file->getClientOriginalName() ?: 'vehicle.jpg'
                )
                ->post('https://api.remove.bg/v1.0/removebg', [
                    'size' => 'auto',
                    'format' => 'png',
                    'type' => 'car',
                    'crop' => 'true',
                    'add_shadow' => 'true',
                ]);

            if (! $response->successful()) {
                Log::warning('remove.bg cutout failed', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 300),
                ]);

                return null;
            }

            $png = $this->writeTempPng($response->body());
            if ($png === null) {
                return null;
            }

            return [
                'file' => $this->toUploadedPng($file, $png),
                'cutout' => true,
                'driver' => 'removebg',
            ];
        } catch (\Throwable $e) {
            Log::warning('remove.bg cutout exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    protected function toUploadedPng(UploadedFile $original, string $pngPath): UploadedFile
    {
        $base = pathinfo($original->getClientOriginalName() ?: 'vehicle', PATHINFO_FILENAME);

        return new UploadedFile(
            $pngPath,
            $base.'-cutout.png',
            'image/png',
            null,
            true
        );
    }

    protected function writeTempPng(string $binary): ?string
    {
        if ($binary === '') {
            return null;
        }
        $path = $this->tempPngPath();
        if ($path === null) {
            return null;
        }
        if (file_put_contents($path, $binary) === false) {
            @unlink($path);

            return null;
        }

        return $path;
    }

    protected function tempPngPath(): ?string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'vehcut_');
        if ($tmp === false) {
            return null;
        }
        $png = $tmp.'.png';
        @unlink($tmp);

        return $png;
    }

    protected function rembgUrl(): string
    {
        return rtrim(trim((string) config('services.vehicle_cutout.rembg.url', '')), '/');
    }

    protected function rembgModel(): string
    {
        $model = trim((string) config('services.vehicle_cutout.rembg.model', 'u2net'));

        return $model !== '' ? $model : 'u2net';
    }

    protected function rembgBinAvailable(): bool
    {
        $bin = trim((string) config('services.vehicle_cutout.rembg.bin', 'rembg'));
        if ($bin === '') {
            return false;
        }
        if (str_contains($bin, '/') || str_contains($bin, '\\')) {
            return is_executable($bin);
        }

        try {
            $result = Process::timeout(5)->run(['which', $bin]);

            return $result->successful() && trim($result->output()) !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    protected function rembgDockerEnabled(): bool
    {
        if (! (bool) config('services.vehicle_cutout.rembg.docker', false)) {
            return false;
        }

        try {
            $result = Process::timeout(5)->run(['docker', 'info']);

            return $result->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    protected function removeBgKey(): string
    {
        return trim((string) config('services.vehicle_cutout.removebg.key', config('services.removebg.key', '')));
    }
}
