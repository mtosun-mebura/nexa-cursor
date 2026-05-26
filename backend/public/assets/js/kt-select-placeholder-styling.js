/**
 * Zorgt dat lege kt-select / KTUI-selects de placeholder visueel als muted tonen.
 */
(function () {
  'use strict';

  function injectOnce() {
    if (document.getElementById('kt-select-placeholder-styles')) return;
    var el = document.createElement('style');
    el.id = 'kt-select-placeholder-styles';
    el.textContent = [
      '[data-kt-select="true"] button.kt-select-toggle[aria-placeholder="true"],',
      '[data-kt-select="true"] .kt-select-toggle[aria-placeholder="true"],',
      'select[data-kt-select="true"] + .kt-select .kt-select-toggle:empty {',
      '  color: var(--muted-foreground, #6b7280) !important;',
      '}',
      '.dark [data-kt-select="true"] button.kt-select-toggle[aria-placeholder="true"],',
      '.dark [data-kt-select="true"] .kt-select-toggle[aria-placeholder="true"] {',
      '  color: var(--muted-foreground, #9ca3af) !important;',
      '}',
    ].join(' ');
    document.head.appendChild(el);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectOnce);
  } else {
    injectOnce();
  }
})();
