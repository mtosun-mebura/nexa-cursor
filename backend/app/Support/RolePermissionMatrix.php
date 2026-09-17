<?php

namespace App\Support;

use App\Services\MenuService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Zelfde module/resource × actie-matrix als op rol bewerken, voor overzichten.
 */
final class RolePermissionMatrix
{
    /**
     * @var array<string, string>
     */
    private const ACTION_NAMES = [
        'view' => 'View',
        'create' => 'Create',
        'edit' => 'Edit',
        'update' => 'Update',
        'delete' => 'Delete',
        'publish' => 'Publish',
        'approve' => 'Approve',
        'schedule' => 'Schedule',
        'send' => 'Send',
        'assign' => 'Assign',
        'view_month' => 'Maand',
        'view-month' => 'Maand',
    ];

    /**
     * @var array<string, string>
     */
    private const MODULE_NAMES = [
        'users' => 'Gebruikers',
        'vacancies' => 'Vacatures',
        'matches' => 'Matches',
        'interviews' => 'Interviews',
        'notifications' => 'Notificaties',
        'email-templates' => 'E-mail Templates',
        'email_templates' => 'E-mail Templates',
        'mailserver' => 'Mailserver',
        'tenant-dashboard' => 'Tenant Dashboard',
        'tenant_dashboard' => 'Tenant Dashboard',
        'agenda' => 'Agenda',
        'companies' => 'Bedrijven',
        'branches' => 'Branches',
        'categories' => 'Categorieën',
        'roles' => 'Rollen en Permissies',
        'permissions' => 'Permissies',
        'dashboard' => 'Dashboard',
        'ai_chatbot' => 'AI-chatbot',
        'ai-chatbot' => 'AI-chatbot',
        'gps_tracking' => 'GPS-tracker',
        'gps-tracking' => 'GPS-tracker',
        'earnings' => 'Inkomsten',
        'rates' => 'Tarieven',
        'rides' => 'Chauffeur dispatch',
        'vehicles' => 'Voertuigen',
    ];

    public function __construct(
        protected PermissionModuleVisibility $visibility,
        protected MenuService $menuService,
    ) {}

    /**
     * @param  iterable<mixed>  $permissions
     * @return array{
     *     count: int,
     *     allActions: Collection<int, string>,
     *     actionNames: array<string, string>,
     *     moduleNames: array<string, string>,
     *     permissionMap: array<string, array<string, mixed>>,
     *     permissionsByMainModule: array<string, list<array{key: string, resource: ?string}>>,
     *     sortedModuleOrder: list<string>,
     *     modulePermissions: array<string, array{module: string}>
     * }
     */
    public function build(iterable $permissions): array
    {
        $allPermissions = $this->visibility->filter(collect($permissions))->values();
        $modulePermissions = $this->visibility->filterModulePermissionGroups(
            $this->menuService->getModulePermissionsGrouped()
        );
        $resourceToModuleMap = $this->resourceToModuleMap($modulePermissions);
        $moduleNames = $this->moduleNames($modulePermissions);

        $permissionModules = $allPermissions->groupBy(
            fn ($permission) => $this->groupKey((string) $permission->name, $resourceToModuleMap)
        );

        $permissionsByMainModule = [];
        $moduleOrder = [];

        foreach ($permissionModules as $moduleKey => $perms) {
            [$mainModule, $resource, $displayKey] = $this->mainModuleAndResource(
                (string) $moduleKey,
                $resourceToModuleMap,
                $perms->first()
            );

            if (! isset($permissionsByMainModule[$mainModule])) {
                $permissionsByMainModule[$mainModule] = [];
                $moduleOrder[] = $mainModule;
            }

            $permissionsByMainModule[$mainModule][] = [
                'key' => $displayKey,
                'resource' => $resource,
            ];
        }

        $permissionMap = [];
        foreach ($allPermissions as $permission) {
            [$action, $module, $mainModuleKey] = $this->actionAndModule(
                (string) $permission->name,
                $resourceToModuleMap
            );
            $permissionMap[$module][$action] = $permission;
        }

        $allActions = $allPermissions->map(
            fn ($permission) => $this->actionFromName((string) $permission->name)
        )->unique()->sort()->values();

        $sortedModuleOrder = [];
        foreach ($modulePermissions as $modData) {
            $modKey = $modData['module'] ?? null;
            if ($modKey && isset($permissionsByMainModule[$modKey])) {
                $sortedModuleOrder[] = $modKey;
            }
        }
        foreach ($moduleOrder as $modKey) {
            if (! in_array($modKey, $sortedModuleOrder, true)) {
                $sortedModuleOrder[] = $modKey;
            }
        }

        return [
            'count' => $allPermissions->count(),
            'allActions' => $allActions,
            'actionNames' => self::ACTION_NAMES,
            'moduleNames' => $moduleNames,
            'permissionMap' => $permissionMap,
            'permissionsByMainModule' => $permissionsByMainModule,
            'sortedModuleOrder' => $sortedModuleOrder,
            'modulePermissions' => $modulePermissions,
        ];
    }

