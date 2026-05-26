<?php

namespace App\Services;

use App\Models\Module;

/**
 * Bepaalt de "actieve module"-context voor o.a. database, uploads en thema-assets.
 *
 * Zodra een module actief is (het actieve thema hoort bij die module, of de eerste actieve module):
 * - Nieuwe data (website-pagina's, etc.) → module-eigen database
 * - Uploads (hero, footer, documenten, media) → module-eigen map (storage: modules/{slug}/...)
 * - Thema-styling kan uit de module-eigen assets/thema-map komen
 *
 * Zonder actieve module werkt Nexa op de hoofddatabase en gedeelde storage.
 */
class ModuleContextService
{
    public function __construct(
        protected WebsiteBuilderService $websiteBuilder,
        protected ModuleDatabaseService $moduleDb
    ) {}

    /**
     * De modulenaam (Module.name) van de actieve module voor frontend/branding.
     * Null als er geen actieve module is of geen thema gekoppeld.
     */
    public function getActiveModuleName(): ?string
    {
        return $this->websiteBuilder->getActiveModuleName();
    }

    /**
     * Of de gegeven modulenaam een eigen database heeft (en dus "zelfstandig" werkt).
     */
    public function moduleHasOwnDatabase(?string $moduleName): bool
    {
        if ($moduleName === null || $moduleName === '') {
            return false;
        }
        if (!$this->moduleDb->supportsModuleDatabases()) {
            return false;
        }
        $module = Module::where('installed', true)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($moduleName))])
            ->first();
        return $module !== null;
    }

    /**
     * Upload-map prefix voor de actieve module: "modules/{slug}/" of leeg voor hoofddatabase.
     * Gebruik bij bv. website-uploads: $prefix . 'website/hero', $prefix . 'website/documents'.
     */
    public function getUploadPathPrefix(?string $moduleName = null): string
    {
        $name = $moduleName ?? $this->getActiveModuleName();
        if ($name === null || $name === '') {
            return '';
        }
        if (!$this->moduleDb->supportsModuleDatabases()) {
            return '';
        }
        $slug = $this->moduleDb->getModuleUploadSlug($name);
        return 'modules/' . $slug . '/';
    }

    /**
     * Resolve module name from request (query of input), validated against installed modules.
     * Voor upload-endpoints: callers kunnen ?module= of form field module meesturen.
     */
    public function getModuleNameFromRequest(\Illuminate\Http\Request $request): ?string
    {
        $raw = $request->query('module') ?? $request->input('module');
        if ($raw === null || !is_string($raw) || trim($raw) === '') {
            return null;
        }
        $name = trim($raw);
        $module = Module::where('installed', true)->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        return $module ? $module->name : null;
    }
}
