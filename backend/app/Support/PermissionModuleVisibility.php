<?php

namespace App\Support;

use App\Services\AdminDashboardModuleContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Bepaalt welke permissies in rollen/permissies-overzichten horen bij een productmodule,
 * zodat inactieve modules (bijv. skillmatching) daar niet meer als rechten verschijnen.
 */
final class PermissionModuleVisibility
{
    private const SKILLMATCHING_RESOURCES = [
        'vacancies',
        'matches',
        'interviews',
        'branches',
        'categories',
        'job-configurations',
        'job_configurations',
        'job-configuration-types',
        'job_configuration_types',
        'job-configuration',
        'agenda',
    ];

    private const TAXI_RESOURCES = [
        'vehicles',
        'rates',
        'rides',
        'ai-chatbot',
        'ai_chatbot',
        'earnings',
        'gps-tracking',
        'gps_tracking',
    ];

    public function __construct(
        protected AdminDashboardModuleContext $modules,
    ) {}

    /**
     * @return array<string, string> resource-slug => productmodule (skillmatching|taxi)
     */
    public function resourceToProductModule(): array
    {
        $map = [];
        foreach (self::SKILLMATCHING_RESOURCES as $resource) {
            $map[$resource] = 'skillmatching';
            $map[str_replace('_', '-', $resource)] = 'skillmatching';
            $map[str_replace('-', '_', $resource)] = 'skillmatching';
        }
        foreach (self::TAXI_RESOURCES as $resource) {
            $map[$resource] = 'taxi';
            $map[str_replace('_', '-', $resource)] = 'taxi';
            $map[str_replace('-', '_', $resource)] = 'taxi';
        }

        return $map;
    }

    public function moduleForPermission(string $name): ?string
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            return null;
        }

        if (str_starts_with($name, 'skillmatching.') || str_starts_with($name, 'skillmatching-')) {
            return 'skillmatching';
        }

        if (str_starts_with($name, 'taxi.') || str_starts_with($name, 'nexa-taxi.') || str_starts_with($name, 'nexa_taxi.')) {
            return 'taxi';
        }

        $resource = $this->resourceFromPermissionName($name);
        if ($resource === null) {
            return null;
        }

        $map = $this->resourceToProductModule();

        return $map[$resource] ?? $map[str_replace('_', '-', $resource)] ?? null;
    }

    public function isVisible(string $name): bool
    {
        $module = $this->moduleForPermission($name);
        if ($module === null) {
            return true;
        }

        return $this->productModuleIsAvailable($module);
    }

    public function isVisibleResourceKey(string $key): bool
    {
        $normalized = strtolower(str_replace('_', '-', trim($key)));
        if ($normalized === '') {
            return true;
        }

        if (str_starts_with($normalized, 'skillmatching')) {
            return $this->productModuleIsAvailable('skillmatching');
        }

        if (str_starts_with($normalized, 'taxi') || str_starts_with($normalized, 'nexa-taxi')) {
            return $this->productModuleIsAvailable('taxi');
        }

        $map = $this->resourceToProductModule();
        $module = $map[$key] ?? $map[$normalized] ?? $map[str_replace('-', '_', $normalized)] ?? null;
        if ($module === null) {
            return true;
        }

        return $this->productModuleIsAvailable($module);
    }

    /**
     * @param  Collection<int, mixed>|iterable<mixed>  $permissions
     * @return Collection<int, mixed>
     */
    public function filter(iterable $permissions): Collection
    {
        return collect($permissions)
            ->filter(function ($permission) {
                $name = is_object($permission) ? (string) ($permission->name ?? '') : (string) $permission;

                return $this->isVisible($name);
            })
            ->values();
    }

    /**
     * @param  iterable<int|string, mixed>  $keys
     * @return Collection<int, string>
     */
    public function filterResourceKeys(iterable $keys): Collection
    {
        return collect($keys)
            ->filter(fn ($key) => $this->isVisibleResourceKey((string) $key))
            ->values();
    }

    /**
     * @param  array<string, array{module?: string, permissions?: array<int, string>}>  $groups
     * @return array<string, array{module?: string, permissions?: array<int, string>}>
     */
    public function filterModulePermissionGroups(array $groups): array
    {
        return array_filter($groups, function ($data) {
            $key = is_array($data) ? (string) ($data['module'] ?? '') : '';

            return $key === '' || $this->isVisibleResourceKey($key);
        });
    }

    protected function productModuleIsAvailable(string $module): bool
    {
        return match ($module) {
            'skillmatching' => $this->modules->skillmatchingAvailable(),
            'taxi' => $this->modules->taxiAvailable(),
            default => true,
        };
    }

    protected function resourceFromPermissionName(string $name): ?string
    {
        if (str_contains($name, '.')) {
            $parts = explode('.', $name);
            if (count($parts) >= 3) {
                return $parts[1];
            }
            if (count($parts) === 2) {
                return $parts[0];
            }
        }

        $dashParts = explode('-', $name);
        if (count($dashParts) < 2) {
            return Str::of($name)->replace('_', '-')->value() ?: null;
        }

        array_shift($dashParts);

        return implode('-', $dashParts) ?: null;
    }
}
