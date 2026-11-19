<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PromoVideoService
{
    public const COMPOSITIONS = [
        'NexaProductPromo' => [
            'label' => 'Product promo (45s, landscape)',
            'width' => 1920,
            'height' => 1080,
            'duration_seconds' => 45,
        ],
        'NexaAppTeaser' => [
            'label' => 'App teaser (15s, verticaal)',
            'width' => 1080,
            'height' => 1920,
            'duration_seconds' => 15,
        ],
    ];

    public function projectPath(): string
    {
        return dirname(base_path()).DIRECTORY_SEPARATOR.'promo-videos';
    }

    public function defaultProps(): array
    {
        return [
            'companyName' => 'NEXA Suite',
            'productName' => 'NexaTaxi',
            'tagline' => 'Online boeken, dispatch en chauffeur-app in één platform',
            'subtitle' => 'White-label taxi SaaS voor ZZP tot vloot',
            'primaryColor' => '#2563eb',
            'accentColor' => '#f97316',
            'backgroundColor' => '#0b1220',
            'ctaText' => 'Start met NexaTaxi',
            'websiteUrl' => 'nexasuite.nl',
            'logoUrl' => 'images/nexa-taxi-logo.png',
            'showAppScreens' => true,
        ];
    }

    public function playerUrl(array $props = [], string $composition = 'promo'): string
    {
        $built = public_path('promo-videos-player/index.html');
        if (! is_file($built)) {
            return '';
        }

        $query = http_build_query([
            'composition' => $composition === 'NexaAppTeaser' || $composition === 'teaser' ? 'teaser' : 'promo',
            'companyName' => $props['companyName'] ?? null,
            'productName' => $props['productName'] ?? null,
            'tagline' => $props['tagline'] ?? null,
            'subtitle' => $props['subtitle'] ?? null,
            'primaryColor' => $props['primaryColor'] ?? null,
            'accentColor' => $props['accentColor'] ?? null,
            'backgroundColor' => $props['backgroundColor'] ?? null,
            'ctaText' => $props['ctaText'] ?? null,
            'websiteUrl' => $props['websiteUrl'] ?? null,
            'logoUrl' => $props['logoUrl'] ?? null,
            'showAppScreens' => ! empty($props['showAppScreens']) ? '1' : '0',
        ]);

        return url('/promo-videos-player/index.html').'?'.$query;
    }

    public function isProjectInstalled(): bool
    {
        $root = $this->projectPath();

        return is_dir($root)
            && is_file($root.'/package.json')
            && is_dir($root.'/node_modules/remotion');
    }

    public function isPlayerBuilt(): bool
    {
        return is_file(public_path('promo-videos-player/index.html'));
    }

    /**
     * @return list<array{name: string, path: string, url: string, size: int, modified_at: string}>
     */
    public function listRenderedVideos(): array
    {
        $disk = Storage::disk('public');
        $dir = 'promo-videos';
        if (! $disk->exists($dir)) {
            return [];
        }

        $items = [];
        foreach ($disk->files($dir) as $file) {
            if (! str_ends_with(strtolower($file), '.mp4')) {
                continue;
            }
            $items[] = [
                'name' => basename($file),
                'path' => $file,
                'url' => $disk->url($file),
                'size' => (int) $disk->size($file),
                'modified_at' => date('Y-m-d H:i', $disk->lastModified($file)),
            ];
        }

        usort($items, fn ($a, $b) => strcmp($b['modified_at'], $a['modified_at']));

        return $items;
    }

    public function writeProps(array $props): string
    {
        $root = $this->projectPath();
        if (! is_dir($root)) {
            throw new RuntimeException('Promo-videos project ontbreekt (promo-videos/).');
        }

        $path = $root.'/props.json';
        File::put($path, json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * @return array{ok: bool, output: string, out_file: string|null}
     */
    public function render(string $composition, array $props, ?string $filename = null): array
    {
        if (! isset(self::COMPOSITIONS[$composition])) {
            throw new RuntimeException('Onbekende composition: '.$composition);
        }

        if (! $this->isProjectInstalled()) {
            throw new RuntimeException(
                'Remotion is niet geïnstalleerd. Voer uit: cd promo-videos && npm install'
            );
        }

        $propsPath = $this->writeProps($props);
        $slug = Str::slug($props['companyName'] ?? 'nexa').'-'.Str::lower($composition).'-'.now()->format('Ymd-His');
        $filename = $filename ?: ($slug.'.mp4');
        $outLocal = $this->projectPath().'/out/'.$filename;

        File::ensureDirectoryExists(dirname($outLocal));

        $result = Process::path($this->projectPath())
            ->timeout(900)
            ->run([
                'npx',
                'remotion',
                'render',
                'src/index.ts',
                $composition,
                $outLocal,
                '--props='.$propsPath,
            ]);

        $output = trim($result->output()."\n".$result->errorOutput());

        if (! $result->successful() || ! is_file($outLocal)) {
            return [
                'ok' => false,
                'output' => $output !== '' ? $output : 'Render mislukt zonder output.',
                'out_file' => null,
            ];
        }

        $publicRelative = 'promo-videos/'.$filename;
        Storage::disk('public')->put($publicRelative, File::get($outLocal));

        return [
            'ok' => true,
            'output' => $output,
            'out_file' => $publicRelative,
        ];
    }

    public function storeLogoUpload(\Illuminate\Http\UploadedFile $file): string
    {
        $path = $file->store('promo-videos/logos', 'public');

        return Storage::disk('public')->url($path);
    }
}
