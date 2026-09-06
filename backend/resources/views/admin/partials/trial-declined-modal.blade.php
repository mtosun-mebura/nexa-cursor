@php
    $trialEndsLabel = $adminTrialDeclined['trial_ends_at']?->translatedFormat('j F Y');
    $startLabel = $adminTrialDeclined['start_date']?->translatedFormat('j F Y');
@endphp
<style>
    #admin-trial-declined-modal .admin-modal-panel {
        background-color: #ffffff;
        box-shadow:
            0 25px 50px -12px rgba(2, 6, 23, 0.35),
            0 0 0 1px rgba(15, 23, 42, 0.06);
    }
    html.dark #admin-trial-declined-modal .admin-modal-panel,
    .dark #admin-trial-declined-modal .admin-modal-panel {
        background-color: #0b0f19;
        box-shadow:
            0 25px 50px -12px rgba(0, 0, 0, 0.65),
            0 0 0 1px rgba(148, 163, 184, 0.12);
    }
</style>
<div id="admin-trial-declined-modal"
     class="flex fixed inset-0 z-[100000] items-center justify-center p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="admin-trial-declined-title"
     data-trial-declined-modal>
    <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-md" data-trial-declined-dismiss></div>
    <div class="admin-modal-panel relative z-10 w-full max-w-lg rounded-2xl border border-border shadow-2xl">
        <div class="border-b border-border px-6 py-5">
            <h2 id="admin-trial-declined-title" class="text-lg font-semibold text-foreground mb-1">Proefperiode beëindigd</h2>
            <p class="text-sm text-muted-foreground mb-0">
                Het abonnement is niet ingegaan. Er volgt geen incasso.
            </p>
        </div>
        <div class="px-6 py-5">
            <p class="text-sm text-foreground mb-0">
                Je kunt het pakket blijven gebruiken tot
                <strong class="text-foreground">{{ $trialEndsLabel }}</strong>.
                Daarna wordt het account op inactief gezet.
            </p>
            <p class="text-sm text-foreground mt-6 mb-6">
                Je kunt het abonnement altijd weer activeren.<br>
                De ingangsdatum blijft
                <strong class="text-foreground">{{ $startLabel }}</strong>.
            </p>
            <p class="text-sm text-foreground mb-0">
                Vragen? Mail <a href="mailto:info@nexasuite.nl" class="text-primary hover:underline">info@nexasuite.nl</a>.
            </p>
        </div>
        <div class="border-t border-border px-6 py-5 flex flex-wrap justify-end gap-2">
            <button type="button" class="kt-btn kt-btn-outline" data-trial-declined-dismiss onclick="window.adminDismissTrialDeclined && window.adminDismissTrialDeclined()">Sluiten</button>
            <form action="{{ route('admin.subscriptions.withdraw') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="kt-btn kt-btn-primary">Abonnement activeren</button>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('admin-trial-declined-modal');
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
    }

    show();

    window.adminDismissTrialDeclined = hide;
    modal.querySelectorAll('[data-trial-declined-dismiss]').forEach(function (el) {
        el.addEventListener('click', hide);
    });
})();
</script>
