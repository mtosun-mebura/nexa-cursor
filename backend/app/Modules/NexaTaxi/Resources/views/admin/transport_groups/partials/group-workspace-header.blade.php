@php
    $workspaceTitle = $workspaceTitle ?? $group->name;
    $workspaceSubtitle = $workspaceSubtitle ?? ($contract->name.' · '.$customer->name);
    $groupsIndexUrl = route('admin.taxi.transport_groups.index', [$customer->id, $contract->id]);
    $customerShowUrl = route('admin.taxi.transport_customers.show', [
        'id' => $customer->id,
        'section' => 'abonnementen',
    ]);
    $contractShowUrl = route('admin.taxi.transport_customers.contract_show', [$customer->id, $contract->id]);
@endphp

<div class="flex flex-wrap items-start justify-between gap-3 pb-5">
    <div class="min-w-0">
        <nav class="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground mb-2" aria-label="Kruimelpad">
            <a href="{{ route('admin.taxi.transport_customers.index') }}" class="hover:text-primary">Klanten</a>
            <span aria-hidden="true">/</span>
            <a href="{{ $customerShowUrl }}" class="hover:text-primary truncate max-w-[10rem] sm:max-w-none">{{ $customer->name }}</a>
            <span aria-hidden="true">/</span>
            <a href="{{ $contractShowUrl }}" class="hover:text-primary truncate max-w-[10rem] sm:max-w-none">{{ $contract->name }}</a>
            <span aria-hidden="true">/</span>
            <a href="{{ $groupsIndexUrl }}" class="hover:text-primary">Groepen</a>
            <span aria-hidden="true">/</span>
            <span class="text-foreground font-medium truncate max-w-[12rem] sm:max-w-none">{{ $group->name }}</span>
        </nav>
        <h1 class="text-xl font-medium leading-none text-mono">{{ $workspaceTitle }}</h1>
        <p class="text-sm text-muted-foreground pt-2 mb-0">{{ $workspaceSubtitle }}</p>
        <div class="pt-3 flex flex-wrap gap-2">
            <a href="{{ $groupsIndexUrl }}" class="kt-btn kt-btn-outline kt-btn-sm">
                <i class="ki-filled ki-arrow-left me-1.5"></i>
                Alle groepen
            </a>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 shrink-0">
        @if($group->active)
            <span class="kt-badge kt-badge-success kt-badge-sm">Actief</span>
        @else
            <span class="kt-badge kt-badge-secondary kt-badge-sm">Inactief</span>
        @endif
        @if(! empty($workspaceHeaderActions))
            {!! $workspaceHeaderActions !!}
        @else
            @can('rides.update')
            <a href="{{ transport_admin_url_with_return(route('admin.taxi.transport_groups.edit', [$customer->id, $contract->id, $group->id]), url()->full()) }}" class="kt-btn kt-btn-outline">
                Bewerken
            </a>
            @endcan
        @endif
    </div>
</div>

@include('taxi::admin.transport_groups.partials.group-workspace-nav')
