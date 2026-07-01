<style>
    .handleiding-prose h2 {
        margin-top: 2rem;
        margin-bottom: 0.75rem;
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--foreground);
        scroll-margin-top: 5rem;
    }
    .handleiding-prose h2:first-child { margin-top: 0; }
    .handleiding-prose h3 {
        margin-top: 1.25rem;
        margin-bottom: 0.5rem;
        font-size: 1rem;
        font-weight: 600;
    }
    .handleiding-prose p,
    .handleiding-prose li {
        color: var(--secondary-foreground);
        line-height: 1.65;
    }
    .handleiding-prose p { margin-bottom: 0.75rem; }
    .handleiding-prose ul,
    .handleiding-prose ol {
        margin: 0.5rem 0 1rem 1.25rem;
        padding: 0;
    }
    .handleiding-prose li { margin-bottom: 0.35rem; }
    .handleiding-step {
        display: flex;
        gap: 0.875rem;
        margin-bottom: 1rem;
    }
    .handleiding-step-num {
        flex-shrink: 0;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 9999px;
        background: color-mix(in srgb, var(--primary) 12%, transparent);
        color: var(--primary);
        font-size: 0.8125rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .handleiding-tip {
        border-left: 3px solid var(--primary);
        background: color-mix(in srgb, var(--primary) 6%, transparent);
        border-radius: 0 0.5rem 0.5rem 0;
        padding: 0.875rem 1rem;
        margin: 1.25rem 0;
        font-size: 0.875rem;
        color: var(--secondary-foreground);
    }
    .handleiding-mock-sidebar {
        width: 11rem;
        border-right: 1px solid var(--border);
        background: var(--background);
        padding: 0.75rem 0.5rem;
        flex-shrink: 0;
    }
    .handleiding-mock-menu-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.6875rem;
        color: var(--secondary-foreground);
        margin-bottom: 0.15rem;
    }
    .handleiding-mock-menu-item.is-active {
        background: color-mix(in srgb, var(--primary) 10%, transparent);
        color: var(--primary);
        font-weight: 600;
    }
    @media (max-width: 1023px) {
        .handleiding-layout-aside { order: 2; }
        .handleiding-layout-main { order: 1; }
    }
</style>
