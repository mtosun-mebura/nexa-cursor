<?php

use App\Models\Module;
use App\Models\WebsitePage;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addAndBackfill((string) config('database.default'));

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
            } catch (Throwable) {
                continue;
            }
            $conn = $svc->getModuleConnectionName($moduleName);
            if (! Config::has("database.connections.{$conn}")) {
                continue;
            }
            $this->addAndBackfill($conn);
        }
    }

    public function down(): void
    {
        $this->dropColumn((string) config('database.default'));

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
            } catch (Throwable) {
                continue;
            }
            $conn = $svc->getModuleConnectionName($moduleName);
            if (! Config::has("database.connections.{$conn}")) {
                continue;
            }
            $this->dropColumn($conn);
        }
    }

    private function addAndBackfill(string $connection): void
    {
        $table = 'website_pages';
        if (! Schema::connection($connection)->hasTable($table)) {
            return;
        }
        if (! Schema::connection($connection)->hasColumn($table, 'menu_title')) {
            Schema::connection($connection)->table($table, function (Blueprint $blueprint) {
                $blueprint->string('menu_title', 80)->nullable();
            });
        }

        $rows = DB::connection($connection)->table($table)->select(['id', 'title', 'slug', 'page_type', 'menu_title'])->get();
        foreach ($rows as $row) {
            $existing = trim((string) ($row->menu_title ?? ''));
            if ($existing !== '') {
                continue;
            }
            $menu = WebsitePage::defaultMenuTitleFromPage(
                (string) ($row->title ?? ''),
                (string) ($row->page_type ?? 'custom'),
                (string) ($row->slug ?? '')
            );
            DB::connection($connection)->table($table)->where('id', $row->id)->update(['menu_title' => $menu]);
        }
    }

    private function dropColumn(string $connection): void
    {
        $table = 'website_pages';
        if (! Schema::connection($connection)->hasTable($table)
            || ! Schema::connection($connection)->hasColumn($table, 'menu_title')) {
            return;
        }
        Schema::connection($connection)->table($table, function (Blueprint $blueprint) {
            $blueprint->dropColumn('menu_title');
        });
    }
};
