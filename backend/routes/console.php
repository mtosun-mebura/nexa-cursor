<?php

use App\Modules\NexaTaxi\Jobs\GenerateContractOccurrencesJob;
use App\Modules\NexaTaxi\Models\TransportRouteTemplate;
use App\Modules\NexaTaxi\Services\ContractOccurrenceGeneratorService;
use App\Services\ModuleDatabaseService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('taxi:generate-contract-occurrences {--days=14 : Aantal dagen vooruit}', function () {
    $days = max(1, (int) $this->option('days'));
    GenerateContractOccurrencesJob::dispatchSync($days);
    $this->info("Contract occurrences gegenereerd voor {$days} dagen vooruit.");
})->purpose('Genereer geplande contract-groepsritten (transport_occurrences + ride_requests)');

Artisan::command('taxi:resync-contract-schedule-times', function () {
    $moduleDb = app(ModuleDatabaseService::class);
    $moduleDb->ensureModuleStorageReady('taxi');
    $conn = $moduleDb->getModuleConnectionName('taxi');
    $service = app(ContractOccurrenceGeneratorService::class);
    $templateIds = TransportRouteTemplate::on($conn)->where('active', true)->pluck('id');
    $updated = 0;

    if ($templateIds->isEmpty()) {
        $this->warn('Geen actieve route-templates gevonden.');

        return;
    }

    foreach ($templateIds as $templateId) {
        $count = $service->resyncScheduleTimesForRouteTemplate($conn, (int) $templateId);
        $updated += $count;
        $this->line("Template #{$templateId}: {$count} rit(ten) bijgewerkt.");
    }

    $this->info("Contractrittijden gesynchroniseerd voor {$updated} ritten.");
})->purpose('Corrigeer geplande tijden van bestaande contract-groepsritten (Europe/Amsterdam)');

Schedule::job(new GenerateContractOccurrencesJob)
    ->dailyAt('04:00')
    ->name('taxi-generate-contract-occurrences')
    ->withoutOverlapping();
