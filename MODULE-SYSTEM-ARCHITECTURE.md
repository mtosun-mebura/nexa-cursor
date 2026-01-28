# Modulair Systeem Architectuur - Nexa Platform

## Overzicht

Dit document beschrijft de architectuur voor een modulair systeem waarbij:
- **Core Framework**: Rollen, Rechten, Chat, Notificaties, Menustructuur, Dashboard, Configuraties
- **Modules**: Zelfstandige functionaliteiten zoals "Nexa Skillmatching" (Vacatures, Matches, Interviews), "Taxi", etc.
- **Frontend Website Builder**: Voor het beheren van frontend pagina's en thema's

---

## 1. Directory Structuur

```
backend/
├── app/
│   ├── Core/                          # Core framework functionaliteit
│   │   ├── Models/
│   │   │   ├── Role.php
│   │   │   ├── Permission.php
│   │   │   ├── Notification.php
│   │   │   └── Menu.php
│   │   ├── Services/
│   │   │   ├── MenuService.php
│   │   │   └── NotificationService.php
│   │   └── Middleware/
│   │       └── ModuleMiddleware.php
│   │
│   ├── Modules/                        # Module namespace
│   │   ├── Base/                      # Base module class
│   │   │   ├── Module.php
│   │   │   └── ModuleServiceProvider.php
│   │   │
│   │   ├── Skillmatching/             # Nexa Skillmatching module
│   │   │   ├── Module.php
│   │   │   ├── ModuleServiceProvider.php
│   │   │   ├── Controllers/
│   │   │   │   ├── VacancyController.php
│   │   │   │   ├── MatchController.php
│   │   │   │   └── InterviewController.php
│   │   │   ├── Models/
│   │   │   │   ├── Vacancy.php
│   │   │   │   ├── Match.php
│   │   │   │   └── Interview.php
│   │   │   ├── Routes/
│   │   │   │   ├── web.php
│   │   │   │   └── api.php
│   │   │   ├── Migrations/
│   │   │   ├── Views/
│   │   │   ├── Assets/
│   │   │   │   ├── css/
│   │   │   │   └── js/
│   │   │   └── config/
│   │   │       └── module.php
│   │   │
│   │   └── Taxi/                       # Taxi module (voorbeeld)
│   │       ├── Module.php
│   │       ├── ModuleServiceProvider.php
│   │       └── ...
│   │
│   └── Services/
│       ├── ModuleManager.php          # Module discovery & management
│       └── WebsiteBuilderService.php  # Frontend website builder
│
├── modules/                           # Externe modules (plugins)
│   ├── skillmatching/
│   ├── taxi/
│   └── ...
│
├── database/
│   └── migrations/
│       ├── core/
│       │   ├── create_modules_table.php
│       │   └── create_module_permissions_table.php
│       └── modules/
│           └── skillmatching/
│               └── ...
│
└── config/
    └── modules.php                    # Module configuratie
```

---

## 2. Module Structuur & Interface

### 2.1 Base Module Class

```php
<?php
// app/Modules/Base/Module.php

namespace App\Modules\Base;

abstract class Module
{
    /**
     * Module naam (uniek identifier)
     */
    abstract public function getName(): string;

    /**
     * Module display naam
     */
    abstract public function getDisplayName(): string;

    /**
     * Module versie
     */
    abstract public function getVersion(): string;

    /**
     * Module beschrijving
     */
    abstract public function getDescription(): string;

    /**
     * Module icon (fontawesome class of SVG path)
     */
    abstract public function getIcon(): string;

    /**
     * Vereiste modules (dependencies)
     */
    public function getDependencies(): array
    {
        return [];
    }

    /**
     * Vereiste Laravel packages
     */
    public function getRequiredPackages(): array
    {
        return [];
    }

    /**
     * Module routes registreren
     */
    public function registerRoutes(): void
    {
        // Override in child class
    }

    /**
     * Module views registreren
     */
    public function registerViews(): void
    {
        // Override in child class
    }

    /**
     * Module assets registreren
     */
    public function registerAssets(): void
    {
        // Override in child class
    }

    /**
     * Module menu items registreren
     */
    public function registerMenuItems(): array
    {
        return [];
    }

    /**
     * Module permissions registreren
     */
    public function registerPermissions(): array
    {
        return [];
    }

    /**
     * Module migrations pad
     */
    public function getMigrationsPath(): ?string
    {
        return null;
    }

    /**
     * Module installatie
     */
    public function install(): bool
    {
        return true;
    }

    /**
     * Module uninstallatie
     */
    public function uninstall(): bool
    {
        return true;
    }

    /**
     * Module activatie
     */
    public function activate(): bool
    {
        return true;
    }

    /**
     * Module deactivatie
     */
    public function deactivate(): bool
    {
        return true;
    }

    /**
     * Module configuratie formulier
     */
    public function getConfigurationForm(): ?string
    {
        return null; // Return view path
    }

    /**
     * Module configuratie opslaan
     */
    public function saveConfiguration(array $data): bool
    {
        return true;
    }

    /**
     * Module configuratie ophalen
     */
    public function getConfiguration(): array
    {
        return [];
    }

    /**
     * Frontend routes voor deze module
     */
    public function getFrontendRoutes(): array
    {
        return [];
    }
}
```

