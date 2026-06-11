(function() {
    function bindLogoDropzoneClick(area, input, linkEl) {
        if (!area || !input) {
            return;
        }

        if (typeof window.bindAdminDropzoneClick === 'function') {
            window.bindAdminDropzoneClick(area, input, linkEl || null, { clearInputFirst: false });
            return;
        }

        var openPicker = function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            if (typeof window.openAdminFilePicker === 'function') {
                window.openAdminFilePicker(input, { clearInputFirst: false });
            } else {
                input.click();
            }
        };

        if (linkEl) {
            linkEl.addEventListener('click', openPicker);
        }

        area.addEventListener('click', function(e) {
            if (e.target.closest('input[type="file"], button, label, .image-remove-btn')) {
                return;
            }
            if (linkEl && (e.target === linkEl || linkEl.contains(e.target))) {
                return;
            }
            if (e.target.closest('a') && (!linkEl || !linkEl.contains(e.target))) {
                return;
            }
            openPicker(e);
        });

        area.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                openPicker(e);
            }
        });
    }

    function initAdminLogoDropzones() {
        document.querySelectorAll('[data-logo-dropzone-init]').forEach(function(wrap) {
            if (wrap.getAttribute('data-logo-dropzone-initialized') === '1') {
                return;
            }
            wrap.setAttribute('data-logo-dropzone-initialized', '1');

            var inputId = wrap.getAttribute('data-input-id');
            var previewId = wrap.getAttribute('data-preview-id');
            var areaId = wrap.getAttribute('data-area-id');
            var linkId = wrap.getAttribute('data-link-id');
            var removeId = wrap.getAttribute('data-remove-id');
            var clientMsgId = wrap.getAttribute('data-client-msg-id');
            var input = inputId ? document.getElementById(inputId) : null;
            var preview = previewId ? document.getElementById(previewId) : null;
            var area = areaId ? document.getElementById(areaId) : null;
            var linkEl = linkId ? document.getElementById(linkId) : null;
            var removeBtn = removeId ? document.getElementById(removeId) : null;
            var existingUrl = wrap.getAttribute('data-existing-url') || '';
            var logoClientMsg = clientMsgId ? document.getElementById(clientMsgId) : null;
            var maxFileBytes = parseInt(wrap.getAttribute('data-max-file-bytes') || String(2 * 1024 * 1024), 10) || (2 * 1024 * 1024);
            var liveLightId = wrap.getAttribute('data-live-preview-light-id') || '';
            var liveDarkId = wrap.getAttribute('data-live-preview-dark-id') || '';
            var modeInputId = wrap.getAttribute('data-logo-mode-input-id') || '';
            var dropKey = wrap.getAttribute('data-image-dropzone') || '1';

            function getLogoMode() {
                var modeEl = modeInputId ? document.getElementById(modeInputId) : null;
                return modeEl && modeEl.value === 'light_dark' ? 'light_dark' : 'single';
            }

            function syncLivePreviewStrip(url) {
                if (!liveLightId && !liveDarkId) return;
                var mode = getLogoMode();
                if (dropKey === 'light') {
                    if (liveLightId) {
                        var ll = document.getElementById(liveLightId);
                        if (ll) ll.src = url;
                    }
                    if (mode === 'single' && liveDarkId) {
                        var ld = document.getElementById(liveDarkId);
                        if (ld) ld.src = url;
                    }
                } else if (dropKey === 'dark' && liveDarkId) {
                    var d = document.getElementById(liveDarkId);
                    if (d) d.src = url;
                }
            }

            function clearLivePreviewStrip() {
                if (!liveLightId && !liveDarkId) return;
                if (dropKey === 'light') {
                    if (liveLightId) {
                        var ll = document.getElementById(liveLightId);
                        if (ll) ll.removeAttribute('src');
                    }
                    if (getLogoMode() === 'single' && liveDarkId) {
                        var ld = document.getElementById(liveDarkId);
                        if (ld) ld.removeAttribute('src');
                    }
                } else if (dropKey === 'dark' && liveDarkId) {
                    var d = document.getElementById(liveDarkId);
                    if (d) d.removeAttribute('src');
                }
            }

            function showLogoClient(message, isError) {
                if (!logoClientMsg) return;
                logoClientMsg.textContent = message || '';
                logoClientMsg.classList.toggle('hidden', !message);
                logoClientMsg.classList.toggle('text-destructive', !!isError);
            }

            function showRemove(show) {
                if (removeBtn) removeBtn.classList.toggle('hidden', !show);
            }

            function setPreviewFromFile(file) {
                if (!preview) return;
                var reader = new FileReader();
                reader.onload = function(e) {
                    var dataUrl = e.target.result;
                    preview.src = dataUrl;
                    preview.classList.remove('hidden');
                    var frame = preview.closest('[data-logo-preview-frame]');
                    if (frame) frame.classList.remove('hidden');
                    showRemove(true);
                    showLogoClient('', false);
                    syncLivePreviewStrip(dataUrl);
                    if (typeof window.syncAdminLogoVisibility === 'function') {
                        window.syncAdminLogoVisibility();
                    }
                };
                reader.readAsDataURL(file);
            }

            function handleFile(file) {
                var allowed = ['image/svg+xml', 'image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
                if (!allowed.includes(file.type)) {
                    showLogoClient('Alleen SVG, PNG, JPG en GIF zijn toegestaan.', true);
                    if (input) input.value = '';
                    return;
                }
                if (file.size > maxFileBytes) {
                    showLogoClient('Het logo mag maximaal ' + Math.round(maxFileBytes / (1024 * 1024)) + 'MB groot zijn.', true);
                    if (input) input.value = '';
                    return;
                }
                var dt = new DataTransfer();
                dt.items.add(file);
                if (input) input.files = dt.files;
                setPreviewFromFile(file);
            }

            bindLogoDropzoneClick(area, input, linkEl);

            if (area && input) {
                area.addEventListener('dragover', function(e) { e.preventDefault(); e.stopPropagation(); area.classList.add('border-primary'); });
                area.addEventListener('dragleave', function(e) { e.preventDefault(); area.classList.remove('border-primary'); });
                area.addEventListener('drop', function(e) {
                    e.preventDefault();
                    area.classList.remove('border-primary');
                    if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
                });
            }
            if (input) {
                input.addEventListener('change', function() {
                    if (this.files && this.files.length) handleFile(this.files[0]);
                });
            }
            if (removeBtn && input && preview) {
                removeBtn.addEventListener('click', function() {
                    input.value = '';
                    if (existingUrl) {
                        preview.src = existingUrl;
                        preview.classList.remove('hidden');
                        showRemove(true);
                        syncLivePreviewStrip(existingUrl);
                    } else {
                        preview.src = '';
                        preview.classList.add('hidden');
                        var frame = preview.closest('[data-logo-preview-frame]');
                        if (frame) frame.classList.add('hidden');
                        showRemove(false);
                        clearLivePreviewStrip();
                    }
                    if (typeof window.syncAdminLogoVisibility === 'function') {
                        window.syncAdminLogoVisibility();
                    }
                });
            }
            if (existingUrl && removeBtn) showRemove(true);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdminLogoDropzones);
    } else {
        initAdminLogoDropzones();
    }
})();