    /**
     * @param  array<string, array{module?: string, permissions?: array<int, string>}>  $modulePermissions
     * @return array<string, string>
     */
    protected function resourceToModuleMap(array $modulePermissions): array
    {
        $map = $this->visibility->resourceToProductModule();
        foreach ($modulePermissions as $moduleData) {
            $moduleKey = (string) ($moduleData['module'] ?? '');
            foreach ($moduleData['permissions'] ?? [] as $permName) {
                $parts = explode('.', (string) $permName);
                if (count($parts) === 2) {
                    $map[$parts[0]] = $moduleKey;
                } elseif (count($parts) >= 3 && $parts[0] === $moduleKey) {
                    $map[$parts[1]] = $moduleKey;
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<string, array{module?: string}>  $modulePermissions
     * @return array<string, string>
     */
    protected function moduleNames(array $modulePermissions): array
    {
        $moduleNames = self::MODULE_NAMES;
        foreach ($modulePermissions as $displayName => $moduleData) {
            $moduleKey = (string) ($moduleData['module'] ?? '');
            if ($moduleKey !== '') {
                $moduleNames[$moduleKey] = (string) $displayName;
            }
        }

        foreach ($this->moduleResourceMenuLabels() as $key => $title) {
            $moduleNames[$key] = $title;
            $underscoreKey = str_replace('-', '_', $key);
            if (! isset($moduleNames[$underscoreKey])) {
                $moduleNames[$underscoreKey] = $title;
            }
        }

        foreach (self::MODULE_NAMES as $resource => $label) {
            foreach (['taxi', 'skillmatching'] as $product) {
                $compound = $product.'-'.str_replace('_', '-', $resource);
                $moduleNames[$compound] ??= $label;
                $moduleNames[$product.'-'.$resource] ??= $label;
            }
        }

        return $moduleNames;
    }

    /**
     * @return array<string, string>
     */
    protected function moduleResourceMenuLabels(): array
    {
        $labels = [];
        try {
            foreach ($this->menuService->getModuleMenuItems() as $menuItem) {
                $menuModule = Str::of((string) ($menuItem['module'] ?? ''))
                    ->replace('_', '-')
                    ->lower()
                    ->value();
                $menuTitle = trim((string) ($menuItem['title'] ?? ''));
                if ($menuModule === '' || $menuTitle === '') {
                    continue;
                }

                $candidateKeys = [];
                if (! empty($menuItem['key'])) {
                    $candidateKeys[] = (string) $menuItem['key'];
                }
                if (! empty($menuItem['route']) && is_string($menuItem['route'])) {
                    $routeParts = explode('.', $menuItem['route']);
                    if (count($routeParts) >= 4 && $routeParts[0] === 'admin' && $routeParts[1] === $menuModule) {
                        $candidateKeys[] = (string) $routeParts[2];
                    }
                }
                if (! empty($menuItem['permission']) && is_string($menuItem['permission'])) {
                    $permissionParts = explode('.', $menuItem['permission']);
                    if (count($permissionParts) >= 2) {
                        $candidateKeys[] = (string) $permissionParts[0];
                    }
                }

                foreach ($candidateKeys as $candidateKey) {
                    $normalized = Str::of($candidateKey)->replace('_', '-')->lower()->value();
                    if ($normalized !== '') {
                        $labels[$menuModule.'-'.$normalized] = $menuTitle;
                    }
                }
            }
        } catch (\Throwable) {
            return $labels;
        }

        return $labels;
    }

    /**
     * @param  array<string, string>  $resourceToModuleMap
     */
    protected function groupKey(string $name, array $resourceToModuleMap): string
    {
        $parts = explode('.', $name);
        if (count($parts) >= 3) {
            return $parts[0].'-'.$parts[1];
        }
        if (count($parts) === 2) {
            $resource = $parts[0];

            return isset($resourceToModuleMap[$resource])
                ? $resourceToModuleMap[$resource].'-'.$resource
                : 'other';
        }

        $dashParts = explode('-', $name);
        if (count($dashParts) > 1) {
            array_shift($dashParts);
            $resource = implode('-', $dashParts);

            return isset($resourceToModuleMap[$resource])
                ? $resourceToModuleMap[$resource].'-'.$resource
                : $resource;
        }

        return 'other';
    }

    /**
     * @param  array<string, string>  $resourceToModuleMap
     * @return array{0: string, 1: ?string, 2: string}
     */
    protected function mainModuleAndResource(string $moduleKey, array $resourceToModuleMap, mixed $firstPerm): array
    {
        $knownStandalone = isset(self::MODULE_NAMES[$moduleKey])
            || isset(self::MODULE_NAMES[str_replace('-', '_', $moduleKey)]);

        if ($knownStandalone && ! isset($resourceToModuleMap[$moduleKey])) {
            return ['other', $moduleKey, $moduleKey];
        }
        $mainModule = 'other';
        $resource = null;

        if (str_contains($moduleKey, '-')) {
            [$mainModule, $resource] = array_pad(explode('-', $moduleKey, 2), 2, null);
        } else {
            $resource = $moduleKey;
            if (isset($resourceToModuleMap[$resource])) {
                $mainModule = $resourceToModuleMap[$resource];
            } elseif ($firstPerm && str_contains((string) $firstPerm->name, '.')) {
                $nameParts = explode('.', (string) $firstPerm->name);
                if (count($nameParts) >= 3) {
                    $mainModule = $nameParts[0];
                    $resource = $nameParts[1];
                }
            }
        }

        $displayKey = $moduleKey;
        if ($mainModule !== 'other' && ! str_contains($moduleKey, '-')) {
            $displayKey = $mainModule.'-'.$resource;
        }

        return [$mainModule, $resource, $displayKey];
    }

    /**
     * @param  array<string, string>  $resourceToModuleMap
     * @return array{0: string, 1: string, 2: string}
     */
    protected function actionAndModule(string $name, array $resourceToModuleMap): array
    {
        $parts = explode('.', $name);
        if (count($parts) >= 3) {
            return [$parts[2], $parts[0].'-'.$parts[1], $parts[0]];
        }
        if (count($parts) === 2) {
            $resource = $parts[0];
            if (isset($resourceToModuleMap[$resource])) {
                return [$parts[1], $resourceToModuleMap[$resource].'-'.$resource, $resourceToModuleMap[$resource]];
            }

            return [$parts[1], 'other', 'other'];
        }

        $dashParts = explode('-', $name);
        $action = $dashParts[0] ?? 'other';
        array_shift($dashParts);
        $oldModule = implode('-', $dashParts) ?: 'other';
        if (isset($resourceToModuleMap[$oldModule])) {
            return [$action, $resourceToModuleMap[$oldModule].'-'.$oldModule, $resourceToModuleMap[$oldModule]];
        }

        return [$action, $oldModule, 'other'];
    }

    protected function actionFromName(string $name): string
    {
        $parts = explode('.', $name);
        if (count($parts) >= 3) {
            return $parts[2];
        }
        if (count($parts) === 2) {
            return $parts[1];
        }
        $dashParts = explode('-', $name);

        return $dashParts[0] ?? 'other';
    }
}
