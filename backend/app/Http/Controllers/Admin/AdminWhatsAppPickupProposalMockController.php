<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\WhatsAppPickupProposalMockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminWhatsAppPickupProposalMockController extends Controller
{
    public function __construct(
        protected WhatsAppPickupProposalMockService $mock
    ) {}

    public function index(): View
    {
        $this->ensureSuperAdmin();

        $selectedTenant = session('selected_tenant');
        $companyId = is_numeric($selectedTenant) ? (int) $selectedTenant : null;

        $rides = collect();
        $payload = ['rides' => [], 'has_seeded_rides' => false, 'fingerprint' => ''];
        $schemaReady = false;
        $schemaError = null;
        try {
            $rides = $this->mock->listMockRides();
            $serialized = $rides->map(fn (RideRequest $ride) => $this->mock->serializeMockRide($ride))->values()->all();
            $payload = [
                'rides' => $serialized,
                'has_seeded_rides' => $rides->contains(fn (RideRequest $ride) => $this->mock->isMockRide($ride)),
                'fingerprint' => implode('~', array_column($serialized, 'fingerprint')),
            ];
            $schemaReady = true;
        } catch (\Throwable $e) {
            $schemaError = $e->getMessage();
        }

        return view('admin.whatsapp-pickup-proposal-mock.index', [
            'mockAllowed' => $this->mock->inboundMockAllowed(),
            'environmentLabel' => $this->mock->environmentLabel(),
            'rides' => $rides,
            'ridesPayload' => $payload['rides'] ?? [],
            'listFingerprint' => $payload['fingerprint'] ?? '',
            'hasSeededRides' => $payload['has_seeded_rides'] ?? false,
            'schemaReady' => $schemaReady,
            'schemaError' => $schemaError,
            'companyId' => $companyId,
            'proposalLabels' => RideRequest::pickupProposalStatusLabels(),
            'rideStatusLabels' => RideRequest::statusLabels(),
        ]);
    }

    public function seed(): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $selectedTenant = session('selected_tenant');
        $companyId = is_numeric($selectedTenant) ? (int) $selectedTenant : null;
        $rides = $this->mock->seedMockRides($companyId);

        return redirect()
            ->route('admin.whatsapp-pickup-proposal-mock.index', ['saved' => 1])
            ->with('success', 'Testdata aangemaakt: twee openstaande voorstellen op hetzelfde nummer ('.WhatsAppPickupProposalMockService::MOCK_PHONE.'), rit #'.$rides[0]->id.' en #'.$rides[1]->id.'.');
    }

    public function simulate(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'ride_id' => ['required', 'integer'],
            'action' => ['required', 'in:accept,decline,remark'],
        ]);

        $result = $this->mock->simulateReply((int) $validated['ride_id'], (string) $validated['action']);
        $labels = RideRequest::pickupProposalStatusLabels();
        $after = $labels[$result['after']] ?? ($result['after'] ?: 'geen voorstel');
        $remark = trim((string) ($result['ride']->pickup_proposal_customer_remark ?? ''));

        $actionLabel = match ($validated['action']) {
            'accept' => 'Accepteren',
            'decline' => 'Weigeren',
            default => 'Opmerking',
        };

        $message = 'Gemockt WhatsApp-antwoord ('.$actionLabel.') verwerkt voor rit #'.$result['ride']->id.'. Voorstelstatus: '.$after.'.';
        if ($remark !== '') {
            $message .= ' Opmerking: '.$remark;
        }
        if (! $result['handled']) {
            $message = 'Webhook ontvangen, maar de ritstatus is niet gewijzigd (rit #'.$result['ride']->id.', status '.$after.').';
        }

        return redirect()
            ->route('admin.whatsapp-pickup-proposal-mock.index', ['saved' => 1])
            ->with('success', $message);
    }

    public function clear(): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $count = $this->mock->clearMockRides();

        return redirect()
            ->route('admin.whatsapp-pickup-proposal-mock.index', ['saved' => 1])
            ->with('success', $count === 0 ? 'Geen testdata om te wissen.' : $count.' testrit(ten) verwijderd.');
    }

    public function feed(): JsonResponse
    {
        $this->ensureSuperAdmin();

        try {
            $payload = $this->mock->listMockRidesPayload();
        } catch (\Throwable $e) {
            return response()->json([
                'rides' => [],
                'has_seeded_rides' => false,
                'fingerprint' => '',
                'mock_allowed' => $this->mock->inboundMockAllowed(),
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json(array_merge($payload, [
            'mock_allowed' => $this->mock->inboundMockAllowed(),
        ]));
    }

    public function destroySelected(Request $request): RedirectResponse|JsonResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'ride_ids' => ['required', 'array', 'min:1'],
            'ride_ids.*' => ['integer'],
        ]);

        $result = $this->mock->removeFromMockList($validated['ride_ids']);
        $parts = [];
        if ($result['deleted'] > 0) {
            $parts[] = $result['deleted'].' testdata-rit(ten) verwijderd';
        }
        if ($result['hidden'] > 0) {
            $parts[] = $result['hidden'].' chauffeur-voorstel(len) uit de lijst gehaald';
        }
        $message = $parts === []
            ? 'Geen geselecteerde voorstellen om te verwijderen.'
            : implode(', ', $parts).'.';

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'deleted' => $result['deleted'],
                'hidden' => $result['hidden'],
            ]);
        }

        return redirect()
            ->route('admin.whatsapp-pickup-proposal-mock.index', ['saved' => 1])
            ->with('success', $message);
    }

    protected function ensureSuperAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admins hebben toegang tot deze testpagina.');
        }
    }
}
