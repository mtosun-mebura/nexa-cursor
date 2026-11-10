@php
    $url = '/website-media/'.$image->website_media_uuid;
@endphp
<div class="ai-image-card" draggable="true" data-uuid="{{ $image->website_media_uuid }}" data-url="{{ $url }}" data-download-url="{{ $url }}?download=1" data-prompt="{{ $image->prompt }}" data-delete-id="{{ $image->id }}">
    <img src="{{ $url }}" alt="" loading="lazy">
    <div class="ai-image-card__actions">
        <button type="button" data-action="open" data-label="{{ __('Bekijken') }}" aria-label="{{ __('Bekijken') }}"><i class="ki-outline ki-eye"></i></button>
        <button type="button" data-action="reuse" data-label="{{ __('Hergebruiken') }}" aria-label="{{ __('Hergebruiken als bron') }}"><i class="ki-outline ki-arrow-up"></i></button>
        <button type="button" data-action="copy" data-label="{{ __('Kopiëren') }}" aria-label="{{ __('Kopiëren') }}"><i class="ki-outline ki-copy"></i></button>
        <button type="button" data-action="download" data-label="{{ __('Downloaden') }}" aria-label="{{ __('Downloaden') }}"><i class="ki-outline ki-file-down"></i></button>
        <button type="button" data-action="delete" data-label="{{ __('Verwijderen') }}" aria-label="{{ __('Verwijderen') }}"><i class="ki-outline ki-trash"></i></button>
    </div>
</div>
