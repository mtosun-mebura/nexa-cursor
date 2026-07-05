@props([
    'caption' => null,
    'title' => 'Schermvoorbeeld',
])
<figure {{ $attributes->merge(['class' => 'handleiding-figure my-6']) }}>
    @if($caption)
        <figcaption class="mb-2 text-sm font-medium text-foreground">{{ $caption }}</figcaption>
    @endif
    <div class="handleiding-screenshot-frame overflow-hidden rounded-xl border border-border bg-muted/20 shadow-sm ring-1 ring-black/5 dark:ring-white/10">
        <div class="handleiding-screenshot-chrome flex items-center gap-2 border-b border-border bg-background/80 px-3 py-2">
            <span class="size-2.5 rounded-full bg-red-400/90"></span>
            <span class="size-2.5 rounded-full bg-amber-400/90"></span>
            <span class="size-2.5 rounded-full bg-emerald-400/90"></span>
            <span class="ms-2 truncate text-xs text-muted-foreground">{{ $title }}</span>
        </div>
        <div class="handleiding-screenshot-body bg-background p-3 sm:p-4">
            {{ $slot }}
        </div>
    </div>
</figure>
