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
</style>
@endpush

@section('content')
<div class="kt-container-fixed min-w-0">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Abonnementen</h1>
            <div class="text-sm font-normal text-secondary-foreground">
                Jaarcontract met maandelijkse incasso's. Upgraden kan direct; downgraden en opzeggen per einde contract.
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
        <div class="kt-alert kt-alert-warning mb-5" role="status">
            @if($pending_type === 'cancel')
                Opzegging ingepland per <strong>{{ $pending_effective_on?->translatedFormat('j F Y') }}</strong>. Tot die datum blijft je huidige pakket actief; daarna stopt de SEPA-incasso.
            @elseif($pending_type === 'downgrade')
                Downgrade naar <strong>{{ $pending_package_name }}</strong> ingepland per <strong>{{ $pending_effective_on?->translatedFormat('j F Y') }}</strong>. Tot die datum blijf je {{ $current_name }} gebruiken tegen de huidige prijs.
            @endif
            <form action="{{ route('admin.subscriptions.withdraw') }}" method="POST" class="mt-3">
                @csrf
                <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">Wijziging intrekken</button>
            </form>
        </div>
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
                    <tr>
                        <td class="text-secondary-foreground font-normal">Einde jaarcontract</td>
                        <td class="font-medium text-foreground">{{ $contract_end_date->translatedFormat('j F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-secondary-foreground font-normal">Opzegtermijn</td>
                        <td class="font-medium text-foreground">
                            @if($ended)
                                Abonnement beëindigd.
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
                                    <form action="{{ route('admin.subscriptions.upgrade') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="package_key" value="{{ $package['key'] }}">
                                        <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm w-full"
                                            onclick="return confirm('Upgraden naar {{ $package['name'] }} gaat per direct. De nieuwe prijs geldt vanaf vandaag en wordt meegenomen in de SEPA-incasso. Doorgaan?')">
                                            Nu upgraden
                                        </button>
                                    </form>
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

            @if($cancel_allowed)
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
@endsection
