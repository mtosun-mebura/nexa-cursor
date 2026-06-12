@props([
    'id' => 'admin-info-' . uniqid(),
    'content' => '',
])

@php
    $tooltipHtml = trim($content !== '' ? $content : (string) $slot);
@endphp

@if ($tooltipHtml !== '')
<span class="admin-info-hover shrink-0 inline-flex">
    <button type="button"
            class="admin-info-hover-trigger kt-btn kt-btn-icon kt-btn-ghost text-muted-foreground"
            aria-label="Meer informatie"
            aria-describedby="{{ $id }}">
        <i class="ki-filled ki-information-2 admin-info-hover-icon" aria-hidden="true"></i>
    </button>
    <span id="{{ $id }}" role="tooltip" class="admin-info-hover-panel">{!! $tooltipHtml !!}</span>
</span>
@endif
