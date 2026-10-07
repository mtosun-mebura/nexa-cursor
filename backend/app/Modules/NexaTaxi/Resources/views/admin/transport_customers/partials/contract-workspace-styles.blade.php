<style>
    .contract-workspace-nav {
        margin-top: 0.25rem;
        border: 1px solid var(--border);
        border-radius: 0.875rem;
        background: var(--background);
        overflow: hidden;
    }

    .contract-workspace-nav__scroll {
        display: flex;
        gap: 0;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: color-mix(in oklab, var(--muted-foreground) 35%, transparent) transparent;
    }

    .contract-workspace-nav__scroll::-webkit-scrollbar {
        height: 6px;
    }

    .contract-workspace-nav__scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .contract-workspace-nav__scroll::-webkit-scrollbar-thumb {
        background: color-mix(in oklab, var(--muted-foreground) 35%, transparent);
        border-radius: 999px;
    }

    .contract-workspace-nav__tab {
        flex: 1 1 8.5rem;
        min-width: 8.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        padding: 0.85rem 1rem;
        text-decoration: none;
        color: inherit;
        border-right: 1px solid var(--border);
        border-bottom: 2px solid transparent;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .contract-workspace-nav__tab:last-child {
        border-right: 0;
    }

    .contract-workspace-nav__tab:hover {
        background: color-mix(in oklab, var(--primary) 5%, var(--background));
    }

    .contract-workspace-nav__tab.is-active {
        background: color-mix(in oklab, var(--primary) 8%, var(--background));
        border-bottom-color: var(--primary);
    }

    .contract-workspace-nav__label {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--foreground);
        line-height: 1.25;
        white-space: nowrap;
    }

    .contract-workspace-nav__count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.35rem;
        height: 1.35rem;
        padding: 0 0.35rem;
        border-radius: 999px;
        background: color-mix(in oklab, var(--muted) 70%, transparent);
        color: var(--muted-foreground);
        font-size: 0.7rem;
        font-weight: 600;
    }

    .contract-workspace-nav__tab.is-active .contract-workspace-nav__count {
        background: color-mix(in oklab, var(--primary) 18%, transparent);
        color: var(--primary);
    }

    .contract-workspace-nav__hint {
        font-size: 0.7rem;
        color: var(--muted-foreground);
        line-height: 1.3;
    }

    .contract-hub-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    @media (min-width: 640px) {
        .contract-hub-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .contract-hub-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    .contract-hub-tile {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        min-height: 7.5rem;
        padding: 1rem 1.1rem;
        border: 1px solid var(--border);
        border-radius: 0.875rem;
        background: var(--background);
        text-decoration: none;
        color: inherit;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
    }

    .contract-hub-tile:hover {
        border-color: color-mix(in oklab, var(--primary) 35%, var(--border));
        background: color-mix(in oklab, var(--primary) 4%, var(--background));
        box-shadow: 0 0 0 1px color-mix(in oklab, var(--primary) 10%, transparent);
    }

    .contract-hub-tile__label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--muted-foreground);
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .contract-hub-tile__value {
        font-size: 1.75rem;
        font-weight: 650;
        line-height: 1;
        color: var(--foreground);
        font-variant-numeric: tabular-nums;
    }

    .contract-hub-tile__meta {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        line-height: 1.35;
        margin-top: auto;
    }
</style>
