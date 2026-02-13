/**
 * Editor.js custom tool: Slider (meerdere afbeeldingen, full-width carousel).
 * Data: { items: [ { url, caption }, ... ] }
 */
(function() {
    'use strict';

    window.EditorJsSlider = function(data, config, api, readOnly) {
        this.data = (data && data.data) ? data.data : (data || { items: [] });
        this.wrapper = null;
        this.readOnly = readOnly;
    };

    EditorJsSlider.prototype.render = function() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'editorjs-slider';

        var items = this.data.items || [];

        if (this.readOnly) {
            var wrap = document.createElement('div');
            wrap.className = 'ce-slider-preview';
            wrap.innerHTML = '<em>Slider met ' + items.length + ' afbeelding(en)</em>';
            this.wrapper.appendChild(wrap);
            return this.wrapper;
        }

        var container = document.createElement('div');
        container.className = 'ce-slider-form space-y-3';
        container.setAttribute('data-items', '1');

        var addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'kt-btn kt-btn-sm kt-btn-outline';
        addBtn.textContent = '+ Afbeelding toevoegen';
        var self = this;
        addBtn.addEventListener('click', function() {
            addItemRow(container, { url: '', caption: '' });
        });

        container.appendChild(addBtn);

        items.forEach(function(item) {
            addItemRow(container, item);
        });

        if (items.length === 0) {
            addItemRow(container, { url: '', caption: '' });
        }

        this.wrapper.appendChild(container);
        return this.wrapper;
    };

    function addItemRow(container, item) {
        var row = document.createElement('div');
        row.className = 'flex flex-wrap gap-2 items-start p-3 border border-input rounded-lg';
        var urlInput = document.createElement('input');
        urlInput.type = 'url';
        urlInput.placeholder = 'Afbeelding URL';
        urlInput.value = item.url || '';
        urlInput.className = 'kt-input flex-1 min-w-[200px]';
        urlInput.setAttribute('data-slider-url', '1');
        var capInput = document.createElement('input');
        capInput.type = 'text';
        capInput.placeholder = 'Onderschrift';
        capInput.value = item.caption || '';
        capInput.className = 'kt-input flex-1 min-w-[120px]';
        capInput.setAttribute('data-slider-caption', '1');
        var delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'kt-btn kt-btn-sm kt-btn-ghost text-destructive';
        delBtn.textContent = 'Verwijderen';
        delBtn.addEventListener('click', function() {
            row.remove();
        });
        row.appendChild(urlInput);
        row.appendChild(capInput);
        row.appendChild(delBtn);
        container.appendChild(row);
    }

    EditorJsSlider.prototype.save = function() {
        if (!this.wrapper) return this.data;
        var rows = this.wrapper.querySelectorAll('[data-slider-url]');
        var items = [];
        rows.forEach(function(row) {
            var urlEl = row.closest('.flex').querySelector('[data-slider-url]');
            var capEl = row.closest('.flex').querySelector('[data-slider-caption]');
            var url = urlEl ? urlEl.value.trim() : '';
            if (url) items.push({ url: url, caption: (capEl ? capEl.value.trim() : '') });
        });
        return { items: items };
    };

    EditorJsSlider.prototype.validate = function(savedData) {
        return savedData.items && savedData.items.length > 0;
    };

    if (typeof window.EditorJS !== 'undefined') {
        window.EditorJsSlider.toolbox = {
            icon: '<svg width="17" height="15" viewBox="0 0 17 15" xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0" width="17" height="15" rx="2" fill="currentColor" opacity="0.3"/><rect x="2" y="2" width="13" height="11" rx="1" fill="currentColor"/></svg>',
            title: 'Slider'
        };
    }
})();
