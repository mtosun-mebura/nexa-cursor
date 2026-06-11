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
        if (document.querySelector('script[type="importmap"]')) return;
        const script = document.createElement('script');
        script.type = 'importmap';
        script.textContent = JSON.stringify(PROSEMIRROR_IMPORTMAP);
        document.head.appendChild(script);
    }

    function buildWysiwygIconDataUrl(iconKey, icons) {
        var def = icons[iconKey];
        if (!def || !def.svg) return '';
        var svgInner = String(def.svg).replace(/\sstroke="currentColor"/gi, '').replace(/\sstroke='currentColor'/gi, '');
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#374151">' + svgInner + '</svg>';
        return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
    }

    function getCsrfToken() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function parseWysiwygIconsFromWrapper(wrapper) {
        var raw = wrapper.getAttribute('data-wysiwyg-icons');
        if (!raw) return {};
        try {
            var parsed = JSON.parse(raw);
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function buildWysiwygIconHtml(iconKey, icons) {
        var def = icons[iconKey];
        if (!def || !def.svg) return '';
        var label = String(def.label || iconKey).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        return '<span class="wysiwyg-inline-icon" data-wysiwyg-icon="' + iconKey + '" contenteditable="false" role="img" aria-label="' + label + '">' +
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="wysiwyg-inline-icon__svg" aria-hidden="true">' +
            def.svg + '</svg></span>';
    }

    function paintWysiwygIconDom(dom, iconKey, icons) {
        var def = icons[iconKey];
        dom.className = 'wysiwyg-inline-icon';
        dom.setAttribute('data-wysiwyg-icon', iconKey || '');
        dom.setAttribute('role', 'img');
        dom.draggable = false;
        if (def && def.label) {
            dom.setAttribute('aria-label', def.label);
        } else {
            dom.removeAttribute('aria-label');
        }
        if (def && def.svg) {
            dom.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#374151" class="wysiwyg-inline-icon__svg" aria-hidden="true">' +
                def.svg + '</svg>';
        } else {
            dom.innerHTML = '';
        }
        return dom;
    }

    function clearCodeMarkBeforeIconInsert(chain, editor) {
        if (editor && editor.isActive('code') && chain && typeof chain.unsetMark === 'function') {
            return chain.unsetMark('code');
        }
        return chain;
    }

    function insertWysiwygIconIntoEditor(editor, iconKey, icons, savedSelection) {
        if (!iconKey || !icons[iconKey]) return false;
        if (!editor.schema.nodes.wysiwygIcon) return false;
        var chain = editor.chain().focus();
        if (savedSelection && typeof savedSelection.from === 'number') {
            chain = chain.setTextSelection({ from: savedSelection.from, to: savedSelection.to });
        } else if (!(editor.view && editor.view.hasFocus && editor.view.hasFocus())) {
            chain = chain.focus('end');
        }
        chain = clearCodeMarkBeforeIconInsert(chain, editor);
        var inserted = chain.insertContent({ type: 'wysiwygIcon', attrs: { icon: iconKey } }).run();
        if (inserted) {
            editor.chain().focus().insertContent('\u00a0').run();
            return true;
        }
        chain = editor.chain().focus();
        if (savedSelection && typeof savedSelection.from === 'number') {
            chain = chain.setTextSelection({ from: savedSelection.from, to: savedSelection.to });
        }
        chain = clearCodeMarkBeforeIconInsert(chain, editor);
        if (editor.commands.insertWysiwygIcon && chain.insertWysiwygIcon(iconKey).run()) {
            return true;
        }
        return false;
    }

    function repairWysiwygIconHtmlInTextNodes(editor, icons) {
        if (!editor || !editor.state || !editor.schema.nodes.wysiwygIcon) return;
        var re = /<span[^>]*\sdata-wysiwyg-icon="([^"]+)"[^>]*>[\s\S]*?<\/span>/gi;
        var tr = editor.state.tr;
        var replacements = [];
        editor.state.doc.descendants(function (node, pos) {
            if (!node.isText || !node.text || node.text.indexOf('wysiwyg-inline-icon') === -1) return;
            var text = node.text;
            var match;
            re.lastIndex = 0;
            while ((match = re.exec(text)) !== null) {
                var iconKey = match[1];
                if (!icons[iconKey]) continue;
                replacements.push({
                    from: pos + match.index,
                    to: pos + match.index + match[0].length,
                    iconKey: iconKey
                });
            }
        });
        if (!replacements.length) return;
        replacements.sort(function (a, b) { return b.from - a.from; });
        replacements.forEach(function (item) {
            var iconNode = editor.schema.nodes.wysiwygIcon.create({ icon: item.iconKey });
            tr.replaceWith(item.from, item.to, iconNode);
        });
        if (tr.docChanged) editor.view.dispatch(tr);
    }

    function buildIconSvgNode(svgInner) {
        var pathNodes = [];
        try {
            var doc = new DOMParser().parseFromString('<svg xmlns="http://www.w3.org/2000/svg">' + svgInner + '</svg>', 'application/xml');
            if (doc.querySelector('parsererror')) {
                doc = new DOMParser().parseFromString('<svg xmlns="http://www.w3.org/2000/svg">' + svgInner + '</svg>', 'text/html');
            }
            var svgRoot = doc.querySelector('svg');
            if (svgRoot) {
                svgRoot.querySelectorAll('path').forEach(function (p) {
                    var attrs = {};
                    for (var i = 0; i < p.attributes.length; i++) {
                        var attr = p.attributes[i];
                        attrs[attr.name] = attr.value;
                    }
                    if (!attrs.stroke) attrs.stroke = 'currentColor';
                    if (!attrs['stroke-width']) attrs['stroke-width'] = '1.5';
                    if (!attrs['stroke-linecap']) attrs['stroke-linecap'] = 'round';
                    if (!attrs['stroke-linejoin']) attrs['stroke-linejoin'] = 'round';
                    if (attrs.d) pathNodes.push(['path', attrs]);
                });
            }
        } catch (e) {}
        if (!pathNodes.length) return null;
        return ['svg', {
            fill: 'none',
            viewBox: '0 0 24 24',
            stroke: 'currentColor',
            'stroke-width': '1.5',
            class: 'wysiwyg-inline-icon__svg',
            'aria-hidden': 'true'
        }].concat(pathNodes);
    }

    function createWysiwygIconExtension(icons, Node) {
        return Node.create({
            name: 'wysiwygIcon',
            group: 'inline',
            inline: true,
            atom: true,
            selectable: true,
            draggable: true,
            addAttributes: function () {
                return {
                    icon: {
                        default: null,
                        parseHTML: function (el) {
                            return el.getAttribute('data-wysiwyg-icon') || null;
                        },
                        renderHTML: function (attrs) {
                            if (!attrs.icon) return {};
                            return { 'data-wysiwyg-icon': attrs.icon };
                        }
                    }
                };
            },
            parseHTML: function () {
                return [{
                    tag: 'span[data-wysiwyg-icon]',
                    priority: 1000,
                    getAttrs: function (el) {
                        var icon = el.getAttribute('data-wysiwyg-icon');
                        return icon ? { icon: icon } : false;
                    }
                }];
            },
            renderHTML: function (_a) {
                var node = _a.node;
                var iconKey = node.attrs.icon;
                var def = icons[iconKey];
                var svgSpec = def && def.svg ? buildIconSvgNode(def.svg) : null;
                var children = svgSpec ? [svgSpec] : [];
                return ['span', {
                    class: 'wysiwyg-inline-icon',
                    'data-wysiwyg-icon': iconKey,
                    role: 'img',
                    'aria-label': def && def.label ? def.label : (iconKey || 'Icoon')
                }].concat(children);
            },
            addNodeView: function () {
                var iconMap = icons;
                return function (props) {
                    var currentNode = props.node;
                    var dom = document.createElement('span');
                    dom.className = 'wysiwyg-inline-icon';
                    dom.contentEditable = 'false';
                    paintWysiwygIconDom(dom, currentNode.attrs.icon, iconMap);
                    return {
                        dom: dom,
                        ignoreMutation: function () { return true; },
                        update: function (updatedNode) {
                            if (updatedNode.type.name !== 'wysiwygIcon') return false;
                            currentNode = updatedNode;
                            paintWysiwygIconDom(dom, currentNode.attrs.icon, iconMap);
                            return true;
                        }
                    };
                };
            },
            addCommands: function () {
                return {
                    insertWysiwygIcon: function (iconKey) {
                        return function (_a) {
                            var chain = _a.chain;
                            if (!iconKey || !icons[iconKey]) return false;
                            return chain().focus().insertContent({ type: 'wysiwygIcon', attrs: { icon: iconKey } }).run();
                        };
                    }
                };
            }
        });
    }

    async function uploadFile(url, fileKey, file, editor) {
        const formData = new FormData();
        formData.append(fileKey, file);
        const token = getCsrfToken();
        if (token) formData.append('_token', token);
        if (typeof window.__websitePageModuleName !== 'undefined' && window.__websitePageModuleName) formData.append('module', window.__websitePageModuleName);
        const res = await fetch(url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' });
        if (!res.ok) throw new Error('Upload mislukt');
        return res.json();
    }

    async function initEditor(wrapper) {
        if (wrapper._flowbiteEditor) return wrapper._flowbiteEditor;
        if (wrapper.getAttribute('data-flowbite-initializing') === '1') return null;
        wrapper.setAttribute('data-flowbite-initializing', '1');
        try {
        const contentEl = wrapper.querySelector('[data-editor-content]');
        const textarea = wrapper.querySelector('[data-editor-input]');
        if (!contentEl || !textarea) return null;

        applyImportMap();

        const wysiwygIcons = parseWysiwygIconsFromWrapper(wrapper);

        const [
            { Editor, Mark, Node },
            { default: StarterKit },
            { default: Link },
            { default: Image },
            UnderlineMod,
            HighlightMod,
            TextAlignMod
        ] = await Promise.all([
            import('https://esm.sh/@tiptap/core@2.6.6'),
            import('https://esm.sh/@tiptap/starter-kit@2.6.6'),
            import('https://esm.sh/@tiptap/extension-link@2.6.6'),
            import('https://esm.sh/@tiptap/extension-image@2.6.6'),
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

        function parseColorFromNode(node) {
            if (!node || node.nodeType !== 1) return null;
            var color = null;
            if (node.getAttribute) {
                var style = node.getAttribute('style');
                if (style && typeof style === 'string' && style.indexOf('color') !== -1) {
                    var m = style.match(/color\s*:\s*([^;]+)/);
                    if (m) color = m[1].trim();
                }
            }
            if (!color && node.style && node.style.color) color = node.style.color;
            return color || null;
        }
        function colorToHex(cssColor) {
            if (!cssColor || typeof cssColor !== 'string') return null;
            cssColor = cssColor.trim();
            if (/^#[0-9A-Fa-f]{3,8}$/.test(cssColor)) {
                if (cssColor.length === 4) return '#' + cssColor[1] + cssColor[1] + cssColor[2] + cssColor[2] + cssColor[3] + cssColor[3];
                if (cssColor.length === 7) return cssColor;
                return null;
            }
            var rgb = cssColor.match(/^rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/);
            if (rgb) {
                var r = ('0' + parseInt(rgb[1], 10).toString(16)).slice(-2);
                var g = ('0' + parseInt(rgb[2], 10).toString(16)).slice(-2);
                var b = ('0' + parseInt(rgb[3], 10).toString(16)).slice(-2);
                return '#' + r + g + b;
            }
            var rgba = cssColor.match(/^rgba\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,/);
            if (rgba) {
                var r = ('0' + parseInt(rgba[1], 10).toString(16)).slice(-2);
                var g = ('0' + parseInt(rgba[2], 10).toString(16)).slice(-2);
                var b = ('0' + parseInt(rgba[3], 10).toString(16)).slice(-2);
                return '#' + r + g + b;
            }
            try {
                var el = document.createElement('span');
                el.style.color = cssColor;
                document.body.appendChild(el);
                var computed = window.getComputedStyle(el).color;
                document.body.removeChild(el);
                var rgb = computed && computed.match(/rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/);
                if (rgb) {
                    var r = ('0' + parseInt(rgb[1], 10).toString(16)).slice(-2);
                    var g = ('0' + parseInt(rgb[2], 10).toString(16)).slice(-2);
                    var b = ('0' + parseInt(rgb[3], 10).toString(16)).slice(-2);
                    return '#' + r + g + b;
                }
            } catch (e) {}
            return null;
        }
        var TextColor = Mark.create({
            name: 'textColor',
            addAttributes() {
                return {
                    color: {
                        default: null,
                        parseHTML: function (el) { return parseColorFromNode(el) || null; },
                        renderHTML: function (attrs) { return attrs.color ? { style: 'color: ' + attrs.color } : {}; }
                    }
                };
            },
            parseHTML() {
                return [{
                    tag: 'span',
                    getAttrs: function (node) {
                        var c = parseColorFromNode(node);
                        return c ? { color: c } : false;
                    },
                    priority: 100
                }];
            },
            renderHTML: function (_a) {
                var mark = _a.mark;
                if (!mark.attrs.color) return ['span', 0];
                return ['span', { style: 'color: ' + mark.attrs.color }, 0];
            },
            addCommands() {
                var self = this;
                return {
                    setColor: function (color) { return function (_a) { var chain = _a.chain; return color ? chain().focus().setMark(self.name, { color: color }).run() : chain().focus().unsetMark(self.name).run(); }; },
                    unsetColor: function () { return function (_a) { var chain = _a.chain; return chain().focus().unsetMark(self.name).run(); }; }
                };
            }
        });

        const extensions = [
            StarterKit.configure({
                heading: { levels: [1, 2, 3, 4] }
            }),
            FontSize,
            FontFamily,
            TextColor,
            createWysiwygIconExtension(wysiwygIcons, Node),
            Link.configure({ openOnClick: false, HTMLAttributes: { target: '_blank', rel: 'noopener' } }),
            Image.configure({ inline: true, allowBase64: true })
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
                attributes: { class: 'format format-sm dark:format-invert focus:outline-none format-blue max-w-none min-h-[280px]' },
                handleDOMEvents: {
                    keydown: function (_view, event) {
                        if ((event.metaKey || event.ctrlKey) && (event.key === 's' || event.key === 'S' || event.keyCode === 83 || event.which === 83)) {
                            event.preventDefault();
                            if (typeof window.syncAllFlowbiteWysiwygEditors === 'function') {
                                window.syncAllFlowbiteWysiwygEditors();
                            }
                            if (typeof window.__submitWebsitePageFormFromShortcut === 'function') {
                                window.__submitWebsitePageFormFromShortcut();
                            }
                            return true;
                        }
                        return false;
                    }
                }
            },
            onUpdate: ({ editor: e }) => { textarea.value = e.getHTML(); }
        });

        wrapper._flowbiteEditor = editor;
        wrapper._wysiwygIcons = wysiwygIcons;

        function q(id) { return document.getElementById(prefix + id) || wrapper.querySelector('[id="' + prefix + id + '"]'); }
        function on(id, fn) { const el = q(id); if (el) el.addEventListener('click', (e) => { e.preventDefault(); fn(); }); }

        function normalizeLinkUrl(raw) {
            var url = (raw || '').trim();
            if (!url) return '';
            if (/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url)) return url;
            return 'https://' + url;
        }

        (function setupLinkDialog() {
            var dialog = wrapper.querySelector('[data-wysiwyg-link-dialog]');
            if (!dialog) {
                dialog = document.createElement('div');
                dialog.className = 'flowbite-wysiwyg-link-dialog hidden absolute inset-0 z-[100] flex items-start justify-center pt-12';
                dialog.setAttribute('data-wysiwyg-link-dialog', '');
                dialog.setAttribute('role', 'dialog');
                dialog.setAttribute('aria-modal', 'true');
                dialog.setAttribute('aria-label', 'Link invoegen');
                dialog.innerHTML =
                    '<div class="flowbite-wysiwyg-link-dialog__backdrop absolute inset-0 rounded-xl bg-gray-900/45" data-wysiwyg-link-backdrop></div>' +
                    '<div class="flowbite-wysiwyg-link-dialog__panel relative z-[1] w-full max-w-sm mx-4 p-4 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg">' +
                    '<p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Link invoegen</p>' +
                    '<label class="block text-xs text-gray-500 dark:text-gray-400 mb-1" for="' + prefix + '-link-url">URL</label>' +
                    '<input type="url" id="' + prefix + '-link-url" class="block w-full mb-3 px-2.5 py-2 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100" data-wysiwyg-link-url placeholder="https://">' +
                    '<label class="block text-xs text-gray-500 dark:text-gray-400 mb-1" for="' + prefix + '-link-label">Tekst (optioneel)</label>' +
                    '<input type="text" id="' + prefix + '-link-label" class="block w-full mb-3 px-2.5 py-2 text-sm rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100" data-wysiwyg-link-label placeholder="Linktekst">' +
                    '<div class="flex gap-2 justify-end mt-1">' +
                    '<button type="button" class="px-3 py-1.5 text-xs rounded bg-blue-600 text-white hover:bg-blue-700" data-wysiwyg-link-save>Toevoegen</button>' +
                    '<button type="button" class="px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600" data-wysiwyg-link-cancel>Annuleren</button>' +
                    '</div></div>';
                wrapper.appendChild(dialog);
            }
            var urlInput = dialog.querySelector('[data-wysiwyg-link-url]');
            var labelInput = dialog.querySelector('[data-wysiwyg-link-label]');
            var saveBtn = dialog.querySelector('[data-wysiwyg-link-save]');
            var cancelBtn = dialog.querySelector('[data-wysiwyg-link-cancel]');
            var backdrop = dialog.querySelector('[data-wysiwyg-link-backdrop]');
            var savedLinkSelection = null;

            function closeLinkDialog() {
                dialog.classList.add('hidden');
                savedLinkSelection = null;
            }

            function openLinkDialog() {
                savedLinkSelection = savedLinkSelection || editor.state.selection;
                var existingHref = '';
                if (editor.isActive('link')) {
                    var attrs = editor.getAttributes('link');
                    existingHref = (attrs && attrs.href) ? attrs.href : '';
                }
                urlInput.value = existingHref || 'https://';
                labelInput.value = '';
                if (!editor.state.selection.empty) {
                    labelInput.value = editor.state.doc.textBetween(
                        editor.state.selection.from,
                        editor.state.selection.to,
                        ' '
                    );
                }
                dialog.classList.remove('hidden');
                setTimeout(function () {
                    urlInput.focus();
                    urlInput.select();
                }, 0);
            }

            function applyLink() {
                var url = normalizeLinkUrl(urlInput.value);
                if (!url) {
                    closeLinkDialog();
                    return;
                }
                editor.chain().focus();
                if (savedLinkSelection) {
                    editor.commands.setTextSelection(savedLinkSelection);
                }
                var linkAttrs = { href: url, target: '_blank', rel: 'noopener noreferrer' };
                if (!editor.state.selection.empty) {
                    editor.chain().focus().extendMarkRange('link').setLink(linkAttrs).run();
                } else {
                    var label = (labelInput.value || '').trim();
                    var text = label || url;
                    editor.chain().focus().insertContent({
                        type: 'text',
                        text: text,
                        marks: [{ type: 'link', attrs: linkAttrs }]
                    }).run();
                }
                closeLinkDialog();
            }

            var linkBtn = q('-toggleLink');
            if (linkBtn) {
                linkBtn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    savedLinkSelection = editor.state.selection;
                });
                linkBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    openLinkDialog();
                });
            }
            if (saveBtn) saveBtn.addEventListener('click', function (e) { e.preventDefault(); applyLink(); });
            if (cancelBtn) cancelBtn.addEventListener('click', function (e) { e.preventDefault(); closeLinkDialog(); });
            if (backdrop) backdrop.addEventListener('click', closeLinkDialog);
            dialog.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeLinkDialog();
                } else if (e.key === 'Enter' && (e.target === urlInput || e.target === labelInput)) {
                    e.preventDefault();
                    applyLink();
                }
            });
        })();

        on('-toggleBold', () => editor.chain().focus().toggleBold().run());
        on('-toggleItalic', () => editor.chain().focus().toggleItalic().run());
        on('-toggleUnderline', () => { if (editor.chain().focus().toggleUnderline) editor.chain().focus().toggleUnderline().run(); });
        on('-toggleStrike', () => editor.chain().focus().toggleStrike().run());
        on('-toggleHighlight', () => { if (editor.chain().focus().toggleHighlight) editor.chain().focus().toggleHighlight().run(); });
        on('-toggleCode', () => editor.chain().focus().toggleCode().run());
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
        on('-clearFormat', () => {
            editor.chain().focus().selectAll().unsetAllMarks().run();
            try { editor.chain().focus().clearNodes().run(); } catch (e) {}
        });
        on('-setH1', () => editor.chain().focus().toggleHeading({ level: 1 }).run());
        on('-setH2', () => editor.chain().focus().toggleHeading({ level: 2 }).run());
        on('-setH3', () => editor.chain().focus().toggleHeading({ level: 3 }).run());
        on('-setH4', () => editor.chain().focus().toggleHeading({ level: 4 }).run());

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

        var textColorInput = q('-textColor');
        if (textColorInput) textColorInput.addEventListener('input', function () {
            var v = textColorInput.value;
            if (v) editor.chain().focus().setColor(v).run();
        });
        on('-unsetTextColor', () => editor.chain().focus().unsetColor().run());

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

        wrapper.querySelectorAll('.flowbite-wysiwyg-icon-pick-btn').forEach(function (btn) {
            var savedIconInsertSelection = null;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                savedIconInsertSelection = editor.state.selection;
            });
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var iconKey = btn.getAttribute('data-icon-key');
                if (!iconKey) return;
                insertWysiwygIconIntoEditor(editor, iconKey, wysiwygIcons, savedIconInsertSelection);
                savedIconInsertSelection = null;
                if (textarea && editor) textarea.value = editor.getHTML();
            });
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
        repairWysiwygIconHtmlInTextNodes(editor, wysiwygIcons);
        textarea.value = editor.getHTML();

        function setBtnActive(el, active) {
            if (!el) return;
            el.classList.toggle('is-active', active);
        }
        wrapper.querySelectorAll('.flowbite-wysiwyg-toolbar button[type="button"]').forEach(function (btn) {
            if (!btn.classList.contains('flowbite-wysiwyg-toolbar-btn')) {
                btn.classList.add('flowbite-wysiwyg-toolbar-btn');
            }
        });
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
            setBtnActive(q('-setH4'), editor.isActive('heading', { level: 4 }));
            var fs = fontSizeSelect; if (fs) { var attrs = editor.getAttributes('fontSize'); fs.value = (attrs && attrs.fontSize) ? attrs.fontSize : ''; }
            var ff = fontFamilySelect; if (ff) { var attrs = editor.getAttributes('fontFamily'); ff.value = (attrs && attrs.fontFamily) ? attrs.fontFamily : ''; }
            var tc = textColorInput; if (tc) { var attrs = editor.getAttributes('textColor'); var c = (attrs && attrs.color) ? attrs.color : ''; var hex = colorToHex(c); tc.value = hex || '#000000'; }
        }
        function scheduleToolbarUpdate() { requestAnimationFrame(updateToolbarState); }
        editor.on('selectionUpdate', scheduleToolbarUpdate);
        editor.on('transaction', scheduleToolbarUpdate);
        editor.on('focus', scheduleToolbarUpdate);
        contentEl.addEventListener('focus', scheduleToolbarUpdate);
        updateToolbarState();

        return editor;
        } finally {
            wrapper.removeAttribute('data-flowbite-initializing');
        }
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
