{{-- Canonieke logo-/titelmaten — gelijk aan native AppBrandLogo / AppPageHeader / ScreenHeader. --}}
<style>
    :root {
        --nexa-brand-logo-width: 10.5rem;   /* 168px */
        --nexa-brand-logo-height: 2.625rem; /* 42px */
        --nexa-brand-logo-bar-min: 2.75rem; /* 44px */
        --nexa-screen-title-size: 1.25rem;  /* 20px */
        --nexa-screen-title-line: 1.5rem;   /* 24px */
        --nexa-page-side-slot: 2.5rem;      /* 40px */
    }

    .app-logo-bar img,
    .tenant-logo-bar img,
    .nexa-brand-logo {
        display: block;
        width: auto;
        height: var(--nexa-brand-logo-height);
        max-width: var(--nexa-brand-logo-width);
        max-height: var(--nexa-brand-logo-height);
        object-fit: contain;
        object-position: center center;
        margin-left: auto;
        margin-right: auto;
        background: transparent;
    }

    .app-nav-title,
    .nexa-screen-title {
        margin: 0;
        text-align: center;
        font-size: var(--nexa-screen-title-size);
        line-height: var(--nexa-screen-title-line);
        font-weight: 700;
        color: var(--text);
        min-width: 0;
    }

    .driver-section-head h2 {
        font-size: var(--nexa-screen-title-size) !important;
        line-height: var(--nexa-screen-title-line) !important;
        font-weight: 700 !important;
    }
</style>
