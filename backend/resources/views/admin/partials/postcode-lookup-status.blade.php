@php
    $statusId = $id ?? 'postcode_lookup_loading';
@endphp
<div id="{{ $statusId }}"
     class="postcode-lookup-status hidden items-center gap-2 text-xs text-muted-foreground mt-1.5"
     role="status"
     aria-live="polite"
     aria-busy="false">
    <span class="postcode-lookup-spinner shrink-0" aria-hidden="true"></span>
    <span>Adres opzoeken…</span>
</div>
@once
<style>
    @keyframes postcode-lookup-spin {
        to { transform: rotate(360deg); }
    }
    .postcode-lookup-spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid var(--border, #e5e7eb);
        border-top-color: var(--primary, #3b82f6);
        border-radius: 9999px;
        animation: postcode-lookup-spin 0.65s linear infinite;
    }
    .dark .postcode-lookup-spinner {
        border-color: rgba(255, 255, 255, 0.2);
        border-top-color: var(--primary, #60a5fa);
    }
    .postcode-lookup-status:not(.hidden) {
        display: flex;
    }
</style>
@endonce
