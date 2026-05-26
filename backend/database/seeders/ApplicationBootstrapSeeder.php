<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Minimale eerste opzet: rollen/rechten + super admin + essentiële referentietabellen.
 * Gebruikt bij admin database-reset, Docker-entrypoint na migrate, en deploy-script.
 */
class ApplicationBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserRoleSeeder::class,
            ApplicationEssentialDataSeeder::class,
        ]);
    }
}
