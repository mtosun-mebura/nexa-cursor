(function () {
    function byId(id) {
        return id ? document.getElementById(id) : null;
    }

    function setLoading(els, on) {
        els.forEach(function (el) {
            if (!el) return;
            el.classList.toggle('hidden', !on);
            el.setAttribute('aria-busy', on ? 'true' : 'false');
        });
    }

    window.bindAdminPostcodeLookup = function (cfg) {
        var postcodeInput = byId(cfg.postcode);
        var houseNumberInput = byId(cfg.huisnummer);
        var streetInput = byId(cfg.street);
        var cityInput = byId(cfg.city);
        var countryInput = byId(cfg.country || '');
        if (!postcodeInput || !houseNumberInput || !streetInput || !cityInput) return;

        var csrf = document.querySelector('meta[name="csrf-token"]');
        if (!csrf) return;

        var loaders = (cfg.loading || []).map(byId).filter(Boolean);
        var url = cfg.url;
        var lookupTimeout;
        var requestSeq = 0;

        function lookup() {
            clearTimeout(lookupTimeout);
            var postcode = postcodeInput.value.trim().toUpperCase().replace(/\s+/g, '');
            var huisnummer = houseNumberInput.value.trim();
            if (!/^[1-9][0-9]{3}[A-Z]{2}$/.test(postcode) || !huisnummer) {
                setLoading(loaders, false);
                return;
            }

            setLoading(loaders, true);
            var seq = ++requestSeq;
            lookupTimeout = setTimeout(function () {
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf.getAttribute('content')
                    },
                    body: JSON.stringify({ postcode: postcode, huisnummer: huisnummer })
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (seq !== requestSeq) return;
                        if (data && data.success && (data.street || data.city)) {
                            if (data.street) streetInput.value = data.street;
                            if (data.city) cityInput.value = data.city;
                            if (countryInput) countryInput.value = data.country || 'Nederland';
                            if (data.street) streetInput.setAttribute('readonly', 'readonly');
                            if (data.city) cityInput.setAttribute('readonly', 'readonly');
                            if (countryInput && data.city) countryInput.setAttribute('readonly', 'readonly');
                            streetInput.dispatchEvent(new Event('input', { bubbles: true }));
                            cityInput.dispatchEvent(new Event('input', { bubbles: true }));
                            var validator = postcodeInput.closest('form') && postcodeInput.closest('form')._formValidator;
                            [streetInput, cityInput, countryInput].forEach(function (field) {
                                if (!field) return;
                                field.dataset.userInteracted = 'true';
                                if (validator && typeof validator.validateField === 'function') {
                                    validator.validateField(field, null, true);
                                }
                            });
                            if (typeof cfg.onSuccess === 'function') cfg.onSuccess(data);
                        } else {
                            streetInput.removeAttribute('readonly');
                            cityInput.removeAttribute('readonly');
                            if (countryInput) countryInput.removeAttribute('readonly');
                        }
                    })
                    .catch(function () {
                        if (seq !== requestSeq) return;
                        streetInput.removeAttribute('readonly');
                        cityInput.removeAttribute('readonly');
                        if (countryInput) countryInput.removeAttribute('readonly');
                    })
                    .finally(function () {
                        if (seq === requestSeq) setLoading(loaders, false);
                    });
            }, 150);
        }

        postcodeInput.addEventListener('blur', lookup);
        houseNumberInput.addEventListener('blur', lookup);
    };
})();
