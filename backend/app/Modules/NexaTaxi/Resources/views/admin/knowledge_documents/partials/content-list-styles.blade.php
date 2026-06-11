@verbatim
<style>
    /* Metronic reset zet list-style: none; inline zodat dit altijd geladen wordt. */
    #content .knowledge-document-content ul,
    .knowledge-document-form .flowbite-wysiwyg-content .ProseMirror ul {
        list-style: none !important;
        padding-left: 0 !important;
        margin: 0.75rem 0 !important;
    }
    #content .knowledge-document-content ol,
    .knowledge-document-form .flowbite-wysiwyg-content .ProseMirror ol {
        list-style-type: decimal !important;
        padding-left: 1.5rem !important;
        margin: 0.75rem 0 !important;
    }
    #content .knowledge-document-content ul > li,
    .knowledge-document-form .flowbite-wysiwyg-content .ProseMirror ul > li {
        position: relative;
        display: block !important;
        padding-left: 1.25rem !important;
        margin: 0.35rem 0 !important;
    }
    #content .knowledge-document-content ul > li::before,
    .knowledge-document-form .flowbite-wysiwyg-content .ProseMirror ul > li::before {
        content: '–';
        position: absolute;
        left: 0;
        font-weight: 600;
    }
    #content .knowledge-document-content ol > li,
    .knowledge-document-form .flowbite-wysiwyg-content .ProseMirror ol > li {
        display: list-item !important;
        margin: 0.35rem 0 !important;
        padding-left: 0.15rem !important;
    }
    #content .knowledge-document-content ul > li > p,
    #content .knowledge-document-content ol > li > p,
    .knowledge-document-form .flowbite-wysiwyg-content .ProseMirror ul > li > p,
    .knowledge-document-form .flowbite-wysiwyg-content .ProseMirror ol > li > p {
        margin: 0 !important;
    }
    #content .knowledge-document-content :is(p, ul, ol, h3) {
        margin-bottom: 0.75rem;
    }
    #content .knowledge-document-content a {
        color: var(--primary);
        text-decoration: underline;
    }
</style>
@endverbatim
