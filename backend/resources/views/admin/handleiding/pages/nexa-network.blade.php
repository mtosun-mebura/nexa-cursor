<p>
    Uitgebreide plattegrond voor super-admins:
    <a href="{{ route('admin.nexa-network.guide') }}" class="text-primary font-medium hover:underline">Dashboard → NEXA Network</a>.
</p>

<p>
    Kort: <strong>tenant</strong> = eigen vloot; <strong>marketplace</strong> = nexasuite.nl/boek + fee;
    <strong>network</strong> = owner Taxi A, uitvoerder Taxi B. Complete is nooit automatisch uitbetaling —
    eerst hold/review, daarna <code>settlement_eligible</code>.
</p>

<div class="handleiding-tip">
    <strong class="text-foreground">Chauffeur-app:</strong>
    bij marketplace/network ziet de chauffeur Klant betaalt / Owner / Executor / NEXA fee
    (zelfde % als onder Betalingen → NEXA Suite ritten → Instellingen).
</div>

<h2 id="links">Direct naar</h2>
<div class="handleiding-step">
    <span class="handleiding-step-num">1</span>
    <div>
        <a href="{{ route('admin.nexa-network.guide') }}">NEXA Network — hoe het werkt</a>
        — stappenplan met exacte links (partners, fee, payout, settlements)
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">2</span>
    <div>
        <a href="{{ route('admin.nexa-network.guide') }}#instellingen">Waar doe ik wat?</a>
        — partners via invite-code (geen tenant-directory)
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">3</span>
    <div>
        <a href="{{ url('/admin/taxi/dispatch-instellingen') }}#dispatch-nexa-network-partners">Network-partners</a>
        — code delen / plakken / accepteren
    </div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">4</span>
    <div><a href="{{ url('/admin/taxi/dispatch-instellingen') }}#dispatch-nexa-network">Chauffeur dispatch: NEXA Network</a> — aan/uit + modus</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">5</span>
    <div><a href="{{ route('admin.nexa-suite-bookings.settings') }}">Fee-instellingen</a> — marketplace én network %</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">6</span>
    <div><a href="{{ route('admin.payout-identities.index') }}">Payout onboarding</a> — KYB metadata</div>
</div>
<div class="handleiding-step">
    <span class="handleiding-step-num">7</span>
    <div><a href="{{ route('admin.payment-flows.guide') }}">Uitleg betalingen</a> + <a href="{{ route('admin.payment-flows.settlements') }}">settlement-wachtrij</a></div>
</div>
