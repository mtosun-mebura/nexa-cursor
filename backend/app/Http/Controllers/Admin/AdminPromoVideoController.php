<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PromoVideoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class AdminPromoVideoController extends Controller
{
    public function __construct(
        protected PromoVideoService $promoVideos
    ) {}

    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $props = array_merge(
            $this->promoVideos->defaultProps(),
            $request->session()->get('promo_video_props', [])
        );

        $composition = (string) $request->session()->get(
            'promo_video_composition',
            'NexaProductPromo'
        );

        return view('admin.promo-videos.index', [
            'props' => $props,
            'composition' => $composition,
            'compositions' => PromoVideoService::COMPOSITIONS,
            'playerUrl' => $this->promoVideos->playerUrl($props, $composition),
            'playerBuilt' => $this->promoVideos->isPlayerBuilt(),
            'projectInstalled' => $this->promoVideos->isProjectInstalled(),
            'rendered' => $this->promoVideos->listRenderedVideos(),
            'studioHint' => 'cd promo-videos && npm run studio',
        ]);
    }

    public function preview(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $data = $this->validatedProps($request);

        $request->session()->put('promo_video_props', $data['props']);
        $request->session()->put('promo_video_composition', $data['composition']);

        return redirect()
            ->route('admin.promo-videos.index')
            ->with('success', __('Preview bijgewerkt.'));
    }

    public function render(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $data = $this->validatedProps($request);

        @set_time_limit(900);

        try {
            $result = $this->promoVideos->render($data['composition'], $data['props']);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.promo-videos.index')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $request->session()->put('promo_video_props', $data['props']);
        $request->session()->put('promo_video_composition', $data['composition']);

        if (! $result['ok']) {
            return redirect()
                ->route('admin.promo-videos.index')
                ->with('error', __('Render mislukt.').' '.Str::limit($result['output'], 400));
        }

        return redirect()
            ->route('admin.promo-videos.index', ['saved' => 1])
            ->with('success', __('Video gerenderd: :file', ['file' => basename((string) $result['out_file'])]));
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin();

        $request->validate([
            'logo' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $url = $this->promoVideos->storeLogoUpload($request->file('logo'));

        return response()->json([
            'url' => $url,
        ]);
    }

    /**
     * @return array{composition: string, props: array<string, mixed>}
     */
    protected function validatedProps(Request $request): array
    {
        $validated = $request->validate([
            'composition' => ['required', 'string', 'in:NexaProductPromo,NexaAppTeaser'],
            'companyName' => ['required', 'string', 'max:120'],
            'productName' => ['required', 'string', 'max:120'],
            'tagline' => ['required', 'string', 'max:240'],
            'subtitle' => ['required', 'string', 'max:240'],
            'primaryColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accentColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'backgroundColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ctaText' => ['required', 'string', 'max:120'],
            'websiteUrl' => ['required', 'string', 'max:120'],
            'logoUrl' => ['nullable', 'string', 'max:500'],
            'showAppScreens' => ['nullable', 'boolean'],
        ]);

        $props = [
            'companyName' => $validated['companyName'],
            'productName' => $validated['productName'],
            'tagline' => $validated['tagline'],
            'subtitle' => $validated['subtitle'],
            'primaryColor' => $validated['primaryColor'],
            'accentColor' => $validated['accentColor'],
            'backgroundColor' => $validated['backgroundColor'],
            'ctaText' => $validated['ctaText'],
            'websiteUrl' => $validated['websiteUrl'],
            'logoUrl' => $validated['logoUrl'] ?: 'images/nexa-taxi-logo.png',
            'showAppScreens' => $request->boolean('showAppScreens'),
        ];

        return [
            'composition' => $validated['composition'],
            'props' => $props,
        ];
    }

    protected function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }
}
