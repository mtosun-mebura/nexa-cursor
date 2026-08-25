<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\Module;
use App\Models\User;
use App\Services\CentralWelcomePageService;
use App\Services\ModuleManager;
use App\Services\ModuleSchemaService;
use Database\Seeders\ApplicationBootstrapSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * Herstelt lokale dev-structuur na een per ongeluk migrate:fresh (modules, Taxi Royaal, marketing).
 * Wist geen tabellen; vult ontbrekende basisdata aan.
 */
class NexaRestoreLocalDevCommand extends Command
{
    protected $signature = 'nexa:restore-local-dev
                            {--skip-modules : Sla module-installatie over}';

    protected $description = 'Herstel lokale dev: modules, Taxi Royaal-tenant, super-admin en centrale marketingpagina\'s.';

    public function handle(ModuleManager $moduleManager, CentralWelcomePageService $central): int
    {
        $this->info('ApplicationBootstrapSeeder …');
        Artisan::call('db:seed', [
            '--class' => ApplicationBootstrapSeeder::class,
            '--force' => true,
        ]);
        $this->line(trim(Artisan::output()));

        if (! $this->option('skip-modules')) {
            foreach (['taxi', 'skillmatching'] as $moduleName) {
                $row = Module::query()->where('name', $moduleName)->first();
                if ($row === null || ! $row->installed) {
                    $this->info("Module installeren: {$moduleName}");
                    $moduleManager->installModule($moduleName);
                }
                if ($row === null || ! $row->active) {
                    $moduleManager->activateModule($moduleName);
                    $this->info("Module geactiveerd: {$moduleName}");
                }
            }
        }

        $company = Company::query()
            ->where('slug', 'taxi-royaal')
            ->orWhere('slug', 'taxiroyaal')
            ->orWhere('name', 'Taxi Royaal')
            ->first();

        if ($company === null) {
            $company = Company::query()->create([
                'name' => 'Taxi Royaal',
                'slug' => 'taxiroyaal',
                'is_active' => true,
                'industry' => 'Logistiek & Transport',
                'city' => 'Enschede',
                'description' => 'Lokale ontwikkeltenant (Taxi Royaal).',
            ]);
        } else {
            $company->fill([
                'name' => 'Taxi Royaal',
                'is_active' => true,
                'industry' => 'Logistiek & Transport',
                'city' => 'Enschede',
                'description' => 'Lokale ontwikkeltenant (Taxi Royaal).',
            ]);
            $company->save();
        }

        $taxiModule = Module::query()->where('name', 'taxi')->first();
        if ($taxiModule !== null) {
            $company->modules()->syncWithoutDetaching([
                $taxiModule->id => ['settings' => null],
            ]);
        }

        CompanyDomain::query()->updateOrCreate(
            ['host' => 'taxiroyaal.nexasuite.nl'],
            ['company_id' => $company->id, 'is_primary' => true]
        );

        User::query()
            ->where('email', 'like', '%@example.%')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super-admin'))
            ->delete();

        $this->info('Centrale marketingpagina\'s …');
        $pages = $central->ensureMarketingPagesExist();
        $this->line($pages->count().' centrale pagina\'s aanwezig.');

        Cache::forget('admin.tenant_switcher.companies');

        $this->newLine();
        $this->info('Lokale dev-structuur hersteld.');
        $this->line('Super-admin: '.ModuleSchemaService::SUPERADMIN_EMAIL);
        $this->line('Tenant: Taxi Royaal (id '.$company->id.', taxiroyaal.nexasuite.nl)');
        $this->warn('Operationele data (ritten, voertuigen, contractklanten, tenant-website-inhoud) is niet automatisch terug te zetten zonder database-backup of sync vanaf productie.');

        return self::SUCCESS;
    }
}
