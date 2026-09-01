{{-- TinyMCE HTML editor for #html_content. Toolbar volgt admin-thema; inhoud is altijd light (e-mail). --}}
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
    /* E-mailcanvas blijft light, ook als de admin dark is. */
    .tox .tox-edit-area__iframe,
    .dark .tox .tox-edit-area__iframe,
    #html-content-card .tox .tox-edit-area__iframe,
    .dark #html-content-card .tox .tox-edit-area__iframe {
        background: #f4f4f4 !important;
    }
    #html-content-card .tox-tinymce,
    #html-content-card .tox-tinymce-aux {
        max-width: 100%;
        min-width: 0;
    }
    #html-content-card .tox-tinymce {
        width: 100% !important;
    }
    @media (max-width: 1023px) {
        #html-content-card .tox .tox-toolbar-overlord .tox-toolbar {
            flex-wrap: wrap;
        }
        #html-content-card .tox .tox-edit-area {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }
    /* Tweede toolbar-regel (link, tabel, code) visueel gescheiden */
    .tox .tox-toolbar-overlord > .tox-toolbar:not(:first-child) {
        border-top: 1px solid rgba(128, 128, 128, 0.25);
        padding-top: 4px;
        margin-top: 2px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById('html_content');
    if (!textarea) return;

    var editorContentResponsive =
        'img { max-width: 100% !important; height: auto !important; }' +
        'table { max-width: 100% !important; width: 100% !important; table-layout: fixed; }' +
        'td, th { word-wrap: break-word; overflow-wrap: anywhere; }';
    var contentStyleEmail =
        'html { background: #f4f4f4; color-scheme: light; }' +
        'body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.6; text-align: left; padding: 0.75rem; background: #f4f4f4 !important; color: #111827 !important; max-width: 100%; overflow-x: auto; box-sizing: border-box; color-scheme: light; }' +
        'p, li, td, th, div, span, strong, em, ol, ul { color: #111827 !important; }' +
        'p { margin: 0 0 0.75em 0; }' +
        'h1 { font-size: 1.875em; font-weight: 700; margin: 0 0 0.5em 0; color: inherit; }' +
        'h2 { font-size: 1.5em; font-weight: 600; margin: 0 0 0.5em 0; color: #111827 !important; }' +
        'h3 { font-size: 1.25em; font-weight: 600; margin: 0 0 0.5em 0; color: #111827 !important; }' +
        'ul, ol { margin: 0 0 0.75em 0; padding-left: 1.5em; }' +
        'table { margin: 0; border-collapse: collapse; }' +
        'a { color: #2563eb; }' +
        'a[style*="background-color"], a[style*="background:"] { color: #ffffff !important; }' +
        'a[style*="background-color"] *, a[style*="background:"] * { color: #ffffff !important; }' +
        'td[bgcolor="#0f172a"], td[style*="background-color: #0f172a"], td[style*="background-color:#0f172a"],' +
        'td[bgcolor="#111827"], td[style*="background-color: #111827"], td[style*="background-color:#111827"],' +
        'td[bgcolor="#1e293b"], td[style*="background-color: #1e293b"], td[style*="background-color:#1e293b"] { color: #ffffff !important; }' +
        'td[bgcolor="#0f172a"] *, td[style*="background-color: #0f172a"] *, td[style*="background-color:#0f172a"] *,' +
        'td[bgcolor="#111827"] *, td[style*="background-color: #111827"] *, td[style*="background-color:#111827"] *,' +
        'td[bgcolor="#1e293b"] *, td[style*="background-color: #1e293b"] *, td[style*="background-color:#1e293b"] * { color: #ffffff !important; }' +
        editorContentResponsive;

    function isDarkMode() {
        return document.documentElement.classList.contains('dark');
    }

    function getEmailTemplateTinymceConfig() {
        var isDark = isDarkMode();
        var isCompact = window.matchMedia('(max-width: 1023px)').matches;
        return {
            selector: '#html_content',
            base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.2',
            suffix: '.min',
            width: '100%',
            height: isCompact ? 420 : 840,
            menubar: false,
            toolbar_mode: 'wrap',
            plugins: 'lists link code charmap table',
            toolbar: [
                'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
                'link | charmap | table | tablecellbackcolor | code',
            ],
            block_formats: 'Paragraaf=p; Kop 1=h1; Kop 2=h2; Kop 3=h3',
            content_style: contentStyleEmail,
            skin: isDark ? 'oxide-dark' : 'oxide',
            content_css: false,
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

    // Bij wissel dark/light (toolbar-skin) editor opnieuw opbouwen; e-mailinhoud blijft light
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
