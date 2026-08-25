<?php

namespace App\Modules\NexaTaxi\Jobs;

use App\Modules\NexaTaxi\Services\ContractInvoiceService;
use App\Models\Company;
use App\Services\CompanyEntitlementService;
use App\Services\ModuleDatabaseService;
use App\Support\TenantPackageCapability;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateContractInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ModuleDatabaseService $moduleDb, ContractInvoiceService $invoices): void
    {
        $moduleDb->ensureModuleStorageReady('taxi');
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $period = $invoices->previousBillingPeriod();
        $contracts = $invoices->contractsDueForInvoicingToday($conn);
        $generated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($contracts as $contract) {
            if (app(\App\Services\NexaDemoAccountService::class)->isDemoCompanyId((int) ($contract->company_id ?? 0))) {
                $skipped++;

                continue;
            }
            $company = Company::query()->find((int) ($contract->company_id ?? 0));
            if (! app(CompanyEntitlementService::class)->allows($company, TenantPackageCapability::MONTHLY_INVOICE_SEPA)) {
                $skipped++;

                continue;
            }
            if ($invoices->findInvoiceForContractPeriod($contract->id, $period)) {
                $skipped++;

                continue;
            }

            try {
                $invoices->generateMonthlyInvoice($conn, $contract, $period, [
                    'send_email' => true,
                    'status' => 'sent',
                ]);
                $generated++;
            } catch (\Throwable $e) {
                $errors++;
                Log::warning('Contractfactuur generatie mislukt.', [
                    'transport_contract_id' => $contract->id,
                    'period' => $period,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('GenerateContractInvoicesJob voltooid.', [
            'period' => $period,
            'generated' => $generated,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }
}
