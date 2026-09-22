<style>
  html { color-scheme: light dark; }
  .redirect-page-body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    background: #f8fafc !important;
    color: #0f172a !important;
  }
  html.dark .redirect-page-body,
  html[data-kt-theme-mode="dark"] .redirect-page-body {
    background: #070a12 !important;
    color: #e5e7eb !important;
  }
  .redirect-page-shell {
    width: max-content;
    max-width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .redirect-page-logo {
    margin-bottom: 1.25rem;
    text-align: center;
  }
  .redirect-card {
    width: max-content;
    max-width: 100%;
    box-sizing: border-box;
    padding: 1.25rem 1.5rem;
    border-radius: 0.75rem;
    text-align: center;
    background: #ffffff !important;
    border: 1px solid #e2e8f0;
    color: #0f172a !important;
    box-shadow: 0 1px 2px rgb(15 23 42 / 0.06);
    --redirect-title: #0f172a;
    --redirect-muted: #475569;
    --redirect-faint: #64748b;
  }
  html.dark .redirect-card,
  html[data-kt-theme-mode="dark"] .redirect-card {
    background: #111827 !important;
    border-color: rgb(148 163 184 / 0.22);
    color: #e5e7eb !important;
    box-shadow: none;
    --redirect-title: #f8fafc;
    --redirect-muted: #94a3b8;
    --redirect-faint: #94a3b8;
  }
  .redirect-card__title {
    margin: 0 0 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1.35;
    color: var(--redirect-title) !important;
    -webkit-text-fill-color: var(--redirect-title) !important;
  }
  .redirect-card__text {
    margin: 0 0 0.75rem;
    font-size: 0.9375rem;
    line-height: 1.5;
    color: var(--redirect-muted) !important;
  }
  .redirect-card__countdown {
    margin: 0 0 1rem;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: var(--redirect-faint) !important;
  }
  .redirect-card__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f97316 !important;
    color: #ffffff !important;
    font-weight: 600;
    font-size: 0.875rem;
    text-decoration: none !important;
  }
  .redirect-card__btn:hover {
    background: #ea580c !important;
    color: #ffffff !important;
  }
</style>
