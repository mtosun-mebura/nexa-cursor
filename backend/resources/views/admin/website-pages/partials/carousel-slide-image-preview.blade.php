@php
    $uuid = trim((string) ($uuid ?? ''));
    $hasImage = $uuid !== '';
    $imageUrl = $hasImage ? url('/website-media/'.$uuid) : '';
@endphp
<div class="carousel-slide-preview shrink-0 flex flex-col items-center gap-1">
    <div class="relative h-20 w-28 shrink-0">
        <img src="{{ $imageUrl }}"
             alt=""
             class="carousel-slide-preview-img absolute inset-0 h-full w-full object-cover rounded border border-border {{ $hasImage ? 'cursor-zoom-in' : 'hidden' }}"
             @if($hasImage) data-carousel-preview-uuid="{{ $uuid }}" title="Klik om groot te bekijken" role="button" tabindex="0" @endif>
        <div class="carousel-slide-preview-upload absolute inset-0 rounded border border-dashed border-border bg-muted/30 flex flex-col items-center justify-center gap-0.5 text-center px-1 cursor-pointer hover:border-primary hover:bg-muted/50 {{ $hasImage ? 'hidden' : '' }}"
             role="button"
             tabindex="0"
             aria-label="Afbeelding uploaden voor deze slide">
            <span class="text-xs font-medium text-secondary-foreground leading-tight">Klik of sleep</span>
            <span class="text-[10px] text-muted-foreground leading-tight">JPG, PNG, WebP</span>
            <span class="carousel-slide-preview-upload-status text-[10px] text-primary hidden" aria-live="polite"></span>
        </div>
        <input type="file"
               class="carousel-slide-preview-file-input hidden"
               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
               tabindex="-1"
               aria-hidden="true">
    </div>
    <button type="button"
            class="carousel-slide-image-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-destructive {{ $hasImage ? '' : 'hidden' }}"
            title="Afbeelding verwijderen"
            aria-label="Afbeelding verwijderen">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
    </button>
</div>
