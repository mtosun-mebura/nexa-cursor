/**
 * Wis-knop rechts in zoekvelden + live datatable-zoek (ook mobiel).
 */
(function () {
  'use strict';

  var SEARCH_DELAY_MS = 120;

  function shouldEnhance(input) {
    if (input.dataset.searchClearInit === '1') return false;
    if (input.type !== 'text' && input.type !== 'search') return false;
    if (input.hasAttribute('data-no-search-clear')) return false;
    return (
      input.hasAttribute('data-kt-datatable-search') ||
      input.getAttribute('name') === 'search'
    );
  }

  function getDatatableInstance(input) {
    var selector = input.getAttribute('data-kt-datatable-search');
    if (!selector) return null;

    var tableEl = document.querySelector(selector);
    if (!tableEl || !window.KTDataTable || typeof window.KTDataTable.getInstance !== 'function') {
      return null;
    }

    return window.KTDataTable.getInstance(tableEl) || null;
  }

  function runDatatableSearch(input, value) {
    var instance = getDatatableInstance(input);
    if (instance && typeof instance.search === 'function') {
      instance.search(value);
      return true;
    }
    return false;
  }

  function restoreInputValue(input, value) {
    if (document.activeElement !== input) return;
    if (input.value !== value) {
      input.value = value;
    }
  }

  function removeHiddenSearchFields(exceptForm) {
    document.querySelectorAll('form input[type="hidden"][name="search"]').forEach(function (el) {
      if (!exceptForm || !exceptForm.contains(el)) {
        el.remove();
      }
    });
  }

  function bindDatatableLiveSearch(input) {
    if (!input.hasAttribute('data-kt-datatable-search')) return;

    var timer = null;

    function scheduleSearch() {
      if (timer) clearTimeout(timer);
      timer = setTimeout(function () {
        timer = null;
        var value = input.value;
        if (!runDatatableSearch(input, value)) {
          input.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true }));
        }
        restoreInputValue(input, value);
        setTimeout(function () { restoreInputValue(input, value); }, 0);
        setTimeout(function () { restoreInputValue(input, value); }, 60);
      }, SEARCH_DELAY_MS);
    }

    input.addEventListener('input', scheduleSearch);
    input.addEventListener('compositionend', scheduleSearch);

    input._adminCancelDatatableSearch = function () {
      if (timer) {
        clearTimeout(timer);
        timer = null;
      }
    };
  }

  function clearSearchInput(input) {
    if (typeof input._adminCancelDatatableSearch === 'function') {
      input._adminCancelDatatableSearch();
    }

    input.value = '';

    var form = input.closest('form');
    var urlSearch = new URLSearchParams(window.location.search).get('search');
    var hadServerSearch = urlSearch !== null && String(urlSearch).trim() !== '';

    removeHiddenSearchFields(form);

    if (hadServerSearch && form && String(form.method || '').toLowerCase() === 'get') {
      form.submit();
      return;
    }

    if (!runDatatableSearch(input, '')) {
      input.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true }));
    }

    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function addClearButton(input) {
    if (!shouldEnhance(input)) return;
    var label = input.closest('label.kt-input');
    if (!label) return;

    input.dataset.searchClearInit = '1';
    label.classList.add('admin-search-input-wrap');
    bindDatatableLiveSearch(input);

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'admin-search-clear-btn';
    btn.setAttribute('aria-label', 'Zoekveld wissen');
    btn.setAttribute('tabindex', '-1');
    btn.innerHTML = '<i class="ki-filled ki-cross text-sm"></i>';
    label.appendChild(btn);

    function sync() {
      var has = String(input.value || '').length > 0;
      btn.classList.toggle('is-visible', has);
      btn.setAttribute('aria-hidden', has ? 'false' : 'true');
    }

    input.addEventListener('input', sync);
    input.addEventListener('change', sync);

    btn.addEventListener('pointerdown', function (e) {
      e.preventDefault();
      e.stopPropagation();
    });

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      clearSearchInput(input);
      sync();
      input.focus();
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
