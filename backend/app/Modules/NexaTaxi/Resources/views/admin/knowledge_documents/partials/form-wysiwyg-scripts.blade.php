<script src="{{ asset('js/flowbite-wysiwyg-init.js') }}?v=20260516c"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form.knowledge-document-form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function () {
        if (typeof window.syncAllFlowbiteWysiwygEditors === 'function') {
            window.syncAllFlowbiteWysiwygEditors();
        }
    });
});
</script>
