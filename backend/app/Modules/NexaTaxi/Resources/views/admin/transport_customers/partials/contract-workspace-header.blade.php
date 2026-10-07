@php
    $workspaceTitle = $workspaceTitle ?? $contract->name;
    $workspaceSubtitle = $workspaceSubtitle ?? null;
    $customerShowUrl = route('admin.taxi.transport_customers.show', [
        'id' => $customer->id,
        'section' => 'abonnementen',
    ]);
@endphp

<div class="flex flex-wrap items-start justify-between gap-3 pb-5">
    <div class="min-w-0">
        <nav class="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground mb-2" aria-label="Kruimelpad">
            <a href="{{ route('admin.taxi.transport_customers.index') }}" class="hover:text-primary">Klanten</a>
            <span aria-hidden="true">/</span>
            <a href="{{ $customerShowUrl }}" class="hover:text-primary truncate max-w-[12rem] sm:max-w-none">{{ $customer->name }}</a>
            <span aria-hidden="true">/</span>
            <span class="text-foreground font-medium truncate max-w-[14rem] sm:max-w-none">{{ $contract->name }}</span>
        </nav>
        <h1 class="text-xl font-medium leading-none text-mono">{{ $workspaceTitle }}</h1>
        @if($workspaceSubtitle)
            <p class="text-sm text-muted-foreground pt-2 mb-0">{{ $workspaceSubtitle }}</p>
        @else
            <p class="text-sm text-muted-foreground pt-2 mb-0">
                Abonnement van {{ $customer->name }}
            </p>
        @endif
        <div class="pt-3 flex flex-wrap gap-2">
            <a href="{{ $customerShowUrl }}" class="kt-btn kt-btn-outline kt-btn-sm">
                <i class="ki-filled ki-arrow-left me-1.5"></i>
                Naar klant
            </a>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 shrink-0">
        @if($contract->status === 'active')
            <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
        @elseif($contract->status === 'paused')
            <span class="kt-badge kt-badge-warning kt-badge-sm">Gepauzeerd</span>
        @else
            <span class="kt-badge kt-badge-secondary kt-badge-sm">Beëindigd</span>
        @endif
        @isset($workspaceHeaderActions)
            {!! $workspaceHeaderActions !!}
        @else
            @can('rides.update')
            <a href="{{ route('admin.taxi.transport_customers.contract_edit', [$customer->id, $contract->id]) }}" class="kt-btn kt-btn-outline">
                Abonnement bewerken
            </a>
            @endcan
        @endisset
    </div>
</div>

@include('taxi::admin.transport_customers.partials.contract-workspace-nav')
