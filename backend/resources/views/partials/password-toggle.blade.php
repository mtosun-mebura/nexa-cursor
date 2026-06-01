@once
{{-- Voegt automatisch een oogje-knop toe aan elk wachtwoordveld (input[type=password]) om het
     ingevoerde wachtwoord te tonen/verbergen. Werkt overal (admin, frontend, chauffeurs-app) zonder
     per-veld aanpassingen. Velden die al een KTUI-toggle hebben (data-kt-toggle-password) of
     data-no-password-toggle worden overgeslagen. --}}
<style>
.js-pw-toggle-wrap { position: relative; display: block; width: 100%; }
.js-pw-toggle-btn {
    position: absolute; top: 0; bottom: 0; right: .5rem; margin: auto 0;
    height: 1.75rem; width: 1.75rem;
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0; border: 0; background: transparent; color: currentColor;
    opacity: .55; cursor: pointer; border-radius: .375rem; line-height: 0; z-index: 3;
    -webkit-appearance: none; appearance: none;
}
.js-pw-toggle-btn:hover, .js-pw-toggle-btn:focus { opacity: .95; outline: none; }
.js-pw-toggle-btn svg { width: 1.15rem; height: 1.15rem; display: block; pointer-events: none; }
</style>
<script>
(function () {
    if (window.__nexaPwToggleInit) return;
    window.__nexaPwToggleInit = true;

    var EYE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>';
    var EYE_OFF = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><path d="M14.12 14.12A3 3 0 1 1 9.88 9.88"/><line x1="2" y1="2" x2="22" y2="22"/></svg>';

    function shouldSkip(input) {
        if (!input || input.dataset.pwToggle === 'done') return true;
        if (input.hasAttribute('data-no-password-toggle')) return true;
        if (typeof input.closest === 'function' && input.closest('[data-kt-toggle-password]')) return true;
        return false;
    }

    function enhance(input) {
        if (shouldSkip(input)) return;
        var parent = input.parentNode;
        if (!parent) return;
        input.dataset.pwToggle = 'done';

        // Hergebruik een bestaande .relative wrapper (bijv. aangemaakt door form-validation.js),
        // anders maken we zelf een wrapper. Zo voorkomen we dubbele wrappers en kapotte icoon-lookups.
        var wrap = (typeof input.closest === 'function') ? input.closest('.relative') : null;
        if (!wrap) {
            wrap = document.createElement('span');
            wrap.className = 'js-pw-toggle-wrap';
            parent.insertBefore(wrap, input);
            wrap.appendChild(input);
        } else if (window.getComputedStyle && getComputedStyle(wrap).position === 'static') {
            wrap.style.position = 'relative';
        }

        // Op formulieren met form-validation.js staat er (soms) een validatie-icoon uiterst rechts
        // (right: ~0.75rem). Dan plaatsen we het oogje links daarvan, zodat ze elkaar niet overlappen.
        var hasValidationIcon = (wrap.querySelector && wrap.querySelector('.validation-icon-wrapper')) ||
            (typeof input.closest === 'function' && input.closest('form[data-validate="true"]'));

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.tabIndex = -1;
        btn.className = 'js-pw-toggle-btn';
        btn.setAttribute('aria-label', 'Wachtwoord tonen');
        btn.setAttribute('aria-pressed', 'false');
        btn.innerHTML = EYE;
        if (hasValidationIcon) {
            btn.style.right = '2.5rem';
            input.style.paddingRight = '4.25rem';
        } else {
            input.style.paddingRight = '2.6rem';
        }
        wrap.appendChild(btn);

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? EYE_OFF : EYE;
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Wachtwoord verbergen' : 'Wachtwoord tonen');
        });
    }

    function scan(root) {
        var nodes = (root || document).querySelectorAll('input[type="password"]');
        for (var i = 0; i < nodes.length; i++) enhance(nodes[i]);
    }

    function init() {
        scan(document);
        if (typeof MutationObserver !== 'undefined') {
            var obs = new MutationObserver(function (muts) {
                for (var i = 0; i < muts.length; i++) {
                    var added = muts[i].addedNodes;
                    for (var j = 0; j < added.length; j++) {
                        var n = added[j];
                        if (!n || n.nodeType !== 1) continue;
                        if (n.matches && n.matches('input[type="password"]')) enhance(n);
                        if (n.querySelectorAll) scan(n);
                    }
                }
            });
            obs.observe(document.documentElement, { childList: true, subtree: true });
        }
    }

    // Met een kleine vertraging draaien zodat andere init-scripts (bv. form-validation.js, dat
    // wrappers en validatie-iconen aanmaakt) eerst klaar zijn; dan hergebruiken we hun wrapper.
    function boot() { setTimeout(init, 0); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
@endonce
