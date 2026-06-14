<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Module;
use Illuminate\Support\Facades\Schema;

/**
 * Module-configuratie per tenant (company_module.settings), met fallback naar modules.configuration.
 */
final class ModuleConfigurationService
{
    public function resolveCompanyId(?int $explicitCompanyId = null): ?int
    {
        if ($explicitCompanyId !== null && $explicitCompanyId > 0) {
            return $explicitCompanyId;
        }

        $user = auth()->user();
        if ($user === null) {
            return null;
        }

        if ($user->hasRole('super-admin') && session('selected_tenant')) {
            return (int) session('selected_tenant');
        }

        if ($user->company_id) {
            return (int) $user->company_id;
        }

        return null;
    }

    public function superAdminRequiresTenantSelection(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->hasRole('super-admin')
            && ! session('selected_tenant');
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(Module|string $module, ?int $companyId = null): array
    {
        $moduleModel = $module instanceof Module
            ? $module
            : Module::query()->whereRaw('LOWER(name) = ?', [strtolower(trim((string) $module))])->first();

        if ($moduleModel === null) {
            return [];
        }

        $defaults = is_array($moduleModel->configuration) ? $moduleModel->configuration : [];
        $companyId = $companyId ?? $this->resolveCompanyId();
        if ($companyId === null) {
            return $defaults;
        }

        $tenantSettings = $this->getTenantSettings($moduleModel, $companyId);

        return array_merge($defaults, $tenantSettings);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTenantSettings(Module $module, int $companyId): array
    {
        if (! Schema::hasTable('company_module')) {
            return [];
        }

        $company = Company::query()->find($companyId);
        if ($company === null) {
            return [];
        }

        $linked = $company->modules()->where('modules.id', $module->id)->first();
        if ($linked === null) {
            return [];
        }

        $settings = $linked->pivot->settings ?? null;
        if (is_string($settings) && $settings !== '') {
            $decoded = json_decode($settings, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($settings) ? $settings : [];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function saveTenantConfiguration(Module $module, int $companyId, array $settings): void
    {
        if (! Schema::hasTable('company_module')) {
            throw new \RuntimeException('Tabel company_module ontbreekt.');
        }

        $company = Company::query()->findOrFail($companyId);
        $payload = [
            'settings' => json_encode($settings, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ];

        if ($company->modules()->where('modules.id', $module->id)->exists()) {
            $company->modules()->updateExistingPivot($module->id, $payload);

            return;
        }

        $company->modules()->attach($module->id, array_merge($payload, [
            'created_at' => now(),
        ]));
    }
}
