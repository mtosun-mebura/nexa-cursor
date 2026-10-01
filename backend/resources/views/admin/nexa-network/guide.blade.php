@extends('admin.layouts.app')

@section('title', 'NEXA Network — hoe het werkt')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-col items-start gap-3 pb-7.5">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-xl font-medium leading-none text-mono mb-0">NEXA Network — hoe het werkt</h1>
            <span class="kt-badge kt-badge-light">Alleen super-admin</span>
        </div>
        <p class="text-sm text-muted-foreground mb-0 max-w-3xl">
            Overzicht van flows én exacte plekken in de admin: partnerlijst, fee, payout, ritten en settlements.
            Fee nu {{ (int) $feePercent }}%. UI zet nooit zelf <code class="text-xs">settlement_eligible</code>.
        </p>
        <div class="flex flex-wrap gap-2">
            <a href="#instellingen" class="kt-btn kt-btn-primary kt-btn-sm">Naar instellingen &amp; acties</a>
            <a href="#modellen-overview" class="kt-btn kt-btn-outline kt-btn-sm">Ritmodellen</a>
            <a href="{{ route('admin.payment-flows.guide') }}" class="kt-btn kt-btn-outline kt-btn-sm">Uitleg betalingen</a>
        </div>
    </div>

    {{-- Waar doe ik wat? --}}
    <div class="kt-card w-full min-w-0 mb-5" id="instellingen">
        <div class="kt-card-header px-5 py-5">
            <h2 class="kt-card-title mb-0">Waar doe ik wat? — instellingen &amp; acties</h2>
            <p class="text-sm text-muted-foreground mb-0 mt-1">
                Volg de stappen in volgorde voor een werkende network-setup. Elke knop gaat direct naar de juiste admin-pagina.
            </p>
        </div>
        <div class="kt-card-content p-5">
            <div class="grid gap-4">
                @foreach($setupSteps as $index => $step)
                    <div class="rounded-lg border border-border p-4 min-w-0">
                        <div class="flex flex-wrap items-start gap-3 mb-3">
                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-semibold text-white shadow-sm">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-semibold text-foreground mb-0">{{ $step['title'] }}</h3>
                                <p class="text-xs text-muted-foreground mb-0 mt-1">
                                    <span class="font-medium text-foreground">Menu:</span> {{ $step['menu'] }}
                                </p>
                                <p class="text-xs text-muted-foreground mb-0 mt-1">{{ $step['why'] }}</p>
                            </div>
                        </div>
                        <ul class="list-disc ps-5 mb-3 text-sm text-muted-foreground space-y-1">
                            @foreach($step['do'] as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                        <div class="flex flex-wrap gap-2">
                            @foreach($step['links'] as $link)
                                <a href="{{ $link['url'] }}"
                                   class="kt-btn kt-btn-sm {{ !empty($link['primary']) ? 'kt-btn-primary' : 'kt-btn-outline' }}">
                                    {{ $link['label'] }}
                                    <i class="ki-filled ki-right ms-1.5 text-xs" aria-hidden="true"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Flow diagram --}}
    <div class="kt-card w-full min-w-0 mb-5" id="modellen-overview">
        <div class="kt-card-header px-5 py-5">
            <h2 class="kt-card-title mb-0">Flow in één oogopslag</h2>
        </div>
        <div class="kt-card-content p-5">
            <div class="grid gap-3 md:grid-cols-3 text-sm">
                <div class="rounded-lg border border-border p-4 min-w-0">
                    <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">1. Tenant</div>
                    <p class="mb-2 text-foreground font-medium">Eigen merk, eigen vloot</p>
                    <p class="text-muted-foreground mb-0 text-xs leading-relaxed">
                        Klant boekt bij Taxi A → chauffeur van A rijdt.<br>
                        <span class="font-mono">company_id = A</span> · fulfiller leeg · geen NEXA-fee per rit.
                    </p>
                </div>
                <div class="rounded-lg border border-border p-4 min-w-0">
                    <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">2. Marketplace</div>
                    <p class="mb-2 text-foreground font-medium">nexasuite.nl/boek</p>
                    <p class="text-muted-foreground mb-0 text-xs leading-relaxed">
                        NEXA matcht dichtstbijzijnde centrale → claimer wordt owner.<br>
                        Fee {{ (int) $feePercent }}% · badge <strong>NEXA Suite</strong>.
                    </p>
                </div>
                <div class="rounded-lg border border-border p-4 min-w-0">
                    <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">3. Network</div>
                    <p class="mb-2 text-foreground font-medium">Owner ≠ uitvoerder</p>
                    <p class="text-muted-foreground mb-0 text-xs leading-relaxed">
                        Taxi A houdt de klant; Taxi B rijdt.<br>
                        <span class="font-mono">company_id = A</span> · <span class="font-mono">fulfilling = B</span> · zelfde fee-%.
                    </p>
                </div>
            </div>

            <div class="mt-5 rounded-lg border border-dashed border-border p-4 text-sm">
                <p class="font-medium text-foreground mb-2">Settlement-keten (na afronden)</p>
                <ol class="list-decimal ps-5 mb-0 text-muted-foreground space-y-1 text-xs sm:text-sm">
                    <li><strong class="text-foreground">complete</strong> → status <code>completed</code> (claim)</li>
                    <li>Evaluatie → <code>hold</code> / <code>review</code> / <code>rejected</code></li>
                    <li>Cron of admin → <code>settlement_eligible</code></li>
                    <li>Pas dan billing / earnings / (later) payout</li>
                </ol>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <figure class="m-0 rounded-lg border border-border overflow-hidden bg-muted/20">
                    <button type="button"
                            class="nexa-rittype-thumb block w-full text-left p-0 border-0 bg-transparent cursor-zoom-in"
                            data-nexa-lightbox-src="{{ asset('images/nexa-taxi/nexa-rittype-tenant.jpg') }}"
                            data-nexa-lightbox-alt="1 Eigen taxibedrijf (Tenant)"
                            aria-label="Vergroot: Tenant-rit">
                        <img src="{{ asset('images/nexa-taxi/nexa-rittype-tenant.jpg') }}" alt="Tenant-rit" class="w-full h-auto object-cover pointer-events-none">
                    </button>
                    <figcaption class="px-3 py-2 text-xs text-muted-foreground">Tenant — klik om te vergroten</figcaption>
                </figure>
                <figure class="m-0 rounded-lg border border-border overflow-hidden bg-muted/20">
                    <button type="button"
                            class="nexa-rittype-thumb block w-full text-left p-0 border-0 bg-transparent cursor-zoom-in"
                            data-nexa-lightbox-src="{{ asset('images/nexa-taxi/nexa-rittype-marketplace.jpg') }}"
                            data-nexa-lightbox-alt="2 NEXA Marketplace"
                            aria-label="Vergroot: Marketplace-rit">
                        <img src="{{ asset('images/nexa-taxi/nexa-rittype-marketplace.jpg') }}" alt="Marketplace-rit" class="w-full h-auto object-cover pointer-events-none">
                    </button>
                    <figcaption class="px-3 py-2 text-xs text-muted-foreground">Marketplace — klik om te vergroten</figcaption>
                </figure>
                <figure class="m-0 rounded-lg border border-border overflow-hidden bg-muted/20">
                    <button type="button"
                            class="nexa-rittype-thumb block w-full text-left p-0 border-0 bg-transparent cursor-zoom-in"
                            data-nexa-lightbox-src="{{ asset('images/nexa-taxi/nexa-rittype-network.jpg') }}"
                            data-nexa-lightbox-alt="3 NEXA Network"
                            aria-label="Vergroot: Network-rit">
                        <img src="{{ asset('images/nexa-taxi/nexa-rittype-network.jpg') }}" alt="Network-rit" class="w-full h-auto object-cover pointer-events-none">
                    </button>
                    <figcaption class="px-3 py-2 text-xs text-muted-foreground">Network — klik om te vergroten</figcaption>
                </figure>
            </div>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="kt-card w-full min-w-0 mb-5">
        <div class="kt-card-header px-5 py-5">
            <h2 class="kt-card-title mb-0">Snelkoppelingen</h2>
        </div>
        <div class="kt-card-content p-5">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($quickLinks as $link)
                    <a href="{{ $link['url'] }}" class="flex flex-col gap-0.5 rounded-lg border border-border px-4 py-3 hover:bg-accent/40 transition-colors min-w-0">
                        <span class="text-sm font-medium text-primary">{{ $link['label'] }}</span>
                        <span class="text-xs text-muted-foreground">{{ $link['hint'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Sections --}}
    <div class="grid gap-5">
        @foreach($sections as $index => $section)
            <div class="kt-card w-full min-w-0" id="{{ $section['id'] }}">
                <div class="kt-card-header flex flex-wrap items-start justify-start gap-3 px-5 py-5">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold text-primary/45">
                        {{ $index + 1 }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="kt-card-title mb-0 text-base">{{ $section['title'] }}</h2>
                        <p class="text-sm text-muted-foreground mb-0 mt-1">{{ $section['summary'] }}</p>
                    </div>
                </div>
                <div class="kt-card-content p-5">
                    <ul class="flex flex-col gap-2 list-none p-0 m-0">
                        @foreach($section['body'] as $line)
                            <li class="flex items-start gap-2 text-sm text-foreground">
                                <i class="ki-filled ki-check-circle text-primary/70 text-base mt-0.5 shrink-0" aria-hidden="true"></i>
                                <span>{{ $line }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if(!empty($section['links']))
                        <div class="flex flex-wrap gap-2 mt-4">
                            @foreach($section['links'] as $link)
                                <a href="{{ $link['url'] }}" class="kt-btn kt-btn-outline kt-btn-sm">{{ $link['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <p class="text-xs text-muted-foreground mt-6 mb-0">
        Technische specificatie: <code class="text-xs">docs/NEXA_TAXI_NETWORK_IMPLEMENTATION.md</code>
        · fases <code class="text-xs">PHASE2</code>–<code class="text-xs">PHASE6</code> in <code class="text-xs">docs/</code>.
    </p>
</div>

{{-- Lightbox: click-to-enlarge voor rittype-infographics --}}
<div id="nexa-rittype-lightbox"
     class="hidden fixed inset-0 z-[100000] items-center justify-center p-4 sm:p-8"
     role="dialog"
     aria-modal="true"
     aria-labelledby="nexa-rittype-lightbox-title"
     hidden>
    <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-md" data-nexa-lightbox-dismiss></div>
    <div class="relative z-10 flex max-h-full w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-border bg-white shadow-2xl dark:bg-[#0b0f19]">
        <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3 sm:px-5">
            <h3 id="nexa-rittype-lightbox-title" class="text-sm font-semibold text-foreground mb-0 truncate">Rittype</h3>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-nexa-lightbox-dismiss aria-label="Sluiten">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="min-h-0 flex-1 overflow-auto p-3 sm:p-5">
            <img id="nexa-rittype-lightbox-img" src="" alt="" class="mx-auto max-h-[min(80vh,900px)] w-auto max-w-full h-auto object-contain">
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var lightbox = document.getElementById('nexa-rittype-lightbox');
    if (!lightbox) return;
    var img = document.getElementById('nexa-rittype-lightbox-img');
    var title = document.getElementById('nexa-rittype-lightbox-title');

    function openLightbox(src, alt) {
        if (!src || !img) return;
        img.src = src;
        img.alt = alt || '';
        if (title) title.textContent = alt || 'Rittype';
        lightbox.hidden = false;
        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.hidden = true;
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        document.body.style.overflow = '';
        if (img) img.removeAttribute('src');
    }

    document.querySelectorAll('[data-nexa-lightbox-src]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openLightbox(btn.getAttribute('data-nexa-lightbox-src'), btn.getAttribute('data-nexa-lightbox-alt'));
        });
    });

    lightbox.querySelectorAll('[data-nexa-lightbox-dismiss]').forEach(function (el) {
        el.addEventListener('click', closeLightbox);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !lightbox.hidden) closeLightbox();
    });
})();
</script>
@endpush