### 2.2 Voorbeeld: Skillmatching Module

```php
<?php
// app/Modules/Skillmatching/Module.php

namespace App\Modules\Skillmatching;

use App\Modules\Base\Module;

class SkillmatchingModule extends Module
{
    public function getName(): string
    {
        return 'skillmatching';
    }

    public function getDisplayName(): string
    {
        return 'Nexa Skillmatching';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Vacature matching en interview management systeem';
    }

    public function getIcon(): string
    {
        return 'ki-filled ki-briefcase';
    }

    public function registerMenuItems(): array
    {
        return [
            [
                'title' => 'Vacatures',
                'route' => 'admin.skillmatching.vacancies.index',
                'icon' => 'ki-filled ki-briefcase',
                'permission' => 'skillmatching.vacancies.view',
                'order' => 10,
            ],
            [
                'title' => 'Matches',
                'route' => 'admin.skillmatching.matches.index',
                'icon' => 'ki-filled ki-abstract-26',
                'permission' => 'skillmatching.matches.view',
                'order' => 20,
            ],
            [
                'title' => 'Interviews',
                'route' => 'admin.skillmatching.interviews.index',
                'icon' => 'ki-filled ki-calendar',
                'permission' => 'skillmatching.interviews.view',
                'order' => 30,
            ],
        ];
    }

    public function registerPermissions(): array
    {
        return [
            'skillmatching.vacancies.view',
            'skillmatching.vacancies.create',
            'skillmatching.vacancies.edit',
            'skillmatching.vacancies.delete',
            'skillmatching.matches.view',
            'skillmatching.matches.create',
            'skillmatching.interviews.view',
            'skillmatching.interviews.create',
            'skillmatching.interviews.edit',
        ];
    }

    public function getMigrationsPath(): ?string
    {
        return database_path('migrations/modules/skillmatching');
    }

    public function getFrontendRoutes(): array
    {
        return [
            [
                'path' => '/vacatures',
                'component' => 'skillmatching::vacancies.index',
                'name' => 'frontend.vacancies',
            ],
            [
                'path' => '/vacatures/{id}',
                'component' => 'skillmatching::vacancies.show',
                'name' => 'frontend.vacancy.show',
            ],
        ];
    }
}
```

---

## 3. Module Manager Service

