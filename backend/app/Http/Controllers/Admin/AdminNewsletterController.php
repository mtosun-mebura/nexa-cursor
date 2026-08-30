<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterProspect;
use App\Services\NewsletterAiWriter;
use App\Services\NewsletterHtmlCompiler;
use App\Services\NewsletterLeadDiscoveryService;
use App\Services\NewsletterSendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminNewsletterController extends Controller
{
    public function __construct(
        protected NewsletterAiWriter $writer,
        protected NewsletterHtmlCompiler $compiler,
        protected NewsletterLeadDiscoveryService $discovery,
        protected NewsletterSendService $sender,
    ) {}

    public function index(): View
    {
        $this->ensureSuperAdmin();

        return view('admin.newsletters.index', [
            'campaigns' => NewsletterCampaign::query()->orderByDesc('id')->limit(50)->get(),
            'subscribedCount' => NewsletterProspect::query()->subscribed()->count(),
            'unsubscribedCount' => NewsletterProspect::query()->where('status', NewsletterProspect::STATUS_UNSUBSCRIBED)->count(),
            'prospectCount' => NewsletterProspect::query()->count(),
        ]);
    }

    public function create(): View
    {
        $this->ensureSuperAdmin();
        $blocks = $this->writer->defaultBlocks();

        return view('admin.newsletters.edit', [
            'campaign' => new NewsletterCampaign([
                'name' => 'Nieuwe nieuwsbrief',
                'subject' => $blocks['title'],
                'preview_text' => 'Ritten, chauffeurs en contracten in één systeem.',
                'blocks' => $blocks,
            ]),
            'previewHtml' => $this->compiler->compile($blocks),
            'stockImages' => $this->stockImages(),
            'isNew' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $data = $this->validatedCampaign($request);
        $campaign = NewsletterCampaign::query()->create($data + [
            'status' => NewsletterCampaign::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.newsletters.edit', $campaign)
            ->with('success', 'Nieuwsbrief opgeslagen.');
    }

    public function edit(NewsletterCampaign $campaign): View
    {
        $this->ensureSuperAdmin();
        $blocks = is_array($campaign->blocks) ? $campaign->blocks : $this->writer->defaultBlocks();

        return view('admin.newsletters.edit', [
            'campaign' => $campaign,
            'previewHtml' => $this->compiler->compile($blocks),
            'stockImages' => $this->stockImages(),
            'isNew' => false,
        ]);
    }

    public function update(Request $request, NewsletterCampaign $campaign): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $campaign->update($this->validatedCampaign($request));

        return redirect()
            ->route('admin.newsletters.edit', $campaign)
            ->with('success', 'Nieuwsbrief bijgewerkt.');
    }

    public function generate(Request $request)
    {
        $this->ensureSuperAdmin();
        $blocks = $this->writer->generate($request->string('brief')->toString());
        $meta = is_array($blocks['_meta'] ?? null) ? $blocks['_meta'] : [];
        unset($blocks['_meta']);

        return response()->json([
            'success' => true,
            'blocks' => $blocks,
            'subject' => $meta['subject'] ?? $blocks['title'],
            'preview_text' => $meta['preview_text'] ?? '',
            'name' => $meta['name'] ?? $blocks['title'],
            'preview_html' => $this->compiler->compile($blocks),
        ]);
    }

    public function preview(Request $request)
    {
        $this->ensureSuperAdmin();
        $features = is_array($request->input('features')) ? array_values($request->input('features')) : [];

        return response()->json([
            'preview_html' => $this->compiler->compile([
                'hero_image' => $request->string('hero_image')->toString(),
                'eyebrow' => $request->string('eyebrow')->toString(),
                'title' => $request->string('title')->toString() ?: 'NEXA Suite voor taxibedrijven',
                'intro' => $request->string('intro')->toString(),
                'features' => $features,
                'cta_label' => $request->string('cta_label')->toString() ?: 'Aanmelden via contact',
                'cta_url' => $request->string('cta_url')->toString() ?: '/contact',
            ]),
        ]);
    }

    public function prospects(): View
    {
        $this->ensureSuperAdmin();

        return view('admin.newsletters.prospects', [
            'prospects' => NewsletterProspect::query()->orderByDesc('id')->get(),
            'provinces' => config('newsletter.provinces'),
            'subscribedCount' => NewsletterProspect::query()->subscribed()->count(),
        ]);
    }

    public function discover(Request $request): RedirectResponse|StreamedResponse|JsonResponse
    {
        $this->ensureSuperAdmin();

        try {
            $validated = $this->validatedDiscover($request);
        } catch (ValidationException $e) {
            if ($this->wantsDiscoverStream($request)) {
                return response()->json([
                    'message' => $e->validator->errors()->first(),
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        }

        $options = [
            'query' => $validated['query'] ?? '',
            'city' => $validated['city'] ?? null,
            'radius_km' => $validated['radius_km'] ?? 50,
            'max_results' => $validated['max_results'] ?? (int) config('newsletter.max_discover_per_run', 300),
        ];

        if ($this->wantsDiscoverStream($request)) {
            return $this->streamDiscover($validated['branch'], $validated['provinces'], $options);
        }

        $result = $this->discovery->discover($validated['branch'], $validated['provinces'], $options);

        if ($result['created'] === 0) {
            return redirect()
                ->route('admin.newsletters.prospects')
                ->with('error', 'Geen bedrijven met een openbaar e-mailadres gevonden. Controleer de Google Maps-sleutel (Places) of probeer een andere plaats/branche.');
        }

        return redirect()
            ->route('admin.newsletters.prospects')
            ->with('success', $result['created'].' bedrijven toegevoegd met e-mail, adres en waar mogelijk telefoon ('.$result['skipped'].' overgeslagen, '.$result['scanned'].' bronnen bekeken).');
    }

    public function storeProspect(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:160'],
            'first_name' => ['nullable', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:40'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:160', 'unique:newsletter_prospects,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:180'],
            'city' => ['nullable', 'string', 'max:80'],
            'province' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        NewsletterProspect::query()->create([
            'company_name' => $validated['company_name'],
            'first_name' => $validated['first_name'] ?? null,
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'website' => $validated['website'] ?? null,
            'branch' => 'taxi',
            'source' => 'manual',
            'status' => NewsletterProspect::STATUS_SUBSCRIBED,
        ]);

        return redirect()
            ->route('admin.newsletters.prospects')
            ->with('success', 'Bedrijf toegevoegd aan de opt-inlijst.');
    }

    public function unsubscribeProspect(NewsletterProspect $prospect): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $prospect->unsubscribe();

        return redirect()
            ->route('admin.newsletters.prospects')
            ->with('success', $prospect->company_name.' is afgemeld.');
    }

    public function sendForm(): View
    {
        $this->ensureSuperAdmin();

        return view('admin.newsletters.send', [
            'campaigns' => NewsletterCampaign::query()->orderByDesc('id')->get(),
            'provinces' => config('newsletter.provinces'),
            'subscribedCount' => NewsletterProspect::query()->subscribed()->count(),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'campaign_id' => ['required', 'integer', 'exists:newsletter_campaigns,id'],
            'provinces' => ['nullable', 'array'],
            'provinces.*' => ['string', 'max:40'],
        ]);
        $campaign = NewsletterCampaign::query()->findOrFail($validated['campaign_id']);
        $stats = $this->sender->send($campaign, $validated['provinces'] ?? []);

        return redirect()
            ->route('admin.newsletters.send')
            ->with('success', 'Verzonden: '.$stats['sent'].' · mislukt: '.$stats['failed'].' · overgeslagen: '.$stats['skipped'].'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCampaign(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'subject' => ['required', 'string', 'max:160'],
            'preview_text' => ['nullable', 'string', 'max:180'],
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:160'],
            'intro' => ['required', 'string', 'max:2000'],
            'hero_image' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['required', 'string', 'max:80'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'features' => ['nullable', 'array'],
            'features.*.title' => ['nullable', 'string', 'max:80'],
            'features.*.text' => ['nullable', 'string', 'max:400'],
            'features.*.image' => ['nullable', 'string', 'max:500'],
        ]);

        return [
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'preview_text' => $validated['preview_text'] ?? null,
            'blocks' => [
                'hero_image' => $validated['hero_image'] ?? '',
                'eyebrow' => $validated['eyebrow'] ?? '',
                'title' => $validated['title'],
                'intro' => $validated['intro'],
                'features' => array_values($validated['features'] ?? []),
                'cta_label' => $validated['cta_label'],
                'cta_url' => $validated['cta_url'] ?: '/contact',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function stockImages(): array
    {
        $out = [];
        foreach (config('newsletter.stock_images', []) as $key => $path) {
            $out[$key] = asset($path);
        }

        return $out;
    }

    /**
     * @return array{branch: string, query?: string, city: ?string, radius_km?: int, max_results?: int, provinces: list<string>}
     */
    private function validatedDiscover(Request $request): array
    {
        $validated = $request->validate([
            'branch' => ['required', 'string', 'max:80'],
            'query' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'radius_km' => ['nullable', 'integer', 'min:5', 'max:100'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:500'],
            'provinces' => ['nullable', 'array'],
            'provinces.*' => ['string', 'max:40'],
        ]);

        $provinces = array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $validated['provinces'] ?? []
        )));
        $city = trim((string) ($validated['city'] ?? ''));

        if ($provinces === [] && $city === '') {
            throw ValidationException::withMessages([
                'city' => 'Kies minstens één provincie, of vul een plaats in. De straal wordt vanaf die plaats gebruikt.',
            ]);
        }

        $validated['provinces'] = $provinces;
        $validated['city'] = $city !== '' ? $city : null;

        return $validated;
    }

    /**
     * @param  list<string>  $provinces
     * @param  array{query: string, city: ?string, radius_km: int, max_results: int}  $options
     */
    private function streamDiscover(string $branch, array $provinces, array $options): StreamedResponse
    {
        return response()->stream(function () use ($branch, $provinces, $options): void {
            $this->flushDiscoverStream();

            $emit = function (array $event): void {
                echo json_encode($event, JSON_UNESCAPED_UNICODE)."\n";
                $this->flushDiscoverStream();
            };

            $emit([
                'type' => 'progress',
                'phase' => 'starting',
                'found' => 0,
                'saved' => 0,
                'skipped' => 0,
            ]);

            try {
                $options['on_progress'] = $emit;
                $result = $this->discovery->discover($branch, $provinces, $options);
                $emit([
                    'type' => 'complete',
                    'success' => $result['created'] > 0,
                    'found' => $result['scanned'],
                    'saved' => $result['created'],
                    'skipped' => $result['skipped'],
                    'message' => $result['created'] > 0
                        ? $result['created'].' bedrijven toegevoegd met e-mail, adres en waar mogelijk telefoon ('.$result['skipped'].' overgeslagen, '.$result['scanned'].' bronnen bekeken).'
                        : 'Geen bedrijven met een openbaar e-mailadres gevonden. Controleer de Google Maps-sleutel (Places) of probeer een andere plaats/branche.',
                    'redirect' => $result['created'] > 0
                        ? route('admin.newsletters.prospects', ['discovered' => $result['created']])
                        : null,
                ]);
            } catch (\Throwable $e) {
                $emit([
                    'type' => 'complete',
                    'success' => false,
                    'found' => 0,
                    'saved' => 0,
                    'message' => 'Zoeken mislukt: '.$e->getMessage(),
                ]);
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function wantsDiscoverStream(Request $request): bool
    {
        return $request->headers->get('X-Discover-Stream') === '1'
            || str_contains((string) $request->header('Accept'), 'application/x-ndjson');
    }

    private function flushDiscoverStream(): void
    {
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function ensureSuperAdmin(): void
    {
        if (! auth()->user()?->hasRole('super-admin')) {
            abort(403, 'Alleen een super-admin mag nieuwsbrieven beheren.');
        }
    }
}
