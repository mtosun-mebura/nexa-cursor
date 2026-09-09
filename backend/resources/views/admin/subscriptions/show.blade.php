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
    .subscription-current-table col:first-child {
        width: 33.333%;
    }
    .subscription-current-table col:last-child {
        width: 66.667%;
    }
    .subscription-modal-panel {
        background-color: var(--color-zinc-100);
    }
    .dark .subscription-modal-panel {
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
                @if(! empty($trial_declined) && ! empty($in_trial))
                    Je hebt gekozen het jaarcontract niet te laten ingaan. Tot {{ $trial_ends_at?->translatedFormat('j F Y') }} kun je het pakket blijven gebruiken en het abonnement weer activeren.
                @elseif(! empty($in_trial))
                    Proefperiode: tot {{ $trial_ends_at?->translatedFormat('j F Y') }} kun je stoppen zonder jaarcontract. Daarna start facturatie.
                @else
                    Jaarcontract met maandelijkse incasso's. Upgraden kan direct; downgraden en opzeggen per einde contract.
                @endif
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger mb-5" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @if($pending_type)
        @php
            $pendingDate = trim((string) ($pending_effective_on?->translatedFormat('j F Y') ?? ''));
            $pendingWithdrawLabel = 'Wijziging intrekken';
            if ($pending_type === 'cancel') {
                $pendingAlertHtml = 'Opzegging ingepland per <strong>'.e($pendingDate).'</strong>. Tot die datum blijft je huidige pakket actief; daarna stopt de SEPA-incasso.';
            } elseif ($pending_type === 'trial_end') {
                $pendingWithdrawLabel = 'Abonnement activeren';
                $pendingAlertHtml = 'Het jaarcontract gaat niet in. Tot <strong>'.e($pendingDate).'</strong> kun je het pakket blijven gebruiken. Er volgt geen incasso. De ingangsdatum blijft <strong>'.e($start_date->translatedFormat('j F Y')).'</strong>.';
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
                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">{{ $pendingWithdrawLabel }}</button>
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
            <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                <h3 class="kt-card-title mb-0">Huidig abonnement</h3>
            </div>
            <div class="kt-card-table kt-scrollable-x-auto pb-3">
                <table class="kt-table kt-table-border-dashed align-middle text-sm text-muted-foreground w-full admin-fluid-table subscription-current-table">
                    <colgroup>
                        <col>
                        <col>
                    </colgroup>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Pakket</td>
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
                    @if(! empty($free_months))
                        <tr>
                            <td class="text-secondary-foreground font-normal"></td>
                            <td class="text-muted-foreground text-xs">{{ (int) $free_months }} {{ (int) $free_months === 1 ? 'maand' : 'maanden' }} gratis</td>
                        </tr>
                    @endif
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
                            @elseif(! empty($trial_declined) && ! empty($in_trial))
                                Jaarcontract is niet geactiveerd. Tot {{ $trial_ends_at?->translatedFormat('j F Y') }} kun je het pakket blijven gebruiken en het abonnement weer activeren.
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

        @if(! empty($addons))
            <div class="kt-card min-w-full">
                <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                    <h3 class="kt-card-title mb-0">Aanvullende modules</h3>
                </div>
                <div class="kt-card-content p-5">
                    <p class="text-sm text-secondary-foreground mb-5">
                        @if(! empty($in_trial))
                            Tijdens de proefperiode kun je alle aanvullende modules gebruiken. Zeg een module op vóór de facturatie start: dan stopt hij kosteloos, zonder factuur. Na de proef zeg je per de 1e van volgende maand op.
                        @else
                            Opzeggen van een module gaat per de 1e van volgende maand. Tot die datum blijft de module actief en wordt het extra bedrag nog geïncasseerd.
                        @endif
                    </p>
                    <div class="grid gap-4 md:grid-cols-3">
                        @foreach($addons as $addon)
                            <div class="rounded-lg border border-border p-4 flex flex-col gap-3">
                                <div>
                                    <div class="text-base font-medium text-foreground">{{ $addon['name'] }}</div>
                                    @if($addon['description'] !== '')
                                        <div class="text-xs text-secondary-foreground mt-1">{{ $addon['description'] }}</div>
                                    @endif
                                    <div class="text-lg font-semibold text-foreground mt-2 tabular-nums">
                                        {{ $addon['price_label'] }}
                                        <span class="text-sm font-normal text-secondary-foreground">per maand{{ ! empty($addon['is_quantity']) ? ' per bundel' : '' }}</span>
                                    </div>
                                </div>
                                @if(! empty($addon['pending_cancel']) && $addon['starts_at'])
                                    <span class="kt-badge kt-badge-sm kt-badge-warning w-fit">Opgezegd per {{ $addon['starts_at']->translatedFormat('j F Y') }}</span>
                                    <p class="text-xs text-muted-foreground mb-0">Blijft tot die datum actief.</p>
                                    @if(! empty($addon['can_withdraw']) && empty($ended))
                                        <form action="{{ route('admin.subscriptions.addons.withdraw', $addon['key']) }}" method="POST" class="mt-auto">
                                            @csrf
                                            <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm w-full">Opzegging intrekken</button>
                                        </form>
                                    @endif
                                @elseif(! empty($addon['pending_decrease']) && $addon['starts_at'])
                                    <span class="kt-badge kt-badge-sm kt-badge-warning w-fit">Wijziging per {{ $addon['starts_at']->translatedFormat('j F Y') }}</span>
                                    <p class="text-xs text-muted-foreground mb-0">Deze maand blijft {{ (int) $addon['entitled'] }} bundel{{ (int) $addon['entitled'] === 1 ? '' : 's' }} actief, daarna {{ (int) $addon['quantity'] }}.</p>
                                    @if(! empty($addon['can_withdraw']) && empty($ended))
                                        <form action="{{ route('admin.subscriptions.addons.withdraw', $addon['key']) }}" method="POST" class="mt-auto">
                                            @csrf
                                            <button type="submit" class="kt-btn kt-btn-outline kt-btn-sm w-full">Wijziging intrekken</button>
                                        </form>
                                    @endif
                                @elseif(! empty($addon['saved']))
                                    <span class="kt-badge kt-badge-sm kt-badge-success w-fit">
                                        @if(! empty($addon['is_quantity']) && (int) $addon['entitled'] > 0)
                                            Actief · {{ (int) $addon['entitled'] }} bundel{{ (int) $addon['entitled'] === 1 ? '' : 's' }}
                                        @else
                                            Actief
                                        @endif
                                    </span>
                                    @if(! empty($addon['can_cancel']) && empty($ended))
                                        <button type="button"
                                            class="kt-btn kt-btn-outline kt-btn-sm w-full mt-auto"
                                            data-addon-cancel-open
                                            data-addon-key="{{ $addon['key'] }}"
                                            data-addon-name="{{ $addon['name'] }}"
                                            data-addon-trial="{{ ! empty($addon['in_trial']) ? '1' : '0' }}"
                                            data-addon-when="{{ $addon['cancel_on']->translatedFormat('j F Y') }}">
                                            @if(! empty($addon['in_trial']))
                                                Opzeggen (proefperiode)
                                            @else
                                                Opzeggen per {{ $addon['cancel_on']->format('d-m-Y') }}
                                            @endif
                                        </button>
                                    @endif
                                @elseif(! empty($addon['trial_only']))
                                    <span class="kt-badge kt-badge-sm kt-badge-light w-fit">Beschikbaar in de proefperiode</span>
                                    <p class="text-xs text-muted-foreground mb-0">Niet op je abonnement gezet: na de proef stopt deze module automatisch, zonder factuur.</p>
                                @else
                                    <span class="text-xs text-muted-foreground">Niet actief</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

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
                                @elseif($package['is_upgrade'] && $pending_type !== 'cancel' && $pending_type !== 'trial_end')
                                    <button type="button"
                                        class="kt-btn kt-btn-primary kt-btn-sm w-full"
                                        data-upgrade-open
                                        data-package-key="{{ $package['key'] }}"
                                        data-package-name="{{ $package['name'] }}"
                                        data-package-amount="{{ $package['amount_label'] }}">
                                        Nu upgraden
                                    </button>
                                @elseif($package['is_downgrade'] && $pending_type !== 'cancel' && $pending_type !== 'trial_end')
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

            @if(! empty($in_trial) && empty($trial_declined))
                <div class="kt-card min-w-full">
                    <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                        <h3 class="kt-card-title mb-0">Proefperiode stoppen</h3>
                    </div>
                    <div class="kt-card-content p-5">
                        <p class="text-sm text-secondary-foreground mb-4">
                            Tot {{ $trial_ends_at?->translatedFormat('j F Y') }} kun je kiezen het jaarcontract niet te laten ingaan. Je blijft het pakket tot die datum gebruiken en er volgt geen incasso. Je kunt het abonnement daarna altijd weer activeren; de ingangsdatum blijft {{ $start_date->translatedFormat('j F Y') }}. Laat je de proef doorlopen, dan gaat het jaarcontract in en start de facturatie.
                        </p>
                        <form id="subscription-end-trial-form" action="{{ route('admin.subscriptions.end-trial') }}" method="POST">
                            @csrf
                            <button type="button" class="kt-btn kt-btn-danger" data-end-trial-open>
                                Proefperiode stoppen
                            </button>
                        </form>
                    </div>
                </div>
            @elseif(empty($in_trial) && $cancel_allowed)
                <div class="kt-card min-w-full">
                    <div class="kt-card-header flex flex-wrap items-center justify-between gap-3 px-5 py-5">
                        <h3 class="kt-card-title mb-0">Opzeggen</h3>
                    </div>
                    <div class="kt-card-content p-5">
                        <p class="text-sm text-secondary-foreground mb-4">
                            @if($past_first_year)
                                Direct opzeggen kan alleen tijdens de proefperiode. Die is voorbij; na het eerste jaar kun je maandelijks opzeggen. Het abonnement en de SEPA-incasso lopen door tot {{ $change_effective_on->translatedFormat('j F Y') }}.
                            @else
                                Direct opzeggen kan alleen tijdens de proefperiode. Die is voorbij, dus opzeggen kan alleen per einde van het jaarcontract ({{ $change_effective_on->translatedFormat('j F Y') }}). Tot die datum blijf je het huidige pakket gebruiken en wordt er maandelijks geïncasseerd.
                            @endif
                        </p>
                        <form id="subscription-cancel-form" action="{{ route('admin.subscriptions.cancel') }}" method="POST">
                            @csrf
                            <button type="button" class="kt-btn kt-btn-danger" data-cancel-open>
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
    <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-md" data-upgrade-dismiss></div>
    <div class="subscription-modal-panel relative w-full max-w-lg rounded-2xl border border-border shadow-2xl">
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

<form id="subscription-addon-cancel-form" method="POST" class="hidden" hidden>
    @csrf
</form>
<div id="subscription-addon-cancel-modal"
     class="hidden fixed inset-0 z-[100000] items-center justify-center p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="subscription-addon-cancel-title"
     hidden>
    <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-md" data-addon-cancel-dismiss></div>
    <div class="subscription-modal-panel relative w-full max-w-lg rounded-2xl border border-border shadow-2xl">
        <div class="border-b border-border px-6 py-5">
            <h2 id="subscription-addon-cancel-title" class="text-lg font-semibold text-foreground mb-1">Module opzeggen</h2>
            <p class="text-sm text-muted-foreground mb-0" id="subscription-addon-cancel-lead"></p>
        </div>
        <div class="px-6 py-5 space-y-3">
            <p class="text-sm text-foreground mb-0" id="subscription-addon-cancel-body"></p>
            <p class="text-sm text-muted-foreground mb-0" id="subscription-addon-cancel-hint"></p>
        </div>
        <div class="border-t border-border px-6 py-5 flex flex-wrap justify-end gap-2">
            <button type="button" class="kt-btn kt-btn-outline" data-addon-cancel-dismiss>Annuleren</button>
            <button type="button" class="kt-btn kt-btn-danger" id="subscription-addon-cancel-confirm">Opzeggen</button>
        </div>
    </div>
</div>
</div>

@if(empty($in_trial) && $cancel_allowed)
<div id="subscription-cancel-modal"
     class="hidden fixed inset-0 z-[100000] items-center justify-center p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="subscription-cancel-title"
     hidden>
    <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-md" data-cancel-dismiss></div>
    <div class="subscription-modal-panel relative w-full max-w-lg rounded-2xl border border-border shadow-2xl">
        <div class="border-b border-border px-6 py-5">
            <h2 id="subscription-cancel-title" class="text-lg font-semibold text-foreground mb-1">Opzeggen bevestigen</h2>
            <p class="text-sm text-muted-foreground mb-0">
                Direct opzeggen kan alleen tijdens de <strong class="text-foreground">proefperiode</strong>.
            </p>
        </div>
        <div class="px-6 py-5 space-y-3">
            @if($past_first_year)
                <p class="text-sm text-foreground mb-0">
                    De proefperiode is voorbij. Het eerste jaarcontract is afgerond, daarom gaat de opzegging in per einde van de lopende maand:
                    <strong>{{ $change_effective_on->translatedFormat('j F Y') }}</strong>.
                </p>
            @else
                <p class="text-sm text-foreground mb-0">
                    De proefperiode is voorbij. Opzeggen kan daardoor alleen per einde van het jaarcontract:
                    <strong>{{ $change_effective_on->translatedFormat('j F Y') }}</strong>.
                </p>
            @endif
            <p class="text-sm text-muted-foreground mb-0">
                Tot die datum blijft je huidige pakket actief en wordt de maandelijkse SEPA-incasso voortgezet.
                Vanaf <strong class="text-foreground">{{ $change_effective_on->translatedFormat('j F Y') }}</strong> stopt de incasso.
            </p>
        </div>
        <div class="border-t border-border px-6 py-5 flex flex-wrap justify-end gap-2">
            <button type="button" class="kt-btn kt-btn-outline" data-cancel-dismiss>Annuleren</button>
            <button type="button" class="kt-btn kt-btn-danger" id="subscription-cancel-confirm">Opzeggen per {{ $change_effective_on->format('d-m-Y') }}</button>
        </div>
    </div>
</div>
@endif

@if(! empty($in_trial) && empty($trial_declined))
<div id="subscription-end-trial-modal"
     class="hidden fixed inset-0 z-[100000] items-center justify-center p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="subscription-end-trial-title"
     hidden>
    <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-md" data-end-trial-dismiss></div>
    <div class="subscription-modal-panel relative w-full max-w-lg rounded-2xl border border-border shadow-2xl">
        <div class="border-b border-border px-6 py-5">
            <h2 id="subscription-end-trial-title" class="text-lg font-semibold text-foreground mb-1">Proefperiode stoppen</h2>
            <p class="text-sm text-muted-foreground mb-0">
                Direct stoppen kan alleen tijdens de <strong class="text-foreground">proefperiode</strong>
                @if($trial_ends_at)
                    (tot {{ $trial_ends_at->translatedFormat('j F Y') }})
                @endif.
            </p>
        </div>
        <div class="px-6 py-5 space-y-3">
            <p class="text-sm text-foreground mb-0">
                Het jaarcontract gaat dan niet in en er volgt geen incasso. Je kunt het pakket blijven gebruiken tot
                <strong class="text-foreground">{{ $trial_ends_at?->translatedFormat('j F Y') }}</strong>.
            </p>
            <p class="text-sm text-muted-foreground mb-0">
                Je kunt het abonnement altijd weer activeren; de ingangsdatum blijft
                <strong class="text-foreground">{{ $start_date->translatedFormat('j F Y') }}</strong>.
                Laat je de proef doorlopen, dan gaat het jaarcontract in. Daarna kun je alleen nog opzeggen per einde contractsdatum
                (<strong class="text-foreground">{{ $contract_end_date->translatedFormat('j F Y') }}</strong>).
            </p>
        </div>
        <div class="border-t border-border px-6 py-5 flex flex-wrap justify-end gap-2">
            <button type="button" class="kt-btn kt-btn-outline" data-end-trial-dismiss>Annuleren</button>
            <button type="button" class="kt-btn kt-btn-danger" id="subscription-end-trial-confirm">Proefperiode stoppen</button>
        </div>
    </div>
</div>
@endif

<script>
(function () {
    function bindConfirmModal(config) {
        var modal = document.getElementById(config.modalId);
        var form = document.getElementById(config.formId);
        var confirmBtn = document.getElementById(config.confirmId);
        if (!modal || !form || !confirmBtn) {
            return;
        }

        var lastFocus = null;
        var submitting = false;
        var requireValue = config.requireValueId
            ? document.getElementById(config.requireValueId)
            : null;

        function isOpen() {
            return !modal.classList.contains('hidden');
        }

        function openModal(btn) {
            lastFocus = btn;
            if (typeof config.onOpen === 'function') {
                config.onOpen(btn);
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
            if (typeof config.onClose === 'function') {
                config.onClose();
            }
            document.body.style.overflow = '';
            if (lastFocus && typeof lastFocus.focus === 'function') {
                lastFocus.focus();
            }
        }

        document.querySelectorAll(config.openSelector).forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                openModal(btn);
            });
        });

        modal.querySelectorAll(config.dismissSelector).forEach(function (el) {
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
            if (submitting) {
                return;
            }
            if (requireValue && !requireValue.value) {
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
    }

    var keyInput = document.getElementById('subscription-upgrade-package-key');
    var nameEl = document.getElementById('subscription-upgrade-package-name');
    var amountEl = document.getElementById('subscription-upgrade-package-amount');

    bindConfirmModal({
        modalId: 'subscription-upgrade-modal',
        formId: 'subscription-upgrade-form',
        confirmId: 'subscription-upgrade-confirm',
        openSelector: '[data-upgrade-open]',
        dismissSelector: '[data-upgrade-dismiss]',
        requireValueId: 'subscription-upgrade-package-key',
        onOpen: function (btn) {
            if (keyInput) {
                keyInput.value = btn.getAttribute('data-package-key') || '';
            }
            if (nameEl) {
                nameEl.textContent = btn.getAttribute('data-package-name') || '';
            }
            if (amountEl) {
                amountEl.textContent = btn.getAttribute('data-package-amount') || '';
            }
        },
        onClose: function () {
            if (keyInput) {
                keyInput.value = '';
            }
        }
    });

    bindConfirmModal({
        modalId: 'subscription-cancel-modal',
        formId: 'subscription-cancel-form',
        confirmId: 'subscription-cancel-confirm',
        openSelector: '[data-cancel-open]',
        dismissSelector: '[data-cancel-dismiss]'
    });

    bindConfirmModal({
        modalId: 'subscription-end-trial-modal',
        formId: 'subscription-end-trial-form',
        confirmId: 'subscription-end-trial-confirm',
        openSelector: '[data-end-trial-open]',
        dismissSelector: '[data-end-trial-dismiss]'
    });

    var addonForm = document.getElementById('subscription-addon-cancel-form');
    var addonLead = document.getElementById('subscription-addon-cancel-lead');
    var addonBody = document.getElementById('subscription-addon-cancel-body');
    var addonHint = document.getElementById('subscription-addon-cancel-hint');
    var addonConfirm = document.getElementById('subscription-addon-cancel-confirm');
    var addonCancelUrls = @json(collect($addons ?? [])->mapWithKeys(fn ($addon) => [$addon['key'] => route('admin.subscriptions.addons.cancel', $addon['key'])]));

    bindConfirmModal({
        modalId: 'subscription-addon-cancel-modal',
        formId: 'subscription-addon-cancel-form',
        confirmId: 'subscription-addon-cancel-confirm',
        openSelector: '[data-addon-cancel-open]',
        dismissSelector: '[data-addon-cancel-dismiss]',
        onOpen: function (btn) {
            var key = btn.getAttribute('data-addon-key') || '';
            var name = btn.getAttribute('data-addon-name') || 'deze module';
            var when = btn.getAttribute('data-addon-when') || '';
            var trial = btn.getAttribute('data-addon-trial') === '1';
            if (addonForm) {
                addonForm.action = addonCancelUrls[key] || '';
            }
            if (addonLead) {
                addonLead.innerHTML = trial
                    ? 'Tijdens de <strong class="text-foreground">proefperiode</strong> zeg je kosteloos op.'
                    : 'Opzeggen gaat per de <strong class="text-foreground">1e van volgende maand</strong>.';
            }
            if (addonBody) {
                addonBody.textContent = trial
                    ? 'Je zegt ' + name + ' op. Tijdens de proef kun je de module blijven gebruiken. Ná de proef wordt hij niet gefactureerd.'
                    : 'Je zegt ' + name + ' op per ' + when + '. Tot die datum blijft de module actief.';
            }
            if (addonHint) {
                addonHint.textContent = trial
                    ? 'Er volgt geen factuur voor deze module.'
                    : 'Het extra maandbedrag stopt vanaf ' + when + '.';
            }
            if (addonConfirm) {
                addonConfirm.textContent = trial
                    ? 'Opzeggen (proefperiode)'
                    : ['Opzeggen', 'per', when].join(' ');
            }
        }
    });
})();
</script>
@endif
@endsection
