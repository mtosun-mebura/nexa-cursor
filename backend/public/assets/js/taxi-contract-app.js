(function () {
    'use strict';

    const cfg = window.NEXA_TAXI_CONTRACT || {};
    const STORAGE_KEY = 'nexa_taxi_contract_token';
    const INSTALL_HINT_KEY = 'nexa_taxi_contract_dismiss_install';
    const ANNOUNCEMENT_DISMISS_KEY = 'nexa_taxi_contract_dismiss_announcements';

    let token = sessionStorage.getItem(STORAGE_KEY) || '';
    let user = null;
    let pollTimer = null;
    let deferredInstallPrompt = null;
    let absencePassengerId = null;
    let weekFrom = null;
    let weekPayload = null;
    let selectedWeekDate = null;
    let activeTab = 'today';

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
        const logoutBtn = $('#btn-logout');
        if (logoutBtn) {
            logoutBtn.hidden = name !== 'home';
        }
    }

    function setTab(tab) {
        activeTab = tab;
        const isToday = tab === 'today';
        const isWeek = tab === 'week';
        const isAbsences = tab === 'absences';
        $('#tab-today').classList.toggle('is-active', isToday);
        const tabWeek = $('#tab-week');
        if (tabWeek) {
            tabWeek.classList.toggle('is-active', isWeek);
        }
        $('#tab-absences').classList.toggle('is-active', isAbsences);
        panelToday.hidden = !isToday;
        if (panelWeek) {
            panelWeek.hidden = !isWeek;
        }
        panelAbsences.hidden = !isAbsences;
        const title = $('#home-title');
        if (title) {
            title.textContent = isWeek ? 'Planning' : isAbsences ? 'Afmeldingen' : 'Vandaag';
        }
    }

    function mondayIso(date) {
        const d = date ? new Date(date) : new Date();
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
        return toIsoDate(new Date());
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
            throw new Error(msg);
        }
        token = data.token;
        sessionStorage.setItem(STORAGE_KEY, token);
        user = data.user;
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
        token = '';
        user = null;
        sessionStorage.removeItem(STORAGE_KEY);
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
            if (activeTab === 'today') {
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

    function passengerCardHtml(item, options) {
        const opts = options || {};
        const legs = Array.isArray(item.legs) ? item.legs : [];
        const showLegLabels = legs.length > 1;
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
                '<p class="muted" style="margin:0;">' +
                escapeHtml(item.pickup_address || 'Geen ophaaladres') +
                '</p>' +
                '<div class="status-pills">' +
                statusPillsHtml(item) +
                '</div>';
        } else {
            body = legs
                .map(function (leg) {
                    const time = formatTime(leg.planned_at);
                    return (
                        '<div class="leg-block">' +
                        (showLegLabels
                            ? '<p class="leg-label">' + escapeHtml(leg.leg_label || '') + '</p>'
                            : '') +
                        '<p class="muted" style="margin:0;">' +
                        escapeHtml(leg.pickup_address || item.pickup_address || 'Geen ophaaladres') +
                        (time ? ' · ' + escapeHtml(time) : '') +
                        '</p>' +
                        '<div class="status-pills">' +
                        statusPillsHtml(leg) +
                        '</div>' +
                        '</div>'
                    );
                })
                .join('');
        }

        return (
            '<div class="card">' +
            '<p class="passenger-name">' +
            escapeHtml(item.name) +
            '</p>' +
            body +
            (item.absence_reason
                ? '<p class="muted" style="margin:0.5rem 0 0;">' +
                  escapeHtml(item.absence_reason) +
                  '</p>'
                : '') +
            actions +
            '</div>'
        );
    }

    function renderToday(payload) {
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
            return;
        }

        panelToday.innerHTML = items.map(function (item) {
            return passengerCardHtml(item);
        }).join('');
    }

    function renderWeek(payload) {
        weekPayload = payload || {};
        const days = weekPayload.days || [];
        if (!selectedWeekDate && days.length) {
            const todayDay = days.find(function (d) {
                return d.is_today;
            });
            selectedWeekDate = (todayDay && todayDay.date) || days[0].date;
        }
        const selected =
            days.find(function (d) {
                return d.date === selectedWeekDate;
            }) || days[0];

        const fromLabel = formatDate(weekPayload.from);
        const toLabel = formatDate(weekPayload.to);
        let html =
            '<div class="week-nav">' +
            '<button type="button" class="btn btn-ghost btn-sm" id="week-prev">←</button>' +
            '<button type="button" class="btn btn-ghost btn-sm" id="week-today">Vandaag</button>' +
            '<div class="week-label">' +
            escapeHtml(fromLabel) +
            ' – ' +
            escapeHtml(toLabel) +
            '</div>' +
            '<button type="button" class="btn btn-ghost btn-sm" id="week-next">→</button>' +
            '</div>' +
            '<div class="week-days">';

        days.forEach(function (day) {
            const d = new Date(day.date + 'T12:00:00');
            const name = d.toLocaleDateString('nl-NL', { weekday: 'short' });
            html +=
                '<button type="button" class="week-day' +
                (day.date === selectedWeekDate ? ' is-active' : '') +
                (day.is_today ? ' is-today' : '') +
                '" data-week-date="' +
                escapeHtml(day.date) +
                '"><span class="wd-name">' +
                escapeHtml(name) +
                '</span><span class="wd-num">' +
                d.getDate() +
                '</span></button>';
        });
        html += '</div>';

        if (!selected) {
            html += '<div class="empty">Geen planningsdata.</div>';
            panelWeek.innerHTML = html;
            return;
        }

        const items = selected.items || [];
        if (!items.length) {
            html += '<div class="empty">Geen leerlingen gekoppeld.</div>';
        } else {
            html += items
                .map(function (item) {
                    return passengerCardHtml(item, {
                        dayStatus: item.day_status,
                        dayStatusLabel: item.day_status_label,
                        absenceDate: selected.date,
                    });
                })
                .join('');
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
                '<div class="empty">Geen openstaande afmeldingen (tot 14 dagen vooruit).</div>';
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
        if (!id || !window.confirm('Afmelding intrekken?')) {
            return;
        }
        try {
            const res = await api('/absences/' + id, { method: 'DELETE' });
            if (!res.ok) {
                throw new Error((res.data && res.data.message) || 'Intrekken mislukt.');
            }
            await Promise.all([loadToday(), loadAbsences(), loadWeek(true)]);
        } catch (e) {
            homeError.textContent = e.message || 'Intrekken mislukt.';
            homeError.hidden = false;
        }
    }

    async function bootAuthenticated() {
        showScreen('home');
        setTab('today');
        try {
            const me = await api('/me');
            if (me.ok && me.data && me.data.user) {
                user = me.data.user;
            }
        } catch (e) {
            /* ignore */
        }
        await Promise.all([loadToday(), loadAnnouncements()]);
        startPoll();
    }

    function detectInstallPlatform() {
        const ua = navigator.userAgent || '';
        const isIos =
            /iPad|iPhone|iPod/i.test(ua) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        if (isIos) {
            return 'ios';
        }
        if (/Android/i.test(ua)) {
            return 'android';
        }
        return 'desktop';
    }

    function installGuideContent(platform) {
        if (platform === 'ios') {
            return {
                label: 'iPhone / iPad',
                intro: 'Voeg de app toe aan je beginscherm via Safari:',
                steps: [
                    'Open deze pagina in Safari (niet in Chrome of een andere browser).',
                    'Tik op de Deel-knop onderaan (vierkant met pijl omhoog).',
                    'Scroll en kies “Zet op beginscherm”.',
                    'Tik op “Voeg toe”. De app verschijnt nu op je beginscherm.',
                ],
            };
        }
        if (platform === 'android') {
            return {
                label: 'Android',
                intro: 'Installeer de app via Chrome op je telefoon:',
                steps: [
                    'Open deze pagina in Chrome.',
                    'Tik rechtsboven op het menu (⋮).',
                    'Kies “App installeren” of “Toevoegen aan startscherm”.',
                    'Bevestig met “Installeren” of “Toevoegen”.',
                ],
            };
        }
        return {
            label: 'Computer / overig',
            intro: 'Het beste werkt dit op je telefoon. Op een computer:',
            steps: [
                'Open deze pagina in Chrome of Edge.',
                'Klik op het installatie-icoon in de adresbalk, of gebruik de knop “Installeer app” als die zichtbaar is.',
                'Op iPhone: open de link in Safari → Deel → Zet op beginscherm.',
                'Op Android: Chrome-menu (⋮) → App installeren / Toevoegen aan startscherm.',
            ],
        };
    }

    function openInstallGuide() {
        const dialog = $('#install-guide-dialog');
        if (!dialog) {
            return;
        }
        const content = installGuideContent(detectInstallPlatform());
        const platformEl = $('#install-guide-platform');
        const introEl = $('#install-guide-intro');
        const stepsEl = $('#install-guide-steps');
        if (platformEl) {
            platformEl.textContent = content.label;
        }
        if (introEl) {
            introEl.textContent = content.intro;
        }
        if (stepsEl) {
            stepsEl.innerHTML = content.steps
                .map(function (step) {
                    return '<li>' + escapeHtml(step) + '</li>';
                })
                .join('');
        }
        dialog.hidden = false;
    }

    function closeInstallGuide() {
        const dialog = $('#install-guide-dialog');
        if (dialog) {
            dialog.hidden = true;
        }
    }

    function updateInstallHint() {
        const hint = $('#install-hint');
        if (!hint) {
            return;
        }
        if (localStorage.getItem(INSTALL_HINT_KEY) === '1') {
            hint.hidden = true;
            return;
        }
        const isStandalone =
            window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true;
        hint.hidden = isStandalone;
        const installBtn = $('#btn-install-app');
        if (installBtn) {
            installBtn.hidden = !deferredInstallPrompt;
        }
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
        const btn = $('#login-btn');
        btn.disabled = true;
        try {
            await login($('#email').value.trim(), $('#password').value);
            await bootAuthenticated();
        } catch (e) {
            loginError.textContent = e.message || 'Inloggen mislukt.';
            loginError.hidden = false;
        } finally {
            btn.disabled = false;
        }
    });

    const logoutBtn = $('#btn-logout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            logout(false);
        });
    }

    $('#tab-today').addEventListener('click', function () {
        setTab('today');
        loadToday();
    });
    const tabWeek = $('#tab-week');
    if (tabWeek) {
        tabWeek.addEventListener('click', function () {
            setTab('week');
            loadWeek();
        });
    }
    $('#tab-absences').addEventListener('click', function () {
        setTab('absences');
        loadAbsences();
    });

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

    panelToday.addEventListener('click', function (ev) {
        bindAbsentClick(ev);
    });

    if (panelWeek) {
        panelWeek.addEventListener('click', function (ev) {
            if (bindAbsentClick(ev)) {
                return;
            }
            const dayBtn = ev.target.closest('[data-week-date]');
            if (dayBtn) {
                selectedWeekDate = dayBtn.getAttribute('data-week-date');
                renderWeek(weekPayload || {});
                return;
            }
            if (ev.target.closest('#week-prev')) {
                weekFrom = addDaysIso(weekFrom || mondayIso(), -7);
                selectedWeekDate = null;
                loadWeek();
                return;
            }
            if (ev.target.closest('#week-today')) {
                weekFrom = mondayIso();
                selectedWeekDate = todayIso();
                loadWeek();
                return;
            }
            if (ev.target.closest('#week-next')) {
                weekFrom = addDaysIso(weekFrom || mondayIso(), 7);
                selectedWeekDate = null;
                loadWeek();
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
    $('#btn-dismiss-install').addEventListener('click', function () {
        localStorage.setItem(INSTALL_HINT_KEY, '1');
        updateInstallHint();
    });
    const btnInstallGuide = $('#btn-install-guide');
    if (btnInstallGuide) {
        btnInstallGuide.addEventListener('click', openInstallGuide);
    }
    const btnInstallGuideClose = $('#install-guide-close');
    if (btnInstallGuideClose) {
        btnInstallGuideClose.addEventListener('click', closeInstallGuide);
    }
    const installGuideDialog = $('#install-guide-dialog');
    if (installGuideDialog) {
        installGuideDialog.addEventListener('click', function (ev) {
            if (ev.target === installGuideDialog) {
                closeInstallGuide();
            }
        });
    }
    $('#btn-install-app').addEventListener('click', async function () {
        if (!deferredInstallPrompt) {
            return;
        }
        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        updateInstallHint();
    });

    window.addEventListener('beforeinstallprompt', function (ev) {
        ev.preventDefault();
        deferredInstallPrompt = ev;
        updateInstallHint();
    });
    window.addEventListener('appinstalled', function () {
        deferredInstallPrompt = null;
        updateInstallHint();
    });

    registerServiceWorker();
    updateInstallHint();

    if (token) {
        bootAuthenticated();
    } else {
        showScreen('login');
    }
})();
