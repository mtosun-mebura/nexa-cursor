@php
    $url = '/website-media/'.$image->website_media_uuid;
@endphp
<div class="ai-image-card" draggable="true" data-uuid="{{ $image->website_media_uuid }}" data-url="{{ $url }}" data-download-url="{{ $url }}?download=1" data-prompt="{{ $image->prompt }}" data-delete-id="{{ $image->id }}">
    <img src="{{ $url }}" alt="" loading="lazy">
    <div class="ai-image-card__actions">
        <button type="button" data-action="open" title="Bekijken"><i class="ki-filled ki-eye"></i></button>
        <button type="button" data-action="copy" title="Kopiëren"><i class="ki-filled ki-copy"></i></button>
        <button type="button" data-action="download" title="Downloaden"><i class="ki-filled ki-file-down"></i></button>
        <button type="button" data-action="delete" title="Verwijderen"><i class="ki-filled ki-trash"></i></button>
    </div>
</div>
