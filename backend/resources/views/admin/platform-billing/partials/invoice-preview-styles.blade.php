<style>
    .platform-billing-invoice-preview {
        min-width: 0;
        max-width: 100%;
    }

    .platform-billing-invoice-preview__header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.5rem;
        margin-bottom: 1.75rem;
    }

    .platform-billing-invoice-preview__logo-image {
        display: block;
        max-height: 4rem;
        max-width: 12.5rem;
        width: auto;
        height: auto;
        object-fit: contain;
    }

    .platform-billing-invoice-preview__issuer {
        margin-left: auto;
        max-width: 20rem;
        text-align: right;
        font-size: 0.8125rem;
        line-height: 1.5;
        color: var(--muted-foreground);
    }

    .platform-billing-invoice-preview__issuer strong {
        color: var(--foreground);
    }

    .platform-billing-invoice-preview__title {
        margin: 0 0 1.25rem;
        font-size: 1.375rem;
        font-weight: 700;
        line-height: 1.2;
        color: var(--foreground);
    }

    .platform-billing-invoice-preview__meta {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
        align-items: start;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
    }

    .platform-billing-invoice-preview__meta-left {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        min-width: 0;
    }

    .platform-billing-invoice-preview__meta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .platform-billing-invoice-preview__meta-label {
        width: 8.125rem;
        flex-shrink: 0;
        color: var(--muted-foreground);
    }

    .platform-billing-invoice-preview__meta-value {
        color: var(--foreground);
    }

    .platform-billing-invoice-preview__meta-right {
        min-width: 0;
        max-width: 20rem;
        padding-left: 1.75rem;
        text-align: left;
        color: var(--foreground);
    }

    .platform-billing-invoice-preview__customer-name {
        font-weight: 500;
    }

    .platform-billing-invoice-preview__table-wrap {
        min-width: 0;
        max-width: 100%;
        margin-bottom: 1.25rem;
    }

    .platform-billing-invoice-preview__table {
        table-layout: fixed;
        width: 100%;
    }

    .platform-billing-invoice-preview__table col.platform-billing-invoice-preview__col-description {
        width: auto;
    }

    .platform-billing-invoice-preview__table col.platform-billing-invoice-preview__col-qty {
        width: 4.25rem;
    }

    .platform-billing-invoice-preview__table col.platform-billing-invoice-preview__col-money {
        width: 7.25rem;
    }

    .platform-billing-invoice-preview__table thead th {
        font-size: 0.875rem;
        line-height: 1.25;
        font-weight: 400;
        vertical-align: top;
        padding-inline: calc(var(--spacing) * 4);
        padding-top: 0.625rem;
        padding-bottom: 0.625rem;
    }

    .platform-billing-invoice-preview__table thead th.platform-billing-invoice-preview__description,
    .platform-billing-invoice-preview__table tbody td.platform-billing-invoice-preview__description {
        padding-left: calc(var(--spacing) * 4);
        padding-right: 0.75rem;
    }

    .platform-billing-invoice-preview__table tbody td.platform-billing-invoice-preview__description {
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
        vertical-align: top;
        line-height: 1.45;
    }

    .platform-billing-invoice-preview__table tbody td.platform-billing-invoice-preview__num,
    .platform-billing-invoice-preview__table tbody td.platform-billing-invoice-preview__money {
        white-space: nowrap;
        vertical-align: top;
    }

    .platform-billing-invoice-preview__table thead th.platform-billing-invoice-preview__money-header {
        white-space: normal;
    }

    .platform-billing-invoice-preview__totals {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 1.25rem;
    }

    .platform-billing-invoice-preview__totals-inner {
        width: 100%;
        max-width: 20rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    .platform-billing-invoice-preview__totals-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
    }

    .platform-billing-invoice-preview__totals-row--grand {
        margin-top: 0.25rem;
        padding-top: 0.5rem;
        border-top: 1px solid var(--border);
        font-size: 1rem;
        font-weight: 600;
        color: var(--foreground);
    }

    .platform-billing-invoice-preview__notes {
        margin-bottom: 1rem;
        padding: 1rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        font-size: 0.875rem;
    }

    .platform-billing-invoice-preview__payment-terms {
        margin-top: 0.25rem;
        font-size: 0.75rem;
        line-height: 1.5;
        color: #64748b;
    }

    .dark .platform-billing-invoice-preview__payment-terms {
        color: var(--muted-foreground);
    }

    #invoice-preview-viewport {
        container-type: inline-size;
        container-name: invoice-preview;
        width: 100%;
        min-width: 0;
    }

    #invoice-preview-viewport.is-responsive-preview {
        max-width: 390px;
        margin-inline: auto;
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        padding: 1rem;
        background: var(--card, var(--background));
    }

    @container invoice-preview (max-width: 640px) {
        #invoice-preview-viewport .platform-billing-invoice-preview__header {
            flex-direction: column;
        }

        #invoice-preview-viewport .platform-billing-invoice-preview__issuer,
        #invoice-preview-viewport .platform-billing-invoice-preview__meta-right {
            max-width: none;
            width: 100%;
            padding-left: 0;
        }

        #invoice-preview-viewport .platform-billing-invoice-preview__issuer {
            margin-left: 0;
            text-align: left;
        }

        #invoice-preview-viewport .platform-billing-invoice-preview__meta {
            grid-template-columns: minmax(0, 1fr);
        }

        #invoice-preview-viewport .platform-billing-invoice-preview__table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        #invoice-preview-viewport .platform-billing-invoice-preview__table col.platform-billing-invoice-preview__col-money {
            width: 6.5rem;
        }

        #invoice-preview-viewport .platform-billing-invoice-preview__table col.platform-billing-invoice-preview__col-qty {
            width: 3.25rem;
        }

        #invoice-preview-viewport .platform-billing-invoice-preview__table thead th {
            font-size: 0.8125rem;
        }

        #invoice-preview-viewport .platform-billing-invoice-preview__totals {
            justify-content: stretch;
        }

        #invoice-preview-viewport .platform-billing-invoice-preview__totals-inner {
            max-width: none;
        }

        #invoice-preview-viewport .platform-billing-invoice-preview__totals-row > span:first-child {
            min-width: 0;
            flex: 1 1 auto;
            padding-right: 0.75rem;
        }

        #invoice-preview-viewport .platform-billing-invoice-preview__totals-row--grand {
            font-size: 0.9375rem;
        }
    }

    @media (max-width: 1023px) {
        #invoice-preview-responsive-btn {
            display: none;
        }
    }
</style>
