@php
    $tone = $tone ?? 'primary';
    $toneClass = match ($tone) {
        'success' => 'text-success',
        'warning' => 'text-warning',
        'danger' => 'text-destructive',
        'info' => 'text-info',
        'secondary' => 'text-secondary-foreground',
        default => 'text-primary',
    };
@endphp
<div class="rounded-xl border border-border bg-background/40 p-4 min-w-0">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs font-medium text-secondary-foreground">{{ $label }}</div>
            <div class="mt-1 text-xl sm:text-2xl font-semibold text-mono tabular-nums leading-tight">{{ $value }}</div>
            @if(!empty($hint))
                <div class="mt-1 text-[11px] leading-snug {{ $hintClass ?? 'text-muted-foreground' }}">{{ $hint }}</div>
            @endif
        </div>
        <i class="ki-filled {{ $icon }} {{ $toneClass }} text-xl shrink-0 mt-0.5" aria-hidden="true"></i>
    </div>
</div>
