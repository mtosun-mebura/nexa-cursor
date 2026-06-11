@push('scripts')
<script src="{{ asset('js/flowbite-wysiwyg-init.js') }}?v=20260516d"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form.knowledge-document-form');
    if (!form) {
        return;
    }

    function syncKnowledgeEditorContent() {
        if (typeof window.syncAllFlowbiteWysiwygEditors === 'function') {
            window.syncAllFlowbiteWysiwygEditors();
        }
    }

    form.addEventListener('submit', function () {
        syncKnowledgeEditorContent();
    }, true);

    var formatBtn = document.getElementById('knowledge-content-format-btn');
    var shortenCheckbox = document.getElementById('knowledge-content-shorten');
    var editorId = 'knowledge-content-editor';
    var textareaId = 'knowledge-content';
    var fullSourceHtml = null;
    var fullSourceLocked = false;
    var lastShortHtml = null;
    var lastShortSourceKey = null;
    var lastFullHtml = null;
    var lastFullSourceKey = null;

    function plainLength(html) {
        return String(html || '').replace(/<[^>]+>/g, '').trim().length;
    }

    function sourceKey(html) {
        return String(html || '').trim();
    }

    function getEditorWrapper() {
        return document.querySelector('[data-flowbite-wysiwyg][data-editor-id="' + editorId + '"]');
    }

    function getEditorContent() {
        var wrapper = getEditorWrapper();
        if (wrapper && wrapper._flowbiteEditor) {
            return wrapper._flowbiteEditor.getHTML();
        }
        var textarea = document.getElementById(textareaId);
        return textarea ? textarea.value : '';
    }

    function setEditorContent(html) {
        var wrapper = getEditorWrapper();
        var textarea = document.getElementById(textareaId);
        var safeHtml = html && String(html).trim() !== '' ? html : '<p></p>';

        if (wrapper && wrapper._flowbiteEditor) {
            wrapper._flowbiteEditor.commands.setContent(safeHtml, true);
            if (textarea) {
                textarea.value = wrapper._flowbiteEditor.getHTML();
            }
            return;
        }
        if (textarea) {
            textarea.value = safeHtml;
        }
    }

    function initFullSource() {
        if (fullSourceLocked) {
            return;
        }
        var textarea = document.getElementById(textareaId);
        var fromTextarea = textarea && textarea.value ? textarea.value : '';
        if (plainLength(fromTextarea) >= 20) {
            fullSourceHtml = fromTextarea;
            return;
        }
        var fromEditor = getEditorContent();
        if (plainLength(fromEditor) >= 20) {
            fullSourceHtml = fromEditor;
        }
    }

    initFullSource();
    window.setTimeout(function () {
        if (!fullSourceLocked) {
            initFullSource();
        }
    }, 600);

    if (!formatBtn) {
        return;
    }

    formatBtn.addEventListener('click', function () {
        var shorten = shortenCheckbox ? shortenCheckbox.checked : false;
        var sourceContent;
        var titleInput = document.getElementById('title');
        var categorySelect = document.getElementById('category');
        var defaultLabel = formatBtn.getAttribute('data-default-label') || 'Tekst opmaken';

        if (shorten) {
            var editorContent = getEditorContent();
            if (!fullSourceLocked && plainLength(editorContent) >= 20) {
                if (!fullSourceHtml || plainLength(editorContent) >= plainLength(fullSourceHtml)) {
                    fullSourceHtml = editorContent;
                }
            } else if (!fullSourceHtml || plainLength(fullSourceHtml) < 20) {
                fullSourceHtml = editorContent;
            } else if (plainLength(editorContent) > plainLength(fullSourceHtml) + 40) {
                fullSourceHtml = editorContent;
                lastShortHtml = null;
                lastShortSourceKey = null;
                lastFullHtml = null;
                lastFullSourceKey = null;
            }
            if (plainLength(fullSourceHtml) < 20) {
                window.alert('Vul eerst minimaal 20 tekens inhoud in om op te maken.');
                return;
            }
            sourceContent = fullSourceHtml;

            if (lastShortHtml && lastShortSourceKey === sourceKey(fullSourceHtml)) {
                setEditorContent(lastShortHtml);
                return;
            }
        } else {
            var editorContent = getEditorContent();
            var editorIsLongerRevision = plainLength(editorContent) > plainLength(fullSourceHtml || '') + 40;

            if (fullSourceHtml && plainLength(fullSourceHtml) >= 20 && !editorIsLongerRevision) {
                sourceContent = fullSourceHtml;
            } else {
                sourceContent = editorContent;
                if (plainLength(sourceContent) < 20) {
                    window.alert('Vul eerst minimaal 20 tekens inhoud in om op te maken.');
                    return;
                }
                fullSourceHtml = sourceContent;
                fullSourceLocked = false;
                lastFullHtml = null;
                lastFullSourceKey = null;
            }

            lastShortHtml = null;
            lastShortSourceKey = null;

            if (lastFullHtml && lastFullSourceKey === sourceKey(fullSourceHtml)) {
                setEditorContent(lastFullHtml);
                return;
            }
        }

        formatBtn.disabled = true;
        formatBtn.innerHTML = '<span class="inline-flex items-center gap-1.5"><i class="ki-filled ki-loading animate-spin" aria-hidden="true"></i> Bezig…</span>';

        fetch(formatBtn.getAttribute('data-format-url'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                content: sourceContent,
                shorten: shorten,
                title: titleInput ? titleInput.value : '',
                category: categorySelect ? categorySelect.value : '',
            }),
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || payload.success === false) {
                        throw new Error(payload.message || 'Tekst opmaken mislukt.');
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                var formattedHtml = payload.html || '';
                setEditorContent(formattedHtml);
                if (shorten) {
                    fullSourceLocked = true;
                    lastShortHtml = formattedHtml;
                    lastShortSourceKey = sourceKey(fullSourceHtml);
                } else {
                    lastFullHtml = formattedHtml;
                    lastFullSourceKey = sourceKey(fullSourceHtml);
                }
            })
            .catch(function (error) {
                window.alert(error && error.message ? error.message : 'Tekst opmaken mislukt.');
            })
            .finally(function () {
                formatBtn.disabled = false;
                formatBtn.innerHTML = '<i class="ki-filled ki-document me-1.5" aria-hidden="true"></i>' + defaultLabel;
            });
    });
});
</script>
@endpush
