<?php

namespace App\Console\Commands;

use App\Services\PromoVideoService;
use Illuminate\Console\Command;

class PromoVideosRenderCommand extends Command
{
    protected $signature = 'promo-videos:render
        {--composition=NexaProductPromo : Composition ID}
        {--props= : Path to props JSON (default: promo-videos/props.json or built-in defaults)}
        {--filename= : Output filename under storage/app/public/promo-videos}';

    protected $description = 'Render a Nexa Remotion promo video via the promo-videos package';

    public function handle(PromoVideoService $promoVideos): int
    {
        $composition = (string) $this->option('composition');
        if (! isset(PromoVideoService::COMPOSITIONS[$composition])) {
            $this->error('Onbekende composition. Kies: '.implode(', ', array_keys(PromoVideoService::COMPOSITIONS)));

            return self::FAILURE;
        }

        $props = $promoVideos->defaultProps();
        $propsPath = $this->option('props');
        if (is_string($propsPath) && $propsPath !== '') {
            if (! is_file($propsPath)) {
                $this->error('Props-bestand niet gevonden: '.$propsPath);

                return self::FAILURE;
            }
            $decoded = json_decode((string) file_get_contents($propsPath), true);
            if (! is_array($decoded)) {
                $this->error('Ongeldige JSON in props-bestand.');

                return self::FAILURE;
            }
            $props = array_merge($props, $decoded);
        } elseif (is_file($promoVideos->projectPath().'/props.json')) {
            $decoded = json_decode((string) file_get_contents($promoVideos->projectPath().'/props.json'), true);
            if (is_array($decoded)) {
                $props = array_merge($props, $decoded);
            }
        }

        $this->info("Rendering {$composition}…");

        try {
            $result = $promoVideos->render(
                $composition,
                $props,
                $this->option('filename') ?: null
            );
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $result['ok']) {
            $this->error('Render mislukt.');
            if ($result['output'] !== '') {
                $this->line($result['output']);
            }

            return self::FAILURE;
        }

        $this->info('Klaar: storage/app/public/'.$result['out_file']);
        $this->line('URL: '.asset('storage/'.$result['out_file']));

        return self::SUCCESS;
    }
}