```php
<?php
// app/Services/ModuleManager.php

namespace App\Services;

use App\Models\Module as ModuleModel;
use App\Modules\Base\Module;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleManager
{
    protected array $modules = [];
    protected array $loadedModules = [];

    /**
     * Discover alle modules
     */
    public function discoverModules(): array
    {
        $modules = [];

        // 1. Discover modules in app/Modules
        $appModulesPath = app_path('Modules');
        if (File::exists($appModulesPath)) {
            $modules = array_merge($modules, $this->scanDirectory($appModulesPath));
        }

        // 2. Discover modules in modules/ directory (external plugins)
        $modulesPath = base_path('modules');
        if (File::exists($modulesPath)) {
            $modules = array_merge($modules, $this->scanDirectory($modulesPath));
        }

        return $modules;
    }

    /**
     * Scan directory voor modules
     */
    protected function scanDirectory(string $path): array
    {
        $modules = [];
        $directories = File::directories($path);

        foreach ($directories as $directory) {
            $moduleName = basename($directory);
            $moduleFile = $directory . '/Module.php';

            if (File::exists($moduleFile)) {
                $modules[] = [
                    'path' => $directory,
                    'name' => $moduleName,
                    'type' => str_contains($directory, 'modules/') ? 'external' : 'internal',
                ];
            }
        }

        return $modules;
    }

    /**
     * Load een module
     */
    public function loadModule(string $moduleName): ?Module
    {
        if (isset($this->loadedModules[$moduleName])) {
            return $this->loadedModules[$moduleName];
        }

        // Try internal modules first
        $moduleClass = "App\\Modules\\{$moduleName}\\Module";
        if (!class_exists($moduleClass)) {
            // Try external modules
            $moduleClass = "Modules\\{$moduleName}\\Module";
        }

        if (!class_exists($moduleClass)) {
            return null;
        }

        $module = new $moduleClass();
        $this->loadedModules[$moduleName] = $module;
        $this->modules[$moduleName] = $module;

        return $module;
    }

    /**
     * Get alle geïnstalleerde modules
     */
    public function getInstalledModules(): array
    {
        return ModuleModel::where('installed', true)->get()->map(function ($module) {
            return $this->loadModule($module->name);
        })->filter()->toArray();
    }

    /**
     * Get alle geactiveerde modules
     */
    public function getActiveModules(): array
    {
        return ModuleModel::where('installed', true)
            ->where('active', true)
            ->get()
            ->map(function ($module) {
                return $this->loadModule($module->name);
            })
            ->filter()
            ->toArray();
    }

    /**
     * Install een module
     */
    public function installModule(string $moduleName): bool
    {
        $module = $this->loadModule($moduleName);
        if (!$module) {
            return false;
        }

        // Check dependencies
        $dependencies = $module->getDependencies();
        foreach ($dependencies as $dep) {
            $depModule = ModuleModel::where('name', $dep)
                ->where('installed', true)
                ->first();
            if (!$depModule) {
                throw new \Exception("Dependency {$dep} not installed");
            }
        }

        // Run migrations
        $migrationsPath = $module->getMigrationsPath();
        if ($migrationsPath && File::exists($migrationsPath)) {
            \Artisan::call('migrate', [
                '--path' => str_replace(database_path('migrations/'), '', $migrationsPath),
            ]);
        }

        // Register permissions
        $permissions = $module->registerPermissions();
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Call module install
        $module->install();

        // Save to database
        ModuleModel::updateOrCreate(
            ['name' => $moduleName],
            [
                'display_name' => $module->getDisplayName(),
                'version' => $module->getVersion(),
                'description' => $module->getDescription(),
                'icon' => $module->getIcon(),
                'installed' => true,
                'active' => false,
            ]
        );

        return true;
    }

    /**
     * Activate een module
     */
    public function activateModule(string $moduleName): bool
    {
        $module = $this->loadModule($moduleName);
        if (!$module) {
            return false;
        }

        $module->activate();

        ModuleModel::where('name', $moduleName)->update(['active' => true]);

        return true;
    }

    /**
     * Deactivate een module
     */
    public function deactivateModule(string $moduleName): bool
    {
        $module = $this->loadModule($moduleName);
        if (!$module) {
            return false;
        }

        $module->deactivate();

        ModuleModel::where('name', $moduleName)->update(['active' => false]);

        return true;
    }
}
```

---

## 4. Database Schema

```php
<?php
// database/migrations/core/create_modules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('version');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('installed')->default(false);
            $table->boolean('active')->default(false);
            $table->json('configuration')->nullable();
            $table->timestamps();
        });

        Schema::create('module_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
            $table->string('permission_name');
            $table->timestamps();

            $table->unique(['module_id', 'permission_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_permissions');
        Schema::dropIfExists('modules');
    }
};
```

---

## 5. Module Service Provider

