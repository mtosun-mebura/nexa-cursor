<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Bepaalt het relatieve pad (t.o.v. project-root = backend/) voor incrementele module-migraties:
 * database/migrations/modules/{slug}.
 */
final class ModuleMigrationPathResolver
{
    public static function pathForModule(string $moduleName): string
    {
        $canonical = strtolower(trim($moduleName));

        return 'database/migrations/modules/'.$canonical;
    }
}
