<?php

namespace App\Modules\NexaTaxi\Services\Concerns;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Builder;

trait ResolvesScopedEmailTemplate
{
    protected function scopedEmailTemplateQuery(string $type, ?int $companyId): Builder
    {
        $query = EmailTemplate::query()->where('type', $type);

        if ($companyId === null || $companyId <= 0) {
            return $query->whereNull('company_id');
        }

        return $query->where('company_id', $companyId);
    }

    protected function findScopedEmailTemplate(string $type, ?int $companyId): ?EmailTemplate
    {
        return $this->scopedEmailTemplateQuery($type, $companyId)
            ->orderBy('id')
            ->first();
    }

    /**
     * Actieve template voor verzending: tenant-kopie eerst, anders globaal.
     * Niet via ORDER BY company_id DESC: in PostgreSQL komen NULL-waarden dan eerst.
     */
    protected function resolveActiveScopedEmailTemplate(string $type, ?int $companyId): ?EmailTemplate
    {
        if ($companyId !== null && $companyId > 0) {
            $tenant = EmailTemplate::query()
                ->where('type', $type)
                ->where('is_active', true)
                ->where('company_id', $companyId)
                ->orderBy('id')
                ->first();
            if ($tenant) {
                return $tenant;
            }
        }

        return EmailTemplate::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->whereNull('company_id')
            ->orderBy('id')
            ->first();
    }

    /**
     * Maak het template alleen aan als het nog niet bestaat; bestaande HTML blijft staan.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function firstOrCreateScopedEmailTemplate(string $type, ?int $companyId, array $attributes): EmailTemplate
    {
        $this->deduplicateScopedEmailTemplates($type, $companyId);

        $existing = $this->findScopedEmailTemplate($type, $companyId);
        if ($existing) {
            return $existing;
        }

        return $this->upsertScopedEmailTemplate($type, $companyId, $attributes);
    }

    /**
     * Houd één rij per type + tenant (of globaal); verwijder oudere duplicaten.
     */
    protected function deduplicateScopedEmailTemplates(string $type, ?int $companyId): void
    {
        $ids = $this->scopedEmailTemplateQuery($type, $companyId)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->pluck('id');

        if ($ids->count() <= 1) {
            return;
        }

        $keepId = (int) $ids->first();
        $deleteIds = $ids->slice(1)->map(static fn ($id) => (int) $id)->all();

        if ($deleteIds !== []) {
            EmailTemplate::query()->whereIn('id', $deleteIds)->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function upsertScopedEmailTemplate(string $type, ?int $companyId, array $attributes): EmailTemplate
    {
        $this->deduplicateScopedEmailTemplates($type, $companyId);

        $lookup = ['type' => $type];
        if ($companyId === null || $companyId <= 0) {
            $lookup['company_id'] = null;
        } else {
            $lookup['company_id'] = $companyId;
        }

        return EmailTemplate::query()->updateOrCreate($lookup, $attributes);
    }
}
