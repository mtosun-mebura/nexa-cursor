{{--
    Standaard admin image upload (Klik of Sleep & Drop) — zelfde patroon als
    website-pages home-sections footer-logo en Algemene instellingen branding.
    Props: name, inputId, previewId, areaId, linkId, removeBtnId, existingUrl (nullable), errorKey (optional)
--}}
@php
    $name = $name ?? 'logo';
    $inputId = $inputId ?? 'image-upload-input';
    $previewId = $previewId ?? 'image-upload-preview';
    $areaId = $areaId ?? 'image-upload-area';
    $linkId = $linkId ?? 'image-upload-link';
    $removeBtnId = $removeBtnId ?? 'image-upload-remove';
    $existingUrl = $existingUrl ?? null;
    $accept = $accept ?? 'image/svg+xml,image/png,image/jpeg,image/jpg,image/gif';
    $dropzoneKey = $dropzoneKey ?? '1';
    $clientMsgId = $clientMsgId ?? null;
    $hintLine = $hintLine ?? 'SVG, PNG, JPG (max. 2MB)';
    $maxFileBytes = $maxFileBytes ?? (2 * 1024 * 1024);
    $livePreviewLightId = $livePreviewLightId ?? null;
    $livePreviewDarkId = $livePreviewDarkId ?? null;
    $logoModeInputId = $logoModeInputId ?? null;
@endphp
<div class="flex flex-col gap-2 w-full min-w-0"
    data-image-dropzone="{{ e($dropzoneKey) }}"
    data-existing-url="{{ $existingUrl ? e($existingUrl) : '' }}"
    data-max-file-bytes="{{ (int) $maxFileBytes }}"
    data-logo-dropzone-init
    data-input-id="{{ e($inputId) }}"
    data-preview-id="{{ e($previewId) }}"
    data-area-id="{{ e($areaId) }}"
    data-link-id="{{ e($linkId) }}"
    data-remove-id="{{ e($removeBtnId) }}"
    @if($clientMsgId) data-client-msg-id="{{ e($clientMsgId) }}" @endif
    @if($livePreviewLightId) data-live-preview-light-id="{{ e($livePreviewLightId) }}" @endif
    @if($livePreviewDarkId) data-live-preview-dark-id="{{ e($livePreviewDarkId) }}" @endif
    @if($logoModeInputId) data-logo-mode-input-id="{{ e($logoModeInputId) }}" @endif>
    <div class="flex flex-col items-center w-full shrink-0">
        <div data-logo-preview-frame="1"
            class="flex items-center justify-center w-full max-w-[250px] h-[90px] max-h-[90px] border border-border rounded bg-muted/20 overflow-hidden {{ $existingUrl ? '' : 'hidden' }}">
            <img alt="Voorbeeld" id="{{ $previewId }}"
                class="max-w-full max-h-full w-auto h-auto object-contain object-center {{ $existingUrl ? '' : 'hidden' }}"
                src="{{ $existingUrl ?: '' }}">
        </div>
        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1 shadow hover:bg-destructive/10 {{ $existingUrl ? '' : 'hidden' }}"
            id="{{ $removeBtnId }}" title="Logo verwijderen" aria-label="Logo verwijderen">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
        </button>
    </div>
    <div class="flex flex-col items-center justify-center w-full p-5 lg:p-7 border border-input rounded-xl border-dashed bg-muted/30 min-h-[130px] min-w-0 cursor-pointer hover:border-primary transition-colors"
        id="{{ $areaId }}" role="button" tabindex="0">
        <div class="flex flex-col place-items-center place-content-center text-center w-full pointer-events-none">
            <div class="flex items-center mb-2.5">
                <div class="relative size-11 shrink-0 flex items-center justify-center">
                    <i class="ki-filled ki-picture text-2xl text-primary"></i>
                </div>
            </div>
            <a class="text-mono text-xs font-medium hover:text-primary mb-px cursor-pointer pointer-events-auto" id="{{ $linkId }}">Klik of Sleep &amp; Drop</a>
            <span class="text-xs text-muted-foreground">{{ $hintLine }}</span>
        </div>
    </div>
    <input type="file" name="{{ $name }}" id="{{ $inputId }}" accept="{{ $accept }}" class="hidden">
</div>
