<?php

namespace App\Services;

use App\Models\Module as ModuleModel;
use App\Services\ModuleManager;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

class MenuService
{
    protected ModuleManager $moduleManager;

    public function __construct(ModuleManager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * Get alle menu items van actieve modules (gefilterd op moduleconfiguratie enabled_menu_items).
     */
    public function getModuleMenuItems(): array
    {
        $menuItems = [];

        try {
            if (!Schema::hasTable('modules')) {
                return $menuItems;
            }

            // Check if user is logged in
            if (!auth()->check()) {
                return $menuItems;
            }

            $user = auth()->user();
            $isSuperAdmin = $user->hasRole('super-admin');

            $activeModules = $this->moduleManager->getActiveModules();

            foreach ($activeModules as $module) {
                if (!$module) {
                    continue;
                }

                $moduleMenuItems = $module->registerMenuItems();
                $enabledKeys = $this->getEnabledMenuKeysForModule($module->getName());

                foreach ($moduleMenuItems as $item) {
                    // Filter op door gebruiker geselecteerde onderdelen (enabled_menu_items in config)
                    if (isset($item['key'])) {
                        if ($enabledKeys !== null && !in_array($item['key'], $enabledKeys, true)) {
                            continue;
                        }
                    }
                    // Check permission if specified
                    if (isset($item['permission'])) {
                        // Super-admin can see everything
                        if ($isSuperAdmin) {
                            // Show menu item for super-admin
                        } else {
                            // Check if permission exists in database
                            $permissionExists = Permission::where('name', $item['permission'])
                                ->where('guard_name', 'web')
                                ->exists();
                            
                            if ($permissionExists) {
                                // Permission exists - check if user has it
                                try {
                                    if (!$user->can($item['permission'])) {
                                        // User doesn't have permission, skip this menu item
                                        continue;
                                    }
                                } catch (\Exception $e) {
                                    // If can() fails, skip to be safe
                                    continue;
                                }
                            }
                            // If permission doesn't exist yet, don't show (wait for proper setup)
                        }
                    }
                    // If no permission specified, show for everyone (or check if logged in)

                    // Add module info to menu item
                    $item['module'] = $module->getName();
                    $item['module_display_name'] = $module->getDisplayName();
                    
                    $menuItems[] = $item;
                }
            }

            // Sort by order if specified
            usort($menuItems, function($a, $b) {
                $orderA = $a['order'] ?? 999;
                $orderB = $b['order'] ?? 999;
                return $orderA <=> $orderB;
            });

        } catch (\Exception $e) {
            // Log error but don't break the page
            \Log::error('Error in getModuleMenuItems: ' . $e->getMessage());
        }

        return $menuItems;
    }

    /**
     * Geef voor een module de lijst enabled menu item keys uit config.
     * Null = geen filter (alle onderdelen tonen), array = alleen deze keys.
     */
    public function getEnabledMenuKeysForModule(string $moduleName): ?array
    {
        if (!Schema::hasTable('modules')) {
            return null;
        }

        $model = ModuleModel::where('name', $moduleName)->first();
        if (!$model || !is_array($model->configuration ?? null)) {
            return null;
        }

        $enabled = $model->configuration['enabled_menu_items'] ?? null;
        if (!is_array($enabled)) {
            return null;
        }

        return $enabled;
    }

    /**
     * Routes die door een actieve module als menuitem worden getoond (bv. admin.branches.index).
     * Gebruikt in de sidebar om dubbele items te vermijden (bv. Branches niet statisch tonen als module het toont).
     */
    public function getRoutesShownByModules(): array
    {
        $routes = [];
        try {
            if (!Schema::hasTable('modules')) {
                return $routes;
            }
            foreach ($this->getModuleMenuItems() as $item) {
                if (!empty($item['route'])) {
                    $routes[$item['route']] = true;
                }
            }
        } catch (\Exception $e) {
            // ignore
        }
        return $routes;
    }

    /**
     * Get alle permissions van actieve modules
     */
    public function getModulePermissions(): array
    {
        $permissions = [];

        try {
            if (!Schema::hasTable('modules')) {
                return $permissions;
            }

            $activeModules = $this->moduleManager->getActiveModules();

            foreach ($activeModules as $module) {
                if (!$module) {
                    continue;
                }

                $modulePermissions = $module->registerPermissions();
                
                foreach ($modulePermissions as $permission) {
                    $permissions[] = [
                        'name' => $permission,
                        'module' => $module->getName(),
                        'module_display_name' => $module->getDisplayName(),
                    ];
                }
            }

        } catch (\Exception $e) {
            // Silently fail if there's an error
        }

        return $permissions;
    }

    /**
     * Get permissions gegroepeerd per module
     */
    public function getModulePermissionsGrouped(): array
    {
        $grouped = [];

        $permissions = $this->getModulePermissions();

        foreach ($permissions as $perm) {
            $moduleName = $perm['module_display_name'];
            if (!isset($grouped[$moduleName])) {
                $grouped[$moduleName] = [
                    'module' => $perm['module'],
                    'display_name' => $moduleName,
                    'permissions' => [],
                ];
            }
            $grouped[$moduleName]['permissions'][] = $perm['name'];
        }

        return $grouped;
    }
}
