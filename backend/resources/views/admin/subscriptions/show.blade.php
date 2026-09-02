@extends('admin.layouts.app')

@section('title', 'Abonnementen')

@push('styles')
<style>
    .kt-btn-danger {
        background-color: #ef4444 !important;
        color: white !important;
        border-color: #ef4444 !important;
    }
    .kt-btn-danger:hover {
        background-color: #dc2626 !important;
        border-color: #dc2626 !important;
    }
    .dark .kt-btn-danger {
        background-color: #dc2626 !important;
        border-color: #dc2626 !important;
    }
    .dark .kt-btn-danger:hover {
        background-color: #b91c1c !important;
        border-color: #b91c1c !important;
    }
    .kt-alert-pending {
        align-items: center;
        padding-block: 0.5rem;
        padding-inline: 0.75rem;
        gap: 0.75rem;
        background-color: #ea580c;
        color: #fff;
    }
    .kt-alert-pending .kt-alert-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.5rem;
        height: 1.5rem;
        border: 0;
        background: transparent;
        padding: 0;
        color: inherit;
    }
    .kt-alert-pending .kt-alert-close i {
        color: inherit;
        font-size: 0.875rem;
    }
    .subscription-upgrade-panel {
        background-color: var(--color-zinc-100);
    }
    .dark .subscription-upgrade-panel {
        background-color: var(--color-zinc-900);
    }
