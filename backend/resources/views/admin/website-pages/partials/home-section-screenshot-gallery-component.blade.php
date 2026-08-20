@php
    $galleryData = $sections[$sectionKey] ?? [];
    $galleryItems = isset($galleryData['items']) && is_array($galleryData['items']) ? array_values($galleryData['items']) : [];
    if ($galleryItems === []) {
        $galleryItems = [['image_url' => '', 'caption' => '', 'alt' => '', 'crop' => 'none']];
    }
    $galleryLayout = in_array(($galleryData['layout'] ?? ''), ['stack', 'grid'], true) ? $galleryData['layout'] : 'stack';
    $galleryCrops = ['none' => 'Geen crop', 'phone' => 'Telefoon (inzoomen)', 'portal' => 'Portaal (inzoomen)'];
    $imagePreviewUrl = $imagePreviewUrl ?? function ($url) {
        return app(\App\Services\WebsiteBuilderService::class)->storageUrlToDisplayUrl($url ?? '');
    };
@endphp
<div class="kt-card home-section-card home-section-card--component home-section-card--module @if($isCardCollapsed) home-section-card--collapsed @endif" data-section="{{ $sectionKey }}">
    <div class="kt-card-header home-section-header home-section-header--component flex items-center justify-between gap-2">
        <span class="home-section-drag-handle cursor-grab active:cursor-grabbing touch-none p-1 -ml-1 rounded text-muted-foreground hover:text-foreground" title="Sleep om volgorde te wijzigen" aria-label="Volgorde wijzigen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" /></svg></span>
        <h3 class="kt-card-title">Screenshot-galerij (Algemeen)</h3>
        <div class="flex items-center gap-1 shrink-0">
            <input type="hidden" name="home_sections[visibility][{{ $sectionKey }}]" id="visibility-{{ $sectionKey }}" value="{{ $vis('') ? '1' : '0' }}">
            <button type="button" class="section-visibility-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" data-target="visibility-{{ $sectionKey }}" title="{{ $vis('') ? 'Verbergen op website' : 'Tonen op website' }}" aria-label="Zichtbaarheid">@if($vis(''))<svg class="w-5 h-5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>@else<svg class="w-5 h-5 text-current opacity-60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>@endif</button>
            <button type="button" class="home-section-collapse-toggle kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-foreground" title="Inklappen" aria-label="Sectie inklappen of uitklappen"><svg class="w-5 h-5 text-current home-section-collapse-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg></button>
            <button type="button" class="home-section-component-remove kt-btn kt-btn-icon kt-btn-sm kt-btn-ghost text-muted-foreground hover:text-destructive" title="Component van pagina verwijderen" aria-label="Component verwijderen"><svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
        </div>
    </div>
    <div class="home-section-card-body kt-card-table p-4 space-y-4">
        <p class="text-sm text-muted-foreground">Productscreenshots met bijschrift. Sleep een afbeelding in de zone of upload via klikken.</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="text-sm text-muted-foreground">Titel</label>
                <input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][title]" value="{{ old('home_sections.'.$sectionKey.'.title', $galleryData['title'] ?? '') }}">
            </div>
            <div>
                <label class="text-sm text-muted-foreground">Subtitel</label>
                <input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][subtitle]" value="{{ old('home_sections.'.$sectionKey.'.subtitle', $galleryData['subtitle'] ?? '') }}">
            </div>
            <div>
                <label class="text-sm text-muted-foreground">Layout</label>
                <select class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][layout]">
                    <option value="stack" {{ $galleryLayout === 'stack' ? 'selected' : '' }}>Gestapeld (één kolom)</option>
                    <option value="grid" {{ $galleryLayout === 'grid' ? 'selected' : '' }}>Grid</option>
                </select>
            </div>
        </div>
        <div class="space-y-4 nexa-screenshot-gallery-items" data-section-key="{{ $sectionKey }}">
            @foreach($galleryItems as $i => $item)
            <div class="nexa-screenshot-gallery-item border border-border rounded-lg p-4 space-y-3" data-item-index="{{ $i }}">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium">Screenshot {{ $i + 1 }}</span>
                    <button type="button" class="nexa-screenshot-gallery-item-remove kt-btn kt-btn-icon kt-btn-xs kt-btn-ghost text-muted-foreground hover:text-destructive" title="Verwijderen" aria-label="Verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                </div>
                <div class="flex flex-wrap items-start gap-2">
                    <div class="shrink-0 flex flex-col items-center">
                        <img alt="Screenshot {{ $i + 1 }}" id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-preview" class="w-full max-w-[200px] max-h-24 object-cover border border-border rounded {{ !empty($item['image_url']) ? '' : 'hidden' }}" src="{{ $imagePreviewUrl($item['image_url'] ?? '') }}">
                        <button type="button" class="image-remove-btn kt-btn kt-btn-xs kt-btn-ghost text-destructive mt-1" data-url-input-id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url" data-preview-id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-preview" title="Afbeelding verwijderen" aria-label="Afbeelding verwijderen"><svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </div>
                    <div class="hero-image-upload-area flex flex-col items-center justify-center p-5 border border-input rounded-xl border-dashed bg-muted/30" data-section-key="{{ $sectionKey }}" data-field="items_{{ $i }}_image_url" data-url-input-id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url" data-file-input-id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-file" data-preview-id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-preview" style="width: 500px; min-width: 280px; height: 130px;">
                        <span class="text-xs text-muted-foreground">Klik of sleep afbeelding</span>
                        <span class="text-xs text-muted-foreground">JPG, PNG, WebP (max. 5MB)</span>
                    </div>
                </div>
                <input type="file" id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url-file" class="hero-image-file-input hidden" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" data-section-key="{{ $sectionKey }}" data-field="items_{{ $i }}_image_url">
                <input type="hidden" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][image_url]" id="hero-{{ $sectionKey }}-items_{{ $i }}_image_url" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.image_url', $item['image_url'] ?? '') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="text-sm text-muted-foreground">Bijschrift</label>
                        <input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][caption]" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.caption', $item['caption'] ?? '') }}">
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground">Alt-tekst</label>
                        <input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][alt]" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.alt', $item['alt'] ?? '') }}">
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground">Link (optioneel)</label>
                        <input type="text" class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][url]" value="{{ old('home_sections.'.$sectionKey.'.items.'.$i.'.url', $item['url'] ?? '') }}" placeholder="/taxi">
                    </div>
                    <div>
                        <label class="text-sm text-muted-foreground">Crop</label>
                        <select class="kt-input mt-1 w-full text-sm" name="home_sections[{{ $sectionKey }}][items][{{ $i }}][crop]">
                            @foreach($galleryCrops as $val => $label)
                            <option value="{{ $val }}" {{ ($item['crop'] ?? 'none') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <button type="button" class="kt-btn kt-btn-xs kt-btn-outline nexa-screenshot-gallery-item-add" data-section-key="{{ $sectionKey }}">+ Screenshot toevoegen</button>
    </div>
</div>
