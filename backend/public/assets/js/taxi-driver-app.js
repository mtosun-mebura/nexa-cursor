(function () {
    'use strict';

    const cfg = window.NEXA_TAXI_DRIVER || {};
    const STORAGE_KEY = 'nexa_taxi_driver_token';
    const COMPANY_KEY = 'nexa_taxi_driver_company_id';
    const ONLINE_KEY = 'nexa_taxi_driver_online';
    const VEHICLE_KEY = 'nexa_taxi_driver_vehicle_id';
    const NOTIFICATIONS_HINT_DISMISSED_KEY = 'nexa_taxi_dismiss_notifications_hint';
    const IOS_AWAKE_HINT_DISMISSED_KEY = 'nexa_taxi_dismiss_ios_awake_hint';
    const INSTALL_HINT_DISMISSED_KEY = 'nexa_taxi_dismiss_install_hint';
    const GUIDE_HINT_DISMISSED_KEY = 'nexa_taxi_dismiss_guide_hint';
    const UI_STATE_KEY = 'nexa_taxi_driver_ui';
    const RIDE_ALERT_TONE_KEY = 'nexa_taxi_ride_alert_tone';
    const RIDE_ALERT_TONES = ['classic', 'chime', 'alert', 'soft', 'siren'];
    const RIDE_ALERT_TONE_DEFAULT = 'classic';
    const GPS_COORDS_KEY = 'nexa_taxi_driver_last_gps';
    const GPS_FIX_OPTIONS = {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 750
    };
    const GPS_SEND_INTERVAL_MS = 1000;
    const GPS_HEARTBEAT_MS = 1000;
    const GPS_MAX_ACCURACY_METERS = 140;
    const GPS_BACKGROUND_ACCURACY_METERS = 250;
    const GPS_MAX_SPEED_MPS = 42;
    const VALID_TABS = ['requests', 'trips', 'planning', 'navigation', 'earnings', 'profile'];
    const VALID_RIDE_KINDS = ['all', 'taxi', 'contract'];
    const RIDE_KIND_KEY = 'nexa_taxi_driver_ride_kind';
    const DEFAULT_RIDE_DURATION_SECONDS = 45 * 60;
    const RIDE_SCHEDULE_BUFFER_SECONDS = 10 * 60;
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

    function storageGet(store, key) {
        try {
            return store.getItem(key) || '';
        } catch (e) {
            return '';
        }
    }

    function storageSet(store, key, value) {
        try {
            store.setItem(key, value);
        } catch (e) {
            /* ignore quota / private mode */
        }
    }

    function storageRemove(store, key) {
        try {
            store.removeItem(key);
        } catch (e) {
            /* ignore */
        }
    }

    function persistToken(value, expiresAt) {
        if (!value) {
            clearPersistedAuth();
            return;
        }
        storageSet(localStorage, STORAGE_KEY, value);
        storageSet(sessionStorage, STORAGE_KEY, value);
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
        storageRemove(localStorage, STORAGE_KEY);
        storageRemove(sessionStorage, STORAGE_KEY);
        storageRemove(localStorage, COMPANY_KEY);
        storageRemove(sessionStorage, COMPANY_KEY);
        storageRemove(sessionStorage, UI_STATE_KEY);
        clearAuthCookie(STORAGE_KEY);
    }

    function readPersistedToken() {
        const local = storageGet(localStorage, STORAGE_KEY);
        if (local) {
            persistToken(local);
            return local;
        }
        const session = storageGet(sessionStorage, STORAGE_KEY);
        if (session) {
            persistToken(session);
            return session;
        }
        const cookie = readCookie(STORAGE_KEY);
        if (cookie) {
            persistToken(cookie);
            return cookie;
        }
        return '';
    }

    let deferredInstallPrompt = null;

    window.addEventListener('beforeinstallprompt', function (ev) {
        ev.preventDefault();
        deferredInstallPrompt = ev;
        updateInstallHint();
    });

    window.addEventListener('appinstalled', function () {
        deferredInstallPrompt = null;
        updateInstallHint();
    });

    let token = readPersistedToken();
    let profileUser = null;
    let pollTimer = null;
    let pushSource = null;
    let timerInterval = null;
    let currentOffer = null;
    let currentActiveRide = null;
    let pendingOffers = [];
    let mainInboxRideCount = 0;
    let scheduledRides = [];
    let scheduledRideExpanded = {};
    let earningsRideExpanded = {};
    let planningWeekFrom = null;
    let planningSelectedDate = null;
    let planningView = 'day';
    let rideKindFilter = (function () {
        try {
            const stored = localStorage.getItem(RIDE_KIND_KEY);
            if (VALID_RIDE_KINDS.indexOf(stored) >= 0) {
                return stored;
            }
        } catch (e) {
            /* ignore */
        }
        return 'all';
    })();
    let navigationMap = null;
    let navigationRenderer = null;
    let navigationMarkers = [];
    let navigationPolyline = null;
    let navigationStops = [];
    let navigationOrigin = null;
    let googleMapsLoadPromise = null;
    let navigationDrawToken = 0;
    let planningPayload = null;
    let archivedRideExpanded = {};
    let archivedSelectedIds = {};
    let activeRideStops = [];
    let activeRideStopsProgress = null;
    let activeRideInboxCollapsed = true;
    let parkedAssignedRides = [];
    let viewingActiveRideId = null;
    const STOP_ARRIVE_RADIUS_M = 120;
    const NAV_SESSION_KEY = 'nexa_taxi_nav_session';
    let navigationWatchId = null;
    let stopGeofenceWatchId = null;
    let stopGeofenceAutoArrivePending = {};
    let stopGeofenceAvailable = null;
    let stopArrivedAnimationIds = {};
    let offerQueueIndex = 0;
    let isOnline = false;
    let selectedVehicleId = (function () {
        const raw = localStorage.getItem(VEHICLE_KEY);
        const parsed = raw != null ? parseInt(raw, 10) : NaN;
        return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
    })();
    let lastVehiclesRefreshAt = 0;
    let vehicleChoiceLocked = false;

    function selectedVehicleQuery(prefix) {
        if (!selectedVehicleId) {
            return '';
        }
        return (prefix || '?') + 'vehicle_id=' + encodeURIComponent(String(selectedVehicleId));
    }
    let lastGpsCoords = (function () {
        try {
            const raw = sessionStorage.getItem(GPS_COORDS_KEY);
            if (!raw) {
                return null;
            }
            const parsed = JSON.parse(raw);
            if (parsed && Number.isFinite(parsed.lat) && Number.isFinite(parsed.lng)) {
                return { lat: parsed.lat, lng: parsed.lng };
            }
        } catch (e) {
            /* ignore */
        }
        return null;
    })();
    let lastGpsSentAt = 0;
    let lastGpsAccuracy = null;
    let lastGpsFixAt = 0;
    let gpsWatchId = null;
    let gpsHeartbeatTimer = null;
    let rideTrackBuffer = [];
    let rideTrackRideId = null;
    let companyId = (function () {
        const raw = storageGet(localStorage, COMPANY_KEY) || storageGet(sessionStorage, COMPANY_KEY);
        const parsed = raw != null ? parseInt(raw, 10) : NaN;
        return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
    })();
    let accountActive = true;
    let lastNotifiedOfferId = null;
    let activeRideAcceptedMessage = null;
    let showNewRideAlertAfterComplete = false;
    const notifiedOfferIds = new Set();
    const notifiedWaitingRideIds = new Set();
    const waitingRideIds = new Set();
    let configuredOfferTtlSeconds = 300;
    let driverPaymentEnabled = false;
    let firstLoginEmail = '';
    let declinedOffers = [];
    let pendingApprovalOffers = [];
    let overdueScheduledRides = [];
    let overdueReleasedOffers = [];
    let archivedOffers = [];
    let unclaimedRides = [];
    let inboxView = 'offers';
    let inboxLoading = false;
    let inboxHasLoaded = false;
    let canViewEarnings = false;
    let canViewMonthEarnings = false;
    let earningsDate = null;
    let earningsLoading = false;
    let paymentPollTimer = null;
    let cachedOpenPayment = null;
    let audioCtx = null;
    let screenWakeLock = null;
    let wakeLockRetryTimer = null;
    let noSleepOscillator = null;
    let noSleepGain = null;
    let noSleepRafId = null;

    const $ = (sel) => document.querySelector(sel);
    const screenLogin = $('#screen-login');
    const screenDispatch = $('#screen-dispatch');

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showAbsenceAlert(alert) {
        const banner = $('#absence-alert-banner');
        const textEl = $('#absence-alert-text');
        if (!banner || !textEl) {
            return;
        }
        if (!alert || !alert.message) {
            return;
        }
        textEl.textContent = alert.message;
        banner.hidden = false;
    }

    let driverNoticeTimer = null;

    function closeDriverNotice() {
        const dialog = $('#driver-notice-dialog');
        if (driverNoticeTimer) {
            window.clearTimeout(driverNoticeTimer);
            driverNoticeTimer = null;
        }
        if (dialog) {
            dialog.classList.remove('is-open');
            dialog.hidden = true;
            dialog.setAttribute('aria-hidden', 'true');
        }
        if (!document.querySelector('.driver-dialog.is-open')) {
            document.body.classList.remove('driver-dialog-open');
        }
    }

    function showDriverNotice(message, options) {
        const opts = options || {};
        const dialog = $('#driver-notice-dialog');
        const titleEl = $('#driver-notice-title');
        const textEl = $('#driver-notice-text');
        const iconEl = $('#driver-notice-icon');
        const okBtn = $('#driver-notice-ok');
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
        }
        if (driverNoticeTimer) {
            window.clearTimeout(driverNoticeTimer);
            driverNoticeTimer = null;
        }
        dialog.hidden = false;
        dialog.setAttribute('aria-hidden', 'false');
        dialog.classList.add('driver-dialog--instant', 'is-open');
        document.body.classList.add('driver-dialog-open');
        requestAnimationFrame(function () {
            dialog.classList.remove('driver-dialog--instant');
        });
        if (okBtn) {
            okBtn.focus();
        }
        driverNoticeTimer = window.setTimeout(closeDriverNotice, isError ? 7000 : 4500);
    }

    let driverConfirmResolve = null;

    function closeDriverConfirm(result) {
        const dialog = $('#driver-confirm-dialog');
        if (dialog) {
            dialog.classList.add('driver-dialog--instant');
            dialog.classList.remove('is-open');
            dialog.hidden = true;
            dialog.setAttribute('aria-hidden', 'true');
            requestAnimationFrame(function () {
                dialog.classList.remove('driver-dialog--instant');
            });
        }
        if (!document.querySelector('.driver-dialog.is-open')) {
            document.body.classList.remove('driver-dialog-open');
        }
        if (driverConfirmResolve) {
            const resolve = driverConfirmResolve;
            driverConfirmResolve = null;
            resolve(!!result);
        }
    }

    function showDriverConfirm(message, options) {
        const opts = options || {};
        const dialog = $('#driver-confirm-dialog');
        const titleEl = $('#driver-confirm-title');
        const textEl = $('#driver-confirm-text');
        const iconEl = $('#driver-confirm-icon');
        const okBtn = $('#driver-confirm-ok');
        const cancelBtn = $('#driver-confirm-cancel');
        if (!dialog || !message) {
            return Promise.resolve(false);
        }
        closeDriverNotice();
        if (driverConfirmResolve) {
            const prev = driverConfirmResolve;
            driverConfirmResolve = null;
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
            driverConfirmResolve = resolve;
            dialog.hidden = false;
            dialog.setAttribute('aria-hidden', 'false');
            dialog.classList.add('driver-dialog--instant', 'is-open');
            document.body.classList.add('driver-dialog-open');
            requestAnimationFrame(function () {
                dialog.classList.remove('driver-dialog--instant');
            });
            if (okBtn) {
                okBtn.focus();
            }
        });
    }

    function initDriverConfirmDialog() {
        const dialog = $('#driver-confirm-dialog');
        if (!dialog || dialog.dataset.bound === '1') {
            return;
        }
        dialog.dataset.bound = '1';
        const okBtn = $('#driver-confirm-ok');
        const cancelBtn = $('#driver-confirm-cancel');
        const backdrop = dialog.querySelector('[data-driver-confirm-dismiss]');
        if (okBtn) {
            okBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                closeDriverConfirm(true);
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                closeDriverConfirm(false);
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                closeDriverConfirm(false);
            });
        }
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && dialog.classList.contains('is-open')) {
                closeDriverConfirm(false);
            }
        });
    }

    function alert(message) {
        showDriverNotice(String(message || 'Er ging iets mis.'), {
            type: 'error',
            title: 'Let op',
        });
    }

    function initDriverNoticeDialog() {
        const dialog = $('#driver-notice-dialog');
        if (!dialog) {
            return;
        }
        const okBtn = $('#driver-notice-ok');
        const backdrop = dialog.querySelector('[data-driver-notice-dismiss]');
        if (okBtn) {
            okBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                closeDriverNotice();
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                closeDriverNotice();
            });
        }
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && dialog.classList.contains('is-open')) {
                closeDriverNotice();
            }
        });
    }

    function showPickupProposalAlert(alert) {
        if (!alert || !alert.message) {
            return;
        }
        const banner = $('#absence-alert-banner');
        const textEl = $('#absence-alert-text');
        const rideId = parseInt(alert.ride_id, 10);
        const hasRide = Number.isFinite(rideId) && rideId > 0;
        if (banner && textEl) {
            let html = '<span class="absence-alert-message">' + escapeHtml(alert.message) + '</span>';
            if (hasRide) {
                html +=
                    ' <button type="button" class="banner-ride-link" data-open-ride-id="' +
                    escapeHtml(String(rideId)) +
                    '">Bekijk rit #' +
                    escapeHtml(String(rideId)) +
                    '</button>';
            }
            textEl.innerHTML = html;
            banner.hidden = false;
        }
        try {
            if (window.Notification && Notification.permission === 'granted') {
                new Notification(hasRide ? 'Ophaalvoorstel · rit #' + rideId : 'Ophaalvoorstel', {
                    body: alert.message,
                });
            }
        } catch (e) {
            /* ignore */
        }
        vibrate(120);
    }

    function pickupProposalExpandKey(prefix, offer, rideId) {
        const offerId = offer && offer.id != null ? String(offer.id) : '';
        return prefix + (offerId || String(rideId));
    }

    function revealRideCard(expandKey) {
        const key = String(expandKey || '').replace(/"/g, '');
        if (!key) {
            return;
        }
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                const card = document.querySelector('.offer-card[data-ride-id="' + key + '"]');
                if (!card) {
                    return;
                }
                card.classList.add('is-pickup-alert-target');
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.setTimeout(function () {
                    card.classList.remove('is-pickup-alert-target');
                }, 2800);
            });
        });
    }

    function openRideFromPickupAlert(rideId) {
        const id = String(rideId || '');
        if (!id) {
            return;
        }

        const pending = (pendingApprovalOffers || []).find(function (offer) {
            return offer && offer.ride && String(offer.ride.id) === id;
        });
        if (pending) {
            const expandKey = pickupProposalExpandKey('pending-approval-', pending, id);
            scheduledRideExpanded[expandKey] = true;
            setMainTab('requests');
            setInboxView('offers');
            revealRideCard(expandKey);
            return;
        }

        const declinedProposal = (declinedOffers || []).find(function (offer) {
            return isCustomerDeclinedProposalOffer(offer) && offer.ride && String(offer.ride.id) === id;
        });
        if (declinedProposal) {
            const expandKey = pickupProposalExpandKey('declined-proposal-', declinedProposal, id);
            scheduledRideExpanded[expandKey] = true;
            setMainTab('requests');
            setInboxView('declined');
            revealRideCard(expandKey);
            return;
        }

        if (
            (currentActiveRide && String(currentActiveRide.id) === id) ||
            (parkedAssignedRides || []).some(function (ride) {
                return ride && String(ride.id) === id;
            })
        ) {
            showActiveRideFullPanel(id);
            return;
        }

        const inTrips =
            (scheduledRides || []).some(function (ride) {
                return ride && String(ride.id) === id;
            }) ||
            (overdueScheduledRides || []).some(function (ride) {
                return ride && String(ride.id) === id;
            });
        if (inTrips) {
            scheduledRideExpanded[id] = true;
            setMainTab('trips');
            revealRideCard(id);
            return;
        }

        const released = (overdueReleasedOffers || []).find(function (offer) {
            return offer && offer.ride && String(offer.ride.id) === id;
        });
        if (released) {
            const expandKey = pickupProposalExpandKey('released-', released, id);
            scheduledRideExpanded[expandKey] = true;
            setMainTab('requests');
            setInboxView('overdue');
            revealRideCard(expandKey);
            return;
        }

        scheduledRideExpanded[id] = true;
        setMainTab('trips');
        revealRideCard(id);
    }

    function setButtonLoading(btn, loading, loadingLabel) {
        if (!btn) {
            return;
        }
        if (!loading) {
            clearButtonLoading(btn);
            return;
        }
        if (!btn.dataset.btnOriginalHtml) {
            btn.dataset.btnOriginalHtml = btn.innerHTML;
        }
        const label = loadingLabel || 'Bezig…';
        btn.disabled = true;
        btn.classList.add('is-loading');
        btn.setAttribute('aria-busy', 'true');
        btn.innerHTML =
            '<span class="btn-spinner" aria-hidden="true"></span>' +
            '<span class="btn-label">' +
            escapeHtml(label) +
            '</span>';
    }

    function clearButtonLoading(btn, options) {
        if (!btn) {
            return;
        }
        const opts = options || {};
        btn.classList.remove('is-loading');
        btn.removeAttribute('aria-busy');
        if (btn.dataset.btnOriginalHtml) {
            btn.innerHTML = btn.dataset.btnOriginalHtml;
            delete btn.dataset.btnOriginalHtml;
        }
        if (opts.disabled !== undefined) {
            btn.disabled = !!opts.disabled;
        } else if (!btn.classList.contains('is-paid')) {
            btn.disabled = false;
        }
    }

    function headers(json) {
        const h = {
            Accept: 'application/json',
            Authorization: token ? 'Bearer ' + token : '',
        };
        if (companyId) {
            h['X-Company-Id'] = String(companyId);
        }
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
            keepalive: !!opts.keepalive,
        });
        let data = null;
        try {
            data = await res.json();
        } catch (e) {
            data = null;
        }
        if (res.status === 401) {
            logout(false);
            throw new Error('Sessie verlopen. Log opnieuw in.');
        }
        if (!res.ok) {
            if (data && data.error === 'driver_not_active') {
                setAccountInactive(true, data.message);
            }
            const msg = (data && data.message) || 'Er ging iets mis.';
            const err = new Error(msg);
            err.code = data && data.error;
            throw err;
        }
        return data;
    }

    function setAccountInactive(inactive, message) {
        accountActive = !inactive;
        const banner = $('#account-inactive-banner');
        const toggle = $('#online-toggle');
        if (banner) {
            if (inactive) {
                banner.hidden = false;
                if (message) {
                    banner.textContent = message;
                }
            } else {
                banner.hidden = true;
            }
        }
        if (toggle) {
            toggle.disabled = inactive;
        }
        if (inactive) {
            isOnline = false;
            inboxLoading = false;
            inboxHasLoaded = false;
            localStorage.setItem(ONLINE_KEY, '0');
            setOnlineUi();
            stopInboxSync();
            renderOffer(null);
            updateEmptyState();
            syncScreenWakeLock();
        }
    }

    function offersViewHasVisibleRides() {
        // Alleen echte openstaande aanbiedingen op de Aanvragen-tab.
        // Geplande/actieve ritten staan onder Ritten en mogen de leegmelding hier niet verbergen.
        if (currentOffer && currentOffer.ride) {
            return true;
        }
        if (pendingOffers && pendingOffers.length > 0) {
            return true;
        }
        if (pendingApprovalOffers && pendingApprovalOffers.length > 0) {
            return true;
        }
        return false;
    }

    const INBOX_EMPTY_ICONS = {
        'no-rides':
            '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 11h14" /><path d="M6 11l1.2-3.6A1.5 1.5 0 0 1 8.62 6h6.76a1.5 1.5 0 0 1 1.42 1.04L18 11" /><path d="M6 11v5a1 1 0 0 0 1 1h1" /><path d="M16 17h1a1 1 0 0 0 1-1v-5" /><circle cx="8" cy="17" r="1.35" /><circle cx="16" cy="17" r="1.35" /><path d="M9 17h6" /></svg>',
        loading: '<span class="inbox-empty-spinner" aria-hidden="true"></span>',
        offline:
            '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2v10" /><path d="M18.4 6.6a8 8 0 1 1-12.8 0" /></svg>',
        inactive:
            '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" /><path d="M8 11V8a4 4 0 0 1 8 0v3" /></svg>',
        error:
            '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4" /><path d="M12 17h.01" /><path d="M10.3 4.5 2.6 18a1.5 1.5 0 0 0 1.3 2.25h16.2a1.5 1.5 0 0 0 1.3-2.25L13.7 4.5a1.5 1.5 0 0 0-2.6 0z" /></svg>',
    };

    function setInboxEmptyIcon(state) {
        const icon = $('#inbox-empty-icon');
        if (!icon) {
            return;
        }
        const key = INBOX_EMPTY_ICONS[state] ? state : 'no-rides';
        icon.dataset.state = key;
        icon.innerHTML = INBOX_EMPTY_ICONS[key];
    }

    function updateEmptyState() {
        const empty = $('#inbox-empty');
        const title = $('#inbox-empty-title');
        const hint = $('#inbox-empty-hint');
        const actions = $('#inbox-empty-actions');
        const btnEmptyOverdue = $('#btn-empty-show-overdue');
        const btnEmptyDeclined = $('#btn-empty-show-declined');
        const btnEmptyArchivedInbox = $('#btn-empty-show-archived-inbox');
        const requestsHead = $('#requests-section-head');
        if (requestsHead) {
            requestsHead.hidden = !isOnline || !accountActive;
        }
        function hideEmptyActions() {
            if (actions) {
                actions.hidden = true;
            }
            if (btnEmptyOverdue) {
                btnEmptyOverdue.hidden = true;
            }
            if (btnEmptyDeclined) {
                btnEmptyDeclined.hidden = true;
            }
            if (btnEmptyArchivedInbox) {
                btnEmptyArchivedInbox.hidden = true;
            }
        }
        if (!empty || !title || !hint) {
            return;
        }
        if (!accountActive) {
            empty.hidden = false;
            setInboxEmptyIcon('inactive');
            title.textContent = 'Account niet actief';
            hint.textContent = 'Je kunt geen ritten ontvangen tot je account is geactiveerd.';
            hideEmptyActions();
            return;
        }
        if (!isOnline) {
            empty.hidden = false;
            setInboxEmptyIcon('offline');
            title.textContent = 'Je bent offline';
            hint.textContent = 'Zet je status op online om ritten te ontvangen.';
            hideEmptyActions();
            return;
        }
        if (isSecondaryInboxView(inboxView)) {
            empty.hidden = true;
            hideEmptyActions();
            return;
        }
        if (!offersViewHasVisibleRides()) {
            empty.hidden = false;
            const overdueCount = overdueInboxCount();
            const declinedCount = declinedOffers.length;
            const archivedCount = archivedOffers.length;
            if (inboxLoading || !inboxHasLoaded) {
                setInboxEmptyIcon('loading');
                title.textContent = 'Ritten inladen…';
                hint.textContent = 'Even geduld, we laden je ritten.';
                hideEmptyActions();
                return;
            }
            setInboxEmptyIcon('no-rides');
            title.textContent = 'Geen nieuwe ritten.';
            if (overdueCount || declinedCount || archivedCount) {
                hint.textContent = 'Nieuwe ritten verschijnen hier automatisch. Eerdere ritten staan onder Verlopen, Afgewezen of Archief.';
            } else {
                hint.textContent = 'Nieuwe ritten verschijnen hier automatisch.';
            }
            if (btnEmptyOverdue) {
                btnEmptyOverdue.hidden = overdueCount < 1;
                btnEmptyOverdue.textContent =
                    overdueCount === 1
                        ? 'Bekijk 1 verlopen rit'
                        : 'Bekijk ' + overdueCount + ' verlopen ritten';
            }
            if (btnEmptyDeclined) {
                btnEmptyDeclined.hidden = declinedCount < 1;
                btnEmptyDeclined.textContent =
                    declinedCount === 1
                        ? 'Bekijk 1 afgewezen rit'
                        : 'Bekijk ' + declinedCount + ' afgewezen ritten';
            }
            const btnEmptyArchived = $('#btn-empty-show-archived-inbox');
            if (btnEmptyArchived) {
                btnEmptyArchived.hidden = archivedCount < 1;
                btnEmptyArchived.textContent =
                    archivedCount === 1
                        ? 'Bekijk 1 gearchiveerde rit'
                        : 'Bekijk ' + archivedCount + ' gearchiveerde ritten';
            }
            if (actions) {
                actions.hidden = overdueCount < 1 && declinedCount < 1 && archivedCount < 1;
            }
            return;
        }
        empty.hidden = true;
        hideEmptyActions();
    }

    function inboxViewTitle(view) {
        if (view === 'declined') {
            return 'Afgewezen';
        }
        if (view === 'overdue') {
            return 'Verlopen';
        }
        if (view === 'archived') {
            return 'Archief';
        }
        return 'Aanvragen';
    }

    function isSecondaryInboxView(view) {
        return view === 'declined' || view === 'overdue' || view === 'archived';
    }

    function overdueInboxCount() {
        return overdueReleasedOffers.length;
    }

    function updateSecondaryNavButtons() {
        const btnDeclined = $('#btn-show-declined');
        const btnOverdue = $('#btn-show-overdue');
        const btnOffers = $('#btn-show-offers');
        const btnArchived = $('#btn-show-archived');
        const declinedCount = $('#declined-count');
        const overdueCount = $('#overdue-count');
        const offersCount = $('#offers-count');
        const archivedCount = $('#archived-count');

        function setToolbarBadge(el, count, btn, label) {
            if (el) {
                el.textContent = String(count);
                el.hidden = count < 1;
            }
            if (btn) {
                btn.setAttribute('aria-label', count > 0 ? label + ' (' + count + ')' : label);
            }
        }
        setToolbarBadge(offersCount, mainInboxRideCount, btnOffers, 'Open');
        setToolbarBadge(declinedCount, declinedOffers.length, btnDeclined, 'Afgewezen');
        setToolbarBadge(overdueCount, overdueInboxCount(), btnOverdue, 'Verlopen');
        setToolbarBadge(archivedCount, archivedOffers.length, btnArchived, 'Archief');
        if (btnOffers) {
            btnOffers.hidden = false;
            btnOffers.classList.toggle('is-active', inboxView === 'offers');
            btnOffers.setAttribute('aria-current', inboxView === 'offers' ? 'page' : 'false');
        }
        if (btnDeclined) {
            btnDeclined.hidden = inboxView !== 'declined' && !declinedOffers.length;
            btnDeclined.classList.toggle('is-active', inboxView === 'declined');
            btnDeclined.setAttribute('aria-current', inboxView === 'declined' ? 'page' : 'false');
        }
        if (btnOverdue) {
            btnOverdue.hidden = inboxView !== 'overdue' && !overdueInboxCount();
            btnOverdue.classList.toggle('is-active', inboxView === 'overdue');
            btnOverdue.setAttribute('aria-current', inboxView === 'overdue' ? 'page' : 'false');
        }
        if (btnArchived) {
            btnArchived.hidden = inboxView !== 'archived' && !archivedOffers.length;
            btnArchived.classList.toggle('is-active', inboxView === 'archived');
            btnArchived.setAttribute('aria-current', inboxView === 'archived' ? 'page' : 'false');
        }
    }

    function syncRequestsSectionHead() {
        const title = $('#requests-section-title');
        const icon = $('#requests-section-icon');
        if (!title || !icon) {
            return;
        }
        if (inboxView === 'overdue') {
            title.textContent = 'Verlopen ritten';
            icon.innerHTML =
                '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3.2 1.8"/></svg>';
            return;
        }
        if (inboxView === 'archived') {
            title.textContent = 'Archief';
            icon.innerHTML =
                '<svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 8h16v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M2 8h20M10 12h4"/></svg>';
            return;
        }
        if (inboxView === 'declined') {
            title.textContent = 'Afgewezen ritten';
            icon.innerHTML =
                '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="m15 9-6 6M9 9l6 6"/></svg>';
            return;
        }
        title.textContent = 'Nieuwe ritaanvraag';
        icon.innerHTML =
            '<svg viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>';
    }

    function setInboxView(view) {
        inboxView = isSecondaryInboxView(view) ? view : 'offers';
        persistUiState();
        const offerStrip = $('#offer-strip');
        const declinedStrip = $('#declined-strip');
        const overdueStrip = $('#overdue-strip');
        const archivedStrip = $('#archived-strip');
        const pendingStrip = $('#pending-approval-strip');
        const title = $('#dispatch-toolbar-title');
        const empty = $('#inbox-empty');

        updateSecondaryNavButtons();
        syncRequestsSectionHead();
        if (title) {
            title.textContent = inboxViewTitle(inboxView);
        }

        if (offerStrip) {
            offerStrip.hidden = inboxView !== 'offers';
        }
        if (pendingStrip) {
            pendingStrip.hidden = inboxView !== 'offers';
        }
        if (declinedStrip) {
            declinedStrip.hidden = inboxView !== 'declined';
        }
        if (overdueStrip) {
            overdueStrip.hidden = inboxView !== 'overdue';
        }
        if (archivedStrip) {
            archivedStrip.hidden = inboxView !== 'archived';
        }

        if (inboxView === 'declined') {
            renderDeclinedOffers(declinedOffers);
            if (empty) {
                empty.hidden = true;
            }
            return;
        }

        if (inboxView === 'overdue') {
            renderOverdueView();
            if (empty) {
                empty.hidden = true;
            }
            return;
        }

        if (inboxView === 'archived') {
            renderArchivedView();
            if (empty) {
                empty.hidden = true;
            }
            return;
        }

        // Geplande ritten staan op de Ritten-tab; niet legen bij inbox-navigatie.
        renderScheduledRides(scheduledRides);
        renderPendingApprovalOffers();
        if (currentOffer) {
            setOfferUiVisible(true);
            if (empty) {
                empty.hidden = true;
            }
        } else {
            setOfferUiVisible(false);
            updateEmptyState();
        }
    }

    function updateDeclinedNavButton() {
        updateSecondaryNavButtons();
    }

    function updateOverdueNavButton() {
        updateSecondaryNavButtons();
    }

    function updateUnclaimedBanner(rides) {
        const banner = $('#unclaimed-rides-banner');
        if (!banner) {
            return;
        }
        const list = Array.isArray(rides) ? rides : [];
        if (!list.length || inboxView !== 'offers') {
            banner.hidden = true;
            banner.innerHTML = '';
            return;
        }
        banner.hidden = false;
        banner.innerHTML =
            '<strong>Let op:</strong> geen chauffeur heeft deze rit(ten) opgepakt. Iemand moet dit oppakken.' +
            '<ul>' +
            list
                .map(function (ride) {
                    const msg = ride.message || ('Rit #' + ride.ride_id);
                    return '<li>' + escapeHtml(msg) + '</li>';
                })
                .join('') +
            '</ul>';
    }

    function isCustomerDeclinedProposalOffer(offer) {
        const ride = (offer && offer.ride) || {};
        const proposal = ride.pickup_proposal || {};
        return String(offer && offer.status ? offer.status : '') === 'accepted'
            && String(proposal.status || '') === 'declined';
    }

    function renderDeclinedOffers(offers) {
        const strip = $('#declined-strip');
        const list = $('#declined-rides-list');
        const empty = $('#declined-empty');
        if (!strip || !list) {
            return;
        }
        const items = Array.isArray(offers) ? offers : [];
        if (!items.length) {
            list.innerHTML = '';
            if (empty) {
                empty.hidden = false;
            }
            return;
        }
        if (empty) {
            empty.hidden = true;
        }
        list.innerHTML = items
            .map(function (offer) {
                if (isCustomerDeclinedProposalOffer(offer)) {
                    return renderCustomerDeclinedProposalCard(offer);
                }
                const ride = offer.ride || {};
                const rideId = ride.id != null ? String(ride.id) : '—';
                return (
                    '<div class="card offer-card declined-ride-card">' +
                    '<div class="offer-card-top">' +
                    '<span class="offer-badge is-muted">Afgewezen</span>' +
                    '</div>' +
                    '<p class="offer-title">Rit #' +
                    escapeHtml(rideId) +
                    '</p>' +
                    offerPickupAtLineHtml(ride) +
                    rideDetailBodyHtml(ride) +
                    '<div class="offer-actions declined-ride-actions">' +
                    '<button type="button" class="btn btn-accept btn-accept-declined" data-offer-id="' +
                    escapeHtml(String(offer.id)) +
                    '">Alsnog accepteren</button>' +
                    '</div>' +
                    '</div>'
                );
            })
            .join('');
    }

    function renderCustomerDeclinedProposalCard(offer) {
        const ride = offer.ride || {};
        const rideId = ride.id != null ? String(ride.id) : '—';
        const offerId = offer.id != null ? String(offer.id) : '';
        const proposal = ride.pickup_proposal || {};
        const expandKey = 'declined-proposal-' + (offerId || rideId);
        const expanded = !!scheduledRideExpanded[expandKey];
        const bodyId = 'declined-proposal-body-' + expandKey;
        const proposedLabel = proposal.proposed_at
            ? formatPickupAt(proposal.proposed_at)
            : formatPickupAt(ridePickupInstant(ride));
        const routeSummary = [ride.pickup_address, ride.dropoff_address]
            .map(function (addr) {
                return addr ? shortAddress(addr) : '';
            })
            .filter(Boolean)
            .join(' → ');
        return (
            '<div class="card offer-card scheduled-ride-card overdue-ride-card' +
            (expanded ? ' is-expanded' : '') +
            '" data-ride-id="' +
            escapeHtml(expandKey) +
            '">' +
            '<button type="button" class="scheduled-ride-toggle" aria-expanded="' +
            (expanded ? 'true' : 'false') +
            '" aria-controls="' +
            escapeHtml(bodyId) +
            '" data-ride-id="' +
            escapeHtml(expandKey) +
            '">' +
            '<span class="scheduled-ride-toggle-text">' +
            '<span class="offer-badge is-danger">Afgewezen door klant</span>' +
            '<span class="offer-title">Rit #' +
            escapeHtml(rideId) +
            '</span>' +
            '<span class="offer-meta scheduled-pickup-at">' +
            escapeHtml(proposedLabel) +
            '</span>' +
            (routeSummary
                ? '<span class="offer-meta scheduled-route-summary">' +
                  escapeHtml(routeSummary) +
                  '</span>'
                : '') +
            '<span class="offer-meta scheduled-proposal-summary">Klant heeft het nieuwe tijdstip afgewezen</span>' +
            '</span>' +
            '<span class="scheduled-ride-chevron" aria-hidden="true">▼</span>' +
            '</button>' +
            '<div class="scheduled-ride-body" id="' +
            escapeHtml(bodyId) +
            '"' +
            (expanded ? '' : ' hidden') +
            '>' +
            pickupProposalBannerHtml(ride) +
            rideDetailBodyHtml(ride) +
            '</div>' +
            '<div class="offer-actions overdue-ride-actions">' +
            '<button type="button" class="btn btn-ghost btn-archive-offer" data-offer-id="' +
            escapeHtml(offerId) +
            '">Archiveren</button>' +
            '<button type="button" class="btn btn-primary btn-propose-pickup" data-ride-id="' +
            escapeHtml(rideId) +
            '">Opnieuw voorstellen</button>' +
            '</div>' +
            '</div>'
        );
    }

    function renderPendingApprovalOffers() {
        const strip = $('#pending-approval-strip');
        const list = $('#pending-approval-list');
        if (!strip || !list) {
            return;
        }
        const items = Array.isArray(pendingApprovalOffers) ? pendingApprovalOffers : [];
        if (inboxView !== 'offers' || !items.length) {
            strip.hidden = true;
            list.innerHTML = '';
            return;
        }
        strip.hidden = false;
        list.innerHTML = items.map(renderPendingApprovalOfferCard).join('');
    }

    function renderPendingApprovalOfferCard(offer) {
        const ride = offer.ride || {};
        const rideId = ride.id != null ? String(ride.id) : '—';
        const offerId = offer.id != null ? String(offer.id) : '';
        const proposal = ride.pickup_proposal || {};
        const expandKey = 'pending-approval-' + (offerId || rideId);
        const expanded = !!scheduledRideExpanded[expandKey];
        const bodyId = 'pending-approval-body-' + expandKey;
        const proposedLabel = proposal.proposed_at
            ? formatPickupAt(proposal.proposed_at)
            : formatPickupAt(ridePickupInstant(ride));
        const routeSummary = [ride.pickup_address, ride.dropoff_address]
            .map(function (addr) {
                return addr ? shortAddress(addr) : '';
            })
            .filter(Boolean)
            .join(' → ');
        return (
            '<div class="card offer-card scheduled-ride-card overdue-ride-card' +
            (expanded ? ' is-expanded' : '') +
            '" data-ride-id="' +
            escapeHtml(expandKey) +
            '">' +
            '<button type="button" class="scheduled-ride-toggle" aria-expanded="' +
            (expanded ? 'true' : 'false') +
            '" aria-controls="' +
            escapeHtml(bodyId) +
            '" data-ride-id="' +
            escapeHtml(expandKey) +
            '">' +
            '<span class="scheduled-ride-toggle-text">' +
            '<span class="offer-badge is-warning">Wacht op klant</span>' +
            '<span class="offer-title">Rit #' +
            escapeHtml(rideId) +
            '</span>' +
            '<span class="offer-meta scheduled-pickup-at">' +
            escapeHtml(proposedLabel) +
            '</span>' +
            (routeSummary
                ? '<span class="offer-meta scheduled-route-summary">' +
                  escapeHtml(routeSummary) +
                  '</span>'
                : '') +
            '<span class="offer-meta scheduled-proposal-summary is-pending">In afwachting van goedkeuring van de klant</span>' +
            '</span>' +
            '<span class="scheduled-ride-chevron" aria-hidden="true">▼</span>' +
            '</button>' +
            '<div class="scheduled-ride-body" id="' +
            escapeHtml(bodyId) +
            '"' +
            (expanded ? '' : ' hidden') +
            '>' +
            pickupProposalBannerHtml(ride) +
            rideDetailBodyHtml(ride) +
            '</div>' +
            '<div class="offer-actions overdue-ride-actions">' +
            (offerId
                ? '<button type="button" class="btn btn-ghost btn-archive-offer" data-offer-id="' +
                  escapeHtml(offerId) +
                  '">Archiveren</button>'
                : '') +
            '<button type="button" class="btn btn-ghost btn-propose-pickup" data-ride-id="' +
            escapeHtml(rideId) +
            '">Opnieuw voorstellen</button>' +
            '</div>' +
            '</div>'
        );
    }

    function ridePickupInstant(ride) {
        if (!ride) {
            return null;
        }
        if (ride.pickup_at) {
            return ride.pickup_at;
        }
        const schedule = ride.schedule || {};
        if (schedule.departure_at) {
            return schedule.departure_at;
        }
        if (schedule.first_pickup_at) {
            return schedule.first_pickup_at;
        }
        if (schedule.destination_arrival_at) {
            return schedule.destination_arrival_at;
        }
        if (ride.return_at) {
            return ride.return_at;
        }
        return null;
    }

    function ridePickupSortValue(ride) {
        const instant = ridePickupInstant(ride);
        if (!instant) {
            return 0;
        }
        const value = Date.parse(instant);
        return isNaN(value) ? 0 : value;
    }

    function sortRidesMostRecentPickupFirst(rides) {
        return (Array.isArray(rides) ? rides.slice() : []).sort(function (a, b) {
            const diff = ridePickupSortValue(b) - ridePickupSortValue(a);
            if (diff !== 0) {
                return diff;
            }
            return String(b && b.id != null ? b.id : '').localeCompare(
                String(a && a.id != null ? a.id : ''),
                undefined,
                { numeric: true }
            );
        });
    }

    function sortRidesEarliestPickupFirst(rides) {
        return (Array.isArray(rides) ? rides.slice() : []).sort(function (a, b) {
            const diff = ridePickupSortValue(a) - ridePickupSortValue(b);
            if (diff !== 0) {
                return diff;
            }
            return String(a && a.id != null ? a.id : '').localeCompare(
                String(b && b.id != null ? b.id : ''),
                undefined,
                { numeric: true }
            );
        });
    }

    function driverCanFilterContractRides() {
        return !!(profileUser && profileUser.can_handle_contract_rides);
    }

    function effectiveRideKindFilter() {
        if (!driverCanFilterContractRides()) {
            return 'all';
        }
        return rideKindFilter;
    }

    function rideMatchesKindFilter(ride, kind) {
        const filter = kind || effectiveRideKindFilter();
        if (filter === 'all') {
            return true;
        }
        const contract = isContractRide(ride);
        if (filter === 'contract') {
            return contract;
        }
        if (filter === 'taxi') {
            return !contract;
        }
        return true;
    }

    function filterRidesByKind(rides) {
        if (!Array.isArray(rides)) {
            return [];
        }
        return rides.filter(function (ride) {
            return rideMatchesKindFilter(ride);
        });
    }

    function filterOffersByKind(offers) {
        if (!Array.isArray(offers)) {
            return [];
        }
        return offers.filter(function (offer) {
            return rideMatchesKindFilter((offer && offer.ride) || offer);
        });
    }

    function rideDurationSeconds(ride) {
        const raw =
            ride && (ride.duration_seconds != null ? ride.duration_seconds : ride.durationSeconds);
        const parsed = parseInt(raw, 10);
        if (!isNaN(parsed) && parsed > 0) {
            return parsed;
        }
        return DEFAULT_RIDE_DURATION_SECONDS;
    }

    function rideScheduleWindow(ride) {
        const startMs = ridePickupSortValue(ride);
        if (!startMs) {
            return null;
        }
        return {
            start: startMs,
            end: startMs + (rideDurationSeconds(ride) + RIDE_SCHEDULE_BUFFER_SECONDS) * 1000,
            ride: ride,
        };
    }

    function collectPlannedScheduleWindows(exceptRideId) {
        const except = exceptRideId != null ? String(exceptRideId) : null;
        const pool = []
            .concat(scheduledRides || [])
            .concat(overdueScheduledRides || [])
            .concat(parkedAssignedRides || []);
        if (currentActiveRide) {
            pool.push(currentActiveRide);
        }
        const seen = {};
        const windows = [];
        pool.forEach(function (ride) {
            if (!ride || ride.id == null) {
                return;
            }
            const id = String(ride.id);
            if (except && id === except) {
                return;
            }
            if (seen[id]) {
                return;
            }
            seen[id] = true;
            const window = rideScheduleWindow(ride);
            if (window) {
                windows.push(window);
            }
        });
        return windows;
    }

    function findScheduleConflictForPickup(pickupIso, durationSeconds, exceptRideId) {
        const startMs = Date.parse(pickupIso);
        if (isNaN(startMs)) {
            return null;
        }
        const dur =
            durationSeconds && durationSeconds > 0
                ? durationSeconds
                : DEFAULT_RIDE_DURATION_SECONDS;
        const endMs = startMs + (dur + RIDE_SCHEDULE_BUFFER_SECONDS) * 1000;
        const windows = collectPlannedScheduleWindows(exceptRideId);
        for (let i = 0; i < windows.length; i++) {
            const existing = windows[i];
            if (startMs < existing.end && endMs > existing.start) {
                return existing.ride;
            }
        }
        return null;
    }

    function syncRideKindFilterUi() {
        const show = driverCanFilterContractRides();
        document.querySelectorAll('.ride-kind-filter').forEach(function (el) {
            el.hidden = !show;
        });
        document.querySelectorAll('.driver-section-head--with-filter').forEach(function (el) {
            el.classList.toggle('has-ride-kind-filter', show);
        });
        const filter = effectiveRideKindFilter();
        document.querySelectorAll('[data-ride-kind]').forEach(function (btn) {
            const active = btn.getAttribute('data-ride-kind') === filter;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function setRideKindFilter(kind, options) {
        const next = VALID_RIDE_KINDS.indexOf(kind) >= 0 ? kind : 'all';
        rideKindFilter = next;
        try {
            localStorage.setItem(RIDE_KIND_KEY, next);
        } catch (e) {
            /* ignore */
        }
        syncRideKindFilterUi();
        persistUiState();
        if (options && options.skipRender) {
            return;
        }
        applyRideKindFilterToViews();
    }

    function applyRideKindFilterToViews() {
        renderScheduledRides(scheduledRides);
        if (pendingOffers && pendingOffers.length) {
            const filtered = filterOffersByKind(pendingOffers);
            if (filtered.length) {
                if (offerQueueIndex >= filtered.length) {
                    offerQueueIndex = 0;
                }
                const shown = filtered[offerQueueIndex] || filtered[0];
                renderOffer(shown, offerQueueIndex, filtered.length, { skipNotify: true });
            } else if (inboxView === 'offers' && !currentActiveRide) {
                renderOffer(null);
            }
        }
        if (planningPayload) {
            renderPlanning(planningPayload);
        }
        updateEmptyState();
        syncTripsEmptyState();
        updateDeclinedNavButton();
        updateOverdueNavButton();
    }

    function offerPickupAtLineHtml(ride) {
        return (
            '<p class="offer-meta offer-pickup-at">' +
            escapeHtml(formatPickupAt(ridePickupInstant(ride))) +
            '</p>'
        );
    }

    function renderOverdueAcceptedRideCard(ride) {
        const rideId = ride.id != null ? String(ride.id) : '—';
        const contract = isContractRide(ride);
        const isGroup = contract && ride.ride_type === 'contract_group';
        const expanded = !!scheduledRideExpanded[rideId];
        const proposal = (ride && ride.pickup_proposal) || {};
        const proposalStatus = String(proposal.status || '');
        let collapsedStatus = '';
        if (proposalStatus === 'pending') {
            collapsedStatus = 'Voorstel verstuurd — wacht op klant';
        } else if (proposalStatus === 'accepted') {
            collapsedStatus = 'Klant heeft nieuw tijdstip geaccepteerd';
        } else if (proposalStatus === 'declined') {
            collapsedStatus = 'Klant heeft voorstel geweigerd';
        }
        const pickupLabel = contract
            ? scheduledRidePickupLabel(ride)
            : formatPickupAt(ridePickupInstant(ride));
        const routeSummary = !isGroup
            ? [
                  ride.pickup_address ? String(ride.pickup_address) : '',
                  ride.dropoff_address ? String(ride.dropoff_address) : '',
              ]
                  .filter(Boolean)
                  .join(' → ')
            : '';
        const bodyId = 'scheduled-ride-body-' + rideId;
        return (
            '<div class="card offer-card scheduled-ride-card overdue-ride-card' +
            (expanded ? ' is-expanded' : '') +
            rideKindCardClass(ride) +
            '" data-ride-id="' +
            escapeHtml(rideId) +
            '">' +
            '<button type="button" class="scheduled-ride-toggle" aria-expanded="' +
            (expanded ? 'true' : 'false') +
            '" aria-controls="' +
            escapeHtml(bodyId) +
            '" data-ride-id="' +
            escapeHtml(rideId) +
            '">' +
            '<span class="scheduled-ride-toggle-text">' +
            '<span class="offer-badge is-danger">Ophaalmoment verlopen</span>' +
            taxiBadgeHtml(ride) +
            (contract ? contractBadgeHtml(ride) : '') +
            nexaSuiteBadgeHtml(ride) +
            '<span class="offer-title">' +
            escapeHtml(contract ? scheduledRideTitle(ride) : 'Rit #' + rideId) +
            '</span>' +
            '<span class="offer-meta scheduled-pickup-at">' +
            escapeHtml(pickupLabel) +
            '</span>' +
            (routeSummary
                ? '<span class="offer-meta scheduled-route-summary">' + escapeHtml(routeSummary) + '</span>'
                : '') +
            (collapsedStatus
                ? '<span class="offer-meta scheduled-proposal-summary">' +
                  escapeHtml(collapsedStatus) +
                  '</span>'
                : '') +
            returnTripBadgeHtml(ride) +
            '</span>' +
            '<span class="scheduled-ride-chevron" aria-hidden="true">▼</span>' +
            '</button>' +
            '<div class="scheduled-ride-body" id="' +
            escapeHtml(bodyId) +
            '"' +
            (expanded ? '' : ' hidden') +
            '>' +
            pickupProposalBannerHtml(ride) +
            (isGroup
                ? ''
                : rideDetailBodyHtml(ride, {
                      hideCustomer: contract,
                      hidePrice: contract,
                  })) +
            (isReturnTripRide(ride) ? returnTripMetaHtml(ride) : '') +
            '</div>' +
            overdueRideActionsHtml(ride, rideId) +
            '</div>'
        );
    }

    function renderOverdueReleasedOfferCard(offer) {
        const ride = offer.ride || {};
        const rideId = ride.id != null ? String(ride.id) : '—';
        const offerId = offer.id != null ? String(offer.id) : '';
        const expandKey = 'released-' + (offerId || rideId);
        const expanded = !!scheduledRideExpanded[expandKey];
        const bodyId = 'overdue-released-body-' + expandKey;
        const pickupLabel = formatPickupAt(ridePickupInstant(ride));
        const routeSummary = [ride.pickup_address, ride.dropoff_address]
            .map(function (addr) {
                return addr ? shortAddress(addr) : '';
            })
            .filter(Boolean)
            .join(' → ');
        const priceLabel =
            ride.quoted_price != null && ride.quoted_price !== ''
                ? formatEuro(ride.quoted_price)
                : '';
        return (
            '<div class="card offer-card scheduled-ride-card overdue-ride-card overdue-ride-card--released' +
            (expanded ? ' is-expanded' : '') +
            '" data-ride-id="' +
            escapeHtml(expandKey) +
            '">' +
            '<button type="button" class="scheduled-ride-toggle" aria-expanded="' +
            (expanded ? 'true' : 'false') +
            '" aria-controls="' +
            escapeHtml(bodyId) +
            '" data-ride-id="' +
            escapeHtml(expandKey) +
            '">' +
            '<span class="scheduled-ride-toggle-text">' +
            '<span class="offer-badge is-danger">Ophaalmoment verlopen</span>' +
            '<span class="offer-title">Rit #' +
            escapeHtml(rideId) +
            '</span>' +
            '<span class="offer-meta scheduled-pickup-at">' +
            escapeHtml(pickupLabel) +
            '</span>' +
            (routeSummary
                ? '<span class="offer-meta scheduled-route-summary">' +
                  escapeHtml(routeSummary) +
                  '</span>'
                : '') +
            (priceLabel
                ? '<span class="offer-meta earnings-ride-amount-summary">' +
                  escapeHtml(priceLabel) +
                  '</span>'
                : '') +
            '</span>' +
            '<span class="scheduled-ride-chevron" aria-hidden="true">▼</span>' +
            '</button>' +
            '<div class="scheduled-ride-body" id="' +
            escapeHtml(bodyId) +
            '"' +
            (expanded ? '' : ' hidden') +
            '>' +
            rideDetailBodyHtml(ride) +
            '</div>' +
            '<div class="offer-actions overdue-ride-actions">' +
            '<button type="button" class="btn btn-ghost btn-archive-offer" data-offer-id="' +
            escapeHtml(offerId) +
            '">Archiveren</button>' +
            '<button type="button" class="btn btn-accept btn-accept-overdue" data-offer-id="' +
            escapeHtml(offerId) +
            '">Nieuw tijdstip voorstellen</button>' +
            '</div>' +
            '</div>'
        );
    }

    function renderArchivedOfferCard(offer) {
        const ride = offer.ride || {};
        const rideId = ride.id != null ? String(ride.id) : '—';
        const offerId = offer.id != null ? String(offer.id) : '';
        const expandKey = offerId || rideId;
        const expanded = !!archivedRideExpanded[expandKey];
        const selected = !!archivedSelectedIds[offerId];
        const bodyId = 'archived-ride-body-' + expandKey;
        const pickupLabel = formatPickupAt(ridePickupInstant(ride));
        const routeSummary = [ride.pickup_address, ride.dropoff_address]
            .map(function (addr) {
                return addr ? shortAddress(addr) : '';
            })
            .filter(Boolean)
            .join(' → ');
        const priceLabel =
            ride.quoted_price != null && ride.quoted_price !== ''
                ? formatEuro(ride.quoted_price)
                : '';
        return (
            '<div class="card offer-card scheduled-ride-card overdue-ride-card overdue-ride-card--archived' +
            (expanded ? ' is-expanded' : '') +
            (selected ? ' is-selected' : '') +
            '" data-archived-offer-id="' +
            escapeHtml(expandKey) +
            '">' +
            '<div class="archived-ride-row">' +
            '<label class="archived-ride-select">' +
            '<input type="checkbox" class="archived-offer-check" data-offer-id="' +
            escapeHtml(offerId) +
            '"' +
            (selected ? ' checked' : '') +
            ' aria-label="Selecteer rit #' +
            escapeHtml(rideId) +
            '">' +
            '</label>' +
            '<button type="button" class="scheduled-ride-toggle archived-ride-toggle" aria-expanded="' +
            (expanded ? 'true' : 'false') +
            '" aria-controls="' +
            escapeHtml(bodyId) +
            '" data-archived-offer-id="' +
            escapeHtml(expandKey) +
            '">' +
            '<span class="scheduled-ride-toggle-text">' +
            '<span class="offer-badge is-muted">Gearchiveerd</span>' +
            '<span class="offer-title">Rit #' +
            escapeHtml(rideId) +
            '</span>' +
            '<span class="offer-meta scheduled-pickup-at">' +
            escapeHtml(pickupLabel) +
            '</span>' +
            (routeSummary
                ? '<span class="offer-meta scheduled-route-summary">' +
                  escapeHtml(routeSummary) +
                  '</span>'
                : '') +
            (priceLabel
                ? '<span class="offer-meta earnings-ride-amount-summary">' +
                  escapeHtml(priceLabel) +
                  '</span>'
                : '') +
            '</span>' +
            '<span class="scheduled-ride-chevron" aria-hidden="true">▼</span>' +
            '</button>' +
            '</div>' +
            '<div class="scheduled-ride-body archived-ride-body" id="' +
            escapeHtml(bodyId) +
            '"' +
            (expanded ? '' : ' hidden') +
            '>' +
            rideDetailBodyHtml(ride) +
            '</div>' +
            '<div class="offer-actions overdue-ride-actions">' +
            '<button type="button" class="btn btn-danger btn-delete-archived-offer" data-offer-id="' +
            escapeHtml(offerId) +
            '">Verwijderen</button>' +
            '</div>' +
            '</div>'
        );
    }

    function getArchivedSelectedIds() {
        return Object.keys(archivedSelectedIds).filter(function (id) {
            return archivedSelectedIds[id];
        });
    }

    function pruneArchivedSelection() {
        const valid = {};
        (archivedOffers || []).forEach(function (offer) {
            if (offer && offer.id != null && archivedSelectedIds[String(offer.id)]) {
                valid[String(offer.id)] = true;
            }
        });
        archivedSelectedIds = valid;
    }

    function syncArchivedBulkBar() {
        const bulkBar = $('#archived-bulk-bar');
        const selectAll = $('#archived-select-all');
        const selectAllLabel = document.querySelector('.archived-bulk-bar__select-all span');
        const deleteBtn = $('#btn-archived-delete-selected');
        const selected = getArchivedSelectedIds();
        const total = (archivedOffers || []).length;
        if (bulkBar) {
            bulkBar.hidden = total < 1;
        }
        if (selectAll) {
            selectAll.checked = total > 0 && selected.length === total;
            selectAll.indeterminate = selected.length > 0 && selected.length < total;
        }
        if (selectAllLabel) {
            selectAllLabel.textContent =
                total > 0 ? 'Alles selecteren (' + total + ')' : 'Alles selecteren';
        }
        if (deleteBtn) {
            deleteBtn.disabled = selected.length < 1;
            deleteBtn.textContent =
                selected.length < 1
                    ? 'Verwijderen'
                    : selected.length === 1
                      ? '1 verwijderen'
                      : selected.length + ' verwijderen';
        }
    }

    function setArchivedOfferSelected(offerId, selected) {
        const key = String(offerId || '');
        if (!key) {
            return;
        }
        if (selected) {
            archivedSelectedIds[key] = true;
        } else {
            delete archivedSelectedIds[key];
        }
        const card = document.querySelector(
            '.overdue-ride-card--archived[data-archived-offer-id="' + CSS.escape(key) + '"]'
        );
        if (card) {
            card.classList.toggle('is-selected', !!selected);
            const check = card.querySelector('.archived-offer-check');
            if (check) {
                check.checked = !!selected;
            }
        }
        syncArchivedBulkBar();
    }

    function confirmArchiveDelete(count) {
        const n = Math.max(1, parseInt(count, 10) || 1);
        const titleEl = $('#archive-delete-confirm-title');
        const textEl = $('#archive-delete-confirm-text');
        const okBtn = $('#archive-delete-confirm-ok');
        if (titleEl) {
            titleEl.textContent = n === 1 ? 'Weet u het zeker?' : n + ' ritten verwijderen?';
        }
        if (textEl) {
            textEl.textContent =
                n === 1
                    ? 'U staat op het punt deze gearchiveerde rit permanent te verwijderen. Deze wijziging kan niet meer ongedaan worden gemaakt.'
                    : 'U staat op het punt deze ' +
                      n +
                      ' gearchiveerde ritten permanent te verwijderen. Deze wijziging kan niet meer ongedaan worden gemaakt.';
        }
        if (okBtn) {
            okBtn.textContent = n === 1 ? 'Definitief verwijderen' : n + ' definitief verwijderen';
        }
        return promptArchiveDeleteConfirm(n);
    }

    let archiveDeleteConfirmResolve = null;

    function closeArchiveDeleteConfirmDialog(result) {
        const dialog = $('#archive-delete-confirm-dialog');
        if (dialog) {
            dialog.classList.add('driver-dialog--instant');
            dialog.classList.remove('is-open');
            dialog.hidden = true;
            dialog.setAttribute('aria-hidden', 'true');
            requestAnimationFrame(function () {
                dialog.classList.remove('driver-dialog--instant');
            });
        }
        document.body.classList.remove('driver-dialog-open');
        if (archiveDeleteConfirmResolve) {
            const resolve = archiveDeleteConfirmResolve;
            archiveDeleteConfirmResolve = null;
            resolve(!!result);
        }
    }

    function promptArchiveDeleteConfirm(count) {
        const dialog = $('#archive-delete-confirm-dialog');
        const n = Math.max(1, parseInt(count, 10) || 1);
        if (!dialog) {
            return showDriverConfirm(
                (n === 1
                    ? 'U staat op het punt deze gearchiveerde rit permanent te verwijderen.'
                    : 'U staat op het punt deze ' +
                      n +
                      ' gearchiveerde ritten permanent te verwijderen.') +
                    ' Deze wijziging kan niet meer ongedaan worden gemaakt.',
                {
                    title: 'Weet u het zeker?',
                    confirmLabel: 'Definitief verwijderen',
                    danger: true,
                }
            );
        }
        return new Promise(function (resolve) {
            archiveDeleteConfirmResolve = resolve;
            dialog.hidden = false;
            dialog.setAttribute('aria-hidden', 'false');
            dialog.classList.add('is-open');
            document.body.classList.add('driver-dialog-open');
            const okBtn = $('#archive-delete-confirm-ok');
            if (okBtn) {
                okBtn.focus();
            }
        });
    }

    function initArchiveDeleteConfirmDialog() {
        const dialog = $('#archive-delete-confirm-dialog');
        if (!dialog || dialog.dataset.bound === '1') {
            return;
        }
        dialog.dataset.bound = '1';
        const okBtn = $('#archive-delete-confirm-ok');
        const cancelBtn = $('#archive-delete-confirm-cancel');
        const backdrop = dialog.querySelector('[data-archive-delete-dismiss]');
        if (okBtn) {
            okBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                closeArchiveDeleteConfirmDialog(true);
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                closeArchiveDeleteConfirmDialog(false);
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function (ev) {
                ev.preventDefault();
                closeArchiveDeleteConfirmDialog(false);
            });
        }
        document.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Escape' || !dialog.classList.contains('is-open')) {
                return;
            }
            closeArchiveDeleteConfirmDialog(false);
        });
    }

    function toggleArchivedRideCard(offerId) {
        const key = String(offerId || '');
        if (!key) {
            return;
        }
        archivedRideExpanded[key] = !archivedRideExpanded[key];
        const card = document.querySelector(
            '.overdue-ride-card--archived[data-archived-offer-id="' + CSS.escape(key) + '"]'
        );
        if (!card) {
            renderArchivedView();
            return;
        }
        const expanded = !!archivedRideExpanded[key];
        const toggle = card.querySelector('.archived-ride-toggle');
        const body = card.querySelector('.archived-ride-body');
        card.classList.toggle('is-expanded', expanded);
        if (toggle) {
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }
        if (body) {
            body.hidden = !expanded;
        }
    }

    function renderOverdueView() {
        const strip = $('#overdue-strip');
        const list = $('#overdue-rides-list');
        const empty = $('#overdue-empty');
        const btnArchived = $('#btn-empty-show-archived');
        if (!strip || !list) {
            return;
        }
        const cards = overdueReleasedOffers.map(renderOverdueReleasedOfferCard);
        if (!cards.length) {
            list.innerHTML = '';
            if (empty) {
                empty.hidden = false;
            }
            if (btnArchived) {
                btnArchived.hidden = archivedOffers.length < 1;
            }
            return;
        }
        if (empty) {
            empty.hidden = true;
        }
        if (btnArchived) {
            btnArchived.hidden = true;
        }
        list.innerHTML = cards.join('');
    }

    function renderArchivedView() {
        const strip = $('#archived-strip');
        const list = $('#archived-rides-list');
        const empty = $('#archived-empty');
        if (!strip || !list) {
            return;
        }
        pruneArchivedSelection();
        const cards = archivedOffers.map(renderArchivedOfferCard);
        if (!cards.length) {
            list.innerHTML = '';
            archivedSelectedIds = {};
            if (empty) {
                empty.hidden = false;
            }
            syncArchivedBulkBar();
            return;
        }
        if (empty) {
            empty.hidden = true;
        }
        list.innerHTML = cards.join('');
        syncArchivedBulkBar();
    }

    function renderOverdueScheduledRides(rides) {
        overdueScheduledRides = Array.isArray(rides) ? rides.slice() : [];
        renderOverdueView();
    }

    function showScreen(name) {
        screenLogin.classList.toggle('is-active', name === 'login');
        screenDispatch.classList.toggle('is-active', name === 'dispatch');
        if (name === 'dispatch') {
            setMainTab(mainTab || 'requests', { keepInbox: true });
        }
        syncScreenWakeLock();
        if (typeof window.nexaPwaSyncThemeToggleTop === 'function') {
            window.nexaPwaSyncThemeToggleTop();
        }
    }

    function shouldKeepGpsAlive() {
        return !!(token && accountActive && isOnline);
    }

    function shouldKeepScreenAwake() {
        return !!(
            shouldKeepGpsAlive() &&
            screenDispatch &&
            screenDispatch.classList.contains('is-active') &&
            document.visibilityState === 'visible'
        );
    }

    function stopWakeLockMaintenance() {
        if (wakeLockRetryTimer) {
            clearInterval(wakeLockRetryTimer);
            wakeLockRetryTimer = null;
        }
    }

    function startWakeLockMaintenance() {
        stopWakeLockMaintenance();
        if (!shouldKeepScreenAwake()) {
            return;
        }
        const intervalMs = isIosDevice() ? 4000 : 20000;
        wakeLockRetryTimer = setInterval(function () {
            if (!shouldKeepScreenAwake()) {
                return;
            }
            if (!screenWakeLock || screenWakeLock.released) {
                acquireScreenWakeLock();
            } else {
                ensureNoSleepMediaPlaying();
            }
        }, intervalMs);
    }

    function ensureNoSleepMediaPlaying() {
        const videoEl = document.getElementById('nosleep-video');
        if (videoEl && videoEl.paused) {
            videoEl.play().catch(function () {});
        }
        const audioEl = document.getElementById('nosleep-audio');
        if (audioEl && audioEl.paused) {
            audioEl.play().catch(function () {});
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume().catch(function () {});
        }
    }

    function cleanupOrphanNoSleepMedia() {
        document.querySelectorAll('video[title="screen-awake"]').forEach(function (el) {
            try {
                el.pause();
            } catch (e) {
                /* ignore */
            }
            el.remove();
        });
    }

    function stopNoSleepCanvasPulse() {
        if (noSleepRafId != null) {
            cancelAnimationFrame(noSleepRafId);
            noSleepRafId = null;
        }
    }

    function startNoSleepCanvasPulse() {
        stopNoSleepCanvasPulse();
        const canvas = document.getElementById('nosleep-canvas');
        if (!canvas) {
            return;
        }
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }
        let tick = 0;
        function pulse() {
            if (!shouldKeepScreenAwake()) {
                stopNoSleepCanvasPulse();
                return;
            }
            tick += 1;
            ctx.fillStyle = tick % 2 === 0 ? 'rgba(15,23,42,0.02)' : 'rgba(15,23,42,0.01)';
            ctx.fillRect(0, 0, 1, 1);
            noSleepRafId = requestAnimationFrame(pulse);
        }
        pulse();
    }

    function startNoSleepInlineVideo() {
        const videoEl = document.getElementById('nosleep-video');
        if (!videoEl) {
            return false;
        }
        videoEl.setAttribute('playsinline', '');
        videoEl.setAttribute('webkit-playsinline', '');
        videoEl.muted = true;
        videoEl.volume = 0;
        videoEl.loop = true;
        const playPromise = videoEl.play();
        if (playPromise && typeof playPromise.catch === 'function') {
            playPromise.catch(function () {});
        }
        return true;
    }

    function stopNoSleepFallback() {
        stopNoSleepCanvasPulse();
        const videoEl = document.getElementById('nosleep-video');
        if (videoEl) {
            try {
                videoEl.pause();
            } catch (e) {
                /* ignore */
            }
        }
        if (noSleepOscillator) {
            try {
                noSleepOscillator.stop();
            } catch (e) {
                /* al gestopt */
            }
            try {
                noSleepOscillator.disconnect();
            } catch (e) {
                /* ignore */
            }
            noSleepOscillator = null;
        }
        if (noSleepGain) {
            try {
                noSleepGain.disconnect();
            } catch (e) {
                /* ignore */
            }
            noSleepGain = null;
        }
        cleanupOrphanNoSleepMedia();
        const audioEl = document.getElementById('nosleep-audio');
        if (audioEl) {
            audioEl.pause();
            audioEl.currentTime = 0;
        }
    }

    function startNoSleepWebAudio() {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) {
            return false;
        }
        if (!audioCtx) {
            audioCtx = new Ctx();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume().catch(function () {});
        }
        if (noSleepOscillator) {
            return true;
        }
        try {
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            gain.gain.value = 0.0001;
            osc.type = 'sine';
            osc.frequency.value = 1;
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(0);
            noSleepOscillator = osc;
            noSleepGain = gain;
            return true;
        } catch (e) {
            return false;
        }
    }

    function startNoSleepHtmlAudio() {
        const audioEl = document.getElementById('nosleep-audio');
        if (!audioEl) {
            return false;
        }
        audioEl.setAttribute('playsinline', '');
        audioEl.setAttribute('webkit-playsinline', '');
        audioEl.muted = true;
        audioEl.volume = 0;
        const playPromise = audioEl.play();
        if (playPromise && typeof playPromise.catch === 'function') {
            playPromise.catch(function () {});
        }
        return true;
    }

    function tuneKeepAliveMediaForVisibility() {
        const hidden = document.visibilityState !== 'visible';
        const audioEl = document.getElementById('nosleep-audio');
        if (audioEl) {
            audioEl.muted = !hidden;
            audioEl.volume = hidden ? 0.02 : 0;
        }
    }

    function startNoSleepFallback() {
        if (!shouldKeepGpsAlive()) {
            stopNoSleepFallback();
            return;
        }
        cleanupOrphanNoSleepMedia();
        startNoSleepWebAudio();
        startNoSleepHtmlAudio();
        startNoSleepInlineVideo();
        tuneKeepAliveMediaForVisibility();
        if (isIosDevice() && document.visibilityState === 'visible') {
            startNoSleepCanvasPulse();
        }
    }

    function releaseScreenWakeLock() {
        stopWakeLockMaintenance();
        stopNoSleepFallback();
        if (!screenWakeLock) {
            return;
        }
        const lock = screenWakeLock;
        screenWakeLock = null;
        lock.release().catch(function () {});
    }

    function bindScreenWakeLock(lock) {
        if (!lock) {
            return;
        }
        screenWakeLock = lock;
        screenWakeLock.addEventListener('release', function () {
            screenWakeLock = null;
            if (shouldKeepScreenAwake()) {
                acquireScreenWakeLock();
            }
        });
    }

    function requestScreenWakeLockFromGesture() {
        if (!shouldKeepScreenAwake() && !(token && accountActive && isOnline)) {
            return;
        }
        unlockAudio();
        startNoSleepFallback();
        if (!('wakeLock' in navigator)) {
            return;
        }
        try {
            const maybePromise = navigator.wakeLock.request('screen');
            if (maybePromise && typeof maybePromise.then === 'function') {
                maybePromise.then(bindScreenWakeLock).catch(function () {
                    screenWakeLock = null;
                    startNoSleepFallback();
                });
            }
        } catch (e) {
            startNoSleepFallback();
        }
    }

    async function acquireScreenWakeLock() {
        if (!shouldKeepScreenAwake()) {
            return;
        }
        if (screenWakeLock && !screenWakeLock.released) {
            startNoSleepFallback();
            return;
        }
        if (screenWakeLock) {
            screenWakeLock = null;
        }
        if ('wakeLock' in navigator) {
            try {
                bindScreenWakeLock(await navigator.wakeLock.request('screen'));
            } catch (e) {
                screenWakeLock = null;
            }
        }
        startNoSleepFallback();
    }

    function syncScreenWakeLock() {
        if (!shouldKeepScreenAwake()) {
            releaseScreenWakeLock();
            return;
        }
        if (screenWakeLock && screenWakeLock.released) {
            screenWakeLock = null;
        }
        acquireScreenWakeLock();
        startWakeLockMaintenance();
    }

    async function onPageBecameVisible() {
        cleanupOrphanNoSleepMedia();
        const app = document.getElementById('app');
        if (app) {
            app.style.visibility = 'visible';
        }
        if (screenWakeLock) {
            try {
                await screenWakeLock.release();
            } catch (e) {
                /* al vrijgegeven door het systeem */
            }
            screenWakeLock = null;
        }
        stopNoSleepFallback();
        if (shouldKeepScreenAwake()) {
            syncScreenWakeLock();
        }
    }

    function vibrate(pattern) {
        if (navigator.vibrate) {
            navigator.vibrate(pattern);
        }
    }

    function unlockAudio() {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) {
            return;
        }
        if (!audioCtx) {
            audioCtx = new Ctx();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume().catch(function () {});
        }
    }

    function playNewRideSound(toneKey) {
        try {
            unlockAudio();
            if (!audioCtx) {
                return;
            }
            const ctx = audioCtx;
            const start = ctx.currentTime;
            const tone = normalizeRideAlertTone(toneKey || getRideAlertTone());
            if (tone === 'chime') {
                playRideToneNotes(ctx, start, [
                    { freq: 1047, type: 'sine', delay: 0, dur: 0.28, peak: 0.32 },
                    { freq: 1319, type: 'sine', delay: 0.16, dur: 0.3, peak: 0.3 },
                    { freq: 1568, type: 'sine', delay: 0.32, dur: 0.38, peak: 0.28 },
                ]);
                return;
            }
            if (tone === 'alert') {
                playRideToneNotes(ctx, start, [
                    { freq: 1480, type: 'sine', delay: 0, dur: 0.09, peak: 0.38 },
                    { freq: 1480, type: 'sine', delay: 0.13, dur: 0.09, peak: 0.38 },
                    { freq: 1480, type: 'sine', delay: 0.26, dur: 0.09, peak: 0.38 },
                    { freq: 1760, type: 'sine', delay: 0.39, dur: 0.12, peak: 0.4 },
                ]);
                return;
            }
            if (tone === 'soft') {
                playRideToneNotes(ctx, start, [
                    { freq: 392, type: 'triangle', delay: 0, dur: 0.32, peak: 0.22 },
                    { freq: 494, type: 'triangle', delay: 0.22, dur: 0.36, peak: 0.2 },
                ]);
                return;
            }
            if (tone === 'siren') {
                playRideToneSiren(ctx, start);
                return;
            }
            playRideToneNotes(ctx, start, [
                { freq: 880, type: 'sine', delay: 0, dur: 0.14, peak: 0.4 },
                { freq: 1174, type: 'sine', delay: 0.18, dur: 0.14, peak: 0.4 },
            ]);
        } catch (e) {
            /* Audio niet beschikbaar (o.a. stille modus iOS). */
        }
    }

    function playRideToneNotes(ctx, start, notes) {
        notes.forEach(function (note) {
            const t = start + (note.delay || 0);
            const dur = note.dur || 0.14;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = note.type || 'sine';
            osc.frequency.value = note.freq;
            gain.gain.setValueAtTime(0.0001, t);
            gain.gain.exponentialRampToValueAtTime(note.peak || 0.3, t + 0.025);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + dur);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(t);
            osc.stop(t + dur + 0.02);
        });
    }

    function playRideToneSiren(ctx, start) {
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(620, start);
        osc.frequency.linearRampToValueAtTime(1180, start + 0.28);
        osc.frequency.linearRampToValueAtTime(620, start + 0.56);
        osc.frequency.linearRampToValueAtTime(1100, start + 0.84);
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(0.28, start + 0.05);
        gain.gain.setValueAtTime(0.26, start + 0.78);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.98);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(start);
        osc.stop(start + 1);
    }

    let serviceWorkerReadyPromise = null;

    function isIosDevice() {
        return /iPhone|iPad|iPod/i.test(navigator.userAgent)
            || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    }

    function isStandalonePwa() {
        if (window.navigator.standalone === true) {
            return true;
        }
        try {
            if (window.matchMedia('(display-mode: standalone)').matches) {
                return true;
            }
            if (window.matchMedia('(display-mode: fullscreen)').matches) {
                return true;
            }
        } catch (e) {
            /* matchMedia niet beschikbaar */
        }
        return false;
    }

    function isInBrowserTabOnIos() {
        if (!isIosDevice()) {
            return false;
        }
        if (isStandalonePwa()) {
            return false;
        }
        if (window.navigator.standalone === true) {
            return false;
        }
        return true;
    }

    function notificationsApiAvailable() {
        try {
            return typeof window !== 'undefined' && 'Notification' in window;
        } catch (e) {
            return false;
        }
    }

    function getNotificationPermission() {
        if (!notificationsApiAvailable()) {
            return 'unsupported';
        }
        try {
            return Notification.permission || 'default';
        } catch (e) {
            return 'unsupported';
        }
    }

    function canRequestNotificationsOnDevice() {
        if (isInBrowserTabOnIos()) {
            return false;
        }
        if (notificationsApiAvailable()) {
            return true;
        }
        return isIosDevice() && 'serviceWorker' in navigator;
    }

    function isNotificationsHintDismissed() {
        return localStorage.getItem(NOTIFICATIONS_HINT_DISMISSED_KEY) === '1';
    }

    function dismissNotificationsHint() {
        localStorage.setItem(NOTIFICATIONS_HINT_DISMISSED_KEY, '1');
        showNotificationsFeedback('');
        updateNotificationsHint();
    }

    function showNotificationsFeedback(message, type) {
        const el = $('#notifications-feedback');
        const textEl = $('#notifications-feedback-text');
        if (!el) {
            return;
        }
        if (!message) {
            el.hidden = true;
            if (textEl) {
                textEl.textContent = '';
            }
            el.classList.remove('is-error', 'is-success');
            return;
        }
        if (textEl) {
            textEl.textContent = message;
        } else {
            el.textContent = message;
        }
        el.hidden = false;
        el.classList.remove('is-error', 'is-success');
        if (type === 'error') {
            el.classList.add('is-error');
        } else if (type === 'success') {
            el.classList.add('is-success');
        }
    }

    function ensureServiceWorkerReady() {
        if (!('serviceWorker' in navigator)) {
            return Promise.resolve(null);
        }
        if (!serviceWorkerReadyPromise) {
            serviceWorkerReadyPromise = navigator.serviceWorker
                .register('/taxi-chauffeur-sw.js', { scope: '/' })
                .then(function (reg) {
                    if (reg.active) {
                        return reg;
                    }
                    const installing = reg.installing || reg.waiting;
                    if (!installing) {
                        return reg;
                    }
                    return new Promise(function (resolve) {
                        installing.addEventListener('statechange', function () {
                            if (installing.state === 'activated') {
                                resolve(reg);
                            }
                        });
                    });
                })
                .then(function () {
                    return navigator.serviceWorker.ready;
                })
                .catch(function () {
                    serviceWorkerReadyPromise = null;
                    return null;
                });
        }
        return serviceWorkerReadyPromise;
    }

    function isInstallHintDismissed() {
        return localStorage.getItem(INSTALL_HINT_DISMISSED_KEY) === '1';
    }

    function dismissInstallHint() {
        localStorage.setItem(INSTALL_HINT_DISMISSED_KEY, '1');
        updateInstallHint();
    }

    function updateGuideHint() {
        const hint = $('#guide-hint');
        const profileLink = $('#profile-guide-link');
        const guideUrl = cfg.guideUrl || '/taxi/chauffeur/handleiding';
        if (profileLink) {
            profileLink.setAttribute('href', guideUrl);
            profileLink.hidden = false;
        }
        const openGuide = $('#btn-open-guide');
        if (openGuide) {
            openGuide.setAttribute('href', guideUrl);
        }
        if (!hint) {
            return;
        }
        hint.hidden = localStorage.getItem(GUIDE_HINT_DISMISSED_KEY) === '1';
        if (typeof window.nexaPwaSyncThemeToggleTop === 'function') {
            window.nexaPwaSyncThemeToggleTop();
        }
    }

    function dismissGuideHint() {
        localStorage.setItem(GUIDE_HINT_DISMISSED_KEY, '1');
        updateGuideHint();
    }

    function updateInstallHint() {
        const hint = $('#install-app-hint');
        const hintText = $('#install-app-hint-text');
        const btn = $('#btn-install-app');
        if (!hint) {
            return;
        }
        if (isStandalonePwa() || isInstallHintDismissed()) {
            hint.hidden = true;
            if (typeof window.nexaPwaSyncThemeToggleTop === 'function') {
                window.nexaPwaSyncThemeToggleTop();
            }
            return;
        }
        if (deferredInstallPrompt) {
            hint.hidden = false;
            if (hintText) {
                hintText.textContent =
                    'Installeer de chauffeur-app op je telefoon voor snellere toegang en betere meldingen.';
            }
            if (btn) {
                btn.hidden = false;
            }
            if (typeof window.nexaPwaSyncThemeToggleTop === 'function') {
                window.nexaPwaSyncThemeToggleTop();
            }
            return;
        }
        if (isInBrowserTabOnIos()) {
            hint.hidden = false;
            if (hintText) {
                hintText.textContent =
                    'Installeer via Safari → Deel → Zet op beginscherm om de app op je telefoon te gebruiken.';
            }
            if (btn) {
                btn.hidden = true;
            }
            if (typeof window.nexaPwaSyncThemeToggleTop === 'function') {
                window.nexaPwaSyncThemeToggleTop();
            }
            return;
        }
        hint.hidden = true;
        if (typeof window.nexaPwaSyncThemeToggleTop === 'function') {
            window.nexaPwaSyncThemeToggleTop();
        }
    }

    async function handleInstallAppClick() {
        if (!deferredInstallPrompt) {
            return;
        }
        try {
            deferredInstallPrompt.prompt();
            const choice = await deferredInstallPrompt.userChoice;
            deferredInstallPrompt = null;
            if (choice && choice.outcome === 'accepted') {
                dismissInstallHint();
                return;
            }
        } catch (e) {
            deferredInstallPrompt = null;
        }
        updateInstallHint();
    }

    function updateNotificationsHint() {
        const hint = $('#notifications-hint');
        const hintText = $('#notifications-hint-text');
        const btn = $('#btn-enable-notifications');
        if (!hint) {
            return;
        }
        if (!isOnline || !accountActive) {
            hint.hidden = true;
            return;
        }
        const permission = getNotificationPermission();
        if (permission === 'granted') {
            localStorage.removeItem(NOTIFICATIONS_HINT_DISMISSED_KEY);
            hint.hidden = true;
            showNotificationsFeedback('');
            return;
        }
        if (isNotificationsHintDismissed()) {
            hint.hidden = true;
            return;
        }
        hint.hidden = false;
        if (btn) {
            btn.hidden = false;
            btn.disabled = false;
        }
        if (isInBrowserTabOnIos() || permission === 'unsupported') {
            if (hintText) {
                hintText.textContent =
                    'Open de app via het icoon op je beginscherm (Safari → Deel → Zet op beginscherm). Meldingen werken niet in een Safari-tab.';
            }
            if (btn) {
                btn.hidden = true;
            }
            return;
        }
        if (hintText) {
            if (permission === 'denied') {
                hintText.textContent =
                    'Meldingen zijn geblokkeerd. Sta ze toe in de instellingen van je telefoon voor deze app (Instellingen → Meldingen).';
            } else {
                hintText.textContent =
                    'Voor een geluid en melding op je telefoon bij nieuwe ritten: sta meldingen toe voor deze app.';
            }
        }
    }

    function requestNotificationPermissionFromGesture(done) {
        function finish(result) {
            updateNotificationsHint();
            if (typeof done === 'function') {
                done(result);
            }
        }
        if (isInBrowserTabOnIos()) {
            showNotificationsFeedback(
                'Open de app via het icoon op je beginscherm (niet via Safari).',
                'error'
            );
            finish('unsupported');
            return;
        }
        if (!notificationsApiAvailable()) {
            showNotificationsFeedback(
                'Meldingen zijn niet beschikbaar. Werk iOS bij (16.4+) en open de app via het beginscherm-icoon.',
                'error'
            );
            finish('unsupported');
            return;
        }
        const permission = getNotificationPermission();
        if (permission === 'granted') {
            finish('granted');
            return;
        }
        if (permission === 'denied') {
            showNotificationsFeedback(
                'Meldingen zijn geblokkeerd. Ga naar Instellingen → Meldingen en sta meldingen toe voor deze app.',
                'error'
            );
            finish('denied');
            return;
        }
        try {
            const maybePromise = Notification.requestPermission();
            if (maybePromise && typeof maybePromise.then === 'function') {
                maybePromise.then(finish).catch(function () {
                    showNotificationsFeedback(
                        'Kon meldingen niet aanvragen. Probeer opnieuw of open de app via het beginscherm-icoon.',
                        'error'
                    );
                    finish('error');
                });
                return;
            }
        } catch (e) {
            /* oudere browsers: callback-variant */
        }
        try {
            Notification.requestPermission(finish);
        } catch (e2) {
            showNotificationsFeedback(
                'Kon meldingen niet aanvragen. Open de app via het icoon op je beginscherm en probeer opnieuw.',
                'error'
            );
            finish('error');
        }
    }

    async function handleEnableNotificationsClick(ev) {
        if (ev) {
            ev.preventDefault();
            ev.stopPropagation();
        }
        unlockAudio();
        showNotificationsFeedback('');

        await ensureServiceWorkerReady();

        if (!notificationsApiAvailable()) {
            showNotificationsFeedback(
                'Meldingen zijn niet beschikbaar op dit toestel. Gebruik iOS 16.4 of nieuwer en open via het beginscherm-icoon.',
                'error'
            );
            return;
        }

        if (getNotificationPermission() === 'granted') {
            showNotificationsFeedback('Meldingen staan al aan.', 'success');
            updateNotificationsHint();
            showRideOfferPhoneNotification({
                id: 'permission-test',
                ride: { pickup_address: 'Meldingen werken', quoted_price: null },
            });
            return;
        }

        requestNotificationPermissionFromGesture(function (result) {
            if (result === 'granted') {
                ensureServiceWorkerReady().then(function () {
                    showRideOfferPhoneNotification({
                        id: 'permission-test',
                        ride: { pickup_address: 'Meldingen ingeschakeld', quoted_price: null },
                    });
                    showNotificationsFeedback('Meldingen zijn ingeschakeld.', 'success');
                });
                return;
            }
            if (result === 'default') {
                showNotificationsFeedback(
                    'Geen toestemming gegeven. Tik opnieuw op de knop en kies Toestaan in het venster van je telefoon.',
                    'error'
                );
            }
        });
    }

    async function prepareDriverAlerts() {
        unlockAudio();
        await ensureServiceWorkerReady();
        updateNotificationsHint();
    }

    function rideOfferNotificationBody(ride) {
        if (!ride) {
            return 'Reageer snel om de rit te accepteren.';
        }
        const pickup = ride.pickup_address != null ? String(ride.pickup_address).trim() : '';
        const price = ride.quoted_price != null ? formatEuro(ride.quoted_price) : '';
        if (pickup && price) {
            return pickup + ' · ' + price;
        }
        if (pickup) {
            return pickup;
        }
        if (price) {
            return price;
        }
        return 'Reageer snel om de rit te accepteren.';
    }

    async function showRideOfferPhoneNotification(offer, options) {
        const opts = options || {};
        if (!notificationsApiAvailable() || getNotificationPermission() !== 'granted' || !offer) {
            return;
        }
        const ride = offer.ride || {};
        const waiting = !!opts.waiting || isOfferWaiting(offer);
        const title = waiting ? 'Rit wacht op chauffeur' : 'Nieuwe rit beschikbaar';
        const secWait = offer.seconds_waiting != null ? Math.max(0, Math.floor(Number(offer.seconds_waiting))) : 0;
        const body = waiting
            ? 'Wacht al ' + formatDuration(secWait) + ' — ' + rideOfferNotificationBody(ride)
            : rideOfferNotificationBody(ride);
        const icon = cfg.notificationIcon || '/favicon.ico';
        const rideId = offerRideId(offer);
        const tag = waiting && rideId
            ? 'nexa-ride-waiting-' + String(rideId)
            : 'nexa-ride-offer-' + String(offer.id);
        const url = cfg.appUrl || '/taxi/chauffeur';
        const payload = {
            type: 'SHOW_RIDE_NOTIFICATION',
            title: title,
            body: body,
            icon: icon,
            tag: tag,
            url: url,
        };

        try {
            if ('serviceWorker' in navigator) {
                const reg = await navigator.serviceWorker.ready;
                if (reg && reg.active) {
                    reg.active.postMessage(payload);
                    return;
                }
            }
        } catch (e) {}
        try {
            new Notification(title, { body: body, icon: icon, tag: tag });
        } catch (e) {}
    }

    async function showOnlineGpsNotification() {
        if (!shouldKeepGpsAlive() || !notificationsApiAvailable() || getNotificationPermission() !== 'granted') {
            return;
        }
        const payload = {
            type: 'SHOW_ONLINE_GPS_NOTIFICATION',
            title: 'Je bent online',
            body: 'Locatie wordt gedeeld met de GPS-tracker, ook als de app op de achtergrond staat.',
            icon: cfg.notificationIcon || '/favicon.ico',
            tag: 'nexa-driver-online-gps',
            url: cfg.appUrl || '/taxi/chauffeur',
        };
        try {
            if ('serviceWorker' in navigator) {
                const reg = await navigator.serviceWorker.ready;
                if (reg && reg.active) {
                    reg.active.postMessage(payload);
                }
            }
        } catch (e) {}
    }

    async function hideOnlineGpsNotification() {
        try {
            if ('serviceWorker' in navigator) {
                const reg = await navigator.serviceWorker.ready;
                if (reg && reg.active) {
                    reg.active.postMessage({ type: 'HIDE_ONLINE_GPS_NOTIFICATION', tag: 'nexa-driver-online-gps' });
                }
                if (reg && typeof reg.getNotifications === 'function') {
                    const notes = await reg.getNotifications({ tag: 'nexa-driver-online-gps' });
                    notes.forEach(function (note) { note.close(); });
                }
            }
        } catch (e) {}
    }

    function notifyRideWaitingAttention(offer) {
        if (!offer || !offer.id || !isOnline || !accountActive || currentActiveRide) {
            return;
        }
        const rideId = offerRideId(offer);
        if (rideId && notifiedWaitingRideIds.has(rideId)) {
            if (offerSecondsRemaining(offer) <= 90) {
                return;
            }
            notifiedWaitingRideIds.delete(rideId);
        }
        if (rideId) {
            notifiedWaitingRideIds.add(rideId);
        }
        playNewRideSound();
        vibrate([200, 100, 200, 100, 200]);
        showNewRideAlert(true, true);
        showRideOfferPhoneNotification(offer, { waiting: true });
    }

    function notifyNewRideOffer(offer) {
        if (!offer || !offer.id || !isOnline || !accountActive || currentActiveRide) {
            return;
        }
        if (isOfferWaiting(offer)) {
            notifyRideWaitingAttention(offer);
            return;
        }
        playNewRideSound();
        vibrate([120, 60, 120, 60, 200]);
        showRideOfferPhoneNotification(offer);
    }

    function onOfferEnteredWaitingState(offer) {
        if (!offer) {
            return;
        }
        offer.is_waiting = true;
        markRideAsWaiting(offer);
        updateOfferUrgencyUi(offer);
        notifyRideWaitingAttention(offer);
        refreshInbox();
    }

    function onOfferFirstSeen(offer) {
        if (!offer || !offer.id || notifiedOfferIds.has(offer.id)) {
            return;
        }
        notifiedOfferIds.add(offer.id);
        lastNotifiedOfferId = offer.id;
        notifyNewRideOffer(offer);
    }

    function detectNewOffersInInbox(offers) {
        if (!isOnline || !accountActive || currentActiveRide) {
            return;
        }
        const fresh = (offers || []).filter(function (offer) {
            return offer && offer.id && !notifiedOfferIds.has(offer.id);
        });
        if (!fresh.length) {
            return;
        }
        fresh.forEach(function (offer) {
            notifiedOfferIds.add(offer.id);
        });
        lastNotifiedOfferId = fresh[fresh.length - 1].id;
        const priority = fresh.find(function (o) {
            return isOfferWaiting(o);
        }) || fresh[0];
        if (isOfferWaiting(priority)) {
            notifyRideWaitingAttention(priority);
        } else {
            notifyNewRideOffer(priority);
        }
    }

    function clearOfferNotificationState() {
        notifiedOfferIds.clear();
        notifiedWaitingRideIds.clear();
        waitingRideIds.clear();
        lastNotifiedOfferId = null;
    }

    function formatEuro(amount) {
        if (amount == null || amount === '') {
            return '';
        }
        return '€\u00a0' + Number(amount).toLocaleString('nl-NL', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function showNewRideAlert() {
        // Banner verwijderd; urgentie blijft via rode stip + alert-geluid.
    }

    function offerRideId(offer) {
        return offer && offer.ride && offer.ride.id ? offer.ride.id : null;
    }

    function formatDuration(seconds) {
        const sec = Math.max(0, Math.floor(Number(seconds) || 0));
        if (sec < 60) {
            return sec + ' s';
        }
        const min = Math.floor(sec / 60);
        const rest = sec % 60;
        if (min < 60) {
            return rest > 0 ? min + ' min ' + rest + ' s' : min + ' min';
        }
        const hr = Math.floor(min / 60);
        const minRest = min % 60;
        return minRest > 0 ? hr + ' u ' + minRest + ' min' : hr + ' u';
    }

    function markRideAsWaiting(offer) {
        const rideId = offerRideId(offer);
        if (rideId) {
            waitingRideIds.add(rideId);
        }
    }

    function unmarkRideAsWaiting(rideId) {
        if (rideId) {
            waitingRideIds.delete(rideId);
        }
    }

    function syncWaitingRideIdsFromOffers(offers) {
        const activeRideIds = new Set();
        (offers || []).forEach(function (offer) {
            const rideId = offerRideId(offer);
            if (!rideId) {
                return;
            }
            activeRideIds.add(rideId);
            if (offer.is_waiting) {
                waitingRideIds.add(rideId);
            }
        });
        waitingRideIds.forEach(function (rideId) {
            if (!activeRideIds.has(rideId)) {
                waitingRideIds.delete(rideId);
            }
        });
    }

    function isOfferPickupOverdue(offer) {
        if (!offer) {
            return false;
        }
        if (offer.is_pickup_overdue) {
            return true;
        }
        const ride = offer.ride || {};
        if (ride.is_pickup_overdue || ride.is_scheduled_overdue) {
            return true;
        }
        const pickupAt = ride.pickup_at || contractRideScheduleInstant(ride);
        if (!pickupAt) {
            return false;
        }
        const ms = parseIsoMs(pickupAt);
        return Number.isFinite(ms) && ms < Date.now();
    }

    function isOfferWaiting(offer) {
        if (!offer) {
            return false;
        }
        if (isOfferPickupOverdue(offer)) {
            markRideAsWaiting(offer);
            return true;
        }
        const rideId = offerRideId(offer);
        if (rideId && waitingRideIds.has(rideId)) {
            return true;
        }
        if (offer.is_waiting) {
            markRideAsWaiting(offer);
            return true;
        }
        if (offerSecondsRemaining(offer) <= 0) {
            markRideAsWaiting(offer);
            return true;
        }
        const secWaiting = offerSecondsWaiting(offer);
        if (secWaiting >= configuredOfferTtlSeconds) {
            markRideAsWaiting(offer);
            return true;
        }
        return false;
    }

    function syncAllPendingOffersWaitingState(options) {
        const opts = options || {};
        (pendingOffers || []).forEach(function (offer) {
            if (!offer) {
                return;
            }
            const rideId = offerRideId(offer);
            const wasTracked = rideId && waitingRideIds.has(rideId);
            if (!isOfferWaiting(offer)) {
                return;
            }
            const wasFlagged = !!offer.is_waiting || wasTracked;
            offer.is_waiting = true;
            markRideAsWaiting(offer);
            if (!wasFlagged && opts.notify) {
                notifyRideWaitingAttention(offer);
            }
        });
    }

    let offerAcceptInFlight = false;

    function setOfferActionButtonsDisabled(disabled, loadingBtn) {
        ['#btn-accept', '#btn-decline', '.btn-accept-declined', '.btn-accept-overdue'].forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (el) {
                if (!disabled) {
                    clearButtonLoading(el);
                    el.disabled = false;
                    if (el.id === 'btn-accept') {
                        syncOfferAcceptButton(currentOffer);
                    }
                    return;
                }
                if (loadingBtn && el === loadingBtn) {
                    setButtonLoading(el, true);
                } else {
                    clearButtonLoading(el);
                    el.disabled = true;
                }
            });
        });
    }

    function setQueueNavDisabled(index, total) {
        const multiple = total > 1;
        const prevBtn = $('#btn-offer-prev');
        const nextBtn = $('#btn-offer-next');
        if (prevBtn) {
            prevBtn.disabled = !multiple || index <= 0;
        }
        if (nextBtn) {
            nextBtn.disabled = !multiple || index >= total - 1;
        }
    }

    function countWaitingOffersInQueue() {
        return (pendingOffers || []).filter(function (offer) {
            return isOfferWaiting(offer);
        }).length;
    }

    function updateOfferQueueUi(index, total) {
        const nav = $('#offer-queue-nav');
        const hint = $('#offer-queue-hint');
        const label = $('#offer-queue-label');
        const title = $('#offer-title');
        const multiple = total > 1;
        const queueOffer = pendingOffers[index] || null;
        const currentWaiting = queueOffer ? isOfferWaiting(queueOffer) : false;
        const waitingCount = countWaitingOffersInQueue();

        if (title) {
            const rideForTitle =
                (queueOffer && queueOffer.ride) || (currentOffer && currentOffer.ride) || null;
            updateOfferTitle(rideForTitle, index, total);
            updateOfferReturnMeta(rideForTitle);
        }
        if (nav) {
            nav.hidden = !multiple;
        }
        if (hint) {
            hint.hidden = !multiple;
            if (multiple) {
                if (waitingCount > 1) {
                    hint.textContent = waitingCount + ' ritten in de wachtrij — reactietijd verlopen. Reageer op elke rit.';
                } else if (currentWaiting) {
                    hint.textContent = 'Reactietijd verlopen. Blader door de wachtrij voor andere openstaande ritten.';
                } else {
                    hint.textContent = 'Je reageert op deze rit. Andere openstaande ritten blijven wachten tot je afwijst of accepteert.';
                }
            }
        }
        if (label) {
            let labelText = 'Rit ' + (index + 1) + ' van ' + total;
            if (currentWaiting) {
                labelText += ' · verlopen';
            }
            label.textContent = labelText;
        }
        setQueueNavDisabled(index, total);
    }

    function showOfferAtIndex(index) {
        const visible = filterOffersByKind(pendingOffers);
        if (!visible.length) {
            renderOffer(null);
            return;
        }
        syncAllPendingOffersWaitingState();
        const idx = Math.max(0, Math.min(index, visible.length - 1));
        offerQueueIndex = idx;
        renderOffer(visible[idx], idx, visible.length, { skipNotify: true });
    }

    function isIosAwakeHintDismissed() {
        return localStorage.getItem(IOS_AWAKE_HINT_DISMISSED_KEY) === '1';
    }

    function dismissIosAwakeHint() {
        localStorage.setItem(IOS_AWAKE_HINT_DISMISSED_KEY, '1');
        updateIosAwakeHint();
    }

    function updateIosAwakeHint() {
        const el = $('#ios-awake-hint');
        if (!el) {
            return;
        }
        el.hidden = !(
            isIosDevice() &&
            token &&
            accountActive &&
            isOnline &&
            !isIosAwakeHintDismissed()
        );
    }

    function setOnlineUi() {
        const toggle = $('#online-toggle');
        const label = $('#online-label');
        if (toggle) {
            toggle.classList.toggle('is-on', isOnline);
            toggle.setAttribute('aria-pressed', isOnline ? 'true' : 'false');
            toggle.setAttribute('aria-label', isOnline ? 'Online' : 'Offline');
            toggle.disabled = !accountActive;
        }
        if (label) {
            label.textContent = isOnline ? 'Online' : 'Offline';
        }
        if (!isOnline && (isSecondaryInboxView(inboxView))) {
            setInboxView('offers');
        }
        syncToolbarNavVisibility();
        syncActiveRideJumpButton();
        updateEmptyState();
        updateNotificationsHint();
        updateIosAwakeHint();
    }

    let mainTab = 'requests';

    function isIsoDate(value) {
        return typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value);
    }

    function persistUiState() {
        try {
            sessionStorage.setItem(
                UI_STATE_KEY,
                JSON.stringify({
                    tab: mainTab,
                    inboxView: inboxView,
                    planningView: planningView,
                    planningSelectedDate: planningSelectedDate,
                    planningWeekFrom: planningWeekFrom,
                    rideKindFilter: rideKindFilter,
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
                mainTab = data.tab;
            }
            if (data.inboxView === 'offers' || data.inboxView === 'declined' || data.inboxView === 'overdue' || data.inboxView === 'archived') {
                inboxView = data.inboxView;
            }
            if (data.planningView === 'week' || data.planningView === 'day') {
                planningView = data.planningView;
            }
            if (VALID_RIDE_KINDS.indexOf(data.rideKindFilter) >= 0) {
                rideKindFilter = data.rideKindFilter;
                try {
                    localStorage.setItem(RIDE_KIND_KEY, rideKindFilter);
                } catch (e) {
                    /* ignore */
                }
            }
            if (isIsoDate(data.planningSelectedDate)) {
                planningSelectedDate = data.planningSelectedDate;
            }
            if (isIsoDate(data.planningWeekFrom)) {
                planningWeekFrom = data.planningWeekFrom;
            }
        } catch (e) {
            /* ignore */
        }
    }

    function syncToolbarNavVisibility() {
        const toolbarNav = $('#toolbar-nav');
        if (toolbarNav) {
            // Altijd zichtbaar op Aanvragen, Ritten, Planning, Inkomsten en Profiel (zolang online).
            toolbarNav.hidden = !isOnline;
        }
    }
    function setMainTab(tab, options) {
        const opts = options || {};
        let next = VALID_TABS.indexOf(tab) !== -1 ? tab : 'requests';
        if (next === 'earnings' && !canViewEarnings) {
            next = 'requests';
        }
        // Overlay-pagina's (betaling/factuur) sluiten bij tabwissel; header/footer blijven.
        if (isPaymentPanelOpen() || isInvoicePanelOpen()) {
            const payment = $('#payment-panel');
            if (payment) {
                payment.classList.remove('is-open');
                payment.hidden = true;
            }
            const invoice = $('#invoice-panel');
            if (invoice) {
                invoice.classList.remove('is-open');
                invoice.hidden = true;
            }
            stopPaymentPoll();
            hidePaymentQr();
            setDispatchOverlayOpen(false);
        }
        // Weg van de ritdetail: inklappen zodat polling/andere tabs niet terugtrekken.
        if (
            next !== 'trips' &&
            currentActiveRide &&
            isDriverInProgressRide(currentActiveRide) &&
            !activeRideInboxCollapsed
        ) {
            activeRideInboxCollapsed = true;
            viewingActiveRideId = null;
            setActiveRideUiVisible(false);
            renderAssignedRidesOverview(currentActiveRide, parkedAssignedRides);
            renderScheduledRides(scheduledRides);
        }
        mainTab = next;
        if (screenDispatch) {
            screenDispatch.classList.toggle('is-nav-tab', next === 'navigation');
        }
        document.querySelectorAll('[data-main-tab-panel]').forEach(function (panel) {
            const key = panel.getAttribute('data-main-tab-panel');
            panel.hidden = key !== next;
        });
        document.querySelectorAll('[data-main-tab]').forEach(function (btn) {
            const active = btn.getAttribute('data-main-tab') === next;
            btn.classList.toggle('is-active', active);
            if (active) {
                btn.setAttribute('aria-current', 'page');
            } else {
                btn.removeAttribute('aria-current');
            }
        });
        syncToolbarNavVisibility();
        if (next === 'requests') {
            if (!opts.keepInbox) {
                // Onderbalk Aanvragen toont altijd nieuwe ritaanvragen, niet Verlopen/Archief/Afgewezen.
                setInboxView('offers');
            } else {
                setInboxView(inboxView);
            }
            updateUnclaimedBanner(unclaimedRides);
            updateEmptyState();
        }
        if (next === 'trips') {
            // Altijd opnieuw tekenen vanuit state, zodat de tab nooit “leeg” blijft na navigatie.
            if (currentActiveRide && isDriverInProgressRide(currentActiveRide) && activeRideInboxCollapsed) {
                renderAssignedRidesOverview(currentActiveRide, parkedAssignedRides);
            } else if (currentActiveRide && isDriverInProgressRide(currentActiveRide)) {
                renderActiveRide(currentActiveRide);
            } else {
                renderAssignedRidesOverview(null, []);
            }
            renderScheduledRides(scheduledRides);
        }
        syncTripsEmptyState();
        if (next === 'earnings') {
            loadEarnings(earningsDate);
        }
        if (next === 'planning') {
            loadPlanning();
        }
        if (next === 'navigation') {
            showNavigationTab();
        }
        if (next === 'profile') {
            if (window.nexaPwaAccent) {
                window.nexaPwaAccent.apply(window.nexaPwaAccent.current());
            }
            syncRideTonePicker(getRideAlertTone());
        }
        persistUiState();
        syncActiveRideJumpButton();
    }

    function tripsListHasContent() {
        const overdueOnly = (overdueScheduledRides || []).filter(function (ride) {
            if (!ride || ride.id == null) {
                return false;
            }
            if (isOpenPickupProposalRide(ride)) {
                return false;
            }
            return !(scheduledRides || []).some(function (item) {
                return String(item.id) === String(ride.id);
            });
        });
        const scheduledCount = (scheduledRides || []).length + overdueOnly.length;
        if (scheduledCount > 0) {
            return true;
        }
        if (currentActiveRide && isDriverInProgressRide(currentActiveRide)) {
            return true;
        }
        if (Array.isArray(parkedAssignedRides) && parkedAssignedRides.length > 0) {
            return true;
        }
        return false;
    }

    function syncTripsEmptyState() {
        const tripsEmpty = $('#trips-empty');
        if (!tripsEmpty) {
            return;
        }
        if (mainTab !== 'trips') {
            tripsEmpty.hidden = true;
            return;
        }
        tripsEmpty.hidden = tripsListHasContent();
    }

    function applyEarningsPermissions(permissions) {
        const perms = permissions || {};
        canViewEarnings = !!perms.earnings_view;
        canViewMonthEarnings = !!perms.earnings_view_month;
        const nav = $('#nav-tab-earnings');
        if (nav) {
            nav.hidden = !canViewEarnings;
        }
        if (!canViewEarnings && mainTab === 'earnings') {
            setMainTab('requests');
        }
    }

    function todayLocalIsoDate() {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function shiftIsoDate(isoDate, deltaDays) {
        const parts = String(isoDate || '').split('-');
        if (parts.length !== 3) {
            return todayLocalIsoDate();
        }
        const dt = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        dt.setDate(dt.getDate() + deltaDays);
        const y = dt.getFullYear();
        const m = String(dt.getMonth() + 1).padStart(2, '0');
        const d = String(dt.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function planningMondayIso(iso) {
        const key = iso || todayContractDateKey();
        const parts = String(key).split('-');
        const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 12);
        if (isNaN(d.getTime())) {
            return todayContractDateKey();
        }
        const day = d.getDay();
        const diff = day === 0 ? -6 : 1 - day;
        d.setDate(d.getDate() + diff);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const da = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + da;
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

    function formatPlanningDayTitle(isoDate) {
        const d = new Date(isoDate + 'T12:00:00');
        if (isNaN(d.getTime())) {
            return isoDate || '';
        }
        return d.toLocaleDateString('nl-NL', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
        });
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
        renderPlanning(planningPayload);
        if (planningView === 'week') {
            scrollPlanningToSelectedDay();
        }
    }

    function planningShiftsHtml(day) {
        const shifts = (day && Array.isArray(day.shifts) ? day.shifts : []).slice();
        if (!shifts.length) {
            return '';
        }
        return shifts
            .map(function (shift) {
                const start = shift.start_time || formatStopTime(shift.start);
                const end = shift.end_time || formatStopTime(shift.end);
                const vehicle = shift.vehicle_label ? String(shift.vehicle_label).trim() : '';
                const notes = shift.notes ? String(shift.notes).trim() : '';
                return (
                    '<div class="planning-shift" role="note">' +
                    '<div class="planning-shift__row">' +
                    '<span class="planning-shift__label">Dienst</span>' +
                    '<span class="planning-shift__time">' +
                    escapeHtml(start) +
                    ' – ' +
                    escapeHtml(end) +
                    '</span>' +
                    '</div>' +
                    (vehicle
                        ? '<span class="planning-shift__meta">' + escapeHtml(vehicle) + '</span>'
                        : '') +
                    (notes
                        ? '<span class="planning-shift__notes">' + escapeHtml(notes) + '</span>'
                        : '') +
                    '</div>'
                );
            })
            .join('');
    }

    function planningDayBodyHtml(day) {
        return planningShiftsHtml(day) + planningDayRidesHtml(day);
    }

    function planningRideCardHtml(ride) {
        if (!ride || ride.id == null) {
            return '';
        }
        const canOpen = ride.status === 'accepted' || ride.status === 'assigned';
        const tag = canOpen ? 'button' : 'div';
        const extra = canOpen
            ? ' type="button" data-planning-ride-id="' +
              escapeHtml(String(ride.id)) +
              '" data-planning-ride-status="' +
              escapeHtml(String(ride.status || '')) +
              '"'
            : '';
        const name = ride.customer_name ? String(ride.customer_name).trim() : '';
        const from = shortAddress(ride.pickup_address);
        const to = shortAddress(ride.dropoff_address);
        const meta = [];
        if (name) {
            meta.push(name);
        }
        if (ride.is_contract || isContractRide(ride)) {
            meta.push('Contract');
        } else {
            meta.push('Taxi');
        }
        if (isNexaSuiteRide(ride)) {
            meta.push(nexaSuiteRideLabel(ride));
        }
        const pax = Number(ride.passengers || 0);
        if (pax > 0) {
            meta.push(pax === 1 ? '1 passagier' : pax + ' passagiers');
        }
        const statusClass =
            ride.status === 'assigned'
                ? ' is-assigned'
                : ride.status === 'completed'
                  ? ' is-completed'
                  : ride.status === 'accepted'
                    ? ' is-accepted'
                    : '';
        const kindClass = isContractRide(ride) ? ' is-contract' : ' is-taxi';
        const nexaSuiteClass = isNexaSuiteRide(ride) ? ' is-nexa-suite' : '';
        return (
            '<' +
            tag +
            ' class="planning-ride-card' +
            statusClass +
            kindClass +
            nexaSuiteClass +
            '"' +
            extra +
            '>' +
            '<div class="planning-ride-card__top">' +
            '<span class="planning-ride-card__time">' +
            escapeHtml(formatStopTime(ride.pickup_at)) +
            '</span>' +
            '<span class="planning-ride-card__status">' +
            escapeHtml(ride.status_label || ride.status || '') +
            '</span>' +
            '</div>' +
            '<p class="planning-ride-card__route">' +
            escapeHtml(from) +
            ' <span class="planning-ride-card__arrow">→</span> ' +
            escapeHtml(to) +
            '</p>' +
            (meta.length
                ? '<p class="planning-ride-card__meta">' + escapeHtml(meta.join(' · ')) + '</p>'
                : '') +
            '</' +
            tag +
            '>'
        );
    }

    function planningDayRidesHtml(day) {
        const rides = sortRidesEarliestPickupFirst(
            filterRidesByKind((day && day.rides) || [])
        );
        if (!rides.length) {
            const emptyKind = effectiveRideKindFilter();
            const emptyLabel =
                emptyKind === 'contract'
                    ? 'Geen contractritten op deze dag.'
                    : emptyKind === 'taxi'
                      ? 'Geen taxiritten op deze dag.'
                      : 'Geen ritten op deze dag.';
            return '<p class="planning-empty">' + emptyLabel + '</p>';
        }
        return rides.map(planningRideCardHtml).join('');
    }

    function planningRideCountLabel(count) {
        return Number(count) === 1 ? '1 rit' : Number(count || 0) + ' ritten';
    }

    function shiftPlanningDay(deltaDays) {
        const current = planningSelectedDate || todayContractDateKey();
        const next = shiftIsoDate(current, deltaDays);
        planningSelectedDate = next;
        const inPayload = ((planningPayload && planningPayload.days) || []).some(function (d) {
            return d.date === next;
        });
        if (inPayload) {
            renderPlanning(planningPayload);
            return;
        }
        planningWeekFrom = planningMondayIso(next);
        persistUiState();
        loadPlanning();
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

    function planningClockIconHtml() {
        return (
            '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
            '<circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>' +
            '<path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 8v4.2L15 15"/>' +
            '</svg>'
        );
    }

    function planningNavHtml(selected) {
        const isWeek = planningView === 'week';
        const todayKey = todayContractDateKey();
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
        const count = selected
            ? Number(selected.ride_count || (selected.rides || []).length || 0)
            : 0;
        const weekTotal = ((planningPayload && planningPayload.days) || []).reduce(function (sum, day) {
            return sum + Number(day.ride_count || (day.rides || []).length || 0);
        }, 0);
        const label = isWeek
            ? escapeHtml(formatPlanningRangeLabel(planningPayload.from, planningPayload.to))
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

    function renderPlanning(payload) {
        const body = $('#planning-body');
        if (!body) {
            return;
        }
        planningPayload = payload || planningPayload || {};
        const days = planningPayload.days || [];
        if (!days.length) {
            body.innerHTML = '<p class="planning-empty">Geen planningsdata.</p>';
            return;
        }
        if (
            !planningSelectedDate ||
            !days.some(function (d) {
                return d.date === planningSelectedDate;
            })
        ) {
            const todayDay = days.find(function (d) {
                return d.is_today;
            });
            planningSelectedDate = (todayDay && todayDay.date) || days[0].date;
        }
        persistUiState();
        const selected =
            days.find(function (d) {
                return d.date === planningSelectedDate;
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
                const count = filterRidesByKind(day.rides || []).length;
                const shiftCount = Array.isArray(day.shifts) ? day.shifts.length : Number(day.shift_count || 0);
                const hasShift = shiftCount > 0;
                html +=
                    '<button type="button" class="planning-week-day' +
                    (day.date === planningSelectedDate ? ' is-active' : '') +
                    (day.is_today ? ' is-today' : '') +
                    (count > 0 ? ' has-rides' : '') +
                    (hasShift ? ' has-shift' : '') +
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
                    '</span>' +
                    (hasShift ? '<span class="wd-shift">' + planningClockIconHtml() + '</span>' : '') +
                    '</span></button>';
            });
            html += '</div>';
            const count = filterRidesByKind((selected && selected.rides) || []).length;
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
                planningDayBodyHtml(selected) +
                '</section>';
        } else if (!selected) {
            html +=
                '<div class="planning-day-stack">' +
                planningHeadingHtml(selected) +
                '<p class="planning-empty">Geen planningsdata.</p>' +
                '</div>';
        } else {
            html +=
                '<div class="planning-day-stack">' +
                planningHeadingHtml(selected) +
                planningDayBodyHtml(selected) +
                '</div>';
        }

        body.innerHTML = html;
    }

    function scrollPlanningToSelectedDay() {
        if (planningView !== 'week' || !planningSelectedDate) {
            return;
        }
        const btn = document.querySelector(
            '.planning-week-day[data-planning-date="' + planningSelectedDate + '"]'
        );
        if (btn && typeof btn.scrollIntoView === 'function') {
            btn.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
        }
    }

    function openPlanningRide(rideId) {
        const id = String(rideId || '');
        if (!id) {
            return;
        }
        const isThisAssigned =
            (currentActiveRide && String(currentActiveRide.id) === id) ||
            (parkedAssignedRides || []).some(function (ride) {
                return ride && String(ride.id) === id;
            });
        if (isThisAssigned) {
            showActiveRideFullPanel(id);
            return;
        }
        if (
            currentActiveRide &&
            isDriverInProgressRide(currentActiveRide) &&
            !activeRideInboxCollapsed
        ) {
            activeRideInboxCollapsed = true;
            viewingActiveRideId = null;
            setActiveRideUiVisible(false);
        }
        openRideFromPickupAlert(id);
    }

    async function loadPlanning(silent) {
        const errorEl = $('#planning-error');
        const loadingEl = $('#planning-loading');
        const body = $('#planning-body');
        if (!planningWeekFrom) {
            planningWeekFrom = planningMondayIso(todayContractDateKey());
        }
        if (errorEl && !silent) {
            errorEl.hidden = true;
            errorEl.textContent = '';
        }
        if (loadingEl && !silent && !planningPayload) {
            loadingEl.hidden = false;
        }
        try {
            const data = await api('/planning?from=' + encodeURIComponent(planningWeekFrom) + selectedVehicleQuery('&'));
            const payload = (data && data.data) || {};
            planningWeekFrom = payload.from || planningWeekFrom;
            if (loadingEl) {
                loadingEl.hidden = true;
            }
            renderPlanning(payload);
        } catch (e) {
            if (loadingEl) {
                loadingEl.hidden = true;
            }
            if (!silent) {
                if (errorEl) {
                    errorEl.textContent = (e && e.message) || 'Kon planning niet laden.';
                    errorEl.hidden = false;
                }
                if (body && !planningPayload) {
                    body.innerHTML = '';
                }
            }
        }
    }

    function setNavigationStatus(text) {
        const el = $('#navigation-status');
        if (el) {
            el.textContent = text || '';
        }
    }

    function setNavigationStartEnabled(on) {
        const btn = $('#btn-start-navigation');
        if (btn) {
            btn.disabled = !on;
        }
    }

    function sameAddress(a, b) {
        return String(a || '')
            .trim()
            .toLowerCase()
            .replace(/\s+/g, ' ') ===
            String(b || '')
                .trim()
                .toLowerCase()
                .replace(/\s+/g, ' ');
    }

    function rideCoord(lat, lng) {
        const y = lat == null || lat === '' ? NaN : Number(lat);
        const x = lng == null || lng === '' ? NaN : Number(lng);
        if (!Number.isFinite(y) || !Number.isFinite(x)) {
            return null;
        }
        return { lat: y, lng: x };
    }

    function collectNavigationStops(rides) {
        const stops = [];
        (rides || []).forEach(function (ride) {
            if (!ride || ride.status === 'completed') {
                return;
            }
            const name = ride.customer_name ? String(ride.customer_name).trim() : '';
            const pickup = ride.pickup_address ? String(ride.pickup_address).trim() : '';
            const dropoff = ride.dropoff_address ? String(ride.dropoff_address).trim() : '';
            if (pickup && (!stops.length || !sameAddress(stops[stops.length - 1].address, pickup))) {
                const coord = rideCoord(ride.pickup_lat, ride.pickup_lng);
                stops.push({
                    address: pickup,
                    label: 'Ophalen',
                    name: name || null,
                    kind: 'pickup',
                    lat: coord ? coord.lat : null,
                    lng: coord ? coord.lng : null,
                });
            }
            if (dropoff && (!stops.length || !sameAddress(stops[stops.length - 1].address, dropoff))) {
                const coord = rideCoord(ride.dropoff_lat, ride.dropoff_lng);
                stops.push({
                    address: dropoff,
                    label: 'Afzetten',
                    name: null,
                    kind: 'dropoff',
                    lat: coord ? coord.lat : null,
                    lng: coord ? coord.lng : null,
                });
            }
        });
        return stops;
    }

    function navigationIconSvg() {
        return '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 20.5 12 16.5l7.5 4L12 3Z"/></svg>';
    }

    function emptyNavSession() {
        return { rideId: null, started: false, arrived: false, legIndex: 0 };
    }

    function readNavSession() {
        try {
            const raw = sessionStorage.getItem(NAV_SESSION_KEY);
            if (!raw) {
                return emptyNavSession();
            }
            return Object.assign(emptyNavSession(), JSON.parse(raw) || {});
        } catch (e) {
            return emptyNavSession();
        }
    }

    function writeNavSession(session) {
        try {
            sessionStorage.setItem(NAV_SESSION_KEY, JSON.stringify(session));
        } catch (e) {
            /* ignore quota */
        }
    }

    function stopNavigationWatch() {
        if (navigationWatchId != null && navigator.geolocation) {
            navigator.geolocation.clearWatch(navigationWatchId);
        }
        navigationWatchId = null;
    }

    function clearNavSession() {
        stopNavigationWatch();
        try {
            sessionStorage.removeItem(NAV_SESSION_KEY);
        } catch (e) {
            /* ignore */
        }
    }

    function stopActiveRideNavigation() {
        clearNavSession();
        navigationStops = [];
        navigationOrigin = null;
        const btn = $('#btn-start-navigation');
        if (btn) {
            btn.textContent = 'Start navigatie';
            btn.disabled = true;
        }
    }

    function navSessionForRide(ride) {
        const session = readNavSession();
        if (!ride || !ride.id) {
            return emptyNavSession();
        }
        if (String(session.rideId) !== String(ride.id)) {
            stopNavigationWatch();
            const next = emptyNavSession();
            next.rideId = ride.id;
            writeNavSession(next);
            return next;
        }
        return session;
    }

    function collectActiveRideNavigationStops(ride) {
        if (!ride) {
            return [];
        }
        if (ride.ride_type === 'contract_group' && (activeRideStops || []).length) {
            return (activeRideStops || [])
                .slice()
                .sort(function (a, b) {
                    return (a.sequence || 0) - (b.sequence || 0);
                })
                .filter(function (stop) {
                    return stop && stop.status !== 'completed' && stop.status !== 'skipped';
                })
                .map(function (stop) {
                    const coord = rideCoord(stop.lat, stop.lng);
                    const kind = stop.stop_type === 'dropoff' ? 'dropoff' : 'pickup';
                    const name = stop.passenger_name ? String(stop.passenger_name).trim() : '';
                    return {
                        address: String(stop.address || '').trim(),
                        label: kind === 'dropoff' ? 'Afzetten' : 'Ophalen',
                        name: kind === 'dropoff' ? null : name || null,
                        kind: kind,
                        lat: coord ? coord.lat : null,
                        lng: coord ? coord.lng : null,
                    };
                })
                .filter(function (stop) {
                    return stop.address;
                });
        }
        return collectNavigationStops([ride]);
    }

    function remainingNavigationStops(stops, session) {
        const list = stops || [];
        if (!list.length || (session && session.arrived)) {
            return [];
        }
        const index = Math.max(0, Number(session && session.legIndex) || 0);
        return list.slice(index);
    }

    function syncNavigationButton(session, hasRoute) {
        const btn = $('#btn-start-navigation');
        if (!btn) {
            return;
        }
        if (!hasRoute) {
            btn.textContent = 'Start navigatie';
            btn.disabled = true;
            return;
        }
        if (session && session.arrived) {
            btn.textContent = 'Aangekomen';
            btn.disabled = true;
            return;
        }
        btn.disabled = false;
        btn.textContent = session && session.started ? 'Hervat navigatie' : 'Start navigatie';
    }

    function navigationStopKind(stop) {
        return stop && stop.kind === 'dropoff' ? 'Afzetten' : 'Ophalen';
    }

    function navigationStopBodyHtml(stop, extraStopText) {
        const name = stop && stop.name ? String(stop.name).trim() : '';
        const kind = navigationStopKind(stop);
        const extra = extraStopText ? String(extraStopText) : '';
        const addr = escapeHtml(shortAddress(stop && stop.address)) + extra;
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

    function renderNavigationStops(stops, session) {
        const list = $('#navigation-stops');
        if (!list) {
            return;
        }
        if (!stops.length) {
            list.innerHTML = '';
            list.hidden = true;
            return;
        }
        const legIndex = session && session.arrived ? stops.length : Math.max(0, Number(session && session.legIndex) || 0);
        list.innerHTML = stops
            .map(function (stop, i) {
                const done = i < legIndex;
                return (
                    '<li class="navigation-route' +
                    (done ? ' is-done' : '') +
                    '"><span class="navigation-stops__num">' +
                    (i + 1) +
                    '</span><span>' +
                    navigationStopBodyHtml(stop, done ? ' · aangekomen' : '') +
                    '</span></li>'
                );
            })
            .join('');
        list.hidden = false;
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
            function finish() {
                if (window.google && window.google.maps && window.google.maps.Map) {
                    resolve();
                    return true;
                }
                return false;
            }
            if (finish()) {
                return;
            }
            window.__nexaGoogleMapsReady = function () {
                resolve();
            };
            const existing = document.getElementById('nexa-google-maps-sdk');
            if (existing) {
                existing.addEventListener('load', function () {
                    if (!finish()) {
                        resolve();
                    }
                });
                existing.addEventListener('error', function () {
                    googleMapsLoadPromise = null;
                    reject(new Error('maps-load'));
                });
                return;
            }
            const script = document.createElement('script');
            script.id = 'nexa-google-maps-sdk';
            script.async = true;
            script.defer = true;
            script.src =
                'https://maps.googleapis.com/maps/api/js?key=' +
                encodeURIComponent(key) +
                '&callback=__nexaGoogleMapsReady';
            script.onerror = function () {
                googleMapsLoadPromise = null;
                reject(new Error('maps-load'));
            };
            document.head.appendChild(script);
        });
        return googleMapsLoadPromise;
    }

    function prefetchGoogleMapsSdk() {
        loadGoogleMapsSdk().catch(function () {});
    }

    function persistLastGpsCoords(coords) {
        if (!coords || !Number.isFinite(coords.lat) || !Number.isFinite(coords.lng)) {
            return;
        }
        lastGpsCoords = {
            lat: coords.lat,
            lng: coords.lng,
            accuracy: Number.isFinite(coords.accuracy) ? coords.accuracy : lastGpsAccuracy,
            heading: Number.isFinite(coords.heading) ? coords.heading : (lastGpsCoords && lastGpsCoords.heading) || null,
            speed: Number.isFinite(coords.speed) ? coords.speed : null,
            at: Number.isFinite(coords.at) ? coords.at : Date.now()
        };
        if (Number.isFinite(lastGpsCoords.accuracy)) {
            lastGpsAccuracy = lastGpsCoords.accuracy;
        }
        lastGpsFixAt = lastGpsCoords.at;
        try {
            sessionStorage.setItem(GPS_COORDS_KEY, JSON.stringify({ lat: lastGpsCoords.lat, lng: lastGpsCoords.lng }));
        } catch (e) {
            /* private mode */
        }
    }

    function resizeNavigationMap() {
        requestAnimationFrame(function () {
            if (navigationMap && typeof google !== 'undefined' && google.maps && google.maps.event) {
                google.maps.event.trigger(navigationMap, 'resize');
            }
        });
    }

    function isLightPwaTheme() {
        return document.documentElement.getAttribute('data-theme') === 'light';
    }

    function navigationMapStyles(light) {
        if (light) {
            return [
                { elementType: 'geometry', stylers: [{ color: '#f1f5f9' }] },
                { elementType: 'labels.text.fill', stylers: [{ color: '#475569' }] },
                { elementType: 'labels.text.stroke', stylers: [{ color: '#ffffff' }] },
                { featureType: 'administrative', elementType: 'geometry.stroke', stylers: [{ color: '#cbd5e1' }] },
                { featureType: 'landscape', stylers: [{ color: '#f8fafc' }] },
                { featureType: 'poi', stylers: [{ visibility: 'off' }] },
                { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
                { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#cbd5e1' }] },
                { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#64748b' }] },
                { featureType: 'transit', stylers: [{ visibility: 'off' }] },
                { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#bfdbfe' }] },
                { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#3b82f6' }] },
            ];
        }
        return [
            { elementType: 'geometry', stylers: [{ color: '#1c1c1e' }] },
            { elementType: 'labels.text.stroke', stylers: [{ color: '#1c1c1e' }] },
            { elementType: 'labels.text.fill', stylers: [{ color: '#9ca3af' }] },
            { featureType: 'administrative', elementType: 'geometry.stroke', stylers: [{ color: '#3f3f46' }] },
            { featureType: 'landscape', stylers: [{ color: '#18181b' }] },
            { featureType: 'poi', stylers: [{ visibility: 'off' }] },
            { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#2a2a2e' }] },
            { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#1a1a1c' }] },
            { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#a1a1aa' }] },
            { featureType: 'transit', stylers: [{ visibility: 'off' }] },
            { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#111113' }] },
            { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#71717a' }] },
        ];
    }

    function navigationMapThemeOptions() {
        const light = isLightPwaTheme();
        return {
            backgroundColor: light ? '#e2e8f0' : '#1a1a1c',
            styles: navigationMapStyles(light),
        };
    }

    function applyNavigationMapTheme() {
        const el = $('#navigation-map');
        if (el) {
            el.style.backgroundColor = isLightPwaTheme() ? '#e2e8f0' : '#1a1a1c';
        }
        if (!navigationMap) {
            return;
        }
        navigationMap.setOptions(navigationMapThemeOptions());
        resizeNavigationMap();
    }

    function bindNavigationMapTheme() {
        if (window.__nexaNavMapThemeBound) {
            return;
        }
        window.__nexaNavMapThemeBound = true;
        window.addEventListener('nexa-pwa-theme-change', applyNavigationMapTheme);
        if (typeof MutationObserver !== 'undefined') {
            new MutationObserver(function () {
                applyNavigationMapTheme();
            }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        }
    }

    function ensureNavigationMap() {
        const el = $('#navigation-map');
        if (!el || !window.google || !window.google.maps) {
            return null;
        }
        const center = lastGpsCoords && Number.isFinite(lastGpsCoords.lat)
            ? { lat: lastGpsCoords.lat, lng: lastGpsCoords.lng }
            : {
                lat: Number(cfg.googleMapsCenterLat) || 52.3676,
                lng: Number(cfg.googleMapsCenterLng) || 4.9041,
            };
        const mapOpts = Object.assign({
            center: center,
            zoom: lastGpsCoords ? 14 : 11,
            disableDefaultUI: true,
            zoomControl: true,
            gestureHandling: 'greedy',
        }, navigationMapThemeOptions());
        if (!navigationMap) {
            navigationMap = new google.maps.Map(el, mapOpts);
        } else {
            navigationMap.setOptions(navigationMapThemeOptions());
        }
        if (!navigationRenderer) {
            navigationRenderer = new google.maps.DirectionsRenderer({
                suppressMarkers: false,
                polylineOptions: { strokeColor: '#f97316', strokeWeight: 5, strokeOpacity: 0.95 },
            });
        }
        return navigationMap;
    }

    async function showNavigationBasemap() {
        bindNavigationMapTheme();
        await loadGoogleMapsSdk();
        ensureNavigationMap();
        applyNavigationMapTheme();
        resizeNavigationMap();
        setTimeout(resizeNavigationMap, 80);
        setTimeout(resizeNavigationMap, 280);
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
        if (stop && Number.isFinite(stop.lat) && Number.isFinite(stop.lng)) {
            return { lat: stop.lat, lng: stop.lng };
        }
        return geocodeAddress(stop && stop.address);
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
        const controller = typeof AbortController === 'function' ? new AbortController() : null;
        const timer = controller
            ? setTimeout(function () {
                  controller.abort();
              }, 1800)
            : null;
        try {
            const res = await fetch(
                'https://router.project-osrm.org/route/v1/driving/' +
                    coords +
                    '?overview=full&geometries=geojson',
                controller ? { signal: controller.signal } : undefined
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
        } finally {
            if (timer) {
                clearTimeout(timer);
            }
        }
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

    function addDriverLocationMarker(origin, bounds) {
        const here = asLatLng(origin);
        if (!here || !navigationMap) {
            return;
        }
        if (bounds) {
            bounds.extend(here);
        }
        addNavigationMarker(here, {
            title: 'Jouw locatie',
            zIndex: 20,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: '#38bdf8',
                fillOpacity: 1,
                strokeColor: '#0f172a',
                strokeWeight: 2,
            },
        });
    }

    async function drawNavigationMarkers(origin, stops) {
        ensureNavigationMap();
        if (!navigationMap) {
            return false;
        }
        clearNavigationOverlays();
        const path = [];
        const bounds = new google.maps.LatLngBounds();
        const stopPositions = await Promise.all(stops.map(function (stop) {
            return resolveStopPosition(stop);
        }));
        stopPositions.forEach(function (pos, i) {
            if (!pos) {
                return;
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
        });
        addDriverLocationMarker(origin, bounds);
        if (path.length < 1) {
            return false;
        }
        if (path.length > 1) {
            navigationPolyline = new google.maps.Polyline({
                map: navigationMap,
                path: path,
                geodesic: true,
                strokeColor: '#f97316',
                strokeOpacity: 0.95,
                strokeWeight: 5,
            });
            fetchRoadPath(path).then(function (road) {
                if (!road || road.length < 2 || !navigationPolyline) {
                    return;
                }
                navigationPolyline.setPath(road);
                navigationPolyline.setOptions({ geodesic: false });
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
        const routeStops = navigationRoutePoints(null, stops);
        if (!routeStops.length) {
            return Promise.resolve(false);
        }
        const origin = stopLocation(routeStops[0]);
        const dest = stopLocation(routeStops[routeStops.length - 1]);
        if (!origin || !dest) {
            return Promise.resolve(false);
        }
        const via = routeStops.slice(1, -1).slice(0, 25);
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

    async function drawNavigationRoute(origin, stops) {
        if (!stops.length || !window.google || !window.google.maps) {
            return false;
        }
        ensureNavigationMap();
        const viaRoad = await drawNavigationDirections(stops);
        if (viaRoad) {
            addDriverLocationMarker(origin);
            return true;
        }
        return drawNavigationMarkers(origin, stops);
    }

    function startNavigationWatch() {
        if (navigationWatchId != null) {
            return;
        }
        if (!navigator.geolocation || typeof navigator.geolocation.watchPosition !== 'function') {
            return;
        }
        navigationWatchId = navigator.geolocation.watchPosition(
            handleNavigationPosition,
            function () {},
            GPS_FIX_OPTIONS
        );
    }

    async function ensureStopCoords(stop) {
        if (!stop) {
            return stop;
        }
        if (Number.isFinite(Number(stop.lat)) && Number.isFinite(Number(stop.lng))) {
            return stop;
        }
        const pos = await geocodeAddress(stop.address);
        if (pos) {
            stop.lat = pos.lat;
            stop.lng = pos.lng;
        }
        return stop;
    }

    function navigationStatusText(session, dest) {
        if (session && session.arrived) {
            return 'Je bent gearriveerd. Navigatie is gestopt.';
        }
        if (session && session.started && dest) {
            return 'Navigatie bezig naar ' + shortAddress(dest.address) + '. Ga verder tot aankomst.';
        }
        return 'Route van je actieve rit: van ophalen naar afzetten.';
    }

    function handleNavigationPosition(position) {
        if (!position || !position.coords) {
            return;
        }
        const origin = { lat: position.coords.latitude, lng: position.coords.longitude };
        navigationOrigin = origin;
        const ride = currentActiveRide && isDriverInProgressRide(currentActiveRide) ? currentActiveRide : null;
        if (!ride) {
            return;
        }
        const session = navSessionForRide(ride);
        if (!session.started || session.arrived) {
            return;
        }
        const dest = remainingNavigationStops(navigationStops, session)[0];
        if (!dest || !Number.isFinite(Number(dest.lat)) || !Number.isFinite(Number(dest.lng))) {
            return;
        }
        const dist = haversineMeters(origin.lat, origin.lng, Number(dest.lat), Number(dest.lng));
        if (dist > STOP_ARRIVE_RADIUS_M) {
            return;
        }
        session.legIndex = Math.max(0, Number(session.legIndex) || 0) + 1;
        if (session.legIndex >= navigationStops.length) {
            session.arrived = true;
            session.started = false;
            writeNavSession(session);
            stopNavigationWatch();
            if (mainTab === 'navigation') {
                renderNavigationStops(navigationStops, session);
                syncNavigationButton(session, true);
                setNavigationStatus('Je bent gearriveerd. Navigatie is gestopt.');
            }
            return;
        }
        writeNavSession(session);
        const nextDest = remainingNavigationStops(navigationStops, session)[0];
        if (mainTab === 'navigation') {
            renderNavigationStops(navigationStops, session);
            syncNavigationButton(session, true);
            setNavigationStatus(navigationStatusText(session, nextDest));
            drawNavigationRoute(origin, remainingNavigationStops(navigationStops, session)).catch(function () {});
        }
        if (nextDest && nextDest.address) {
            const url = navigationDirUrl(origin, [nextDest]);
            if (url) {
                window.open(url, '_blank', 'noopener');
            }
        }
    }

    async function showNavigationTab() {
        const ride = currentActiveRide && isDriverInProgressRide(currentActiveRide) ? currentActiveRide : null;
        if (!ride) {
            stopNavigationWatch();
            navigationStops = [];
            navigationOrigin = null;
            renderNavigationStops([]);
            syncNavigationButton(null, false);
            setNavigationStatus('Geen actieve rit. Start een rit onder Ritten en tik op het navigatie-icoon.');
            try {
                await showNavigationBasemap();
            } catch (e) {
                /* kaart blijft leeg als Maps niet laadt */
            }
            return;
        }
        const drawToken = ++navigationDrawToken;
        const session = navSessionForRide(ride);
        setNavigationStatus(session.started && !session.arrived ? 'Navigatie hervatten…' : 'Route van je rit laden…');
        navigationStops = collectActiveRideNavigationStops(ride);
        renderNavigationStops(navigationStops, session);
        if (!navigationStops.length) {
            syncNavigationButton(session, false);
            setNavigationStatus('Deze rit heeft geen ophaal- of afzetadres.');
            try {
                await showNavigationBasemap();
            } catch (e) {
                /* kaart blijft leeg als Maps niet laadt */
            }
            return;
        }
        if (session.arrived) {
            syncNavigationButton(session, true);
            setNavigationStatus('Je bent gearriveerd. Navigatie is gestopt.');
        } else {
            syncNavigationButton(session, true);
        }
        navigationOrigin = lastGpsCoords;
        const remaining = remainingNavigationStops(navigationStops, session);
        const dest = remaining[0];
        if (!session.arrived) {
            setNavigationStatus(navigationStatusText(session, dest));
        }
        const mapsPromise = loadGoogleMapsSdk();
        const gpsPromise = getDriverPosition();
        gpsPromise.then(function (origin) {
            if (drawToken !== navigationDrawToken || !origin) {
                return;
            }
            navigationOrigin = origin;
        });
        try {
            await mapsPromise;
            if (drawToken !== navigationDrawToken) {
                return;
            }
            bindNavigationMapTheme();
            ensureNavigationMap();
            applyNavigationMapTheme();
            resizeNavigationMap();
            await Promise.all(
                navigationStops.map(function (stop) {
                    return ensureStopCoords(stop);
                })
            );
            if (drawToken !== navigationDrawToken) {
                return;
            }
            if (!navigationOrigin) {
                navigationOrigin = await gpsPromise;
            }
            if (drawToken !== navigationDrawToken) {
                return;
            }
            const drawn = await drawNavigationRoute(
                navigationOrigin,
                remaining.length ? remaining : navigationStops
            );
            if (drawToken !== navigationDrawToken) {
                return;
            }
            if (!drawn && !session.arrived) {
                setNavigationStatus(
                    'Kaart kon de route niet tekenen. Je kunt navigatie wel starten.'
                );
            }
            resizeNavigationMap();
            setTimeout(resizeNavigationMap, 80);
        } catch (e) {
            if (drawToken !== navigationDrawToken) {
                return;
            }
            if (!session.arrived) {
                setNavigationStatus(
                    'Kaart is niet beschikbaar. Start navigatie opent Google Maps.'
                );
            }
        }
        if (session.started && !session.arrived) {
            startNavigationWatch();
        }
    }

    async function startGoogleNavigation() {
        const ride = currentActiveRide && isDriverInProgressRide(currentActiveRide) ? currentActiveRide : null;
        if (!ride || !navigationStops.length) {
            return;
        }
        const session = navSessionForRide(ride);
        if (session.arrived) {
            return;
        }
        session.started = true;
        writeNavSession(session);
        syncNavigationButton(session, true);
        const remaining = remainingNavigationStops(navigationStops, session);
        const stops = remaining.length ? remaining : navigationStops;
        await Promise.all(
            stops.map(function (stop) {
                return ensureStopCoords(stop);
            })
        );
        const url = navigationDirUrl(null, stops);
        if (url) {
            window.open(url, '_blank', 'noopener');
        }
        startNavigationWatch();
        const dest = remaining[0];
        setNavigationStatus(navigationStatusText(session, dest));
    }

    function shortAddress(value) {
        const text = String(value || '').trim();
        if (!text) {
            return '—';
        }
        const first = text.split(',')[0].trim();
        return first || text;
    }

    function renderEarnings(data) {
        const summary = $('#earnings-summary');
        const list = $('#earnings-rides-list');
        const empty = $('#earnings-empty');
        const error = $('#earnings-error');
        const loading = $('#earnings-loading');
        const dayLabel = $('#earnings-day-label');
        const daySub = $('#earnings-day-sub');
        const dayTotal = $('#earnings-day-total');
        const dayCount = $('#earnings-day-count');
        const monthCard = $('#earnings-month-card');
        const prevBtn = $('#btn-earnings-prev');
        const nextBtn = $('#btn-earnings-next');
        const todayBtn = $('#btn-earnings-today');

        if (loading) {
            loading.hidden = true;
        }
        if (error) {
            error.hidden = true;
            error.textContent = '';
        }

        if (!data) {
            if (summary) {
                summary.hidden = true;
            }
            if (list) {
                list.innerHTML = '';
            }
            if (empty) {
                empty.hidden = true;
            }
            if (todayBtn) {
                todayBtn.disabled = true;
            }
            return;
        }

        earningsDate = data.date || todayLocalIsoDate();
        if (dayLabel) {
            dayLabel.textContent = data.label || earningsDate;
        }
        if (daySub) {
            daySub.textContent = earningsDate;
        }
        if (dayTotal) {
            dayTotal.textContent = formatEuro(data.day_total || 0);
        }
        if (dayCount) {
            const n = data.ride_count || 0;
            dayCount.textContent = n === 1 ? '1 rit' : n + ' ritten';
        }
        if (summary) {
            summary.hidden = false;
        }
        if (monthCard) {
            if (canViewMonthEarnings && data.month) {
                monthCard.hidden = false;
                const monthLabel = $('#earnings-month-label');
                const monthTotal = $('#earnings-month-total');
                const monthCount = $('#earnings-month-count');
                if (monthLabel) {
                    monthLabel.textContent = 'Totaal ' + (data.month.label || 'deze maand');
                }
                if (monthTotal) {
                    monthTotal.textContent = formatEuro(data.month.total || 0);
                }
                if (monthCount) {
                    const mn = data.month.ride_count || 0;
                    monthCount.textContent = mn === 1 ? '1 rit' : mn + ' ritten';
                }
            } else {
                monthCard.hidden = true;
            }
        }
        if (prevBtn) {
            prevBtn.disabled = false;
        }
        if (nextBtn) {
            nextBtn.disabled = !!data.is_today;
        }
        if (todayBtn) {
            todayBtn.disabled = !!data.is_today;
        }

        const rides = Array.isArray(data.rides) ? data.rides : [];
        if (list) {
            list.innerHTML = rides
                .map(function (ride) {
                    const badges = [];
                    if (ride.is_contract) {
                        badges.push('<span class="offer-badge is-muted">Contract</span>');
                    } else if (isNexaSuiteRide(ride)) {
                        badges.push('<span class="offer-badge is-nexa-suite">' + escapeHtml(nexaSuiteRideLabel(ride)) + '</span>');
                    } else if (ride.payment_status === 'paid') {
                        badges.push('<span class="offer-badge is-success">Betaald</span>');
                    } else if (ride.payment_method === 'cash') {
                        badges.push('<span class="offer-badge is-muted">Contant</span>');
                    }
                    if (ride.credited_as === 'outbound') {
                        badges.push('<span class="offer-badge is-muted">Heenrit</span>');
                    }
                    const rideId = ride.id != null ? String(ride.id) : '';
                    const expandKey = rideId || String(ride.completed_at || ride.completed_time || Math.random());
                    const expanded = !!earningsRideExpanded[expandKey];
                    const bodyId = 'earnings-ride-body-' + expandKey;
                    const routeSummary = [ride.pickup_address, ride.dropoff_address]
                        .map(function (addr) {
                            return addr ? shortAddress(addr) : '';
                        })
                        .filter(Boolean)
                        .join(' → ');
                    const amountLabel = formatEuro(ride.amount || 0);
                    return (
                        '<article class="card offer-card earnings-ride-card scheduled-ride-card' +
                        (expanded ? ' is-expanded' : '') +
                        '" data-earnings-ride-id="' +
                        escapeHtml(expandKey) +
                        '">' +
                        '<button type="button" class="scheduled-ride-toggle earnings-ride-toggle" aria-expanded="' +
                        (expanded ? 'true' : 'false') +
                        '" aria-controls="' +
                        escapeHtml(bodyId) +
                        '" data-earnings-ride-id="' +
                        escapeHtml(expandKey) +
                        '">' +
                        '<span class="scheduled-ride-toggle-text">' +
                        '<span class="earnings-ride-toggle-top">' +
                        '<span class="offer-badge-row">' +
                        badges.join('') +
                        '</span>' +
                        (ride.completed_time
                            ? '<span class="offer-ago">' + escapeHtml(ride.completed_time) + '</span>'
                            : '') +
                        '</span>' +
                        (rideId
                            ? '<span class="offer-title">Rit #' + escapeHtml(rideId) + '</span>'
                            : '') +
                        (routeSummary
                            ? '<span class="offer-meta scheduled-route-summary">' +
                              escapeHtml(routeSummary) +
                              '</span>'
                            : '') +
                        '<span class="offer-meta earnings-ride-amount-summary">' +
                        escapeHtml(amountLabel) +
                        '</span>' +
                        '</span>' +
                        '<span class="scheduled-ride-chevron" aria-hidden="true">▼</span>' +
                        '</button>' +
                        '<div class="scheduled-ride-body earnings-ride-body" id="' +
                        escapeHtml(bodyId) +
                        '"' +
                        (expanded ? '' : ' hidden') +
                        '>' +
                        '<div class="offer-body-grid">' +
                        routeTimelineHtml(ride.pickup_address, ride.dropoff_address) +
                        '<div class="offer-meta-grid">' +
                        '<div class="offer-customer-block"></div>' +
                        '<div class="offer-stats">' +
                        '<div class="offer-price-wrap">' +
                        '<p class="offer-price">' +
                        escapeHtml(amountLabel) +
                        '</p>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</article>'
                    );
                })
                .join('');
        }
        if (empty) {
            empty.hidden = rides.length > 0;
        }
    }

    function toggleEarningsRideCard(rideId) {
        const key = String(rideId || '');
        if (!key) {
            return;
        }
        earningsRideExpanded[key] = !earningsRideExpanded[key];
        const card = document.querySelector(
            '.earnings-ride-card[data-earnings-ride-id="' + CSS.escape(key) + '"]'
        );
        if (!card) {
            return;
        }
        const expanded = !!earningsRideExpanded[key];
        const toggle = card.querySelector('.earnings-ride-toggle');
        const body = card.querySelector('.earnings-ride-body');
        card.classList.toggle('is-expanded', expanded);
        if (toggle) {
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }
        if (body) {
            body.hidden = !expanded;
        }
    }

    async function loadEarnings(dateIso) {
        if (!canViewEarnings || earningsLoading) {
            return;
        }
        const date = dateIso || earningsDate || todayLocalIsoDate();
        earningsLoading = true;
        const loading = $('#earnings-loading');
        const error = $('#earnings-error');
        const empty = $('#earnings-empty');
        const list = $('#earnings-rides-list');
        if (loading) {
            loading.hidden = false;
        }
        if (error) {
            error.hidden = true;
        }
        if (empty) {
            empty.hidden = true;
        }
        if (list) {
            list.innerHTML = '';
        }
        try {
            const res = await api('/earnings?date=' + encodeURIComponent(date));
            if (res && res.permissions) {
                applyEarningsPermissions(res.permissions);
            }
            renderEarnings(res && res.data ? res.data : null);
        } catch (e) {
            if (loading) {
                loading.hidden = true;
            }
            if (error) {
                error.hidden = false;
                error.textContent = e.message || 'Inkomsten konden niet worden geladen.';
            }
            if (e.code === 'earnings_forbidden') {
                applyEarningsPermissions({ earnings_view: false, earnings_view_month: false });
            }
        } finally {
            earningsLoading = false;
        }
    }

    async function setOnline(value) {
        if (!accountActive && value) {
            updateEmptyState();
            return;
        }
        isOnline = !!value;
        localStorage.setItem(ONLINE_KEY, isOnline ? '1' : '0');
        setOnlineUi();
        updateProfileOnlineStatus();
        updateNotificationsHint();
        if (isOnline) {
            await prepareDriverAlerts();
            requestScreenWakeLockFromGesture();
            await refreshDriverVehicles();
        }
        if (!token) {
            return;
        }
        try {
            const coords = isOnline ? await getDriverPosition() : lastGpsCoords;
            const body = { is_online: isOnline };
            if (coords && Number.isFinite(coords.lat) && Number.isFinite(coords.lng)) {
                body.lat = coords.lat;
                body.lng = coords.lng;
                persistLastGpsCoords(coords);
            }
            if (selectedVehicleId) {
                body.vehicle_id = selectedVehicleId;
            }
            await api('/availability', {
                method: 'PUT',
                body: body,
            });
        } catch (e) {
            if (e.code === 'driver_not_active') {
                return;
            }
            console.warn(e);
        }
        if (isOnline) {
            startGpsTracking();
            await refreshInbox();
        } else {
            stopGpsTracking();
            inboxLoading = false;
            inboxHasLoaded = false;
            stopInboxSync();
            renderOffer(null);
            updateEmptyState();
        }
        syncScreenWakeLock();
    }

    function persistSelectedVehicle(id) {
        const parsed = parseInt(id, 10);
        selectedVehicleId = Number.isFinite(parsed) && parsed > 0 ? parsed : null;
        if (selectedVehicleId) {
            localStorage.setItem(VEHICLE_KEY, String(selectedVehicleId));
        } else {
            localStorage.removeItem(VEHICLE_KEY);
        }
        const select = $('#driver-vehicle-select');
        if (select && selectedVehicleId && !select.hidden) {
            select.value = String(selectedVehicleId);
        }
    }

    function vehicleOptionLabel(item) {
        if (!item) {
            return '';
        }
        if (item.label) {
            return String(item.label);
        }
        const plate = item.license_plate ? String(item.license_plate) : '';
        return plate ? (plate + (item.name ? ' · ' + item.name : '')) : (item.name || ('Voertuig ' + item.id));
    }

    function renderDriverVehicles(payload) {
        const row = $('#driver-vehicle-row');
        const select = $('#driver-vehicle-select');
        const assigned = $('#driver-vehicle-assigned');
        const assignedValue = $('#driver-vehicle-assigned-value');
        const assignedUntil = $('#driver-vehicle-assigned-until');
        const label = row ? row.querySelector('label[for="driver-vehicle-select"]') : null;
        if (!row || !select) {
            return;
        }
        const locked = !!(payload && payload.locked);
        const assignedVehicle = payload && payload.assigned_vehicle ? payload.assigned_vehicle : null;
        const list = Array.isArray(payload)
            ? payload
            : ((payload && Array.isArray(payload.data)) ? payload.data : []);
        vehicleChoiceLocked = locked;

        if (locked && assignedVehicle) {
            persistSelectedVehicle(assignedVehicle.id);
            select.hidden = true;
            if (label) {
                label.hidden = true;
            }
            if (assigned) {
                assigned.hidden = false;
            }
            if (assignedValue) {
                assignedValue.textContent = vehicleOptionLabel(assignedVehicle);
            }
            if (assignedUntil) {
                const until = payload && payload.assigned_until ? String(payload.assigned_until).trim() : '';
                assignedUntil.hidden = !until;
                assignedUntil.textContent = until ? 'Dienst tot ' + until : '';
            }
            row.hidden = false;
            return;
        }

        select.hidden = false;
        if (label) {
            label.hidden = false;
        }
        if (assigned) {
            assigned.hidden = true;
        }
        if (assignedUntil) {
            assignedUntil.hidden = true;
            assignedUntil.textContent = '';
        }

        if (!list.length) {
            row.hidden = true;
            return;
        }
        const current = select.value;
        select.innerHTML = '<option value="">Kies kenteken</option>';
        list.forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = String(item.id);
            opt.textContent = vehicleOptionLabel(item);
            select.appendChild(opt);
        });
        const preferred = selectedVehicleId ? String(selectedVehicleId) : current;
        if (preferred && list.some(function (item) { return String(item.id) === preferred; })) {
            select.value = preferred;
            persistSelectedVehicle(preferred);
        }
        row.hidden = false;
    }

    async function refreshDriverVehicles() {
        if (!token) {
            return;
        }
        lastVehiclesRefreshAt = Date.now();
        try {
            const data = await api('/vehicles');
            const previousId = selectedVehicleId;
            renderDriverVehicles(data);
            if (data && data.locked && data.assigned_vehicle && isOnline && selectedVehicleId && selectedVehicleId !== previousId) {
                try {
                    await api('/availability', {
                        method: 'PUT',
                        body: { is_online: true, vehicle_id: selectedVehicleId },
                    });
                } catch (e) {
                    console.warn(e);
                }
            }
        } catch (e) {
            console.warn(e);
        }
    }

    function coordsFromGeolocation(pos) {
        if (!pos || !pos.coords) {
            return null;
        }
        const lat = Number(pos.coords.latitude);
        const lng = Number(pos.coords.longitude);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return null;
        }
        const accuracy = Number(pos.coords.accuracy);
        const heading = Number(pos.coords.heading);
        const speed = Number(pos.coords.speed);
        return {
            lat: lat,
            lng: lng,
            accuracy: Number.isFinite(accuracy) ? accuracy : null,
            heading: Number.isFinite(heading) && heading >= 0 ? heading : null,
            speed: Number.isFinite(speed) && speed >= 0 ? speed : null,
            at: Date.now()
        };
    }

    function shouldAcceptGpsFix(next) {
        if (!next || !Number.isFinite(next.lat) || !Number.isFinite(next.lng)) {
            return false;
        }
        const acc = next.accuracy;
        const maxAcc = document.visibilityState === 'visible'
            ? GPS_MAX_ACCURACY_METERS
            : GPS_BACKGROUND_ACCURACY_METERS;
        if (Number.isFinite(acc) && acc > maxAcc) {
            return false;
        }
        if (lastGpsCoords && Number.isFinite(acc) && Number.isFinite(lastGpsAccuracy)
            && acc > lastGpsAccuracy + 35 && acc > GPS_MAX_ACCURACY_METERS) {
            return false;
        }
        if (lastGpsCoords && lastGpsFixAt) {
            const dt = Math.max(0.25, ((next.at || Date.now()) - lastGpsFixAt) / 1000);
            const dist = rideTrackDistanceMeters(lastGpsCoords, next);
            if (dist > 120 && (dist / dt) > GPS_MAX_SPEED_MPS && !(Number.isFinite(acc) && acc <= 12)) {
                return false;
            }
        }
        return true;
    }

    function getDriverPosition() {
        const cachedIsFresh = lastGpsCoords
            && lastGpsFixAt
            && (Date.now() - lastGpsFixAt) < 8000
            && (!Number.isFinite(lastGpsAccuracy) || lastGpsAccuracy <= GPS_MAX_ACCURACY_METERS);
        if (cachedIsFresh) {
            refreshDriverPosition();
            return Promise.resolve(lastGpsCoords);
        }
        return refreshDriverPosition();
    }

    function refreshDriverPosition() {
        return new Promise(function (resolve) {
            if (!navigator.geolocation) {
                resolve(lastGpsCoords);
                return;
            }
            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    const coords = coordsFromGeolocation(pos);
                    if (coords && shouldAcceptGpsFix(coords)) {
                        persistLastGpsCoords(coords);
                        resolve(coords);
                        return;
                    }
                    resolve(lastGpsCoords);
                },
                function () {
                    resolve(lastGpsCoords);
                },
                GPS_FIX_OPTIONS
            );
        });
    }

    function rideTrackDistanceMeters(a, b) {
        const dLat = (a.lat - b.lat) * 111320;
        const dLng = (a.lng - b.lng) * 111320 * Math.cos((a.lat * Math.PI) / 180);
        return Math.sqrt(dLat * dLat + dLng * dLng);
    }

    function resetRideTrackBuffer(rideId) {
        const id = rideId != null ? parseInt(rideId, 10) : NaN;
        if (!Number.isFinite(id) || id <= 0) {
            clearRideTrackBuffer();
            return;
        }
        if (rideTrackRideId === id) {
            return;
        }
        rideTrackBuffer = [];
        rideTrackRideId = id;
    }

    function clearRideTrackBuffer() {
        rideTrackBuffer = [];
        rideTrackRideId = null;
    }

    function recordRideTrackPoint(coords) {
        if (!currentActiveRide || !isDriverInProgressRide(currentActiveRide) || !coords) {
            return;
        }
        const rideId = parseInt(currentActiveRide.id, 10);
        if (!Number.isFinite(rideId) || rideId <= 0) {
            return;
        }
        if (rideTrackRideId !== rideId) {
            rideTrackBuffer = [];
            rideTrackRideId = rideId;
        }
        const last = rideTrackBuffer[rideTrackBuffer.length - 1];
        if (last && rideTrackDistanceMeters(last, coords) < 12) {
            return;
        }
        if (rideTrackBuffer.length >= 1500) {
            rideTrackBuffer.shift();
        }
        rideTrackBuffer.push({
            lat: Number(coords.lat),
            lng: Number(coords.lng),
            t: Date.now(),
        });
    }

    function snapshotRideTrack(rideId) {
        const id = parseInt(rideId, 10);
        if (!Number.isFinite(id) || rideTrackRideId !== id) {
            return [];
        }
        return rideTrackBuffer.map(function (point) {
            return { lat: point.lat, lng: point.lng, t: point.t };
        });
    }

    async function sendDriverLocation(coords, withVehicle, force) {
        if (!token || !coords || !Number.isFinite(coords.lat) || !Number.isFinite(coords.lng)) {
            return;
        }
        persistLastGpsCoords(coords);
        recordRideTrackPoint(coords);
        const now = Date.now();
        if (!force && now - lastGpsSentAt < GPS_SEND_INTERVAL_MS) {
            return;
        }
        lastGpsSentAt = now;
        const body = { lat: coords.lat, lng: coords.lng };
        if (Number.isFinite(coords.accuracy)) {
            body.accuracy = coords.accuracy;
        }
        if (Number.isFinite(coords.heading)) {
            body.heading = coords.heading;
        }
        if (Number.isFinite(coords.speed)) {
            body.speed = coords.speed;
        }
        if (withVehicle && selectedVehicleId) {
            body.vehicle_id = selectedVehicleId;
        }
        try {
            await api('/availability/location', { method: 'PUT', body: body, keepalive: true });
        } catch (e) {
            if (e.code !== 'driver_not_active') {
                console.warn(e);
            }
        }
    }

    function startGpsTracking() {
        stopGpsTracking();
        if (!navigator.geolocation || typeof navigator.geolocation.watchPosition !== 'function') {
            gpsHeartbeatTimer = setInterval(function () {
                refreshDriverPosition().then(function (coords) {
                    sendDriverLocation(coords, false, true);
                });
            }, GPS_HEARTBEAT_MS);
            showOnlineGpsNotification();
            return;
        }
        gpsWatchId = navigator.geolocation.watchPosition(
            function (pos) {
                const coords = coordsFromGeolocation(pos);
                if (!coords || !shouldAcceptGpsFix(coords)) {
                    return;
                }
                sendDriverLocation(coords, false);
            },
            function () {},
            GPS_FIX_OPTIONS
        );
        gpsHeartbeatTimer = setInterval(function () {
            refreshDriverPosition().then(function (coords) {
                sendDriverLocation(coords, false, true);
            });
        }, GPS_HEARTBEAT_MS);
        showOnlineGpsNotification();
    }

    function stopGpsTracking() {
        if (gpsWatchId != null && navigator.geolocation) {
            navigator.geolocation.clearWatch(gpsWatchId);
            gpsWatchId = null;
        }
        if (gpsHeartbeatTimer) {
            clearInterval(gpsHeartbeatTimer);
            gpsHeartbeatTimer = null;
        }
        hideOnlineGpsNotification();
    }

    function parseIsoMs(iso) {
        if (!iso) {
            return NaN;
        }
        const ms = new Date(iso).getTime();
        return Number.isFinite(ms) ? ms : NaN;
    }

    function secondsSinceIso(iso) {
        const ms = parseIsoMs(iso);
        if (isNaN(ms)) {
            return 0;
        }
        return Math.max(0, Math.floor((Date.now() - ms) / 1000));
    }

    function secondsUntilIso(iso) {
        const ms = parseIsoMs(iso);
        if (isNaN(ms)) {
            return 0;
        }
        return Math.max(0, Math.floor((ms - Date.now()) / 1000));
    }

    function offerWaitingSinceIso(offer) {
        if (!offer) {
            return null;
        }
        if (offer.waiting_since_at) {
            return offer.waiting_since_at;
        }
        if (offer.ride && offer.ride.waiting_since_at) {
            return offer.ride.waiting_since_at;
        }
        if (offer.ride && offer.ride.created_at) {
            return offer.ride.created_at;
        }
        return offer.offered_at || null;
    }

    function offerSecondsWaiting(offer) {
        if (!offer) {
            return 0;
        }
        const since = offerWaitingSinceIso(offer);
        const fromIso = since ? secondsSinceIso(since) : 0;
        const fromServer = offer.seconds_waiting != null
            ? Math.max(0, Math.floor(Number(offer.seconds_waiting)))
            : 0;
        return Math.max(fromIso, fromServer);
    }

    function offerSecondsRemaining(offer) {
        if (!offer) {
            return 0;
        }
        if (offer.expires_at) {
            return secondsUntilIso(offer.expires_at);
        }
        if (offer.seconds_remaining == null) {
            return 0;
        }
        return Math.max(0, Math.floor(Number(offer.seconds_remaining)));
    }

    function mergeOfferFromServer(local, remote) {
        if (!local || !remote) {
            return remote || local;
        }
        local.expires_at = remote.expires_at;
        local.offered_at = remote.offered_at;
        local.waiting_since_at = remote.waiting_since_at;
        local.is_waiting = remote.is_waiting;
        local.is_pickup_overdue = remote.is_pickup_overdue;
        local.urgency = remote.urgency;
        local.status = remote.status;
        local.seconds_remaining = remote.seconds_remaining;
        local.seconds_waiting = remote.seconds_waiting;
        if (remote.ride) {
            local.ride = remote.ride;
        }
        return local;
    }

    function updateOfferUrgencyUi(offer) {
        const banner = $('#offer-waiting-banner');
        const waitingDot = $('#offer-waiting-dot');
        const card = $('#offer-card');
        const waiting = isOfferWaiting(offer);
        const pickupOverdue = isOfferPickupOverdue(offer);

        if (card) {
            card.classList.toggle('is-waiting', waiting && !pickupOverdue);
            card.classList.toggle('is-pickup-overdue', pickupOverdue);
        }
        if (waitingDot) {
            waitingDot.hidden = !waiting;
            waitingDot.classList.toggle('is-overdue', pickupOverdue);
        }
        if (banner) {
            if (pickupOverdue) {
                banner.hidden = false;
                banner.classList.add('is-visible', 'is-overdue');
                banner.textContent = 'Ophaalmoment verlopen — stel een nieuw tijdstip voor of weiger';
            } else if (waiting) {
                banner.hidden = false;
                banner.classList.add('is-visible');
                banner.classList.remove('is-overdue');
                banner.textContent = 'Rit wacht op een chauffeur — reageer nu';
            } else {
                banner.hidden = true;
                banner.classList.remove('is-visible', 'is-overdue');
                banner.textContent = '';
            }
        }

        if (waiting || pickupOverdue) {
            showNewRideAlert(true, true);
        } else {
            showNewRideAlert(false);
        }
        syncOfferAcceptButton(offer);
    }

    function offerAcceptButtonLabel(offer) {
        return isOfferPickupOverdue(offer) ? 'Nieuw tijdstip voorstellen' : 'Accepteren';
    }

    function syncOfferAcceptButton(offer) {
        const btn = $('#btn-accept');
        const actions = $('#offer-actions-panel');
        const overdue = isOfferPickupOverdue(offer);
        if (actions) {
            actions.classList.toggle('is-pickup-overdue', overdue);
        }
        if (!btn || btn.classList.contains('is-loading')) {
            return;
        }
        if (overdue) {
            btn.innerHTML = 'Nieuw tijdstip<br>voorstellen';
        } else {
            btn.textContent = 'Accepteren';
        }
        btn.setAttribute('aria-label', offerAcceptButtonLabel(offer));
    }

    function updateOfferTimerDisplay(offer) {
        updateOfferUrgencyUi(offer);
    }

    function clearOfferTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        const banner = $('#offer-waiting-banner');
        const waitingDot = $('#offer-waiting-dot');
        const card = $('#offer-card');
        if (waitingDot) {
            waitingDot.hidden = true;
        }
        if (banner) {
            banner.textContent = '';
            banner.hidden = true;
            banner.classList.remove('is-visible', 'is-overdue');
        }
        if (card) {
            card.classList.remove('is-waiting', 'is-pickup-overdue');
        }
        syncOfferAcceptButton(null);
    }

    function startOfferTimer(offer) {
        clearOfferTimer();
        if (!offer) {
            return;
        }
        syncAllPendingOffersWaitingState();
        updateOfferUrgencyUi(offer);
        timerInterval = setInterval(function () {
            if (!currentOffer) {
                clearOfferTimer();
                return;
            }
            syncAllPendingOffersWaitingState({ notify: true });
            updateOfferUrgencyUi(currentOffer);
        }, 1000);
    }

    function setOfferUiVisible(visible) {
        const strip = $('#offer-strip');
        if (strip) {
            strip.hidden = !visible;
        }
    }

    function setActiveRideUiVisible(visible) {
        const strip = $('#active-ride-strip');
        if (strip) {
            strip.hidden = !visible;
        }
        if (!visible) {
            setCompleteRideButtonVisible(false);
        }
        syncActiveRideJumpButton();
        syncTripsEmptyState();
    }

    function syncActiveRideJumpButton() {
        const btn = $('#btn-active-ride-jump');
        if (!btn) {
            return;
        }
        const hasActive = !!(currentActiveRide && isDriverInProgressRide(currentActiveRide));
        const viewingFull = hasActive && !activeRideInboxCollapsed && mainTab === 'trips';
        btn.hidden = !isOnline || !hasActive || viewingFull;
    }

    function isDriverAcceptedRide(ride) {
        if (!ride || !ride.id) {
            return false;
        }
        const status = ride.status != null ? String(ride.status) : '';
        return status === 'assigned' || status === 'accepted';
    }

    function isDriverInProgressRide(ride) {
        return !!(ride && ride.id && String(ride.status || '') === 'assigned');
    }

    function isDriverScheduledRide(ride) {
        return !!(ride && ride.id && String(ride.status || '') === 'accepted');
    }

    const CONTRACT_TZ = 'Europe/Amsterdam';

    function formatPickupAt(value) {
        if (!value) {
            return 'Ophaalmoment onbekend';
        }
        var date = new Date(value);
        if (isNaN(date.getTime())) {
            return 'Ophaalmoment onbekend';
        }
        try {
            return date.toLocaleString('nl-NL', {
                timeZone: CONTRACT_TZ,
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            });
        } catch (e) {
            return date.toISOString();
        }
    }

    function formatContractScheduleAt(value) {
        if (!value) {
            return '—';
        }
        var date = new Date(value);
        if (isNaN(date.getTime())) {
            return '—';
        }
        try {
            return date.toLocaleString('nl-NL', {
                timeZone: CONTRACT_TZ,
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            });
        } catch (e) {
            return formatPickupAt(value);
        }
    }

    function setPickupAtLine(el, pickupAt) {
        if (!el) {
            return;
        }
        if (!pickupAt) {
            el.hidden = true;
            el.textContent = '';
            return;
        }
        el.hidden = false;
        el.textContent = 'Ophaalmoment: ' + formatPickupAt(pickupAt);
    }

    function setCompleteRideButtonVisible(visible) {
        const btn = $('#btn-complete-ride');
        if (btn) {
            btn.hidden = !visible;
        }
    }

    const PAYMENT_FAILED_STATUSES = ['failed', 'canceled', 'expired'];

    function setDriverActionButtonVisible(btn, visible) {
        if (!btn) {
            return;
        }
        btn.hidden = !visible;
        if (visible) {
            btn.style.removeProperty('display');
        } else {
            btn.style.setProperty('display', 'none', 'important');
        }
    }

    function setPayRideButtonVisible(visible) {
        setDriverActionButtonVisible($('#btn-pay-ride'), visible);
    }

    function setSendInvoiceButtonVisible(visible) {
        setDriverActionButtonVisible($('#btn-send-invoice'), visible);
    }

    function isContractGroupRide(ride) {
        return !!(ride && ride.ride_type === 'contract_group' && isContractRide(ride));
    }

    function syncContractRideStripUi(ride) {
        const strip = $('#active-ride-strip');
        if (!strip) {
            return;
        }
        const isContract =
            !!(ride && isDriverInProgressRide(ride) && isContractRide(ride));
        const isTaxi =
            !!(ride && isDriverInProgressRide(ride) && !isContractRide(ride));
        strip.classList.toggle('is-contract-ride', isContract);
        strip.classList.toggle('is-taxi-ride', isTaxi);
        strip.classList.toggle(
            'is-contract-group-ride',
            !!(ride && isDriverInProgressRide(ride) && isContractGroupRide(ride))
        );
        if (isContract) {
            setPayRideButtonVisible(false);
            setSendInvoiceButtonVisible(false);
        }
    }

    function assignedRidesForOverview(primary, parked) {
        const seen = new Set();
        const out = [];
        function add(ride) {
            if (!ride || !ride.id || !isDriverInProgressRide(ride)) {
                return;
            }
            const key = String(ride.id);
            if (seen.has(key)) {
                return;
            }
            seen.add(key);
            out.push(ride);
        }
        add(primary);
        (Array.isArray(parked) ? parked : []).forEach(add);
        return out;
    }

    function assignedRideIsCurrentlyActive(ride) {
        return !!(
            ride &&
            ride.id &&
            currentActiveRide &&
            String(currentActiveRide.id) === String(ride.id) &&
            isDriverInProgressRide(ride)
        );
    }

    function assignedRideOverviewCardHtml(ride) {
        const rideId = String(ride.id);
        const isActive = assignedRideIsCurrentlyActive(ride);
        const activeBadge = isActive
            ? '<span class="offer-badge is-success">Actief</span>'
            : '';
        const navBtn = isActive
            ? '<button type="button" class="active-ride-nav-btn btn-active-ride-navigate" aria-label="Navigatie">' +
              navigationIconSvg() +
              '</button>'
            : '';
        return (
            '<div class="card offer-card active-ride-collapsed-banner parked-assigned-ride-card' +
            (isActive ? ' is-active-ride' : '') +
            rideKindCardClass(ride) +
            '" data-ride-id="' +
            escapeHtml(rideId) +
            '">' +
            '<div class="offer-card-top">' +
            '<div class="offer-badge-row">' +
            activeBadge +
            taxiBadgeHtml(ride) +
            contractBadgeHtml(ride) +
            nexaSuiteBadgeHtml(ride) +
            returnTripBadgeHtml(ride) +
            '</div>' +
            navBtn +
            '</div>' +
            '<p class="offer-title">' +
            escapeHtml(collapsedRideBannerTitle(ride)) +
            '</p>' +
            '<p class="offer-meta">' +
            escapeHtml(collapsedRideBannerMeta(ride)) +
            '</p>' +
            '<button type="button" class="btn btn-primary btn-open-parked-ride" data-ride-id="' +
            escapeHtml(rideId) +
            '">' +
            escapeHtml(collapsedRideOpenButtonLabel(ride)) +
            '</button>' +
            '</div>'
        );
    }

    function collapsedRideBannerTitle(ride) {
        if (isContractGroupRide(ride)) {
            return scheduledRideTitle(ride);
        }
        if (isReturnTripRide(ride) && returnTripLeg(ride) === 'waiting') {
            return 'Terugrit — wacht op vertrek';
        }
        if (isReturnTripRide(ride)) {
            return 'Retourrit #' + ride.id;
        }
        return 'Actieve rit #' + ride.id;
    }

    function collapsedRideBannerMeta(ride) {
        if (ride.ride_type === 'contract_group') {
            let metaText = scheduledRidePickupLabel(ride);
            const progress =
                (currentActiveRide && String(currentActiveRide.id) === String(ride.id)
                    ? activeRideStopsProgress
                    : null) ||
                ride.stops ||
                null;
            if (progress && progress.pickups_total) {
                metaText +=
                    ' · ' + progress.pickups_done + ' / ' + progress.pickups_total + ' ophaalstops';
            }
            return metaText;
        }
        if (isReturnTripRide(ride) && returnTripLeg(ride) === 'waiting') {
            return 'Terugreis ' + formatPickupAt(ride.return_at || ride.pickup_at);
        }
        return formatPickupAt(ride.pickup_at);
    }

    function collapsedRideOpenButtonLabel(ride) {
        if (isContractGroupRide(ride)) {
            return 'Stoplijst openen';
        }
        return 'Rit openen';
    }

    function renderAssignedRidesOverview(primary, parked) {
        const strip = $('#parked-assigned-rides-strip');
        const list = $('#parked-assigned-rides-list');
        if (!strip || !list) {
            return;
        }
        const rides = assignedRidesForOverview(primary, parked);
        if (!rides.length || !activeRideInboxCollapsed) {
            strip.hidden = true;
            list.innerHTML = '';
            syncTripsEmptyState();
            return;
        }
        strip.hidden = false;
        list.innerHTML = rides.map(assignedRideOverviewCardHtml).join('');
        syncTripsEmptyState();
    }

    function renderParkedAssignedRides(rides) {
        renderAssignedRidesOverview(currentActiveRide, rides);
    }

    function showAllRidesInbox() {
        if (currentActiveRide && isDriverInProgressRide(currentActiveRide)) {
            activeRideInboxCollapsed = true;
            viewingActiveRideId = null;
            setActiveRideUiVisible(false);
            renderAssignedRidesOverview(currentActiveRide, parkedAssignedRides);
            if (scheduledRides.length) {
                renderScheduledRides(scheduledRides);
            }
            setInboxView('offers');
            refreshInbox();
        }
        setMainTab('trips');
        syncActiveRideJumpButton();
        const scrollEl = document.querySelector('#screen-dispatch .dispatch-scroll');
        if (scrollEl) {
            scrollEl.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function showActiveRideFullPanel(rideId) {
        let ride = currentActiveRide;
        if (rideId != null && rideId !== '') {
            const id = String(rideId);
            if (currentActiveRide && String(currentActiveRide.id) === id) {
                ride = currentActiveRide;
            } else {
                ride = assignedRidesForOverview(currentActiveRide, parkedAssignedRides).find(function (r) {
                    return String(r.id) === id;
                });
            }
        }
        if (!ride || !isDriverInProgressRide(ride)) {
            currentActiveRide = null;
            viewingActiveRideId = null;
            parkedAssignedRides = [];
            activeRideInboxCollapsed = true;
            setActiveRideUiVisible(false);
            renderAssignedRidesOverview(null, []);
            syncActiveRideJumpButton();
            setMainTab('trips');
            refreshInbox();
            return;
        }
        currentActiveRide = ride;
        viewingActiveRideId = ride.id;
        activeRideInboxCollapsed = false;
        const parkedStrip = $('#parked-assigned-rides-strip');
        if (parkedStrip) {
            parkedStrip.hidden = true;
        }
        setMainTab('trips');
        renderActiveRide(ride);
        syncActiveRideJumpButton();
        const scrollEl = document.querySelector('#screen-dispatch .dispatch-scroll');
        if (scrollEl) {
            scrollEl.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function isRidePaymentPaid(ride) {
        const payment = ride && ride.payment ? ride.payment : {};
        return payment.status === 'paid';
    }

    function canCompleteActiveRide(ride) {
        if (!ride) {
            return false;
        }
        if (isReturnTripRide(ride) && returnTripLeg(ride) === 'waiting') {
            return false;
        }
        if (ride.ride_type === 'contract_group') {
            const progress = activeRideStopsProgress || ride.stops || null;
            if (progress && progress.pickups_total > 0 && !progress.all_pickups_done) {
                return false;
            }
        }
        const payment = ride.payment || {};
        if (typeof payment.can_complete === 'boolean') {
            return payment.can_complete;
        }
        if (payment.requires_payment_before_complete) {
            return isRidePaymentPaid(ride);
        }
        return true;
    }

    function syncCompleteRideButton(ride) {
        const btn = $('#btn-complete-ride');
        if (!btn) {
            return;
        }
        if (ride && isContractGroupRide(ride)) {
            setCompleteRideButtonVisible(false);
            btn.disabled = true;
            btn.classList.remove('is-disabled');
            btn.removeAttribute('aria-disabled');
            btn.title = '';
            syncReturnLegButtons(null);
            return;
        }
        const leg = returnTripLeg(ride);
        if (ride && isReturnTripRide(ride) && leg === 'waiting') {
            setCompleteRideButtonVisible(false);
            btn.textContent = 'Rit afronden';
            syncReturnLegButtons(ride);
            return;
        }
        syncReturnLegButtons(ride);
        const show = ride && isDriverInProgressRide(ride);
        btn.hidden = !show;
        if (!show) {
            btn.disabled = true;
            btn.classList.remove('is-disabled');
            btn.removeAttribute('aria-disabled');
            btn.title = '';
            btn.textContent = 'Rit afronden';
            syncReturnLegButtons(null);
            return;
        }
        if (isReturnTripRide(ride) && leg === 'outbound') {
            btn.textContent = 'Heenrit afgerond';
        } else if (isReturnTripRide(ride) && leg === 'return') {
            btn.textContent = 'Retourrit afronden';
        } else {
            btn.textContent = 'Rit afronden';
        }
        const canComplete = canCompleteActiveRide(ride);
        btn.disabled = !canComplete;
        btn.classList.toggle('is-disabled', !canComplete);
        if (canComplete) {
            btn.removeAttribute('aria-disabled');
            btn.title = '';
        } else {
            btn.setAttribute('aria-disabled', 'true');
            btn.title =
                ride.ride_type === 'contract_group' &&
                activeRideStopsProgress &&
                !activeRideStopsProgress.all_pickups_done
                    ? 'Verwerk eerst alle ophaalstops'
                    : 'Rond eerst de betaling af voordat je de rit afrondt';
        }
    }

    function syncReturnLegButtons(ride) {
        const startBtn = $('#btn-start-return');
        const releaseBtn = $('#btn-release-return');
        const show =
            ride &&
            isDriverInProgressRide(ride) &&
            isReturnTripRide(ride) &&
            returnTripLeg(ride) === 'waiting';
        if (startBtn) {
            startBtn.hidden = !show;
            if (show) {
                startBtn.dataset.rideId = String(ride.id);
            } else {
                delete startBtn.dataset.rideId;
            }
        }
        if (releaseBtn) {
            const canRelease = show && !!ride.can_release_return;
            releaseBtn.hidden = !canRelease;
            if (canRelease) {
                releaseBtn.dataset.rideId = String(ride.id);
            } else {
                delete releaseBtn.dataset.rideId;
            }
        }
    }

    function syncSendInvoiceButton(ride) {
        const btn = $('#btn-send-invoice');
        if (!btn) {
            return;
        }
        if (!ride || !isDriverInProgressRide(ride) || !driverPaymentEnabled || isContractRide(ride)) {
            setSendInvoiceButtonVisible(false);
            btn.disabled = true;
            btn.classList.remove('is-disabled');
            btn.removeAttribute('aria-disabled');
            return;
        }
        const invoice = ride.invoice || {};
        const canSend = !!invoice.can_send;
        const legLabel = invoice.invoice_leg_label;
        setSendInvoiceButtonVisible(true);
        btn.textContent = legLabel ? 'Factuur ' + legLabel + ' versturen' : 'Factuur versturen';
        btn.disabled = !canSend;
        btn.classList.toggle('is-disabled', !canSend);
        if (canSend) {
            btn.removeAttribute('aria-disabled');
            btn.title = invoice.includes_total_invoice
                ? 'Verstuurt terugritfactuur en totaalfactuur'
                : '';
        } else {
            btn.setAttribute('aria-disabled', 'true');
            if (invoice.return_invoice_sent || invoice.outbound_invoice_sent) {
                const pendingLeg = !invoice.outbound_invoice_sent
                    ? 'heenrit'
                    : (!invoice.return_invoice_sent ? 'terugrit' : null);
                btn.title = pendingLeg
                    ? 'Markeer de ' + pendingLeg + ' eerst als betaald'
                    : 'Factuur is al verstuurd';
            } else {
                btn.title = 'Markeer het ritdeel eerst als betaald';
            }
        }
    }

    function parsePaymentAmountInput() {
        const amountInput = $('#payment-amount');
        const amount = amountInput ? parseFloat(amountInput.value) : NaN;
        if (!Number.isFinite(amount) || amount < 0.01) {
            return null;
        }
        return Math.round(amount * 100) / 100;
    }

    function isPaymentQrVisible() {
        const qrSection = $('#payment-qr-section');
        return !!(qrSection && !qrSection.hidden);
    }

    function updatePaymentCloseButtonLabel(qrVisible) {
        const closeBtn = $('#btn-payment-close');
        if (closeBtn) {
            closeBtn.textContent = qrVisible ? 'Terug' : 'Sluiten';
        }
    }

    function syncPaymentPanelUi(options) {
        const opts = options || {};
        const qrVisible = !!opts.qrVisible;
        const createBtn = $('#btn-payment-create');
        const cashBtn = $('#btn-cash-paid');
        const amountInput = $('#payment-amount');
        if (createBtn) {
            createBtn.hidden = qrVisible;
            if (!qrVisible) {
                clearButtonLoading(createBtn);
                createBtn.disabled = false;
            }
        }
        if (cashBtn) {
            cashBtn.hidden = qrVisible;
            cashBtn.disabled = qrVisible;
        }
        if (amountInput) {
            amountInput.disabled = qrVisible;
        }
        updatePaymentCloseButtonLabel(qrVisible);
    }

    function hidePaymentQr() {
        stopPaymentPoll();
        const qrSection = $('#payment-qr-section');
        const qrImg = $('#payment-qr-img');
        const statusText = $('#payment-status-text');
        if (qrSection) {
            qrSection.hidden = true;
        }
        if (qrImg) {
            qrImg.removeAttribute('src');
        }
        if (statusText) {
            statusText.textContent = 'Wachten op betaling…';
        }
        syncPaymentPanelUi({ qrVisible: false });
    }

    function resolvePaymentError(ride, openPayment) {
        const payment = ride && ride.payment ? ride.payment : {};
        if (payment.payment_error) {
            return payment.payment_error;
        }
        if (openPayment && PAYMENT_FAILED_STATUSES.indexOf(openPayment.status) !== -1) {
            const labels = {
                failed: 'Betaling is mislukt. Probeer opnieuw te betalen.',
                canceled: 'Betaling is geannuleerd. Probeer opnieuw te betalen.',
                expired: 'Betaling is verlopen. Probeer opnieuw te betalen.',
            };
            return labels[openPayment.status] || 'Betaling is niet gelukt. Probeer opnieuw te betalen.';
        }
        return '';
    }

    function syncRideActionButtons(ride, openPayment) {
        const errEl = $('#payment-ride-error');
        if (!ride || !isDriverInProgressRide(ride)) {
            syncContractRideStripUi(null);
            setPayRideButtonVisible(false);
            syncSendInvoiceButton(null);
            syncCompleteRideButton(null);
            if (errEl) {
                errEl.hidden = true;
                errEl.textContent = '';
            }
            return;
        }
        if (isContractRide(ride)) {
            syncContractRideStripUi(ride);
            setPayRideButtonVisible(false);
            syncSendInvoiceButton(null);
            syncCompleteRideButton(ride);
            if (errEl) {
                errEl.hidden = true;
                errEl.textContent = '';
            }
            return;
        }
        syncContractRideStripUi(ride);
        const payment = ride.payment || {};
        const isPaid = payment.status === 'paid';
        const leg = returnTripLeg(ride);
        const payAllowedLeg =
            !isReturnTripRide(ride) || leg === 'outbound' || leg === 'return';
        const showPayButton =
            !isContractRide(ride) &&
            driverPaymentEnabled &&
            payAllowedLeg &&
            (payment.requires_payment_before_complete || isPaid);
        const paymentError = isPaid ? '' : resolvePaymentError(ride, openPayment);
        setPayRideButtonVisible(showPayButton);
        syncSendInvoiceButton(ride);
        syncCompleteRideButton(ride);
        const payBtn = $('#btn-pay-ride');
        if (payBtn) {
            payBtn.disabled = isPaid;
            payBtn.classList.toggle('is-paid', isPaid);
            payBtn.textContent = isPaid ? 'Betaald' : 'Betalen';
            if (isPaid) {
                payBtn.setAttribute('aria-disabled', 'true');
            } else {
                payBtn.removeAttribute('aria-disabled');
            }
        }
        if (errEl) {
            if (paymentError) {
                errEl.hidden = false;
                errEl.textContent = paymentError;
            } else {
                errEl.hidden = true;
                errEl.textContent = '';
            }
        }
    }

    function stopPaymentPoll() {
        if (paymentPollTimer) {
            clearInterval(paymentPollTimer);
            paymentPollTimer = null;
        }
    }

    function setDispatchOverlayOpen(open) {
        if (screenDispatch) {
            screenDispatch.classList.toggle('overlay-panel-open', !!open);
        }
    }

    function syncMainTabPanelsForOverlay() {
        const paymentOpen = isPaymentPanelOpen();
        const invoiceOpen = isInvoicePanelOpen();
        const overlayOpen = paymentOpen || invoiceOpen;
        document.querySelectorAll('[data-main-tab-panel]').forEach(function (panel) {
            if (overlayOpen) {
                panel.hidden = true;
                return;
            }
            const key = panel.getAttribute('data-main-tab-panel');
            panel.hidden = key !== mainTab;
        });
        setDispatchOverlayOpen(overlayOpen);
    }

    function isPaymentPanelOpen() {
        const panel = $('#payment-panel');
        return !!(panel && panel.classList.contains('is-open') && !panel.hidden);
    }

    function isInvoicePanelOpen() {
        const panel = $('#invoice-panel');
        return !!(panel && panel.classList.contains('is-open') && !panel.hidden);
    }

    function isActiveOpenPayment(openPayment) {
        return !!(
            openPayment &&
            openPayment.checkout_url &&
            openPayment.status === 'open' &&
            PAYMENT_FAILED_STATUSES.indexOf(openPayment.status) === -1
        );
    }

    function openPaymentAmountsMatch(openPayment, amount) {
        if (!openPayment || amount == null) {
            return false;
        }
        const openAmount = parseFloat(openPayment.amount);
        if (isNaN(openAmount)) {
            return false;
        }
        return Math.abs(openAmount - amount) < 0.005;
    }

    function closePaymentPanel() {
        const panel = $('#payment-panel');
        if (panel) {
            panel.classList.remove('is-open');
            panel.hidden = true;
        }
        stopPaymentPoll();
        hidePaymentQr();
        syncMainTabPanelsForOverlay();
    }

    function openPaymentPanel(ride) {
        const panel = $('#payment-panel');
        const amountInput = $('#payment-amount');
        if (!panel || !ride) {
            return;
        }
        stopPaymentPoll();
        hidePaymentQr();
        closeInvoicePanel();
        const payment = ride.payment || {};
        const amount =
            payment.amount_due != null
                ? payment.amount_due
                : payment.leg_amount != null
                  ? payment.leg_amount
                  : ride.quoted_price;
        if (amountInput) {
            amountInput.value =
                amount != null && !isNaN(parseFloat(amount))
                    ? String(parseFloat(amount).toFixed(2))
                    : '';
            amountInput.disabled = false;
        }
        syncPaymentPanelUi({ qrVisible: false });
        setMainTab('trips');
        panel.hidden = false;
        panel.classList.add('is-open');
        syncMainTabPanelsForOverlay();
        const scrollEl = document.querySelector('#screen-dispatch .dispatch-scroll');
        if (scrollEl) {
            scrollEl.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    async function loadPaymentState(rideId) {
        const res = await api('/dispatch/rides/' + rideId + '/payment');
        const data = res.data || null;
        cachedOpenPayment = data && data.open_payment ? data.open_payment : null;
        return data;
    }

    function showPaymentQr(openPayment) {
        const qrSection = $('#payment-qr-section');
        const qrImg = $('#payment-qr-img');
        const amountInput = $('#payment-amount');
        const createBtn = $('#btn-payment-create');
        if (!openPayment || !openPayment.checkout_url) {
            return;
        }
        if (amountInput) {
            amountInput.disabled = true;
        }
        syncPaymentPanelUi({ qrVisible: true });
        if (qrImg) {
            qrImg.src =
                openPayment.qr_url ||
                'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' +
                    encodeURIComponent(openPayment.checkout_url);
        }
        if (qrSection) {
            qrSection.hidden = false;
        }
    }

    function startPaymentPoll(rideId) {
        stopPaymentPoll();
        paymentPollTimer = setInterval(async function () {
            try {
                const data = await loadPaymentState(rideId);
                if (!data || !data.ride) {
                    return;
                }
                currentActiveRide = data.ride;
                if (
                    data.open_payment &&
                    PAYMENT_FAILED_STATUSES.indexOf(data.open_payment.status) !== -1
                ) {
                    stopPaymentPoll();
                    closePaymentPanel();
                    renderActiveRide(data.ride);
                    return;
                }
                syncRideActionButtons(data.ride, data.open_payment);
                if (data.open_payment && data.open_payment.status === 'paid') {
                    stopPaymentPoll();
                    closePaymentPanel();
                    renderActiveRide(data.ride);
                    return;
                }
                if (data.payment && data.payment.status === 'paid') {
                    stopPaymentPoll();
                    const statusText = $('#payment-status-text');
                    if (statusText) {
                        statusText.textContent = 'Betaling ontvangen.';
                    }
                    setTimeout(function () {
                        closePaymentPanel();
                        renderActiveRide(data.ride);
                        refreshActiveRideInvoiceState();
                    }, 800);
                }
            } catch (e) {
                /* poll errors ignored */
            }
        }, 2500);
    }

    async function createRidePayment() {
        const rideId = resolveActiveRideId();
        if (!rideId) {
            return;
        }
        const amount = parsePaymentAmountInput();
        if (amount === null) {
            alert('Vul een geldig bedrag in.');
            return;
        }
        const createBtn = $('#btn-payment-create');
        setButtonLoading(createBtn, true, 'QR laden…');
        try {
            let data = null;
            try {
                data = await loadPaymentState(rideId);
            } catch (e) {
                data = null;
            }
            if (data && data.ride) {
                currentActiveRide = data.ride;
            }
            const openPayment = data && data.open_payment ? data.open_payment : cachedOpenPayment;
            if (
                isActiveOpenPayment(openPayment) &&
                openPaymentAmountsMatch(openPayment, amount)
            ) {
                showPaymentQr(openPayment);
                startPaymentPoll(rideId);
                return;
            }
            const res = await api('/dispatch/rides/' + rideId + '/payment', {
                method: 'POST',
                body: { amount: amount },
            });
            data = res.data || {};
            if (data.ride) {
                currentActiveRide = data.ride;
            }
            cachedOpenPayment = data.open_payment || null;
            if (data.open_payment) {
                showPaymentQr(data.open_payment);
                startPaymentPoll(rideId);
            }
        } catch (e) {
            alert(e.message);
        } finally {
            clearButtonLoading(createBtn);
        }
    }

    let pendingCashConfirmAmount = null;
    let cashConfirmSubmitting = false;

    function closeCashConfirmDialog() {
        const dialog = $('#cash-confirm-dialog');
        const okBtn = $('#cash-confirm-ok');
        pendingCashConfirmAmount = null;
        if (dialog) {
            dialog.classList.add('driver-dialog--instant');
            dialog.classList.remove('is-open');
            dialog.hidden = true;
            dialog.setAttribute('aria-hidden', 'true');
            requestAnimationFrame(function () {
                dialog.classList.remove('driver-dialog--instant');
            });
        }
        if (okBtn) {
            clearButtonLoading(okBtn);
            okBtn.disabled = false;
        }
        document.body.classList.remove('driver-dialog-open');
    }

    function showCashConfirmDialog(amount) {
        const dialog = $('#cash-confirm-dialog');
        const amountEl = $('#cash-confirm-amount');
        if (!dialog) {
            showDriverConfirm(
                'Bevestig: klant heeft ' +
                    formatEuro(amount) +
                    ' contant betaald? Dit bedrag wordt vastgelegd.',
                {
                    title: 'Contant betalen?',
                    confirmLabel: 'Bevestigen',
                }
            ).then(function (ok) {
                if (ok) {
                    void executeRideCashPayment(amount);
                }
            });
            return;
        }
        pendingCashConfirmAmount = amount;
        if (amountEl) {
            amountEl.textContent = formatEuro(amount);
        }
        const okBtn = $('#cash-confirm-ok');
        if (okBtn) {
            clearButtonLoading(okBtn);
            okBtn.disabled = false;
        }
        dialog.hidden = false;
        dialog.setAttribute('aria-hidden', 'false');
        dialog.classList.add('is-open');
        document.body.classList.add('driver-dialog-open');
        if (okBtn) {
            okBtn.focus();
        }
    }

    function initCashConfirmDialog() {
        const dialog = $('#cash-confirm-dialog');
        if (!dialog) {
            return;
        }
        const okBtn = $('#cash-confirm-ok');
        const cancelBtn = $('#cash-confirm-cancel');
        const backdrop = dialog.querySelector('[data-cash-confirm-dismiss]');

        if (okBtn) {
            okBtn.addEventListener('click', function () {
                if (cashConfirmSubmitting) {
                    return;
                }
                const amount = pendingCashConfirmAmount;
                if (amount === null) {
                    closeCashConfirmDialog();
                    return;
                }
                closeCashConfirmDialog();
                void executeRideCashPayment(amount);
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                closeCashConfirmDialog();
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                closeCashConfirmDialog();
            });
        }
        document.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Escape' || !dialog.classList.contains('is-open')) {
                return;
            }
            closeCashConfirmDialog();
        });
    }

    async function executeRideCashPayment(amount) {
        const rideId = resolveActiveRideId();
        if (!rideId) {
            return;
        }
        if (cashConfirmSubmitting) {
            return;
        }
        cashConfirmSubmitting = true;
        const cashBtn = $('#btn-cash-paid');
        if (cashBtn) {
            cashBtn.disabled = true;
        }
        try {
            const res = await api('/dispatch/rides/' + rideId + '/payment/cash', {
                method: 'POST',
                body: { amount: amount },
            });
            stopPaymentPoll();
            closePaymentPanel();
            if (res.data && res.data.ride) {
                currentActiveRide = res.data.ride;
                renderActiveRide(res.data.ride);
                refreshActiveRideInvoiceState();
            } else {
                await refreshInbox();
            }
        } catch (e) {
            alert(e.message || 'Contante betaling kon niet worden geregistreerd.');
            syncPaymentPanelUi({ qrVisible: false });
        } finally {
            cashConfirmSubmitting = false;
            if (cashBtn) {
                cashBtn.disabled = false;
            }
        }
    }

    function markRideCashPaid() {
        const rideId = resolveActiveRideId();
        if (!rideId) {
            return;
        }
        const amount = parsePaymentAmountInput();
        if (amount === null) {
            alert('Vul een geldig bedrag in.');
            return;
        }
        const dialog = $('#cash-confirm-dialog');
        if (!dialog) {
            showDriverConfirm(
                'Bevestig: klant heeft ' +
                    formatEuro(amount) +
                    ' contant betaald? Dit bedrag wordt vastgelegd.',
                {
                    title: 'Contant betalen?',
                    confirmLabel: 'Bevestigen',
                }
            ).then(function (ok) {
                if (ok) {
                    void executeRideCashPayment(amount);
                }
            });
            return;
        }
        showCashConfirmDialog(amount);
    }

    function closeInvoicePanel() {
        const panel = $('#invoice-panel');
        if (panel) {
            panel.classList.remove('is-open');
            panel.hidden = true;
        }
        const status = $('#invoice-send-status');
        if (status) {
            status.hidden = true;
            status.textContent = '';
            status.classList.remove('is-error');
        }
        syncMainTabPanelsForOverlay();
    }

    function openInvoicePanel(invoiceData) {
        const panel = $('#invoice-panel');
        const emailInput = $('#invoice-email');
        const numberInput = $('#invoice-number');
        const panelTitle = panel ? panel.querySelector('.driver-section-head h2') : null;
        if (!panel) {
            return;
        }
        closePaymentPanel();
        const data = invoiceData || {};
        if (panelTitle) {
            panelTitle.textContent = data.invoice_leg_label
                ? 'Factuur ' + data.invoice_leg_label + ' versturen'
                : 'Factuur versturen';
        }
        if (emailInput) {
            emailInput.value = data.customer_email || '';
        }
        if (numberInput) {
            numberInput.value = data.invoice_number || '';
        }
        const sendBtn = $('#btn-invoice-send');
        if (sendBtn) {
            sendBtn.disabled = !data.invoice_number;
            sendBtn.textContent = data.includes_total_invoice
                ? 'Versturen (incl. totaalfactuur)'
                : 'Versturen';
        }
        const meta = panel.querySelector('.offer-meta');
        if (meta) {
            meta.textContent = data.includes_total_invoice
                ? 'De terugritfactuur en totaalfactuur worden als PDF naar de klant gemaild.'
                : 'De factuur wordt als PDF naar de klant gemaild.';
        }
        const status = $('#invoice-send-status');
        if (status) {
            status.hidden = true;
            status.textContent = '';
            status.classList.remove('is-error');
        }
        setMainTab('trips');
        panel.hidden = false;
        panel.classList.add('is-open');
        syncMainTabPanelsForOverlay();
        const scrollEl = document.querySelector('#screen-dispatch .dispatch-scroll');
        if (scrollEl) {
            scrollEl.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    async function refreshActiveRideInvoiceState() {
        const rideId = resolveActiveRideId();
        if (!rideId || !currentActiveRide || isContractRide(currentActiveRide)) {
            return;
        }
        try {
            const res = await api('/dispatch/rides/' + rideId + '/invoice');
            if (currentActiveRide && String(currentActiveRide.id) === String(rideId)) {
                currentActiveRide.invoice = res.data || {};
                syncSendInvoiceButton(currentActiveRide);
            }
        } catch (e) {
            /* factuur laden mislukt — knop blijft op basis van ritstatus */
        }
    }

    async function openSendInvoiceFlow() {
        const rideId = resolveActiveRideId();
        if (!rideId) {
            return;
        }
        if (currentActiveRide && isContractRide(currentActiveRide)) {
            return;
        }
        const sendInvoiceBtn = $('#btn-send-invoice');
        if (sendInvoiceBtn && (sendInvoiceBtn.disabled || sendInvoiceBtn.hidden)) {
            return;
        }
        setButtonLoading(sendInvoiceBtn, true);
        try {
            const res = await api('/dispatch/rides/' + rideId + '/invoice');
            const data = res.data || {};
            openInvoicePanel(data);
        } catch (e) {
            alert(e.message || 'Factuurgegevens konden niet worden geladen.');
        } finally {
            clearButtonLoading(sendInvoiceBtn);
        }
    }

    async function sendRideInvoice() {
        const rideId = resolveActiveRideId();
        if (!rideId) {
            return;
        }
        const emailInput = $('#invoice-email');
        const numberInput = $('#invoice-number');
        const email = emailInput ? String(emailInput.value).trim() : '';
        const invoiceNumber = numberInput ? String(numberInput.value).trim() : '';
        if (!email) {
            alert('Vul een e-mailadres in.');
            return;
        }
        if (!invoiceNumber) {
            alert('Factuurnummer ontbreekt. Sluit dit venster en open factuur versturen opnieuw.');
            return;
        }
        const sendBtn = $('#btn-invoice-send');
        const status = $('#invoice-send-status');
        setButtonLoading(sendBtn, true, 'Versturen…');
        try {
            const res = await api('/dispatch/rides/' + rideId + '/invoice/send', {
                method: 'POST',
                body: {
                    email: email,
                    invoice_number: invoiceNumber || undefined,
                },
            });
            if (res.data && res.data.ride) {
                currentActiveRide = res.data.ride;
                renderActiveRide(res.data.ride);
            }
            if (status) {
                status.hidden = false;
                status.classList.remove('is-error');
                status.textContent = res.message || 'Factuur verstuurd.';
            }
            setTimeout(closeInvoicePanel, 1200);
        } catch (e) {
            if (status) {
                status.hidden = false;
                status.classList.add('is-error');
                status.textContent = e.message || 'Versturen mislukt.';
            } else {
                alert(e.message);
            }
        } finally {
            clearButtonLoading(sendBtn);
        }
    }

    async function openPayRideFlow() {
        const rideId = resolveActiveRideId();
        if (!rideId) {
            return;
        }
        if (currentActiveRide && isContractRide(currentActiveRide)) {
            return;
        }
        const payBtn = $('#btn-pay-ride');
        const payWasPaid = payBtn && payBtn.classList.contains('is-paid');
        setButtonLoading(payBtn, true);
        try {
            const data = await loadPaymentState(rideId);
            if (data && data.ride) {
                currentActiveRide = data.ride;
            }
            syncRideActionButtons(currentActiveRide, data && data.open_payment);
            // Altijd eerst de keuze-pagina (bedrag / QR / contant), nooit direct de QR.
            openPaymentPanel(currentActiveRide);
        } catch (e) {
            alert(e.message);
        } finally {
            clearButtonLoading(payBtn, {
                disabled: payWasPaid || (payBtn && payBtn.classList.contains('is-paid')),
            });
        }
    }

    function renderOffer(offer, index, total, options) {
        const empty = $('#inbox-empty');
        const opts = options || {};

        if (!offer || !offer.ride) {
            currentOffer = null;
            pendingOffers = [];
            offerQueueIndex = 0;
            clearOfferNotificationState();
            clearOfferTimer();
            showNewRideAlert(false);
            updateOfferQueueUi(0, 0);
            setOfferUiVisible(false);
            updateEmptyState();
            return;
        }

        syncAllPendingOffersWaitingState();

        const queueIndex = index != null ? index : offerQueueIndex;
        const queueTotal = total != null ? total : (pendingOffers.length || 1);
        offerQueueIndex = queueIndex;
        updateOfferQueueUi(queueIndex, queueTotal);

        if (lastNotifiedOfferId !== offer.id) {
            if (opts.skipNotify) {
                notifiedOfferIds.add(offer.id);
                lastNotifiedOfferId = offer.id;
            } else {
                onOfferFirstSeen(offer);
            }
        }

        currentOffer = offer;
        if (empty) empty.hidden = true;
        setActiveRideUiVisible(false);
        setOfferUiVisible(true);
        setOfferActionButtonsDisabled(false);
        setQueueNavDisabled(queueIndex, queueTotal);

        const btnAccept = $('#btn-accept');
        const btnDecline = $('#btn-decline');
        if (btnAccept) {
            btnAccept.dataset.offerId = String(offer.id);
        }
        if (btnDecline) {
            btnDecline.dataset.offerId = String(offer.id);
        }

        setAddressLink($('#offer-pickup'), offer.ride.pickup_address);
        setAddressLink($('#offer-dropoff'), offer.ride.dropoff_address);
        setPickupAtLine($('#offer-pickup-at'), offer.ride.pickup_at);
        setCustomerLine($('#offer-customer'), offer.ride.customer_name, offer.ride.customer_phone);
        setRidePriceDisplay($('#offer-price'), offer.ride);

        const badge = $('#offer-badge');
        if (badge) {
            const label = offerBadgeLabel(offer.ride, offer);
            badge.textContent = label;
            const isNexa = label === 'NEXA Suite' || (offer.ride && isNexaSuiteRide(offer.ride) && label === nexaSuiteRideLabel(offer.ride));
            badge.classList.toggle('is-muted', label !== 'Nieuw' && label !== 'Groep' && label !== 'Verlopen' && !isNexa);
            badge.classList.toggle('is-success', label === 'Nieuw');
            badge.classList.toggle('is-danger', label === 'Verlopen');
            badge.classList.toggle('is-nexa-suite', !!isNexa);
        }
        const vehicleBadge = $('#offer-vehicle-badge');
        if (vehicleBadge) {
            vehicleBadge.textContent = offerVehicleLabel(offer.ride);
        }
        const ago = $('#offer-ago');
        if (ago) {
            ago.textContent = formatOfferAgo(offer.offered_at || offer.ride.created_at || offer.ride.waiting_since_at);
        }
        const distEl = $('#offer-stats-distance');
        if (distEl) {
            distEl.textContent = rideDistanceLabel(offer.ride);
        }
        const durEl = $('#offer-stats-duration');
        if (durEl) {
            durEl.textContent = rideDurationLabel(offer.ride);
        }

        startOfferTimer(offer);
    }

    function isNexaSuiteRide(ride) {
        if (!ride) {
            return false;
        }
        if (ride.is_nexa_suite) {
            return true;
        }
        return ride.source === 'nexa_suite';
    }

    function nexaSuiteRideLabel(ride) {
        if (ride && ride.nexa_suite_label) {
            return String(ride.nexa_suite_label);
        }
        return 'NEXA Suite';
    }

    function nexaSuiteBadgeHtml(ride) {
        if (!isNexaSuiteRide(ride)) {
            return '';
        }
        return '<span class="nexa-suite-ride-badge">' + escapeHtml(nexaSuiteRideLabel(ride)) + '</span>';
    }

    function isContractRide(ride) {
        if (!ride) {
            return false;
        }
        if (ride.is_contract) {
            return true;
        }
        if (ride.transport_contract_id) {
            return true;
        }
        if (ride.source === 'contract') {
            return true;
        }
        const payment = ride.payment || {};
        if (payment.method === 'contract') {
            return true;
        }
        return ride.ride_type === 'contract_group' || ride.ride_type === 'contract_individual';
    }

    function contractRideScheduleInstant(ride) {
        if (!ride) {
            return null;
        }
        const schedule = ride.schedule || {};
        if (schedule.destination_arrival_at) {
            return schedule.destination_arrival_at;
        }
        if (schedule.departure_at) {
            return schedule.departure_at;
        }
        return ride.pickup_at || null;
    }

    function calendarDateInContractTz(date) {
        const parts = new Intl.DateTimeFormat('en-CA', {
            timeZone: CONTRACT_TZ,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        }).formatToParts(date);
        return {
            year: parseInt(
                parts.find(function (part) {
                    return part.type === 'year';
                }).value,
                10
            ),
            month: parseInt(
                parts.find(function (part) {
                    return part.type === 'month';
                }).value,
                10
            ),
            day: parseInt(
                parts.find(function (part) {
                    return part.type === 'day';
                }).value,
                10
            ),
        };
    }

    function contractRideCalendarDateKey(ride) {
        if (ride && ride.scheduled_date) {
            return String(ride.scheduled_date).slice(0, 10);
        }
        const instant = contractRideScheduleInstant(ride);
        if (!instant) {
            return null;
        }
        const date = new Date(instant);
        if (isNaN(date.getTime())) {
            return null;
        }
        const cal = calendarDateInContractTz(date);
        return (
            cal.year +
            '-' +
            String(cal.month).padStart(2, '0') +
            '-' +
            String(cal.day).padStart(2, '0')
        );
    }

    function currentContractWeekBounds() {
        const cal = calendarDateInContractTz(new Date());
        const anchor = new Date(Date.UTC(cal.year, cal.month - 1, cal.day));
        const isoWeekday = anchor.getUTCDay() === 0 ? 7 : anchor.getUTCDay();
        const monday = new Date(anchor);
        monday.setUTCDate(anchor.getUTCDate() - (isoWeekday - 1));
        const sunday = new Date(monday);
        sunday.setUTCDate(monday.getUTCDate() + 6);
        function formatKey(date) {
            return (
                date.getUTCFullYear() +
                '-' +
                String(date.getUTCMonth() + 1).padStart(2, '0') +
                '-' +
                String(date.getUTCDate()).padStart(2, '0')
            );
        }
        return {
            start: formatKey(monday),
            end: formatKey(sunday),
        };
    }

    function isContractRideInCurrentWeek(ride) {
        const dateKey = contractRideCalendarDateKey(ride);
        if (!dateKey) {
            return true;
        }
        const bounds = currentContractWeekBounds();
        return dateKey >= bounds.start && dateKey <= bounds.end;
    }

    function todayContractDateKey() {
        const cal = calendarDateInContractTz(new Date());
        return (
            cal.year +
            '-' +
            String(cal.month).padStart(2, '0') +
            '-' +
            String(cal.day).padStart(2, '0')
        );
    }

    function isContractRideToday(ride) {
        if (!isContractRide(ride)) {
            return true;
        }
        const dateKey = contractRideCalendarDateKey(ride);
        return !!(dateKey && dateKey === todayContractDateKey());
    }

    function formatContractScheduleDateLabel(ride) {
        const dateKey = contractRideCalendarDateKey(ride);
        if (!dateKey) {
            return 'de ritdag';
        }
        const parts = dateKey.split('-');
        const date = new Date(Date.UTC(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 12));
        if (isNaN(date.getTime())) {
            return 'de ritdag';
        }
        return date.toLocaleDateString('nl-NL', {
            timeZone: CONTRACT_TZ,
            weekday: 'short',
            day: 'numeric',
            month: 'short',
        });
    }

    function scheduledRideActionsHtml(ride, rideId) {
        if (ride && (ride.is_scheduled_overdue || ride.is_pickup_overdue) && !isContractRide(ride)) {
            return overdueRideActionsHtml(ride, rideId);
        }
        const escapedRideId = escapeHtml(rideId);
        const canStartToday = !isContractRide(ride) || isContractRideToday(ride);
        let html = '<div class="offer-actions scheduled-ride-actions">';
        if (!isContractRide(ride)) {
            html +=
                '<button type="button" class="btn btn-danger btn-release-ride" data-ride-id="' +
                escapedRideId +
                '">Vrijgeven</button>';
        }
        if (canStartToday) {
            html +=
                '<button type="button" class="btn btn-primary btn-start-ride" data-ride-id="' +
                escapedRideId +
                '">Rit starten</button>';
        } else if (isContractRide(ride)) {
            html +=
                '<p class="offer-meta contract-start-hint">Te starten op ' +
                escapeHtml(formatContractScheduleDateLabel(ride)) +
                '</p>';
        }
        html += '</div>';
        return html;
    }

    function isOpenPickupProposalRide(ride) {
        const status = String((ride && ride.pickup_proposal && ride.pickup_proposal.status) || '');
        return status === 'pending' || status === 'declined';
    }

    function pickupProposalBannerHtml(ride) {
        const proposal = (ride && ride.pickup_proposal) || {};
        const status = String(proposal.status || '');
        if (!status) {
            return '';
        }
        let text = '';
        let cls = 'offer-waiting-banner is-visible';
        if (status === 'pending') {
            text =
                'Voorstel verstuurd — wacht op reactie van de klant' +
                (proposal.proposed_at ? ' (' + formatPickupAt(proposal.proposed_at) + ')' : '');
        } else if (status === 'accepted') {
            cls += ' is-success-banner';
            text =
                'Klant heeft het nieuwe ophaalmoment geaccepteerd' +
                (proposal.proposed_at ? ': ' + formatPickupAt(proposal.proposed_at) : '');
        } else if (status === 'declined') {
            cls += ' is-overdue';
            text = 'Klant heeft het voorstel geweigerd';
            if (proposal.customer_remark) {
                text += '. Opmerking: ' + proposal.customer_remark;
            }
        } else {
            return '';
        }
        if (status !== 'declined' && proposal.customer_remark) {
            text += '. Opmerking: ' + proposal.customer_remark;
        }
        return (
            '<p class="' +
            cls +
            '" role="status">' +
            escapeHtml(text) +
            '</p>'
        );
    }

    function overdueRideActionsHtml(ride, rideId) {
        const escapedRideId = escapeHtml(rideId);
        const contract = isContractRide(ride);
        const proposal = (ride && ride.pickup_proposal) || {};
        const proposalStatus = String(proposal.status || '');
        let html = '<div class="offer-actions overdue-ride-actions">';
        if (contract) {
            html +=
                '<button type="button" class="btn btn-danger btn-release-ride btn-overdue-complete-ride" data-ride-id="' +
                escapedRideId +
                '">Rit afronden</button>';
        } else {
            html +=
                '<button type="button" class="btn btn-danger btn-release-ride" data-ride-id="' +
                escapedRideId +
                '">Vrijgeven</button>';
            if (proposalStatus === 'accepted') {
                html +=
                    '<button type="button" class="btn btn-primary btn-start-ride" data-ride-id="' +
                    escapedRideId +
                    '">Rit starten</button>';
            } else if (proposalStatus === 'pending') {
                html +=
                    '<button type="button" class="btn btn-ghost btn-propose-pickup" data-ride-id="' +
                    escapedRideId +
                    '">Opnieuw voorstellen</button>';
            } else {
                html +=
                    '<button type="button" class="btn btn-primary btn-propose-pickup" data-ride-id="' +
                    escapedRideId +
                    '">Nieuw tijdstip voorstellen</button>';
            }
        }
        html += '</div>';
        return html;
    }

    function addCalendarDaysToDateKey(dateKey, days) {
        const parts = dateKey.split('-');
        const date = new Date(Date.UTC(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 12));
        if (isNaN(date.getTime())) {
            return dateKey;
        }
        date.setUTCDate(date.getUTCDate() + days);
        return (
            date.getUTCFullYear() +
            '-' +
            String(date.getUTCMonth() + 1).padStart(2, '0') +
            '-' +
            String(date.getUTCDate()).padStart(2, '0')
        );
    }

    function isContractIndividualRideInHorizon(ride, horizonDays) {
        const dateKey = contractRideCalendarDateKey(ride);
        if (!dateKey) {
            return true;
        }
        const today = todayContractDateKey();
        const endKey = addCalendarDaysToDateKey(today, horizonDays);
        return dateKey >= today && dateKey <= endKey;
    }

    function isContractRideVisibleInScheduledInbox(ride) {
        if (!isContractRide(ride)) {
            return true;
        }
        if (ride.is_scheduled_overdue) {
            return false;
        }
        if (ride.ride_type === 'contract_individual') {
            return isContractIndividualRideInHorizon(ride, 14);
        }
        return isContractRideInCurrentWeek(ride);
    }

    function filterScheduledRidesForInbox(rides) {
        if (!Array.isArray(rides)) {
            return [];
        }
        return sortRidesEarliestPickupFirst(
            rides.filter(function (ride) {
                return isContractRideVisibleInScheduledInbox(ride);
            })
        );
    }

    function prepareScheduledRidesForInbox(scheduled) {
        return filterScheduledRidesForInbox(Array.isArray(scheduled) ? scheduled : []).filter(
            function (ride) {
                return !isOpenPickupProposalRide(ride);
            }
        );
    }

    function contractBadgeHtml(ride) {
        if (!isContractRide(ride)) {
            return '';
        }
        return '<span class="contract-ride-badge">Contract</span>';
    }

    function taxiBadgeHtml(ride) {
        if (!ride || isContractRide(ride)) {
            return '';
        }
        return '<span class="taxi-ride-badge">Taxi</span>';
    }

    function rideKindCardClass(ride) {
        return isContractRide(ride) ? ' is-contract-ride' : ' is-taxi-ride';
    }

    function contractOrMarketplaceBadgesHtml(ride) {
        return contractBadgeHtml(ride) + nexaSuiteBadgeHtml(ride);
    }

    function isReturnTripRide(ride) {
        return !!(ride && ride.return_trip);
    }

    function returnTripLeg(ride) {
        return ride && ride.return_leg ? String(ride.return_leg) : null;
    }

    function returnTripBadgeHtml(ride) {
        if (!isReturnTripRide(ride)) {
            return '';
        }
        return '<span class="return-ride-badge">Retour</span>';
    }

    function resolveReturnLegDisplayLabel(ride) {
        const payment = ride && ride.payment ? ride.payment : {};
        if (payment.payment_leg_label) {
            return payment.payment_leg_label;
        }
        if (!isReturnTripRide(ride)) {
            return null;
        }
        const leg = returnTripLeg(ride);
        if (leg === 'outbound') {
            return 'Heenrit';
        }
        if (leg === 'return' || leg === 'waiting') {
            return 'Terugrit';
        }
        return null;
    }

    function isPerLegReturnPaymentRide(ride) {
        if (!isReturnTripRide(ride)) {
            return false;
        }
        const payment = ride && ride.payment ? ride.payment : {};
        if (payment.method && payment.method !== 'driver') {
            return false;
        }
        return payment.leg_amount != null || payment.payment_leg_label != null || payment.amount_due != null;
    }

    function resolvePerLegDisplayAmount(ride) {
        const payment = ride && ride.payment ? ride.payment : {};
        if (payment.amount_due != null) {
            return payment.amount_due;
        }
        if (payment.status === 'paid' && payment.final_price != null) {
            return payment.final_price;
        }
        if (payment.leg_amount != null) {
            return payment.leg_amount;
        }
        return null;
    }

    function resolveReturnTripDisplayTotal(ride) {
        const payment = ride && ride.payment ? ride.payment : {};
        const legs = payment.return_trip_leg_amounts || ride.return_trip_leg_amounts;
        if (legs && legs.total != null) {
            return legs.total;
        }
        return ride.quoted_price;
    }

    function ridePriceDisplayHtml(ride) {
        const total = resolveReturnTripDisplayTotal(ride);
        if (total == null) {
            return '<p class="offer-price">—</p>';
        }
        if (!isPerLegReturnPaymentRide(ride)) {
            return '<p class="offer-price">' + formatEuro(total) + '</p>';
        }

        const legLabel = resolveReturnLegDisplayLabel(ride);
        const legAmount = resolvePerLegDisplayAmount(ride);

        if (legLabel && legAmount != null) {
            let html =
                '<p class="offer-price-leg">' +
                '<span class="offer-customer-label">' +
                escapeHtml(legLabel) +
                ':</span></p>' +
                '<p class="offer-price">' +
                formatEuro(legAmount) +
                '</p>';
            html += '<p class="offer-price-total">Totaal ' + formatEuro(total) + '</p>';
            return html;
        }

        return '<p class="offer-price">' + formatEuro(total) + '</p>';
    }

    function setRidePriceDisplay(container, ride) {
        if (!container) {
            return;
        }
        container.innerHTML = ridePriceDisplayHtml(ride);
    }

    function updateOfferTitle(ride, index, total) {
        const title = $('#offer-title');
        if (!title) {
            return;
        }
        const multiple = total > 1;
        const base = ride && ride.outbound_completed ? 'Retourrit' : 'Nieuwe rit';
        const queue = multiple ? ' (' + (index + 1) + ' van ' + total + ')' : '';
        title.innerHTML = returnTripBadgeHtml(ride) + base + queue;
    }

    function updateOfferReturnMeta(ride) {
        let returnMetaEl = $('#offer-return-meta');
        if (!returnMetaEl) {
            const titleEl = $('#offer-title');
            if (!titleEl || !titleEl.parentNode) {
                return;
            }
            returnMetaEl = document.createElement('div');
            returnMetaEl.id = 'offer-return-meta';
            titleEl.parentNode.insertBefore(returnMetaEl, titleEl.nextSibling);
        }
        returnMetaEl.innerHTML = returnTripMetaHtml(ride);
        returnMetaEl.hidden = !isReturnTripRide(ride);
    }

    function returnTripMetaHtml(ride) {
        if (!isReturnTripRide(ride)) {
            return '';
        }
        var html = '';
        if (ride.outbound_completed) {
            html += '<p class="offer-meta return-outbound-done">Heenrit al uitgevoerd</p>';
        }
        if (ride.return_at) {
            html +=
                '<p class="offer-meta offer-return-at">Retour om: ' +
                escapeHtml(formatPickupAt(ride.return_at)) +
                '</p>';
        }
        return html;
    }

    function activeReturnLegHint(ride) {
        if (!isReturnTripRide(ride) || !isDriverInProgressRide(ride)) {
            return '';
        }
        var leg = returnTripLeg(ride);
        if (leg === 'outbound') {
            return 'Heenrit — laat de klant eerst betalen en rond daarna af bij de bestemming.';
        }
        if (leg === 'waiting') {
            return 'Heenrit afgerond — start de retour of geef deze vrij voor een andere chauffeur.';
        }
        if (leg === 'return') {
            return 'Retourrit — rond af wanneer de klant is teruggebracht.';
        }
        return '';
    }

    function contractRideScheduleHtml(ride) {
        if (!ride || ride.ride_type !== 'contract_group') {
            return (
                '<p class="offer-meta offer-pickup-at">' +
                escapeHtml(formatPickupAt(ride && ride.pickup_at)) +
                '</p>'
            );
        }
        const schedule = ride.schedule || {};
        let html = '';
        if (schedule.destination_arrival_at) {
            html +=
                '<p class="offer-meta offer-pickup-at"><strong>Aankomst bestemming:</strong> ' +
                escapeHtml(formatContractScheduleAt(schedule.destination_arrival_at)) +
                '</p>';
        }
        if (schedule.departure_at || ride.pickup_at) {
            html +=
                '<p class="offer-meta" style="font-size:0.8125rem;margin-top:-0.35rem"><strong>Vertrek:</strong> ' +
                escapeHtml(formatContractScheduleAt(schedule.departure_at || ride.pickup_at)) +
                '</p>';
        }
        if (!html) {
            html =
                '<p class="offer-meta offer-pickup-at">' +
                escapeHtml(formatPickupAt(ride.pickup_at)) +
                '</p>';
        }
        return html;
    }

    function scheduledRidePickupLabel(ride) {
        if (ride && ride.ride_type === 'contract_group' && ride.schedule && ride.schedule.destination_arrival_at) {
            return 'Aankomst ' + formatContractScheduleAt(ride.schedule.destination_arrival_at);
        }
        return formatPickupAt(ride && ride.pickup_at);
    }

    function scheduledRideTitle(ride) {
        const rideId = String(ride.id || '');
        if (ride.ride_type === 'contract_group' && ride.stops && ride.stops.pickups_total) {
            return 'Groepsrit #' + rideId + ' (' + ride.stops.pickups_total + ' stops)';
        }
        if (ride.ride_type === 'contract_individual') {
            const name = ride.customer_name ? String(ride.customer_name).trim() : '';
            return name ? 'Individuele rit: ' + name : 'Individuele contractrit #' + rideId;
        }
        if (isContractRide(ride)) {
            return 'Contractrit #' + rideId;
        }
        return 'Geplande rit #' + rideId;
    }

    function formatStopTime(iso) {
        if (!iso) {
            return '—';
        }
        const d = new Date(iso);
        if (isNaN(d.getTime())) {
            return '—';
        }
        return d.toLocaleTimeString('nl-NL', {
            timeZone: CONTRACT_TZ,
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function stopStatusLabel(status) {
        const labels = {
            planned: 'Gepland',
            arrived: 'Aangekomen',
            picked_up: 'Opgehaald',
            skipped: 'Afwezig',
            completed: 'Afgerond',
        };
        return labels[status] || status || '—';
    }

    function markStopArrivedAnimation(stopId, previousStatus) {
        if (previousStatus === 'planned') {
            stopArrivedAnimationIds[String(stopId)] = true;
        }
    }

    function clearStopArrivedAnimation(stopId) {
        delete stopArrivedAnimationIds[String(stopId)];
    }

    function shouldAnimateStopArrived(stop) {
        return !!(
            stop &&
            stop.status === 'arrived' &&
            stopArrivedAnimationIds[String(stop.id)]
        );
    }

    function stopStatusHtml(stop) {
        const status = stop.status || 'planned';
        const label = stopStatusLabel(status);
        const stopId = escapeHtml(String(stop.id));
        const statusClass = 'contract-stop-status contract-stop-status--' + escapeHtml(status);

        if (shouldAnimateStopArrived(stop)) {
            return (
                '<span class="' +
                statusClass +
                ' contract-stop-status--arriving" data-stop-id="' +
                stopId +
                '">' +
                '<span class="contract-stop-status-phase contract-stop-status-phase--from" aria-hidden="true">Gepland</span>' +
                '<span class="contract-stop-status-phase contract-stop-status-phase--to">' +
                escapeHtml(label) +
                '</span>' +
                '</span>'
            );
        }

        return (
            '<span class="' + statusClass + '" data-stop-id="' + stopId + '">' + escapeHtml(label) + '</span>'
        );
    }

    function bindStopStatusAnimations(container) {
        if (!container) {
            return;
        }
        container.querySelectorAll('.contract-stop-status--arriving').forEach(function (el) {
            const stopId = el.getAttribute('data-stop-id');
            let finished = false;
            const finish = function () {
                if (finished || !el.isConnected) {
                    return;
                }
                finished = true;
                el.classList.remove('contract-stop-status--arriving');
                el.textContent = stopStatusLabel('arrived');
                clearStopArrivedAnimation(stopId);
            };
            const toPhase = el.querySelector('.contract-stop-status-phase--to');
            if (toPhase) {
                toPhase.addEventListener('animationend', function handler(ev) {
                    if (ev.animationName !== 'contract-stop-to-in') {
                        return;
                    }
                    toPhase.removeEventListener('animationend', handler);
                    finish();
                });
            }
            window.setTimeout(finish, 850);
        });
    }

    function stopHasCoords(stop) {
        return (
            stop &&
            Number.isFinite(Number(stop.lat)) &&
            Number.isFinite(Number(stop.lng))
        );
    }

    function shouldUseStopGeofence(ride) {
        return !!(
            ride &&
            ride.ride_type === 'contract_group' &&
            isDriverInProgressRide(ride) &&
            isContractRide(ride)
        );
    }

    function contractStopGeofenceActive() {
        if (!shouldUseStopGeofence(currentActiveRide)) {
            return false;
        }
        if (stopGeofenceAvailable === false) {
            return false;
        }
        return !!(navigator.geolocation && typeof navigator.geolocation.watchPosition === 'function');
    }

    function haversineMeters(lat1, lng1, lat2, lng2) {
        const toRad = function (deg) {
            return (deg * Math.PI) / 180;
        };
        const earthRadiusM = 6371000;
        const dLat = toRad(lat2 - lat1);
        const dLng = toRad(lng2 - lng1);
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) *
                Math.cos(toRad(lat2)) *
                Math.sin(dLng / 2) *
                Math.sin(dLng / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return earthRadiusM * c;
    }

    function nextPickupStopForGeofence(stops) {
        if (!Array.isArray(stops)) {
            return null;
        }
        const sorted = stops.slice().sort(function (a, b) {
            return (a.sequence || 0) - (b.sequence || 0);
        });
        for (let i = 0; i < sorted.length; i++) {
            const stop = sorted[i];
            if (stop.stop_type !== 'pickup' || stop.status !== 'planned') {
                continue;
            }
            if (!stopHasCoords(stop)) {
                continue;
            }
            return stop;
        }
        return null;
    }

    function isNextPickupStop(stop, stops) {
        const next = nextPickupStopForGeofence(stops);
        return !!(next && String(next.id) === String(stop.id));
    }

    function stopStopGeofenceWatch() {
        if (stopGeofenceWatchId != null && navigator.geolocation) {
            navigator.geolocation.clearWatch(stopGeofenceWatchId);
        }
        stopGeofenceWatchId = null;
    }

    function resetStopGeofenceState() {
        stopStopGeofenceWatch();
        stopGeofenceAutoArrivePending = {};
        stopGeofenceAvailable = null;
        stopArrivedAnimationIds = {};
    }

    function applyLocalStopStatus(stopId, status) {
        const idx = activeRideStops.findIndex(function (s) {
            return String(s.id) === String(stopId);
        });
        if (idx >= 0) {
            const previousStatus = activeRideStops[idx].status;
            if (status === 'arrived') {
                markStopArrivedAnimation(stopId, previousStatus);
            }
            activeRideStops[idx] = Object.assign({}, activeRideStops[idx], { status: status });
        }
    }

    function handleStopGeofencePosition(position) {
        if (!shouldUseStopGeofence(currentActiveRide)) {
            return;
        }
        stopGeofenceAvailable = true;
        const nextStop = nextPickupStopForGeofence(activeRideStops);
        if (!nextStop) {
            return;
        }
        const driverLat = position.coords.latitude;
        const driverLng = position.coords.longitude;
        const distanceM = haversineMeters(
            driverLat,
            driverLng,
            Number(nextStop.lat),
            Number(nextStop.lng)
        );
        if (distanceM > STOP_ARRIVE_RADIUS_M) {
            return;
        }
        const pendingKey = String(nextStop.id);
        if (stopGeofenceAutoArrivePending[pendingKey]) {
            return;
        }
        stopGeofenceAutoArrivePending[pendingKey] = true;
        applyLocalStopStatus(nextStop.id, 'arrived');
        renderActiveRide(currentActiveRide);
        vibrate(30);
        handleStopAction(currentActiveRide.id, nextStop.id, 'arrive', { silent: true })
            .catch(function () {
                clearStopArrivedAnimation(nextStop.id);
                applyLocalStopStatus(nextStop.id, 'planned');
                renderActiveRide(currentActiveRide);
            })
            .finally(function () {
                delete stopGeofenceAutoArrivePending[pendingKey];
            });
    }

    function handleStopGeofenceError(error) {
        if (error && error.code === 1) {
            stopGeofenceAvailable = false;
            if (currentActiveRide) {
                renderActiveRide(currentActiveRide);
            }
        }
    }

    function startStopGeofenceWatch() {
        stopStopGeofenceWatch();
        if (!contractStopGeofenceActive()) {
            return;
        }
        stopGeofenceWatchId = navigator.geolocation.watchPosition(
            handleStopGeofencePosition,
            handleStopGeofenceError,
            {
                enableHighAccuracy: true,
                maximumAge: 15000,
                timeout: 20000,
            }
        );
    }

    function syncStopGeofenceWatch(ride) {
        if (!shouldUseStopGeofence(ride)) {
            resetStopGeofenceState();
            return;
        }
        startStopGeofenceWatch();
    }

    function renderRideStopsHtml(stops, progress) {
        if (!Array.isArray(stops) || !stops.length) {
            return '';
        }
        const progressText =
            progress && progress.pickups_total
                ? '<p class="offer-meta contract-stops-progress">' +
                  progress.pickups_done +
                  ' / ' +
                  progress.pickups_total +
                  ' ophaalstops afgehandeld</p>'
                : '';
        const items = stops
            .map(function (stop) {
                const isPickup = stop.stop_type === 'pickup';
                const isDone =
                    stop.status === 'picked_up' ||
                    stop.status === 'skipped' ||
                    stop.status === 'completed';
                const canAct = !isDone && isDriverInProgressRide(currentActiveRide);
                const rideId = escapeHtml(String(currentActiveRide ? currentActiveRide.id : ''));
                const stopId = escapeHtml(String(stop.id));
                const geofenceActive = contractStopGeofenceActive();
                const hideArriveForGeofence =
                    geofenceActive &&
                    isPickup &&
                    stop.status === 'planned' &&
                    stopHasCoords(stop);
                const showGeofenceHint =
                    hideArriveForGeofence && isNextPickupStop(stop, stops);
                let actions = '';
                if (canAct && isPickup) {
                    const showArrive = stop.status === 'planned' && !hideArriveForGeofence;
                    const showPickupSkip =
                        stop.status === 'planned' || stop.status === 'arrived';
                    if (showArrive || showPickupSkip) {
                        let hint = '';
                        if (showGeofenceHint) {
                            hint =
                                '<p class="contract-stop-auto-hint">Aangekomen wordt automatisch gemeld bij aankomst (GPS)</p>';
                        }
                        let buttons = '';
                        if (showArrive) {
                            buttons +=
                                '<button type="button" class="btn btn-sm btn-stop-arrive" data-ride-id="' +
                                rideId +
                                '" data-stop-id="' +
                                stopId +
                                '">Aangekomen</button>';
                        }
                        if (showPickupSkip) {
                            buttons +=
                                '<button type="button" class="btn btn-sm btn-primary btn-stop-pickup" data-ride-id="' +
                                rideId +
                                '" data-stop-id="' +
                                stopId +
                                '">Opgehaald</button>' +
                                '<button type="button" class="btn btn-sm btn-stop-skip" data-ride-id="' +
                                rideId +
                                '" data-stop-id="' +
                                stopId +
                                '">Afwezig</button>';
                        }
                        actions =
                            hint + '<div class="contract-stop-actions">' + buttons + '</div>';
                    }
                } else if (canAct && stop.stop_type === 'destination') {
                    actions =
                        '<div class="contract-stop-actions">' +
                        '<button type="button" class="btn btn-sm btn-primary btn-stop-pickup" data-ride-id="' +
                        escapeHtml(String(currentActiveRide ? currentActiveRide.id : '')) +
                        '" data-stop-id="' +
                        escapeHtml(String(stop.id)) +
                        '">Aangekomen</button>' +
                        '</div>';
                }
                return (
                    '<div class="contract-stop-item' +
                    (isDone ? ' is-done' : '') +
                    '">' +
                    '<div class="contract-stop-head">' +
                    '<span class="contract-stop-seq">' +
                    stop.sequence +
                    '</span>' +
                    '<div class="contract-stop-main">' +
                    '<strong>' +
                    escapeHtml(stop.passenger_name || (isPickup ? 'Ophalen' : 'Bestemming')) +
                    '</strong>' +
                    '<span class="contract-stop-time">' +
                    escapeHtml(formatStopTime(stop.planned_at)) +
                    '</span>' +
                    '</div>' +
                    stopStatusHtml(stop) +
                    '</div>' +
                    addressLinkHtml(stop.address, isPickup ? '📍' : '🏁') +
                    actions +
                    '</div>'
                );
            })
            .join('');
        return (
            '<div class="contract-stops-panel">' +
            '<p class="offer-title">Stoplijst</p>' +
            progressText +
            items +
            '</div>'
        );
    }

    async function fetchRideStops(rideId) {
        const res = await api('/dispatch/rides/' + rideId + '/stops');
        activeRideStops = (res.data && res.data.stops) || [];
        activeRideStopsProgress = (res.data && res.data.progress) || null;
        if (currentActiveRide && String(currentActiveRide.id) === String(rideId) && activeRideStopsProgress) {
            currentActiveRide.stops = {
                total: activeRideStopsProgress.stops_total,
                pickups_total: activeRideStopsProgress.pickups_total,
                pickups_done: activeRideStopsProgress.pickups_done,
                all_pickups_done: activeRideStopsProgress.all_pickups_done,
            };
        }
        return activeRideStops;
    }

    async function handleStopAction(rideId, stopId, action, options) {
        const opts = options || {};
        const path =
            '/dispatch/rides/' +
            rideId +
            '/stops/' +
            stopId +
            '/' +
            action;
        const res = await api(path, { method: 'POST' });
        if (res.data && res.data.ride_completed) {
            resetStopGeofenceState();
            vibrate(100);
            showNewRideAlertAfterComplete = true;
            renderActiveRide(null);
            await refreshInbox();
            updateEmptyState();
            return res;
        }
        if (res.data && res.data.stop) {
            const idx = activeRideStops.findIndex(function (s) {
                return String(s.id) === String(stopId);
            });
            if (idx >= 0) {
                const previousStatus = activeRideStops[idx].status;
                if (res.data.stop.status === 'arrived') {
                    markStopArrivedAnimation(stopId, previousStatus);
                }
                activeRideStops[idx] = res.data.stop;
            }
        }
        if (res.data && res.data.progress) {
            activeRideStopsProgress = res.data.progress;
            if (currentActiveRide) {
                currentActiveRide.stops = {
                    total: res.data.progress.stops_total,
                    pickups_total: res.data.progress.pickups_total,
                    pickups_done: res.data.progress.pickups_done,
                    all_pickups_done: res.data.progress.all_pickups_done,
                };
            }
        }
        renderActiveRide(currentActiveRide);
        syncCompleteRideButton(currentActiveRide);
        syncStopGeofenceWatch(currentActiveRide);
        if (!opts.silent) {
            vibrate(30);
        }
        return res;
    }

    function renderScheduledRides(rides) {
        const strip = $('#scheduled-rides-strip');
        const list = $('#scheduled-rides-list');
        if (Array.isArray(rides)) {
            scheduledRides = prepareScheduledRidesForInbox(rides);
        }
        if (!strip || !list) {
            syncTripsEmptyState();
            return;
        }
        const overdueOnly = (overdueScheduledRides || []).filter(function (ride) {
            if (!ride || ride.id == null) {
                return false;
            }
            if (isOpenPickupProposalRide(ride)) {
                return false;
            }
            return !scheduledRides.some(function (item) {
                return String(item.id) === String(ride.id);
            });
        });
        const tripsRides = sortRidesEarliestPickupFirst(
            filterRidesByKind(scheduledRides.concat(overdueOnly))
        );
        const hideForActiveDetail =
            isDriverInProgressRide(currentActiveRide) && !activeRideInboxCollapsed;
        if (!tripsRides.length || hideForActiveDetail) {
            strip.hidden = true;
            // Lijst niet wissen bij tijdelijke hide (actieve rit open): state blijft voor tab-wissel.
            if (!hideForActiveDetail) {
                list.innerHTML = '';
            }
            syncTripsEmptyState();
            return;
        }
        strip.hidden = false;
        list.innerHTML = tripsRides
            .map(function (ride) {
                const rideId = String(ride.id);
                const isOverdueAccepted = overdueOnly.some(function (item) {
                    return String(item.id) === rideId;
                });
                if (isOverdueAccepted) {
                    return renderOverdueAcceptedRideCard(ride);
                }
                const expanded = !!scheduledRideExpanded[rideId];
                return (
                    '<div class="card offer-card scheduled-ride-card' +
                    (expanded ? ' is-expanded' : '') +
                    rideKindCardClass(ride) +
                    '" data-ride-id="' +
                    escapeHtml(rideId) +
                    '">' +
                    '<button type="button" class="scheduled-ride-toggle" aria-expanded="' +
                    (expanded ? 'true' : 'false') +
                    '" aria-controls="scheduled-ride-body-' +
                    escapeHtml(rideId) +
                    '" data-ride-id="' +
                    escapeHtml(rideId) +
                    '">' +
                    '<span class="scheduled-ride-toggle-text">' +
                    taxiBadgeHtml(ride) +
                    contractBadgeHtml(ride) +
                    nexaSuiteBadgeHtml(ride) +
                    '<span class="offer-title">' +
                    escapeHtml(scheduledRideTitle(ride)) +
                    '</span>' +
                    '<span class="offer-meta scheduled-pickup-at">' +
                    escapeHtml(scheduledRidePickupLabel(ride)) +
                    '</span>' +
                    returnTripBadgeHtml(ride) +
                    '</span>' +
                    '<span class="scheduled-ride-chevron" aria-hidden="true">▼</span>' +
                    '</button>' +
                    '<div class="scheduled-ride-body" id="scheduled-ride-body-' +
                    escapeHtml(rideId) +
                    '"' +
                    (expanded ? '' : ' hidden') +
                    '>' +
                    pickupProposalBannerHtml(ride) +
                    rideDetailBodyHtml(ride, { hidePrice: isContractRide(ride) }) +
                    (isReturnTripRide(ride) ? returnTripMetaHtml(ride) : '') +
                    '</div>' +
                    scheduledRideActionsHtml(ride, rideId) +
                    '</div>'
                );
            })
            .join('');
        syncTripsEmptyState();
    }

    function toggleScheduledRideCard(rideId) {
        const key = String(rideId || '');
        if (!key) {
            return;
        }
        scheduledRideExpanded[key] = !scheduledRideExpanded[key];
        if (key.indexOf('released-') === 0 || inboxView === 'overdue') {
            renderOverdueView();
            return;
        }
        if (key.indexOf('pending-approval-') === 0) {
            renderPendingApprovalOffers();
            return;
        }
        if (key.indexOf('declined-proposal-') === 0) {
            renderDeclinedOffers(declinedOffers);
            return;
        }
        renderScheduledRides(scheduledRides);
    }

    function renderActiveRide(ride) {
        const el = $('#active-ride');
        const completeBtn = $('#btn-complete-ride');
        if (!ride) {
            currentActiveRide = null;
            activeRideStops = [];
            activeRideStopsProgress = null;
            activeRideInboxCollapsed = true;
            viewingActiveRideId = null;
            parkedAssignedRides = [];
            renderAssignedRidesOverview(null, []);
            resetStopGeofenceState();
            syncContractRideStripUi(null);
            const parkedStrip = $('#parked-assigned-rides-strip');
            if (parkedStrip) {
                parkedStrip.hidden = true;
            }
            setActiveRideUiVisible(false);
            if (el) {
                el.innerHTML = '';
            }
            stopActiveRideNavigation();
            if (mainTab === 'navigation') {
                showNavigationTab();
            }
            if (completeBtn) {
                completeBtn.disabled = false;
                delete completeBtn.dataset.rideId;
            }
            setCompleteRideButtonVisible(false);
            syncTripsEmptyState();
            return;
        }
        currentActiveRide = ride;
        if (isDriverInProgressRide(ride)) {
            prefetchGoogleMapsSdk();
        }
        // Op Aanvragen (Verlopen/Afgewezen/Archief) de actieve-rit-UI niet tonen.
        // Op Ritten altijd wel tekenen — ook als je net van Verlopen komt via de jump-knop.
        if (isSecondaryInboxView(inboxView) && mainTab === 'requests') {
            setOfferUiVisible(false);
            setActiveRideUiVisible(false);
            return;
        }
        if (activeRideInboxCollapsed && isDriverInProgressRide(ride)) {
            renderAssignedRidesOverview(ride, parkedAssignedRides);
            setActiveRideUiVisible(false);
            syncContractRideStripUi(ride);
            syncStopGeofenceWatch(ride);
            syncScreenWakeLock();
            updateEmptyState();
            syncTripsEmptyState();
            return;
        }
        const parkedStripHidden = $('#parked-assigned-rides-strip');
        if (parkedStripHidden) {
            parkedStripHidden.hidden = true;
        }
        setOfferUiVisible(false);
        setActiveRideUiVisible(true);
        syncContractRideStripUi(ride);
        showNewRideAlert(false);
        if (el) {
            const acceptedText = String(activeRideAcceptedMessage || 'Rit gestart.')
                .replace(/\.+$/, '')
                .trim() || 'Rit gestart';
            const contractBadge = taxiBadgeHtml(ride) + contractBadgeHtml(ride) + nexaSuiteBadgeHtml(ride);
            const stopsHtml =
                ride.ride_type === 'contract_group'
                    ? renderRideStopsHtml(activeRideStops, activeRideStopsProgress)
                    : '';
            const returnHint = activeReturnLegHint(ride);
            const title =
                isReturnTripRide(ride) && returnTripLeg(ride) !== 'outbound'
                    ? 'Retourrit'
                    : 'Jouw rit';
            const hint =
                returnHint ||
                (ride.ride_type === 'contract_group'
                    ? 'Werk alle ophaalstops af. Bij aankomst op de bestemming rondt de rit automatisch af.'
                    : 'Rond de rit af wanneer de klant is afgezet. Daarna kun je weer nieuwe ritten ontvangen.');
            const navBtn =
                '<button type="button" class="active-ride-nav-btn" id="btn-active-ride-navigate" aria-label="Navigatie">' +
                navigationIconSvg() +
                '</button>';
            el.innerHTML =
                '<div class="offer-card-top">' +
                '<div class="offer-badge-row">' +
                '<span class="offer-badge">Actief</span>' +
                '<span class="offer-badge is-success" role="status">' +
                escapeHtml(acceptedText) +
                '</span>' +
                '</div>' +
                '<div class="offer-card-meta-right">' +
                navBtn +
                (contractBadge || '') +
                returnTripBadgeHtml(ride) +
                '<span class="offer-vehicle-pill">' +
                escapeHtml(offerVehicleLabel(ride)) +
                '</span>' +
                '</div>' +
                '</div>' +
                '<p class="offer-title">' +
                escapeHtml(title) +
                '</p>' +
                returnTripMetaHtml(ride) +
                contractRideScheduleHtml(ride) +
                '<p class="offer-meta active-ride-hint">' +
                escapeHtml(hint) +
                '</p>' +
                rideDetailBodyHtml(ride, {
                    stopsHtml: ride.ride_type === 'contract_group' ? stopsHtml : '',
                    hideCustomer: ride.ride_type === 'contract_group',
                    hidePrice: isContractRide(ride),
                });
            bindStopStatusAnimations(el);
            activeRideAcceptedMessage = null;
            if (ride.ride_type === 'contract_group' && isDriverInProgressRide(ride) && !activeRideStops.length) {
                fetchRideStops(ride.id)
                    .then(function () {
                        renderActiveRide(currentActiveRide);
                        syncCompleteRideButton(currentActiveRide);
                        syncStopGeofenceWatch(currentActiveRide);
                    })
                    .catch(function () {});
            } else {
                syncStopGeofenceWatch(ride);
            }
        }
        if (completeBtn && isDriverInProgressRide(ride)) {
            completeBtn.dataset.rideId = String(ride.id);
        }
        syncRideActionButtons(ride);
        syncScreenWakeLock();
        updateEmptyState();
        syncTripsEmptyState();
        if (isDriverInProgressRide(ride)) {
            const session = readNavSession();
            if (String(session.rideId) === String(ride.id) && session.started && !session.arrived) {
                navigationStops = collectActiveRideNavigationStops(ride);
                startNavigationWatch();
            }
        }
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function mapsSearchUrl(address) {
        const q = address != null ? String(address).trim() : '';
        if (!q) {
            return '';
        }
        return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(q);
    }

    function telHref(phone) {
        const trimmed = phone != null ? String(phone).trim() : '';
        if (!trimmed) {
            return '';
        }
        if (trimmed.charAt(0) === '+') {
            const normalized = '+' + trimmed.slice(1).replace(/\D/g, '');
            return normalized.length > 1 ? 'tel:' + normalized : '';
        }
        const digits = trimmed.replace(/\D/g, '');
        return digits ? 'tel:' + digits : '';
    }

    function customerLinePartsHtml(name, phone) {
        const parts = [];
        const nameStr = name != null ? String(name).trim() : '';
        const phoneStr = phone != null ? String(phone).trim() : '';
        if (nameStr) {
            parts.push(escapeHtml(nameStr));
        }
        if (phoneStr) {
            const href = telHref(phoneStr);
            if (href) {
                parts.push(
                    '<a class="offer-phone" href="' +
                        escapeHtml(href) +
                        '" aria-label="Bel ' +
                        escapeHtml(phoneStr) +
                        '">' +
                        escapeHtml(phoneStr) +
                        '</a>'
                );
            } else {
                parts.push(escapeHtml(phoneStr));
            }
        }
        return parts.join(' · ');
    }

    function customerLineHtml(name, phone) {
        const nameStr = name != null ? String(name).trim() : '';
        const phoneStr = phone != null ? String(phone).trim() : '';
        if (!nameStr && !phoneStr) {
            return '';
        }
        let html = '<div class="offer-customer-block">';
        html +=
            '<p class="offer-customer-row"><span class="offer-customer-label">Naam:</span> ' +
            escapeHtml(nameStr || '—') +
            '</p>';
        if (phoneStr) {
            const digits = phoneStr.replace(/[^\d+]/g, '');
            const href = digits ? 'tel:' + digits : '';
            html +=
                '<p class="offer-customer-row"><span class="offer-customer-label">Telefoon:</span> ' +
                (href
                    ? '<a class="offer-phone" href="' +
                      escapeHtml(href) +
                      '" aria-label="Bel ' +
                      escapeHtml(phoneStr) +
                      '">' +
                      escapeHtml(phoneStr) +
                      '</a>'
                    : escapeHtml(phoneStr)) +
                '</p>';
        } else {
            html +=
                '<p class="offer-customer-row"><span class="offer-customer-label">Telefoon:</span> —</p>';
        }
        html += '</div>';
        return html;
    }

    function setCustomerLine(el, name, phone) {
        if (!el) {
            return;
        }
        const nameStr = name != null ? String(name).trim() : '';
        const phoneStr = phone != null ? String(phone).trim() : '';
        if (!nameStr && !phoneStr) {
            el.innerHTML = '';
            el.hidden = true;
            return;
        }
        el.hidden = false;
        let html = '';
        html +=
            '<p class="offer-customer-row"><span class="offer-customer-label">Naam:</span> ' +
            escapeHtml(nameStr || '—') +
            '</p>';
        if (phoneStr) {
            const digits = phoneStr.replace(/[^\d+]/g, '');
            const href = digits ? 'tel:' + digits : '';
            html +=
                '<p class="offer-customer-row"><span class="offer-customer-label">Telefoon:</span> ' +
                (href
                    ? '<a class="offer-phone" href="' +
                      escapeHtml(href) +
                      '" aria-label="Bel ' +
                      escapeHtml(phoneStr) +
                      '">' +
                      escapeHtml(phoneStr) +
                      '</a>'
                    : escapeHtml(phoneStr)) +
                '</p>';
        } else {
            html +=
                '<p class="offer-customer-row"><span class="offer-customer-label">Telefoon:</span> —</p>';
        }
        el.innerHTML = html;
    }

    function setAddressLink(el, address) {
        if (!el) {
            return;
        }
        const text = address != null ? String(address).trim() : '';
        const parts = splitAddressLines(text);
        el.innerHTML = '';
        const main = document.createElement('span');
        main.className = 'offer-route-main';
        main.textContent = parts.main || '—';
        el.appendChild(main);
        if (parts.sub) {
            const sub = document.createElement('span');
            sub.className = 'offer-route-sub';
            sub.textContent = parts.sub;
            el.appendChild(sub);
        }
        if (text) {
            el.href = mapsSearchUrl(text);
            el.removeAttribute('aria-disabled');
        } else {
            el.removeAttribute('href');
            el.setAttribute('aria-disabled', 'true');
        }
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

    function formatOfferAgo(iso) {
        if (!iso) {
            return '';
        }
        const ms = parseIsoMs(iso);
        if (!Number.isFinite(ms)) {
            return '';
        }
        const mins = Math.max(0, Math.round((Date.now() - ms) / 60000));
        if (mins < 1) {
            return 'Zojuist';
        }
        if (mins === 1) {
            return '1 min. geleden';
        }
        if (mins < 60) {
            return mins + ' min. geleden';
        }
        const hours = Math.round(mins / 60);
        return hours + (hours === 1 ? ' uur geleden' : ' uur geleden');
    }

    function offerBadgeLabel(ride, offer) {
        if (isOfferPickupOverdue(offer || { ride: ride })) {
            return 'Verlopen';
        }
        if (!ride) {
            return 'Nieuw';
        }
        if (ride.is_contract || ride.contract_label) {
            return 'Contract';
        }
        if (isNexaSuiteRide(ride)) {
            return nexaSuiteRideLabel(ride);
        }
        if (ride.return_trip || ride.outbound_completed) {
            return 'Retour';
        }
        if ((ride.passengers || 0) >= 5) {
            return 'Groep';
        }
        return 'Nieuw';
    }

    function offerVehicleLabel(ride) {
        if (!ride) {
            return 'Sedan';
        }
        const pax = parseInt(ride.passengers || 1, 10) || 1;
        if (pax >= 5) {
            return 'Van · ' + pax + ' pers.';
        }
        return 'Sedan · ' + pax + ' pers.';
    }

    function routeAddressLinkInnerHtml(address) {
        const text = address != null ? String(address).trim() : '';
        const parts = splitAddressLines(text);
        let html =
            '<span class="offer-route-main">' + escapeHtml(parts.main || '—') + '</span>';
        if (parts.sub) {
            html += '<span class="offer-route-sub">' + escapeHtml(parts.sub) + '</span>';
        }
        return html;
    }

    function routeStopHtml(label, address, variant) {
        const text = address != null ? String(address).trim() : '';
        const href = text ? mapsSearchUrl(text) : '';
        const linkAttrs = href
            ? ' href="' + escapeHtml(href) + '" target="_blank" rel="noopener noreferrer"'
            : ' href="#" aria-disabled="true"';
        return (
            '<div class="offer-route-stop">' +
            '<div class="offer-route-head">' +
            '<span class="offer-route-dot offer-route-dot--' +
            escapeHtml(variant) +
            '" aria-hidden="true"></span>' +
            '<p class="offer-route-label">' +
            escapeHtml(label) +
            '</p>' +
            '</div>' +
            '<a class="offer-route-link"' +
            linkAttrs +
            '>' +
            routeAddressLinkInnerHtml(address) +
            '</a>' +
            '</div>'
        );
    }

    function routeTimelineHtml(pickupAddress, dropoffAddress) {
        return (
            '<div class="offer-route">' +
            routeStopHtml('Ophalen', pickupAddress, 'pickup') +
            routeStopHtml('Afzetten', dropoffAddress, 'dropoff') +
            '</div>'
        );
    }

    function rideDistanceLabel(ride) {
        if (!ride || ride.distance_km == null) {
            return '';
        }
        return 'Afstand: ' + String(ride.distance_km).replace('.', ',') + ' km';
    }

    function resolveRideDurationMinutes(ride) {
        if (!ride) {
            return null;
        }
        if (ride.duration_seconds != null && Number.isFinite(Number(ride.duration_seconds))) {
            return Math.max(0, Math.round(Number(ride.duration_seconds) / 60));
        }
        if (ride.duration_minutes != null && Number.isFinite(Number(ride.duration_minutes))) {
            return Math.max(0, Math.round(Number(ride.duration_minutes)));
        }
        return null;
    }

    function formatHoursAndMinutes(totalMinutes) {
        const mins = Math.max(0, Math.round(Number(totalMinutes) || 0));
        const hours = Math.floor(mins / 60);
        const rest = mins % 60;
        if (hours <= 0) {
            return rest + ' min';
        }
        if (rest <= 0) {
            return hours + ' u';
        }
        return hours + ' u ' + rest + ' min';
    }

    function rideDurationLabel(ride) {
        const mins = resolveRideDurationMinutes(ride);
        if (mins == null) {
            return '';
        }
        return 'Tijd: ' + formatHoursAndMinutes(mins);
    }

    function rideStatsHtml(ride, options) {
        const opts = options || {};
        const hidePrice = !!opts.hidePrice || isContractRide(ride);
        const dist = rideDistanceLabel(ride);
        const dur = rideDurationLabel(ride);
        return (
            '<div class="offer-stats">' +
            '<div class="offer-price-wrap">' +
            (hidePrice ? '' : ridePriceDisplayHtml(ride)) +
            '</div>' +
            (dist ? '<p class="offer-stats-line">' + escapeHtml(dist) + '</p>' : '') +
            (dur ? '<p class="offer-stats-line">' + escapeHtml(dur) + '</p>' : '') +
            '</div>'
        );
    }

    function rideDetailBodyHtml(ride, options) {
        const opts = options || {};
        if (!ride) {
            return '';
        }
        if (opts.stopsHtml) {
            return opts.stopsHtml;
        }
        const customerHtml = opts.hideCustomer
            ? ''
            : customerLineHtml(ride.customer_name, ride.customer_phone);
        return (
            '<div class="offer-body-grid">' +
            routeTimelineHtml(ride.pickup_address, ride.dropoff_address) +
            '<div class="offer-meta-grid">' +
            (customerHtml || '<div class="offer-customer-block"></div>') +
            rideStatsHtml(ride, opts) +
            '</div>' +
            '</div>'
        );
    }

    function addressLinkHtml(address, icon) {
        const text = address != null ? String(address).trim() : '';
        const label = escapeHtml(text || '—');
        if (!text) {
            return (
                '<p class="offer-address-row">' +
                '<span class="offer-address-icon" aria-hidden="true">' + icon + '</span>' +
                '<span class="offer-address">' + label + '</span>' +
                '</p>'
            );
        }
        const href = mapsSearchUrl(text);
        return (
            '<p class="offer-address-row">' +
            '<span class="offer-address-icon" aria-hidden="true">' + icon + '</span>' +
            '<a class="offer-address" href="' + href + '" target="_blank" rel="noopener noreferrer">' +
            label +
            '</a>' +
            '</p>'
        );
    }

    async function refreshScheduledRidesOnly() {
        if (!token || !accountActive) {
            return;
        }
        try {
            const res = await api('/dispatch/inbox' + selectedVehicleQuery('?'));
            const active = res.data && res.data.active_ride;
            const scheduled = (res.data && res.data.scheduled_rides) || [];
            overdueScheduledRides = (res.data && res.data.overdue_scheduled_rides) || [];
            pendingApprovalOffers = (res.data && res.data.pending_approval_offers) || [];
            parkedAssignedRides = (res.data && res.data.parked_assigned_rides) || [];
            if (active) {
                renderActiveRide(active);
            } else {
                renderActiveRide(null);
            }
            renderScheduledRides(scheduled);
            updateEmptyState();
            syncTripsEmptyState();
        } catch (e) {
            /* ignore — offline preview is best-effort */
        }
    }

    async function refreshInbox() {
        if (!token || !isOnline) {
            return;
        }
        if (Date.now() - lastVehiclesRefreshAt > 15000) {
            refreshDriverVehicles();
        }
        const scrollEl = document.querySelector('#screen-dispatch .dispatch-scroll');
        const savedScroll =
            scrollEl && (isSecondaryInboxView(inboxView)) ? scrollEl.scrollTop : null;
        if (shouldKeepScreenAwake()) {
            syncScreenWakeLock();
        }
        const showLoader = !inboxHasLoaded;
        if (showLoader) {
            inboxLoading = true;
            updateEmptyState();
        }
        let proposalDecision = null;
        try {
            const res = await api('/dispatch/inbox' + selectedVehicleQuery('?'));
            const offers = (res.data && res.data.offers) || [];
            declinedOffers = (res.data && res.data.declined_offers) || [];
            pendingApprovalOffers = (res.data && res.data.pending_approval_offers) || [];
            overdueScheduledRides = (res.data && res.data.overdue_scheduled_rides) || [];
            overdueReleasedOffers = (res.data && res.data.overdue_released_offers) || [];
            archivedOffers = (res.data && res.data.archived_offers) || [];
            if (res.meta) {
                if (res.meta.offer_ttl_seconds) {
                    configuredOfferTtlSeconds = Math.max(15, parseInt(res.meta.offer_ttl_seconds, 10) || 300);
                }
                driverPaymentEnabled = !!res.meta.driver;
                unclaimedRides = res.meta.unclaimed_rides || [];
            }
            const active = res.data && res.data.active_ride;
            const scheduled = (res.data && res.data.scheduled_rides) || [];
            const absenceAlert = res.data && res.data.absence_alert;
            showAbsenceAlert(absenceAlert);
            const pickupAlert = res.data && res.data.pickup_proposal_alert;
            showPickupProposalAlert(pickupAlert);
            proposalDecision = pickupAlert && pickupAlert.decision ? String(pickupAlert.decision) : null;
            if (
                absenceAlert &&
                currentActiveRide &&
                currentActiveRide.ride_type === 'contract_group' &&
                isDriverInProgressRide(currentActiveRide)
            ) {
                try {
                    await fetchRideStops(currentActiveRide.id);
                    renderActiveRide(currentActiveRide);
                    syncCompleteRideButton(currentActiveRide);
                    syncStopGeofenceWatch(currentActiveRide);
                } catch (e) {
                    /* best-effort */
                }
            }
            // Badge bij “Open” = openstaande aanbiedingen + wachtend op klantgoedkeuring
            // (binnen het gekozen rittype-filter).
            const visibleOffers = filterOffersByKind(offers);
            const visiblePendingApproval = filterOffersByKind(pendingApprovalOffers);
            mainInboxRideCount = visibleOffers.length + visiblePendingApproval.length;
            updateDeclinedNavButton();
            updateOverdueNavButton();
            inboxHasLoaded = true;
            inboxLoading = false;
            if (active) {
                parkedAssignedRides = (res.data && res.data.parked_assigned_rides) || [];
                if (activeRideInboxCollapsed && isDriverInProgressRide(active)) {
                    currentActiveRide = active;
                    renderAssignedRidesOverview(active, parkedAssignedRides);
                    setActiveRideUiVisible(false);
                    updateUnclaimedBanner(unclaimedRides);
                    syncWaitingRideIdsFromOffers(offers);
                    renderScheduledRides(scheduled);
                    if (isSecondaryInboxView(inboxView)) {
                        setInboxView(inboxView);
                        syncTripsEmptyState();
                        return;
                    }
                    setInboxView('offers');
                    pendingOffers = offers;
                    syncAllPendingOffersWaitingState();
                    detectNewOffersInInbox(offers);
                    if (visibleOffers.length > 0) {
                        if (currentOffer) {
                            const found = visibleOffers.findIndex(function (o) {
                                return o.id === currentOffer.id;
                            });
                            if (found >= 0) {
                                offerQueueIndex = found;
                            } else if (offerQueueIndex >= visibleOffers.length) {
                                offerQueueIndex = 0;
                            }
                        }
                        const shown = visibleOffers[offerQueueIndex] || visibleOffers[0];
                        if (!currentOffer || currentOffer.id !== shown.id) {
                            renderOffer(shown, offerQueueIndex, visibleOffers.length);
                        } else {
                            mergeOfferFromServer(currentOffer, shown);
                            updateOfferTimerDisplay(currentOffer);
                            updateOfferQueueUi(offerQueueIndex, visibleOffers.length);
                        }
                    } else {
                        clearOfferNotificationState();
                        renderOffer(null);
                    }
                    updateEmptyState();
                    syncTripsEmptyState();
                    return;
                }
                let rideToShow = active;
                if (viewingActiveRideId != null) {
                    const viewId = String(viewingActiveRideId);
                    const parkedMatch = parkedAssignedRides.find(function (r) {
                        return String(r.id) === viewId;
                    });
                    if (parkedMatch) {
                        rideToShow = parkedMatch;
                    } else if (String(active.id) !== viewId) {
                        viewingActiveRideId = null;
                    }
                }
                currentActiveRide = rideToShow;
                renderParkedAssignedRides([]);
                renderScheduledRides(scheduled);
                renderActiveRide(rideToShow);
                currentOffer = null;
                clearOfferTimer();
                setOfferUiVisible(false);
                updateUnclaimedBanner(unclaimedRides);
                syncWaitingRideIdsFromOffers(offers);
                if (isSecondaryInboxView(inboxView)) {
                    setInboxView(inboxView);
                } else {
                    setInboxView('offers');
                }
                updateEmptyState();
                syncTripsEmptyState();
                return;
            }
            updateUnclaimedBanner(unclaimedRides);
            syncWaitingRideIdsFromOffers(offers);
            renderActiveRide(null);
            renderScheduledRides(scheduled);
            if (inboxView === 'declined') {
                setInboxView('declined');
                syncTripsEmptyState();
                return;
            }
            if (inboxView === 'overdue') {
                setInboxView('overdue');
                syncTripsEmptyState();
                return;
            }
            if (inboxView === 'archived') {
                setInboxView('archived');
                syncTripsEmptyState();
                return;
            }
            pendingOffers = offers;
            syncAllPendingOffersWaitingState();
            detectNewOffersInInbox(offers);
            if (showNewRideAlertAfterComplete && offers.length > 0) {
                showNewRideAlertAfterComplete = false;
                showNewRideAlert(true);
            }
            if (visibleOffers.length > 0) {
                if (currentOffer) {
                    const found = visibleOffers.findIndex(function (o) {
                        return o.id === currentOffer.id;
                    });
                    if (found >= 0) {
                        offerQueueIndex = found;
                    } else if (offerQueueIndex >= visibleOffers.length) {
                        offerQueueIndex = 0;
                    }
                }
                const shown = visibleOffers[offerQueueIndex] || visibleOffers[0];
                if (!currentOffer || currentOffer.id !== shown.id) {
                    renderOffer(shown, offerQueueIndex, visibleOffers.length);
                } else {
                    mergeOfferFromServer(currentOffer, shown);
                    updateOfferTimerDisplay(currentOffer);
                    updateOfferQueueUi(offerQueueIndex, visibleOffers.length);
                }
            } else {
                clearOfferNotificationState();
                renderOffer(null);
            }
            renderPendingApprovalOffers();
            updateEmptyState();
        } catch (e) {
            console.warn('inbox', e);
            inboxLoading = false;
            inboxHasLoaded = true;
            const empty = $('#inbox-empty');
            const title = $('#inbox-empty-title');
            const hint = $('#inbox-empty-hint');
            if (empty && title && hint && isOnline && accountActive) {
                empty.hidden = false;
                setInboxEmptyIcon('error');
                title.textContent = 'Kon ritten niet laden';
                hint.textContent = e.message || 'Probeer opnieuw of log opnieuw in.';
            }
        } finally {
            if (inboxLoading) {
                inboxLoading = false;
                updateEmptyState();
            }
            if (proposalDecision === 'accepted') {
                setMainTab('trips');
            } else if (proposalDecision === 'reopened_offer') {
                setMainTab('requests');
                setInboxView('offers');
            } else if (proposalDecision === 'declined') {
                setMainTab('requests');
                setInboxView('declined');
            }
            if (savedScroll !== null && scrollEl) {
                requestAnimationFrame(function () {
                    scrollEl.scrollTop = savedScroll;
                });
            }
        }
    }

    function startPolling() {
        startInboxSync();
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function disconnectPushStream() {
        if (pushSource) {
            pushSource.close();
            pushSource = null;
        }
    }

    function connectPushStream() {
        disconnectPushStream();
        if (!cfg.streamEnabled || !token || !isOnline || !accountActive) {
            return;
        }
        const url = cfg.apiBase + '/dispatch/stream?token=' + encodeURIComponent(token);
        pushSource = new EventSource(url);

        pushSource.addEventListener('inbox-update', function () {
            refreshInbox();
        });

        pushSource.addEventListener('reconnect', function () {
            if (pushSource) {
                pushSource.close();
                pushSource = null;
            }
            if (isOnline && token) {
                setTimeout(connectPushStream, 300);
            }
        });

        pushSource.onerror = function () {
            /* EventSource herverbindt automatisch; fallback-poll blijft actief. */
        };
    }

    function startInboxSync() {
        refreshInbox();
        connectPushStream();
        stopPolling();
        const ms = cfg.pollMs || (cfg.streamEnabled ? 15000 : 2000);
        pollTimer = setInterval(refreshInbox, ms);
    }

    function stopInboxSync() {
        stopPolling();
        disconnectPushStream();
    }

    async function login(email, password) {
        const res = await fetch(cfg.loginUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ email, password }),
        });
        const data = await res.json();
        if (!res.ok) {
            const err = new Error(data.message || 'Inloggen mislukt.');
            err.code = data.error;
            throw err;
        }
        token = data.token;
        persistToken(token, data.expires_at);
        if (data.user && data.user.company_id) {
            persistCompanyId(data.user.company_id);
        }
        if (data.user && typeof data.user.is_online === 'boolean') {
            applyOnlineStateFromServer(data.user.is_online);
        }
        if (data.user && data.user.vehicle_id) {
            persistSelectedVehicle(data.user.vehicle_id);
        }
        if (data.meta && data.meta.poll_interval_ms) {
            cfg.pollMs = data.meta.poll_interval_ms;
        }
        applyEarningsPermissions(data.permissions || {});
        renderProfileUser(data.user || null);
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
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ email }),
        });
        const data = await res.json().catch(function () { return {}; });
        if (!res.ok) {
            throw new Error(data.message || 'Code aanvragen mislukt.');
        }
        return data;
    }

    async function verifyLoginCode(email, code, password) {
        const res = await fetch(cfg.loginCodeVerifyUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ email, code, password }),
        });
        const data = await res.json().catch(function () { return {}; });
        if (!res.ok) {
            throw new Error(data.message || 'Activeren mislukt.');
        }
        token = data.token;
        persistToken(token, data.expires_at);
        if (data.user && data.user.company_id) {
            persistCompanyId(data.user.company_id);
        }
        if (data.user && typeof data.user.is_online === 'boolean') {
            applyOnlineStateFromServer(data.user.is_online);
        }
        if (data.user && data.user.vehicle_id) {
            persistSelectedVehicle(data.user.vehicle_id);
        }
        if (data.meta && data.meta.poll_interval_ms) {
            cfg.pollMs = data.meta.poll_interval_ms;
        }
        applyEarningsPermissions(data.permissions || {});
        renderProfileUser(data.user || null);
        return data;
    }

    function logout(callApi) {
        stopInboxSync();
        stopGpsTracking();
        clearOfferTimer();
        if (callApi && token) {
            fetch(cfg.apiBase + '/logout', {
                method: 'POST',
                headers: headers(),
            }).catch(function () {});
        }
        companyId = null;
        clearPersistedAuth();
        mainTab = 'requests';
        inboxView = 'offers';
        planningView = 'day';
        planningSelectedDate = null;
        planningWeekFrom = null;
        renderProfileUser(null);
        releaseScreenWakeLock();
        showScreen('login');
    }

    function resolveOfferIdFromClick(ev) {
        const btn = ev && ev.target && ev.target.closest
            ? ev.target.closest('[data-offer-id], #btn-accept, .btn-accept-declined, .btn-accept-overdue')
            : null;
        if (btn && btn.dataset && btn.dataset.offerId) {
            const parsed = parseInt(btn.dataset.offerId, 10);
            if (!isNaN(parsed) && parsed > 0) {
                return parsed;
            }
        }
        return currentOffer && currentOffer.id ? currentOffer.id : null;
    }

    function isRidePickupPast(ride) {
        if (!ride) {
            return false;
        }
        if (ride.requires_pickup_adjustment || ride.is_scheduled_overdue) {
            return true;
        }
        if (!ride.pickup_at) {
            return false;
        }
        const date = new Date(ride.pickup_at);
        return !isNaN(date.getTime()) && date.getTime() < Date.now();
    }

    function findOfferById(offerId) {
        const targetId = String(offerId);
        const pools = [currentOffer]
            .concat(pendingOffers || [])
            .concat(declinedOffers || [])
            .concat(overdueReleasedOffers || []);
        for (let i = 0; i < pools.length; i++) {
            const offer = pools[i];
            if (offer && String(offer.id) === targetId) {
                return offer;
            }
        }
        return null;
    }

    function toDatetimeLocalValue(value) {
        if (!value) {
            return '';
        }
        const date = new Date(value);
        if (isNaN(date.getTime())) {
            return '';
        }
        const pad = function (n) {
            return String(n).padStart(2, '0');
        };
        return (
            date.getFullYear() +
            '-' +
            pad(date.getMonth() + 1) +
            '-' +
            pad(date.getDate()) +
            'T' +
            pad(date.getHours()) +
            ':' +
            pad(date.getMinutes())
        );
    }

    function parseLocalDatetimeLocalMs(value) {
        if (!value || typeof value !== 'string') {
            return NaN;
        }
        const match = value.trim().match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2}))?$/);
        if (!match) {
            return NaN;
        }
        return new Date(
            Number(match[1]),
            Number(match[2]) - 1,
            Number(match[3]),
            Number(match[4]),
            Number(match[5]),
            match[6] ? Number(match[6]) : 0,
            0
        ).getTime();
    }

    function fromDatetimeLocalValue(value) {
        const ms = parseLocalDatetimeLocalMs(value);
        if (isNaN(ms)) {
            return null;
        }
        return new Date(ms).toISOString();
    }

    function isLocalDatetimeInFuture(value) {
        const ms = parseLocalDatetimeLocalMs(value);
        return !isNaN(ms) && ms > Date.now();
    }

    function nowDatetimeLocalMin() {
        const date = new Date();
        date.setSeconds(0, 0);
        return toDatetimeLocalValue(date.toISOString());
    }

    function defaultFuturePickupLocalValue() {
        const date = new Date(Date.now() + 30 * 60 * 1000);
        date.setSeconds(0, 0);
        return toDatetimeLocalValue(date.toISOString());
    }

    function readPickupAdjustInputIso(inputEl) {
        syncPickupAdjustInputFromParts();
        if (!inputEl) {
            inputEl = $('#pickup-adjust-input');
        }
        if (!inputEl) {
            return null;
        }
        const raw = String(inputEl.value || '').trim();
        if (!raw) {
            return null;
        }
        return fromDatetimeLocalValue(raw);
    }

    function syncPickupAdjustInputFromParts() {
        const dateEl = $('#pickup-adjust-date');
        const timeEl = $('#pickup-adjust-time');
        const hidden = $('#pickup-adjust-input');
        if (!hidden) {
            return '';
        }
        const dateValue = dateEl ? String(dateEl.value || '').trim() : '';
        const timeValue = timeEl ? String(timeEl.value || '').trim() : '';
        if (dateValue && timeValue) {
            hidden.value = dateValue + 'T' + timeValue;
        } else {
            hidden.value = '';
        }
        return hidden.value;
    }

    function setPickupAdjustPartsFromLocal(localValue) {
        const dateEl = $('#pickup-adjust-date');
        const timeEl = $('#pickup-adjust-time');
        const hidden = $('#pickup-adjust-input');
        if (!localValue) {
            if (dateEl) {
                dateEl.value = '';
            }
            if (timeEl) {
                timeEl.value = '';
            }
            if (hidden) {
                hidden.value = '';
            }
            applyPickupAdjustMinConstraints();
            return;
        }
        const parts = String(localValue).split('T');
        const datePart = parts[0] || '';
        const timePart = (parts[1] || '').slice(0, 5);
        if (dateEl) {
            dateEl.value = datePart;
        }
        if (timeEl) {
            timeEl.value = timePart;
        }
        if (hidden) {
            hidden.value = datePart && timePart ? datePart + 'T' + timePart : '';
        }
        applyPickupAdjustMinConstraints();
    }

    function applyPickupAdjustMinConstraints() {
        const dateEl = $('#pickup-adjust-date');
        const timeEl = $('#pickup-adjust-time');
        const nowMin = nowDatetimeLocalMin();
        const minParts = nowMin.split('T');
        const minDate = minParts[0] || '';
        const minTime = (minParts[1] || '').slice(0, 5);
        if (dateEl) {
            dateEl.min = minDate;
        }
        if (timeEl) {
            const selectedDate = dateEl ? String(dateEl.value || '').trim() : '';
            timeEl.min = selectedDate && selectedDate === minDate ? minTime : '';
        }
    }

    function confirmPickupAdjustInput(inputEl, onIso) {
        function submit() {
            syncPickupAdjustInputFromParts();
            const raw = inputEl ? String(inputEl.value || '').trim() : '';
            const iso = readPickupAdjustInputIso(inputEl);
            if (!iso || !isLocalDatetimeInFuture(raw)) {
                alert('Kies een ophaalmoment in de toekomst.');
                return;
            }
            onIso(iso);
        }
        if (inputEl && document.activeElement === inputEl) {
            inputEl.blur();
            window.setTimeout(submit, 50);
            return;
        }
        submit();
    }

    let pickupAdjustResolver = null;

    function closePickupAdjustDialog(result, options) {
        const opts = options || {};
        const keepBodyLock = !!opts.keepBodyLock;
        const dialog = $('#pickup-adjust-dialog');
        const askStep = $('#pickup-adjust-ask-step');
        const editStep = $('#pickup-adjust-edit-step');
        const input = $('#pickup-adjust-input');
        const confirmBtn = $('#pickup-adjust-confirm');
        if (dialog) {
            dialog.classList.add('driver-dialog--instant');
            dialog.classList.remove('is-open');
            dialog.hidden = true;
            dialog.setAttribute('aria-hidden', 'true');
            requestAnimationFrame(function () {
                dialog.classList.remove('driver-dialog--instant');
            });
        }
        if (askStep) {
            askStep.hidden = false;
        }
        if (editStep) {
            editStep.hidden = true;
        }
        if (input) {
            input.value = '';
            input.removeAttribute('min');
        }
        const dateInput = $('#pickup-adjust-date');
        const timeInput = $('#pickup-adjust-time');
        if (dateInput) {
            dateInput.value = '';
            dateInput.removeAttribute('min');
        }
        if (timeInput) {
            timeInput.value = '';
            timeInput.removeAttribute('min');
        }
        if (confirmBtn) {
            clearButtonLoading(confirmBtn);
            confirmBtn.disabled = false;
        }
        if (!keepBodyLock) {
            document.body.classList.remove('driver-dialog-open');
        }
        if (pickupAdjustResolver) {
            const resolve = pickupAdjustResolver;
            pickupAdjustResolver = null;
            resolve(result);
        }
    }

    function showPickupAdjustEditStep(pickupAt) {
        const askStep = $('#pickup-adjust-ask-step');
        const editStep = $('#pickup-adjust-edit-step');
        const dateInput = $('#pickup-adjust-date');
        const confirmBtn = $('#pickup-adjust-confirm');
        if (askStep) {
            askStep.hidden = true;
        }
        if (editStep) {
            editStep.hidden = false;
        }
        if (confirmBtn) {
            confirmBtn.textContent = 'Voorstellen';
        }
        const localValue = toDatetimeLocalValue(pickupAt);
        const isPast = localValue && !isLocalDatetimeInFuture(localValue);
        setPickupAdjustPartsFromLocal(!localValue || isPast ? defaultFuturePickupLocalValue() : localValue);
        if (dateInput) {
            dateInput.focus();
        }
    }

    function promptPickupAdjustment(pickupAt, options) {
        const opts = options || {};
        const requireNewTime = !!opts.requireNewTime;
        const dialog = $('#pickup-adjust-dialog');
        if (!dialog) {
            const raw = window.prompt(
                'Nieuw ophaalmoment (YYYY-MM-DDTHH:MM):',
                defaultFuturePickupLocalValue()
            );
            if (raw === null) {
                return Promise.resolve(undefined);
            }
            const iso = fromDatetimeLocalValue(raw);
            if (!iso || !isLocalDatetimeInFuture(raw)) {
                alert('Kies een geldig ophaalmoment in de toekomst.');
                return Promise.resolve(undefined);
            }
            return Promise.resolve(iso);
        }
        return new Promise(function (resolve) {
            pickupAdjustResolver = resolve;
            try {
                openPickupAdjustDialog(pickupAt, { requireNewTime: requireNewTime });
            } catch (err) {
                pickupAdjustResolver = null;
                resolve(undefined);
                console.warn('pickupAdjust', err);
            }
        });
    }

    function initPickupAdjustDialog() {
        const dialog = $('#pickup-adjust-dialog');
        if (!dialog) {
            return;
        }
        const keepBtn = $('#pickup-adjust-keep');
        const changeBtn = $('#pickup-adjust-change');
        const cancelAskBtn = $('#pickup-adjust-cancel-ask');
        const backBtn = $('#pickup-adjust-back');
        const confirmBtn = $('#pickup-adjust-confirm');
        const backdrop = dialog.querySelector('[data-pickup-adjust-dismiss]');

        if (keepBtn) {
            keepBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                closePickupAdjustDialog(false);
            });
        }
        if (changeBtn) {
            changeBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                const currentText = $('#pickup-adjust-current');
                const pickupAt =
                    currentText && currentText.dataset.pickupAt ? currentText.dataset.pickupAt : null;
                showPickupAdjustEditStep(pickupAt);
            });
        }
        if (cancelAskBtn) {
            cancelAskBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                closePickupAdjustDialog(undefined);
            });
        }
        if (backBtn) {
            backBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                const dialogEl = $('#pickup-adjust-dialog');
                if (dialogEl && dialogEl.dataset.requireNewTime === '1') {
                    closePickupAdjustDialog(undefined);
                    return;
                }
                const askStep = $('#pickup-adjust-ask-step');
                const editStep = $('#pickup-adjust-edit-step');
                if (askStep) {
                    askStep.hidden = false;
                }
                if (editStep) {
                    editStep.hidden = true;
                }
            });
        }
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                const inputEl = $('#pickup-adjust-input');
                confirmPickupAdjustInput(inputEl, function (iso) {
                    const confirmButton = $('#pickup-adjust-confirm');
                    setButtonLoading(confirmButton, true, 'Opslaan…');
                    closePickupAdjustDialog(iso, { keepBodyLock: true });
                });
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                closePickupAdjustDialog(undefined);
            });
        }
        const dateInput = $('#pickup-adjust-date');
        const timeInput = $('#pickup-adjust-time');
        if (dateInput) {
            dateInput.addEventListener('change', function () {
                syncPickupAdjustInputFromParts();
                applyPickupAdjustMinConstraints();
            });
        }
        if (timeInput) {
            timeInput.addEventListener('change', syncPickupAdjustInputFromParts);
        }
        document.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Escape' || !dialog.classList.contains('is-open')) {
                return;
            }
            closePickupAdjustDialog(undefined);
        });
    }

    function openPickupAdjustDialog(pickupAt, options) {
        const opts = options || {};
        const requireNewTime = !!opts.requireNewTime;
        const dialog = $('#pickup-adjust-dialog');
        const currentEl = $('#pickup-adjust-current');
        const askStep = $('#pickup-adjust-ask-step');
        const editStep = $('#pickup-adjust-edit-step');
        const backBtn = $('#pickup-adjust-back');
        const confirmBtn = $('#pickup-adjust-confirm');
        if (!dialog) {
            return;
        }
        if (currentEl) {
            const formattedPickup = formatPickupAt(pickupAt);
            currentEl.innerHTML =
                'Huidig ophaalmoment:' +
                '<span class="pickup-adjust-current__value">' +
                escapeHtml(formattedPickup) +
                '</span>';
            if (pickupAt) {
                currentEl.dataset.pickupAt = pickupAt;
            } else {
                delete currentEl.dataset.pickupAt;
            }
        }
        dialog.dataset.requireNewTime = requireNewTime ? '1' : '0';
        if (backBtn) {
            backBtn.textContent = requireNewTime ? 'Annuleren' : 'Terug';
        }
        if (confirmBtn) {
            // Nieuw ophaalmoment → altijd WhatsApp rit_ophaal_voorstel (klant bevestigt).
            confirmBtn.textContent = 'Voorstellen';
        }
        dialog.hidden = false;
        dialog.setAttribute('aria-hidden', 'false');
        dialog.classList.add('is-open');
        document.body.classList.add('driver-dialog-open');
        if (requireNewTime) {
            if (askStep) {
                askStep.hidden = true;
            }
            showPickupAdjustEditStep(pickupAt);
            return;
        }
        if (askStep) {
            askStep.hidden = false;
        }
        if (editStep) {
            editStep.hidden = true;
        }
    }

    async function acceptOffer(ev) {
        if (offerAcceptInFlight) {
            // Herstel vastzittende lock (geen open dialog / loading-knop).
            const hasOpenDialog = !!document.querySelector('.driver-dialog.is-open');
            const hasLoadingBtn = !!document.querySelector(
                '#btn-accept.is-loading, .btn-accept-declined.is-loading, .btn-accept-overdue.is-loading'
            );
            if (hasOpenDialog || hasLoadingBtn) {
                return;
            }
            offerAcceptInFlight = false;
            document.body.classList.remove('driver-accept-in-flight');
        }
        const btn =
            ev && ev.target && ev.target.closest
                ? ev.target.closest('#btn-accept, .btn-accept-declined, .btn-accept-overdue')
                : null;
        const offerId = resolveOfferIdFromClick(ev);
        if (!offerId) {
            alert('Aanbod niet gevonden. Vernieuw de lijst en probeer opnieuw.');
            return;
        }
        const offer = findOfferById(offerId);
        const ride = (offer && offer.ride) || null;
        const activeBtn = btn || $('#btn-accept');
        const fromOverdueView = !!(btn && btn.classList.contains('btn-accept-overdue'));
        const fromDeclinedView = !!(btn && btn.classList.contains('btn-accept-declined'));
        // Verlopen heracceptatie: altijd nieuw ophaalmoment kiezen.
        // Overige aanbiedingen: popup als ophaaltijd al voorbij is.
        const needsPickupPrompt = fromOverdueView || isRidePickupPast(ride);
        const requireNewPickupTime = fromOverdueView || isRidePickupPast(ride);
        let acceptBody = {};
        let acceptErrorMessage = null;

        if (
            document.body.classList.contains('driver-dialog-open') &&
            !document.querySelector('.driver-dialog.is-open')
        ) {
            document.body.classList.remove('driver-dialog-open');
        }

        try {
            if (needsPickupPrompt) {
                const pickupChoice = await promptPickupAdjustment(ridePickupInstant(ride), {
                    requireNewTime: requireNewPickupTime,
                });
                if (pickupChoice === undefined) {
                    return;
                }
                if (pickupChoice !== false) {
                    acceptBody.pickup_at = pickupChoice;
                } else if (requireNewPickupTime) {
                    alert('Kies een nieuw ophaalmoment in de toekomst.');
                    return;
                }
            }

            const conflictPickup =
                acceptBody.pickup_at || ridePickupInstant(ride) || null;
            if (conflictPickup) {
                const conflict = findScheduleConflictForPickup(
                    conflictPickup,
                    rideDurationSeconds(ride),
                    ride && ride.id
                );
                if (conflict) {
                    const when = formatPickupAt(ridePickupInstant(conflict));
                    alert(
                        'Je hebt al een rit gepland rond dit tijdstip' +
                            (when ? ' (' + when + ')' : '') +
                            '. Accepteer geen overlapping — bekijk eerst je geplande ritten.'
                    );
                    return;
                }
            }

            offerAcceptInFlight = true;
            document.body.classList.add('driver-accept-in-flight');
            setOfferActionButtonsDisabled(true, activeBtn);
            if (acceptBody.pickup_at) {
                showDriverNotice('Nieuw ophaalmoment voorgesteld aan de klant via WhatsApp.', {
                    title: 'Voorstel verstuurd',
                });
            }

            await api('/dispatch/offers/' + offerId + '/accept', {
                method: 'POST',
                body: acceptBody,
            });
            showNewRideAlert(false);
            vibrate(100);
            inboxView = 'offers';
            await refreshInbox();
            if (acceptBody.pickup_at) {
                setMainTab('requests');
                setInboxView('offers');
            } else if (fromDeclinedView || fromOverdueView) {
                setMainTab('trips');
                setInboxView('offers');
            }
        } catch (e) {
            acceptErrorMessage = e.message || 'Accepteren mislukt.';
            try {
                await refreshInbox();
                if (fromDeclinedView) {
                    setInboxView('declined');
                }
            } catch (refreshErr) {
                /* ignore */
            }
        } finally {
            offerAcceptInFlight = false;
            document.body.classList.remove('driver-accept-in-flight');
            const noticeOpen = !!document.querySelector('#driver-notice-dialog.is-open');
            if (!noticeOpen) {
                document.body.classList.remove('driver-dialog-open');
            }
            setOfferActionButtonsDisabled(false);
        }
        if (acceptErrorMessage) {
            showDriverNotice(acceptErrorMessage, {
                type: 'error',
                title: 'Accepteren mislukt',
            });
        }
    }

    async function declineOffer(ev) {
        const offerId = resolveOfferIdFromClick(ev);
        if (!offerId) {
            return;
        }
        const activeBtn = ev.target.closest('#btn-decline') || $('#btn-decline');
        setOfferActionButtonsDisabled(true, activeBtn);
        try {
            const reason = await promptDeclineReason();
            if (reason === null) {
                return;
            }
            const body = {};
            if (reason !== '') {
                body.decline_reason = reason;
            }
            await api('/dispatch/offers/' + offerId + '/decline', {
                method: 'POST',
                body: body,
            });
            vibrate(50);
            offerQueueIndex = 0;
            await refreshInbox();
            if (!pendingOffers.length && declinedOffers.length) {
                setInboxView('declined');
            }
        } catch (e) {
            alert(e.message);
            await refreshInbox();
        } finally {
            setOfferActionButtonsDisabled(false);
        }
    }

    async function archiveReleasedOffer(ev) {
        const btn = ev.target.closest('.btn-archive-offer');
        const offerId = btn && btn.dataset.offerId ? parseInt(btn.dataset.offerId, 10) : NaN;
        if (!Number.isFinite(offerId) || offerId <= 0) {
            return;
        }
        setButtonLoading(btn, true, 'Archiveren…');
        try {
            const fromPending = !!(btn && btn.closest('#pending-approval-strip'));
            const fromDeclined = inboxView === 'declined' || !!(btn && btn.closest('#declined-strip'));
            await api('/dispatch/offers/' + offerId + '/archive', { method: 'POST' });
            vibrate(40);
            await refreshInbox();
            setMainTab('requests');
            if (fromPending) {
                setInboxView('offers');
            } else if (fromDeclined) {
                setInboxView(declinedOffers.length ? 'declined' : 'offers');
            } else {
                setInboxView(overdueReleasedOffers.length ? 'overdue' : 'offers');
            }
        } catch (e) {
            alert(e.message || 'Archiveren mislukt.');
            try {
                await refreshInbox();
            } catch (refreshErr) {
                /* ignore */
            }
        } finally {
            clearButtonLoading(btn);
        }
    }

    async function deleteArchivedOffer(ev) {
        const btn = ev.target.closest('.btn-delete-archived-offer');
        const offerId = btn && btn.dataset.offerId ? parseInt(btn.dataset.offerId, 10) : NaN;
        if (!Number.isFinite(offerId) || offerId <= 0) {
            return;
        }
        const confirmed = await confirmArchiveDelete(1);
        if (!confirmed) {
            return;
        }
        setButtonLoading(btn, true, 'Verwijderen…');
        try {
            const res = await api('/dispatch/offers/' + offerId + '/archive', { method: 'DELETE' });
            delete archivedSelectedIds[String(offerId)];
            vibrate(40);
            showDriverNotice((res && res.message) || 'Rit verwijderd.', {
                title: 'Verwijderd',
            });
            await refreshInbox();
            setMainTab('requests');
            setInboxView(archivedOffers.length ? 'archived' : 'overdue');
        } catch (e) {
            alert(e.message || 'Verwijderen mislukt.');
            try {
                await refreshInbox();
            } catch (refreshErr) {
                /* ignore */
            }
        } finally {
            clearButtonLoading(btn);
        }
    }

    async function deleteSelectedArchivedOffers() {
        const selected = getArchivedSelectedIds()
            .map(function (id) {
                return parseInt(id, 10);
            })
            .filter(function (id) {
                return Number.isFinite(id) && id > 0;
            });
        if (!selected.length) {
            return;
        }
        const confirmed = await confirmArchiveDelete(selected.length);
        if (!confirmed) {
            return;
        }
        const deleteBtn = $('#btn-archived-delete-selected');
        setButtonLoading(deleteBtn, true, 'Verwijderen…');
        try {
            const res = await api('/dispatch/archived-offers/delete', {
                method: 'POST',
                body: { offer_ids: selected },
            });
            selected.forEach(function (id) {
                delete archivedSelectedIds[String(id)];
            });
            vibrate(40);
            showDriverNotice((res && res.message) || 'Ritten verwijderd.', {
                title: 'Verwijderd',
            });
            await refreshInbox();
            setMainTab('requests');
            setInboxView(archivedOffers.length ? 'archived' : 'overdue');
        } catch (e) {
            alert(e.message || 'Verwijderen mislukt.');
            try {
                await refreshInbox();
            } catch (refreshErr) {
                /* ignore */
            }
        } finally {
            clearButtonLoading(deleteBtn);
            syncArchivedBulkBar();
        }
    }

    let declineReasonResolve = null;

    function closeDeclineReasonDialog(result) {
        const dialog = $('#decline-reason-dialog');
        const input = $('#decline-reason-input');
        if (dialog) {
            dialog.classList.add('driver-dialog--instant');
            dialog.classList.remove('is-open');
            dialog.hidden = true;
            dialog.setAttribute('aria-hidden', 'true');
            requestAnimationFrame(function () {
                dialog.classList.remove('driver-dialog--instant');
            });
        }
        document.body.classList.remove('driver-dialog-open');
        if (declineReasonResolve) {
            const resolve = declineReasonResolve;
            declineReasonResolve = null;
            resolve(result);
        }
        if (input) {
            input.value = '';
        }
    }

    function promptDeclineReason() {
        const dialog = $('#decline-reason-dialog');
        const input = $('#decline-reason-input');
        if (!dialog) {
            return Promise.resolve('');
        }
        return new Promise(function (resolve) {
            declineReasonResolve = resolve;
            if (input) {
                input.value = '';
            }
            dialog.hidden = false;
            dialog.setAttribute('aria-hidden', 'false');
            dialog.classList.add('is-open');
            document.body.classList.add('driver-dialog-open');
            if (input) {
                input.focus();
            }
        });
    }

    function initDeclineReasonDialog() {
        const dialog = $('#decline-reason-dialog');
        if (!dialog) {
            return;
        }
        const confirmBtn = $('#decline-reason-confirm');
        const cancelBtn = $('#decline-reason-cancel');
        const backdrop = dialog.querySelector('[data-decline-reason-dismiss]');
        const input = $('#decline-reason-input');

        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                const reason = input ? String(input.value || '').trim() : '';
                closeDeclineReasonDialog(reason);
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                closeDeclineReasonDialog(null);
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                closeDeclineReasonDialog(null);
            });
        }
        document.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Escape' || !dialog.classList.contains('is-open')) {
                return;
            }
            closeDeclineReasonDialog(null);
        });
    }

    function findRideInProposalOffers(rideId) {
        const match = (pendingApprovalOffers || [])
            .concat(declinedOffers || [])
            .find(function (offer) {
                return offer && offer.ride && String(offer.ride.id) === String(rideId);
            });
        return match && match.ride ? match.ride : null;
    }

    async function proposePickupForRide(ev) {
        const btn = ev.target.closest('.btn-propose-pickup');
        const rideId = btn && btn.dataset.rideId ? parseInt(btn.dataset.rideId, 10) : NaN;
        if (!Number.isFinite(rideId) || rideId <= 0) {
            return;
        }
        const ride =
            scheduledRides.find(function (item) {
                return String(item.id) === String(rideId);
            }) ||
            overdueScheduledRides.find(function (item) {
                return String(item.id) === String(rideId);
            }) ||
            findRideInProposalOffers(rideId);
        const pickupChoice = await promptPickupAdjustment(ridePickupInstant(ride), {
            requireNewTime: true,
        });
        if (pickupChoice === undefined || pickupChoice === false) {
            return;
        }
        showDriverNotice('Nieuw ophaalmoment voorgesteld aan de klant via WhatsApp.', {
            title: 'Voorstel verstuurd',
        });
        setButtonLoading(btn, true, 'Versturen…');
        try {
            await api('/dispatch/rides/' + rideId + '/propose-pickup', {
                method: 'POST',
                body: { pickup_at: pickupChoice },
            });
            vibrate(80);
            setMainTab('requests');
            await refreshInbox();
            setInboxView('offers');
        } catch (e) {
            showDriverNotice(e.message || 'Voorstel versturen mislukt.', {
                type: 'error',
                title: 'Voorstel mislukt',
            });
            try {
                await refreshInbox();
            } catch (refreshErr) {
                /* ignore */
            }
        } finally {
            clearButtonLoading(btn);
        }
    }

    async function startScheduledRide(ev) {
        const btn = ev.target.closest('.btn-start-ride');
        const rideId = btn && btn.dataset.rideId ? parseInt(btn.dataset.rideId, 10) : NaN;
        if (!Number.isFinite(rideId) || rideId <= 0) {
            return;
        }
        const ride =
            scheduledRides.find(function (item) {
                return String(item.id) === String(rideId);
            }) ||
            overdueScheduledRides.find(function (item) {
                return String(item.id) === String(rideId);
            });
        if (ride && isContractRide(ride) && !isContractRideToday(ride)) {
            alert('Contractritten kun je alleen starten op de dag van de rit.');
            return;
        }
        setButtonLoading(btn, true);
        try {
            const res = await api('/dispatch/rides/' + rideId + '/start', { method: 'POST' });
            activeRideAcceptedMessage = (res && res.message) || 'Rit gestart.';
            activeRideInboxCollapsed = false;
            vibrate(100);
            inboxView = 'offers';
            if (res && res.data && res.data.ride) {
                resetRideTrackBuffer(res.data.ride.id);
                setMainTab('trips');
                renderActiveRide(res.data.ride);
            }
            await refreshInbox();
            syncActiveRideJumpButton();
        } catch (e) {
            alert(e.message);
            await refreshInbox();
        } finally {
            clearButtonLoading(btn);
        }
    }

    async function completeOverdueScheduledRide(ev) {
        const btn = ev.target.closest('.btn-overdue-complete-ride');
        const rideId = btn && btn.dataset.rideId ? parseInt(btn.dataset.rideId, 10) : NaN;
        if (!Number.isFinite(rideId) || rideId <= 0) {
            return;
        }
        const confirmed = await showDriverConfirm(
            'Weet je zeker dat je deze contractrit wilt afronden? Openstaande stops worden als niet uitgevoerd gemarkeerd.',
            {
                title: 'Rit afronden?',
                confirmLabel: 'Afronden',
                danger: true,
            }
        );
        if (!confirmed) {
            return;
        }
        setButtonLoading(btn, true);
        try {
            await api('/dispatch/rides/' + rideId + '/complete', { method: 'POST' });
            delete scheduledRideExpanded[String(rideId)];
            vibrate(100);
            showNewRideAlertAfterComplete = true;
            await refreshInbox();
            if (overdueInboxCount()) {
                setInboxView('overdue');
            } else {
                setInboxView('offers');
            }
            updateEmptyState();
        } catch (e) {
            alert(e.message);
            await refreshInbox();
        } finally {
            clearButtonLoading(btn);
        }
    }

    async function releaseScheduledRide(ev) {
        const btn = ev.target.closest('.btn-release-ride');
        const rideId = btn && btn.dataset.rideId ? parseInt(btn.dataset.rideId, 10) : NaN;
        if (!Number.isFinite(rideId) || rideId <= 0) {
            return;
        }
        const confirmed = await showDriverConfirm(
            'Weet je zeker dat je deze rit wilt vrijgeven? Een andere chauffeur kan hem dan overnemen.',
            {
                title: 'Rit vrijgeven?',
                confirmLabel: 'Vrijgeven',
                danger: true,
            }
        );
        if (!confirmed) {
            return;
        }
        setButtonLoading(btn, true);
        try {
            await api('/dispatch/rides/' + rideId + '/release', { method: 'POST' });
            delete scheduledRideExpanded[String(rideId)];
            vibrate(50);
            await refreshInbox();
            if (overdueInboxCount()) {
                setInboxView('overdue');
            }
            updateEmptyState();
        } catch (e) {
            alert(e.message);
            await refreshInbox();
        } finally {
            clearButtonLoading(btn);
        }
    }

    async function startReturnLeg(ev) {
        const btn = ev.target.closest('#btn-start-return');
        const rideId = btn && btn.dataset.rideId ? parseInt(btn.dataset.rideId, 10) : resolveActiveRideId();
        if (!Number.isFinite(rideId) || rideId <= 0) {
            return;
        }
        setButtonLoading(btn, true);
        try {
            const res = await api('/dispatch/rides/' + rideId + '/start-return', { method: 'POST' });
            activeRideAcceptedMessage = (res && res.message) || 'Retourrit gestart.';
            vibrate(100);
            if (res && res.data && res.data.ride) {
                renderActiveRide(res.data.ride);
            }
            await refreshInbox();
        } catch (e) {
            alert(e.message);
            await refreshInbox();
        } finally {
            clearButtonLoading(btn);
        }
    }

    async function releaseReturnLeg(ev) {
        const btn = ev.target.closest('#btn-release-return');
        const rideId = btn && btn.dataset.rideId ? parseInt(btn.dataset.rideId, 10) : resolveActiveRideId();
        if (!Number.isFinite(rideId) || rideId <= 0) {
            return;
        }
        const confirmed = await showDriverConfirm(
            'Retour vrijgeven? Een andere chauffeur kan de terugweg overnemen. De heenrit blijft geregistreerd.',
            {
                title: 'Retour vrijgeven?',
                confirmLabel: 'Vrijgeven',
                danger: true,
            }
        );
        if (!confirmed) {
            return;
        }
        setButtonLoading(btn, true);
        try {
            await api('/dispatch/rides/' + rideId + '/release-return', { method: 'POST' });
            vibrate(50);
            renderActiveRide(null);
            await refreshInbox();
            updateEmptyState();
        } catch (e) {
            alert(e.message);
            await refreshInbox();
        } finally {
            clearButtonLoading(btn);
        }
    }

    function resolveActiveRideId() {
        if (currentActiveRide && currentActiveRide.id) {
            return currentActiveRide.id;
        }
        const btn = $('#btn-complete-ride');
        if (btn && btn.dataset.rideId) {
            return parseInt(btn.dataset.rideId, 10);
        }
        return null;
    }

    async function completeActiveRide() {
        const rideId = resolveActiveRideId();
        if (!rideId) {
            return;
        }
        const btn = $('#btn-complete-ride');
        if (
            btn &&
            (btn.disabled || btn.classList.contains('is-disabled'))
        ) {
            alert(
                (currentActiveRide && !canCompleteActiveRide(currentActiveRide)
                    ? btn.title
                    : '') || 'Rond eerst de betaling af voordat je de rit afrondt.'
            );
            return;
        }
        if (!isDriverInProgressRide(currentActiveRide)) {
            alert('Start de rit eerst voordat je deze afrondt.');
            return;
        }
        if (currentActiveRide && !canCompleteActiveRide(currentActiveRide)) {
            alert('Rond eerst de betaling af voordat je de rit afrondt.');
            return;
        }
        setButtonLoading(btn, true);
        try {
            const track = snapshotRideTrack(rideId);
            const body = track.length ? { track: track } : {};
            const res = await api('/dispatch/rides/' + rideId + '/complete', { method: 'POST', body: body });
            vibrate(100);
            if (res && res.data && res.data.outbound_completed && res.data.ride) {
                activeRideAcceptedMessage = res.message || 'Heenrit afgerond.';
                renderActiveRide(res.data.ride);
                await refreshInbox();
                updateEmptyState();
                return;
            }
            clearRideTrackBuffer();
            showNewRideAlertAfterComplete = true;
            renderActiveRide(null);
            await refreshInbox();
            updateEmptyState();
        } catch (e) {
            alert(e.message);
            await refreshInbox();
        } finally {
            clearButtonLoading(btn);
        }
    }

    function persistCompanyId(id) {
        const parsed = parseInt(id, 10);
        if (!Number.isFinite(parsed) || parsed <= 0) {
            return;
        }
        companyId = parsed;
        storageSet(localStorage, COMPANY_KEY, String(parsed));
        storageSet(sessionStorage, COMPANY_KEY, String(parsed));
    }

    function applyOnlineStateFromServer(isOnlineOnServer) {
        if (typeof isOnlineOnServer !== 'boolean') {
            isOnline = localStorage.getItem(ONLINE_KEY) === '1';
        } else {
            isOnline = isOnlineOnServer;
        }
        localStorage.setItem(ONLINE_KEY, isOnline ? '1' : '0');
        if (!isOnline) {
            inboxLoading = false;
            inboxHasLoaded = false;
        }
        setOnlineUi();
        updateProfileOnlineStatus();
        if (isOnline && token && accountActive) {
            startGpsTracking();
        } else {
            stopGpsTracking();
        }
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

    function updateProfileOnlineStatus() {
        const statusEl = $('#profile-account-status');
        if (!statusEl || !statusEl.dataset.hasUser) {
            return;
        }
        const parts = [];
        if (statusEl.dataset.accountActive === '0') {
            parts.push('Inactief');
        } else {
            parts.push('Actief');
        }
        parts.push(isOnline ? 'online' : 'offline');
        statusEl.textContent = parts.join(' · ');
        statusEl.classList.remove('is-empty');
    }

    function renderProfileUser(user) {
        profileUser = user || null;
        const nameEl = $('#profile-name');
        const emailEl = $('#profile-email');
        const phoneEl = $('#profile-phone');
        const companyEl = $('#profile-company');
        const statusEl = $('#profile-account-status');
        if (!user) {
            setProfileField(nameEl, '');
            setProfileField(emailEl, '');
            setProfileField(phoneEl, '');
            setProfileField(companyEl, '');
            if (statusEl) {
                delete statusEl.dataset.hasUser;
                delete statusEl.dataset.accountActive;
            }
            setProfileField(statusEl, '');
            return;
        }
        const fullName =
            (user.name && String(user.name).trim()) ||
            [user.first_name, user.last_name].filter(Boolean).join(' ').trim() ||
            '';
        setProfileField(nameEl, fullName);
        setProfileField(emailEl, user.email || '');
        setProfileField(phoneEl, user.phone || '');
        setProfileField(companyEl, user.company_name || '');
        if (statusEl) {
            statusEl.dataset.hasUser = '1';
            statusEl.dataset.accountActive = user.is_account_active === false ? '0' : '1';
        }
        updateProfileOnlineStatus();
        applyAccentFromUser(user);
        applyRideAlertToneFromUser(user);
        syncRideKindFilterUi();
        applyRideKindFilterToViews();
    }

    function applyAccentFromUser(user) {
        if (window.nexaPwaAccent && user && user.pwa_accent) {
            window.nexaPwaAccent.apply(user.pwa_accent);
        }
    }

    function persistAccent(accent) {
        if (profileUser) {
            profileUser.pwa_accent = accent;
        }
        if (!token) {
            return;
        }
        api('/accent', { method: 'PUT', body: { accent: accent } })
            .then(function (res) {
                if (res && res.pwa_accent && profileUser) {
                    profileUser.pwa_accent = res.pwa_accent;
                }
            })
            .catch(function () {});
    }

    function normalizeRideAlertTone(value) {
        const key = String(value == null ? '' : value).toLowerCase();
        return RIDE_ALERT_TONES.indexOf(key) >= 0 ? key : RIDE_ALERT_TONE_DEFAULT;
    }

    function getRideAlertTone() {
        try {
            return normalizeRideAlertTone(localStorage.getItem(RIDE_ALERT_TONE_KEY));
        } catch (e) {
            return RIDE_ALERT_TONE_DEFAULT;
        }
    }

    function syncRideTonePicker(tone) {
        const selected = normalizeRideAlertTone(tone);
        document.querySelectorAll('[data-ride-tone]').forEach(function (btn) {
            const on = btn.getAttribute('data-ride-tone') === selected;
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.classList.toggle('is-selected', on);
        });
    }

    function storeRideAlertTone(tone) {
        const next = normalizeRideAlertTone(tone);
        try {
            localStorage.setItem(RIDE_ALERT_TONE_KEY, next);
        } catch (e) {
            /* private mode */
        }
        if (profileUser) {
            profileUser.ride_alert_tone = next;
        }
        syncRideTonePicker(next);
        return next;
    }

    function applyRideAlertToneFromUser(user) {
        if (user && user.ride_alert_tone) {
            storeRideAlertTone(user.ride_alert_tone);
            return;
        }
        syncRideTonePicker(getRideAlertTone());
    }

    function persistRideAlertTone(tone) {
        const next = storeRideAlertTone(tone);
        if (!token) {
            return;
        }
        api('/ride-alert-tone', { method: 'PUT', body: { tone: next } })
            .then(function (res) {
                if (res && res.ride_alert_tone && profileUser) {
                    profileUser.ride_alert_tone = res.ride_alert_tone;
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

    function bindRideTonePicker() {
        document.querySelectorAll('[data-ride-tone]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const tone = btn.getAttribute('data-ride-tone');
                persistRideAlertTone(tone);
                playNewRideSound(tone);
            });
        });
        syncRideTonePicker(getRideAlertTone());
    }

    async function bootstrap() {
        updateNotificationsHint();
        if (!token) {
            showScreen('login');
            return;
        }
        restoreUiState();
        setPlanningView(planningView, { skipRender: true });
        showScreen('dispatch');
        prefetchGoogleMapsSdk();
        try {
            const me = await api('/me');
            if (me.user && me.user.company_id) {
                persistCompanyId(me.user.company_id);
            }
            applyEarningsPermissions(me.permissions || {});
            renderProfileUser(me.user || null);
            const active = me.user && me.user.is_account_active !== false;
            setAccountInactive(!active);
            applyOnlineStateFromServer(me.user && me.user.is_online);
            if (me.user && me.user.vehicle_id) {
                persistSelectedVehicle(me.user.vehicle_id);
            }
            await refreshDriverVehicles();
            if (accountActive) {
                if (isOnline) {
                    startInboxSync();
                } else {
                    stopInboxSync();
                    await refreshScheduledRidesOnly();
                    updateEmptyState();
                }
            }
        } catch (e) {
            if (e.code === 'driver_not_active') {
                applyOnlineStateFromServer(false);
                updateEmptyState();
                return;
            }
            companyId = null;
            clearPersistedAuth();
            mainTab = 'requests';
            inboxView = 'offers';
            planningView = 'day';
            planningSelectedDate = null;
            planningWeekFrom = null;
            renderProfileUser(null);
            showScreen('login');
        }
        syncScreenWakeLock();
    }

    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async function (ev) {
        ev.preventDefault();
        const err = $('#login-error');
        const btn = $('#login-btn');
        err.hidden = true;
        const firstPanel = $('#first-login-panel');
        if (firstPanel && !firstPanel.hidden) {
            const verifyBtn = document.getElementById('btn-verify-login-code');
            if (verifyBtn && $('#first-login-verify') && !$('#first-login-verify').hidden) {
                verifyBtn.click();
            } else {
                const sendBtn = document.getElementById('btn-send-login-code');
                if (sendBtn) {
                    sendBtn.click();
                }
            }
            return;
        }
        setButtonLoading(btn, true, 'Inloggen…');
        try {
            await login($('#email').value.trim(), $('#password').value);
            unlockAudio();
            showScreen('dispatch');
            prefetchGoogleMapsSdk();
            requestScreenWakeLockFromGesture();
            await setOnline(true);
            syncScreenWakeLock();
            startInboxSync();
        } catch (e) {
            if (e && e.code === 'first_login_required') {
                setFirstLoginMode(true);
            }
            err.textContent = e.message;
            err.hidden = false;
        } finally {
            clearButtonLoading(btn);
        }
        });
    }

    function showLoginError(message) {
        const err = $('#login-error');
        if (!err) {
            return;
        }
        err.textContent = message || '';
        err.hidden = !message;
    }

    const openFirstLoginBtn = document.getElementById('btn-open-first-login');
    if (openFirstLoginBtn) {
        openFirstLoginBtn.addEventListener('click', function () {
            showLoginError('');
            setFirstLoginMode(true);
        });
    }
    const cancelFirstLoginBtn = document.getElementById('btn-cancel-first-login');
    if (cancelFirstLoginBtn) {
        cancelFirstLoginBtn.addEventListener('click', function () {
            showLoginError('');
            setFirstLoginMode(false);
        });
    }
    const sendLoginCodeBtn = document.getElementById('btn-send-login-code');
    if (sendLoginCodeBtn) {
        sendLoginCodeBtn.addEventListener('click', async function () {
            const email = ($('#email') && $('#email').value.trim()) || '';
            showLoginError('');
            if (!email) {
                showLoginError('Vul eerst je e-mailadres in.');
                return;
            }
            setButtonLoading(sendLoginCodeBtn, true, 'Versturen…');
            try {
                await requestLoginCode(email);
                rememberFirstLoginEmail(email);
                setFirstLoginCodeSent(true);
            } catch (e) {
                showLoginError(e.message || 'Code aanvragen mislukt.');
            } finally {
                clearButtonLoading(sendLoginCodeBtn);
            }
        });
    }
    const verifyLoginCodeBtn = document.getElementById('btn-verify-login-code');
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
            setButtonLoading(verifyLoginCodeBtn, true, 'Activeren…');
            try {
                await verifyLoginCode(email, code, password);
                unlockAudio();
                showScreen('dispatch');
                prefetchGoogleMapsSdk();
                requestScreenWakeLockFromGesture();
                await setOnline(true);
                syncScreenWakeLock();
                startInboxSync();
            } catch (e) {
                showLoginError(e.message || 'Activeren mislukt.');
            } finally {
                clearButtonLoading(verifyLoginCodeBtn);
            }
        });
    }

    const onlineToggle = $('#online-toggle');
    if (onlineToggle) {
        onlineToggle.addEventListener('click', async function () {
        if (!accountActive) {
            updateEmptyState();
            return;
        }
        const turningOn = !isOnline;
        if (turningOn) {
            unlockAudio();
            requestScreenWakeLockFromGesture();
        }
        await setOnline(turningOn);
        if (isOnline) {
            syncScreenWakeLock();
            startInboxSync();
        } else {
            releaseScreenWakeLock();
            stopInboxSync();
            renderOffer(null);
            showNewRideAlert(false);
            showNewRideAlertAfterComplete = false;
            await refreshScheduledRidesOnly();
            updateEmptyState();
        }
        });
    }

    const vehicleSelect = $('#driver-vehicle-select');
    if (vehicleSelect) {
        vehicleSelect.addEventListener('change', async function () {
            if (vehicleChoiceLocked) {
                if (selectedVehicleId) {
                    vehicleSelect.value = String(selectedVehicleId);
                }
                return;
            }
            persistSelectedVehicle(vehicleSelect.value);
            // Vorige kenteken-ritten meteen weg; daarna opnieuw laden voor het gekozen voertuig.
            scheduledRides = [];
            overdueScheduledRides = [];
            renderScheduledRides([]);
            planningPayload = null;
            if (isOnline && lastGpsCoords) {
                await sendDriverLocation(lastGpsCoords, true, true);
            } else if (isOnline && token) {
                try {
                    await api('/availability', {
                        method: 'PUT',
                        body: { is_online: true, vehicle_id: selectedVehicleId },
                    });
                } catch (e) {
                    console.warn(e);
                }
            }
            try {
                if (token && isOnline) {
                    await refreshInbox();
                } else if (token) {
                    await refreshScheduledRidesOnly();
                }
            } catch (e) {
                console.warn(e);
            }
            try {
                if (token) {
                    await loadPlanning(true);
                }
            } catch (e) {
                console.warn(e);
            }
            syncTripsEmptyState();
        });
    }

    if (screenDispatch) {
        screenDispatch.addEventListener('change', function (ev) {
            const check = ev.target.closest('.archived-offer-check');
            if (check) {
                setArchivedOfferSelected(check.dataset.offerId, !!check.checked);
                return;
            }
            if (ev.target && ev.target.id === 'archived-select-all') {
                const selectAll = !!ev.target.checked;
                archivedSelectedIds = {};
                (archivedOffers || []).forEach(function (offer) {
                    if (offer && offer.id != null && selectAll) {
                        archivedSelectedIds[String(offer.id)] = true;
                    }
                });
                renderArchivedView();
            }
        });
        screenDispatch.addEventListener('click', function (ev) {
            const tabBtn = ev.target.closest('[data-main-tab]');
            if (tabBtn) {
                ev.preventDefault();
                const tab = tabBtn.getAttribute('data-main-tab');
                if (tab === 'trips') {
                    // Altijd rittenoverzicht, ook tijdens een open actieve rit.
                    showAllRidesInbox();
                    return;
                }
                if (tab === 'requests') {
                    setMainTab('requests');
                    setInboxView('offers');
                    updateUnclaimedBanner(unclaimedRides);
                    return;
                }
                setMainTab(tab);
                return;
            }
            if (ev.target.closest('#btn-start-navigation')) {
                ev.preventDefault();
                startGoogleNavigation();
                return;
            }
            if (ev.target.closest('#btn-active-ride-navigate') || ev.target.closest('.btn-active-ride-navigate')) {
                ev.preventDefault();
                setMainTab('navigation');
                return;
            }
            if (ev.target.closest('#btn-earnings-prev')) {
                ev.preventDefault();
                loadEarnings(shiftIsoDate(earningsDate || todayLocalIsoDate(), -1));
                return;
            }
            if (ev.target.closest('#btn-earnings-next')) {
                ev.preventDefault();
                if (earningsDate && earningsDate < todayLocalIsoDate()) {
                    loadEarnings(shiftIsoDate(earningsDate, 1));
                }
                return;
            }
            if (ev.target.closest('#btn-earnings-today')) {
                ev.preventDefault();
                if (earningsDate !== todayLocalIsoDate()) {
                    loadEarnings(todayLocalIsoDate());
                }
                return;
            }
            const planningViewBtn = ev.target.closest('[data-planning-view]');
            if (planningViewBtn) {
                ev.preventDefault();
                setPlanningView(planningViewBtn.getAttribute('data-planning-view'));
                return;
            }
            const rideKindBtn = ev.target.closest('[data-ride-kind]');
            if (rideKindBtn) {
                ev.preventDefault();
                setRideKindFilter(rideKindBtn.getAttribute('data-ride-kind'));
                return;
            }
            if (ev.target.closest('#planning-week-prev')) {
                ev.preventDefault();
                if (planningView === 'week') {
                    planningWeekFrom = shiftIsoDate(planningWeekFrom || planningMondayIso(), -7);
                    if (planningSelectedDate) {
                        planningSelectedDate = shiftIsoDate(planningSelectedDate, -7);
                    }
                    persistUiState();
                    loadPlanning();
                } else {
                    shiftPlanningDay(-1);
                }
                return;
            }
            if (ev.target.closest('#planning-week-next')) {
                ev.preventDefault();
                if (planningView === 'week') {
                    planningWeekFrom = shiftIsoDate(planningWeekFrom || planningMondayIso(), 7);
                    if (planningSelectedDate) {
                        planningSelectedDate = shiftIsoDate(planningSelectedDate, 7);
                    }
                    persistUiState();
                    loadPlanning();
                } else {
                    shiftPlanningDay(1);
                }
                return;
            }
            if (ev.target.closest('#planning-week-today')) {
                ev.preventDefault();
                planningSelectedDate = todayContractDateKey();
                planningWeekFrom = planningMondayIso(planningSelectedDate);
                persistUiState();
                loadPlanning();
                return;
            }
            const planningRideBtn = ev.target.closest('[data-planning-ride-id]');
            if (planningRideBtn) {
                ev.preventDefault();
                openPlanningRide(planningRideBtn.getAttribute('data-planning-ride-id'));
                return;
            }
            const planningDayBtn = ev.target.closest('[data-planning-date]');
            if (planningDayBtn) {
                ev.preventDefault();
                planningSelectedDate = planningDayBtn.getAttribute('data-planning-date');
                renderPlanning(planningPayload);
                scrollPlanningToSelectedDay();
                return;
            }
            const jump = ev.target.closest('[data-main-tab-jump]');
            if (jump) {
                ev.preventDefault();
                setMainTab(jump.getAttribute('data-main-tab-jump'));
                return;
            }
            if (ev.target.closest('#btn-accept') || ev.target.closest('.btn-accept-declined') || ev.target.closest('.btn-accept-overdue')) {
                ev.preventDefault();
                acceptOffer(ev).catch(function (err) {
                    console.warn('acceptOffer', err);
                    offerAcceptInFlight = false;
                    document.body.classList.remove('driver-accept-in-flight');
                    document.body.classList.remove('driver-dialog-open');
                    setOfferActionButtonsDisabled(false);
                    showDriverNotice((err && err.message) || 'Accepteren mislukt.', {
                        type: 'error',
                        title: 'Accepteren mislukt',
                    });
                });
                return;
            }
            if (ev.target.closest('#btn-decline')) {
                ev.preventDefault();
                declineOffer(ev);
                return;
            }
            if (ev.target.closest('#btn-show-overdue') || ev.target.closest('#btn-empty-show-overdue')) {
                ev.preventDefault();
                setMainTab('requests');
                setInboxView('overdue');
                return;
            }
            if (ev.target.closest('#btn-show-offers')) {
                ev.preventDefault();
                setMainTab('requests');
                setInboxView('offers');
                updateUnclaimedBanner(unclaimedRides);
                return;
            }
            if (ev.target.closest('#btn-show-declined') || ev.target.closest('#btn-empty-show-declined')) {
                ev.preventDefault();
                setMainTab('requests');
                setInboxView('declined');
                return;
            }
            if (
                ev.target.closest('#btn-show-archived') ||
                ev.target.closest('#btn-empty-show-archived') ||
                ev.target.closest('#btn-empty-show-archived-inbox')
            ) {
                ev.preventDefault();
                setMainTab('requests');
                setInboxView('archived');
                return;
            }
            if (ev.target.closest('.btn-archive-offer')) {
                ev.preventDefault();
                archiveReleasedOffer(ev).catch(function (err) {
                    console.warn('archiveOffer', err);
                });
                return;
            }
            if (ev.target.closest('.btn-delete-archived-offer')) {
                ev.preventDefault();
                deleteArchivedOffer(ev).catch(function (err) {
                    console.warn('deleteArchivedOffer', err);
                });
                return;
            }
            if (ev.target.closest('.btn-stop-arrive')) {
                ev.preventDefault();
                const btn = ev.target.closest('.btn-stop-arrive');
                handleStopAction(btn.dataset.rideId, btn.dataset.stopId, 'arrive').catch(function (e) {
                    alert(e.message || 'Actie mislukt.');
                });
                return;
            }
            if (ev.target.closest('.btn-stop-pickup')) {
                ev.preventDefault();
                const btn = ev.target.closest('.btn-stop-pickup');
                handleStopAction(btn.dataset.rideId, btn.dataset.stopId, 'pickup').catch(function (e) {
                    alert(e.message || 'Actie mislukt.');
                });
                return;
            }
            if (ev.target.closest('.btn-stop-skip')) {
                ev.preventDefault();
                const btn = ev.target.closest('.btn-stop-skip');
                handleStopAction(btn.dataset.rideId, btn.dataset.stopId, 'skip').catch(function (e) {
                    alert(e.message || 'Actie mislukt.');
                });
                return;
            }
            if (ev.target.closest('.btn-propose-pickup')) {
                ev.preventDefault();
                proposePickupForRide(ev).catch(function (err) {
                    console.warn('proposePickup', err);
                });
                return;
            }
            if (ev.target.closest('.btn-start-ride')) {
                ev.preventDefault();
                startScheduledRide(ev);
                return;
            }
            if (ev.target.closest('.btn-overdue-complete-ride')) {
                ev.preventDefault();
                completeOverdueScheduledRide(ev);
                return;
            }
            if (ev.target.closest('.btn-release-ride')) {
                ev.preventDefault();
                releaseScheduledRide(ev);
                return;
            }
            if (ev.target.closest('.archived-offer-check')) {
                return;
            }
            if (ev.target.closest('#archived-select-all')) {
                return;
            }
            if (ev.target.closest('#btn-archived-delete-selected')) {
                ev.preventDefault();
                deleteSelectedArchivedOffers().catch(function (err) {
                    console.warn('deleteSelectedArchivedOffers', err);
                });
                return;
            }
            var toggleArchived = ev.target.closest('.archived-ride-toggle');
            if (toggleArchived) {
                ev.preventDefault();
                toggleArchivedRideCard(toggleArchived.dataset.archivedOfferId);
                return;
            }
            var toggleEarnings = ev.target.closest('.earnings-ride-toggle');
            if (toggleEarnings) {
                ev.preventDefault();
                toggleEarningsRideCard(toggleEarnings.dataset.earningsRideId);
                return;
            }
            var toggleScheduled = ev.target.closest('.scheduled-ride-toggle');
            if (toggleScheduled) {
                ev.preventDefault();
                toggleScheduledRideCard(toggleScheduled.dataset.rideId);
                return;
            }
            if (ev.target.closest('#btn-offer-prev')) {
                ev.preventDefault();
                showOfferAtIndex(offerQueueIndex - 1);
                return;
            }
            if (ev.target.closest('#btn-offer-next')) {
                ev.preventDefault();
                showOfferAtIndex(offerQueueIndex + 1);
                return;
            }
            if (ev.target.closest('#btn-active-ride-jump')) {
                ev.preventDefault();
                showActiveRideFullPanel(
                    currentActiveRide && currentActiveRide.id != null ? currentActiveRide.id : null
                );
                return;
            }
            if (ev.target.closest('.btn-open-parked-ride')) {
                ev.preventDefault();
                const parkedBtn = ev.target.closest('.btn-open-parked-ride');
                if (parkedBtn && parkedBtn.dataset.rideId) {
                    showActiveRideFullPanel(parkedBtn.dataset.rideId);
                }
                return;
            }
            if (ev.target.closest('#btn-start-return')) {
                ev.preventDefault();
                startReturnLeg(ev);
                return;
            }
            if (ev.target.closest('#btn-release-return')) {
                ev.preventDefault();
                releaseReturnLeg(ev);
                return;
            }
            if (ev.target.closest('#btn-complete-ride')) {
                ev.preventDefault();
                const completeBtn = $('#btn-complete-ride');
                if (
                    completeBtn &&
                    (completeBtn.disabled || completeBtn.classList.contains('is-disabled'))
                ) {
                    return;
                }
                completeActiveRide();
                return;
            }
            if (ev.target.closest('#btn-pay-ride')) {
                ev.preventDefault();
                if (currentActiveRide && isContractRide(currentActiveRide)) {
                    return;
                }
                const payBtn = $('#btn-pay-ride');
                if (
                    payBtn &&
                    (payBtn.disabled || payBtn.classList.contains('is-paid'))
                ) {
                    return;
                }
                openPayRideFlow();
                return;
            }
            if (ev.target.closest('#btn-cash-paid')) {
                ev.preventDefault();
                const cashBtn = $('#btn-cash-paid');
                if (cashBtn && cashBtn.disabled) {
                    return;
                }
                markRideCashPaid();
                return;
            }
            if (ev.target.closest('#btn-payment-close')) {
                ev.preventDefault();
                if (isPaymentQrVisible()) {
                    hidePaymentQr();
                } else {
                    closePaymentPanel();
                }
                return;
            }
            if (ev.target.closest('#btn-payment-create')) {
                ev.preventDefault();
                createRidePayment();
                return;
            }
            if (ev.target.closest('#btn-send-invoice')) {
                ev.preventDefault();
                const sendInvoiceBtn = $('#btn-send-invoice');
                if (sendInvoiceBtn && !sendInvoiceBtn.disabled && !sendInvoiceBtn.hidden) {
                    openSendInvoiceFlow();
                }
                return;
            }
            if (ev.target.closest('#btn-invoice-close')) {
                ev.preventDefault();
                closeInvoicePanel();
                return;
            }
            if (ev.target.closest('#btn-invoice-send')) {
                ev.preventDefault();
                sendRideInvoice();
            }
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('payment_done') === '1' && token) {
        setTimeout(function () {
            refreshInbox();
        }, 400);
    }

    const btnLogout = $('#btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', function () {
            logout(true);
        });
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            onPageBecameVisible();
            if (isOnline && token) {
                refreshInbox();
                refreshDriverPosition().then(function (coords) {
                    sendDriverLocation(coords, false, true);
                });
            }
            return;
        }
        if (screenWakeLock) {
            screenWakeLock.release().catch(function () {});
            screenWakeLock = null;
        }
        stopWakeLockMaintenance();
        if (shouldKeepGpsAlive()) {
            startNoSleepFallback();
            showOnlineGpsNotification();
            refreshDriverPosition().then(function (coords) {
                sendDriverLocation(coords, false, true);
            });
        } else {
            stopNoSleepFallback();
        }
    });

    window.addEventListener('pageshow', function () {
        onPageBecameVisible();
    });

    window.addEventListener('focus', function () {
        syncScreenWakeLock();
    });

    const btnEnableNotifications = $('#btn-enable-notifications');
    if (btnEnableNotifications) {
        btnEnableNotifications.addEventListener('click', handleEnableNotificationsClick);
    }

    const btnInstallApp = $('#btn-install-app');
    if (btnInstallApp) {
        btnInstallApp.addEventListener('click', handleInstallAppClick);
    }

    const btnDismissInstallHint = $('#btn-dismiss-install-hint');
    if (btnDismissInstallHint) {
        btnDismissInstallHint.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            dismissInstallHint();
        });
    }

    const btnDismissGuideHint = $('#btn-dismiss-guide-hint');
    if (btnDismissGuideHint) {
        btnDismissGuideHint.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            dismissGuideHint();
        });
    }

    const btnDismissNotificationsHint = $('#btn-dismiss-notifications-hint');
    if (btnDismissNotificationsHint) {
        btnDismissNotificationsHint.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            dismissNotificationsHint();
        });
    }

    const absenceAlertBanner = $('#absence-alert-banner');
    if (absenceAlertBanner) {
        absenceAlertBanner.addEventListener('click', function (ev) {
            const rideLink = ev.target.closest('.banner-ride-link');
            if (!rideLink) {
                return;
            }
            ev.preventDefault();
            ev.stopPropagation();
            openRideFromPickupAlert(rideLink.getAttribute('data-open-ride-id'));
        });
    }

    const btnDismissAbsenceAlert = $('#btn-dismiss-absence-alert');
    if (btnDismissAbsenceAlert) {
        btnDismissAbsenceAlert.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            const banner = $('#absence-alert-banner');
            if (banner) {
                banner.hidden = true;
            }
        });
    }

    const btnDismissIosAwakeHint = $('#btn-dismiss-ios-awake-hint');
    if (btnDismissIosAwakeHint) {
        btnDismissIosAwakeHint.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            dismissIosAwakeHint();
        });
    }

    const btnDismissNotificationsFeedback = $('#btn-dismiss-notifications-feedback');
    if (btnDismissNotificationsFeedback) {
        btnDismissNotificationsFeedback.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            showNotificationsFeedback('');
        });
    }

    function onUserKeepAwakeGesture() {
        if (!token || !accountActive || !isOnline) {
            return;
        }
        requestScreenWakeLockFromGesture();
    }

    ['touchstart', 'touchend', 'pointerdown'].forEach(function (eventName) {
        document.addEventListener(eventName, onUserKeepAwakeGesture, { passive: true });
    });

    document.addEventListener('click', function () {
        unlockAudio();
    }, { once: true, capture: true });

    ensureServiceWorkerReady().then(function () {
        updateNotificationsHint();
        updateInstallHint();
        updateGuideHint();
    });

    initCashConfirmDialog();
    initArchiveDeleteConfirmDialog();
    initDeclineReasonDialog();
    initPickupAdjustDialog();
    initDriverNoticeDialog();
    initDriverConfirmDialog();
    syncRideKindFilterUi();

    bindAccentPicker();
    bindRideTonePicker();
    bindNavigationMapTheme();
    updateGuideHint();
    bootstrap();
})();
