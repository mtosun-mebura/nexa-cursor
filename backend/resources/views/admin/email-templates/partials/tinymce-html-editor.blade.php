{{-- TinyMCE HTML editor for #html_content - dark/light zoals bij componenten --}}
@push('styles')
<style>
    /* Match admin input border/radius for TinyMCE container */
    .tox-tinymce {
        border-radius: var(--radius, 0.375rem) !important;
        border-color: var(--color-input, #e5e7eb) !important;
    }
    .dark .tox-tinymce {
        border-color: var(--color-input) !important;
        background-color: #1f2937 !important;
    }
    .dark .tox .tox-edit-area__iframe {
        background: #1f2937 !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById('html_content');
    if (!textarea) return;

    var contentStyleLight = 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; text-align: left; padding: 1rem; color: #333; }' +
        'body * { text-align: left; }' +
        'p { margin: 0 0 0.75em 0; }' +
        'h1 { font-size: 1.875em; font-weight: 700; margin: 0 0 0.5em 0; }' +
        'h2 { font-size: 1.5em; font-weight: 600; margin: 0 0 0.5em 0; }' +
        'h3 { font-size: 1.25em; font-weight: 600; margin: 0 0 0.5em 0; }' +
        'ul, ol { margin: 0 0 0.75em 0; padding-left: 1.5em; }' +
        'table { margin: 0; border-collapse: collapse; }' +
        'a { color: #2563eb; }';
    var contentStyleDark = 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; text-align: left; padding: 1rem; background: #1f2937; color: #f3f4f6; }' +
        'body * { text-align: left; }' +
        'p { margin: 0 0 0.75em 0; }' +
        'h1 { font-size: 1.875em; font-weight: 700; margin: 0 0 0.5em 0; }' +
        'h2 { font-size: 1.5em; font-weight: 600; margin: 0 0 0.5em 0; }' +
        'h3 { font-size: 1.25em; font-weight: 600; margin: 0 0 0.5em 0; }' +
        'ul, ol { margin: 0 0 0.75em 0; padding-left: 1.5em; }' +
        'table { margin: 0; border-collapse: collapse; }' +
        'a { color: #93c5fd; }';

    function isDarkMode() {
        return document.documentElement.classList.contains('dark');
    }

    function getEmailTemplateTinymceConfig() {
        var isDark = isDarkMode();
        return {
            selector: '#html_content',
            base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.2',
            suffix: '.min',
            height: 840,
            menubar: false,
            plugins: 'lists link code charmap table',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | charmap | table | tablecellbackcolor | code',
            block_formats: 'Paragraaf=p; Kop 1=h1; Kop 2=h2; Kop 3=h3',
            content_style: isDark ? contentStyleDark : contentStyleLight,
            skin: isDark ? 'oxide-dark' : 'oxide',
            content_css: isDark ? 'dark' : 'default',
            branding: false,
            promotion: false,
            resize: true,
            valid_elements: '*[*]',
            extended_valid_elements: '*[*]',
            setup: function(editor) {
                editor.on('change keyup', function() {
                    editor.save();
                });
                editor.ui.registry.addIcon('tablecellbackcolor', '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><rect x="2" y="2" width="9" height="9" fill="currentColor" stroke="currentColor" stroke-width="1.2"/><rect x="13" y="2" width="9" height="9" fill="none" stroke="currentColor" stroke-width="1.2"/><rect x="2" y="13" width="9" height="9" fill="none" stroke="currentColor" stroke-width="1.2"/><rect x="13" y="13" width="9" height="9" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>');
                editor.ui.registry.addButton('tablecellbackcolor', {
                    icon: 'tablecellbackcolor',
                    tooltip: 'Achtergrondkleur tabelcel',
                    onAction: function() {
                        var node = editor.selection.getNode();
                        var cell = editor.dom.getParent(node, 'td,th');
                        if (!cell) {
                            editor.windowManager.alert('Zet de cursor in een tabelcel om de achtergrondkleur te wijzigen.');
                            return;
                        }
                        var currentColor = editor.dom.getStyle(cell, 'background-color') || '#ffffff';
                        editor.windowManager.open({
                            title: 'Achtergrondkleur tabelcel',
                            body: { type: 'panel', items: [{ type: 'colorinput', name: 'color', label: 'Kleur' }] },
                            initialData: { color: currentColor },
                            buttons: [
                                { type: 'submit', text: 'Toepassen', primary: true },
                                { type: 'cancel', text: 'Annuleren' }
                            ],
                            onSubmit: function(api) {
                                var data = api.getData();
                                if (data.color) {
                                    editor.dom.setStyle(cell, 'background-color', data.color);
                                    editor.nodeChanged();
                                }
                                api.close();
                            }
                        });
                    }
                });
            }
        };
    }

    function initEmailTemplateTinymce() {
        if (typeof tinymce === 'undefined') return;
        var config = getEmailTemplateTinymceConfig();
        tinymce.init(config);
    }

    initEmailTemplateTinymce();

    // Bij wissel dark/light (zoals bij componenten) editor opnieuw opbouwen
    var themeObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                var ed = tinymce.get('html_content');
                if (ed) {
                    var html = ed.getContent();
                    ed.remove();
                    setTimeout(function() {
                        initEmailTemplateTinymce();
                        var newEd = tinymce.get('html_content');
                        if (newEd && html) newEd.setContent(html);
                    }, 50);
                }
            }
        });
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
</script>
@endpush
