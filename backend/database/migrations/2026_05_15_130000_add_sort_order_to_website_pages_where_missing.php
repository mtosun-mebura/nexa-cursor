<?php

use App\Models\Module;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

/**
 * Oudere module-databases kunnen website_pages zonder sort_order hebben;
 * dan unset de admin-controller sort_order en lijkt "Volgorde" niet opgeslagen.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = 'website_pages';
        if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'sort_order')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedInteger('sort_order')->default(0);
            });
        }

        $svc = app(ModuleDatabaseService::class);
        if (! $svc->supportsModuleDatabases()) {
            return;
        }
        foreach (Module::query()->where('installed', true)->pluck('name') as $moduleName) {
            if (! is_string($moduleName) || $moduleName === '') {
                continue;
            }
            try {
                $svc->registerConnection($moduleName);
            } catch (\Throwable) {
                continue;
            }
            $conn = $svc->getModuleConnectionName($moduleName);
            if (! Config::has("database.connections.{$conn}")) {
                continue;
            }
            if (! Schema::connection($conn)->hasTable($table)) {
                continue;
            }
            if (Schema::connection($conn)->hasColumn($table, 'sort_order')) {
                continue;
            }
            Schema::connection($conn)->table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedInteger('sort_order')->default(0);
            });
        }
    }

    public function down(): void
    {
        // Hoofddatabase: niet droppen — sort_order zit in de standaardschema's; alleen toevoegen in up() als die ooit ontbrak.
        $table = 'website_pages';
        $svc = app(ModuleDatabaseService::class);
        if (! $svc->supportsModuleDatabases()) {
            return;
        }
        foreach (Module::query()->where('installed', true)->pluck('name') as $moduleName) {
            if (! is_string($moduleName) || $moduleName === '') {
                continue;
            }
            try {
                $svc->registerConnection($moduleName);
            } catch (\Throwable) {
                continue;
            }
            $conn = $svc->getModuleConnectionName($moduleName);
            if (! Config::has("database.connections.{$conn}")) {
                continue;
            }
            if (! Schema::connection($conn)->hasColumn($table, 'sort_order')) {
                continue;
            }
            Schema::connection($conn)->table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('sort_order');
            });
        }
    }
};
