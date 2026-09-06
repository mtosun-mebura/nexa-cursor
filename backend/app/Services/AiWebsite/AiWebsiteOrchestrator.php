<?php

namespace App\Services\AiWebsite;

use App\Models\AiWebsiteGeneration;
use App\Models\Company;
use App\Models\FrontendTheme;
use App\Services\AiWebsite\Planning\AiSitemapPlanner;
use App\Services\AiWebsite\Planning\AiWebsiteBriefService;
use App\Services\WebsiteAiGeneratorService;
use App\Services\WebsiteAiSourceReader;
use Throwable;

class AiWebsiteOrchestrator
{
    public function __construct(
        protected WebsiteAiSourceReader $sourceReader,
        protected AiWebsiteBriefService $briefService,
        protected AiSitemapPlanner $sitemapPlanner,
        protected WebsiteAiGeneratorService $generator,
    ) {}

    /**
     * Brief + sitemap, daarna de gevraagde pagina’s als concept (home, over-ons, contact, …).
     *
     * @param  array<string, mixed>  $input
     * @return array{created: list<array{id: int, slug: string, title: string}>, updated: list<array{id: int, slug: string, title: string}>, skipped: list<string>, images: int, used_openai: bool, source_pages: int, homepage_page_id: int|null, generation_id: int}
     */
    public function run(array $input): array
    {
        $company = Company::query()->findOrFail((int) $input['company_id']);
        $theme = FrontendTheme::query()->findOrFail((int) $input['frontend_theme_id']);
        $sourceUrl = trim((string) ($input['source_url'] ?? ''));
        $sourceType = (($input['source_type'] ?? '') === 'url' || $sourceUrl !== '') ? 'url' : 'new';
        $userId = (int) ($input['user_id'] ?? 0);

        $generation = AiWebsiteGeneration::query()->create([
            'company_id' => $company->id,
            'user_id' => $userId > 0 ? $userId : null,
            'status' => AiWebsiteGeneration::STATUS_CONTEXT,
            'current_step' => AiWebsiteGeneration::STATUS_CONTEXT,
            'source_type' => $sourceType,
            'source_url' => $sourceUrl !== '' ? $sourceUrl : null,
            'source_context' => trim((string) ($input['context'] ?? '')),
            'generation_settings_json' => [
                'max_pages' => (int) ($input['max_pages'] ?? 3),
                'frontend_theme_id' => $theme->id,
                'primary_color' => (string) ($input['primary_color'] ?? ''),
                'secondary_color' => (string) ($input['secondary_color'] ?? ''),
                'generate_images' => (bool) ($input['generate_images'] ?? false),
                'replace_existing' => (bool) ($input['replace_existing'] ?? false),
                'goals' => $input['goals'] ?? [],
                'style' => (string) ($input['style'] ?? ''),
                'tone' => (string) ($input['tone'] ?? ''),
                'phase' => 1,
            ],
            'prompt_versions' => implode(',', array_values(config('ai_website.prompt_versions', []))),
            'started_at' => now(),
        ]);

        try {
            $generation->markStep(AiWebsiteGeneration::STATUS_ANALYSIS);
            $source = $sourceType === 'url' ? $this->sourceReader->read($sourceUrl) : ['url' => '', 'pages' => [], 'summary' => ''];
            $generation->source_extract_json = [
                'url' => $source['url'] ?? '',
                'summary' => $source['summary'] ?? '',
                'page_count' => count($source['pages'] ?? []),
            ];
            $generation->save();

            $generation->markStep(AiWebsiteGeneration::STATUS_WEBSITE_BRIEF);
            $briefResult = $this->briefService->make($company, $input, $source);
            $generation->website_brief_json = $briefResult['brief'];
            $generation->save();

            $generation->markStep(AiWebsiteGeneration::STATUS_SITEMAP);
            $pack = $this->sitemapPlanner->make($company, $briefResult['brief'], $input, (string) $theme->slug, $source);
            $generation->sitemap_json = $pack['sitemap'];
            $generation->page_plans_json = ['pages' => $pack['pages'] ?? [$pack['homepage']], 'homepage' => $pack['homepage']];
            $generation->used_openai = $briefResult['used_openai'] || $pack['used_openai'];
            $generation->save();

            $generation->markStep(AiWebsiteGeneration::STATUS_BUILD);
            $result = $this->generator->persistDraftPages(
                $company,
                $theme,
                $pack['pages'] ?? [$pack['homepage']],
                $pack['sitemap'],
                (string) ($input['primary_color'] ?? ''),
                (string) ($input['secondary_color'] ?? ''),
                (bool) ($input['generate_images'] ?? false),
                (bool) ($input['replace_existing'] ?? false),
            );

            $generation->homepage_page_id = $result['homepage_page_id'];
            $generation->markStep(AiWebsiteGeneration::STATUS_COMPLETED);

            return $result + [
                'used_openai' => (bool) $generation->used_openai,
                'source_pages' => count($source['pages'] ?? []),
                'generation_id' => (int) $generation->id,
            ];
        } catch (Throwable $e) {
            $generation->markFailed($e->getMessage());
            throw $e;
        }
    }
}
