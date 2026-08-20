<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NexaPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNexaPricingController extends Controller
{
    public function __construct(
        protected NexaPricingService $pricing
    ) {}

    public function edit(): View
    {
        $this->ensureSuperAdmin();
        $this->pricing->syncWebsitePage();
        $pricing = $this->pricing->get();

        return view('admin.nexa-pricing.edit', [
            'pricing' => $pricing,
            'websitePageUrl' => url('/prijzen'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $valid = $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'vat_note' => ['nullable', 'string', 'max:255'],
            'packages' => ['required', 'array', 'min:1'],
            'packages.*.name' => ['required', 'string', 'max:80'],
            'packages.*.audience' => ['nullable', 'string', 'max:160'],
            'packages.*.price' => ['required', 'string', 'max:20'],
            'packages.*.offer' => ['nullable', 'string', 'max:80'],
            'packages.*.free_months' => ['nullable', 'integer', 'min:0', 'max:24'],
            'packages.*.period' => ['nullable', 'string', 'max:40'],
            'packages.*.badge' => ['nullable', 'string', 'max:40'],
            'packages.*.highlighted' => ['nullable'],
            'packages.*.cta_text' => ['nullable', 'string', 'max:80'],
            'packages.*.cta_url' => ['nullable', 'string', 'max:160'],
            'packages.*.features' => ['nullable', 'array'],
            'packages.*.features.*' => ['nullable', 'string', 'max:255'],
            'packages.*.features_text' => ['nullable', 'string', 'max:4000'],
            'website.title' => ['nullable', 'string', 'max:120'],
            'website.price_prefix' => ['nullable', 'string', 'max:40'],
            'website.price_label' => ['required', 'string', 'max:20'],
            'website.offer' => ['nullable', 'string', 'max:80'],
            'website.period' => ['nullable', 'string', 'max:40'],
            'website.subtitle' => ['nullable', 'string', 'max:1000'],
            'website.cta_text' => ['nullable', 'string', 'max:80'],
            'website.cta_url' => ['nullable', 'string', 'max:160'],
            'website.features' => ['nullable', 'array'],
            'website.features.*' => ['nullable', 'string', 'max:255'],
            'website.features_text' => ['nullable', 'string', 'max:4000'],
            'addons' => ['nullable', 'array'],
            'addons.*.name' => ['nullable', 'string', 'max:80'],
            'addons.*.price' => ['nullable', 'string', 'max:80'],
            'addons.*.description' => ['nullable', 'string', 'max:255'],
        ], [
            'packages.required' => 'Voeg minstens één maandpakket toe.',
            'packages.min' => 'Voeg minstens één maandpakket toe.',
            'packages.*.name.required' => 'Elk pakket heeft een naam nodig.',
            'packages.*.price.required' => 'Elk pakket heeft een maandprijs nodig.',
            'website.price_label.required' => 'Vul de eenmalige websiteprijs in.',
        ]);

        $this->pricing->save($valid);

        return redirect()
            ->route('admin.nexa-pricing.edit')
            ->with('success', 'Prijzen opgeslagen. De websitepagina /prijzen is bijgewerkt.');
    }

    private function ensureSuperAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Alleen super-admins hebben toegang tot de prijzen.');
        }
    }
}
