<?php

namespace App\Modules\Template;

use App\Modules\Base\Module as BaseModule;

class Module extends BaseModule
{
    public function getName(): string
    {
        return 'template';
    }

    public function getDisplayName(): string
    {
        return 'Template';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Omschrijving';
    }

    /**
     * Schema voor deze module (gebruikt voor DB-connectie bij PostgreSQL).
     */
    public function getSchemaName(): ?string
    {
        return 'nexa_template';
    }

    public function getIcon(): string
    {
        return 'ki-filled ki-briefcase';
    }

    public function registerMenuItems(): array
    {
        return [];
    }

    public function registerPermissions(): array
    {
        return [];
    }
}
