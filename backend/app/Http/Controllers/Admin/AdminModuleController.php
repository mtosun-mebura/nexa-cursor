<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\InstallModuleJob;
use App\Models\Company;
use App\Models\Module as ModuleModel;
use App\Services\DatabaseResetService;
use App\Services\MenuService;
use App\Services\ModuleDatabaseService;
use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AdminModuleController extends Controller
{
    protected ModuleManager $moduleManager;

    protected MenuService $menuService;

    protected DatabaseResetService $databaseResetService;

    public function __construct(ModuleManager $moduleManager, MenuService $menuService, DatabaseResetService $databaseResetService)
    {
        $this->moduleManager = $moduleManager;
        $this->menuService = $menuService;
        $this->databaseResetService = $databaseResetService;
    }

    public function index()
    {
        $availableModules = $this->moduleManager->discoverModules();

        $dbService = app(ModuleDatabaseService::class);
        $useSingleDatabase = (bool) config('module_database.use_single_database', false);
        $hasModuleDatabases = $dbService->supportsModuleDatabases();

        foreach ($availableModules as &$module) {
            $module['installing'] = Cache::has('module_installing_'.$module['name']);
            // Alleen aparte module-DB tonen als multi-DB echt actief is (niet bij MODULE_USE_SINGLE_DATABASE=true).
            $module['database_name'] = ($module['installed'] && ! $useSingleDatabase && $hasModuleDatabases)
                ? $dbService->getModuleDatabaseName($module['name'])
                : null;
        }
        unset($module);

        // Calculate statistics
        $stats = [
            'total_modules' => count($availableModules),
            'installed_modules' => count(array_filter($availableModules, fn ($m) => $m['installed'])),
            'active_modules' => count(array_filter($availableModules, fn ($m) => $m['active'])),
            'internal_modules' => count(array_filter($availableModules, fn ($m) => $m['type'] === 'internal')),
            'external_modules' => count(array_filter($availableModules, fn ($m) => $m['type'] === 'external')),
        ];

        return view('admin.modules.index', compact('availableModules', 'stats', 'hasModuleDatabases', 'useSingleDatabase'));
    }

    public function install(string $moduleName)
    {
        if (Cache::has('module_installing_'.$moduleName)) {
            return redirect()->route('admin.modules.index')
                ->with('info', "Module {$moduleName} wordt al geïnstalleerd. Vernieuw de pagina over een minuut.");
        }

        if (config('queue.default') === 'sync') {
            // Geen queue worker: direct uitvoeren met verhoogde time limit
            set_time_limit(300);
            try {
                $this->moduleManager->installModule($moduleName);

                return redirect()->route('admin.modules.index')
                    ->with('success', "Module {$moduleName} succesvol geïnstalleerd");
            } catch (\Throwable $e) {
                Log::error('Module install failed: '.$moduleName, [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $message = $e->getMessage();
                if ($e->getPrevious()) {
                    $message .= ' ('.$e->getPrevious()->getMessage().')';
                }

                return redirect()->route('admin.modules.index')
                    ->with('error', 'Fout bij installeren van '.$moduleName.': '.$message);
            }
        }

        InstallModuleJob::dispatch($moduleName);

        return redirect()->route('admin.modules.index')
            ->with('success', "Installatie van {$moduleName} is gestart. Dit duurt ongeveer een minuut. Vernieuw de pagina om de status te zien.");
    }

    public function activate(string $moduleName)
    {
        try {
            $this->moduleManager->activateModule($moduleName);

            return redirect()->route('admin.modules.index')
                ->with('success', "Module {$moduleName} succesvol geactiveerd");
        } catch (\Exception $e) {
            return redirect()->route('admin.modules.index')
                ->with('error', 'Fout bij activeren: '.$e->getMessage());
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
                ->with('error', 'Fout bij deactiveren: '.$e->getMessage());
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
                ->with('error', 'Fout bij verwijderen: '.$e->getMessage());
        }
    }

    /**
     * Toon configuratieformulier: welke onderdelen (menu items) horen bij deze module.
     */
    public function config(string $moduleName)
    {
        $module = $this->moduleManager->loadModule($moduleName);
        if (! $module) {
            return redirect()->route('admin.modules.index')->with('error', 'Module niet gevonden.');
        }

        $moduleModel = ModuleModel::whereRaw('LOWER(name) = ?', [strtolower($moduleName)])->first();
        if (! $moduleModel || ! $moduleModel->installed) {
            return redirect()->route('admin.modules.index')->with('error', 'Module is niet geïnstalleerd.');
        }

        $allItems = $module->registerMenuItems();
        $availableItems = array_values(array_filter($allItems, fn ($item) => isset($item['key'])));
        usort($availableItems, fn ($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        $config = $moduleModel->configuration ?? [];
        $hasDashboardKey = array_key_exists('dashboard_link_visible', $config);
        $enabledKeys = $config['enabled_menu_items'] ?? null;
        if ($enabledKeys === null) {
            $enabledKeys = array_column($availableItems, 'key');
        }

        $dbService = app(ModuleDatabaseService::class);
        $conn = null;
        if ($dbService->supportsModuleDatabases()) {
            $connName = $dbService->getModuleConnectionName($moduleModel->name);
            if (Config::has("database.connections.{$connName}")) {
                $conn = $connName;
            }
        }
        try {
            $companies = $conn ? Company::on($conn)->orderBy('name')->get() : Company::orderBy('name')->get();
        } catch (\Throwable $e) {
            $companies = Company::orderBy('name')->get();
        }

        return view('admin.modules.config', [
            'moduleName' => $moduleModel->name,
            'module' => $module,
            'availableItems' => $availableItems,
            'enabledKeys' => array_fill_keys($enabledKeys, true),
            'app_name' => $config['app_name'] ?? '',
            'app_description' => $config['app_description'] ?? '',
            'dashboard_link_visible' => $hasDashboardKey
                ? (($config['dashboard_link_visible'] ?? '0') === '1')
                : false,
            'dashboard_link_label' => $config['dashboard_link_label'] ?? 'Mijn Nexa',
            'company_id' => $config['company_id'] ?? null,
            'companies' => $companies,
        ]);
    }

    /**
     * Opslaan welke onderdelen bij deze module horen.
     */
    public function saveConfig(Request $request, string $moduleName)
    {
        $module = $this->moduleManager->loadModule($moduleName);
        if (! $module) {
            return redirect()->route('admin.modules.index')->with('error', 'Module niet gevonden.');
        }

        $moduleModel = ModuleModel::whereRaw('LOWER(name) = ?', [strtolower($moduleName)])->first();
        if (! $moduleModel || ! $moduleModel->installed) {
            return redirect()->route('admin.modules.index')->with('error', 'Module is niet geïnstalleerd.');
        }

        $allItems = $module->registerMenuItems();
        $validKeys = array_filter(array_column($allItems, 'key'));
        $submitted = $request->input('enabled_menu_items', []);
        if (! is_array($submitted)) {
            $submitted = [];
        }
        $enabledKeys = array_values(array_intersect($submitted, $validKeys));

        $companyId = $request->filled('company_id') ? (int) $request->company_id : null;
        if ($companyId !== null) {
            $dbService = app(ModuleDatabaseService::class);
            $conn = null;
            if ($dbService->supportsModuleDatabases()) {
                $connName = $dbService->getModuleConnectionName($moduleModel->name);
                if (Config::has("database.connections.{$connName}")) {
                    $conn = $connName;
                }
            }
            try {
                $exists = $conn ? Company::on($conn)->where('id', $companyId)->exists() : Company::where('id', $companyId)->exists();
            } catch (\Throwable $e) {
                $exists = Company::where('id', $companyId)->exists();
            }
            if (! $exists) {
                return redirect()->back()->withInput()->withErrors(['company_id' => 'Het gekozen bedrijf bestaat niet.']);
            }
        }

        $config = $moduleModel->configuration ?? [];
        $config['enabled_menu_items'] = $enabledKeys;
        $config['app_name'] = $request->input('app_name', '');
        $config['app_description'] = $request->input('app_description', '');
        // Eén veld dashboard_link_visible ('0'|'1'); geen boolean() — dubbele name in een vorige versie gaf array → false.
        $raw = $request->input('dashboard_link_visible');
        $config['dashboard_link_visible'] = ($raw === '1' || $raw === true || $raw === 1) ? '1' : '0';
        $config['dashboard_link_label'] = $request->input('dashboard_link_label', 'Mijn Nexa');
        $config['company_id'] = $companyId;
        $moduleModel->update(['configuration' => $config]);

        return redirect()->route('admin.modules.config', $moduleModel->name)
            ->with('success', 'Module-configuratie bijgewerkt.');
    }

    /**
     * Leeg alle tabellen en herstel alleen super admin (m.tosun@mebura.nl) met alle rechten.
     */
    public function databaseReset(Request $request)
    {
        $request->validate(['confirm_reset' => 'required|in:1,yes']);

        try {
            $this->databaseResetService->resetAndRestoreSuperAdmin();

            return redirect()->route('admin.modules.index')
                ->with('success', 'Database gereset. Alle tabellen zijn geleegd. Super admin m.tosun@mebura.nl is hersteld met alle rechten.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.modules.index')
                ->with('error', 'Database reset mislukt: '.$e->getMessage());
        }
    }

    /**
     * Voer alle dummydata-seeders uit die bij de opgegeven module horen.
     */
    public function databaseDummydata(string $moduleName)
    {
        $module = $this->moduleManager->loadModule($moduleName);
        if (! $module) {
            return redirect()->route('admin.modules.index')->with('error', 'Module niet gevonden.');
        }

        $seeders = $module->getDummySeeders();
        if (empty($seeders)) {
            return redirect()->route('admin.modules.index')
                ->with('success', "Module {$moduleName} heeft geen dummydata-seeders geconfigureerd.");
        }

        $run = 0;
        foreach ($seeders as $seederClass) {
            if (! class_exists($seederClass)) {
                continue;
            }
            Artisan::call('db:seed', ['--class' => $seederClass, '--force' => true]);
            $run++;
        }

        return redirect()->route('admin.modules.index')
            ->with('success', "Dummydata voor {$moduleName} uitgevoerd ({$run} seeder(s)).");
    }
}
