<?php

namespace App\Modules\Base;

/**
 * Base Module Class
 * Alle modules moeten deze class extenden
 */
abstract class Module
{
    /**
     * Module naam (uniek identifier, lowercase, kebab-case)
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
     * Module routes registreren
     * Wordt aangeroepen door ModuleServiceProvider
     */
    public function registerRoutes(): void
    {
        // Override in child class
    }

    /**
     * Module views pad
     */
    public function getViewsPath(): ?string
    {
        $reflection = new \ReflectionClass($this);
        $modulePath = dirname($reflection->getFileName());
        // Try Resources/views first (Laravel convention), then Views (legacy)
        $viewsPath = $modulePath . '/Resources/views';
        if (!is_dir($viewsPath)) {
            $viewsPath = $modulePath . '/Views';
        }
        
        return is_dir($viewsPath) ? $viewsPath : null;
    }

    /**
     * Module routes pad
     */
    public function getRoutesPath(): ?string
    {
        $reflection = new \ReflectionClass($this);
        $modulePath = dirname($reflection->getFileName());
        $routesPath = $modulePath . '/Routes';
        
        return is_dir($routesPath) ? $routesPath : null;
    }

    /**
     * Module menu items registreren
     * Return array van menu items
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
        $reflection = new \ReflectionClass($this);
        $modulePath = dirname($reflection->getFileName());
        $migrationsPath = $modulePath . '/Migrations';
        
        return is_dir($migrationsPath) ? $migrationsPath : null;
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
     * Module configuratie formulier view
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
