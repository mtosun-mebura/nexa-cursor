@php
    $requests = $requests ?? [];
@endphp

<div class="platform-billing-mollie-requests space-y-3">
    @forelse($requests as $request)
        <details class="kt-card border border-border overflow-hidden">
            <summary class="kt-card-header cursor-pointer select-none list-none flex items-center gap-2.5 py-3 px-4 lg:px-5 [&::-webkit-details-marker]:hidden">
                <span class="kt-badge kt-badge-sm kt-badge-outline font-mono shrink-0">{{ $request['method'] }}</span>
                <span class="text-sm font-medium text-foreground">{{ $request['label'] }}</span>
                <i class="ki-filled ki-down ms-auto text-muted-foreground text-xs transition-transform [[open]_&]:rotate-180"></i>
            </summary>
            <div class="kt-card-content border-t border-border p-4 lg:p-5 pt-4 space-y-3">
                <p class="text-sm text-secondary-foreground">{{ $request['description'] }}</p>
                <p class="text-xs font-mono text-muted-foreground break-all">{{ $request['endpoint'] }}</p>
                <pre class="text-xs leading-relaxed bg-muted/40 border border-border rounded-md p-3 overflow-x-auto whitespace-pre-wrap break-words">{{ json_encode($request['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </details>
    @empty
        <p class="text-sm text-secondary-foreground">Geen Mollie API-requests van toepassing voor deze instellingen.</p>
    @endforelse
</div>
