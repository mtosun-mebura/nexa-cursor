{{-- Editor.js block-editor voor website-pagina's. Componenten: koppen, tekst, lijsten, tabellen, afbeeldingen, citaten, code. --}}
@push('styles')
<style>
    .codex-editor__redactor { padding-bottom: 100px !important; }
    .ce-block__content, .ce-toolbar__content { max-width: 100% !important; }
    .ce-toolbar__plus { color: var(--color-primary, #2563eb); }
    .ce-toolbar__plus:hover { background: rgba(37, 99, 235, 0.1); }
</style>
@endpush

<div id="editorjs" class="min-h-[320px] rounded-lg border border-input bg-background p-4"></div>
<input type="hidden" name="content" id="page_content_json" value="{{ isset($contentJson) ? e($contentJson) : '' }}">

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/table@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/simple-image@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@2"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/code@2"></script>
<script src="{{ asset('js/editorjs-image-aligned.js') }}"></script>
<script src="{{ asset('js/editorjs-slider.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var holder = document.getElementById('editorjs');
    var hiddenInput = document.getElementById('page_content_json');
    if (!holder || !hiddenInput) return;

    var initialData = null;
    try {
        var val = hiddenInput.value && hiddenInput.value.trim();
        if (val) initialData = JSON.parse(val);
    } catch (e) {}

    var tools = {};
    if (typeof window.Header !== 'undefined') {
        tools.header = { class: window.Header, config: { placeholder: 'Kop', levels: [1, 2, 3], defaultLevel: 2 } };
    }
    if (typeof window.List !== 'undefined') {
        tools.list = { class: window.List, inlineToolbar: true, config: { defaultStyle: 'unordered' } };
    }
    if (typeof window.Table !== 'undefined') {
        tools.table = { class: window.Table, inlineToolbar: true, config: { rows: 2, cols: 3 } };
    }
    if (typeof window.SimpleImage !== 'undefined') {
        tools.image = { class: window.SimpleImage, inlineToolbar: false };
    }
    if (typeof window.Quote !== 'undefined') {
        tools.quote = { class: window.Quote, inlineToolbar: true, config: { quotePlaceholder: 'Citaat', captionPlaceholder: 'Bron' } };
    }
    if (typeof window.CodeTool !== 'undefined') {
        tools.code = { class: window.CodeTool, config: { placeholder: 'Code plakken' } };
    }
    if (typeof window.EditorJsImageAligned !== 'undefined') {
        tools.imageAligned = { class: window.EditorJsImageAligned };
    }
    if (typeof window.EditorJsSlider !== 'undefined') {
        tools.slider = { class: window.EditorJsSlider };
    }

    var editor = new EditorJS({
        holder: 'editorjs',
        placeholder: 'Voeg blokken toe met de + knop: koppen, tekst, lijsten, tabellen, afbeeldingen (met uitlijning), slider, citaten, code.',
        data: initialData || undefined,
        tools: tools,
        onChange: function() {
            editor.save().then(function(data) {
                hiddenInput.value = JSON.stringify(data);
            }).catch(function() {});
        }
    });

    window._websitePageEditor = editor;

    var form = document.getElementById('website-page-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var f = this;
            if (typeof tinymce !== 'undefined' && tinymce.triggerSave) tinymce.triggerSave();
            editor.save().then(function(data) {
                hiddenInput.value = JSON.stringify(data);
                f.submit();
            }).catch(function() {
                hiddenInput.value = JSON.stringify({ blocks: [], time: Date.now(), version: '2.28.2' });
                f.submit();
            });
        });
    }
});
</script>
@endpush