```php
<?php
// app/Modules/Base/ModuleServiceProvider.php

namespace App\Modules\Base;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Services\ModuleManager;

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected Module $module;
    protected ModuleManager $moduleManager;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->moduleManager = app(ModuleManager::class);
    }

    public function register(): void
    {
        // Register module
        $moduleClass = $this->getModuleClass();
        $this->module = new $moduleClass();
    }

    public function boot(): void
    {
        // Only boot if module is active
        if (!$this->isModuleActive()) {
            return;
        }

        // Register routes
        $this->registerRoutes();

        // Register views
        $this->registerViews();

        // Register menu items
        $this->registerMenuItems();
    }

    protected function registerRoutes(): void
    {
        $moduleName = $this->module->getName();
        $routesPath = $this->getRoutesPath();

        if (file_exists($routesPath . '/web.php')) {
            Route::middleware(['web', 'admin'])
                ->prefix("admin/{$moduleName}")
                ->name("admin.{$moduleName}.")
                ->group($routesPath . '/web.php');
        }

        if (file_exists($routesPath . '/api.php')) {
            Route::middleware(['api', 'auth:sanctum'])
                ->prefix("api/{$moduleName}")
                ->name("api.{$moduleName}.")
                ->group($routesPath . '/api.php');
        }
    }

    protected function registerViews(): void
    {
        $viewsPath = $this->getViewsPath();
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $this->module->getName());
        }
    }

    protected function registerMenuItems(): void
    {
        $menuItems = $this->module->registerMenuItems();
        // Register with MenuService
        app(\App\Core\Services\MenuService::class)->addModuleItems(
            $this->module->getName(),
            $menuItems
        );
    }

    protected function isModuleActive(): bool
    {
        return \App\Models\Module::where('name', $this->module->getName())
            ->where('active', true)
            ->exists();
    }

    abstract protected function getModuleClass(): string;
    abstract protected function getRoutesPath(): string;
    abstract protected function getViewsPath(): string;
}
```

---

## 6. Module Beheer Controller

```php
<?php
// app/Http/Controllers/Admin/AdminModuleController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ModuleManager;
use Illuminate\Http\Request;

class AdminModuleController extends Controller
{
    protected ModuleManager $moduleManager;

    public function __construct(ModuleManager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    public function index()
    {
        $installedModules = $this->moduleManager->getInstalledModules();
        $availableModules = $this->moduleManager->discoverModules();

        return view('admin.modules.index', compact('installedModules', 'availableModules'));
    }

    public function install(string $moduleName)
    {
        try {
            $this->moduleManager->installModule($moduleName);
            return redirect()->route('admin.modules.index')
                ->with('success', "Module {$moduleName} geïnstalleerd");
        } catch (\Exception $e) {
            return redirect()->route('admin.modules.index')
                ->with('error', $e->getMessage());
        }
    }

    public function activate(string $moduleName)
    {
        try {
            $this->moduleManager->activateModule($moduleName);
            return redirect()->route('admin.modules.index')
                ->with('success', "Module {$moduleName} geactiveerd");
        } catch (\Exception $e) {
            return redirect()->route('admin.modules.index')
                ->with('error', $e->getMessage());
        }
    }

    public function deactivate(string $moduleName)
    {
        try {
            $this->moduleManager->deactivateModule($moduleName);
            return redirect()->route('admin.modules.index')
                ->with('success', "Module {$moduleName} gedeactiveerd");
        } catch (\Exception $e) {
            return redirect()->route('admin.modules.index')
                ->with('error', $e->getMessage());
        }
    }

    public function configure(string $moduleName)
    {
        $module = $this->moduleManager->loadModule($moduleName);
        $configuration = $module->getConfiguration();

        return view('admin.modules.configure', compact('module', 'configuration'));
    }

    public function saveConfiguration(Request $request, string $moduleName)
    {
        $module = $this->moduleManager->loadModule($moduleName);
        $module->saveConfiguration($request->all());

        return redirect()->route('admin.modules.index')
            ->with('success', "Configuratie opgeslagen");
    }
}
```

---

## 7. Frontend Website Builder

### 7.1 Database Schema

```php
<?php
// database/migrations/core/create_pages_table.php

Schema::create('pages', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->string('title');
    $table->text('content')->nullable(); // JSON of HTML
    $table->string('template')->nullable();
    $table->boolean('published')->default(false);
    $table->integer('order')->default(0);
    $table->timestamps();
});

Schema::create('themes', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->json('settings'); // Colors, fonts, etc.
    $table->boolean('active')->default(false);
    $table->timestamps();
});
```

### 7.2 Website Builder Service

