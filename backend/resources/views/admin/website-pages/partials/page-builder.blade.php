{{-- Page builder: vaste blokken per thema (geen vrije "Component toevoegen"). Blokken worden geladen op basis van gekozen module/thema. --}}
@push('styles')
<style>
    .builder-frame { min-height: 200px; }
    .builder-card { transition: box-shadow 0.2s; }
    .builder-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .builder-card.sortable-ghost { opacity: 0.4; }
    .builder-drag-handle { cursor: grab; }
    .builder-drag-handle:active { cursor: grabbing; }
</style>
@endpush

<div class="builder-frame rounded-xl border-2 border-dashed border-input p-4">
    <p class="text-xs text-muted-foreground mb-3" id="builder-theme-hint">Kies een module (of Geen voor kernpagina&apos;s); de vaste blokken van het bijbehorende thema worden hier geladen. Vul de inhoud per blok in. Sleep om de volgorde te wijzigen; gebruik de breedte-knoppen voor responsive layout.</p>
    <div id="builder-blocks" class="grid grid-cols-12 gap-4">
        {{-- Blokken worden via JS geladen op basis van thema (module_choice) --}}
    </div>
    <div id="builder-empty" class="text-center py-12 text-muted-foreground text-sm">
        Geen blokken. Kies een module om de thema-blokken te laden.
    </div>
</div>
<input type="hidden" name="content" id="page_content_json" value="{{ isset($contentJson) ? e($contentJson) : '' }}">

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="{{ asset('js/page-builder.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var initialJson = document.getElementById('page_content_json').value;
    var themeBlocksUrl = '{{ route("admin.website-pages.theme-blocks") }}';
    window.PageBuilder.init({
        containerId: 'builder-blocks',
        emptyId: 'builder-empty',
        inputId: 'page_content_json',
        addBtnId: null,
        addMenuId: null,
        addMenuTypesSelector: null,
        initialJson: initialJson,
        uploadUrl: '{{ route("admin.website-media.upload") }}',
        uploadCsrfToken: '{{ csrf_token() }}',
        themeBlocksUrl: themeBlocksUrl,
        useThemeBlocksOnly: true
    });
    var form = document.getElementById('website-page-form');
    if (form) {
        form.addEventListener('submit', function() {
            if (typeof tinymce !== 'undefined' && tinymce.triggerSave) tinymce.triggerSave();
            if (window.PageBuilder && window.PageBuilder.syncToInput) window.PageBuilder.syncToInput();
        });
    }
    var moduleChoice = document.getElementById('module_choice');
    if (moduleChoice && window.PageBuilder.loadThemeBlocksForModule) {
        function loadThemeBlocks() {
            var moduleName = moduleChoice.value || '';
            window.PageBuilder.loadThemeBlocksForModule(moduleName);
        }
        moduleChoice.addEventListener('change', loadThemeBlocks);
        var hasExistingBlocks = false;
        try {
            var parsed = initialJson && initialJson.trim() ? JSON.parse(initialJson) : null;
            hasExistingBlocks = !!(parsed && parsed.blocks && parsed.blocks.length);
        } catch (e) {}
        if (!hasExistingBlocks) loadThemeBlocks();
    }
});
</script>
@endpush
