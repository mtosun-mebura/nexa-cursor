<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RidePlatformSettlement;
use App\Services\ModuleDatabaseService;
use App\Services\Payout\PlatformRidePayoutService;
use App\Services\Payout\PlatformRideSettlementService;
use App\Support\NexaMarketplaceFeeCopy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPaymentFlowsGuideController extends Controller
{
    public function guide(): View
    {
        $this->authorizeSuperAdmin();

        $fee = NexaMarketplaceFeeCopy::percent();
        $ownerShare = (int) config('nexa_payout.network_owner_share_of_net_percent', 15);
        $fulfillerShare = (int) config('nexa_payout.network_fulfiller_share_of_net_percent', 85);

        $exampleGross = 100.00;
        $exampleFee = round($exampleGross * ($fee / 100), 2);
        $exampleNet = round($exampleGross - $exampleFee, 2);
        $exampleOwner = round($exampleNet * ($ownerShare / 100), 2);
        $exampleFulfiller = round($exampleNet - $exampleOwner, 2);

        return view('admin.payment-flows.guide', [
            'feePercent' => $fee,
            'ownerSharePercent' => $ownerShare,
            'fulfillerSharePercent' => $fulfillerShare,
            'platformCollectEnabled' => (bool) config('nexa_payout.platform_collect_enabled', true),
            'example' => [
                'gross' => $exampleGross,
                'fee' => $exampleFee,
                'net' => $exampleNet,
                'owner' => $exampleOwner,
                'fulfiller' => $exampleFulfiller,
            ],
        ]);
    }

    public function settlements(Request $request): View
    {
        $this->authorizeSuperAdmin();

        $status = $request->string('status')->toString();
        $query = RidePlatformSettlement::query()
            ->with(['ownerCompany', 'fulfillerCompany'])
            ->orderByDesc('id');

        if ($status !== '' && array_key_exists($status, RidePlatformSettlement::statusLabels())) {
            $query->where('status', $status);
        }

        return view('admin.payment-flows.settlements', [
            'settlements' => $query->paginate(30)->withQueryString(),
            'statusFilter' => $status,
            'statusLabels' => RidePlatformSettlement::statusLabels(),
        ]);
    }

    public function processQueue(
        PlatformRideSettlementService $settlements,
        PlatformRidePayoutService $payouts,
        ModuleDatabaseService $moduleDb,
    ): RedirectResponse {
        $this->authorizeSuperAdmin();

        $sync = ['created' => 0, 'skipped' => 0];
        try {
            $moduleDb->ensureModuleStorageReady('taxi');
            $conn = $moduleDb->getModuleConnectionName('taxi');
            $sync = $settlements->syncEligibleRides($conn);
        } catch (\Throwable $e) {
            // Module DB may be unavailable in some environments; payouts can still run.
        }

        $stats = $payouts->processPending();

        return back()->with(
            'success',
            "Ledgers nieuw: {$sync['created']}. Payouts: verwerkt {$stats['processed']}, betaald {$stats['paid']}, mislukt {$stats['failed']}, handmatig {$stats['manual']}."
        );
    }

    public function retryPayout(RidePlatformSettlement $settlement, PlatformRidePayoutService $payouts): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $status = $payouts->processOne($settlement->fresh());

        return back()->with(
            'success',
            'Handmatige payout-poging: '.$settlement->fresh()->statusLabel().' ('.$status.').'
        );
    }

    public function forcePaidOut(RidePlatformSettlement $settlement, PlatformRidePayoutService $payouts): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $payouts->processOne($settlement->fresh(), forceManualSucceed: true);

        return back()->with('success', 'Settlement geforceerd als uitbetaald (naslag). Alleen gebruiken als de overboeking buiten NEXA al is gedaan.');
    }

    private function authorizeSuperAdmin(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->hasRole('super-admin')) {
            abort(403);
        }
    }
}
