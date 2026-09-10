{{-- Standaard admin-bevestiging (verwijderen en overige confirms). --}}
<style>
    #admin-confirm-modal .admin-modal-panel {
        background-color: #ffffff;
        color: #0f172a;
        box-shadow:
            0 25px 50px -12px rgba(2, 6, 23, 0.35),
            0 0 0 1px rgba(15, 23, 42, 0.06);
    }
    html.dark #admin-confirm-modal .admin-modal-panel,
    .dark #admin-confirm-modal .admin-modal-panel {
        background-color: #0b0f19;
        color: #f8fafc;
        box-shadow:
            0 25px 50px -12px rgba(0, 0, 0, 0.65),
            0 0 0 1px rgba(148, 163, 184, 0.12);
    }
</style>
<div id="admin-confirm-modal"
     class="hidden fixed inset-0 z-[100000] items-center justify-center p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="admin-confirm-modal-title"
     hidden>
    <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-md" data-admin-confirm-dismiss></div>
    <div class="admin-modal-panel relative z-10 w-full max-w-md rounded-2xl border border-border shadow-2xl">
        <div class="border-b border-border px-5 py-5">
            <h3 id="admin-confirm-modal-title" class="text-lg font-semibold text-foreground mb-0">Bevestigen</h3>
        </div>
        <div class="px-5 py-5">
            <p id="admin-confirm-modal-message" class="text-sm text-muted-foreground mb-0 whitespace-pre-wrap"></p>
        </div>
        <div class="border-t border-border px-5 py-5 flex flex-wrap justify-end gap-2">
            <button type="button" class="kt-btn kt-btn-outline" data-admin-confirm-dismiss>Annuleren</button>
            <button type="button" class="kt-btn kt-btn-destructive" data-admin-confirm-accept>Verwijderen</button>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('admin-confirm-modal');
    if (!modal) {
        return;
    }
    var titleEl = document.getElementById('admin-confirm-modal-title');
    var messageEl = document.getElementById('admin-confirm-modal-message');
    var acceptBtn = modal.querySelector('[data-admin-confirm-accept]');
    var pending = null;
    var lastActive = null;

    function decodeAttr(value) {
        var s = String(value || '')
            .replace(/\\n/g, '\n')
            .replace(/\\'/g, "'")
            .replace(/\\"/g, '"');
        var ta = document.createElement('textarea');
        ta.innerHTML = s;
        return ta.value.trim();
    }

    function extractConfirmMessage(el) {
        if (!el || !el.getAttribute) {
            return '';
        }
        var data = el.getAttribute('data-admin-confirm')
            || el.getAttribute('data-confirm-message');
        if (data) {
            return data.trim();
        }
        var attr = el.getAttribute('onsubmit') || el.getAttribute('onclick') || '';
        var match = attr.match(/confirm\s*\(\s*(['"])([\s\S]*?)\1\s*\)/);
        return match ? decodeAttr(match[2]).trim() : '';
    }

    function hasConfirm(el) {
        return extractConfirmMessage(el) !== '';
    }

    function isDestructive(message, el) {
        var label = ((el && el.getAttribute('data-admin-confirm-label')) || '') + ' ' + (message || '');
        return /verwijder|wissen|annuleer|deactiveer|ontkoppel|afmelden|overschrijf|herstel|leeg/i.test(label);
    }

    function inferTitle(message, el) {
        var title = el && (el.getAttribute('data-admin-confirm-title') || el.getAttribute('data-confirm-title'));
        if (title) {
            return title;
        }
        if (isDestructive(message, el)) {
            return 'Verwijderen';
        }
        return 'Bevestigen';
    }

    function inferConfirmLabel(message, el) {
        var label = el && el.getAttribute('data-admin-confirm-label');
        if (label) {
            return label;
        }
        if (isDestructive(message, el)) {
            return 'Verwijderen';
        }
        return 'Bevestigen';
    }

    function closeModal(didAccept) {
        var action = pending;
        pending = null;
        modal.hidden = true;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.removeEventListener('keydown', onKeydown, true);
        if (lastActive && typeof lastActive.focus === 'function') {
            try { lastActive.focus(); } catch (e) {}
        }
        lastActive = null;
        if (!action) {
            return;
        }
        if (didAccept && typeof action.onAccept === 'function') {
            action.onAccept();
        } else if (!didAccept && typeof action.onCancel === 'function') {
            action.onCancel();
        }
    }

    function onKeydown(e) {
        if (e.key === 'Escape' && !modal.hidden) {
            e.preventDefault();
            closeModal(false);
        }
    }

    function openModal(opts) {
        opts = opts || {};
        var message = (opts.message || '').toString().trim();
        if (!message) {
            if (typeof opts.onAccept === 'function') {
                opts.onAccept();
            }
            return;
        }
        pending = opts;
        lastActive = document.activeElement;
        if (titleEl) {
            titleEl.textContent = opts.title || 'Bevestigen';
        }
        if (messageEl) {
            messageEl.textContent = message;
        }
        if (acceptBtn) {
            acceptBtn.textContent = opts.confirmLabel || 'Bevestigen';
            acceptBtn.classList.toggle('kt-btn-destructive', opts.destructive !== false);
            acceptBtn.classList.toggle('kt-btn-primary', opts.destructive === false);
        }
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        document.querySelectorAll('.kt-menu-dropdown.show, [data-kt-menu-dropdown].show').forEach(function (el) {
            el.classList.remove('show');
        });
        modal.hidden = false;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.addEventListener('keydown', onKeydown, true);
        var cancelBtn = modal.querySelector('[data-admin-confirm-dismiss].kt-btn');
        if (cancelBtn) {
            cancelBtn.focus();
        }
    }

    function submitForm(form) {
        if (!form) {
            return;
        }
        form.adminConfirmAccepted = true;
        var originalOnsubmit = form.getAttribute('onsubmit');
        if (originalOnsubmit) {
            form.setAttribute('data-admin-confirm-onsubmit', originalOnsubmit);
            form.removeAttribute('onsubmit');
        }
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            HTMLFormElement.prototype.submit.call(form);
        }
        queueMicrotask(function () {
            form.adminConfirmAccepted = false;
            var stored = form.getAttribute('data-admin-confirm-onsubmit');
            if (stored !== null) {
                form.setAttribute('onsubmit', stored);
                form.removeAttribute('data-admin-confirm-onsubmit');
            }
        });
    }

    window.showAdminConfirm = function (options) {
        options = options || {};
        return new Promise(function (resolve) {
            var message = options.message || '';
            var destructive = options.destructive;
            if (destructive === undefined) {
                destructive = isDestructive(message, null);
            }
            openModal({
                title: options.title || inferTitle(message, null),
                message: message,
                confirmLabel: options.confirmLabel || inferConfirmLabel(message, null),
                destructive: destructive,
                onAccept: function () { resolve(true); },
                onCancel: function () { resolve(false); },
            });
        });
    };

    function confirmElement(el, thenSubmitForm) {
        var message = extractConfirmMessage(el);
        if (!message) {
            return false;
        }
        openModal({
            title: inferTitle(message, el),
            message: message,
            confirmLabel: inferConfirmLabel(message, el),
            destructive: isDestructive(message, el),
            onAccept: function () {
                if (thenSubmitForm) {
                    submitForm(thenSubmitForm);
                    return;
                }
                if (el.matches && el.matches('button, input, a')) {
                    el.adminConfirmAccepted = true;
                    var originalOnclick = el.getAttribute('onclick');
                    if (originalOnclick) {
                        el.removeAttribute('onclick');
                    }
                    el.click();
                    queueMicrotask(function () {
                        el.adminConfirmAccepted = false;
                        if (originalOnclick) {
                            el.setAttribute('onclick', originalOnclick);
                        }
                    });
                }
            },
        });
        return true;
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        if (form.adminConfirmAccepted) {
            return;
        }
        if (form.hasAttribute('data-admin-confirm-skip')) {
            return;
        }
        if (!hasConfirm(form)) {
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        confirmElement(form, form);
    }, true);

    document.addEventListener('click', function (e) {
        var target = e.target;
        if (target && target.nodeType !== 1) {
            target = target.parentElement;
        }
        if (!target || typeof target.closest !== 'function') {
            return;
        }
        if (target.closest('#admin-confirm-modal')) {
            if (target.closest('[data-admin-confirm-accept]')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                closeModal(true);
            } else if (target === modal || target.closest('[data-admin-confirm-dismiss]')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                closeModal(false);
            }
            return;
        }
        var btn = target.closest('button, input[type="submit"], input[type="button"]');
        if (!btn) {
            return;
        }
        if (btn.adminConfirmAccepted) {
            return;
        }
        var form = btn.form || btn.closest('form');
        var type = (btn.getAttribute('type') || (btn.tagName === 'BUTTON' ? 'submit' : '')).toLowerCase();
        if (form && hasConfirm(form) && (type === 'submit' || type === '')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            confirmElement(form, form);
            return;
        }
        if (hasConfirm(btn) && (!form || !hasConfirm(form))) {
            var associated = btn.getAttribute('form') ? document.getElementById(btn.getAttribute('form')) : form;
            e.preventDefault();
            e.stopImmediatePropagation();
            if (associated && (type === 'submit' || btn.hasAttribute('form'))) {
                confirmElement(btn, associated);
            } else {
                confirmElement(btn, null);
            }
        }
    }, true);

    modal.addEventListener('click', function (e) {
        var target = e.target;
        if (target && target.nodeType !== 1) {
            target = target.parentElement;
        }
        if (!target || typeof target.closest !== 'function') {
            return;
        }
        if (target.closest('[data-admin-confirm-accept]')) {
            e.preventDefault();
            closeModal(true);
            return;
        }
        if (target === modal || target.closest('[data-admin-confirm-dismiss]')) {
            e.preventDefault();
            closeModal(false);
        }
    });
})();
</script>
