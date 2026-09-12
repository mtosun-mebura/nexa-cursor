(function () {
    'use strict';

    const cfg = window.NEXA_TAXI_CONTRACT || {};
    const STORAGE_KEY = 'nexa_taxi_contract_token';
    const UI_STATE_KEY = 'nexa_taxi_contract_ui';
    const GUIDE_HINT_KEY = 'nexa_taxi_contract_dismiss_guide';
    const ANNOUNCEMENT_DISMISS_KEY = 'nexa_taxi_contract_dismiss_announcements';
    const VALID_TABS = ['today', 'week', 'navigation', 'absences', 'profile'];
    const TOKEN_MAX_AGE = 14 * 24 * 60 * 60;

    function readCookie(name) {
        try {
            const parts = ('; ' + document.cookie).split('; ' + name + '=');
            if (parts.length < 2) {
                return '';
            }
            return decodeURIComponent(parts.pop().split(';').shift() || '');
        } catch (e) {
            return '';
        }
    }

    function writeAuthCookie(name, value, maxAge) {
        let cookie = name + '=' + encodeURIComponent(value) + '; path=/taxi; max-age=' + maxAge + '; SameSite=Lax';
        if (window.location.protocol === 'https:') {
            cookie += '; Secure';
        }
        document.cookie = cookie;
    }

    function clearAuthCookie(name) {
        document.cookie = name + '=; path=/taxi; max-age=0; SameSite=Lax';
        document.cookie = name + '=; path=/; max-age=0; SameSite=Lax';
    }

    function persistToken(value, expiresAt) {
        if (!value) {
            clearPersistedAuth();
            return;
        }
        try { localStorage.setItem(STORAGE_KEY, value); } catch (e) { /* ignore */ }
        try { sessionStorage.setItem(STORAGE_KEY, value); } catch (e) { /* ignore */ }
        let maxAge = TOKEN_MAX_AGE;
        if (expiresAt) {
            const ts = Date.parse(expiresAt);
            if (!isNaN(ts)) {
                maxAge = Math.max(60, Math.floor((ts - Date.now()) / 1000));
            }
        }
        writeAuthCookie(STORAGE_KEY, value, maxAge);
    }

    function clearPersistedAuth() {
        token = '';
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) { /* ignore */ }
        try { sessionStorage.removeItem(STORAGE_KEY); } catch (e) { /* ignore */ }
        try { sessionStorage.removeItem(UI_STATE_KEY); } catch (e) { /* ignore */ }
        clearAuthCookie(STORAGE_KEY);
    }

    function readPersistedToken() {
        try {
            const local = localStorage.getItem(STORAGE_KEY);
            if (local) {
                persistToken(local);
                return local;
            }
        } catch (e) { /* ignore */ }
        try {
            const session = sessionStorage.getItem(STORAGE_KEY);
            if (session) {
                persistToken(session);
                return session;
            }
        } catch (e) { /* ignore */ }
        const cookie = readCookie(STORAGE_KEY);
        if (cookie) {
            persistToken(cookie);
            return cookie;
        }
        return '';
    }

    let token = readPersistedToken();
    let firstLoginEmail = '';
    let user = null;
    let pollTimer = null;
    let absencePassengerId = null;
    let weekFrom = null;
    let weekPayload = null;
    let selectedWeekDate = null;
    let planningView = 'day';
    let activeTab = 'today';
    let lastTodayPayload = null;
    let navigationStops = [];
    let navigationOrigin = null;
    let navigationMap = null;
    let navigationRenderer = null;
    let navigationMarkers = [];
    let navigationPolyline = null;
    let googleMapsLoadPromise = null;
    let navigationDrawToken = 0;

    function isIsoDate(value) {
        return typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value);
    }

    function persistUiState() {
        try {
            sessionStorage.setItem(
                UI_STATE_KEY,
                JSON.stringify({
                    tab: activeTab,
                    planningView: planningView,
                    selectedWeekDate: selectedWeekDate,
                    weekFrom: weekFrom,
                })
            );
        } catch (e) {
            /* ignore quota / private mode */
        }
    }

    function restoreUiState() {
        try {
            const raw = sessionStorage.getItem(UI_STATE_KEY);
            if (!raw) {
                return;
            }
            const data = JSON.parse(raw);
            if (!data || typeof data !== 'object') {
                return;
            }
            if (VALID_TABS.indexOf(data.tab) >= 0) {
                activeTab = data.tab;
            }
            if (data.planningView === 'week' || data.planningView === 'day') {
                planningView = data.planningView;
            }
            if (isIsoDate(data.selectedWeekDate)) {
                selectedWeekDate = data.selectedWeekDate;
            }
            if (isIsoDate(data.weekFrom)) {
                weekFrom = data.weekFrom;
            }
        } catch (e) {
            /* ignore */
        }
    }

    const $ = (sel) => document.querySelector(sel);
    const screenLogin = $('#screen-login');
    const screenHome = $('#screen-home');
    const panelToday = $('#panel-today');
    const panelWeek = $('#panel-week');
    const panelAbsences = $('#panel-absences');
    const homeError = $('#home-error');
    const loginError = $('#login-error');
    const announcementBanners = $('#announcement-banners');

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    let contractNoticeTimer = null;
    let contractConfirmResolve = null;

    function closeContractNotice() {
        const dialog = $('#contract-notice-dialog');
        if (contractNoticeTimer) {
            window.clearTimeout(contractNoticeTimer);
            contractNoticeTimer = null;
        }
        if (dialog) {
            dialog.hidden = true;
        }
    }

    function showContractNotice(message, options) {
        const opts = options || {};
        const dialog = $('#contract-notice-dialog');
        const titleEl = $('#contract-notice-title');
        const textEl = $('#contract-notice-text');
        const iconEl = $('#contract-notice-icon');
        const okBtn = $('#contract-notice-ok');
        if (!dialog || !message) {
            return;
        }
        const isError = opts.type === 'error';
        if (titleEl) {
            titleEl.textContent = opts.title || (isError ? 'Mislukt' : 'Gelukt');
        }
        if (textEl) {
            textEl.textContent = message;
        }
        if (iconEl) {
            iconEl.textContent = isError ? '!' : '✓';
            iconEl.classList.toggle('is-success', !isError);
            iconEl.classList.toggle('is-error', isError);
            iconEl.classList.remove('is-warn');
        }
        if (contractNoticeTimer) {
            window.clearTimeout(contractNoticeTimer);
            contractNoticeTimer = null;
        }
        dialog.hidden = false;
        if (okBtn) {
            okBtn.focus();
        }
        contractNoticeTimer = window.setTimeout(closeContractNotice, isError ? 7000 : 4500);
    }

    function closeContractConfirm(result) {
        const dialog = $('#contract-confirm-dialog');
        if (dialog) {
            dialog.hidden = true;
        }
        if (contractConfirmResolve) {
            const resolve = contractConfirmResolve;
            contractConfirmResolve = null;
            resolve(!!result);
        }
    }

    function showContractConfirm(message, options) {
        const opts = options || {};
        const dialog = $('#contract-confirm-dialog');
        const titleEl = $('#contract-confirm-title');
        const textEl = $('#contract-confirm-text');
        const iconEl = $('#contract-confirm-icon');
        const okBtn = $('#contract-confirm-ok');
        const cancelBtn = $('#contract-confirm-cancel');
        if (!dialog || !message) {
            return Promise.resolve(false);
        }
        closeContractNotice();
        if (contractConfirmResolve) {
            const prev = contractConfirmResolve;
            contractConfirmResolve = null;
            prev(false);
        }
        if (titleEl) {
            titleEl.textContent = opts.title || 'Weet je het zeker?';
        }
        if (textEl) {
            textEl.textContent = message;
        }
        if (iconEl) {
            iconEl.textContent = opts.icon || '?';
            iconEl.classList.toggle('is-error', !!opts.danger);
            iconEl.classList.toggle('is-warn', !opts.danger);
            iconEl.classList.remove('is-success');
        }
        if (okBtn) {
            okBtn.textContent = opts.confirmLabel || 'Bevestigen';
            okBtn.classList.toggle('btn-danger', !!opts.danger);
            okBtn.classList.toggle('btn-primary', !opts.danger);
        }
        if (cancelBtn) {
            cancelBtn.textContent = opts.cancelLabel || 'Annuleren';
        }
        return new Promise(function (resolve) {
            contractConfirmResolve = resolve;
            dialog.hidden = false;
            if (okBtn) {
                okBtn.focus();
            }
        });
    }

    function initContractDialogs() {
        const notice = $('#contract-notice-dialog');
        const confirmDlg = $('#contract-confirm-dialog');
        if (notice && notice.dataset.bound !== '1') {
            notice.dataset.bound = '1';
            const okBtn = $('#contract-notice-ok');
            if (okBtn) {
                okBtn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    closeContractNotice();
                });
            }
            notice.addEventListener('click', function (ev) {
                if (ev.target === notice) {
                    closeContractNotice();
                }
            });
            document.addEventListener('keydown', function (ev) {
                if (ev.key === 'Escape' && notice && !notice.hidden) {
                    closeContractNotice();
                }
            });
        }
        if (confirmDlg && confirmDlg.dataset.bound !== '1') {
            confirmDlg.dataset.bound = '1';
            const okBtn = $('#contract-confirm-ok');
            const cancelBtn = $('#contract-confirm-cancel');
            if (okBtn) {
                okBtn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    closeContractConfirm(true);
                });
            }
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    closeContractConfirm(false);
                });
            }
            confirmDlg.addEventListener('click', function (ev) {
                if (ev.target === confirmDlg) {
                    closeContractConfirm(false);
                }
            });
            document.addEventListener('keydown', function (ev) {
                if (ev.key === 'Escape' && confirmDlg && !confirmDlg.hidden) {
                    closeContractConfirm(false);
                }
            });
        }
    }

    function alert(message) {
        showContractNotice(String(message || 'Er ging iets mis.'), {
            type: 'error',
            title: 'Let op',
        });
    }

    function headers(json) {
        const h = {
            Accept: 'application/json',
            Authorization: token ? 'Bearer ' + token : '',
        };
        if (json) {
            h['Content-Type'] = 'application/json';
        }
        return h;
    }

    async function api(path, options) {
        const opts = options || {};
        const res = await fetch(cfg.apiBase + path, {
            method: opts.method || 'GET',
            headers: headers(opts.body !== undefined),
            body: opts.body !== undefined ? JSON.stringify(opts.body) : undefined,
            credentials: 'same-origin',
        });
        let data = null;
        try {
            data = await res.json();
        } catch (e) {
            data = null;
        }
        if (res.status === 401) {
            logout(true);
            throw new Error('Sessie verlopen.');
        }
        return { ok: res.ok, status: res.status, data: data };
    }

    function showScreen(name) {
        screenLogin.classList.toggle('is-active', name === 'login');
        screenHome.classList.toggle('is-active', name === 'home');
        requestAnimationFrame(syncThemeToggleTop);
    }

    function syncThemeToggleTop() {
        if (typeof window.nexaPwaSyncThemeToggleTop === 'function') {
            window.nexaPwaSyncThemeToggleTop();
            return;
        }
        let anchor = null;
        if (screenHome && screenHome.classList.contains('is-active')) {
            anchor = document.querySelector('.home-top__row') || $('#home-title');
        } else if (screenLogin && screenLogin.classList.contains('is-active')) {
            anchor = screenLogin.querySelector('h1');
        }
        if (!anchor) {
            document.documentElement.style.removeProperty('--nexa-pwa-theme-top');
            return;
        }
        const rect = anchor.getBoundingClientRect();
        const chrome = document.getElementById('nexa-pwa-chrome-actions');
        const chromeH = chrome ? chrome.getBoundingClientRect().height || 36 : 36;
        const top = Math.round(rect.top + (rect.height - chromeH) / 2);
        document.documentElement.style.setProperty(
            '--nexa-pwa-theme-top',
            Math.max(0, top) + 'px'
        );
    }

    function setProfileField(el, value) {
        if (!el) {
            return;
        }
        const text = value != null ? String(value).trim() : '';
        if (text !== '') {
            el.textContent = text;
            el.classList.remove('is-empty');
        } else {
            el.textContent = 'Niet beschikbaar';
            el.classList.add('is-empty');
        }
    }

    function renderProfileUser(u, opts) {
        const applyServerAccent = !opts || opts.applyServerAccent !== false;
        setProfileField($('#profile-name'), u && u.name);
        setProfileField($('#profile-email'), u && u.email);
        setProfileField($('#profile-phone'), u && u.phone);
        setProfileField($('#profile-role'), u && u.portal_role_label);
        setProfileField($('#profile-company'), u && u.company_name);
        if (!window.nexaPwaAccent) {
            return;
        }
        if (applyServerAccent && u && u.pwa_accent) {
            window.nexaPwaAccent.apply(u.pwa_accent);
        } else {
            window.nexaPwaAccent.apply(window.nexaPwaAccent.current());
        }
    }

    function persistAccent(accent) {
        if (user) {
            user.pwa_accent = accent;
        }
        if (!token) {
            return;
        }
        api('/accent', { method: 'PUT', body: { accent: accent } })
            .then(function (res) {
                if (res && res.ok && res.data && res.data.pwa_accent && user) {
                    user.pwa_accent = res.data.pwa_accent;
                }
            })
            .catch(function () {});
    }

    function bindAccentPicker() {
        document.querySelectorAll('[data-pwa-accent]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const accent = btn.getAttribute('data-pwa-accent');
                if (window.nexaPwaAccent) {
                    window.nexaPwaAccent.apply(accent);
                }
                persistAccent(accent);
            });
        });
    }

    function setTab(tab) {
        activeTab = tab;
        const isToday = tab === 'today';
        const isWeek = tab === 'week';
        const isAbsences = tab === 'absences';
        const isProfile = tab === 'profile';
        const isNavigation = tab === 'navigation';
        const screenHome = $('#screen-home');
        if (screenHome) {
            screenHome.classList.toggle('is-nav-tab', isNavigation);
        }
        const panelTodayWrap = $('#tab-panel-today');
        if (panelTodayWrap) {
            panelTodayWrap.hidden = !isToday;
        }
        panelToday.hidden = !isToday;
        if (panelWeek) {
            panelWeek.hidden = !isWeek;
        }
        panelAbsences.hidden = !isAbsences;
        const panelProfile = $('#panel-profile');
        if (panelProfile) {
            panelProfile.hidden = !isProfile;
        }
        const panelNav = $('#tab-panel-navigation');
        if (panelNav) {
            panelNav.hidden = !isNavigation;
        }
        document.querySelectorAll('.contract-bottom-nav__btn').forEach(function (btn) {
            const on = btn.getAttribute('data-main-tab') === tab;
            btn.classList.toggle('is-active', on);
            if (on) {
                btn.setAttribute('aria-current', 'page');
            } else {
                btn.removeAttribute('aria-current');
            }
        });
        const title = $('#home-title');
        if (title) {
            title.textContent = isWeek
                ? 'Planning'
                : isAbsences
                  ? 'Afmeldingen'
                  : isProfile
                    ? 'Profiel'
                    : isNavigation
                      ? 'Navigatie'
                      : 'Vandaag';
        }
        const planningToggle = $('#planning-view-toggle');
        if (planningToggle) {
            planningToggle.hidden = !isWeek;
        }
        if (isNavigation) {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    refreshNavigationPanel();
                });
            });
        }
        persistUiState();
    }

    function mondayIso(date) {
        const d = date
            ? new Date(typeof date === 'string' && date.length <= 10 ? date + 'T12:00:00' : date)
            : new Date();
        if (isNaN(d.getTime())) {
            return mondayIso(todayIso());
        }
        const day = d.getDay();
        const diff = day === 0 ? -6 : 1 - day;
        d.setDate(d.getDate() + diff);
        return toIsoDate(d);
    }

    function toIsoDate(d) {
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + m + '-' + day;
    }

    function addDaysIso(iso, days) {
        const d = new Date(iso + 'T12:00:00');
        d.setDate(d.getDate() + days);
        return toIsoDate(d);
    }

    function todayIso() {
        try {
            const parts = new Intl.DateTimeFormat('en-GB', {
                timeZone: 'Europe/Amsterdam',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            }).formatToParts(new Date());
            const get = function (type) {
                const part = parts.find(function (p) {
                    return p.type === type;
                });
                return part ? part.value : '';
            };
            return get('year') + '-' + get('month') + '-' + get('day');
        } catch (e) {
            return toIsoDate(new Date());
        }
    }

    function formatTime(iso) {
        if (!iso) {
            return '';
        }
        try {
            return new Date(iso).toLocaleTimeString('nl-NL', {
                hour: '2-digit',
                minute: '2-digit',
            });
        } catch (e) {
            return '';
        }
    }

    function formatDate(isoDate) {
        if (!isoDate) {
            return '';
        }
        try {
            return new Date(isoDate + 'T12:00:00').toLocaleDateString('nl-NL', {
                weekday: 'short',
                day: 'numeric',
                month: 'short',
            });
        } catch (e) {
            return isoDate;
        }
    }

    function formatPlanningDayTitle(isoDate) {
        const d = new Date((isoDate || '') + 'T12:00:00');
        if (isNaN(d.getTime())) {
            return isoDate || '';
        }
        return d.toLocaleDateString('nl-NL', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
        });
    }

    function formatPlanningRangeLabel(from, to) {
        const a = new Date(from + 'T12:00:00');
        const b = new Date(to + 'T12:00:00');
        if (isNaN(a.getTime()) || isNaN(b.getTime())) {
            return '';
        }
        const opts = { day: 'numeric', month: 'short' };
        return a.toLocaleDateString('nl-NL', opts) + ' – ' + b.toLocaleDateString('nl-NL', opts);
    }

    function shortAddress(value) {
        const text = String(value || '').trim();
        if (!text) {
            return '—';
        }
        const first = text.split(',')[0].trim();
        return first || text;
    }

    async function login(email, password) {
        const res = await fetch(cfg.loginUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email, password: password }),
            credentials: 'same-origin',
        });
        let data = null;
        try {
            data = await res.json();
        } catch (e) {
            data = null;
        }
        if (!res.ok) {
            const msg =
                (data && (data.message || (data.errors && data.errors.email && data.errors.email[0]))) ||
                'Inloggen mislukt.';
            const err = new Error(msg);
            err.code = data && data.error;
            throw err;
        }
        token = data.token;
        persistToken(token, data.expires_at);
        user = data.user;
        renderProfileUser(user);
        return data;
    }

    function getFirstLoginEmail() {
        const live = ($('#email') && $('#email').value.trim()) || '';
        if (live) {
            return live;
        }
        if (firstLoginEmail) {
            return firstLoginEmail;
        }
        const stash = $('#first-login-email');
        return (stash && stash.value.trim()) || '';
    }

    function rememberFirstLoginEmail(email) {
        firstLoginEmail = String(email || '').trim();
        const stash = $('#first-login-email');
        if (stash) {
            stash.value = firstLoginEmail;
        }
        const emailInput = $('#email');
        if (emailInput && firstLoginEmail) {
            emailInput.value = firstLoginEmail;
        }
    }

    function setFirstLoginMode(on) {
        const panel = $('#first-login-panel');
        const passwordBlock = $('#login-password-block');
        const loginBtn = $('#login-btn');
        const openWrap = $('#login-first-open-wrap');
        const passwordInput = $('#password');
        const remembered = on ? '' : getFirstLoginEmail();
        if (panel) {
            panel.hidden = !on;
        }
        if (passwordBlock) {
            passwordBlock.hidden = !!on;
        }
        if (loginBtn) {
            loginBtn.hidden = !!on;
        }
        if (openWrap) {
            openWrap.hidden = !!on;
        }
        if (passwordInput) {
            passwordInput.required = !on;
        }
        if (!on) {
            setFirstLoginCodeSent(false);
            const emailInput = $('#email');
            if (emailInput && remembered) {
                emailInput.value = remembered;
            }
            firstLoginEmail = '';
            const stash = $('#first-login-email');
            if (stash) {
                stash.value = '';
            }
        }
    }

    function setFirstLoginCodeSent(on) {
        const emailBlock = $('#login-email-block');
        const emailInput = $('#email');
        const sendBtn = $('#btn-send-login-code');
        const verify = $('#first-login-verify');
        const panel = $('#first-login-panel');
        if (emailBlock) {
            emailBlock.hidden = !!on;
        }
        if (emailInput) {
            emailInput.required = !on;
            if (on && firstLoginEmail) {
                emailInput.value = firstLoginEmail;
            }
        }
        if (sendBtn) {
            sendBtn.hidden = !!on;
        }
        if (verify) {
            verify.hidden = !on;
        }
        if (panel) {
            panel.classList.toggle('is-code-sent', !!on);
            const note = panel.querySelector('.login-first-note');
            if (note) {
                const sent = note.getAttribute('data-note-sent');
                const idle = note.getAttribute('data-note-idle');
                if (on && sent) {
                    note.textContent = sent;
                } else if (!on && idle) {
                    note.textContent = idle;
                }
            }
        }
    }

    async function requestLoginCode(email) {
        const res = await fetch(cfg.loginCodeRequestUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email }),
            credentials: 'same-origin',
        });
        const data = await res.json().catch(function () { return {}; });
        if (!res.ok) {
            throw new Error((data && data.message) || 'Code aanvragen mislukt.');
        }
        return data;
    }

    async function verifyLoginCode(email, code, password) {
        const res = await fetch(cfg.loginCodeVerifyUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email, code: code, password: password }),
            credentials: 'same-origin',
        });
        const data = await res.json().catch(function () { return {}; });
        if (!res.ok) {
            throw new Error((data && data.message) || 'Activeren mislukt.');
        }
        token = data.token;
        persistToken(token, data.expires_at);
        user = data.user;
        renderProfileUser(user);
        return data;
    }

    function logout(silent) {
        const hadToken = !!token;
        const oldToken = token;
        stopPoll();
        if (!silent && hadToken) {
            fetch(cfg.apiBase + '/logout', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    Authorization: 'Bearer ' + oldToken,
                },
                credentials: 'same-origin',
            }).catch(function () {});
        }
        clearPersistedAuth();
        user = null;
        activeTab = 'today';
        planningView = 'day';
        selectedWeekDate = null;
        weekFrom = null;
        renderProfileUser(null);
        showScreen('login');
    }

    function stopPoll() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function startPoll() {
        stopPoll();
        const ms = Math.max(5000, parseInt(cfg.pollMs, 10) || 15000);
        pollTimer = setInterval(function () {
            if (!token) {
                return;
            }
            if (activeTab === 'today' || activeTab === 'navigation') {
                loadToday(true);
            }
            loadAnnouncements(true);
        }, ms);
    }

    function statusPillsHtml(legOrItem) {
        const statusKey = legOrItem.status_key || 'none';
        const statusClass = 'status-' + escapeHtml(statusKey);
        const pills = [];
        if (statusKey === 'absent') {
            pills.push(
                '<span class="status-pill status-absent">' +
                    escapeHtml(legOrItem.status || 'Afwezig') +
                    '</span>'
            );
            return pills.join('');
        }
        if (statusKey === 'none') {
            pills.push(
                '<span class="status-pill status-none">' +
                    escapeHtml(legOrItem.status || 'Geen rit') +
                    '</span>'
            );
            return pills.join('');
        }
        if (statusKey === 'expired') {
            pills.push(
                '<span class="status-pill status-expired">' +
                    escapeHtml(legOrItem.status || 'Verlopen') +
                    '</span>'
            );
            return pills.join('');
        }
        if (statusKey !== 'picked_up' && statusKey !== 'completed') {
            pills.push(
                '<span class="status-pill ' +
                    statusClass +
                    '">' +
                    escapeHtml(legOrItem.status || '—') +
                    '</span>'
            );
        }
        pills.push(
            '<span class="status-pill ' +
                (legOrItem.picked_up ? 'status-picked_up' : 'status-none') +
                '">' +
                (legOrItem.picked_up ? 'Opgehaald' : 'Nog niet opgehaald') +
                '</span>'
        );
        pills.push(
            '<span class="status-pill ' +
                (legOrItem.destination_reached ? 'status-completed' : 'status-none') +
                '">' +
                (legOrItem.destination_reached
                    ? 'Bestemming bereikt'
                    : 'Bestemming nog niet bereikt') +
                '</span>'
        );
        return pills.join('');
    }

    let passengerCardExpanded = {};

    function passengerExpandKey(item, options) {
        const opts = options || {};
        const dateKey = opts.absenceDate || 'today';
        return String(item.passenger_id || item.name || '') + '|' + dateKey;
    }

    function passengerCollapsedSummary(item, options) {
        const opts = options || {};
        const legs = Array.isArray(item.legs) ? item.legs : [];
        if (opts.dayStatus === 'absent') {
            return 'Afgemeld';
        }
        if (opts.dayStatus === 'exception') {
            return 'Geen vervoer';
        }
        if (legs.length === 0) {
            return opts.dayStatusLabel || item.status || 'Geen rit';
        }
        return legs
            .map(function (leg) {
                const label = leg.leg_label || '';
                const time = formatTime(leg.planned_at);
                const parts = [];
                if (label) {
                    parts.push(label);
                }
                if (time) {
                    parts.push(time);
                }
                return parts.join(' ');
            })
            .filter(Boolean)
            .join(' · ');
    }

    function splitAddressLines(address) {
        const text = address != null ? String(address).trim() : '';
        if (!text) {
            return { main: '', sub: '' };
        }
        const comma = text.indexOf(',');
        if (comma > 0 && comma < text.length - 1) {
            return {
                main: text.slice(0, comma).trim(),
                sub: text.slice(comma + 1).trim(),
            };
        }
        return { main: text, sub: '' };
    }

    function routeAddressInnerHtml(address) {
        const parts = splitAddressLines(address);
        let html =
            '<span class="offer-route-main">' + escapeHtml(parts.main || '—') + '</span>';
        if (parts.sub) {
            html += '<span class="offer-route-sub">' + escapeHtml(parts.sub) + '</span>';
        }
        return html;
    }

    function routeStopHtml(label, address, variant, timeLabel) {
        const time = timeLabel ? String(timeLabel).trim() : '';
        return (
            '<div class="offer-route-stop">' +
            '<div class="offer-route-head">' +
            '<span class="offer-route-dot offer-route-dot--' +
            escapeHtml(variant) +
            '" aria-hidden="true"></span>' +
            '<p class="offer-route-label">' +
            escapeHtml(label) +
            '</p>' +
            (time ? '<span class="offer-route-label-time">' + escapeHtml(time) + '</span>' : '') +
            '</div>' +
            '<div class="offer-route-body">' +
            routeAddressInnerHtml(address) +
            '</div>' +
            '</div>'
        );
    }

    function routeTimelineHtml(pickupAddress, dropoffAddress, pickupTime, dropoffTime) {
        return (
            '<div class="offer-route">' +
            routeStopHtml('Ophalen', pickupAddress, 'pickup', pickupTime) +
            routeStopHtml('Afzetten', dropoffAddress || '—', 'dropoff', dropoffTime) +
            '</div>'
        );
    }

    function passengerCardHtml(item, options) {
        const opts = options || {};
        const legs = Array.isArray(item.legs) ? item.legs : [];
        const showLegLabels = legs.length > 1;
        const expandKey = passengerExpandKey(item, opts);
        const expanded = !!passengerCardExpanded[expandKey];
        const bodyId = 'passenger-card-body-' + expandKey.replace(/[^a-zA-Z0-9_-]/g, '-');
        let actions = '';
        if (item.can_cancel) {
            actions =
                '<div class="card-actions">' +
                '<button type="button" class="btn btn-danger btn-sm" data-absent="' +
                item.passenger_id +
                '" data-name="' +
                escapeHtml(item.name) +
                '"' +
                (opts.absenceDate ? ' data-date="' + escapeHtml(opts.absenceDate) + '"' : '') +
                '>Afmelden</button>' +
                '</div>';
        } else if (item.absence_id) {
            actions =
                '<div class="card-actions">' +
                '<button type="button" class="btn btn-ghost btn-sm" data-revoke="' +
                item.absence_id +
                '">Afmelding intrekken</button>' +
                '</div>';
        }

        let body = '';
        if (opts.dayStatus === 'absent' || opts.dayStatus === 'exception') {
            body =
                '<div class="status-pills"><span class="status-pill status-' +
                (opts.dayStatus === 'absent' ? 'absent' : 'none') +
                '">' +
                escapeHtml(opts.dayStatusLabel || opts.dayStatus) +
                '</span></div>';
        } else if (opts.dayStatus && opts.dayStatus !== 'scheduled' && legs.length === 0) {
            body =
                '<div class="status-pills"><span class="status-pill status-' +
                (opts.dayStatus === 'absent' ? 'absent' : 'none') +
                '">' +
                escapeHtml(opts.dayStatusLabel || opts.dayStatus) +
                '</span></div>';
        } else if (legs.length === 0) {
            body =
                routeTimelineHtml(item.pickup_address || 'Geen ophaaladres', item.destination_address || '') +
                '<div class="status-pills">' +
                statusPillsHtml(item) +
                '</div>';
        } else {
            body = legs
                .map(function (leg) {
                    return (
                        '<div class="leg-block">' +
                        (showLegLabels || leg.leg_label
                            ? '<p class="leg-label">' + escapeHtml(leg.leg_label || '') + '</p>'
                            : '') +
                        routeTimelineHtml(
                            leg.pickup_address || item.pickup_address || 'Geen ophaaladres',
                            leg.destination_address || '',
                            formatTime(leg.planned_at),
                            formatTime(leg.destination_at)
                        ) +
                        '<div class="status-pills">' +
                        statusPillsHtml(leg) +
                        '</div>' +
                        '</div>'
                    );
                })
                .join('');
        }

        const summary = passengerCollapsedSummary(item, opts);

        return (
            '<div class="card passenger-card' +
            (expanded ? ' is-expanded' : '') +
            '" data-expand-key="' +
            escapeHtml(expandKey) +
            '">' +
            '<button type="button" class="passenger-card-toggle" aria-expanded="' +
            (expanded ? 'true' : 'false') +
            '" aria-controls="' +
            escapeHtml(bodyId) +
            '" data-expand-key="' +
            escapeHtml(expandKey) +
            '">' +
            '<span class="passenger-card-toggle-text">' +
            '<span class="passenger-name">' +
            escapeHtml(item.name) +
            '</span>' +
            (summary
                ? '<span class="passenger-card-summary">' + escapeHtml(summary) + '</span>'
                : '') +
            '</span>' +
            '<span class="passenger-card-chevron" aria-hidden="true">▼</span>' +
            '</button>' +
            '<div class="passenger-card-body" id="' +
            escapeHtml(bodyId) +
            '"' +
            (expanded ? '' : ' hidden') +
            '>' +
            body +
            (item.absence_reason
                ? '<p class="muted" style="margin:0.5rem 0 0;">' +
                  escapeHtml(item.absence_reason) +
                  '</p>'
                : '') +
            '</div>' +
            actions +
            '</div>'
        );
    }

    function renderToday(payload) {
        lastTodayPayload = payload || {};
        const items = (payload && payload.items) || [];
        const destinationEl = $('#home-destination');
        $('#home-subtitle').textContent =
            (user && user.portal_role_label ? user.portal_role_label + ' · ' : '') +
            (payload.customer_name || '') +
            (payload.date ? ' · ' + formatDate(payload.date) : '');

        if (destinationEl) {
            const destinationSummary = payload.destination_summary || '';
            if (destinationSummary && activeTab === 'today') {
                destinationEl.hidden = false;
                destinationEl.textContent = 'Bestemming: ' + destinationSummary;
            } else if (activeTab !== 'today') {
                /* keep as-is from tab switch */
            } else {
                destinationEl.hidden = true;
                destinationEl.textContent = '';
            }
        }

        if (!items.length) {
            panelToday.innerHTML = '<div class="empty">Geen leerlingen gekoppeld.</div>';
            if (activeTab === 'navigation') {
                refreshNavigationPanel();
            }
            return;
        }

        panelToday.innerHTML = items.map(function (item) {
            return passengerCardHtml(item);
        }).join('');
        if (activeTab === 'navigation') {
            refreshNavigationPanel();
        }
    }

    function planningCarIconHtml() {
        return (
            '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
            '<path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 11h14M6 11l1.2-3.6A1.5 1.5 0 0 1 8.6 6h6.8a1.5 1.5 0 0 1 1.4 1.04L18 11M6 11v5a1 1 0 0 0 1 1h1M16 17h1a1 1 0 0 0 1-1v-5"/>' +
            '<circle cx="8" cy="17" r="1.3" stroke="currentColor" stroke-width="2"/>' +
            '<circle cx="16" cy="17" r="1.3" stroke="currentColor" stroke-width="2"/>' +
            '</svg>'
        );
    }

    function planningDayRideCount(day) {
        if (!day) {
            return 0;
        }
        return (day.items || []).reduce(function (sum, item) {
            return sum + planningItemRideCount(item);
        }, 0);
    }

    function planningLegIsAbsent(leg) {
        return !!(leg && (leg.status_key === 'absent' || leg.day_status === 'absent'));
    }

    function planningItemIsFullyAbsent(item) {
        if (!item) {
            return false;
        }
        if (item.day_status === 'absent' || item.status_key === 'absent') {
            return true;
        }
        const legs = Array.isArray(item.legs) ? item.legs : [];
        return legs.length > 0 && legs.every(planningLegIsAbsent);
    }

    function planningItemRideCount(item) {
        if (!item) {
            return 0;
        }
        if (item.day_status === 'absent' || item.day_status === 'exception' || item.day_status === 'none') {
            return 0;
        }
        if (item.status_key === 'absent' || planningItemIsFullyAbsent(item)) {
            return 0;
        }
        const legs = Array.isArray(item.legs) ? item.legs : [];
        return legs.filter(function (leg) {
            return !planningLegIsAbsent(leg);
        }).length;
    }

    function planningRideCountLabel(count) {
        return Number(count) === 1 ? '1 rit' : Number(count || 0) + ' ritten';
    }

    function planningRideIsExpired(item) {
        if (!item) {
            return false;
        }
        if (item.day_status === 'absent' || item.day_status === 'exception' || item.day_status === 'none') {
            return false;
        }
        if (item.picked_up || item.destination_reached) {
            return false;
        }
        const legs = Array.isArray(item.legs) ? item.legs : [];
        if (legs.some(function (leg) {
            return leg.picked_up || leg.destination_reached;
        })) {
            return false;
        }
        const liveKeys = ['picked_up', 'completed', 'arrived', 'en_route', 'absent'];
        if (liveKeys.indexOf(item.status_key) >= 0) {
            return false;
        }
        if (item.day_status === 'expired' || item.status_key === 'expired') {
            return true;
        }
        if (legs.some(function (leg) {
            return leg.status_key === 'expired';
        })) {
            return true;
        }
        const first = legs[0] || {};
        const at = first.planned_at || item.planned_at;
        if (!at) {
            return false;
        }
        const ms = Date.parse(at);
        return !isNaN(ms) && ms < Date.now();
    }

    function planningRideStatusLabel(item) {
        if (!item) {
            return 'Gepland';
        }
        if (item.day_status === 'absent' || item.day_status === 'exception' || item.day_status === 'none') {
            return item.day_status_label || item.status || 'Geen rit';
        }
        if (planningRideIsExpired(item)) {
            return 'Verlopen';
        }
        const legs = Array.isArray(item.legs) ? item.legs : [];
        const first = legs[0] || {};
        return first.status || item.status || item.day_status_label || 'Gepland';
    }

    function planningCardStatusClass(item) {
        const status = item && item.day_status ? String(item.day_status) : '';
        if (status === 'absent' || planningItemIsFullyAbsent(item)) {
            return ' is-absent';
        }
        if (status === 'exception' || status === 'none') {
            return ' is-completed';
        }
        if (item && item.destination_reached) {
            return ' is-completed';
        }
        if (item && item.picked_up) {
            return ' is-assigned';
        }
        const legs = (item && item.legs) || [];
        if (legs.length && legs.every(function (leg) { return leg.destination_reached; })) {
            return ' is-completed';
        }
        if (legs.some(function (leg) { return leg.picked_up; })) {
            return ' is-assigned';
        }
        if (planningRideIsExpired(item)) {
            return ' is-expired';
        }
        return ' is-accepted';
    }

    function planningNavHtml(selected) {
        const isWeek = planningView === 'week';
        const todayKey = todayIso();
        const isToday = !!(selected && selected.date === todayKey);
        const prevLabel = isWeek ? 'Vorige week' : 'Vorige dag';
        const nextLabel = isWeek ? 'Volgende week' : 'Volgende dag';
        return (
            '<div class="planning-week-nav' +
            (isWeek ? ' is-week' : ' is-day') +
            '">' +
            '<button type="button" class="planning-week-nav__btn" id="planning-week-prev" aria-label="' +
            prevLabel +
            '">←</button>' +
            '<button type="button" class="planning-week-nav__today" id="planning-week-today"' +
            (isToday ? ' disabled' : '') +
            '>Vandaag</button>' +
            '<div class="planning-week-nav__spacer"></div>' +
            '<button type="button" class="planning-week-nav__btn" id="planning-week-next" aria-label="' +
            nextLabel +
            '">→</button>' +
            '</div>'
        );
    }

    function planningHeadingHtml(selected) {
        const isWeek = planningView === 'week';
        const count = planningDayRideCount(selected);
        const weekTotal = ((weekPayload && weekPayload.days) || []).reduce(function (sum, day) {
            return sum + planningDayRideCount(day);
        }, 0);
        const label = isWeek
            ? escapeHtml(formatPlanningRangeLabel(weekPayload.from, weekPayload.to))
            : escapeHtml(formatPlanningDayTitle(selected && selected.date));
        const shownCount = isWeek ? weekTotal : count;
        return (
            '<div class="planning-heading' +
            (isWeek ? ' is-week' : ' is-day') +
            '">' +
            '<div class="planning-week-label">' +
            label +
            '</div>' +
            '<div class="planning-week-nav__count">' +
            planningCarIconHtml() +
            '<span>' +
            shownCount +
            '</span></div>' +
            '</div>'
        );
    }

    function planningPassengerCardHtml(item, date) {
        if (!item) {
            return '';
        }
        const expandKey = passengerExpandKey(item, { absenceDate: date });
        const expanded = !!passengerCardExpanded[expandKey];
        const legs = Array.isArray(item.legs) ? item.legs : [];
        const first = legs[0] || {};
        const time = formatTime(first.planned_at || item.planned_at);
        const from = shortAddress(first.pickup_address || item.pickup_address);
        const to = shortAddress(first.destination_address || item.destination_address);
        const statusLabel = planningRideStatusLabel(item);
        const full = expanded
            ? passengerCardHtml(item, {
                  dayStatus: item.day_status,
                  dayStatusLabel: item.day_status_label,
                  absenceDate: date,
              })
            : '';
        const detailStart = full.indexOf('<div class="passenger-card-body"');
        let detailHtml = detailStart >= 0 ? full.slice(detailStart) : '';
        if (detailHtml.slice(-6) === '</div>') {
            detailHtml = detailHtml.slice(0, -6);
        }
        return (
            '<div class="planning-ride-card' +
            planningCardStatusClass(item) +
            (expanded ? ' is-expanded' : '') +
            '" data-expand-key="' +
            escapeHtml(expandKey) +
            '">' +
            '<button type="button" class="planning-ride-card__hit" data-planning-open="' +
            escapeHtml(String(item.passenger_id || '')) +
            '" data-planning-open-date="' +
            escapeHtml(date || '') +
            '" aria-expanded="' +
            (expanded ? 'true' : 'false') +
            '">' +
            '<div class="planning-ride-card__top">' +
            '<span class="planning-ride-card__time">' +
            escapeHtml(time || '—') +
            '</span>' +
            '<span class="planning-ride-card__status">' +
            escapeHtml(statusLabel) +
            '</span>' +
            '</div>' +
            '<p class="planning-ride-card__route">' +
            escapeHtml(from) +
            ' <span class="planning-ride-card__arrow">→</span> ' +
            escapeHtml(to) +
            '</p>' +
            '<p class="planning-ride-card__meta">' +
            escapeHtml(item.name || '') +
            (passengerCollapsedSummary(item, { dayStatus: item.day_status, dayStatusLabel: item.day_status_label })
                ? ' · ' +
                  escapeHtml(
                      passengerCollapsedSummary(item, {
                          dayStatus: item.day_status,
                          dayStatusLabel: item.day_status_label,
                      })
                  )
                : '') +
            '</p>' +
            '</button>' +
            (expanded
                ? '<div class="planning-ride-card__detail">' + detailHtml + '</div>'
                : '') +
            '</div>'
        );
    }

    function planningDayCardsHtml(day) {
        const items = ((day && day.items) || []).filter(function (item) {
            return item.day_status !== 'none';
        });
        if (!items.length) {
            return '<p class="planning-empty">Geen ritten op deze dag.</p>';
        }
        return items
            .map(function (item) {
                return planningPassengerCardHtml(item, day.date);
            })
            .join('');
    }

    function setPlanningView(view, options) {
        planningView = view === 'week' ? 'week' : 'day';
        document.querySelectorAll('[data-planning-view]').forEach(function (btn) {
            const active = btn.getAttribute('data-planning-view') === planningView;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        persistUiState();
        if (options && options.skipRender) {
            return;
        }
        renderWeek(weekPayload);
    }

    function shiftPlanningDay(deltaDays) {
        const current = selectedWeekDate || todayIso();
        selectedWeekDate = addDaysIso(current, deltaDays);
        const monday = mondayIso(selectedWeekDate);
        if (monday !== weekFrom) {
            weekFrom = monday;
            loadWeek();
            return;
        }
        renderWeek(weekPayload);
    }

    function revealPassengerCard(expandKey) {
        const key = String(expandKey || '');
        if (!key) {
            return;
        }
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                const selector = '[data-expand-key="' + key.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"]';
                const card = document.querySelector(selector);
                if (card && typeof card.scrollIntoView === 'function') {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        });
    }

    function togglePlanningPassenger(expandKey) {
        const key = String(expandKey || '');
        if (!key) {
            return;
        }
        const willExpand = !passengerCardExpanded[key];
        passengerCardExpanded[key] = willExpand;
        renderWeek(weekPayload);
        if (willExpand) {
            revealPassengerCard(key);
        }
    }

    function renderWeek(payload) {
        weekPayload = payload || {};
        const days = weekPayload.days || [];
        if (!days.length) {
            panelWeek.innerHTML = '<p class="planning-empty">Geen planningsdata.</p>';
            return;
        }
        if (
            !selectedWeekDate ||
            !days.some(function (d) {
                return d.date === selectedWeekDate;
            })
        ) {
            const todayDay = days.find(function (d) {
                return d.is_today;
            });
            selectedWeekDate = (todayDay && todayDay.date) || days[0].date;
        }
        persistUiState();
        const selected =
            days.find(function (d) {
                return d.date === selectedWeekDate;
            }) || days[0];

        let html = planningNavHtml(selected);

        if (planningView === 'week') {
            html += planningHeadingHtml(selected);
            html += '<div class="planning-week-days">';
            days.forEach(function (day) {
                const d = new Date(day.date + 'T12:00:00');
                const name = isNaN(d.getTime())
                    ? ''
                    : d.toLocaleDateString('nl-NL', { weekday: 'short' });
                const count = planningDayRideCount(day);
                html +=
                    '<button type="button" class="planning-week-day' +
                    (day.date === selectedWeekDate ? ' is-active' : '') +
                    (day.is_today ? ' is-today' : '') +
                    (count > 0 ? ' has-rides' : '') +
                    '" data-planning-date="' +
                    escapeHtml(day.date) +
                    '"><span class="wd-name">' +
                    escapeHtml(name) +
                    '</span><span class="wd-num">' +
                    (isNaN(d.getTime()) ? '' : d.getDate()) +
                    '</span><span class="wd-rides">' +
                    planningCarIconHtml() +
                    '<span>' +
                    count +
                    '</span></span></button>';
            });
            html += '</div>';
            const count = planningDayRideCount(selected);
            html +=
                '<section class="planning-day-section" id="planning-day-' +
                escapeHtml(selected.date) +
                '">' +
                '<h3 class="planning-day-section__title">' +
                '<span>' +
                escapeHtml(formatPlanningDayTitle(selected.date)) +
                '</span> ' +
                '<span class="planning-day-section__count">' +
                escapeHtml(planningRideCountLabel(count)) +
                '</span>' +
                '</h3>' +
                planningDayCardsHtml(selected) +
                '</section>';
        } else {
            html +=
                '<div class="planning-day-stack">' +
                planningHeadingHtml(selected) +
                planningDayCardsHtml(selected) +
                '</div>';
        }

        panelWeek.innerHTML = html;
    }

    function dismissedAnnouncementIds() {
        try {
            const raw = sessionStorage.getItem(ANNOUNCEMENT_DISMISS_KEY);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed.map(String) : [];
        } catch (e) {
            return [];
        }
    }

    function dismissAnnouncement(id) {
        const ids = dismissedAnnouncementIds();
        const sid = String(id);
        if (ids.indexOf(sid) === -1) {
            ids.push(sid);
            sessionStorage.setItem(ANNOUNCEMENT_DISMISS_KEY, JSON.stringify(ids));
        }
        loadAnnouncements(true);
    }

    function renderAnnouncements(payload) {
        if (!announcementBanners) {
            return;
        }
        const dismissed = dismissedAnnouncementIds();
        const rows = ((payload && payload.announcements) || []).filter(function (row) {
            if (dismissed.indexOf(String(row.id)) === -1) {
                return true;
            }
            return row.severity === 'critical';
        });
        if (!rows.length) {
            announcementBanners.hidden = true;
            announcementBanners.innerHTML = '';
            return;
        }
        announcementBanners.hidden = false;
        announcementBanners.innerHTML = rows
            .map(function (row) {
                const severity = escapeHtml(row.severity || 'info');
                const canDismiss = row.severity !== 'critical';
                return (
                    '<div class="banner-announcement severity-' +
                    severity +
                    '" role="status">' +
                    (canDismiss
                        ? '<button type="button" class="banner-dismiss" data-dismiss-announcement="' +
                          row.id +
                          '" aria-label="Sluiten">×</button>'
                        : '') +
                    '<p class="announcement-title">' +
                    escapeHtml(row.title || 'Melding') +
                    '</p>' +
                    (row.body
                        ? '<p class="announcement-body">' + escapeHtml(row.body) + '</p>'
                        : '') +
                    '</div>'
                );
            })
            .join('');
    }

    function renderAbsences(payload) {
        const rows = (payload && payload.absences) || [];
        if (!rows.length) {
            panelAbsences.innerHTML =
                '<div class="empty">Geen openstaande afmeldingen.<br><span class="empty__sub">(tot 14 dagen vooruit)</span></div>';
            return;
        }
        panelAbsences.innerHTML = rows
            .map(function (row) {
                return (
                    '<div class="card">' +
                    '<p class="passenger-name">' +
                    escapeHtml(row.passenger_name) +
                    '</p>' +
                    '<p class="muted" style="margin:0;">' +
                    escapeHtml(formatDate(row.date)) +
                    (row.reason ? ' · ' + escapeHtml(row.reason) : '') +
                    '</p>' +
                    '<div class="card-actions">' +
                    '<button type="button" class="btn btn-ghost btn-sm" data-revoke="' +
                    row.id +
                    '">Intrekken</button>' +
                    '</div>' +
                    '</div>'
                );
            })
            .join('');
    }

    function setNavigationStatus(text) {
        const el = $('#navigation-status');
        if (el) {
            el.textContent = text || '';
        }
    }

    function syncNavigationButton(hasRoute) {
        const btn = $('#btn-start-navigation');
        if (!btn) {
            return;
        }
        btn.disabled = !hasRoute;
        btn.textContent = 'Start navigatie';
    }

    function hasLatLng(point) {
        const lat = Number(point && point.lat);
        const lng = Number(point && point.lng);
        return Number.isFinite(lat) && Number.isFinite(lng) && !(lat === 0 && lng === 0);
    }

    function asLatLng(point) {
        if (!hasLatLng(point)) {
            return null;
        }
        return { lat: Number(point.lat), lng: Number(point.lng) };
    }

    function stopLocation(stop) {
        return asLatLng(stop) || (stop && String(stop.address || '').trim()) || null;
    }

    function stopPointKey(stop) {
        const coords = asLatLng(stop);
        if (coords) {
            return coords.lat.toFixed(5) + ',' + coords.lng.toFixed(5);
        }
        return String((stop && stop.address) || '')
            .trim()
            .toLowerCase();
    }

    function navigationRoutePoints(origin, stops) {
        const points = [];
        if (hasLatLng(origin)) {
            points.push({ lat: Number(origin.lat), lng: Number(origin.lng) });
        }
        (stops || []).forEach(function (stop) {
            if (!stop) {
                return;
            }
            points.push(stop);
        });
        const unique = [];
        points.forEach(function (point) {
            const key = stopPointKey(point);
            if (!key) {
                return;
            }
            const prev = unique.length ? stopPointKey(unique[unique.length - 1]) : '';
            if (key !== prev) {
                unique.push(point);
            }
        });
        return unique;
    }

    function navigationDirUrl(origin, stops) {
        const points = navigationRoutePoints(origin, stops);
        if (!points.length) {
            return '';
        }
        const path = points
            .map(function (point) {
                const coords = asLatLng(point);
                if (coords) {
                    return coords.lat + ',' + coords.lng;
                }
                return String(point.address || '').trim();
            })
            .filter(Boolean);
        if (!path.length) {
            return '';
        }
        if (path.length === 1) {
            return (
                'https://www.google.com/maps/dir/?api=1&travelmode=driving&dir_action=navigate&destination=' +
                encodeURIComponent(path[0])
            );
        }
        return 'https://www.google.com/maps/dir/' + path.map(encodeURIComponent).join('/');
    }

    function navigationStopUrl(origin, stop) {
        if (!stop) {
            return '';
        }
        return navigationDirUrl(origin, [stop]);
    }

    function getCurrentPosition() {
        return new Promise(function (resolve) {
            if (!navigator.geolocation) {
                resolve(null);
                return;
            }
            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude });
                },
                function () {
                    resolve(null);
                },
                { enableHighAccuracy: true, timeout: 8000, maximumAge: 15000 }
            );
        });
    }

    function loadGoogleMapsSdk() {
        if (window.google && window.google.maps && window.google.maps.Map) {
            return Promise.resolve();
        }
        if (googleMapsLoadPromise) {
            return googleMapsLoadPromise;
        }
        const key = cfg.googleMapsApiKey ? String(cfg.googleMapsApiKey).trim() : '';
        if (!key) {
            return Promise.reject(new Error('no-key'));
        }
        googleMapsLoadPromise = new Promise(function (resolve, reject) {
            const existing = document.getElementById('nexa-contract-google-maps-sdk');
            if (existing) {
                existing.addEventListener('load', function () {
                    resolve();
                });
                existing.addEventListener('error', function () {
                    googleMapsLoadPromise = null;
                    reject(new Error('maps-load'));
                });
                return;
            }
            window.__nexaContractGoogleMapsReady = function () {
                resolve();
            };
            const script = document.createElement('script');
            script.id = 'nexa-contract-google-maps-sdk';
            script.async = true;
            script.defer = true;
            script.src =
                'https://maps.googleapis.com/maps/api/js?key=' +
                encodeURIComponent(key) +
                '&callback=__nexaContractGoogleMapsReady';
            script.onerror = function () {
                googleMapsLoadPromise = null;
                reject(new Error('maps-load'));
            };
            document.head.appendChild(script);
        });
        return googleMapsLoadPromise;
    }

    function ensureNavigationMap() {
        const el = $('#navigation-map');
        if (!el || !window.google || !window.google.maps) {
            return null;
        }
        const center = {
            lat: Number(cfg.googleMapsCenterLat) || 52.3676,
            lng: Number(cfg.googleMapsCenterLng) || 4.9041,
        };
        const mapOpts = {
            center: center,
            zoom: 11,
            disableDefaultUI: true,
            zoomControl: true,
            gestureHandling: 'greedy',
            backgroundColor: '#1a1a1c',
        };
        const mapId = cfg.googleMapsMapId ? String(cfg.googleMapsMapId).trim() : '';
        if (mapId) {
            mapOpts.mapId = mapId;
        } else {
            mapOpts.styles = [
                { elementType: 'geometry', stylers: [{ color: '#1c1c1e' }] },
                { elementType: 'labels.text.stroke', stylers: [{ color: '#1c1c1e' }] },
                { elementType: 'labels.text.fill', stylers: [{ color: '#9ca3af' }] },
                { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#2a2a2e' }] },
                { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#111113' }] },
                { featureType: 'poi', stylers: [{ visibility: 'off' }] },
            ];
        }
        if (!navigationMap) {
            navigationMap = new google.maps.Map(el, mapOpts);
        }
        if (!navigationRenderer) {
            navigationRenderer = new google.maps.DirectionsRenderer({
                suppressMarkers: false,
                polylineOptions: { strokeColor: '#f97316', strokeWeight: 5, strokeOpacity: 0.95 },
            });
        }
        return navigationMap;
    }

    function clearNavigationOverlays() {
        navigationMarkers.forEach(function (marker) {
            marker.setMap(null);
        });
        navigationMarkers = [];
        if (navigationPolyline) {
            navigationPolyline.setMap(null);
            navigationPolyline = null;
        }
        if (navigationRenderer) {
            navigationRenderer.setMap(null);
        }
    }

    function geocodeAddress(address) {
        return new Promise(function (resolve) {
            if (!address || !window.google || !google.maps.Geocoder) {
                resolve(null);
                return;
            }
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ address: address, region: 'nl' }, function (results, status) {
                if (status === 'OK' && results && results[0] && results[0].geometry) {
                    const loc = results[0].geometry.location;
                    resolve({ lat: loc.lat(), lng: loc.lng() });
                    return;
                }
                resolve(null);
            });
        });
    }

    async function resolveStopPosition(stop) {
        const coords = asLatLng(stop);
        if (coords) {
            return coords;
        }
        return geocodeAddress(stop && stop.address);
    }

    function addNavigationMarker(position, opts) {
        const marker = new google.maps.Marker({
            position: position,
            map: navigationMap,
            title: opts.title || '',
            label: opts.label || undefined,
            icon: opts.icon || undefined,
            zIndex: opts.zIndex || 1,
        });
        navigationMarkers.push(marker);
        return marker;
    }

    function resizeNavigationMap() {
        if (!navigationMap || !window.google || !google.maps || !google.maps.event) {
            return;
        }
        google.maps.event.trigger(navigationMap, 'resize');
        if (navigationRenderer) {
            const result = navigationRenderer.getDirections && navigationRenderer.getDirections();
            const bounds = result && result.routes && result.routes[0] && result.routes[0].bounds;
            if (bounds) {
                navigationMap.fitBounds(bounds);
                return;
            }
        }
        if (navigationMarkers.length > 1) {
            const bounds = new google.maps.LatLngBounds();
            navigationMarkers.forEach(function (marker) {
                const pos = marker.getPosition && marker.getPosition();
                if (pos) {
                    bounds.extend(pos);
                }
            });
            if (!bounds.isEmpty()) {
                navigationMap.fitBounds(bounds, 48);
            }
        }
    }

    async function fetchRoadPath(points) {
        if (!points || points.length < 2) {
            return null;
        }
        const coords = points
            .map(function (point) {
                return Number(point.lng) + ',' + Number(point.lat);
            })
            .join(';');
        try {
            const res = await fetch(
                'https://router.project-osrm.org/route/v1/driving/' +
                    coords +
                    '?overview=full&geometries=geojson'
            );
            if (!res.ok) {
                return null;
            }
            const data = await res.json();
            const geometry = data && data.routes && data.routes[0] && data.routes[0].geometry;
            if (!geometry || geometry.type !== 'LineString' || !Array.isArray(geometry.coordinates)) {
                return null;
            }
            return geometry.coordinates.map(function (pair) {
                return { lat: pair[1], lng: pair[0] };
            });
        } catch (e) {
            return null;
        }
    }

    async function drawNavigationMarkers(stops) {
        ensureNavigationMap();
        if (!navigationMap) {
            return false;
        }
        clearNavigationOverlays();
        const path = [];
        const bounds = new google.maps.LatLngBounds();
        for (let i = 0; i < stops.length; i++) {
            const pos = await resolveStopPosition(stops[i]);
            if (!pos) {
                continue;
            }
            path.push(pos);
            bounds.extend(pos);
            addNavigationMarker(pos, {
                title: stops[i].name || stops[i].label || stops[i].address || '',
                zIndex: 10 + i,
                label: {
                    text: String(i + 1),
                    color: '#ffffff',
                    fontWeight: '700',
                    fontSize: '11px',
                },
            });
        }
        if (!path.length) {
            return false;
        }
        if (path.length > 1) {
            const road = await fetchRoadPath(path);
            navigationPolyline = new google.maps.Polyline({
                map: navigationMap,
                path: road && road.length > 1 ? road : path,
                geodesic: !road,
                strokeColor: '#f97316',
                strokeOpacity: 0.95,
                strokeWeight: 5,
            });
        }
        if (path.length === 1) {
            navigationMap.setCenter(path[0]);
            navigationMap.setZoom(14);
        } else {
            navigationMap.fitBounds(bounds, 48);
        }
        return true;
    }

    function drawNavigationDirections(stops) {
        if (!navigationRenderer || !stops.length) {
            return Promise.resolve(false);
        }
        const origin = stopLocation(stops[0]);
        const dest = stopLocation(stops[stops.length - 1]);
        if (!origin || !dest) {
            return Promise.resolve(false);
        }
        const via = stops.slice(1, -1).slice(0, 25);
        const request = {
            origin: origin,
            destination: dest,
            travelMode: google.maps.TravelMode.DRIVING,
            optimizeWaypoints: false,
        };
        if (via.length) {
            request.waypoints = via.map(function (stop) {
                return { location: stopLocation(stop), stopover: true };
            }).filter(function (wp) {
                return wp.location;
            });
        }
        return new Promise(function (resolve) {
            const service = new google.maps.DirectionsService();
            service.route(request, function (result, status) {
                if (status === google.maps.DirectionsStatus.OK && result) {
                    clearNavigationOverlays();
                    navigationRenderer.setMap(navigationMap);
                    navigationRenderer.setDirections(result);
                    resolve(true);
                    return;
                }
                resolve(false);
            });
        });
    }

    async function drawNavigationRoute(stops) {
        if (!stops.length || !window.google || !window.google.maps) {
            return false;
        }
        ensureNavigationMap();
        const viaRoad = await drawNavigationDirections(stops);
        if (viaRoad) {
            return true;
        }
        return drawNavigationMarkers(stops);
    }

    function navigationStopKind(stop) {
        return stop && stop.kind === 'dropoff' ? 'Afzetten' : 'Ophalen';
    }

    function navigationStopBodyHtml(stop) {
        const name = stop && stop.name ? String(stop.name).trim() : '';
        const kind = navigationStopKind(stop);
        const time = stop && stop.planned_at ? formatTime(stop.planned_at) : '';
        const addr =
            escapeHtml(shortAddress(stop && stop.address)) + (time ? ' · ' + escapeHtml(time) : '');
        if (name) {
            return (
                '<strong class="navigation-route__name">' +
                escapeHtml(name) +
                '</strong><span class="navigation-route__kind">' +
                escapeHtml(kind) +
                '</span><span class="navigation-route__stop">' +
                addr +
                '</span>'
            );
        }
        return (
            '<strong class="navigation-route__kind">' +
            escapeHtml(kind) +
            '</strong><span class="navigation-route__stop">' +
            addr +
            '</span>'
        );
    }

    function renderNavigationStops(stops) {
        const list = $('#navigation-stops');
        if (!list) {
            return;
        }
        if (!stops.length) {
            list.innerHTML = '';
            list.hidden = true;
            return;
        }
        list.innerHTML = stops
            .map(function (stop, i) {
                return (
                    '<li><button type="button" class="navigation-stop-btn" data-nav-stop="' +
                    i +
                    '"><span class="navigation-stops__num">' +
                    (i + 1) +
                    '</span><span>' +
                    navigationStopBodyHtml(stop) +
                    '</span></button></li>'
                );
            })
            .join('');
        list.hidden = false;
    }

    function navigationStatusText(stops, origin) {
        if (!stops.length) {
            return 'Geen ophaalroute voor vandaag.';
        }
        const pickups = stops.filter(function (stop) {
            return stop.kind === 'pickup';
        }).length;
        const first = stops[0];
        const dest = stops[stops.length - 1];
        const fromHere = origin ? ' vanaf je locatie' : '';
        const unscheduled = stops.every(function (stop) {
            return !stop.planned_at;
        });
        const prefix = unscheduled ? 'Geen rit gepland vandaag. ' : '';
        if (pickups === 0) {
            return prefix + 'Navigeer naar de bestemming' + fromHere + '.';
        }
        if (pickups === 1) {
            return (
                prefix +
                'Route' +
                fromHere +
                ': ophalen bij ' +
                (first.name || shortAddress(first.address)) +
                (dest && dest.kind === 'dropoff' ? ', daarna ' + shortAddress(dest.address) : '') +
                '. Tik een stop om direct te navigeren.'
            );
        }
        return (
            prefix +
            pickups +
            ' ophaalstops' +
            fromHere +
            (dest && dest.kind === 'dropoff' ? ', daarna ' + shortAddress(dest.address) : '') +
            '. Tik een stop om direct te navigeren.'
        );
    }

    async function refreshNavigationPanel() {
        const payload = lastTodayPayload || {};
        const nav = payload.navigation || {};
        navigationStops = Array.isArray(nav.stops)
            ? nav.stops.filter(function (stop) {
                  return stop && String(stop.address || '').trim();
              })
            : [];
        renderNavigationStops(navigationStops);
        syncNavigationButton(navigationStops.length > 0);
        if (!navigationStops.length) {
            setNavigationStatus('Geen ophaalroute voor vandaag.');
            return;
        }
        const token = ++navigationDrawToken;
        setNavigationStatus(navigationStatusText(navigationStops, null));
        getCurrentPosition().then(function (pos) {
            if (token !== navigationDrawToken) {
                return;
            }
            navigationOrigin = pos;
        });
        try {
            await loadGoogleMapsSdk();
            if (token !== navigationDrawToken) {
                return;
            }
            ensureNavigationMap();
            const drawn = await drawNavigationRoute(navigationStops);
            if (token !== navigationDrawToken) {
                return;
            }
            if (!drawn) {
                setNavigationStatus(
                    navigationStatusText(navigationStops, navigationOrigin) +
                        ' Kaart kon de route niet tekenen; Start navigatie opent Google Maps.'
                );
            }
            window.requestAnimationFrame(function () {
                resizeNavigationMap();
            });
        } catch (e) {
            if (token !== navigationDrawToken) {
                return;
            }
            setNavigationStatus(navigationStatusText(navigationStops, navigationOrigin));
        }
    }

    function originNearRoute(origin, stops) {
        if (!hasLatLng(origin) || !stops || !stops.length) {
            return null;
        }
        const first = asLatLng(stops[0]);
        if (!first) {
            return origin;
        }
        const dlat = Number(origin.lat) - first.lat;
        const dlng = Number(origin.lng) - first.lng;
        if (dlat * dlat + dlng * dlng > 0.25) {
            return null;
        }
        return origin;
    }

    async function startGoogleNavigation() {
        if (!navigationStops.length) {
            return;
        }
        const url = navigationDirUrl(originNearRoute(navigationOrigin, navigationStops), navigationStops);
        if (url) {
            window.open(url, '_blank', 'noopener');
        }
    }

    async function startStopNavigation(index) {
        const stop = navigationStops[index];
        if (!stop) {
            return;
        }
        const url = navigationStopUrl(originNearRoute(navigationOrigin, [stop]), stop);
        if (url) {
            window.open(url, '_blank', 'noopener');
        }
    }

    async function loadToday(silent) {
        if (!silent) {
            homeError.hidden = true;
        }
        try {
            const res = await api('/today');
            if (!res.ok) {
                throw new Error((res.data && res.data.message) || 'Kon status niet laden.');
            }
            renderToday(res.data.data || {});
        } catch (e) {
            if (!silent) {
                homeError.textContent = e.message || 'Fout bij laden.';
                homeError.hidden = false;
            }
        }
    }

    async function loadWeek(silent) {
        if (!silent) {
            homeError.hidden = true;
        }
        if (!weekFrom) {
            weekFrom = mondayIso();
        }
        try {
            const res = await api('/week?from=' + encodeURIComponent(weekFrom));
            if (!res.ok) {
                throw new Error((res.data && res.data.message) || 'Kon planning niet laden.');
            }
            const data = res.data.data || {};
            weekFrom = data.from || weekFrom;
            renderWeek(data);
            const destinationEl = $('#home-destination');
            if (destinationEl && activeTab === 'week') {
                destinationEl.hidden = true;
                destinationEl.textContent = '';
            }
            $('#home-subtitle').textContent =
                (user && user.portal_role_label ? user.portal_role_label + ' · ' : '') +
                (data.customer_name || '');
        } catch (e) {
            if (!silent) {
                homeError.textContent = e.message || 'Fout bij laden.';
                homeError.hidden = false;
            }
        }
    }

    async function loadAnnouncements(silent) {
        try {
            const res = await api('/announcements');
            if (!res.ok) {
                return;
            }
            renderAnnouncements(res.data.data || {});
        } catch (e) {
            if (!silent) {
                /* ignore soft failures for banners */
            }
        }
    }

    async function loadAbsences() {
        homeError.hidden = true;
        try {
            const res = await api('/absences');
            if (!res.ok) {
                throw new Error((res.data && res.data.message) || 'Kon afmeldingen niet laden.');
            }
            renderAbsences(res.data.data || {});
            const destinationEl = $('#home-destination');
            if (destinationEl && activeTab === 'absences') {
                destinationEl.hidden = true;
            }
        } catch (e) {
            homeError.textContent = e.message || 'Fout bij laden.';
            homeError.hidden = false;
        }
    }

    function maxAbsenceIso() {
        const max = new Date();
        max.setDate(max.getDate() + 14);
        return toIsoDate(max);
    }

    function daysBetweenInclusive(fromIso, toIso) {
        if (!fromIso || !toIso) {
            return 0;
        }
        const from = new Date(fromIso + 'T12:00:00');
        const to = new Date(toIso + 'T12:00:00');
        if (Number.isNaN(from.getTime()) || Number.isNaN(to.getTime()) || to < from) {
            return 0;
        }
        return Math.round((to - from) / 86400000) + 1;
    }

    function updateAbsenceDaysHint() {
        const hint = $('#absence-days-hint');
        const fromEl = $('#absence-date-from');
        const toEl = $('#absence-date-to');
        if (!hint || !fromEl || !toEl) {
            return;
        }
        const days = daysBetweenInclusive(fromEl.value, toEl.value);
        if (days < 1) {
            hint.textContent = '';
            return;
        }
        hint.textContent =
            days === 1 ? '(1 dag)' : '(' + days + ' dagen)';
    }

    function syncAbsenceDateBounds() {
        const fromEl = $('#absence-date-from');
        const toEl = $('#absence-date-to');
        if (!fromEl || !toEl) {
            return;
        }
        const today = todayIso();
        const max = maxAbsenceIso();
        fromEl.min = today;
        fromEl.max = max;
        toEl.min = fromEl.value || today;
        toEl.max = max;
        if (toEl.value && fromEl.value && toEl.value < fromEl.value) {
            toEl.value = fromEl.value;
        }
        updateAbsenceDaysHint();
    }

    function openAbsenceDialog(passengerId, name, dateIso) {
        absencePassengerId = passengerId;
        $('#absence-dialog-name').textContent = name || '';
        const dateValue = dateIso || todayIso();
        const fromEl = $('#absence-date-from');
        const toEl = $('#absence-date-to');
        fromEl.value = dateValue;
        toEl.value = dateValue;
        syncAbsenceDateBounds();
        $('#absence-reason').value = '';
        $('#absence-error').hidden = true;
        $('#absence-dialog').hidden = false;
    }

    function closeAbsenceDialog() {
        absencePassengerId = null;
        $('#absence-dialog').hidden = true;
    }

    async function submitAbsence() {
        if (!absencePassengerId) {
            return;
        }
        const btn = $('#absence-confirm');
        btn.disabled = true;
        $('#absence-error').hidden = true;
        try {
            const dateFrom = $('#absence-date-from').value;
            const dateTo = $('#absence-date-to').value;
            const res = await api('/passengers/' + absencePassengerId + '/absences', {
                method: 'POST',
                body: {
                    date_from: dateFrom,
                    date_to: dateTo || dateFrom,
                    date: dateFrom,
                    reason: $('#absence-reason').value || null,
                },
            });
            if (!res.ok) {
                throw new Error((res.data && res.data.message) || 'Afmelden mislukt.');
            }
            closeAbsenceDialog();
            await Promise.all([loadToday(), loadAbsences(), loadWeek(true)]);
        } catch (e) {
            $('#absence-error').textContent = e.message || 'Afmelden mislukt.';
            $('#absence-error').hidden = false;
        } finally {
            btn.disabled = false;
        }
    }

    async function revokeAbsence(id) {
        if (!id) {
            return;
        }
        const confirmed = await showContractConfirm('Afmelding intrekken?', {
            title: 'Afmelding intrekken?',
            confirmLabel: 'Intrekken',
            danger: true,
        });
        if (!confirmed) {
            return;
        }
        try {
            const res = await api('/absences/' + id, { method: 'DELETE' });
            if (!res.ok) {
                throw new Error((res.data && res.data.message) || 'Intrekken mislukt.');
            }
            showContractNotice('Afmelding ingetrokken.');
            await Promise.all([loadToday(), loadAbsences(), loadWeek(true)]);
        } catch (e) {
            showContractNotice(e.message || 'Intrekken mislukt.', {
                type: 'error',
                title: 'Intrekken mislukt',
            });
        }
    }

    async function bootAuthenticated() {
        restoreUiState();
        showScreen('home');
        setPlanningView(planningView, { skipRender: true });
        setTab(activeTab);
        try {
            const me = await api('/me');
            if (me.ok && me.data && me.data.user) {
                user = me.data.user;
                renderProfileUser(user);
            }
        } catch (e) {
            /* ignore */
        }
        const loads = [loadToday(), loadAnnouncements()];
        if (activeTab === 'week') {
            loads.push(loadWeek());
        } else if (activeTab === 'absences') {
            loads.push(loadAbsences());
        }
        await Promise.all(loads);
        startPoll();
    }

    function updateGuideHint() {
        const hint = $('#guide-hint');
        if (!hint) {
            return;
        }
        hint.hidden = localStorage.getItem(GUIDE_HINT_KEY) === '1';
        requestAnimationFrame(syncThemeToggleTop);
    }

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            return;
        }
        navigator.serviceWorker.register('/taxi-contract-sw.js').catch(function () {});
    }

    $('#login-form').addEventListener('submit', async function (ev) {
        ev.preventDefault();
        loginError.hidden = true;
        const firstPanel = $('#first-login-panel');
        if (firstPanel && !firstPanel.hidden) {
            const verifyBtn = $('#btn-verify-login-code');
            const verifyWrap = $('#first-login-verify');
            if (verifyBtn && verifyWrap && !verifyWrap.hidden) {
                verifyBtn.click();
            } else {
                const sendBtn = $('#btn-send-login-code');
                if (sendBtn) {
                    sendBtn.click();
                }
            }
            return;
        }
        const btn = $('#login-btn');
        btn.disabled = true;
        try {
            await login($('#email').value.trim(), $('#password').value);
            await bootAuthenticated();
        } catch (e) {
            if (e && e.code === 'first_login_required') {
                setFirstLoginMode(true);
            }
            loginError.textContent = e.message || 'Inloggen mislukt.';
            loginError.hidden = false;
        } finally {
            btn.disabled = false;
        }
    });

    function showLoginError(message) {
        loginError.textContent = message || '';
        loginError.hidden = !message;
    }

    const openFirstLoginBtn = $('#btn-open-first-login');
    if (openFirstLoginBtn) {
        openFirstLoginBtn.addEventListener('click', function () {
            showLoginError('');
            setFirstLoginMode(true);
        });
    }
    const cancelFirstLoginBtn = $('#btn-cancel-first-login');
    if (cancelFirstLoginBtn) {
        cancelFirstLoginBtn.addEventListener('click', function () {
            showLoginError('');
            setFirstLoginMode(false);
        });
    }
    const sendLoginCodeBtn = $('#btn-send-login-code');
    if (sendLoginCodeBtn) {
        sendLoginCodeBtn.addEventListener('click', async function () {
            const email = ($('#email') && $('#email').value.trim()) || '';
            showLoginError('');
            if (!email) {
                showLoginError('Vul eerst je e-mailadres in.');
                return;
            }
            sendLoginCodeBtn.disabled = true;
            try {
                await requestLoginCode(email);
                rememberFirstLoginEmail(email);
                setFirstLoginCodeSent(true);
            } catch (e) {
                showLoginError(e.message || 'Code aanvragen mislukt.');
            } finally {
                sendLoginCodeBtn.disabled = false;
            }
        });
    }
    const verifyLoginCodeBtn = $('#btn-verify-login-code');
    if (verifyLoginCodeBtn) {
        verifyLoginCodeBtn.addEventListener('click', async function () {
            const email = getFirstLoginEmail();
            const code = ($('#login-code') && $('#login-code').value.trim()) || '';
            const password = ($('#new-password') && $('#new-password').value) || '';
            const confirm = ($('#new-password-confirm') && $('#new-password-confirm').value) || '';
            showLoginError('');
            if (!email) {
                showLoginError('Vul eerst je e-mailadres in.');
                return;
            }
            if (password !== confirm) {
                showLoginError('De wachtwoorden komen niet overeen.');
                return;
            }
            verifyLoginCodeBtn.disabled = true;
            try {
                await verifyLoginCode(email, code, password);
                await bootAuthenticated();
            } catch (e) {
                showLoginError(e.message || 'Activeren mislukt.');
            } finally {
                verifyLoginCodeBtn.disabled = false;
            }
        });
    }

    const logoutBtn = $('#btn-logout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            logout(false);
        });
    }

    document.querySelectorAll('.contract-bottom-nav__btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const tab = btn.getAttribute('data-main-tab') || 'today';
            setTab(tab);
            if (tab === 'today') {
                loadToday();
            } else if (tab === 'week') {
                loadWeek();
            } else if (tab === 'absences') {
                loadAbsences();
            } else if (tab === 'navigation') {
                if (!lastTodayPayload) {
                    loadToday();
                }
            } else if (tab === 'profile') {
                renderProfileUser(user, { applyServerAccent: false });
            }
        });
    });

    const planningToggle = $('#planning-view-toggle');
    if (planningToggle) {
        planningToggle.addEventListener('click', function (ev) {
            const btn = ev.target.closest('[data-planning-view]');
            if (!btn) {
                return;
            }
            ev.preventDefault();
            setPlanningView(btn.getAttribute('data-planning-view'));
        });
    }

    function bindAbsentClick(ev) {
        const absentBtn = ev.target.closest('[data-absent]');
        if (absentBtn) {
            openAbsenceDialog(
                parseInt(absentBtn.getAttribute('data-absent'), 10),
                absentBtn.getAttribute('data-name') || '',
                absentBtn.getAttribute('data-date') || null
            );
            return true;
        }
        const revokeBtn = ev.target.closest('[data-revoke]');
        if (revokeBtn) {
            revokeAbsence(parseInt(revokeBtn.getAttribute('data-revoke'), 10));
            return true;
        }
        return false;
    }

    function bindPassengerCardToggle(ev) {
        const toggle = ev.target.closest('.passenger-card-toggle');
        if (!toggle) {
            return false;
        }
        const key = toggle.getAttribute('data-expand-key');
        if (!key) {
            return true;
        }
        passengerCardExpanded[key] = !passengerCardExpanded[key];
        const card = toggle.closest('.passenger-card');
        const expanded = !!passengerCardExpanded[key];
        if (card) {
            card.classList.toggle('is-expanded', expanded);
            const body = card.querySelector('.passenger-card-body');
            if (body) {
                body.hidden = !expanded;
            }
        }
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        return true;
    }

    panelToday.addEventListener('click', function (ev) {
        if (bindPassengerCardToggle(ev)) {
            return;
        }
        bindAbsentClick(ev);
    });

    if (panelWeek) {
        panelWeek.addEventListener('click', function (ev) {
            if (bindPassengerCardToggle(ev)) {
                return;
            }
            if (bindAbsentClick(ev)) {
                return;
            }
            const openBtn = ev.target.closest('[data-planning-open]');
            if (openBtn) {
                ev.preventDefault();
                const card = openBtn.closest('[data-expand-key]');
                const key = card && card.getAttribute('data-expand-key');
                if (key) {
                    togglePlanningPassenger(key);
                    return;
                }
                const day = openBtn.getAttribute('data-planning-open-date') || selectedWeekDate || todayIso();
                togglePlanningPassenger(
                    passengerExpandKey(
                        { passenger_id: openBtn.getAttribute('data-planning-open') },
                        { absenceDate: day }
                    )
                );
                return;
            }
            const dayBtn = ev.target.closest('[data-planning-date]');
            if (dayBtn) {
                selectedWeekDate = dayBtn.getAttribute('data-planning-date');
                renderWeek(weekPayload || {});
                return;
            }
            if (ev.target.closest('#planning-week-prev')) {
                ev.preventDefault();
                if (planningView === 'week') {
                    weekFrom = addDaysIso(weekFrom || mondayIso(), -7);
                    if (selectedWeekDate) {
                        selectedWeekDate = addDaysIso(selectedWeekDate, -7);
                    }
                    loadWeek();
                } else {
                    shiftPlanningDay(-1);
                }
                return;
            }
            if (ev.target.closest('#planning-week-today')) {
                ev.preventDefault();
                selectedWeekDate = todayIso();
                weekFrom = mondayIso(selectedWeekDate);
                loadWeek();
                return;
            }
            if (ev.target.closest('#planning-week-next')) {
                ev.preventDefault();
                if (planningView === 'week') {
                    weekFrom = addDaysIso(weekFrom || mondayIso(), 7);
                    if (selectedWeekDate) {
                        selectedWeekDate = addDaysIso(selectedWeekDate, 7);
                    }
                    loadWeek();
                } else {
                    shiftPlanningDay(1);
                }
            }
        });
    }

    panelAbsences.addEventListener('click', function (ev) {
        const revokeBtn = ev.target.closest('[data-revoke]');
        if (revokeBtn) {
            revokeAbsence(parseInt(revokeBtn.getAttribute('data-revoke'), 10));
        }
    });

    if (announcementBanners) {
        announcementBanners.addEventListener('click', function (ev) {
            const btn = ev.target.closest('[data-dismiss-announcement]');
            if (btn) {
                dismissAnnouncement(btn.getAttribute('data-dismiss-announcement'));
            }
        });
    }

    $('#absence-cancel').addEventListener('click', closeAbsenceDialog);
    $('#absence-confirm').addEventListener('click', submitAbsence);
    const absenceDateFrom = $('#absence-date-from');
    const absenceDateTo = $('#absence-date-to');
    if (absenceDateFrom) {
        absenceDateFrom.addEventListener('change', function () {
            const toEl = $('#absence-date-to');
            if (toEl) {
                // Standaard 1 dag: tot = van na keuze van-datum.
                toEl.value = absenceDateFrom.value;
            }
            syncAbsenceDateBounds();
        });
    }
    if (absenceDateTo) {
        absenceDateTo.addEventListener('change', syncAbsenceDateBounds);
    }
    const dismissGuideBtn = $('#btn-dismiss-guide-hint');
    if (dismissGuideBtn) {
        dismissGuideBtn.addEventListener('click', function () {
            localStorage.setItem(GUIDE_HINT_KEY, '1');
            updateGuideHint();
        });
    }

    const startNavBtn = $('#btn-start-navigation');
    if (startNavBtn) {
        startNavBtn.addEventListener('click', function () {
            startGoogleNavigation();
        });
    }
    const navStops = $('#navigation-stops');
    if (navStops) {
        navStops.addEventListener('click', function (ev) {
            const btn = ev.target.closest('[data-nav-stop]');
            if (!btn) {
                return;
            }
            const index = parseInt(btn.getAttribute('data-nav-stop'), 10);
            if (!isNaN(index)) {
                startStopNavigation(index);
            }
        });
    }

    registerServiceWorker();
    bindAccentPicker();
    updateGuideHint();
    initContractDialogs();
    window.addEventListener('resize', syncThemeToggleTop);
    requestAnimationFrame(syncThemeToggleTop);

    if (token) {
        bootAuthenticated();
    } else {
        showScreen('login');
    }
})();
