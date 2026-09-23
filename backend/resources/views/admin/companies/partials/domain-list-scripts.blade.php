{{-- Shared AJAX handlers for tenant domain list (show + wizard). --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const domainList = document.getElementById('company-domains-list');
    const domainEmptyMsg = document.getElementById('company-domains-empty');
    const domainListWrap = document.getElementById('company-domains-list-wrap');
    const domainAddForm = document.getElementById('company-domain-add-form');

    if (!domainList && !domainAddForm) {
        return;
    }

    function applyCompanyDomainsList(data) {
        if (domainList && data.tbody_html !== undefined) {
            domainList.innerHTML = data.tbody_html;
        }
        const hasDomains = data.has_domains !== false;
        if (hasDomains) {
            domainEmptyMsg?.classList.add('hidden');
            domainListWrap?.classList.remove('hidden');
            if (domainAddForm) {
                domainAddForm.classList.add('pt-5', 'border-t', 'border-border');
                domainAddForm.classList.remove('rounded-xl', 'border', 'border-input', 'bg-muted/15', 'p-4', 'sm:p-5');
            }
        } else {
            domainEmptyMsg?.classList.remove('hidden');
            domainListWrap?.classList.add('hidden');
            if (domainAddForm) {
                domainAddForm.classList.remove('pt-5', 'border-t', 'border-border');
                domainAddForm.classList.add('rounded-xl', 'border', 'border-input', 'bg-muted/15', 'p-4', 'sm:p-5');
            }
        }
    }

    function fetchJsonDomainAction(url, formData) {
        return fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().then(function (data) {
                return { ok: response.ok, status: response.status, data: data };
            }).catch(function () {
                return { ok: response.ok, status: response.status, data: {} };
            });
        });
    }

    function setDomainEditOpen(row, open) {
        if (!row) return;
        const view = row.querySelector('[data-domain-view]');
        const form = row.querySelector('[data-domain-edit-form]');
        const toggle = row.querySelector('.js-domain-edit-toggle');
        if (view) {
            view.classList.toggle('hidden', open);
        }
        if (form) {
            form.classList.toggle('hidden', !open);
            if (open) {
                form.classList.add('flex');
                const input = form.querySelector('input[name="host"]');
                input?.focus();
                input?.select();
            } else {
                form.classList.remove('flex');
                const err = form.querySelector('[data-domain-edit-error]');
                if (err) {
                    err.textContent = '';
                    err.classList.add('hidden');
                }
            }
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.js-domain-edit-toggle');
        if (editBtn) {
            e.preventDefault();
            const row = editBtn.closest('[data-domain-row]');
            const form = row?.querySelector('[data-domain-edit-form]');
            const isOpen = form && !form.classList.contains('hidden');
            document.querySelectorAll('[data-domain-row]').forEach(function (r) {
                setDomainEditOpen(r, false);
            });
            if (!isOpen) {
                setDomainEditOpen(row, true);
            }
            return;
        }
        const cancelBtn = e.target.closest('.js-domain-edit-cancel');
        if (cancelBtn) {
            e.preventDefault();
            const row = cancelBtn.closest('[data-domain-row]');
            const form = row?.querySelector('[data-domain-edit-form]');
            const label = row?.querySelector('[data-domain-host-label]');
            if (form && label) {
                const input = form.querySelector('input[name="host"]');
                if (input) {
                    input.value = label.textContent.trim();
                }
            }
            setDomainEditOpen(row, false);
        }
    });

    document.addEventListener('change', function (e) {
        const sw = e.target;
        if (!(sw instanceof HTMLInputElement) || !sw.classList.contains('js-domain-primary-switch')) {
            return;
        }
        const form = sw.closest('form.js-company-domain-action');
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        // Reflect intent: checked → set primary; unchecked → clear
        let clearInput = form.querySelector('input[name="clear"]');
        if (sw.checked) {
            clearInput?.remove();
        } else if (!clearInput) {
            clearInput = document.createElement('input');
            clearInput.type = 'hidden';
            clearInput.name = 'clear';
            clearInput.value = '1';
            form.appendChild(clearInput);
        }
        form.requestSubmit();
    });

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.classList.contains('js-company-domain-action')) {
            return;
        }
        e.preventDefault();

        const runDomainAction = function () {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            const editErr = form.querySelector('[data-domain-edit-error]');
            if (editErr) {
                editErr.textContent = '';
                editErr.classList.add('hidden');
            }

            fetchJsonDomainAction(form.action, new FormData(form))
                .then(function (result) {
                    if (!result.ok) {
                        if (result.status === 422 && result.data && result.data.errors && result.data.errors.host) {
                            const msg = Array.isArray(result.data.errors.host)
                                ? result.data.errors.host[0]
                                : result.data.errors.host;
                            if (editErr) {
                                editErr.textContent = msg;
                                editErr.classList.remove('hidden');
                            } else {
                                alert(msg);
                            }
                            // Revert primary switch if validation somehow failed
                            const sw = form.querySelector('.js-domain-primary-switch');
                            if (sw) {
                                sw.checked = !sw.checked;
                            }
                            return;
                        }
                        throw new Error((result.data && result.data.message) ? result.data.message : 'Actie mislukt');
                    }
                    applyCompanyDomainsList(result.data);
                })
                .catch(function (err) {
                    const sw = form.querySelector('.js-domain-primary-switch');
                    if (sw && form.getAttribute('data-domain-primary-toggle') === '1') {
                        sw.checked = !sw.checked;
                    }
                    alert(err.message || 'Er is een fout opgetreden.');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                });
        };

        if (form.getAttribute('data-domain-destroy') === '1') {
            if (typeof window.showAdminConfirm === 'function') {
                window.showAdminConfirm({
                    title: 'Domein verwijderen',
                    message: 'Domein verwijderen?',
                    confirmLabel: 'Verwijderen'
                }).then(function (ok) {
                    if (ok) {
                        runDomainAction();
                    }
                });
                return;
            }
            if (!window.confirm('Domein verwijderen?')) {
                return;
            }
        }

        runDomainAction();
    });

    if (domainAddForm) {
        const hostInput = document.getElementById('domain_host');
        const ajaxErr = document.getElementById('domain-host-error-ajax');

        function clearDomainHostErrors() {
            if (ajaxErr) {
                ajaxErr.textContent = '';
                ajaxErr.classList.add('hidden');
            }
            if (hostInput) {
                hostInput.classList.remove('border-destructive');
            }
        }

        hostInput?.addEventListener('input', clearDomainHostErrors);

        domainAddForm.addEventListener('submit', function (e) {
            e.preventDefault();
            clearDomainHostErrors();
            const submitBtn = domainAddForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            fetchJsonDomainAction(domainAddForm.action, new FormData(domainAddForm))
                .then(function (result) {
                    if (!result.ok) {
                        if (result.status === 422 && result.data && result.data.errors && result.data.errors.host) {
                            const msg = Array.isArray(result.data.errors.host)
                                ? result.data.errors.host[0]
                                : result.data.errors.host;
                            if (ajaxErr) {
                                ajaxErr.textContent = msg;
                                ajaxErr.classList.remove('hidden');
                            }
                            if (hostInput) {
                                hostInput.classList.add('border-destructive');
                            }
                            return;
                        }
                        throw new Error((result.data && result.data.message) ? result.data.message : 'Opslaan mislukt');
                    }
                    applyCompanyDomainsList(result.data);
                    domainAddForm.reset();
                })
                .catch(function (err) {
                    alert(err.message || 'Er is een fout opgetreden bij het toevoegen van het domein.');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                });
        });
    }
});
</script>
