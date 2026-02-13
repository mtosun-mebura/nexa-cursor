/**
 * Editor.js custom tool: Afbeelding met uitlijning (links, rechts, midden, volle breedte).
 * Data: { url, caption, alignment } met alignment: 'left' | 'right' | 'center' | 'full'
 */
(function() {
    'use strict';
    var ALIGNMENTS = [
        { value: 'left', label: 'Links' },
        { value: 'right', label: 'Rechts' },
        { value: 'center', label: 'Midden' },
        { value: 'full', label: 'Volle breedte' }
    ];

    window.EditorJsImageAligned = function(data, config, api, readOnly) {
        this.data = (data && data.data) ? data.data : (data || { url: '', caption: '', alignment: 'center' });
        this.wrapper = null;
        this.readOnly = readOnly;
    };

    EditorJsImageAligned.prototype.render = function() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'editorjs-image-aligned';

        var url = this.data.url || '';
        var caption = this.data.caption || '';
        var alignment = this.data.alignment || 'center';

        if (this.readOnly) {
            var wrap = document.createElement('div');
            wrap.className = 'ce-image-aligned-preview ce-image-aligned--' + alignment;
            if (url) {
                var img = document.createElement('img');
                img.src = url;
                img.alt = caption || 'Afbeelding';
                img.style.maxWidth = alignment === 'full' ? '100%' : (alignment === 'left' || alignment === 'right' ? '280px' : '100%');
                img.style.height = 'auto';
                wrap.appendChild(img);
            }
            if (caption) {
                var cap = document.createElement('p');
                cap.className = 'ce-image-caption';
                cap.textContent = caption;
                wrap.appendChild(cap);
            }
            this.wrapper.appendChild(wrap);
            return this.wrapper;
        }

        var form = document.createElement('div');
        form.className = 'ce-image-aligned-form space-y-3';

        var urlLabel = document.createElement('label');
        urlLabel.className = 'block text-sm font-medium';
        urlLabel.textContent = 'Afbeelding URL';
        var urlInput = document.createElement('input');
        urlInput.type = 'url';
        urlInput.placeholder = 'https://...';
        urlInput.value = url;
        urlInput.className = 'kt-input w-full';
        urlInput.setAttribute('data-url', '1');
        form.appendChild(urlLabel);
        form.appendChild(urlInput);

        var alignLabel = document.createElement('label');
        alignLabel.className = 'block text-sm font-medium';
        alignLabel.textContent = 'Uitlijning';
        var alignSelect = document.createElement('select');
        alignSelect.className = 'kt-input w-full max-w-xs';
        alignSelect.setAttribute('data-alignment', '1');
        ALIGNMENTS.forEach(function(opt) {
            var o = document.createElement('option');
            o.value = opt.value;
            o.textContent = opt.label;
            if (opt.value === alignment) o.selected = true;
            alignSelect.appendChild(o);
        });
        form.appendChild(alignLabel);
        form.appendChild(alignSelect);

        var capLabel = document.createElement('label');
        capLabel.className = 'block text-sm font-medium';
        capLabel.textContent = 'Onderschrift (optioneel)';
        var capInput = document.createElement('input');
        capInput.type = 'text';
        capInput.placeholder = 'Onderschrift';
        capInput.value = caption;
        capInput.className = 'kt-input w-full';
        capInput.setAttribute('data-caption', '1');
        form.appendChild(capLabel);
        form.appendChild(capInput);

        var prev = document.createElement('div');
        prev.className = 'mt-2';
        var prevImg = document.createElement('img');
        prevImg.src = url;
        prevImg.alt = caption || 'Preview';
        prevImg.style.maxWidth = '200px';
        prevImg.style.height = 'auto';
        prevImg.style.borderRadius = '6px';
        prevImg.style.display = url ? '' : 'none';
        prev.appendChild(prevImg);
        form.appendChild(prev);

        urlInput.addEventListener('input', function() {
            var v = urlInput.value.trim();
            prevImg.src = v || '';
            prevImg.style.display = v ? '' : 'none';
        });

        this.wrapper.appendChild(form);
        return this.wrapper;
    };

    EditorJsImageAligned.prototype.save = function() {
        if (!this.wrapper) return this.data;
        var urlEl = this.wrapper.querySelector('[data-url]');
        var capEl = this.wrapper.querySelector('[data-caption]');
        var alignEl = this.wrapper.querySelector('[data-alignment]');
        return {
            url: urlEl ? urlEl.value.trim() : (this.data.url || ''),
            caption: capEl ? capEl.value.trim() : (this.data.caption || ''),
            alignment: alignEl ? alignEl.value : (this.data.alignment || 'center')
        };
    };

    EditorJsImageAligned.prototype.validate = function(savedData) {
        return !!savedData.url;
    };

    if (typeof window.EditorJS !== 'undefined' && window.EditorJS.Toolbox) {
        window.EditorJsImageAligned.toolbox = {
            icon: '<svg width="17" height="15" viewBox="0 0 17 15" xmlns="http://www.w3.org/2000/svg"><path d="M10.5 0L14 4.5L10.5 9L7 4.5L10.5 0Z"/><path d="M6.5 6L10 10.5L6.5 15L3 10.5L6.5 6Z"/></svg>',
            title: 'Afbeelding (uitlijning)'
        };
    }
})();
