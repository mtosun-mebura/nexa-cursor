/**
 * Page builder: kader met + knop, sleepbare componenten, resize (breedte), responsive.
 * Slaat op als JSON: { blocks: [ { id, type, data, width } ], time, version }.
 */
(function() {
    'use strict';

    var WIDTHS = [
        { value: 'full', label: 'Volle breedte', class: 'col-span-12' },
        { value: 'half', label: 'Half', class: 'col-span-12 md:col-span-6' },
        { value: 'third', label: 'Derde', class: 'col-span-12 md:col-span-4' }
    ];

    function defaultData(type) {
        var d = {
            paragraph: { text: '' },
            header: { text: '', level: 2 },
            list: { style: 'unordered', items: [''] },
            table: { content: [['','',''],['','','']] },
            image: { url: '', caption: '' },
            imageAligned: { url: '', caption: '', alignment: 'center' },
            slider: { items: [{ url: '', caption: '' }] },
            quote: { text: '', caption: '' },
            code: { code: '' }
        };
        return d[type] ? JSON.parse(JSON.stringify(d[type])) : {};
    }

    function typeLabel(type) {
        var labels = { paragraph: 'Paragraaf', header: 'Kop', list: 'Lijst', table: 'Tabel', image: 'Afbeelding', imageAligned: 'Afbeelding (uitlijning)', slider: 'Slider', quote: 'Citaat', code: 'Code' };
        return labels[type] || type;
    }

    function formHtml(type, data) {
        var d = data || defaultData(type);
        var esc = function(s) { return (s == null ? '' : String(s)).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); };
        switch (type) {
            case 'paragraph':
                return '<label class="block text-xs font-medium mb-1">Tekst</label><textarea class="builder-field kt-input w-full min-h-[80px] text-sm" data-field="text" rows="3">' + esc(d.text) + '</textarea>';
            case 'header':
                return '<label class="block text-xs font-medium mb-1">Koptekst</label><input type="text" class="builder-field kt-input w-full text-sm" data-field="text" value="' + esc(d.text) + '" placeholder="Kop">' +
                    '<label class="block text-xs font-medium mt-2 mb-1">Niveau</label><select class="builder-field kt-input w-full text-sm" data-field="level"><option value="1"' + (d.level==1?' selected':'') + '>1</option><option value="2"' + (d.level==2?' selected':'') + '>2</option><option value="3"' + (d.level==3?' selected':'') + '>3</option></select>';
            case 'list':
                var items = (d.items && d.items.length) ? d.items : [''];
                var listHtml = '<label class="block text-xs font-medium mb-1">Lijst (één item per regel)</label><textarea class="builder-field kt-input w-full min-h-[60px] text-sm" data-field="items" rows="4" placeholder="Item 1\nItem 2">' + esc(items.join('\n')) + '</textarea>' +
                    '<select class="builder-field kt-input w-full mt-2 text-sm" data-field="style"><option value="unordered"' + (d.style==='unordered'?' selected':'') + '>Opsomming</option><option value="ordered"' + (d.style==='ordered'?' selected':'') + '>Genummerd</option></select>';
                return listHtml;
            case 'table':
                var rows = (d.content && d.content.length) ? d.content : [['','',''],['','','']];
                var tableHtml = '<div class="overflow-x-auto"><table class="builder-table w-full text-sm border border-input"><tbody>';
                rows.forEach(function(row, ri) {
                    tableHtml += '<tr>';
                    (row.length ? row : ['','','']).forEach(function(cell, ci) {
                        tableHtml += '<td class="p-1 border border-input"><input type="text" class="builder-field kt-input w-full text-xs" data-row="' + ri + '" data-col="' + ci + '" value="' + esc(cell) + '"></td>';
                    });
                    tableHtml += '</tr>';
                });
                tableHtml += '</tbody></table></div><button type="button" class="builder-table-add-row kt-btn kt-btn-sm kt-btn-ghost mt-1">+ Rij</button>';
                return tableHtml;
            case 'image':
                return '<label class="block text-xs font-medium mb-1">Plaatje kiezen</label>' +
                    '<div class="flex flex-wrap gap-2 items-center mb-2"><input type="file" class="builder-image-upload kt-input text-sm" accept="image/*" data-field="url">' +
                    '<span class="text-xs text-muted-foreground">of URL:</span><input type="url" class="builder-field kt-input flex-1 min-w-0 text-sm" data-field="url" value="' + esc(d.url) + '" placeholder="https://..."></div>' +
                    '<label class="block text-xs font-medium mt-2 mb-1">Onderschrift</label><input type="text" class="builder-field kt-input w-full text-sm" data-field="caption" value="' + esc(d.caption) + '">';
            case 'imageAligned':
                return '<label class="block text-xs font-medium mb-1">Plaatje kiezen</label>' +
                    '<div class="flex flex-wrap gap-2 items-center mb-2"><input type="file" class="builder-image-upload kt-input text-sm" accept="image/*" data-field="url">' +
                    '<span class="text-xs text-muted-foreground">of URL:</span><input type="url" class="builder-field kt-input flex-1 min-w-0 text-sm" data-field="url" value="' + esc(d.url) + '" placeholder="https://..."></div>' +
                    '<label class="block text-xs font-medium mt-2 mb-1">Uitlijning</label><select class="builder-field kt-input w-full text-sm" data-field="alignment">' +
                    '<option value="left"' + (d.alignment==='left'?' selected':'') + '>Links</option><option value="right"' + (d.alignment==='right'?' selected':'') + '>Rechts</option><option value="center"' + (d.alignment==='center'?' selected':'') + '>Midden</option><option value="full"' + (d.alignment==='full'?' selected':'') + '>Volle breedte</option></select>' +
                    '<label class="block text-xs font-medium mt-2 mb-1">Onderschrift</label><input type="text" class="builder-field kt-input w-full text-sm" data-field="caption" value="' + esc(d.caption) + '">';
            case 'slider':
                var items = (d.items && d.items.length) ? d.items : [{ url: '', caption: '' }];
                var sliderHtml = '<div class="builder-slider-items space-y-2">';
                items.forEach(function(it, idx) {
                    sliderHtml += '<div class="builder-slider-item flex flex-wrap gap-2 items-center p-2 border border-input rounded">' +
                        '<input type="file" class="builder-slider-upload kt-input text-sm" accept="image/*" data-slider-index="' + idx + '" data-slider-field="url">' +
                        '<input type="url" class="builder-field kt-input flex-1 min-w-[120px] text-sm" data-slider-index="' + idx + '" data-slider-field="url" value="' + esc(it.url) + '" placeholder="URL">' +
                        '<input type="text" class="builder-field kt-input flex-1 min-w-[80px] text-sm" data-slider-index="' + idx + '" data-slider-field="caption" value="' + esc(it.caption || '') + '" placeholder="Onderschrift">' +
                        '<button type="button" class="builder-slider-remove kt-btn kt-btn-sm kt-btn-ghost text-destructive">×</button></div>';
                });
                sliderHtml += '</div><button type="button" class="builder-slider-add kt-btn kt-btn-sm kt-btn-outline mt-2">+ Afbeelding</button>';
                return sliderHtml;
            case 'quote':
                return '<label class="block text-xs font-medium mb-1">Citaat</label><textarea class="builder-field kt-input w-full min-h-[60px] text-sm" data-field="text" rows="2">' + esc(d.text) + '</textarea>' +
                    '<label class="block text-xs font-medium mt-2 mb-1">Bron</label><input type="text" class="builder-field kt-input w-full text-sm" data-field="caption" value="' + esc(d.caption) + '">';
            case 'code':
                return '<label class="block text-xs font-medium mb-1">Code</label><textarea class="builder-field kt-input w-full min-h-[100px] font-mono text-sm" data-field="code" rows="5">' + esc(d.code) + '</textarea>';
            default:
                return '<p class="text-muted-foreground text-sm">Onbekend type: ' + esc(type) + '</p>';
        }
    }

    function readBlockDataFromCard(cardEl, type) {
        var data = {};
        var base = cardEl.querySelector('.builder-form-content');
        if (!base) return defaultData(type);
        switch (type) {
            case 'paragraph':
                var ta = base.querySelector('[data-field="text"]');
                data.text = ta ? ta.value : '';
                break;
            case 'header':
                data.text = (base.querySelector('[data-field="text"]') || {}).value || '';
                data.level = parseInt((base.querySelector('[data-field="level"]') || {}).value || '2', 10);
                break;
            case 'list':
                data.style = (base.querySelector('[data-field="style"]') || {}).value || 'unordered';
                var itemsVal = (base.querySelector('[data-field="items"]') || {}).value || '';
                data.items = itemsVal.split('\n').map(function(s) { return s.trim(); }).filter(Boolean);
                if (data.items.length === 0) data.items = [''];
                break;
            case 'table':
                var inputs = base.querySelectorAll('.builder-table input.builder-field');
                var rows = {};
                var maxCol = 0;
                inputs.forEach(function(inp) {
                    var r = parseInt(inp.getAttribute('data-row'), 10);
                    var c = parseInt(inp.getAttribute('data-col'), 10);
                    if (!rows[r]) rows[r] = {};
                    rows[r][c] = inp.value;
                    if (c > maxCol) maxCol = c;
                });
                var rowIndices = Object.keys(rows).map(Number).sort(function(a,b){ return a - b; });
                data.content = rowIndices.map(function(r) {
                    var rowObj = rows[r] || {};
                    var arr = [];
                    for (var c = 0; c <= maxCol; c++) arr.push(rowObj[c] !== undefined ? rowObj[c] : '');
                    return arr;
                });
                break;
            case 'image':
                data.url = (base.querySelector('input[type="url"][data-field="url"]') || base.querySelector('[data-field="url"]') || {}).value || '';
                data.caption = (base.querySelector('[data-field="caption"]') || {}).value || '';
                break;
            case 'imageAligned':
                data.url = (base.querySelector('input[type="url"][data-field="url"]') || base.querySelector('[data-field="url"]') || {}).value || '';
                data.caption = (base.querySelector('[data-field="caption"]') || {}).value || '';
                data.alignment = (base.querySelector('[data-field="alignment"]') || {}).value || 'center';
                break;
            case 'slider':
                data.items = [];
                base.querySelectorAll('.builder-slider-item').forEach(function(item) {
                    var urlInp = item.querySelector('[data-slider-field="url"]');
                    var capInp = item.querySelector('[data-slider-field="caption"]');
                    data.items.push({ url: urlInp ? urlInp.value : '', caption: capInp ? capInp.value : '' });
                });
                data.items = data.items.filter(function(i) { return i.url; });
                if (data.items.length === 0) data.items = [{ url: '', caption: '' }];
                break;
            case 'quote':
                data.text = (base.querySelector('[data-field="text"]') || {}).value || '';
                data.caption = (base.querySelector('[data-field="caption"]') || {}).value || '';
                break;
            case 'code':
                data.code = (base.querySelector('[data-field="code"]') || {}).value || '';
                break;
            default:
                data = defaultData(type);
        }
        return data;
    }

    function uuid() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function widthClass(width) {
        var w = WIDTHS.find(function(x) { return x.value === width; });
        return w ? w.class : WIDTHS[0].class;
    }

    var config = {};
    var sortable = null;

    function uploadImage(file, setStatus) {
        var uploadUrl = config.uploadUrl;
        var token = config.uploadCsrfToken;
        if (!uploadUrl || !file) return Promise.reject(new Error('Upload niet geconfigureerd'));
        setStatus = setStatus || function() {};
        setStatus('uploaden...');
        var formData = new FormData();
        formData.append('file', file);
        if (token) formData.append('_token', token);
        return fetch(uploadUrl, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) {
                if (!r.ok) throw new Error('Upload mislukt');
                return r.json();
            })
            .then(function(data) {
                setStatus('');
                return data.url || data;
            })
            .catch(function(err) {
                setStatus('Fout');
                throw err;
            });
    }

    function getBlocksFromDOM() {
        var container = document.getElementById(config.containerId);
        if (!container) return [];
        var cards = container.querySelectorAll('.builder-card');
        var blocks = [];
        cards.forEach(function(card) {
            var id = card.getAttribute('data-block-id');
            var type = card.getAttribute('data-block-type');
            var width = card.getAttribute('data-block-width') || 'full';
            var data = readBlockDataFromCard(card, type);
            blocks.push({ id: id, type: type, data: data, width: width });
        });
        return blocks;
    }

    function syncToInput() {
        var blocks = getBlocksFromDOM();
        var payload = { blocks: blocks, time: Date.now(), version: '2.0' };
        var input = document.getElementById(config.inputId);
        if (input) input.value = JSON.stringify(payload);
        var emptyEl = document.getElementById(config.emptyId);
        if (emptyEl) emptyEl.style.display = blocks.length ? 'none' : 'block';
    }

    function renderBlockCard(block) {
        var id = block.id || uuid();
        var type = block.type || 'paragraph';
        var data = block.data || defaultData(type);
        var width = block.width || 'full';
        var wClass = widthClass(width);
        var card = document.createElement('div');
        card.className = 'builder-card kt-card p-4 ' + wClass;
        card.setAttribute('data-block-id', id);
        card.setAttribute('data-block-type', type);
        card.setAttribute('data-block-width', width);
        card.innerHTML =
            '<div class="flex flex-wrap items-start justify-between gap-2 mb-3">' +
            '  <div class="flex items-center gap-2">' +
            '    <span class="builder-drag-handle text-muted-foreground cursor-grab p-1 rounded hover:bg-muted" title="Sleep om te verplaatsen" aria-label="Sleep om volgorde te wijzigen"><i class="ki-filled ki-menu"></i></span>' +
            '    <span class="text-sm font-medium">' + typeLabel(type) + '</span>' +
            '  </div>' +
            '  <div class="flex items-center gap-1">' +
            '    <span class="text-xs text-muted-foreground mr-1">Breedte:</span>' +
            WIDTHS.map(function(w) {
                return '<button type="button" class="builder-width-btn kt-btn kt-btn-sm ' + (w.value === width ? 'kt-btn-primary' : 'kt-btn-ghost') + '" data-width="' + w.value + '" title="' + w.label + '">' + (w.value === 'full' ? '100%' : w.value === 'half' ? '½' : '⅓') + '</button>';
            }).join('') +
            '    <button type="button" class="builder-remove kt-btn kt-btn-sm kt-btn-ghost text-destructive" title="Verwijderen">Verwijderen</button>' +
            '  </div>' +
            '</div>' +
            '<div class="builder-form-content">' + formHtml(type, data) + '</div>';
        bindCardEvents(card, type);
        return card;
    }

    function bindCardEvents(card, type) {
        var container = document.getElementById(config.containerId);
        card.querySelectorAll('.builder-width-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var w = btn.getAttribute('data-width');
                card.setAttribute('data-block-width', w);
                card.className = 'builder-card kt-card p-4 ' + widthClass(w);
                card.querySelectorAll('.builder-width-btn').forEach(function(b) { b.classList.toggle('kt-btn-primary', b.getAttribute('data-width') === w); b.classList.toggle('kt-btn-ghost', b.getAttribute('data-width') !== w); });
                syncToInput();
            });
        });
        card.querySelector('.builder-remove').addEventListener('click', function() {
            card.remove();
            syncToInput();
        });
        card.querySelectorAll('.builder-field').forEach(function(el) {
            el.addEventListener('input', function() { syncToInput(); });
            el.addEventListener('change', function() { syncToInput(); });
        });
        card.querySelectorAll('.builder-image-upload').forEach(function(fileInp) {
            fileInp.addEventListener('change', function() {
                var file = fileInp.files && fileInp.files[0];
                if (!file) return;
                var urlInp = card.querySelector('input[type="url"][data-field="url"]');
                if (!urlInp) return;
                uploadImage(file, function(msg) { if (urlInp.placeholder !== undefined) urlInp.placeholder = msg || 'https://...'; })
                    .then(function(url) { urlInp.value = url; syncToInput(); })
                    .catch(function() {});
                fileInp.value = '';
            });
        });
        card.querySelectorAll('.builder-slider-upload').forEach(function(fileInp) {
            fileInp.addEventListener('change', function() {
                var file = fileInp.files && fileInp.files[0];
                if (!file) return;
                var idx = fileInp.getAttribute('data-slider-index');
                var item = fileInp.closest('.builder-slider-item');
                var urlInp = item ? item.querySelector('input[data-slider-field="url"]') : null;
                if (!urlInp) return;
                uploadImage(file).then(function(url) { urlInp.value = url; syncToInput(); }).catch(function() {});
                fileInp.value = '';
            });
        });
        card.querySelectorAll('.builder-table-add-row').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tbody = card.querySelector('.builder-table tbody');
                var cols = (tbody && tbody.querySelector('tr')) ? tbody.querySelector('tr').querySelectorAll('td').length : 3;
                var tr = document.createElement('tr');
                for (var c = 0; c < cols; c++) {
                    var td = document.createElement('td');
                    td.className = 'p-1 border border-input';
                    td.innerHTML = '<input type="text" class="builder-field kt-input w-full text-xs" data-row="' + (tbody ? tbody.querySelectorAll('tr').length : 0) + '" data-col="' + c + '" value="">';
                    tr.appendChild(td);
                }
                tbody.appendChild(tr);
                syncToInput();
            });
        });
        card.querySelectorAll('.builder-slider-add').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var wrap = card.querySelector('.builder-slider-items');
                var idx = wrap ? wrap.querySelectorAll('.builder-slider-item').length : 0;
                var div = document.createElement('div');
                div.className = 'builder-slider-item flex flex-wrap gap-2 items-center p-2 border border-input rounded';
                div.innerHTML = '<input type="file" class="builder-slider-upload kt-input text-sm" accept="image/*" data-slider-index="' + idx + '">' +
                    '<input type="url" class="builder-field kt-input flex-1 min-w-[120px] text-sm" data-slider-index="' + idx + '" data-slider-field="url" value="" placeholder="URL">' +
                    '<input type="text" class="builder-field kt-input flex-1 min-w-[80px] text-sm" data-slider-index="' + idx + '" data-slider-field="caption" value="" placeholder="Onderschrift">' +
                    '<button type="button" class="builder-slider-remove kt-btn kt-btn-sm kt-btn-ghost text-destructive">×</button>';
                wrap.appendChild(div);
                div.querySelector('.builder-slider-remove').addEventListener('click', function() { div.remove(); syncToInput(); });
                div.querySelectorAll('.builder-field').forEach(function(el) { el.addEventListener('input', function() { syncToInput(); }); el.addEventListener('change', function() { syncToInput(); }); });
                var fu = div.querySelector('.builder-slider-upload');
                if (fu) fu.addEventListener('change', function() {
                    var file = fu.files && fu.files[0];
                    if (!file) return;
                    var urlInp = div.querySelector('input[data-slider-field="url"]');
                    if (urlInp) uploadImage(file).then(function(url) { urlInp.value = url; syncToInput(); }).catch(function() {});
                    fu.value = '';
                });
                syncToInput();
            });
        });
        card.querySelectorAll('.builder-slider-remove').forEach(function(btn) {
            btn.addEventListener('click', function() {
                btn.closest('.builder-slider-item').remove();
                syncToInput();
            });
        });
    }

    function addBlock(type) {
        var container = document.getElementById(config.containerId);
        var emptyEl = document.getElementById(config.emptyId);
        if (!container) return;
        var block = { id: uuid(), type: type, data: defaultData(type), width: 'full' };
        var card = renderBlockCard(block);
        container.appendChild(card);
        if (emptyEl) emptyEl.style.display = 'none';
        syncToInput();
    }

    function setBlocks(blocks) {
        var container = document.getElementById(config.containerId);
        var emptyEl = document.getElementById(config.emptyId);
        if (!container) return;
        container.innerHTML = '';
        (blocks || []).forEach(function(block) {
            var b = { id: block.id || uuid(), type: block.type || 'paragraph', data: block.data || defaultData(block.type), width: block.width || 'full' };
            var card = renderBlockCard(b);
            container.appendChild(card);
        });
        if (emptyEl) emptyEl.style.display = (blocks && blocks.length) ? 'none' : 'block';
        syncToInput();
    }

    function loadThemeBlocksForModule(moduleName) {
        var url = config.themeBlocksUrl;
        if (!url) return;
        var params = new URLSearchParams();
        if (moduleName) params.set('module_name', moduleName);
        var fullUrl = url + (params.toString() ? '?' + params.toString() : '');
        fetch(fullUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var blocks = (data && data.blocks) ? data.blocks : [];
                blocks = blocks.map(function(b) {
                    return { id: uuid(), type: b.type || 'paragraph', data: b.data || defaultData(b.type), width: b.width || 'full' };
                });
                setBlocks(blocks);
            })
            .catch(function() { setBlocks([]); });
    }

    function init(opts) {
        config = opts || {};
        var container = document.getElementById(config.containerId);
        var emptyEl = document.getElementById(config.emptyId);
        var addBtn = config.addBtnId ? document.getElementById(config.addBtnId) : null;
        var addMenu = config.addMenuId ? document.getElementById(config.addMenuId) : null;
        var initialJson = config.initialJson || '';
        var useThemeBlocksOnly = config.useThemeBlocksOnly === true;

        if (!container) return;

        var blocks = [];
        try {
            var parsed = initialJson && initialJson.trim() ? JSON.parse(initialJson) : null;
            if (parsed && parsed.blocks && Array.isArray(parsed.blocks)) {
                blocks = parsed.blocks.map(function(b) {
                    return { id: b.id || uuid(), type: b.type || 'paragraph', data: b.data || defaultData(b.type), width: b.width || 'full' };
                });
            }
        } catch (e) {}

        if (blocks.length) {
            blocks.forEach(function(block) {
                var card = renderBlockCard(block);
                container.appendChild(card);
            });
        }
        if (emptyEl) emptyEl.style.display = blocks.length ? 'none' : 'block';
        syncToInput();

        if (!useThemeBlocksOnly && addBtn && addMenu) {
            addBtn.addEventListener('click', function() {
                var open = addMenu.classList.toggle('hidden');
                addBtn.setAttribute('aria-expanded', open ? 'false' : 'true');
            });
            document.querySelectorAll(config.addMenuTypesSelector || '.builder-add-type').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var type = btn.getAttribute('data-type');
                    addBlock(type);
                    addMenu.classList.add('hidden');
                    addBtn.setAttribute('aria-expanded', 'false');
                });
            });
            document.addEventListener('click', function(e) {
                if (addMenu && !addMenu.classList.contains('hidden') && !addMenu.contains(e.target) && !addBtn.contains(e.target)) {
                    addMenu.classList.add('hidden');
                    addBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        sortable = Sortable.create(container, {
            animation: 150,
            handle: '.builder-drag-handle',
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                syncToInput();
            }
        });
    }

    window.PageBuilder = { init: init, addBlock: addBlock, setBlocks: setBlocks, loadThemeBlocksForModule: loadThemeBlocksForModule, getBlocksFromDOM: getBlocksFromDOM, syncToInput: syncToInput };
})();