```php
<?php
// app/Services/WebsiteBuilderService.php

namespace App\Services;

use App\Models\Page;
use App\Models\Theme;

class WebsiteBuilderService
{
    public function createPage(array $data): Page
    {
        return Page::create($data);
    }

    public function updatePage(Page $page, array $data): Page
    {
        $page->update($data);
        return $page;
    }

    public function getPageBySlug(string $slug): ?Page
    {
        return Page::where('slug', $slug)
            ->where('published', true)
            ->first();
    }

    public function activateTheme(string $themeSlug): bool
    {
        Theme::where('active', true)->update(['active' => false]);
        Theme::where('slug', $themeSlug)->update(['active' => true]);
        return true;
    }
}
```

---

## 8. Implementatie Stappenplan

### Fase 1: Core Infrastructure (Week 1-2)
1. ✅ Create base Module class
2. ✅ Create ModuleManager service
3. ✅ Create database migrations
4. ✅ Create Module model
5. ✅ Create ModuleServiceProvider base class

### Fase 2: Module Discovery & Management (Week 2-3)
1. ✅ Implement module discovery
2. ✅ Create AdminModuleController
3. ✅ Create module management views
4. ✅ Implement install/activate/deactivate

### Fase 3: Migrate Existing Code (Week 3-4)
1. ✅ Move Vacancies → Skillmatching module
2. ✅ Move Matches → Skillmatching module
3. ✅ Move Interviews → Skillmatching module
4. ✅ Update routes & controllers
5. ✅ Update menu registration

### Fase 4: Frontend Website Builder (Week 4-5)
1. ✅ Create pages & themes tables
2. ✅ Create WebsiteBuilderService
3. ✅ Create page builder UI
4. ✅ Create theme customizer
5. ✅ Integrate with frontend routing

### Fase 5: Testing & Documentation (Week 5-6)
1. ✅ Test module installation
2. ✅ Test module activation
3. ✅ Test frontend builder
4. ✅ Write documentation
5. ✅ Create example module (Taxi)

---

## 9. Best Practices

1. **Namespace Conventies**
   - Modules: `App\Modules\{ModuleName}\*`
   - External: `Modules\{ModuleName}\*`

2. **Naming Conventies**
   - Module naam: lowercase, kebab-case (skillmatching, taxi-module)
   - Class names: PascalCase
   - Routes: `admin.{module}.{resource}.{action}`

3. **Dependencies**
   - Modules kunnen andere modules vereisen
   - Check dependencies bij installatie
   - Versie management voor modules

4. **Security**
   - Permissions per module
   - Middleware per module route
   - CSRF protection

5. **Performance**
   - Cache module discovery
   - Lazy load modules
   - Asset optimization per module

---

## 10. Voorbeeld: Taxi Module

```php
<?php
// modules/taxi/Module.php

namespace Modules\Taxi;

use App\Modules\Base\Module;

class TaxiModule extends Module
{
    public function getName(): string
    {
        return 'taxi';
    }

    public function getDisplayName(): string
    {
        return 'Taxi Management';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Taxi booking en management systeem';
    }

    public function getIcon(): string
    {
        return 'ki-filled ki-car';
    }

    public function registerMenuItems(): array
    {
        return [
            [
                'title' => 'Taxi Bookingen',
                'route' => 'admin.taxi.bookings.index',
                'icon' => 'ki-filled ki-calendar-tick',
                'permission' => 'taxi.bookings.view',
            ],
            [
                'title' => 'Voertuigen',
                'route' => 'admin.taxi.vehicles.index',
                'icon' => 'ki-filled ki-car',
                'permission' => 'taxi.vehicles.view',
            ],
        ];
    }

    public function registerPermissions(): array
    {
        return [
            'taxi.bookings.view',
            'taxi.bookings.create',
            'taxi.vehicles.view',
            'taxi.vehicles.manage',
        ];
    }
}
```

---

## Conclusie

Dit modulaire systeem biedt:
- ✅ Flexibele module structuur
- ✅ Auto-discovery van modules
- ✅ Installatie/activatie systeem
- ✅ Permission management per module
- ✅ Frontend website builder integratie
- ✅ Schaalbaar en onderhoudbaar

**Volgende stap**: Start met Fase 1 implementatie!
