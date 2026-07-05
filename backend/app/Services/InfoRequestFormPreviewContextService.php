<?php

namespace App\Services;

use App\Models\WebsitePage;

class InfoRequestFormPreviewContextService
{
    /**
     * @return list<array{
     *     id: string,
     *     page_title: string,
     *     page_slug: string,
     *     section_key: string,
     *     width_percent: int,
     *     layout: 'text_block_half'|'standalone',
     *     label: string
     * }>
     */
    public function contextsForCompany(?int $companyId): array
    {
        $query = WebsitePage::query()->orderBy('title');
        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $contexts = [];
        foreach ($query->get() as $page) {
            $sections = $page->getHomeSections();
            if ($sections === []) {
                continue;
            }

            foreach ($sections as $sectionKey => $sectionData) {
                if (! is_string($sectionKey) || ! is_array($sectionData)) {
                    continue;
                }

                $baseType = preg_replace('/_\d+$/', '', $sectionKey);
                if ($baseType === 'text_block') {
                    $context = $this->textBlockContext($page, $sectionKey, $sectionData, $sections);
                    if ($context !== null) {
                        $contexts[] = $context;
                    }

                    continue;
                }

                if ($baseType === 'email_template') {
                    $context = $this->standaloneEmailTemplateContext($page, $sectionKey, $sectionData);
                    if ($context !== null) {
                        $contexts[] = $context;
                    }
                }
            }
        }

        return $contexts;
    }

    /**
     * @param  list<array<string, mixed>>  $contexts
     * @return array<string, mixed>
     */
    public function defaultContext(array $contexts): array
    {
        if ($contexts !== []) {
            return $contexts[0];
        }

        return [
            'id' => 'default',
            'page_title' => '',
            'page_slug' => '',
            'section_key' => '',
            'width_percent' => 100,
            'layout' => 'text_block_half',
            'label' => 'Standaard (100% sectiebreedte)',
        ];
    }

    /**
     * @param  array<string, mixed>  $allSections
     * @return array<string, mixed>|null
     */
    private function textBlockContext(WebsitePage $page, string $sectionKey, array $data, array $allSections): ?array
    {
        $sideKey = trim((string) ($data['side_component_key'] ?? ''));
        $alignment = (string) ($data['alignment'] ?? 'left');
        if ($sideKey === '' || ! in_array($alignment, ['left', 'right'], true)) {
            return null;
        }

        if (! $this->hasLinkedForm($sideKey, $data, $allSections)) {
            return null;
        }

        $widthPercent = max(30, min(100, (int) ($data['width_percent'] ?? 100)));

        return $this->buildContext($page, $sectionKey, $widthPercent, 'text_block_half');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function standaloneEmailTemplateContext(WebsitePage $page, string $sectionKey, array $data): ?array
    {
        $templateId = $data['template_id'] ?? null;
        if ($templateId === null || $templateId === '') {
            return null;
        }

        return $this->buildContext($page, $sectionKey, 100, 'standalone');
    }

    /**
     * @param  array<string, mixed>  $textBlockData
     * @param  array<string, mixed>  $allSections
     */
    private function hasLinkedForm(string $sideKey, array $textBlockData, array $allSections): bool
    {
        if (preg_replace('/_\d+$/', '', $sideKey) !== 'email_template') {
            return false;
        }

        $sideTemplateId = $textBlockData['side_template_id'] ?? null;
        if ($sideTemplateId !== null && $sideTemplateId !== '') {
            return true;
        }

        $linked = $allSections[$sideKey] ?? null;
        if (is_array($linked) && ! empty($linked['template_id'])) {
            return true;
        }

        return FrontendComponentService::isComponentKey($sideKey);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(WebsitePage $page, string $sectionKey, int $widthPercent, string $layout): array
    {
        $pageTitle = (string) ($page->title ?: $page->slug ?: 'Pagina');
        $pageSlug = (string) ($page->slug ?? '');
        $layoutLabel = $layout === 'text_block_half' ? 'formulier naast tekst' : 'formuliersectie';

        return [
            'id' => $page->id.'::'.$sectionKey,
            'page_title' => $pageTitle,
            'page_slug' => $pageSlug,
            'section_key' => $sectionKey,
            'width_percent' => $widthPercent,
            'layout' => $layout,
            'label' => sprintf('%s · %d%% · %s', $pageTitle, $widthPercent, $layoutLabel),
        ];
    }
}
