<?php

use App\Models\Module;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

/**
 * Module-databases hebben geen `companies`-tabel: alleen een nullable company_id (logische FK naar centrale DB).
 */
return new class extends Migration
{
    public function up(): void
    {
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
            $table = 'website_pages';
            if (! Schema::connection($conn)->hasTable($table)) {
                continue;
            }
            if (Schema::connection($conn)->hasColumn($table, 'company_id')) {
                continue;
            }
            Schema::connection($conn)->table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable();
                $table->index(['company_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
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
            $table = 'website_pages';
            if (! Schema::connection($conn)->hasColumn($table, 'company_id')) {
                continue;
            }
            Schema::connection($conn)->table($table, function (Blueprint $table) {
                $table->dropIndex(['company_id', 'is_active']);
                $table->dropColumn('company_id');
            });
        }
    }
};
