@php
    /** @var array<string, mixed> $adminTaxiSetup */
    $forceOpen = ! empty($adminTaxiSetup['force_open']);
@endphp
<style>
    #admin-taxi-setup-modal .admin-modal-panel {
        background-color: #ffffff;
        color: #0f172a;
        box-shadow:
            0 25px 50px -12px rgba(2, 6, 23, 0.35),
            0 0 0 1px rgba(15, 23, 42, 0.06);
    }
    html.dark #admin-taxi-setup-modal .admin-modal-panel,
    .dark #admin-taxi-setup-modal .admin-modal-panel {
        background-color: #0b0f19;
        color: #f8fafc;
        box-shadow:
            0 25px 50px -12px rgba(0, 0, 0, 0.65),
            0 0 0 1px rgba(148, 163, 184, 0.12);
    }
</style>
<div id="admin-taxi-setup-modal"
     class="{{ $forceOpen ? 'flex' : 'hidden' }} fixed inset-0 z-[100000] items-center justify-center p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="admin-taxi-setup-title"
     data-taxi-setup-modal
     data-force-open="{{ $forceOpen ? '1' : '0' }}"
     @if(! $forceOpen) hidden @endif>
    <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-md" data-taxi-setup-dismiss></div>
    <div class="admin-modal-panel relative z-10 w-full max-w-xl rounded-2xl border border-border shadow-2xl max-h-[90vh] overflow-y-auto"
         style="scrollbar-width: thin; scrollbar-color: rgba(148,163,184,.55) transparent;">
        <div class="border-b border-border px-6 py-5">
            <h2 id="admin-taxi-setup-title" class="text-lg font-semibold text-foreground mb-1">Nexa Taxi inrichten</h2>
            <p class="text-sm text-muted-foreground mb-0">
                Volg deze stappen zodat boekingen, prijzen en e-mails goed werken voor
                <strong class="text-foreground">{{ $adminTaxiSetup['company_name'] ?? 'jouw bedrijf' }}</strong>.
            </p>
        </div>
        <div class="px-6 py-5 space-y-4">
            @foreach(($adminTaxiSetup['steps'] ?? []) as $step)
                @php
                    $done = ! empty($step['done']);
                    $warning = ! empty($step['warning']);
                @endphp
                <div class="rounded-xl border border-border p-4 {{ $done ? 'bg-emerald-500/5' : ($warning ? 'bg-amber-500/5' : 'bg-muted/20') }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h3 class="text-sm font-semibold text-foreground mb-0">{{ $step['title'] }}</h3>
                                @if($done)
                                    <span class="kt-badge kt-badge-sm kt-badge-success">Klaar</span>
                                @elseif(! empty($step['required']))
                                    <span class="kt-badge kt-badge-sm kt-badge-danger">Verplicht</span>
                                @else
                                    <span class="kt-badge kt-badge-sm kt-badge-warning">Aanbevolen</span>
                                @endif
                            </div>
                            <p class="text-sm text-foreground mb-0">{{ $step['description'] }}</p>
                        </div>
                        @if(! empty($step['url']) && ! empty($step['button']))
                            <a href="{{ $step['url'] }}" class="kt-btn kt-btn-sm {{ $done ? 'kt-btn-outline' : 'kt-btn-primary' }} shrink-0">
                                {{ $step['button'] }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach

            @if(empty($adminTaxiSetup['booking_allowed']))
                <p class="text-sm text-red-500 mb-4">
                    {{ $adminTaxiSetup['booking_block_message'] ?? 'Zonder voertuig kan de boekingsmodule niet op de website.' }}
                </p>
            @endif

            <p class="text-sm text-muted-foreground mb-0">
                Dit stappenplan blijft beschikbaar via de banner bovenaan en via je notificaties, totdat alles is afgerond.
            </p>
        </div>
        <div class="border-t border-border px-6 py-5 flex flex-wrap justify-end gap-2">
            <button type="button"
                    class="kt-btn kt-btn-outline"
                    data-taxi-setup-dismiss
                    onclick="window.adminDismissTaxiSetup && window.adminDismissTaxiSetup()">
                Later verder
            </button>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('admin-taxi-setup-modal');
    if (!modal) {
        return;
    }

    function show() {
        modal.hidden = false;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function hide() {
        modal.hidden = true;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        try {
            var url = new URL(window.location.href);
            if (url.searchParams.has('taxi_setup')) {
                url.searchParams.delete('taxi_setup');
                window.history.replaceState({}, '', url.pathname + url.search + url.hash);
            }
        } catch (err) {}
    }

    window.adminDismissTaxiSetup = hide;
    window.adminOpenTaxiSetup = show;

    if (modal.getAttribute('data-force-open') === '1') {
        show();
    }

    modal.querySelectorAll('[data-taxi-setup-dismiss]').forEach(function (el) {
        el.addEventListener('click', hide);
    });
})();
</script>
