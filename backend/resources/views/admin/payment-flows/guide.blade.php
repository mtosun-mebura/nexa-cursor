@extends('admin.layouts.app')

@section('title', 'Uitleg betalingen')

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-col items-start gap-3 pb-7.5">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-xl font-medium leading-none text-mono mb-0">Uitleg betalingen</h1>
            <span class="kt-badge kt-badge-light">Alleen super-admin</span>
        </div>
        <p class="text-sm text-muted-foreground mb-0 max-w-3xl">
            Hoe marketplace- en network-ritten financieel lopen: klant betaalt NEXA, fee blijft bij NEXA,
            netto gaat automatisch naar de taxipartij(en). Fee-PDF’s zijn naslag — geen openstaande vordering.
            Platform-collect staat nu
            <strong class="text-foreground">{{ $platformCollectEnabled ? 'aan' : 'uit' }}</strong>
            (fee {{ (int) $feePercent }}%).
        </p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.payment-flows.settlements') }}" class="kt-btn kt-btn-primary kt-btn-sm">Settlement-wachtrij</a>
            <a href="{{ route('admin.nexa-suite-bookings.settings') }}" class="kt-btn kt-btn-outline kt-btn-sm">Fee %</a>
            <a href="{{ route('admin.payout-identities.index') }}" class="kt-btn kt-btn-outline kt-btn-sm">Payout onboarding</a>
            <a href="{{ route('admin.nexa-network.guide') }}" class="kt-btn kt-btn-outline kt-btn-sm">NEXA Network guide</a>
        </div>
    </div>

    {{-- Kernprincipe --}}
    <div class="kt-card w-full min-w-0 mb-5">
        <div class="kt-card-header px-5 py-5">
            <h2 class="kt-card-title mb-0">Kernprincipe: platform collect</h2>
        </div>
        <div class="kt-card-content p-5">
            <ol class="list-decimal ps-5 mb-0 text-sm text-muted-foreground space-y-2">
                <li><strong class="text-foreground">Klant betaalt NEXA</strong> (platform-incasso via Mollie Connect — live wiring volgt op partner-credentials).</li>
                <li>Rit wordt afgerond → settlement-gate (hold/review) → pas daarna <code class="text-xs">settlement_eligible</code>.</li>
                <li>Cron maakt een <strong class="text-foreground">ledger</strong>: bruto − NEXA-fee = netto.</li>
                <li>Cron (of handmatige knop) betaalt netto uit naar verified payout-identity van de taxi(’s).</li>
                <li>Maandelijkse “boekingsfactuur” = <strong class="text-foreground">specificatie / naslag</strong> van reeds ingehouden fee, geen inningsfactuur.</li>
            </ol>
        </div>
    </div>

    {{-- Marketplace --}}
    <div class="kt-card w-full min-w-0 mb-5">
        <div class="kt-card-header px-5 py-5">
            <h2 class="kt-card-title mb-0">1. Marketplace (nexasuite.nl)</h2>
        </div>
        <div class="kt-card-content p-5">
            <p class="text-sm text-muted-foreground mb-4">
                Klant boekt op nexasuite.nl. NEXA matcht een centrale (claimer). Die centrale is owner én uitvoerder.
            </p>
            <div class="rounded-lg border border-border bg-muted/20 p-4 mb-4 overflow-x-auto">
                <pre class="text-xs sm:text-sm mb-0 font-mono text-foreground leading-relaxed whitespace-pre">Klant ──€{{ number_format($example['gross'], 2, ',', '.') }}──► NEXA
                              │
                              ├─ Fee {{ (int) $feePercent }}% = €{{ number_format($example['fee'], 2, ',', '.') }}  (blijft bij NEXA)
                              │
                              └─ Netto €{{ number_format($example['net'], 2, ',', '.') }} ──► Claimende taxi (100% netto)</pre>
            </div>
            <div class="grid gap-3 sm:grid-cols-3 text-sm">
                <div class="rounded-lg border border-border p-3">
                    <div class="text-xs uppercase tracking-wide text-muted-foreground mb-1">Voorbeeld rit</div>
                    <p class="mb-0 font-medium text-foreground">€{{ number_format($example['gross'], 2, ',', '.') }} bruto</p>
                </div>
                <div class="rounded-lg border border-border p-3">
                    <div class="text-xs uppercase tracking-wide text-muted-foreground mb-1">NEXA fee</div>
                    <p class="mb-0 font-medium text-foreground">€{{ number_format($example['fee'], 2, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-border p-3">
                    <div class="text-xs uppercase tracking-wide text-muted-foreground mb-1">Uit naar taxi</div>
                    <p class="mb-0 font-medium text-foreground">€{{ number_format($example['net'], 2, ',', '.') }}</p>
                </div>
            </div>
            <p class="text-xs text-muted-foreground mt-4 mb-0">
                Model in ledger: <code class="text-xs">marketplace</code>. Geen aparte fee-incasso achteraf → geen dunning-loop op provisie.
            </p>
        </div>
    </div>

    {{-- Network --}}
    <div class="kt-card w-full min-w-0 mb-5">
        <div class="kt-card-header px-5 py-5">
            <h2 class="kt-card-title mb-0">2. Network (owner ≠ uitvoerder)</h2>
        </div>
        <div class="kt-card-content p-5">
            <p class="text-sm text-muted-foreground mb-4">
                Klant blijft van Taxi A (owner). Geen chauffeur → Taxi B (fulfiller) rijdt.
                Klantbetaling via platform; na fee split NEXA het netto tussen A en B
                (nu {{ (int) $ownerSharePercent }}% / {{ (int) $fulfillerSharePercent }}%, configureerbaar).
            </p>
            <div class="rounded-lg border border-border bg-muted/20 p-4 mb-4 overflow-x-auto">
                <pre class="text-xs sm:text-sm mb-0 font-mono text-foreground leading-relaxed whitespace-pre">Klant ──€{{ number_format($example['gross'], 2, ',', '.') }}──► NEXA
                              │
                              ├─ Fee {{ (int) $feePercent }}% = €{{ number_format($example['fee'], 2, ',', '.') }}
                              │
                              └─ Netto €{{ number_format($example['net'], 2, ',', '.') }}
                                    ├─ {{ (int) $ownerSharePercent }}% → Taxi A (owner)     €{{ number_format($example['owner'], 2, ',', '.') }}
                                    └─ {{ (int) $fulfillerSharePercent }}% → Taxi B (fulfiller) €{{ number_format($example['fulfiller'], 2, ',', '.') }}</pre>
            </div>
            <p class="text-xs text-muted-foreground mb-0">
                Env: <code class="text-xs">NEXA_NETWORK_OWNER_SHARE_PERCENT</code> /
                <code class="text-xs">NEXA_NETWORK_FULFILLER_SHARE_PERCENT</code>.
                Beide bedrijven hebben een enabled payout-identity nodig.
            </p>
        </div>
    </div>

    {{-- Automatisch vs handmatig --}}
    <div class="kt-card w-full min-w-0 mb-5">
        <div class="kt-card-header px-5 py-5">
            <h2 class="kt-card-title mb-0">Automatisch én handmatig</h2>
        </div>
        <div class="kt-card-content p-5">
            <div class="grid gap-4 sm:grid-cols-2 text-sm">
                <div class="rounded-lg border border-border p-4">
                    <p class="font-medium text-foreground mb-2">Automatisch (cron)</p>
                    <ul class="list-disc ps-5 mb-0 text-muted-foreground space-y-1 text-xs sm:text-sm">
                        <li><code class="text-xs">taxi:release-settlement-holds</code> — elke 15 min</li>
                        <li><code class="text-xs">taxi:process-platform-settlements</code> — ledgers + payouts, elke 15 min</li>
                        <li>Na 3 mislukte pogingen → status <em>Handmatige actie nodig</em></li>
                    </ul>
                </div>
                <div class="rounded-lg border border-border p-4">
                    <p class="font-medium text-foreground mb-2">Handmatig (als iets vastzit)</p>
                    <ul class="list-disc ps-5 mb-0 text-muted-foreground space-y-1 text-xs sm:text-sm">
                        <li>Settlement-wachtrij: “Verwerk wachtrij nu”</li>
                        <li>Per rij: “Opnieuw uitbetalen” (retry)</li>
                        <li>Per rij: “Forceer uitbetaald” als de overboeking buiten NEXA al is gedaan</li>
                        <li>Rit-detail: “Settlement vrijgeven” bij hold/review</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Tenant contrast --}}
    <div class="kt-card w-full min-w-0 mb-5">
        <div class="kt-card-header px-5 py-5">
            <h2 class="kt-card-title mb-0">3. Eigen tenant-ritten (geen platform-fee)</h2>
        </div>
        <div class="kt-card-content p-5">
            <p class="text-sm text-muted-foreground mb-0">
                Boeking via de eigen website van de tenant: klant betaalt het taxibedrijf (hun Mollie-key).
                Geen NEXA-provisie per rit, geen platform-settlement-ledger. Abonnement dekt het platform.
            </p>
        </div>
    </div>

    <p class="text-xs text-muted-foreground mb-0">
        Live Mollie Connect transfers zijn nog stub (<code class="text-xs">StubPlatformSettlementTransferClient</code>).
        Ledgers en statusmachine zijn productie-klaar; PSP-transfer wordt later aangesloten op dezelfde interface.
    </p>
</div>
@endsection
