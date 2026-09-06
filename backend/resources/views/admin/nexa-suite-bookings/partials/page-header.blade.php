@php
    $title = $title ?? 'NEXA Suite ritten';
    $subtitle = $subtitle ?? '';
@endphp
<div class="admin-nexa-suite-page-header flex flex-wrap items-center lg:items-end justify-between gap-x-5 gap-y-3 pb-7.5">
    <div class="flex flex-col justify-center gap-2">
        <h1 class="text-xl font-medium leading-none text-mono">{{ $title }}</h1>
        <p class="text-sm text-secondary-foreground mb-0">{{ $subtitle }}</p>
    </div>
    <div class="admin-nexa-suite-page-header-actions flex flex-wrap items-center justify-end gap-2.5">
        {{ $slot ?? '' }}
    </div>
</div>
