@php
$flowbiteWysiwygTemplate = view('admin.website-pages.partials.flowbite-wysiwyg', [
    'editorId' => '__FLOWBITE_EDITOR_ID__',
    'name' => '__FLOWBITE_NAME__',
    'value' => '',
    'textareaId' => '__FLOWBITE_TEXTAREA_ID__',
    'placeholder' => '__FLOWBITE_PLACEHOLDER__',
    'contentMinHeightPx' => '__FLOWBITE_MIN_H__',
    'contentMaxHeightPx' => '__FLOWBITE_MAX_H__',
])->render();
@endphp
<script type="text/template" id="flowbite-wysiwyg-tpl">{!! $flowbiteWysiwygTemplate !!}</script>
<script>
(function() {
    function getFlowbiteWysiwygHtml(editorId, name, textareaId, placeholder, heightOpts) {
        heightOpts = heightOpts || {};
        var minH = heightOpts.minH != null ? String(heightOpts.minH) : '300';
        var maxH = heightOpts.maxH != null ? String(heightOpts.maxH) : minH;
        var tpl = document.getElementById('flowbite-wysiwyg-tpl');
        if (!tpl || !tpl.textContent) return '';
        return tpl.textContent
            .replace(/__FLOWBITE_EDITOR_ID__/g, editorId)
            .replace(/__FLOWBITE_NAME__/g, name)
            .replace(/__FLOWBITE_TEXTAREA_ID__/g, textareaId)
            .replace(/__FLOWBITE_PLACEHOLDER__/g, placeholder || '')
            .replace(/__FLOWBITE_MIN_H__/g, minH)
            .replace(/__FLOWBITE_MAX_H__/g, maxH);
    }
    window.getFlowbiteWysiwygHtml = getFlowbiteWysiwygHtml;
})();
</script>
<script type="importmap">
{"imports":{"https://esm.sh/v135/prosemirror-model@1.22.3/es2022/prosemirror-model.mjs":"https://esm.sh/v135/prosemirror-model@1.19.3/es2022/prosemirror-model.mjs","https://esm.sh/v135/prosemirror-model@1.22.1/es2022/prosemirror-model.mjs":"https://esm.sh/v135/prosemirror-model@1.19.3/es2022/prosemirror-model.mjs"}}
</script>
<script src="{{ asset('js/flowbite-wysiwyg-init.js') }}?v=20260516d"></script>
