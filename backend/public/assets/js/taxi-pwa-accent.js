(function (window) {
    'use strict';

    var KEY = 'nexa-taxi-pwa-accent';
    var ALLOWED = ['orange', 'yellow', 'blue', 'red', 'green', 'pink'];

    function normalize(value) {
        var key = String(value == null ? '' : value).toLowerCase();
        return ALLOWED.indexOf(key) >= 0 ? key : 'orange';
    }

    function syncSwatches(accent) {
        document.querySelectorAll('[data-pwa-accent]').forEach(function (btn) {
            var on = btn.getAttribute('data-pwa-accent') === accent;
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.classList.toggle('is-selected', on);
        });
    }

    function apply(value) {
        var accent = normalize(value);
        document.documentElement.setAttribute('data-accent', accent);
        try {
            localStorage.setItem(KEY, accent);
        } catch (e) {}
        syncSwatches(accent);
        return accent;
    }

    function current() {
        return normalize(document.documentElement.getAttribute('data-accent'));
    }

    window.nexaPwaAccent = {
        KEY: KEY,
        apply: apply,
        current: current,
        normalize: normalize,
    };

    try {
        var stored = localStorage.getItem(KEY);
        if (stored) {
            apply(stored);
        } else {
            syncSwatches(current());
        }
    } catch (e) {
        syncSwatches(current());
    }
})(window);
