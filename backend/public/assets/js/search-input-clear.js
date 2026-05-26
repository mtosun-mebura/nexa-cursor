/**
 * Wis-knop rechts in zoekvelden (label.kt-input + datatable-zoek of name=search).
 */
(function () {
  'use strict';

  function shouldEnhance(input) {
    if (input.dataset.searchClearInit === '1') return false;
    if (input.type !== 'text' && input.type !== 'search') return false;
    if (input.hasAttribute('data-no-search-clear')) return false;
    return (
      input.hasAttribute('data-kt-datatable-search') ||
      (input.getAttribute('name') === 'search')
    );
  }

  function addClearButton(input) {
    if (!shouldEnhance(input)) return;
    var label = input.closest('label.kt-input');
    if (!label) return;

    input.dataset.searchClearInit = '1';

    if (getComputedStyle(label).position === 'static') {
      label.style.position = 'relative';
    }

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className =
      'kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost absolute end-1 top-1/2 z-[2] -translate-y-1/2 hidden';
    btn.setAttribute('aria-label', 'Zoekveld wissen');
    btn.setAttribute('tabindex', '-1');
    btn.innerHTML = '<i class="ki-filled ki-cross text-sm"></i>';
    label.appendChild(btn);

    if (!input.classList.contains('pe-10') && !input.style.paddingRight) {
      input.classList.add('pe-10');
    }

    function sync() {
      var has = String(input.value || '').trim().length > 0;
      btn.classList.toggle('hidden', !has);
    }

    input.addEventListener('input', sync);
    input.addEventListener('change', sync);
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      input.value = '';
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      try {
        input.focus();
      } catch (_) {}
      sync();
    });
    sync();
  }

  function init() {
    document.querySelectorAll('label.kt-input input').forEach(addClearButton);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
