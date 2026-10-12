<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncKnvTaxiMembersJob;
use App\Models\TaxiDiscoveryRun;
use App\Models\TaxiProspect;
use App\Models\TaxiProspectInvitation;
use App\Models\TaxiProspectOptOut;
use App\Services\TaxiPartnerFinder\TaxiPartnerDiscoveryService;
use App\Services\TaxiPartnerFinder\TaxiProspectNewsletterBridge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminTaxiPartnerFinderController extends Controller
{
    public function __construct(
        protected TaxiPartnerDiscoveryService $discovery,
        protected TaxiProspectNewsletterBridge $newsletterBridge,
    ) {}

    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $query = TaxiProspect::query()->with('sources')->orderByDesc('id');

        if ($request->filled('q')) {
            $query->search($request->string('q')->toString());
        }
        if ($request->filled('province')) {
            $query->where('province', $request->string('province')->toString());
        }
        if ($request->filled('knv')) {
            $query->where('knv_member_status', $request->string('knv')->toString());
        }
        if ($request->filled('source')) {
            $source = $request->string('source')->toString();
            $query->whereHas('sources', fn ($q) => $q->where('source_type', $source));
        }
        if ($request->filled('lead_status')) {
            $query->where('lead_status', $request->string('lead_status')->toString());
        }
        if ($request->string('has_email')->toString() === '1') {
            $query->whereNotNull('email')->where('email', '!=', '');
        }
        if ($request->string('has_email')->toString() === '0') {
            $query->where(fn ($q) => $q->whereNull('email')->orWhere('email', ''));
        }

        $stats = [
            'total' => TaxiProspect::query()->count(),
            'knv' => TaxiProspect::query()->where('knv_member_status', TaxiProspect::KNV_MEMBER)->count(),
            'missing_email' => TaxiProspect::query()->where(fn ($q) => $q->whereNull('email')->orWhere('email', ''))->count(),
            'contact_ready' => TaxiProspect::query()->where('lead_status', TaxiProspect::LEAD_CONTACT_READY)->count(),
            'by_province' => TaxiProspect::query()
                ->selectRaw('province, count(*) as c')
                ->whereNotNull('province')
                ->groupBy('province')
                ->orderByDesc('c')
                ->pluck('c', 'province'),
        ];

        return view('admin.taxi-partner-finder.index', [
            'prospects' => $query->paginate(50)->withQueryString(),
            'stats' => $stats,
            'provinces' => config('newsletter.provinces', []),
            'runs' => TaxiDiscoveryRun::query()->orderByDesc('id')->limit(10)->get(),
            'filters' => $request->only(['q', 'province', 'knv', 'source', 'lead_status', 'has_email']),
            'leadStatuses' => config('taxi-partner-finder.lead_statuses', []),
        ]);
    }

    public function show(TaxiProspect $prospect): View
    {
        $this->ensureSuperAdmin();
        $prospect->load(['sources', 'serviceAreas', 'invitations', 'contacts']);

        return view('admin.taxi-partner-finder.show', [
            'prospect' => $prospect,
            'provinces' => config('newsletter.provinces', []),
            'leadStatuses' => config('taxi-partner-finder.lead_statuses', []),
        ]);
    }

    public function update(Request $request, TaxiProspect $prospect): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:180'],
            'trade_name' => ['nullable', 'string', 'max:180'],
            'kvk_number' => ['nullable', 'regex:/^[0-9]{8}$/'],
            'street' => ['nullable', 'string', 'max:160'],
            'house_number' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:16'],
            'city' => ['nullable', 'string', 'max:80'],
            'province' => ['nullable', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'website' => ['nullable', 'string', 'max:255'],
            'lead_status' => ['required', 'in:'.implode(',', config('taxi-partner-finder.lead_statuses', []))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $prospect->fill($validated);
        $prospect->markContactReadyIfPossible();
        $prospect->save();

        return redirect()
            ->route('admin.taxi-partner-finder.show', $prospect)
            ->with('success', 'Prospect bijgewerkt.');
    }

    public function syncKnv(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'async' => ['nullable', 'boolean'],
            'push_newsletter' => ['nullable', 'boolean'],
        ]);
        $push = (bool) ($validated['push_newsletter'] ?? false);

        if ($request->boolean('async')) {
            SyncKnvTaxiMembersJob::dispatch($request->user()?->id, $push);

            return redirect()
                ->route('admin.taxi-partner-finder.index')
                ->with('success', 'KNV-synchronisatie staat in de wachtrij.');
        }

        $result = $this->discovery->syncKnv($request->user()?->id, $push);

        return redirect()
            ->route('admin.taxi-partner-finder.index')
            ->with(
                'success',
                sprintf(
                    'KNV-sync klaar: %d nieuw, %d bijgewerkt, %d duplicaat/fuzzy, %d mislukt.',
                    $result['created'],
                    $result['updated'],
                    $result['duplicates'],
                    $result['failed']
                )
            );
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'push_newsletter' => ['nullable', 'boolean'],
        ]);

        $contents = file_get_contents($validated['csv']->getRealPath() ?: '');
        if ($contents === false || trim($contents) === '') {
            return redirect()
                ->route('admin.taxi-partner-finder.index')
                ->with('error', 'CSV-bestand is leeg.');
        }

        $result = $this->discovery->importKnvCsv(
            $contents,
            $request->user()?->id,
            (bool) ($validated['push_newsletter'] ?? false)
        );

        return redirect()
            ->route('admin.taxi-partner-finder.index')
            ->with(
                'success',
                sprintf(
                    'CSV-import klaar: %d nieuw, %d bijgewerkt, %d duplicaat/fuzzy, %d mislukt.',
                    $result['created'],
                    $result['updated'],
                    $result['duplicates'],
                    $result['failed']
                )
            );
    }

    public function pushNewsletter(TaxiProspect $prospect): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $row = $this->newsletterBridge->pushIfEligible($prospect);
        if (! $row) {
            return redirect()
                ->back()
                ->with('error', 'Geen geldig e-mailadres of prospect is afgemeld.');
        }

        return redirect()
            ->back()
            ->with('success', 'Toegevoegd aan nieuwsbrief-klantenlijst: '.$row->email);
    }

    public function prepareInvitations(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:taxi_prospects,id'],
        ]);

        $count = 0;
        foreach ($validated['ids'] as $id) {
            $prospect = TaxiProspect::query()->find($id);
            if (! $prospect || $prospect->lead_status === TaxiProspect::LEAD_OPTED_OUT) {
                continue;
            }
            TaxiProspectInvitation::query()->create([
                'taxi_prospect_id' => $prospect->id,
                'status' => TaxiProspectInvitation::STATUS_DRAFT,
                'channel' => 'newsletter',
                'prepared_by' => $request->user()?->id,
                'prepared_at' => now(),
                'notes' => 'Handmatig voorbereid — geen automatische mailing.',
            ]);
            if ($prospect->lead_status === TaxiProspect::LEAD_CONTACT_READY
                || $prospect->lead_status === TaxiProspect::LEAD_DISCOVERED) {
                $prospect->lead_status = 'invited';
                $prospect->save();
            }
            $count++;
        }

        return redirect()
            ->route('admin.taxi-partner-finder.index')
            ->with('success', $count.' uitnodiging(en) als concept voorbereid (geen automatische e-mail).');
    }

    public function optOut(Request $request, TaxiProspect $prospect): RedirectResponse
    {
        $this->ensureSuperAdmin();
        TaxiProspectOptOut::query()->create([
            'taxi_prospect_id' => $prospect->id,
            'email' => $prospect->email,
            'phone' => $prospect->phone,
            'reason' => $request->string('reason')->toString() ?: 'admin',
            'opted_out_at' => now(),
        ]);
        $prospect->lead_status = TaxiProspect::LEAD_OPTED_OUT;
        $prospect->save();

        return redirect()
            ->route('admin.taxi-partner-finder.show', $prospect)
            ->with('success', 'Prospect gemarkeerd als opted-out.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->ensureSuperAdmin();
        $filename = 'taxi-prospects-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, [
                'id', 'company_name', 'kvk_number', 'email', 'phone', 'website',
                'street', 'house_number', 'postal_code', 'city', 'province',
                'knv_member_status', 'lead_status', 'sources',
            ], ';');

            $query = TaxiProspect::query()->with('sources')->orderBy('id');
            if ($request->filled('province')) {
                $query->where('province', $request->string('province')->toString());
            }
            $query->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $p) {
                    fputcsv($out, [
                        $p->id,
                        $p->company_name,
                        $p->kvk_number,
                        $p->email,
                        $p->phone,
                        $p->website,
                        $p->street,
                        $p->house_number,
                        $p->postal_code,
                        $p->city,
                        $p->province,
                        $p->knv_member_status,
                        $p->lead_status,
                        $p->sources->pluck('source_type')->implode('|'),
                    ], ';');
                }
            });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }
}
