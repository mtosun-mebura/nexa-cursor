/**
 * Flowbite-style WYSIWYG (Tiptap) – volledige toolbar, hoogte 300px, bladeren voor afbeelding/document.
 * syncAllFlowbiteWysiwygEditors() vóór form submit; initFlowbiteWysiwyg(container) voor dynamische editors.
 */
(function () {
    const PROSEMIRROR_IMPORTMAP = {
        "imports": {
            "https://esm.sh/v135/prosemirror-model@1.22.3/es2022/prosemirror-model.mjs": "https://esm.sh/v135/prosemirror-model@1.19.3/es2022/prosemirror-model.mjs",
            "https://esm.sh/v135/prosemirror-model@1.22.1/es2022/prosemirror-model.mjs": "https://esm.sh/v135/prosemirror-model@1.19.3/es2022/prosemirror-model.mjs"
        }
    };

    function applyImportMap() {
        if (document.querySelector('script[type="importmap"][data-flowbite-wysiwyg]')) return;
        const existing = document.querySelector('script[type="importmap"]');
        if (existing && existing.textContent && existing.textContent.indexOf('prosemirror-model') !== -1) return;
        const script = document.createElement('script');
        script.type = 'importmap';
        script.setAttribute('data-flowbite-wysiwyg', '1');
        script.textContent = JSON.stringify(PROSEMIRROR_IMPORTMAP);
        document.head.appendChild(script);
    }

    function getCsrfToken() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    async function uploadFile(url, fileKey, file, editor) {
        const formData = new FormData();
        formData.append(fileKey, file);
        const token = getCsrfToken();
        if (token) formData.append('_token', token);
        const res = await fetch(url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('Upload mislukt');
        return res.json();
    }

    async function initEditor(wrapper) {
        if (wrapper._flowbiteEditor) return wrapper._flowbiteEditor;
        const contentEl = wrapper.querySelector('[data-editor-content]');
        const textarea = wrapper.querySelector('[data-editor-input]');
        if (!contentEl || !textarea) return null;

        applyImportMap();

        const [
            { Editor, Mark },
            { default: StarterKit },
            { default: Link },
            { default: Image },
            { default: HardBreak },
            UnderlineMod,
            HighlightMod,
            TextAlignMod
        ] = await Promise.all([
            import('https://esm.sh/@tiptap/core@2.6.6'),
            import('https://esm.sh/@tiptap/starter-kit@2.6.6'),
            import('https://esm.sh/@tiptap/extension-link@2.6.6'),
            import('https://esm.sh/@tiptap/extension-image@2.6.6'),
            import('https://esm.sh/@tiptap/extension-hard-break@2.6.6'),
            import('https://esm.sh/@tiptap/extension-underline@2.6.6').catch(() => ({ default: null })),
            import('https://esm.sh/@tiptap/extension-highlight@2.6.6').catch(() => ({ default: null })),
            import('https://esm.sh/@tiptap/extension-text-align@2.6.6').catch(() => ({ default: null }))
        ]);

        const Underline = UnderlineMod && UnderlineMod.default;
        const Highlight = HighlightMod && HighlightMod.default;
        const TextAlign = TextAlignMod && TextAlignMod.default;

        var FontSize = Mark.create({
            name: 'fontSize',
            addAttributes() {
                return {
                    fontSize: {
                        default: null,
                        parseHTML: function (el) { return (el.style && el.style.fontSize) || null; },
                        renderHTML: function (attrs) { return attrs.fontSize ? { style: 'font-size: ' + attrs.fontSize } : {}; }
                    }
                };
            },
            parseHTML() {
                return [{ tag: 'span', getAttrs: function (node) { return (node.style && node.style.fontSize) ? { fontSize: node.style.fontSize } : {}; } }];
            },
            renderHTML: function (_a) {
                var mark = _a.mark;
                if (!mark.attrs.fontSize) return ['span', 0];
                return ['span', { style: 'font-size: ' + mark.attrs.fontSize }, 0];
            },
            addCommands() {
                var self = this;
                return {
                    setFontSize: function (fontSize) { return function (_a) { var chain = _a.chain; return fontSize ? chain().focus().setMark(self.name, { fontSize: fontSize }).run() : chain().focus().unsetMark(self.name).run(); }; },
                    unsetFontSize: function () { return function (_a) { var chain = _a.chain; return chain().focus().unsetMark(self.name).run(); }; }
                };
            }
        });

        var FontFamily = Mark.create({
            name: 'fontFamily',
            addAttributes() {
                return {
                    fontFamily: {
                        default: null,
                        parseHTML: function (el) { return (el.style && el.style.fontFamily) || null; },
                        renderHTML: function (attrs) { return attrs.fontFamily ? { style: 'font-family: ' + attrs.fontFamily } : {}; }
                    }
                };
            },
            parseHTML() {
                return [{ tag: 'span', getAttrs: function (node) { return (node.style && node.style.fontFamily) ? { fontFamily: node.style.fontFamily } : {}; } }];
            },
            renderHTML: function (_a) {
                var mark = _a.mark;
                if (!mark.attrs.fontFamily) return ['span', 0];
                return ['span', { style: 'font-family: ' + mark.attrs.fontFamily }, 0];
            },
            addCommands() {
                var self = this;
                return {
                    setFontFamily: function (fontFamily) { return function (_a) { var chain = _a.chain; return fontFamily ? chain().focus().setMark(self.name, { fontFamily: fontFamily }).run() : chain().focus().unsetMark(self.name).run(); }; },
                    unsetFontFamily: function () { return function (_a) { var chain = _a.chain; return chain().focus().unsetMark(self.name).run(); }; }
                };
            }
        });

        const extensions = [
            StarterKit,
            HardBreak,
            FontSize,
            FontFamily,
            Link.configure({ openOnClick: false, HTMLAttributes: { target: '_blank', rel: 'noopener' } }),
            Image
        ];
        if (Underline) extensions.push(Underline);
        if (Highlight) extensions.push(Highlight.configure({ multicolor: false }));
        if (TextAlign) extensions.push(TextAlign.configure({ types: ['heading', 'paragraph'] }));

        const prefix = wrapper.getAttribute('data-editor-id') || '';
        const initialContent = (textarea.value || '').trim();
        const uploadImageUrl = wrapper.getAttribute('data-upload-image-url') || '';
        const uploadDocumentUrl = wrapper.getAttribute('data-upload-document-url') || '';

        const editor = new Editor({
            element: contentEl,
            extensions: extensions,
            content: initialContent || undefined,
            editorProps: {
                attributes: { class: 'format lg:format-lg dark:format-invert focus:outline-none format-blue max-w-none min-h-[280px]' }
            },
            onUpdate: ({ editor: e }) => { textarea.value = e.getHTML(); }
        });

        wrapper._flowbiteEditor = editor;

        function q(id) { return document.getElementById(prefix + id) || wrapper.querySelector('[id="' + prefix + id + '"]'); }
        function on(id, fn) { const el = q(id); if (el) el.addEventListener('click', (e) => { e.preventDefault(); fn(); }); }

        on('-toggleBold', () => editor.chain().focus().toggleBold().run());
        on('-toggleItalic', () => editor.chain().focus().toggleItalic().run());
        on('-toggleUnderline', () => { if (editor.chain().focus().toggleUnderline) editor.chain().focus().toggleUnderline().run(); });
        on('-toggleStrike', () => editor.chain().focus().toggleStrike().run());
        on('-toggleHighlight', () => { if (editor.chain().focus().toggleHighlight) editor.chain().focus().toggleHighlight().run(); });
        on('-toggleCode', () => editor.chain().focus().toggleCode().run());
        on('-toggleLink', () => {
            const url = window.prompt('URL:', 'https://');
            if (url) editor.chain().focus().setLink({ href: url }).run();
        });
        on('-removeLink', () => editor.chain().focus().unsetLink().run());
        on('-alignLeft', () => { if (editor.chain().focus().setTextAlign) editor.chain().focus().setTextAlign('left').run(); });
        on('-alignCenter', () => { if (editor.chain().focus().setTextAlign) editor.chain().focus().setTextAlign('center').run(); });
        on('-alignRight', () => { if (editor.chain().focus().setTextAlign) editor.chain().focus().setTextAlign('right').run(); });
        on('-toggleList', () => editor.chain().focus().toggleBulletList().run());
        on('-toggleOrderedList', () => editor.chain().focus().toggleOrderedList().run());
        on('-toggleBlockquote', () => editor.chain().focus().toggleBlockquote().run());
        on('-toggleHR', () => { if (editor.chain().focus().setHorizontalRule) editor.chain().focus().setHorizontalRule().run(); });
        on('-undo', () => editor.chain().focus().undo().run());
        on('-redo', () => editor.chain().focus().redo().run());
        on('-setParagraph', () => editor.chain().focus().setParagraph().run());
        on('-setH1', () => editor.chain().focus().toggleHeading({ level: 1 }).run());
        on('-setH2', () => editor.chain().focus().toggleHeading({ level: 2 }).run());
        on('-setH3', () => editor.chain().focus().toggleHeading({ level: 3 }).run());

        var fontSizeSelect = q('-fontSize');
        if (fontSizeSelect) fontSizeSelect.addEventListener('change', function () {
            var v = fontSizeSelect.value;
            editor.chain().focus()[v ? 'setFontSize' : 'unsetFontSize'](v || undefined).run();
        });
        var fontFamilySelect = q('-fontFamily');
        if (fontFamilySelect) fontFamilySelect.addEventListener('change', function () {
            var v = fontFamilySelect.value;
            editor.chain().focus()[v ? 'setFontFamily' : 'unsetFontFamily'](v || undefined).run();
        });

        on('-addImage', () => {
            const input = wrapper.querySelector('.flowbite-wysiwyg-image-input');
            if (!input || !uploadImageUrl) return;
            input.value = '';
            input.accept = 'image/*';
            input.onchange = function () {
                const file = input.files && input.files[0];
                if (!file) return;
                uploadFile(uploadImageUrl, 'image', file).then(function (data) {
                    var url = (data && (data.url || data.logo_url)) || '';
                    if (url) editor.chain().focus().setImage({ src: url }).run();
                }).catch(function () { alert('Upload afbeelding mislukt.'); });
                input.onchange = null;
            };
            input.click();
        });

        on('-addDocument', () => {
            const input = wrapper.querySelector('.flowbite-wysiwyg-document-input');
            if (!input || !uploadDocumentUrl) return;
            input.value = '';
            input.onchange = function () {
                const file = input.files && input.files[0];
                if (!file) return;
                uploadFile(uploadDocumentUrl, 'document', file).then(function (data) {
                    var url = data.url || data;
                    if (typeof url !== 'string') return;
                    var fullUrl = url.indexOf('http') === 0 ? url : (window.location.origin + (url.charAt(0) === '/' ? '' : '/') + url);
                    var label = (data.filename || file.name || 'Document').replace(/</g, '');
                    editor.chain().focus().insertContent('<a href="' + fullUrl + '" target="_blank" rel="noopener">' + label + '</a>').run();
                }).catch(function () { alert('Upload document mislukt.'); });
                input.onchange = null;
            };
            input.click();
        });

        editor.on('update', () => { textarea.value = editor.getHTML(); });
        textarea.value = editor.getHTML();

        var activeBtnClasses = ['bg-gray-200', 'dark:bg-gray-600'];
        function setBtnActive(el, active) {
            if (!el) return;
            activeBtnClasses.forEach(function (c) { el.classList.toggle(c, active); });
        }
        function updateToolbarState() {
            setBtnActive(q('-toggleBold'), editor.isActive('bold'));
            setBtnActive(q('-toggleItalic'), editor.isActive('italic'));
            setBtnActive(q('-toggleUnderline'), editor.isActive('underline'));
            setBtnActive(q('-toggleStrike'), editor.isActive('strike'));
            setBtnActive(q('-toggleHighlight'), editor.isActive('highlight'));
            setBtnActive(q('-toggleCode'), editor.isActive('code'));
            setBtnActive(q('-toggleLink'), editor.isActive('link'));
            setBtnActive(q('-alignLeft'), editor.isActive('paragraph', { textAlign: 'left' }) || editor.isActive('heading', { textAlign: 'left' }));
            setBtnActive(q('-alignCenter'), editor.isActive('paragraph', { textAlign: 'center' }) || editor.isActive('heading', { textAlign: 'center' }));
            setBtnActive(q('-alignRight'), editor.isActive('paragraph', { textAlign: 'right' }) || editor.isActive('heading', { textAlign: 'right' }));
            setBtnActive(q('-toggleList'), editor.isActive('bulletList'));
            setBtnActive(q('-toggleOrderedList'), editor.isActive('orderedList'));
            setBtnActive(q('-toggleBlockquote'), editor.isActive('blockquote'));
            setBtnActive(q('-setParagraph'), editor.isActive('paragraph'));
            setBtnActive(q('-setH1'), editor.isActive('heading', { level: 1 }));
            setBtnActive(q('-setH2'), editor.isActive('heading', { level: 2 }));
            setBtnActive(q('-setH3'), editor.isActive('heading', { level: 3 }));
            var fs = fontSizeSelect; if (fs) { var attrs = editor.getAttributes('fontSize'); fs.value = (attrs && attrs.fontSize) ? attrs.fontSize : ''; }
            var ff = fontFamilySelect; if (ff) { var attrs = editor.getAttributes('fontFamily'); ff.value = (attrs && attrs.fontFamily) ? attrs.fontFamily : ''; }
        }
        function scheduleToolbarUpdate() { requestAnimationFrame(updateToolbarState); }
        editor.on('selectionUpdate', scheduleToolbarUpdate);
        editor.on('transaction', scheduleToolbarUpdate);
        editor.on('focus', scheduleToolbarUpdate);
        contentEl.addEventListener('focus', scheduleToolbarUpdate);
        updateToolbarState();

        return editor;
    }

    function initAll() {
        document.querySelectorAll('[data-flowbite-wysiwyg]').forEach(function (w) {
            if (!w._flowbiteEditor) initEditor(w);
        });
    }

    window.syncAllFlowbiteWysiwygEditors = function () {
        document.querySelectorAll('[data-flowbite-wysiwyg]').forEach(function (w) {
            var ta = w.querySelector('[data-editor-input]');
            if (w._flowbiteEditor && ta) ta.value = w._flowbiteEditor.getHTML();
        });
    };

    window.initFlowbiteWysiwyg = function (container) {
        var scope = container && container.nodeType === 1 ? container : document;
        scope.querySelectorAll('[data-flowbite-wysiwyg]').forEach(function (w) {
            if (!w._flowbiteEditor) initEditor(w);
        });
    };

    window.destroyFlowbiteWysiwygIn = function (container) {
        (container || document).querySelectorAll('[data-flowbite-wysiwyg]').forEach(function (w) {
            if (w._flowbiteEditor) {
                try { w._flowbiteEditor.destroy(); } catch (e) {}
                w._flowbiteEditor = null;
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
