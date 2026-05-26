{{-- TinyMCE HTML-editor voor website-pagina inhoud (#content) --}}
@push('styles')
<style>
    .tox-tinymce {
        border-radius: var(--radius, 0.375rem) !important;
        border-color: var(--color-input, #e5e7eb) !important;
    }
    .dark .tox-tinymce {
        border-color: var(--color-input) !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById('content');
    if (!textarea) return;

    tinymce.init({
        selector: '#content',
        base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.2',
        suffix: '.min',
        height: 400,
        menubar: false,
        plugins: 'lists link code charmap table',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | charmap | table | code',
        block_formats: 'Paragraaf=p; Kop 1=h1; Kop 2=h2; Kop 3=h3',
        content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; padding: 1rem; color: #333; }' +
            'p { margin: 0 0 0.75em 0; }' +
            'h1 { font-size: 1.875em; font-weight: 700; margin: 0 0 0.5em 0; }' +
            'h2 { font-size: 1.5em; font-weight: 600; margin: 0 0 0.5em 0; }' +
            'h3 { font-size: 1.25em; font-weight: 600; margin: 0 0 0.5em 0; }' +
            'ul, ol { margin: 0 0 0.75em 0; padding-left: 1.5em; }' +
            'table { border-collapse: collapse; }' +
            'a { color: #2563eb; }',
        branding: false,
        promotion: false,
        resize: true,
        valid_elements: '*[*]',
        extended_valid_elements: '*[*]',
        setup: function(editor) {
            editor.on('change keyup', function() {
                editor.save();
            });
        }
    });
});
</script>
@endpush
