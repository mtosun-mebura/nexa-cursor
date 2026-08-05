@props(['paginator'])

@if($paginator->total() > 0)
    <div class="kt-card-footer flex flex-wrap items-center justify-between gap-3 py-4 px-5 border-t border-input">
        <div class="text-sm text-secondary-foreground">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} van {{ $paginator->total() }}
        </div>
        @if($paginator->hasPages())
            {{ $paginator->withQueryString()->links() }}
        @endif
    </div>
@endif
