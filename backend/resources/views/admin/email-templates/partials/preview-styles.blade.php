<style>
    .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1.png') }}');
    }
    .dark .hero-bg {
        background-image: url('{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}');
    }

    /* Preview toont de e-mail zoals in een light-mode client (zelfde als verzonden mail). */
    .email-preview-content {
        border-radius: 0.75rem !important;
        overflow: hidden;
        color-scheme: light;
        background-color: #f3f4f4 !important;
    }

    .email-preview-content .prose {
        max-width: none !important;
        font-family: Arial, sans-serif !important;
        font-size: 14px !important;
        line-height: 1.5 !important;
        color: #333 !important;
        --tw-prose-body: #333333;
        --tw-prose-headings: #111827;
        --tw-prose-bold: #111827;
        --tw-prose-links: #2563eb;
    }

    .email-preview-content .prose,
    .email-preview-content .prose *:not(table):not(td):not(th) {
        text-align: left !important;
    }

    .email-preview-content .prose > table,
    .email-preview-content .prose > table table[role="presentation"]:first-of-type {
        width: 100% !important;
        max-width: 600px !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }

    .email-preview-content .info-request-email-card,
    .email-preview-content table.info-request-email-card,
    .email-preview-content table[style*="box-shadow: 0 2px 4px"],
    .email-preview-content table[style*="box-shadow:0 2px 4px"] {
        width: 100% !important;
        max-width: 600px !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        border-radius: 8px !important;
        overflow: hidden !important;
    }

    .email-preview-content .info-request-email-card > tbody > tr:not(.info-request-field-row) > td:only-child,
    .email-preview-content table.info-request-email-card > tbody > tr:not(.info-request-field-row) > td:only-child {
        width: 100% !important;
        box-sizing: border-box !important;
    }

    .email-preview-content .info-request-email-header,
    .email-preview-content td.info-request-email-header,
    .email-preview-content td[style*="background-color: #2563eb"],
    .email-preview-content td[style*="background-color:#2563eb"] {
        width: 100% !important;
        box-sizing: border-box !important;
        border-radius: 8px 8px 0 0 !important;
        background-color: #2563eb !important;
        color: #ffffff !important;
    }

    .email-preview-content .info-request-email-footer,
    .email-preview-content td.info-request-email-footer,
    .email-preview-content td[style*="background-color: #f9fafb"],
    .email-preview-content td[style*="background-color:#f9fafb"] {
        border-radius: 0 0 8px 8px !important;
        background-color: #f9fafb !important;
    }

    .email-preview-content .info-request-email-body,
    .email-preview-content td.info-request-email-body {
        width: 100% !important;
        box-sizing: border-box !important;
        background-color: #ffffff !important;
        color: #333333 !important;
    }

    .email-preview-content table.info-request-fields {
        width: 100% !important;
        max-width: none !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        margin: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .email-preview-content table.info-request-fields td.info-request-field-label {
        width: 175px !important;
        max-width: 175px !important;
        min-width: 175px !important;
        white-space: nowrap !important;
        text-align: right !important;
        padding: 6px 10px 6px 14px !important;
        vertical-align: top !important;
        background-color: #ffffff !important;
        color: #374151 !important;
        border: none !important;
    }

    .email-preview-content table.info-request-fields td.info-request-field-value,
    .email-preview-content table.info-request-fields td.info-request-field-value--multiline {
        width: auto !important;
        padding: 6px 10px 6px 10px !important;
        vertical-align: top !important;
        word-break: break-word !important;
        background-color: #ffffff !important;
        color: #111827 !important;
        border: none !important;
    }

    .email-preview-content tr.info-request-field-divider td {
        padding: 0 !important;
        margin: 0 !important;
        height: 1px !important;
        line-height: 1px !important;
        font-size: 1px !important;
        background-color: #d1d5db !important;
        border: none !important;
    }

    .email-preview-content table.info-request-fields td.info-request-field-value--multiline {
        white-space: pre-wrap !important;
    }

    .email-preview-content .prose p { margin: 0 0 0.75em 0 !important; color: #333333 !important; }
    .email-preview-content .prose h1 { font-size: 1.875em !important; font-weight: 700 !important; margin: 0 0 0.5em 0 !important; }
    .email-preview-content .prose h2 { font-size: 1.5em !important; font-weight: 600 !important; margin: 0 0 0.5em 0 !important; }
    .email-preview-content .prose h3 { font-size: 1.25em !important; font-weight: 600 !important; margin: 0 0 0.5em 0 !important; }
    .email-preview-content .prose ul,
    .email-preview-content .prose ol { margin: 0 0 0.75em 0 !important; padding-left: 1.5em !important; }
    .email-preview-content .prose a { color: #2563eb !important; }
    .email-preview-content img {
        width: auto !important;
        height: auto !important;
        max-width: 100% !important;
        object-fit: contain !important;
        display: block !important;
    }
</style>