</style>
@endpush

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Abonnementen</h1>
            <div class="text-sm font-normal text-secondary-foreground">
                @if(! empty($in_trial))
                    Proefperiode: tot {{ $trial_ends_at?->translatedFormat('j F Y') }} kun je stoppen zonder jaarcontract. Daarna start facturatie.
                @else
                    Jaarcontract met maandelijkse incasso's. Upgraden kan direct; downgraden en opzeggen per einde contract.
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5" role="alert">
            <i class="ki-filled ki-check-circle me-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @if($pending_type)
        @php
            $pendingDate = trim((string) ($pending_effective_on?->translatedFormat('j F Y') ?? ''));
            if ($pending_type === 'cancel') {
                $pendingAlertHtml = 'Opzegging ingepland per <strong>'.e($pendingDate).'</strong>. Tot die datum blijft je huidige pakket actief; daarna stopt de SEPA-incasso.';
            } else {
                $pendingAlertHtml = 'Downgrade naar <strong>'.e($pending_package_name).'</strong> ingepland per <strong>'.e($pendingDate).'</strong>. Tot die datum blijf je '.e($current_name).' gebruiken tegen de huidige prijs.';
            }
        @endphp
        <div class="kt-alert kt-alert-warning kt-alert-pending mb-5" role="status">
            <div class="kt-alert-content min-w-0">
                <p class="mb-0">{!! $pendingAlertHtml !!}</p>
            </div>
            <div class="kt-alert-actions shrink-0">
                <form action="{{ route('admin.subscriptions.withdraw') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">Wijziging intrekken</button>
                </form>
                <button type="button" class="kt-alert-close" data-pending-alert-dismiss aria-label="Melding sluiten">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
        </div>
        <script>
            document.querySelector('[data-pending-alert-dismiss]')?.addEventListener('click', function () {
                this.closest('.kt-alert-pending')?.remove();
            });
        </script>
    @endif

    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Huidig abonnement</h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full">
                    <tr>
                        <td class="min-w-56 text-secondary-foreground font-normal">Pakket</td>
                        <td class="font-medium text-foreground">{{ $current_name }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Maandprijs (excl. btw)</td>
                        <td class="font-medium text-foreground tabular-nums">{{ $current_amount_label }} per maand</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Begindatum</td>
                        <td class="font-medium text-foreground">{{ $start_date->translatedFormat('j F Y') }}</td>
                    </tr>
                    @if(! empty($in_trial) && $trial_ends_at)
                        <tr>
                            <td class="text-secondary-foreground font-normal">Proef tot</td>
                            <td class="font-medium text-foreground">{{ $trial_ends_at->translatedFormat('j F Y') }} (nog {{ $trial_days_remaining }} {{ $trial_days_remaining === 1 ? 'dag' : 'dagen' }})</td>
                        </tr>
                        <tr>
                            <td class="text-secondary-foreground font-normal">Facturatie start</td>
                            <td class="font-medium text-foreground">{{ $billing_start_date->translatedFormat('j F Y') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-secondary-foreground font-normal">Einde jaarcontract</td>
                        <td class="font-medium text-foreground">{{ $contract_end_date->translatedFormat('j F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Opzegtermijn</td>
                        <td class="font-medium text-foreground">
                            @if($ended)
                                Abonnement beëindigd.
                            @elseif(! empty($in_trial))
                                Proefperiode: je kunt nu nog stoppen. Daarna zit je vast aan het jaarcontract tot {{ $contract_end_date->translatedFormat('j F Y') }}.
                            @elseif($past_first_year)
                                Eerste jaar is voorbij: maandelijks opzegbaar (einde van de lopende maand).
                            @else
                                Jaarcontract: opzeggen of downgraden kan per {{ $contract_end_date->translatedFormat('j F Y') }}.
                            @endif
                        </td>
                    </tr>
                    @if($profile->subscription_end_date)
                        <tr>
                            <td class="text-secondary-foreground font-normal">SEPA stopt per</td>
                            <td class="font-medium text-foreground">{{ $profile->subscription_end_date->translatedFormat('j F Y') }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        @if(! $ended && $current_key !== '')
            <div class="kt-card min-w-full">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Pakket wijzigen</h3>
                </div>
                <div class="kt-card-content p-5">
                    <p class="text-sm text-secondary-foreground mb-5">
                        Upgraden gaat per direct: je krijgt de extra functies vandaag en de nieuwe prijs geldt vanaf vandaag, bovenop je huidige maandelijkse abonnement. Downgraden gaat pas in per {{ $change_effective_on->translatedFormat('j F Y') }}.
                    </p>
                    <div class="grid gap-4 md:grid-cols-3">
                        @foreach($packages as $package)
                            <div class="rounded-lg border border-border p-4 flex flex-col gap-3 {{ $package['is_current'] ? 'ring-1 ring-primary' : '' }}">
                                <div>
                                    <div class="text-base font-medium text-foreground">{{ $package['name'] }}</div>
                                    @if($package['audience'])
                                        <div class="text-xs text-secondary-foreground mt-1">{{ $package['audience'] }}</div>
                                    @endif
                                    <div class="text-lg font-semibold text-foreground mt-2 tabular-nums">{{ $package['amount_label'] }} <span class="text-sm font-normal text-secondary-foreground">per maand</span></div>
                                </div>
                                @if($package['is_current'])
                                    <span class="kt-badge kt-badge-sm kt-badge-success w-fit">Huidig pakket</span>
                                @elseif($package['is_upgrade'] && $pending_type !== 'cancel')
                                    <button type="button"
                                        class="kt-btn kt-btn-primary kt-btn-sm w-full"
                                        data-upgrade-open
                                        data-package-key="{{ $package['key'] }}"
                                        data-package-name="{{ $package['name'] }}"
                                        data-package-amount="{{ $package['amount_label'] }}">
                                        Nu upgraden
                                    </button>
                                @elseif($package['is_downgrade'] && $pending_type !== 'cancel')
                                    <form action="{{ route('admin.subscriptions.downgrade') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="package_key" value="{{ $package['key'] }}">
                                        <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm w-full"
                                            onclick="return confirm('Downgraden naar {{ $package['name'] }} gaat in per {{ $change_effective_on->translatedFormat('j F Y') }}. Tot die datum blijf je {{ $current_name }} gebruiken. Doorgaan?')">
                                            Downgraden per {{ $change_effective_on->format('d-m-Y') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @if(! empty($in_trial))
                <div class="kt-card min-w-full">
                    <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                        <h3 class="kt-card-title mb-0">Proefperiode stoppen</h3>
                    </div>
                    <div class="kt-card-content p-5">
                        <p class="text-sm text-secondary-foreground mb-4">
                            Tot {{ $trial_ends_at?->translatedFormat('j F Y') }} kun je de proef beëindigen. Je tenant wordt dan inactief gezet en er volgt geen incasso. Laat je de proef doorlopen, dan gaat het jaarcontract in (inclusief deze proefperiode) en start de facturatie.
                        </p>
                        <form action="{{ route('admin.subscriptions.end-trial') }}" method="POST">
                            @csrf
                            <button type="submit" class="kt-btn kt-btn-danger"
                                onclick="return confirm('Proefperiode stoppen? Je tenant wordt inactief en er wordt niets geïncasseerd.')">
                                Proefperiode stoppen
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($cancel_allowed)
                <div class="kt-card min-w-full">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Opzeggen</h3>
                    </div>
                    <div class="kt-card-content p-5">
                        <p class="text-sm text-secondary-foreground mb-4">
                            @if($past_first_year)
                                Je kunt maandelijks opzeggen. Het abonnement en de SEPA-incasso lopen door tot {{ $change_effective_on->translatedFormat('j F Y') }}.
                            @else
                                Opzeggen kan per einde van het jaarcontract ({{ $change_effective_on->translatedFormat('j F Y') }}). Tot die datum blijf je het huidige pakket gebruiken en wordt er maandelijks geïncasseerd.
                            @endif
                        </p>
                        <form action="{{ route('admin.subscriptions.cancel') }}" method="POST">
                            @csrf
                            <button type="submit" class="kt-btn kt-btn-danger"
                                onclick="return confirm('Opzeggen per {{ $change_effective_on->translatedFormat('j F Y') }}? De SEPA-incasso stopt vanaf die datum. Tot die tijd blijft je huidige pakket actief.')">
                                Opzeggen per {{ $change_effective_on->format('d-m-Y') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @elseif($current_key === '')
            <div class="kt-card min-w-full">
                <div class="kt-card-content p-5 text-sm text-secondary-foreground">
                    Er is nog geen pakket gekoppeld aan dit bedrijf. Neem contact op met NEXA om een abonnement te starten.
                </div>
            </div>
        @endif
    </div>
</div>

@if(! $ended && $current_key !== '')
<form id="subscription-upgrade-form" action="{{ route('admin.subscriptions.upgrade') }}" method="POST" class="hidden" hidden>
    @csrf
    <input type="hidden" name="package_key" id="subscription-upgrade-package-key" value="">
</form>

<div id="subscription-upgrade-modal"
     class="hidden fixed inset-0 z-[100000] items-center justify-center p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="subscription-upgrade-title"
     hidden>
    <div class="absolute inset-0 bg-zinc-950/70 backdrop-blur-md" data-upgrade-dismiss></div>
    <div class="subscription-upgrade-panel relative w-full max-w-lg rounded-2xl border border-border shadow-2xl">
        <div class="border-b border-border px-6 py-5">
            <h2 id="subscription-upgrade-title" class="text-lg font-semibold text-foreground mb-1">Upgraden bevestigen</h2>
            <p class="text-sm text-muted-foreground mb-0">
                Na goedkeuring gaat de upgrade <strong class="text-foreground">direct live</strong>.
            </p>
        </div>
        <div class="px-6 py-5 space-y-3">
            <p class="text-sm text-foreground mb-0">
                Je schakelt over naar <strong id="subscription-upgrade-package-name"></strong>
                (<span id="subscription-upgrade-package-amount" class="tabular-nums"></span> per maand).
            </p>
            <p class="text-sm text-muted-foreground mb-0">
                Extra functies zijn vanaf vandaag beschikbaar. De nieuwe prijs geldt vanaf vandaag en gaat mee in de maandelijkse incasso.
            </p>
            <p class="text-sm text-muted-foreground mb-0">
                Terug naar een lager pakket kan daarna pas per einde van het jaarcontract
                (<strong class="text-foreground">{{ $change_effective_on->translatedFormat('j F Y') }}</strong>).
            </p>
        </div>
        <div class="border-t border-border px-6 py-5 flex flex-wrap justify-end gap-2">
            <button type="button" class="kt-btn kt-btn-outline" data-upgrade-dismiss>Annuleren</button>
            <button type="button" class="kt-btn kt-btn-primary" id="subscription-upgrade-confirm">Upgraden</button>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('subscription-upgrade-modal');
    var form = document.getElementById('subscription-upgrade-form');
    var keyInput = document.getElementById('subscription-upgrade-package-key');
    var nameEl = document.getElementById('subscription-upgrade-package-name');
    var amountEl = document.getElementById('subscription-upgrade-package-amount');
    var confirmBtn = document.getElementById('subscription-upgrade-confirm');
    if (!modal || !form || !keyInput || !confirmBtn) {
        return;
    }

    var lastFocus = null;
    var submitting = false;

    function isOpen() {
        return !modal.classList.contains('hidden');
    }

    function openModal(btn) {
        lastFocus = btn;
        keyInput.value = btn.getAttribute('data-package-key') || '';
        if (nameEl) {
            nameEl.textContent = btn.getAttribute('data-package-name') || '';
        }
        if (amountEl) {
            amountEl.textContent = btn.getAttribute('data-package-amount') || '';
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        confirmBtn.focus();
    }

    function closeModal() {
        if (submitting) {
            return;
        }
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('hidden', 'hidden');
        keyInput.value = '';
        document.body.style.overflow = '';
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    document.querySelectorAll('[data-upgrade-open]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openModal(btn);
        });
    });

    modal.querySelectorAll('[data-upgrade-dismiss]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            closeModal();
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen()) {
            e.preventDefault();
            closeModal();
        }
    });

    confirmBtn.addEventListener('click', function () {
        if (submitting || !keyInput.value) {
            return;
        }
        submitting = true;
        confirmBtn.disabled = true;
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });
})();
</script>
@endif
@endsection
