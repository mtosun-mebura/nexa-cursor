@props([
    'active' => 'Dashboard',
    'title' => 'Pagina',
    'companyName' => 'Uw taxibedrijf',
])
<div class="flex gap-3 min-h-[220px] rounded-lg ring-1 ring-border overflow-hidden bg-muted/10">
    <div class="handleiding-mock-sidebar">
        <div class="px-2 py-2 mb-1 text-[10px] font-semibold text-foreground truncate">{{ $companyName }}</div>
        @foreach(['Dashboard', 'Handleiding', 'Ritten', 'Voertuigen', 'Tarieven', 'Gebruikers', 'Website', 'Facturen', 'Abonnementen'] as $item)
            <div class="handleiding-mock-menu-item {{ $item === $active ? 'is-active' : '' }}">
                <span class="size-1.5 rounded-full {{ $item === $active ? 'bg-primary' : 'bg-muted-foreground/40' }}"></span>
                {{ $item }}
            </div>
        @endforeach
    </div>
    <div class="flex-1 min-w-0 p-3">
        <div class="flex items-center justify-between gap-2 mb-3">
            <div class="text-sm font-semibold text-foreground">{{ $title }}</div>
            {{ $toolbar ?? '' }}
        </div>
        {{ $slot }}
    </div>
</div>
