<?php

namespace App\Http\Middleware;

use App\Modules\NexaTaxi\Services\TaxiContractPortalAccessService;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Services\ModuleDatabaseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTaxiContractPortal
{
    public function __construct(
        protected TaxiContractPortalAccessService $access,
        protected ModuleDatabaseService $moduleDb
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $conn = $this->moduleDb->getModuleConnectionName('taxi');
        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables($conn);

        $context = $this->access->resolveContext($conn, $user);
        if ($context === null) {
            return response()->json([
                'message' => 'Geen toegang tot het contractportaal.',
            ], 403);
        }

        $request->attributes->set('taxi_contract_conn', $conn);
        $request->attributes->set('taxi_contract_context', $context);
        $request->attributes->set('taxi_company_id', $context['company_id']);

        return $next($request);
    }
}
