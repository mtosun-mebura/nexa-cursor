(function () {
    'use strict';

    const cfg = window.NEXA_TAXI_DRIVER || {};
    const STORAGE_KEY = 'nexa_taxi_driver_token';
    const COMPANY_KEY = 'nexa_taxi_driver_company_id';
    const ONLINE_KEY = 'nexa_taxi_driver_online';
    const NOTIFICATIONS_HINT_DISMISSED_KEY = 'nexa_taxi_dismiss_notifications_hint';
    const IOS_AWAKE_HINT_DISMISSED_KEY = 'nexa_taxi_dismiss_ios_awake_hint';

    let token = sessionStorage.getItem(STORAGE_KEY) || '';
    let pollTimer = null;
    let pushSource = null;
    let timerInterval = null;
    let currentOffer = null;
    let currentActiveRide = null;
    let pendingOffers = [];
    let offerQueueIndex = 0;
    let isOnline = false;
    let companyId = (function () {
        const raw = sessionStorage.getItem(COMPANY_KEY);
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
    let paymentPollTimer = null;
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
        const offlineHint = $('#offline-hint');
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
            localStorage.setItem(ONLINE_KEY, '0');
            setOnlineUi();
            stopInboxSync();
            renderOffer(null);
            if (offlineHint) {
                offlineHint.hidden = true;
            }
            updateEmptyState();
            syncScreenWakeLock();
        }
    }

    function updateEmptyState() {
        const empty = $('#inbox-empty');
        const title = $('#inbox-empty-title');
        const hint = $('#inbox-empty-hint');
        const offlineHint = $('#offline-hint');
        if (!empty || !title || !hint) {
            return;
        }
        if (!accountActive) {
            empty.hidden = false;
            title.textContent = 'Account niet actief';
            hint.textContent = 'Je kunt geen ritten ontvangen tot je account is geactiveerd.';
            if (offlineHint) {
                offlineHint.hidden = true;
            }
            return;
        }
        if (!isOnline) {
            if (offlineHint) {
                offlineHint.hidden = false;
            }
            empty.hidden = false;
            title.textContent = 'Je bent offline';
            hint.textContent = 'Zet je status op online om ritten te ontvangen.';
            return;
        }
        if (offlineHint) {
            offlineHint.hidden = true;
        }
        const activeStrip = $('#active-ride-strip');
        const hasActiveRide = currentActiveRide || (activeStrip && !activeStrip.hidden);
        if (!currentOffer && !hasActiveRide) {
            empty.hidden = false;
            title.textContent = 'Geen openstaande ritten.';
            hint.textContent = 'Nieuwe ritten verschijnen hier automatisch.';
        }
    }

    function showScreen(name) {
        screenLogin.classList.toggle('is-active', name === 'login');
        screenDispatch.classList.toggle('is-active', name === 'dispatch');
        syncScreenWakeLock();
    }

    function shouldKeepScreenAwake() {
        return !!(
            token &&
            accountActive &&
            isOnline &&
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

    function startNoSleepFallback() {
        if (!shouldKeepScreenAwake()) {
            stopNoSleepFallback();
            return;
        }
        cleanupOrphanNoSleepMedia();
        startNoSleepWebAudio();
        startNoSleepHtmlAudio();
        startNoSleepInlineVideo();
        if (isIosDevice()) {
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

    function playNewRideSound() {
        try {
            unlockAudio();
            if (!audioCtx) {
                return;
            }
            const ctx = audioCtx;
            const start = ctx.currentTime;
            const freqs = [880, 1174];
            freqs.forEach(function (freq, i) {
                const t = start + i * 0.18;
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.0001, t);
                gain.gain.exponentialRampToValueAtTime(0.4, t + 0.03);
                gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.14);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(t);
                osc.stop(t + 0.15);
            });
        } catch (e) {
            /* Audio niet beschikbaar (o.a. stille modus iOS). */
        }
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
        return 'Notification' in window;
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
        if (Notification.permission === 'granted') {
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
        if (isInBrowserTabOnIos()) {
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
            if (Notification.permission === 'denied') {
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
        if (Notification.permission === 'granted') {
            finish('granted');
            return;
        }
        if (Notification.permission === 'denied') {
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

        if (Notification.permission === 'granted') {
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
        if (!notificationsApiAvailable() || Notification.permission !== 'granted' || !offer) {
            return;
        }
        const ride = offer.ride || {};
        const waiting = !!opts.waiting || isOfferWaiting(offer);
        const title = waiting ? 'Rit wacht op chauffeur' : 'Nieuwe rit beschikbaar';
        const secWait = offer.seconds_waiting != null ? Math.max(0, Math.floor(Number(offer.seconds_waiting))) : 0;
        const body = waiting
            ? 'Wacht al ' + formatDuration(secWait) + ' — ' + rideOfferNotificationBody(ride)
            : rideOfferNotificationBody(ride);
        const icon = cfg.notificationIcon || '/assets/media/app/nexa-chauffeur-icon-192.png';
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
        } catch (e) {
            /* fallback hieronder */
        }

        try {
            const n = new Notification(title, {
                body: body,
                icon: icon,
                tag: tag,
                renotify: true,
            });
            n.onclick = function () {
                window.focus();
                n.close();
            };
        } catch (e) {
            /* Notification API niet beschikbaar */
        }
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

    function showNewRideAlert(show, waiting) {
        const el = $('#new-ride-alert');
        if (!el) {
            return;
        }
        el.hidden = !show;
        if (show) {
            el.textContent = waiting
                ? 'Rit wacht op chauffeur — reageer nu'
                : 'Nieuwe rit beschikbaar — reageer snel.';
        }
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

    function isOfferWaiting(offer) {
        if (!offer) {
            return false;
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

    function setOfferActionButtonsDisabled(disabled, loadingBtn) {
        ['#btn-accept', '#btn-decline'].forEach(function (sel) {
            const el = $(sel);
            if (!el) {
                return;
            }
            if (!disabled) {
                clearButtonLoading(el);
                return;
            }
            if (loadingBtn && el === loadingBtn) {
                setButtonLoading(el, true);
            } else {
                el.disabled = true;
            }
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
            title.textContent = multiple
                ? 'Nieuwe rit (' + (index + 1) + ' van ' + total + ')'
                : 'Nieuwe rit';
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
        if (!pendingOffers.length) {
            renderOffer(null);
            return;
        }
        syncAllPendingOffersWaitingState();
        const idx = Math.max(0, Math.min(index, pendingOffers.length - 1));
        offerQueueIndex = idx;
        renderOffer(pendingOffers[idx], idx, pendingOffers.length, { skipNotify: true });
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
        const pill = $('#online-pill');
        const toggle = $('#online-toggle');
        if (pill) {
            pill.textContent = isOnline ? 'Online' : 'Offline';
            pill.classList.toggle('offline', !isOnline);
        }
        if (toggle) {
            toggle.classList.toggle('is-on', isOnline);
            toggle.setAttribute('aria-pressed', isOnline ? 'true' : 'false');
            toggle.disabled = !accountActive;
        }
        updateEmptyState();
        updateNotificationsHint();
        updateIosAwakeHint();
    }

    async function setOnline(value) {
        if (!accountActive && value) {
            updateEmptyState();
            return;
        }
        isOnline = !!value;
        localStorage.setItem(ONLINE_KEY, isOnline ? '1' : '0');
        setOnlineUi();
        updateNotificationsHint();
        if (isOnline) {
            await prepareDriverAlerts();
            requestScreenWakeLockFromGesture();
        }
        if (!token) {
            return;
        }
        try {
            await api('/availability', {
                method: 'PUT',
                body: { is_online: isOnline },
            });
        } catch (e) {
            if (e.code === 'driver_not_active') {
                return;
            }
            console.warn(e);
        }
        if (isOnline) {
            await refreshInbox();
        } else {
            stopInboxSync();
            renderOffer(null);
            updateEmptyState();
        }
        syncScreenWakeLock();
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
        const timerWaitEl = $('#offer-timer-wait');
        const timerAcceptEl = $('#offer-timer-accept');
        const banner = $('#offer-waiting-banner');
        const card = $('#offer-card');
        const waiting = isOfferWaiting(offer);
        const secRemaining = offerSecondsRemaining(offer);
        const secWaiting = offerSecondsWaiting(offer);

        if (card) {
            card.classList.toggle('is-waiting', waiting);
        }
        if (banner) {
            if (waiting) {
                banner.textContent = 'Rit wacht op een chauffeur — reageer nu';
                banner.classList.add('is-visible');
                banner.hidden = false;
            } else {
                banner.textContent = '';
                banner.classList.remove('is-visible');
                banner.hidden = true;
            }
        }

        if (timerWaitEl) {
            if (waiting) {
                timerWaitEl.textContent = 'Wacht al ' + formatDuration(secWaiting);
                timerWaitEl.hidden = false;
            } else {
                timerWaitEl.textContent = '';
                timerWaitEl.hidden = true;
            }
        }

        if (timerAcceptEl) {
            timerAcceptEl.classList.remove('is-urgent');
            if (secRemaining > 0) {
                timerAcceptEl.textContent = 'Nog ' + secRemaining + ' s om te accepteren';
                timerAcceptEl.hidden = false;
                if (!waiting && secRemaining <= 60) {
                    timerAcceptEl.classList.add('is-urgent');
                }
            } else if (waiting) {
                timerAcceptEl.textContent = 'Reactietijd verlopen — reageer nu';
                timerAcceptEl.hidden = false;
                timerAcceptEl.classList.add('is-urgent');
            } else {
                timerAcceptEl.textContent = '';
                timerAcceptEl.hidden = true;
            }
        }

        if (waiting) {
            showNewRideAlert(true, true);
        } else {
            showNewRideAlert(false);
        }
    }

    function updateOfferTimerDisplay(offer) {
        updateOfferUrgencyUi(offer);
    }

    function clearOfferTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        const timerWaitEl = $('#offer-timer-wait');
        const timerAcceptEl = $('#offer-timer-accept');
        const banner = $('#offer-waiting-banner');
        const card = $('#offer-card');
        if (timerWaitEl) {
            timerWaitEl.textContent = '';
            timerWaitEl.hidden = true;
        }
        if (timerAcceptEl) {
            timerAcceptEl.textContent = '';
            timerAcceptEl.hidden = true;
            timerAcceptEl.classList.remove('is-urgent');
        }
        if (banner) {
            banner.textContent = '';
            banner.classList.remove('is-visible');
            banner.hidden = true;
        }
        if (card) {
            card.classList.remove('is-waiting');
        }
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
    }

    function isDriverAcceptedRide(ride) {
        if (!ride || !ride.id) {
            return false;
        }
        const status = ride.status != null ? String(ride.status) : '';
        return status === 'assigned' || status === 'accepted';
    }

    function setCompleteRideButtonVisible(visible) {
        const btn = $('#btn-complete-ride');
        if (btn) {
            btn.hidden = !visible;
        }
    }

    const PAYMENT_FAILED_STATUSES = ['failed', 'canceled', 'expired'];

    function setPayRideButtonVisible(visible) {
        const btn = $('#btn-pay-ride');
        if (btn) {
            btn.hidden = !visible;
        }
    }

    function setSendInvoiceButtonVisible(visible) {
        const btn = $('#btn-send-invoice');
        if (btn) {
            btn.hidden = !visible;
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

    function syncPaymentPanelUi(options) {
        const opts = options || {};
        const qrVisible = !!opts.qrVisible;
        const createBtn = $('#btn-payment-create');
        const cashBtn = $('#btn-cash-paid');
        const amountInput = $('#payment-amount');
        if (createBtn) {
            createBtn.hidden = qrVisible;
            createBtn.disabled = false;
        }
        if (cashBtn) {
            cashBtn.hidden = qrVisible;
            cashBtn.disabled = qrVisible;
        }
        if (amountInput && !qrVisible) {
            amountInput.disabled = false;
        }
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
        if (!ride || !isDriverAcceptedRide(ride)) {
            setPayRideButtonVisible(false);
            setSendInvoiceButtonVisible(false);
            setCompleteRideButtonVisible(false);
            if (errEl) {
                errEl.hidden = true;
                errEl.textContent = '';
            }
            return;
        }
        const payment = ride.payment || {};
        const isPaid = payment.status === 'paid';
        const showPayButton =
            driverPaymentEnabled &&
            (payment.requires_payment_before_complete || isPaid);
        const paymentError = isPaid ? '' : resolvePaymentError(ride, openPayment);
        const canComplete = payment.can_complete !== false;
        const invoice = ride.invoice || {};
        const showInvoiceButton = isPaid && invoice.can_send;
        setPayRideButtonVisible(showPayButton);
        setSendInvoiceButtonVisible(showInvoiceButton);
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
        const completeBtn = $('#btn-complete-ride');
        if (completeBtn) {
            completeBtn.hidden = !canComplete;
            completeBtn.disabled = !canComplete;
        }
    }

    function stopPaymentPoll() {
        if (paymentPollTimer) {
            clearInterval(paymentPollTimer);
            paymentPollTimer = null;
        }
    }

    function closePaymentPanel() {
        const panel = $('#payment-panel');
        if (panel) {
            panel.classList.remove('is-open');
            panel.hidden = true;
        }
        stopPaymentPoll();
    }

    function openPaymentPanel(ride) {
        const panel = $('#payment-panel');
        const amountInput = $('#payment-amount');
        const qrSection = $('#payment-qr-section');
        if (!panel || !ride) {
            return;
        }
        const amount =
            ride.payment && ride.payment.amount_due != null
                ? ride.payment.amount_due
                : ride.quoted_price;
        if (amountInput) {
            amountInput.value =
                amount != null && !isNaN(parseFloat(amount))
                    ? String(parseFloat(amount).toFixed(2))
                    : '';
            amountInput.disabled = false;
        }
        if (qrSection) {
            qrSection.hidden = true;
        }
        const statusText = $('#payment-status-text');
        if (statusText) {
            statusText.textContent = 'Wachten op betaling…';
        }
        syncPaymentPanelUi({ qrVisible: false });
        panel.hidden = false;
        panel.classList.add('is-open');
    }

    async function loadPaymentState(rideId) {
        const res = await api('/dispatch/rides/' + rideId + '/payment');
        return res.data || null;
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
            const res = await api('/dispatch/rides/' + rideId + '/payment', {
                method: 'POST',
                body: { amount: amount },
            });
            const data = res.data || {};
            if (data.ride) {
                currentActiveRide = data.ride;
            }
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

    let cashConfirmResolver = null;

    function closeCashConfirmDialog(confirmed) {
        const dialog = $('#cash-confirm-dialog');
        const okBtn = $('#cash-confirm-ok');
        if (dialog) {
            dialog.classList.remove('is-open');
            dialog.hidden = true;
            dialog.setAttribute('aria-hidden', 'true');
        }
        if (okBtn) {
            clearButtonLoading(okBtn);
        }
        document.body.classList.remove('driver-dialog-open');
        if (cashConfirmResolver) {
            cashConfirmResolver(!!confirmed);
            cashConfirmResolver = null;
        }
    }

    function showCashConfirmDialog(amount) {
        const dialog = $('#cash-confirm-dialog');
        const amountEl = $('#cash-confirm-amount');
        if (!dialog) {
            return Promise.resolve(
                window.confirm(
                    'Bevestig: klant heeft ' +
                        formatEuro(amount) +
                        ' contant betaald? Dit bedrag wordt vastgelegd.'
                )
            );
        }
        if (amountEl) {
            amountEl.textContent = formatEuro(amount);
        }
        dialog.hidden = false;
        dialog.setAttribute('aria-hidden', 'false');
        dialog.classList.add('is-open');
        document.body.classList.add('driver-dialog-open');
        return new Promise(function (resolve) {
            cashConfirmResolver = resolve;
            const okBtn = $('#cash-confirm-ok');
            if (okBtn) {
                okBtn.focus();
            }
        });
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
                closeCashConfirmDialog(true);
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                closeCashConfirmDialog(false);
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                closeCashConfirmDialog(false);
            });
        }
        document.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Escape' || !dialog.classList.contains('is-open')) {
                return;
            }
            closeCashConfirmDialog(false);
        });
    }

    async function markRideCashPaid() {
        const rideId = resolveActiveRideId();
        if (!rideId) {
            return;
        }
        const amount = parsePaymentAmountInput();
        if (amount === null) {
            alert('Vul een geldig bedrag in.');
            return;
        }
        const confirmed = await showCashConfirmDialog(amount);
        if (!confirmed) {
            return;
        }
        const cashBtn = $('#btn-cash-paid');
        const okBtn = $('#cash-confirm-ok');
        setButtonLoading(cashBtn, true);
        setButtonLoading(okBtn, true, 'Bevestigen…');
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
            } else {
                await refreshInbox();
            }
        } catch (e) {
            alert(e.message || 'Contante betaling kon niet worden geregistreerd.');
            syncPaymentPanelUi({ qrVisible: false });
        } finally {
            clearButtonLoading(cashBtn);
        }
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
    }

    function openInvoicePanel(invoiceData) {
        const panel = $('#invoice-panel');
        const emailInput = $('#invoice-email');
        const numberInput = $('#invoice-number');
        if (!panel) {
            return;
        }
        const data = invoiceData || {};
        if (emailInput) {
            emailInput.value = data.customer_email || '';
        }
        if (numberInput) {
            numberInput.value = data.invoice_number || '';
        }
        const status = $('#invoice-send-status');
        if (status) {
            status.hidden = true;
            status.textContent = '';
            status.classList.remove('is-error');
        }
        panel.hidden = false;
        panel.classList.add('is-open');
    }

    async function openSendInvoiceFlow() {
        const rideId = resolveActiveRideId();
        if (!rideId) {
            return;
        }
        const sendInvoiceBtn = $('#btn-send-invoice');
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
        const payBtn = $('#btn-pay-ride');
        const payWasPaid = payBtn && payBtn.classList.contains('is-paid');
        setButtonLoading(payBtn, true);
        try {
            const data = await loadPaymentState(rideId);
            if (data && data.ride) {
                currentActiveRide = data.ride;
            }
            syncRideActionButtons(currentActiveRide, data && data.open_payment);
            openPaymentPanel(currentActiveRide);
            if (data && data.open_payment && data.open_payment.checkout_url) {
                showPaymentQr(data.open_payment);
                startPaymentPoll(rideId);
            }
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
            if (empty) {
                empty.hidden = !isOnline && accountActive;
                updateEmptyState();
            }
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
        setCustomerLine($('#offer-customer'), offer.ride.customer_name, offer.ride.customer_phone);
        $('#offer-price').textContent = formatEuro(offer.ride.quoted_price);

        startOfferTimer(offer);
    }

    function renderActiveRide(ride) {
        const el = $('#active-ride');
        const empty = $('#inbox-empty');
        const completeBtn = $('#btn-complete-ride');
        if (!ride) {
            currentActiveRide = null;
            setActiveRideUiVisible(false);
            if (el) {
                el.innerHTML = '';
            }
            if (completeBtn) {
                completeBtn.disabled = false;
                delete completeBtn.dataset.rideId;
            }
            setCompleteRideButtonVisible(false);
            return;
        }
        currentActiveRide = ride;
        setOfferUiVisible(false);
        setActiveRideUiVisible(true);
        setCompleteRideButtonVisible(isDriverAcceptedRide(ride));
        if (empty) empty.hidden = true;
        showNewRideAlert(false);
        if (el) {
            const acceptedText = activeRideAcceptedMessage || 'Rit geaccepteerd.';
            const customerHtml = customerLineHtml(ride.customer_name, ride.customer_phone);
            el.innerHTML =
                '<p class="banner-ride-accepted" role="status">' + escapeHtml(acceptedText) + '</p>' +
                '<p class="offer-title">Jouw rit</p>' +
                '<p class="offer-meta" style="margin:-0.25rem 0 0.75rem;font-size:0.8125rem;color:#94a3b8;">' +
                'Rond de rit af wanneer de klant is afgezet. Daarna kun je weer nieuwe ritten ontvangen.' +
                '</p>' +
                addressLinkHtml(ride.pickup_address, '📍') +
                addressLinkHtml(ride.dropoff_address, '🏁') +
                customerHtml +
                '<p class="offer-price">' +
                formatEuro(ride.quoted_price) +
                '</p>';
            activeRideAcceptedMessage = null;
        }
        if (completeBtn && isDriverAcceptedRide(ride)) {
            completeBtn.dataset.rideId = String(ride.id);
        }
        syncRideActionButtons(ride);
        syncScreenWakeLock();
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
        const inner = customerLinePartsHtml(name, phone);
        if (!inner) {
            return '';
        }
        return '<p class="offer-meta offer-customer">' + inner + '</p>';
    }

    function setCustomerLine(el, name, phone) {
        if (!el) {
            return;
        }
        const inner = customerLinePartsHtml(name, phone);
        el.innerHTML = inner;
        el.hidden = !inner;
    }

    function setAddressLink(el, address) {
        if (!el) {
            return;
        }
        const text = address != null ? String(address).trim() : '';
        el.textContent = text || '—';
        if (text) {
            el.href = mapsSearchUrl(text);
            el.removeAttribute('aria-disabled');
        } else {
            el.removeAttribute('href');
            el.setAttribute('aria-disabled', 'true');
        }
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

    async function refreshInbox() {
        if (!token || !isOnline) {
            return;
        }
        if (shouldKeepScreenAwake()) {
            syncScreenWakeLock();
        }
        try {
            const res = await api('/dispatch/inbox');
            const offers = (res.data && res.data.offers) || [];
            if (res.meta) {
                if (res.meta.offer_ttl_seconds) {
                    configuredOfferTtlSeconds = Math.max(15, parseInt(res.meta.offer_ttl_seconds, 10) || 300);
                }
                driverPaymentEnabled = !!res.meta.driver;
            }
            syncWaitingRideIdsFromOffers(offers);
            const active = res.data && res.data.active_ride;
            if (active) {
                renderActiveRide(active);
                currentOffer = null;
                clearOfferTimer();
                setOfferUiVisible(false);
                $('#inbox-empty').hidden = true;
                return;
            }
            pendingOffers = offers;
            syncAllPendingOffersWaitingState();
            detectNewOffersInInbox(offers);
            if (showNewRideAlertAfterComplete && offers.length > 0) {
                showNewRideAlertAfterComplete = false;
                showNewRideAlert(true);
            }
            if (offers.length > 0) {
                if (currentOffer) {
                    const found = offers.findIndex(function (o) {
                        return o.id === currentOffer.id;
                    });
                    if (found >= 0) {
                        offerQueueIndex = found;
                    } else if (offerQueueIndex >= offers.length) {
                        offerQueueIndex = 0;
                    }
                }
                const shown = offers[offerQueueIndex] || offers[0];
                if (!currentOffer || currentOffer.id !== shown.id) {
                    renderOffer(shown, offerQueueIndex, offers.length);
                } else {
                    mergeOfferFromServer(currentOffer, shown);
                    updateOfferTimerDisplay(currentOffer);
                    updateOfferQueueUi(offerQueueIndex, offers.length);
                }
            } else {
                clearOfferNotificationState();
                renderOffer(null);
            }
        } catch (e) {
            console.warn('inbox', e);
            const empty = $('#inbox-empty');
            const title = $('#inbox-empty-title');
            const hint = $('#inbox-empty-hint');
            if (empty && title && hint && isOnline && accountActive) {
                empty.hidden = false;
                title.textContent = 'Kon ritten niet laden';
                hint.textContent = e.message || 'Probeer opnieuw of log opnieuw in.';
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
        sessionStorage.setItem(STORAGE_KEY, token);
        if (data.user && data.user.company_id) {
            persistCompanyId(data.user.company_id);
        }
        if (data.user && typeof data.user.is_online === 'boolean') {
            applyOnlineStateFromServer(data.user.is_online);
        }
        if (data.meta && data.meta.poll_interval_ms) {
            cfg.pollMs = data.meta.poll_interval_ms;
        }
        return data;
    }

    function logout(callApi) {
        stopInboxSync();
        clearOfferTimer();
        if (callApi && token) {
            fetch(cfg.apiBase + '/logout', {
                method: 'POST',
                headers: headers(),
            }).catch(function () {});
        }
        token = '';
        companyId = null;
        sessionStorage.removeItem(STORAGE_KEY);
        sessionStorage.removeItem(COMPANY_KEY);
        releaseScreenWakeLock();
        showScreen('login');
    }

    function resolveOfferIdFromClick(ev) {
        const btn = ev && ev.target && ev.target.closest
            ? ev.target.closest('[data-offer-id]')
            : null;
        if (btn && btn.dataset.offerId) {
            return parseInt(btn.dataset.offerId, 10);
        }
        return currentOffer && currentOffer.id ? currentOffer.id : null;
    }

    async function acceptOffer(ev) {
        const offerId = resolveOfferIdFromClick(ev);
        if (!offerId) {
            return;
        }
        const activeBtn = ev.target.closest('#btn-accept') || $('#btn-accept');
        setOfferActionButtonsDisabled(true, activeBtn);
        try {
            const res = await api('/dispatch/offers/' + offerId + '/accept', { method: 'POST' });
            activeRideAcceptedMessage = (res && res.message) || 'Rit geaccepteerd.';
            showNewRideAlert(false);
            vibrate(100);
            if (res && res.data && res.data.ride) {
                renderActiveRide(res.data.ride);
            }
            await refreshInbox();
        } catch (e) {
            alert(e.message);
            await refreshInbox();
        } finally {
            setOfferActionButtonsDisabled(false);
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
            await api('/dispatch/offers/' + offerId + '/decline', { method: 'POST' });
            await refreshInbox();
        } catch (e) {
            alert(e.message);
        } finally {
            setOfferActionButtonsDisabled(false);
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
        setButtonLoading(btn, true);
        try {
            await api('/dispatch/rides/' + rideId + '/complete', { method: 'POST' });
            vibrate(100);
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
        sessionStorage.setItem(COMPANY_KEY, String(parsed));
    }

    function applyOnlineStateFromServer(isOnlineOnServer) {
        if (typeof isOnlineOnServer !== 'boolean') {
            isOnline = localStorage.getItem(ONLINE_KEY) === '1';
        } else {
            isOnline = isOnlineOnServer;
        }
        localStorage.setItem(ONLINE_KEY, isOnline ? '1' : '0');
        setOnlineUi();
    }

    async function bootstrap() {
        updateNotificationsHint();
        if (!token) {
            showScreen('login');
            return;
        }
        showScreen('dispatch');
        try {
            const me = await api('/me');
            if (me.user && me.user.company_id) {
                persistCompanyId(me.user.company_id);
            }
            const active = me.user && me.user.is_account_active !== false;
            setAccountInactive(!active);
            applyOnlineStateFromServer(me.user && me.user.is_online);
            if (accountActive) {
                if (isOnline) {
                    startInboxSync();
                } else {
                    stopInboxSync();
                    updateEmptyState();
                }
            }
        } catch (e) {
            if (e.code === 'driver_not_active') {
                applyOnlineStateFromServer(false);
                updateEmptyState();
                return;
            }
            token = '';
            sessionStorage.removeItem(STORAGE_KEY);
            sessionStorage.removeItem(COMPANY_KEY);
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
        setButtonLoading(btn, true, 'Inloggen…');
        try {
            await login($('#email').value.trim(), $('#password').value);
            unlockAudio();
            showScreen('dispatch');
            requestScreenWakeLockFromGesture();
            await setOnline(true);
            syncScreenWakeLock();
            startInboxSync();
        } catch (e) {
            err.textContent = e.message;
            err.hidden = false;
        } finally {
            clearButtonLoading(btn);
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
            updateEmptyState();
        }
        });
    }

    if (screenDispatch) {
        screenDispatch.addEventListener('click', function (ev) {
            if (ev.target.closest('#btn-accept')) {
                ev.preventDefault();
                acceptOffer(ev);
                return;
            }
            if (ev.target.closest('#btn-decline')) {
                ev.preventDefault();
                declineOffer(ev);
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
            if (ev.target.closest('#btn-complete-ride')) {
                ev.preventDefault();
                completeActiveRide();
                return;
            }
            if (ev.target.closest('#btn-pay-ride')) {
                ev.preventDefault();
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
                closePaymentPanel();
                return;
            }
            if (ev.target.closest('#btn-payment-create')) {
                ev.preventDefault();
                createRidePayment();
                return;
            }
            if (ev.target.closest('#btn-send-invoice')) {
                ev.preventDefault();
                openSendInvoiceFlow();
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
            }
            return;
        }
        stopNoSleepFallback();
        stopWakeLockMaintenance();
        if (screenWakeLock) {
            screenWakeLock.release().catch(function () {});
            screenWakeLock = null;
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

    const btnDismissNotificationsHint = $('#btn-dismiss-notifications-hint');
    if (btnDismissNotificationsHint) {
        btnDismissNotificationsHint.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            dismissNotificationsHint();
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
    });

    initCashConfirmDialog();

    bootstrap();
})();
