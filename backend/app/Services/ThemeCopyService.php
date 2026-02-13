<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Kopieert thema-bestanden uit backend/themas/ (buiten public) naar:
 * - public/frontend-themes/ (gedeeld)
 * - per module: app/Modules/{Name}/Resources/frontend/themes/
 */
class ThemeCopyService
{
    /** Bronmap themas (binnen app, buiten public). */
    protected string $themasSourcePath;

    /** Doelmap in backend public. */
    protected string $publicThemesPath;

    public function __construct()
    {
        $this->themasSourcePath = rtrim(config('app.themas_source_path', base_path('themas')), '/');
        $this->publicThemesPath = public_path('frontend-themes');
    }

    /**
     * Of de bronmap themas bestaat.
     */
    public function hasThemasSource(): bool
    {
        return File::isDirectory($this->themasSourcePath);
    }

    /**
     * Beschikbare thema-folders in de themas-bronmap (directe subdirs).
     */
    public function getAvailableThemeFolders(): array
    {
        if (!$this->hasThemasSource()) {
            return [];
        }
        $dirs = File::directories($this->themasSourcePath);
        $names = [];
        foreach ($dirs as $dir) {
            $names[] = basename($dir);
        }
        return $names;
    }

    /**
     * Kopieer alle thema's naar public/frontend-themes/ zodat ze direct bruikbaar zijn.
     */
    public function copyThemesToPublic(): array
    {
        $results = [];
        if (!$this->hasThemasSource()) {
            return $results;
        }
        if (!File::exists($this->publicThemesPath)) {
            File::makeDirectory($this->publicThemesPath, 0755, true);
        }
        foreach ($this->getAvailableThemeFolders() as $folder) {
            $slug = $this->folderToSlug($folder);
            $target = $this->publicThemesPath . '/' . $slug;
            $this->copyDirectory($this->themasSourcePath . '/' . $folder, $target);
            $results[] = $slug;
        }
        return $results;
    }

    /**
     * Kopieer alle thema's naar de frontend-map van een module (bij install/activatie direct zichtbaar).
     */
    public function copyThemesToModule(string $moduleName): array
    {
        $results = [];
        if (!$this->hasThemasSource()) {
            return $results;
        }
        $modulePath = $this->getModulePath($moduleName);
        if (!$modulePath || !File::isDirectory($modulePath)) {
            return $results;
        }
        $targetBase = $modulePath . '/Resources/frontend/themes';
        if (!File::exists($targetBase)) {
            File::makeDirectory($targetBase, 0755, true);
        }
        foreach ($this->getAvailableThemeFolders() as $folder) {
            $slug = $this->folderToSlug($folder);
            $target = $targetBase . '/' . $slug;
            $this->copyDirectory($this->themasSourcePath . '/' . $folder, $target);
            $results[] = $slug;
        }
        return $results;
    }

    /**
     * Kopieer één thema naar de module zodat de module zelfstandig werkt zonder gedeelde thema-bestanden.
     * Theme slug = bv. atom-v2, nextly-template, next-landing-vpn.
     */
    public function copySingleThemeToModule(string $themeSlug, string $moduleName): bool
    {
        $sourcePath = $this->getSourcePathForThemeSlug($themeSlug);
        if (!$sourcePath || !File::isDirectory($sourcePath)) {
            $sourcePath = $this->publicThemesPath . '/' . $themeSlug;
        }
        if (!File::isDirectory($sourcePath)) {
            return false;
        }
        $modulePath = $this->getModulePath($moduleName);
        if (!$modulePath || !File::isDirectory($modulePath)) {
            return false;
        }
        $targetBase = $modulePath . '/Resources/frontend/themes';
        if (!File::exists($targetBase)) {
            File::makeDirectory($targetBase, 0755, true);
        }
        $target = $targetBase . '/' . $themeSlug;
        $this->copyDirectory($sourcePath, $target);
        return true;
    }

    /**
     * Bronpad in de themas-bronmap voor een theme slug (uit FrontendTheme).
     */
    public function getSourcePathForThemeSlug(string $themeSlug): ?string
    {
        $map = [
            'atom-v2' => 'atom-v2',
            'nextly-template' => 'nextly-template-main',
            'next-landing-vpn' => 'next-landing-vpn-main',
        ];
        $folder = $map[$themeSlug] ?? $themeSlug;
        $path = $this->themasSourcePath . '/' . $folder;
        return File::isDirectory($path) ? $path : null;
    }

    /**
     * Pad naar het thema in public (voor gebruik in views).
     */
    public function getPublicThemeUrl(string $slug): string
    {
        return asset('frontend-themes/' . $slug);
    }

    protected function folderToSlug(string $folder): string
    {
        $slug = strtolower($folder);
        $slug = preg_replace('/-main$/', '', $slug);
        $slug = preg_replace('/[^a-z0-9_-]/', '-', $slug);
        return $slug ?: $folder;
    }

    protected function getModulePath(string $moduleName): ?string
    {
        $modulesPath = app_path('Modules');
        $directories = File::directories($modulesPath);
        foreach ($directories as $dir) {
            $name = basename($dir);
            if (strtolower($name) === strtolower($moduleName)) {
                return $dir;
            }
        }
        return null;
    }

    protected function copyDirectory(string $source, string $target): void
    {
        if (!File::isDirectory($source)) {
            return;
        }
        if (File::exists($target)) {
            File::deleteDirectory($target);
        }
        File::copyDirectory($source, $target);
    }
}
