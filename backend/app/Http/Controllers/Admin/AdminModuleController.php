<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module as ModuleModel;
use App\Services\MenuService;
use App\Services\ModuleManager;
use Illuminate\Http\Request;

class AdminModuleController extends Controller
{
    protected ModuleManager $moduleManager;

    protected MenuService $menuService;

    public function __construct(ModuleManager $moduleManager, MenuService $menuService)
    {
        $this->moduleManager = $moduleManager;
        $this->menuService = $menuService;
    }

    public function index()
    {
        $availableModules = $this->moduleManager->discoverModules();
        
        // Calculate statistics
        $stats = [
            'total_modules' => count($availableModules),
            'installed_modules' => count(array_filter($availableModules, fn($m) => $m['installed'])),
            'active_modules' => count(array_filter($availableModules, fn($m) => $m['active'])),
            'internal_modules' => count(array_filter($availableModules, fn($m) => $m['type'] === 'internal')),
            'external_modules' => count(array_filter($availableModules, fn($m) => $m['type'] === 'external')),
        ];
        
        return view('admin.modules.index', compact('availableModules', 'stats'));
    }

    public function install(string $moduleName)
    {
        try {
            $this->moduleManager->installModule($moduleName);
            return redirect()->route('admin.modules.index')
                ->with('success', "Module {$moduleName} succesvol geïnstalleerd");
        } catch (\Exception $e) {
            return redirect()->route('admin.modules.index')
                ->with('error', "Fout bij installeren: " . $e->getMessage());
        }
    }

    public function activate(string $moduleName)
    {
        try {
            $this->moduleManager->activateModule($moduleName);
            return redirect()->route('admin.modules.index')
                ->with('success', "Module {$moduleName} succesvol geactiveerd");
        } catch (\Exception $e) {
            return redirect()->route('admin.modules.index')
                ->with('error', "Fout bij activeren: " . $e->getMessage());
        }
    }

    public function deactivate(string $moduleName)
    {
        try {
            $this->moduleManager->deactivateModule($moduleName);
            return redirect()->route('admin.modules.index')
                ->with('success', "Module {$moduleName} succesvol gedeactiveerd");
        } catch (\Exception $e) {
            return redirect()->route('admin.modules.index')
                ->with('error', "Fout bij deactiveren: " . $e->getMessage());
        }
    }

    public function uninstall(string $moduleName)
    {
        try {
            $this->moduleManager->uninstallModule($moduleName);
            return redirect()->route('admin.modules.index')
                ->with('success', "Module {$moduleName} succesvol verwijderd");
        } catch (\Exception $e) {
            return redirect()->route('admin.modules.index')
                ->with('error', "Fout bij verwijderen: " . $e->getMessage());
        }
    }

    /**
     * Toon configuratieformulier: welke onderdelen (menu items) horen bij deze module.
     */
    public function config(string $moduleName)
    {
        $module = $this->moduleManager->loadModule($moduleName);
        if (!$module) {
            return redirect()->route('admin.modules.index')->with('error', 'Module niet gevonden.');
        }

        $moduleModel = ModuleModel::where('name', $moduleName)->first();
        if (!$moduleModel || !$moduleModel->installed) {
            return redirect()->route('admin.modules.index')->with('error', 'Module is niet geïnstalleerd.');
        }

        $allItems = $module->registerMenuItems();
        $availableItems = array_values(array_filter($allItems, fn($item) => isset($item['key'])));
        usort($availableItems, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        $config = $moduleModel->configuration ?? [];
        $enabledKeys = $config['enabled_menu_items'] ?? null;
        if ($enabledKeys === null) {
            $enabledKeys = array_column($availableItems, 'key');
        }

        return view('admin.modules.config', [
            'moduleName' => $moduleName,
            'module' => $module,
            'availableItems' => $availableItems,
            'enabledKeys' => array_fill_keys($enabledKeys, true),
        ]);
    }

    /**
     * Opslaan welke onderdelen bij deze module horen.
     */
    public function saveConfig(Request $request, string $moduleName)
    {
        $module = $this->moduleManager->loadModule($moduleName);
        if (!$module) {
            return redirect()->route('admin.modules.index')->with('error', 'Module niet gevonden.');
        }

        $moduleModel = ModuleModel::where('name', $moduleName)->first();
        if (!$moduleModel || !$moduleModel->installed) {
            return redirect()->route('admin.modules.index')->with('error', 'Module is niet geïnstalleerd.');
        }

        $allItems = $module->registerMenuItems();
        $validKeys = array_filter(array_column($allItems, 'key'));
        $submitted = $request->input('enabled_menu_items', []);
        if (!is_array($submitted)) {
            $submitted = [];
        }
        $enabledKeys = array_values(array_intersect($submitted, $validKeys));

        $config = $moduleModel->configuration ?? [];
        $config['enabled_menu_items'] = $enabledKeys;
        $moduleModel->update(['configuration' => $config]);

        return redirect()->route('admin.modules.config', $moduleName)
            ->with('success', 'Module-onderdelen bijgewerkt.');
    }
}
