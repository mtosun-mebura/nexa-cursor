(function () {
    'use strict';

    const app = document.getElementById('app');
    if (!app) return;

    const cfg = {
        apiBase: app.dataset.apiBase || '/api/taxi/v1/customer',
        loginUrl: app.dataset.loginUrl,
        registerUrl: app.dataset.registerUrl,
        bookUrl: app.dataset.bookUrl,
        quoteUrl: app.dataset.quoteUrl,
        liveUrl: app.dataset.liveUrl,
        addressSearchUrl: app.dataset.addressSearchUrl,
        nearbyTaxisUrl: app.dataset.nearbyTaxisUrl || '/nexa-taxi/booking/nearby-taxis',
        taxiCarUrl: app.dataset.taxiCarUrl || '/images/gps/car-sedan.png',
        taxiVanUrl: app.dataset.taxiVanUrl || '/images/gps/car-van.png',
        taxiBusUrl: app.dataset.taxiBusUrl || '/images/gps/car-bus.png',
        launcherUrl: app.dataset.launcherUrl || '/taxi/app',
        googleMapsApiKey: (app.dataset.mapsKey || '').trim(),
        googleMapsMapId: (app.dataset.mapsMapId || '').trim(),
        centerLat: parseFloat(app.dataset.centerLat || '52.2215'),
        centerLng: parseFloat(app.dataset.centerLng || '6.8936'),
        marketplaceSectionKey: 'component:taxi.algemene_boekingsmodule',
        fleetRadiusKm: 50,
    };

    const TOKEN_KEY = 'nexa_taxi_customer_token';
    const MODE_KEY = 'nexa_taxi_customer_mode';
    const GUEST_RIDES_KEY = 'nexa_taxi_guest_rides';
    const PROFILE_DRAFT_KEY = 'nexa_taxi_guest_profile';
    const SCREEN_KEY = 'nexa_taxi_customer_screen';
    const TRACK_KEY = 'nexa_taxi_customer_track_token';
    const ARCHIVED_RIDES_KEY = 'nexa_taxi_customer_archived_rides';

    function readSession(key) {
        try { return localStorage.getItem(key) || ''; } catch (e) { return ''; }
    }
    function writeSession(key, value) {
        try {
            if (value) localStorage.setItem(key, String(value));
            else localStorage.removeItem(key);
        } catch (e) {}
    }

    const state = {
        token: localStorage.getItem(TOKEN_KEY) || '',
        mode: localStorage.getItem(MODE_KEY) || '',
        user: null,
        pickup: null,
        dropoff: null,
        quote: null,
        trackToken: readSession(TRACK_KEY) || null,
        liveTimer: null,
        fleetTimer: null,
        map: null,
        liveMap: null,
        pickupMarker: null,
        dropoffMarker: null,
        livePickupMarker: null,
        liveDropoffMarker: null,
        vehicleMarker: null,
        liveRouteLine: null,
        liveRouteKey: '',
        liveRouteFetchSeq: 0,
        taxiMarkers: {},
        taxiPrev: {},
        taxiOverlays: {},
        fleetRadiusCircle: null,
        fleetDidFit: false,
        fleetCount: null,
        nearestTaxi: null,
        acceptedNotified: false,
        liveRide: null,
        livePhase: null,
        mapsReady: false,
        routeLine: null,
        routeFetchSeq: 0,
        routeMetricsCache: null,
        placesService: null,
        addressCache: {},
        audioCtx: null,
        showArchivedRides: false,
        ridesCache: [],
        expandedRideToken: '',
        bookVehicleMarker: null,
        followDidCenter: false,
        screen: '',
    };

    let googleMapsLoadPromise = null;
    let CustomerTaxiOverlay = null;
    const ADDRESS_CACHE_MAX = 40;
    const FLEET_POLL_MS = 2500;

    const el = (id) => document.getElementById(id);
    const screens = {
        welcome: el('screen-welcome'),
        auth: el('screen-auth'),
        book: el('screen-book'),
        live: el('screen-live'),
        rides: el('screen-rides'),
        profile: el('screen-profile'),
    };

    function setTrackToken(token) {
        const value = token ? String(token).trim() : '';
        state.trackToken = value || null;
        writeSession(TRACK_KEY, state.trackToken || '');
    }

    function persistScreenHash(name) {
        try {
            const path = window.location.pathname + (window.location.search || '');
            if (!name || name === 'welcome') {
                if (window.location.hash) {
                    window.history.replaceState({}, '', path);
                }
                return;
            }
            const nextHash = '#' + name;
            if (window.location.hash !== nextHash) {
                window.history.replaceState({}, '', path + nextHash);
            }
        } catch (e) {}
    }

    function showScreen(name) {
        state.screen = name;
        Object.keys(screens).forEach((key) => {
            if (screens[key]) screens[key].hidden = key !== name;
        });
        updateTabBar(name);
        writeSession(SCREEN_KEY, name);
        persistScreenHash(name);
        if (name === 'live' && state.trackToken) {
            writeSession(TRACK_KEY, state.trackToken);
        }
        try {
            window.dispatchEvent(new CustomEvent('nexa-customer-screen', { detail: { screen: name } }));
        } catch (e) {}
        if (name === 'rides' || name === 'live') {
            startOpenRidesPoll();
        } else if (state.openRidesTimer) {
            window.clearTimeout(state.openRidesTimer);
            state.openRidesTimer = null;
        }
    }

    function updateTabBar(screenName) {
        const bar = el('app-tabbar');
        if (!bar) return;
        const show = screenName === 'book' || screenName === 'rides'
            || screenName === 'profile' || screenName === 'live';
        bar.hidden = !show;
        const activeTab = screenName === 'live' ? 'rides' : screenName;
        bar.querySelectorAll('.tab[data-tab]').forEach(function (tab) {
            tab.classList.toggle('active', tab.dataset.tab === activeTab);
        });
        refreshActiveRideUi();
        syncTabbarKeyboardVisibility();
    }

    function isEditableFocusTarget(node) {
        if (!node || node.nodeType !== 1) return false;
        if (node.isContentEditable) return true;
        const tag = String(node.tagName || '').toUpperCase();
        if (tag === 'TEXTAREA' || tag === 'SELECT') return true;
        if (tag !== 'INPUT') return false;
        const type = String(node.type || 'text').toLowerCase();
        return !(['button', 'submit', 'reset', 'checkbox', 'radio', 'file', 'hidden', 'range', 'color'].indexOf(type) >= 0);
    }

    /** Verberg vaste footer terwijl het mobiele toetsenbord open is (voorkomt half meescrollen). */
    function syncTabbarKeyboardVisibility() {
        const bar = el('app-tabbar');
        if (!bar) return;
        const vv = window.visualViewport;
        const layoutH = window.innerHeight || 0;
        const viewportH = vv && isFinite(vv.height) ? vv.height : layoutH;
        const shrink = Math.max(0, layoutH - viewportH);
        const typing = isEditableFocusTarget(document.activeElement);
        const narrow = !(window.matchMedia) || window.matchMedia('(max-width: 48rem)').matches;
        // Mobiel + tekstveld in focus ≈ toetsenbord open. visualViewport-shrink als extra signaal.
        const keyboardOpen = (narrow && typing) || shrink > 120;
        bar.classList.toggle('is-keyboard-hidden', !!keyboardOpen && !bar.hidden);
    }

    function bindKeyboardTabbarVisibility() {
        const sync = function () { syncTabbarKeyboardVisibility(); };
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', sync);
            window.visualViewport.addEventListener('scroll', sync);
        }
        window.addEventListener('resize', sync);
        document.addEventListener('focusin', sync);
        document.addEventListener('focusout', function () {
            // iOS sluit het toetsenbord met een korte delay na blur.
            setTimeout(sync, 150);
        });
        sync();
    }

    function toast(msg) {
        const t = el('toast');
        if (!t) return;
        t.textContent = msg;
        t.hidden = false;
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => { t.hidden = true; }, 3200);
    }

    function unlockAudio() {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        if (!state.audioCtx) {
            try { state.audioCtx = new Ctx(); } catch (e) { return; }
        }
        if (state.audioCtx.state === 'suspended') {
            state.audioCtx.resume().catch(function () {});
        }
    }

    function playAcceptedSignal() {
        try {
            if (navigator.vibrate) {
                navigator.vibrate([120, 60, 120, 60, 200]);
            }
        } catch (e) {}

        try {
            unlockAudio();
            const ctx = state.audioCtx;
            if (!ctx) return;
            const start = ctx.currentTime;
            [
                { freq: 880, delay: 0, dur: 0.16, peak: 0.28 },
                { freq: 1174, delay: 0.14, dur: 0.18, peak: 0.26 },
                { freq: 1568, delay: 0.3, dur: 0.28, peak: 0.24 },
            ].forEach(function (note) {
                const t = start + note.delay;
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = note.freq;
                gain.gain.setValueAtTime(0.0001, t);
                gain.gain.exponentialRampToValueAtTime(note.peak, t + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, t + note.dur);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(t);
                osc.stop(t + note.dur + 0.02);
            });
        } catch (e) {
            /* Audio niet beschikbaar (o.a. stille modus iOS). */
        }
    }

    function csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    async function api(url, options = {}) {
        const headers = Object.assign({
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }, options.headers || {});
        if (state.token) headers.Authorization = 'Bearer ' + state.token;
        if (options.method && options.method !== 'GET') {
            headers['X-CSRF-TOKEN'] = csrf();
        }
        const res = await fetch(url, Object.assign({}, options, { headers }));
        let data = null;
        try { data = await res.json(); } catch (e) { data = null; }
        if (!res.ok) {
            const err = new Error((data && (data.message || data.error)) || ('Fout ' + res.status));
            err.status = res.status;
            err.data = data;
            throw err;
        }
        return data;
    }

    function setToken(token) {
        state.token = token || '';
        if (token) localStorage.setItem(TOKEN_KEY, token);
        else localStorage.removeItem(TOKEN_KEY);
    }

    function setMode(mode) {
        state.mode = mode || '';
        if (mode) localStorage.setItem(MODE_KEY, mode);
        else localStorage.removeItem(MODE_KEY);
        try {
            localStorage.setItem('nexa_taxi_app_role', mode === 'guest' ? 'customer-guest' : 'customer');
        } catch (e) {}
    }

    function guestRides() {
        try { return JSON.parse(localStorage.getItem(GUEST_RIDES_KEY) || '[]'); } catch (e) { return []; }
    }

    function writeGuestRides(list) {
        localStorage.setItem(GUEST_RIDES_KEY, JSON.stringify((list || []).slice(0, 50)));
    }

    function rideArchiveKey(ride) {
        if (!ride) return '';
        const id = Number(ride.id) || 0;
        if (id > 0) return 'id:' + id;
        const token = String(ride.token || '').trim();
        return token ? ('token:' + token) : '';
    }

    function archivedRideKeys() {
        try {
            const raw = JSON.parse(localStorage.getItem(ARCHIVED_RIDES_KEY) || '[]');
            return Array.isArray(raw) ? raw.map(String).filter(Boolean) : [];
        } catch (e) {
            return [];
        }
    }

    function writeArchivedRideKeys(keys) {
        const unique = [];
        (keys || []).forEach(function (key) {
            const value = String(key || '').trim();
            if (value && unique.indexOf(value) < 0) unique.push(value);
        });
        localStorage.setItem(ARCHIVED_RIDES_KEY, JSON.stringify(unique.slice(0, 200)));
    }

    function isRideArchived(ride) {
        const key = rideArchiveKey(ride);
        return !!(key && archivedRideKeys().indexOf(key) >= 0);
    }

    function setRideArchived(ride, archived) {
        const key = rideArchiveKey(ride);
        if (!key) return false;
        const keys = archivedRideKeys().filter(function (k) { return k !== key; });
        if (archived) keys.unshift(key);
        writeArchivedRideKeys(keys);
        return true;
    }

    function saveGuestRide(rideId, trackToken, meta) {
        const id = Number(rideId) || 0;
        const token = String(trackToken || '').trim();
        if (!token) return;
        const list = guestRides().filter(function (r) {
            return r && r.token !== token && Number(r.id) !== id;
        });
        const prev = guestRides().find(function (r) {
            return r && (r.token === token || Number(r.id) === id);
        }) || {};
        list.unshift({
            id: id || prev.id || 0,
            token: token,
            at: Date.now(),
            from: (meta && meta.from) || prev.from || '',
            to: (meta && meta.to) || prev.to || '',
            phase: (meta && meta.phase) || prev.phase || 'searching',
            status_label: (meta && meta.status_label) || prev.status_label || 'Zoeken…',
            payment_failure_status: (meta && meta.payment_failure_status) || prev.payment_failure_status || null,
            rating: (meta && meta.rating !== undefined) ? meta.rating : (prev.rating || null),
            pickup_at: (meta && meta.pickup_at) || prev.pickup_at || '',
            passengers: (meta && meta.passengers != null) ? meta.passengers : (prev.passengers != null ? prev.passengers : null),
            baggage_summary: (meta && meta.baggage_summary != null) ? meta.baggage_summary : (prev.baggage_summary || ''),
            baggage: (meta && meta.baggage !== undefined) ? meta.baggage : (prev.baggage || null),
            distance_km: (meta && meta.distance_km != null) ? meta.distance_km : (prev.distance_km != null ? prev.distance_km : null),
            duration_seconds: (meta && meta.duration_seconds != null) ? meta.duration_seconds : (prev.duration_seconds != null ? prev.duration_seconds : null),
            quoted_price: (meta && meta.quoted_price !== undefined) ? meta.quoted_price : (prev.quoted_price != null ? prev.quoted_price : null),
            amount: (meta && meta.amount) || prev.amount || '',
            pickup_confirmation: (meta && meta.pickup_confirmation !== undefined)
                ? meta.pickup_confirmation
                : (prev.pickup_confirmation || null),
        });
        writeGuestRides(list);
        refreshActiveRideUi();
    }

    function updateGuestRideByToken(trackToken, patch) {
        const token = String(trackToken || '').trim();
        if (!token) return;
        const clean = {};
        Object.keys(patch || {}).forEach(function (key) {
            if (patch[key] === undefined) return;
            if (key === 'rating' || key === 'pickup_confirmation') {
                clean[key] = patch[key];
                return;
            }
            if (patch[key] !== null && patch[key] !== '') {
                clean[key] = patch[key];
            }
        });
        const list = guestRides().map(function (r) {
            if (!r || r.token !== token) return r;
            return Object.assign({}, r, clean);
        });
        writeGuestRides(list);
        refreshActiveRideUi();
    }

    let liveRateStars = 0;

    function hideLiveRating() {
        const box = el('live-rating');
        if (box) box.hidden = true;
        liveRateStars = 0;
        syncLiveRatingStars(0);
        const comment = el('live-rating-comment');
        const btn = el('btn-live-rate');
        const done = el('live-rating-done');
        const stars = el('live-rating-stars');
        if (comment) {
            comment.hidden = true;
            comment.value = '';
        }
        if (btn) btn.hidden = true;
        if (done) {
            done.hidden = true;
            done.textContent = '';
        }
        if (stars) stars.hidden = true;
    }

    function syncLiveRatingStars(n) {
        const wrap = el('live-rating-stars');
        if (!wrap) return;
        wrap.querySelectorAll('.live-rating__star').forEach(function (btn) {
            const v = Number(btn.getAttribute('data-stars') || 0);
            btn.classList.toggle('is-on', v > 0 && v <= n);
        });
    }

    function renderLiveRating(ride) {
        const box = el('live-rating');
        if (!box) return;
        const rating = ride && ride.rating ? ride.rating : null;
        if (!rating || (!rating.can_rate && !rating.rated)) {
            hideLiveRating();
            return;
        }
        box.hidden = false;
        const label = el('live-rating-label');
        const stars = el('live-rating-stars');
        const comment = el('live-rating-comment');
        const btn = el('btn-live-rate');
        const done = el('live-rating-done');
        if (rating.rated && rating.stars) {
            if (label) label.textContent = 'Jouw beoordeling';
            if (stars) stars.hidden = true;
            if (comment) comment.hidden = true;
            if (btn) btn.hidden = true;
            if (done) {
                done.hidden = false;
                done.textContent = '★'.repeat(Number(rating.stars))
                    + '☆'.repeat(Math.max(0, 5 - Number(rating.stars)))
                    + (rating.comment ? (' — ' + rating.comment) : '');
            }
            return;
        }
        if (label) label.textContent = 'Beoordeel je rit';
        if (stars) stars.hidden = false;
        if (comment) comment.hidden = false;
        if (btn) {
            btn.hidden = false;
            btn.disabled = false;
            btn.textContent = 'Beoordeling versturen';
        }
        if (done) {
            done.hidden = true;
            done.textContent = '';
        }
        syncLiveRatingStars(liveRateStars);
    }

    async function submitLiveRating() {
        const token = state.trackToken;
        if (!token) return;
        if (liveRateStars < 1) {
            alert('Kies 1 tot 5 sterren.');
            return;
        }
        const btn = el('btn-live-rate');
        const comment = el('live-rating-comment');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Bezig…';
        }
        try {
            const data = await api(cfg.apiBase + '/live/rate', {
                method: 'POST',
                body: {
                    token: token,
                    stars: liveRateStars,
                    comment: comment && comment.value ? comment.value.trim() : null,
                },
            });
            if (data && data.ride) {
                renderLive(data.ride);
            } else if (data && data.rating) {
                renderLiveRating({ rating: data.rating, phase: 'completed' });
            }
            liveRateStars = 0;
        } catch (e) {
            alert((e && e.message) || 'Beoordeling opslaan mislukt.');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Beoordeling versturen';
            }
        }
    }

    function isActiveRidePhase(phase) {
        return phase === 'searching' || phase === 'accepted'
            || phase === 'awaiting_payment' || phase === 'other';
    }

    /** Tab-badge: alleen actieve rit of wachten op chauffeur-acceptatie. */
    function isRidesTabBadgePhase(phase) {
        return phase === 'searching' || phase === 'accepted';
    }

    function countRidesTabBadge() {
        const seen = Object.create(null);
        let count = 0;

        function add(key, phase) {
            if (!isRidesTabBadgePhase(phase)) return;
            const k = String(key || '');
            if (!k || seen[k]) return;
            seen[k] = true;
            count += 1;
        }

        (state.ridesCache || []).forEach(function (ride) {
            if (!ride) return;
            add(ride.token ? ('t:' + ride.token) : ('i:' + String(ride.id || '')), ride.phase);
        });
        guestRides().forEach(function (ride) {
            if (!ride || !ride.token) return;
            add('t:' + ride.token, ride.phase || 'searching');
        });

        return count;
    }

    function selectedPaymentMethod() {
        return 'booking';
    }

    function updatePaymentUi(payment) {
        const card = el('payment-card');
        if (!card) return;
        const opts = payment || {};
        const canBooking = opts.booking !== false;
        card.hidden = !canBooking;
        const emailLabel = document.querySelector('#email-field label');
        if (emailLabel) {
            emailLabel.textContent = 'E-mail (verplicht voor betaling)';
        }
    }

    function activeGuestRide() {
        if (state.trackToken) {
            const current = guestRides().find(function (r) {
                return r && r.token === state.trackToken;
            });
            if (current && isActiveRidePhase(current.phase || 'searching')) return current;
            if (current) return current;
        }
        return guestRides().find(function (r) {
            return r && r.token && isActiveRidePhase(r.phase || 'searching');
        }) || null;
    }

    function refreshActiveRideUi() {
        const active = activeGuestRide();
        const badge = el('tab-rides-badge');
        if (badge) {
            const badgeCount = countRidesTabBadge();
            badge.classList.toggle('is-on', badgeCount > 0);
            badge.textContent = badgeCount > 0 ? String(badgeCount) : '';
            badge.setAttribute('aria-hidden', badgeCount > 0 ? 'false' : 'true');
        }
        const banner = el('active-ride-banner');
        if (!banner) return;
        const onBook = screens.book && !screens.book.hidden;
        if (!active || !isActiveRidePhase(active.phase) || !onBook) {
            banner.hidden = true;
            return;
        }
        banner.hidden = false;
        const title = el('active-ride-title');
        const meta = el('active-ride-meta');
        if (title) {
            title.textContent = active.phase === 'accepted'
                ? (active.status_label || 'Taxi onderweg')
                : 'Openstaande rit';
        }
        if (meta) {
            const from = shortAddress(active.from);
            const to = shortAddress(active.to);
            const route = (from && from !== 'Onbekend' && to && to !== 'Onbekend')
                ? (from + ' → ' + to)
                : (from && from !== 'Onbekend' ? from : '');
            meta.textContent = route || (active.status_label || 'Tik om te volgen');
        }
    }

    function openActiveRide() {
        const active = activeGuestRide();
        if (!active || !active.token) {
            stopFleetPolling();
            showScreen('rides');
            loadRides();
            return;
        }
        setTrackToken(active.token);
        state.acceptedNotified = !!(active.phase && active.phase !== 'searching');
        state.livePhase = active.phase || null;
        openLive(null);
    }

    function fillProfileDraft() {
        try {
            const d = JSON.parse(localStorage.getItem(PROFILE_DRAFT_KEY) || '{}');
            if (d.first_name) el('first-name').value = d.first_name;
            if (d.last_name) el('last-name').value = d.last_name;
            if (d.phone) el('phone').value = d.phone;
            if (d.email) el('email').value = d.email;
        } catch (e) {}
    }
    function persistProfileDraft() {
        localStorage.setItem(PROFILE_DRAFT_KEY, JSON.stringify({
            first_name: el('first-name').value.trim(),
            last_name: el('last-name').value.trim(),
            phone: el('phone').value.trim(),
            email: el('email').value.trim(),
        }));
    }

    function applyUserToForm(user) {
        if (!user) return;
        el('first-name').value = user.first_name || '';
        el('last-name').value = user.last_name || '';
        el('phone').value = user.phone || '';
        el('email').value = user.email || '';
        el('prof-first').value = user.first_name || '';
        el('prof-last').value = user.last_name || '';
        el('prof-phone').value = user.phone || '';
        el('prof-email').value = user.email || '';
        const dash = (v) => (v && String(v).trim() !== '' ? String(v).trim() : '—');
        if (el('view-first')) el('view-first').textContent = dash(user.first_name);
        if (el('view-last')) el('view-last').textContent = dash(user.last_name);
        if (el('view-phone')) el('view-phone').textContent = dash(user.phone);
        if (el('view-email')) el('view-email').textContent = dash(user.email);
    }

    function setProfileEditMode(editing) {
        const view = el('profile-view');
        const form = el('profile-form');
        if (!view || !form) return;
        view.hidden = !!editing;
        form.hidden = !editing;
        if (editing && el('prof-first')) {
            el('prof-first').focus();
        }
    }

    const QUICK_PRESETS = [
        { minutes: 10, label: 'Over 10 min' },
        { minutes: 30, label: 'Over een half uur' },
        { minutes: 60, label: 'Over 1 uur' },
    ];

    /** Zelfde afronding als native defaultPickupDate: volgende 5 minuten. */
    function presetDate(minutesFromNow) {
        const d = new Date(Date.now() + minutesFromNow * 60 * 1000);
        d.setSeconds(0, 0);
        const rem = d.getMinutes() % 5;
        if (rem !== 0) d.setMinutes(d.getMinutes() + (5 - rem));
        return d;
    }

    function snapMinutes(m) {
        return Math.min(55, Math.round(m / 5) * 5);
    }

    function sameMinute(a, b) {
        return a.getFullYear() === b.getFullYear()
            && a.getMonth() === b.getMonth()
            && a.getDate() === b.getDate()
            && a.getHours() === b.getHours()
            && snapMinutes(a.getMinutes()) === snapMinutes(b.getMinutes());
    }

    function clampToFuture(d) {
        const min = presetDate(5);
        return d.getTime() < min.getTime() ? min : d;
    }

    function defaultPickupAt() {
        return toLocalInputValue(presetDate(10));
    }

    function toLocalInputValue(date) {
        const d = date instanceof Date ? date : new Date(date);
        const pad = (n) => String(n).padStart(2, '0');
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
            + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function toPickupAtPayload(value) {
        const local = toLocalInputValue(parseLocalInputValue(value || defaultPickupAt()));
        // Wall-clock zonder Z: backend slaat Europe/Amsterdam-tijd op zoals de klant koos.
        return local.length === 16 ? local + ':00' : local;
    }

    function collectBaggagePayload() {
        const baggage = {};
        const special = {};
        document.querySelectorAll('[data-baggage-key]').forEach(function (node) {
            const key = node.getAttribute('data-baggage-key');
            const qty = parseInt(node.getAttribute('data-qty') || '0', 10) || 0;
            if (!key || qty <= 0) return;
            if (node.getAttribute('data-baggage-special') === '1') {
                special[key] = qty;
            } else {
                baggage[key] = qty;
            }
        });
        return { baggage: baggage, special_baggage: special };
    }

    function bindBaggageSteppers() {
        document.querySelectorAll('[data-baggage-step]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const wrap = btn.closest('[data-baggage-key]');
                if (!wrap) return;
                const delta = parseInt(btn.getAttribute('data-baggage-step') || '0', 10) || 0;
                const max = parseInt(wrap.getAttribute('data-max') || '6', 10) || 6;
                let qty = parseInt(wrap.getAttribute('data-qty') || '0', 10) || 0;
                qty = Math.max(0, Math.min(max, qty + delta));
                wrap.setAttribute('data-qty', String(qty));
                const display = wrap.querySelector('[data-baggage-qty]');
                if (display) display.textContent = String(qty);
                maybeQuote();
            });
        });
    }

    function parseLocalInputValue(value) {
        const raw = String(value || '').trim();
        const m = raw.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/);
        if (!m) return new Date(raw);
        return new Date(
            Number(m[1]), Number(m[2]) - 1, Number(m[3]),
            Number(m[4]), Number(m[5]), 0, 0
        );
    }

    function formatPickupLabel(value) {
        const d = parseLocalInputValue(value);
        if (!(d instanceof Date) || Number.isNaN(d.getTime())) return 'Kies tijd';
        const now = new Date();
        const startToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const startSelected = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        const dayDiff = Math.round((startSelected - startToday) / 86400000);
        const time = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        if (dayDiff === 0) return 'Vandaag ' + time;
        if (dayDiff === 1) return 'Morgen ' + time;
        const day = d.toLocaleDateString('nl-NL', { weekday: 'short', day: 'numeric', month: 'short' });
        return day + ' ' + time;
    }

    function setPickupAtValue(value, options) {
        const opts = options || {};
        const iso = value || defaultPickupAt();
        const input = el('pickup-at');
        const label = el('pickup-at-label');
        if (input) input.value = iso;
        if (label) label.textContent = formatPickupLabel(iso);
        if (!opts.silent) {
            maybeQuote();
        }
    }

    function currentTheme() {
        return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    }

    function mapStylesForTheme() {
        if (currentTheme() === 'light') {
            return [
                { featureType: 'poi', stylers: [{ visibility: 'off' }] },
                { featureType: 'transit', stylers: [{ visibility: 'simplified' }] },
            ];
        }
        return [
            { elementType: 'geometry', stylers: [{ color: '#1c1c1e' }] },
            { elementType: 'labels.text.stroke', stylers: [{ color: '#1c1c1e' }] },
            { elementType: 'labels.text.fill', stylers: [{ color: '#9ca3af' }] },
            { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#2a2a2e' }] },
            { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#111113' }] },
            { featureType: 'poi', stylers: [{ visibility: 'off' }] },
        ];
    }

    function ensurePlacesLibrary() {
        if (window.google && google.maps && google.maps.places) {
            return Promise.resolve();
        }
        if (window.google && google.maps && typeof google.maps.importLibrary === 'function') {
            return google.maps.importLibrary('places').then(function () {});
        }
        return Promise.resolve();
    }

    function loadGoogleMapsSdk() {
        if (window.google && window.google.maps && window.google.maps.Map) {
            state.mapsReady = true;
            return ensurePlacesLibrary();
        }
        if (googleMapsLoadPromise) return googleMapsLoadPromise;
        if (!cfg.googleMapsApiKey) {
            return Promise.reject(new Error('no-key'));
        }
        googleMapsLoadPromise = new Promise(function (resolve, reject) {
            window.__nexaCustomerGoogleMapsReady = function () {
                state.mapsReady = true;
                ensurePlacesLibrary().then(resolve).catch(function () { resolve(); });
            };
            const existing = document.getElementById('nexa-customer-google-maps-sdk');
            if (existing) {
                existing.addEventListener('load', function () {
                    if (window.google && window.google.maps) {
                        state.mapsReady = true;
                        ensurePlacesLibrary().then(resolve).catch(function () { resolve(); });
                    }
                });
                existing.addEventListener('error', function () {
                    googleMapsLoadPromise = null;
                    reject(new Error('maps-load'));
                });
                return;
            }
            const script = document.createElement('script');
            script.id = 'nexa-customer-google-maps-sdk';
            script.async = true;
            script.defer = true;
            script.src = 'https://maps.googleapis.com/maps/api/js?key='
                + encodeURIComponent(cfg.googleMapsApiKey)
                + '&libraries=places,geometry&language=nl'
                + '&callback=__nexaCustomerGoogleMapsReady';
            script.onerror = function () {
                googleMapsLoadPromise = null;
                reject(new Error('maps-load'));
            };
            document.head.appendChild(script);
        });
        return googleMapsLoadPromise;
    }

    function setMapError(msg) {
        const box = el('map-error');
        if (!box) return;
        if (!msg) {
            box.hidden = true;
            box.textContent = '';
            return;
        }
        box.hidden = false;
        box.textContent = msg;
    }

    function mapOptions(center) {
        const opts = {
            center: center,
            zoom: 14,
            disableDefaultUI: true,
            zoomControl: true,
            gestureHandling: 'greedy',
            clickableIcons: false,
            backgroundColor: currentTheme() === 'light' ? '#e2e8f0' : '#0f172a',
        };
        if (cfg.googleMapsMapId) {
            opts.mapId = cfg.googleMapsMapId;
        } else {
            opts.styles = mapStylesForTheme();
        }
        return opts;
    }

    function pickupMarkerIcon() {
        return {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 9,
            fillColor: '#ea580c',
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 2.5,
        };
    }

    function dropoffMarkerIcon() {
        // Klassieke Maps-druppel (bestemming), groen.
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
                '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="40" viewBox="0 0 28 40">' +
                '<path fill="#22c55e" stroke="#ffffff" stroke-width="1.5" ' +
                'd="M14 1C7.4 1 2 6.4 2 13c0 9.1 12 25 12 25s12-15.9 12-25C26 6.4 20.6 1 14 1z"/>' +
                '<circle cx="14" cy="13" r="4.5" fill="#ffffff"/>' +
                '</svg>'
            ),
            scaledSize: new google.maps.Size(28, 40),
            anchor: new google.maps.Point(14, 40),
        };
    }

    async function ensureBookMap() {
        const mapEl = el('map');
        if (!mapEl) return null;
        try {
            await loadGoogleMapsSdk();
        } catch (e) {
            setMapError(e && e.message === 'no-key'
                ? 'Google Maps-sleutel ontbreekt. Controleer de serverconfiguratie.'
                : 'Kaart kon niet worden geladen. Controleer je verbinding.');
            return null;
        }
        setMapError(null);
        const center = state.pickup
            ? { lat: state.pickup.lat, lng: state.pickup.lng }
            : { lat: cfg.centerLat, lng: cfg.centerLng };
        if (!state.map) {
            state.map = new google.maps.Map(mapEl, mapOptions(center));
            state.pickupMarker = new google.maps.Marker({
                map: state.map,
                position: center,
                title: 'Ophalen',
                icon: pickupMarkerIcon(),
                zIndex: 10,
            });
        }
        // Kaart was vaak verborgen bij init → forceer resize zodra zichtbaar.
        requestAnimationFrame(function () {
            if (!state.map || !window.google || !google.maps.event) return;
            google.maps.event.trigger(state.map, 'resize');
            if (state.pickup) {
                state.map.setCenter({ lat: state.pickup.lat, lng: state.pickup.lng });
            }
        });
        return state.map;
    }

    async function ensureLiveMap() {
        const mapEl = el('live-map');
        if (!mapEl) return null;
        try {
            await loadGoogleMapsSdk();
        } catch (e) {
            return null;
        }
        const center = state.pickup
            ? { lat: state.pickup.lat, lng: state.pickup.lng }
            : { lat: cfg.centerLat, lng: cfg.centerLng };
        if (!state.liveMap) {
            state.liveMap = new google.maps.Map(mapEl, mapOptions(center));
        }
        requestAnimationFrame(function () {
            if (state.liveMap && window.google && google.maps.event) {
                google.maps.event.trigger(state.liveMap, 'resize');
            }
        });
        return state.liveMap;
    }

    function liveRidePoints(ride) {
        const pickup = (ride && ride.pickup_lat != null && ride.pickup_lng != null)
            ? { lat: Number(ride.pickup_lat), lng: Number(ride.pickup_lng) }
            : (state.pickup && isFinite(state.pickup.lat) && isFinite(state.pickup.lng)
                ? { lat: state.pickup.lat, lng: state.pickup.lng }
                : null);
        const dropoff = (ride && ride.dropoff_lat != null && ride.dropoff_lng != null)
            ? { lat: Number(ride.dropoff_lat), lng: Number(ride.dropoff_lng) }
            : (state.dropoff && isFinite(state.dropoff.lat) && isFinite(state.dropoff.lng)
                ? { lat: state.dropoff.lat, lng: state.dropoff.lng }
                : null);
        if (pickup && (!isFinite(pickup.lat) || !isFinite(pickup.lng))) return { pickup: null, dropoff: null };
        if (dropoff && (!isFinite(dropoff.lat) || !isFinite(dropoff.lng))) {
            return { pickup: pickup, dropoff: null };
        }
        return { pickup: pickup, dropoff: dropoff };
    }

    function updateLiveEndpointMarkers(pickup, dropoff) {
        if (!state.liveMap || !window.google || !google.maps) return;
        if (pickup) {
            if (!state.livePickupMarker) {
                state.livePickupMarker = new google.maps.Marker({
                    map: state.liveMap,
                    title: 'Ophalen',
                    icon: pickupMarkerIcon(),
                    zIndex: 10,
                });
            }
            state.livePickupMarker.setPosition(pickup);
            state.livePickupMarker.setMap(state.liveMap);
        }
        if (dropoff) {
            if (!state.liveDropoffMarker) {
                state.liveDropoffMarker = new google.maps.Marker({
                    map: state.liveMap,
                    title: 'Bestemming',
                    icon: dropoffMarkerIcon(),
                    zIndex: 11,
                });
            }
            state.liveDropoffMarker.setPosition(dropoff);
            state.liveDropoffMarker.setMap(state.liveMap);
        }
    }

    function drawLiveRoutePath(pathPoints, pickup, dropoff) {
        if (!state.liveMap || !window.google || !google.maps) return false;
        const path = Array.isArray(pathPoints) ? pathPoints.filter(function (p) {
            return p && isFinite(p.lat) && isFinite(p.lng);
        }) : [];
        if (path.length < 2) return false;
        if (!state.liveRouteLine) {
            state.liveRouteLine = new google.maps.Polyline({
                map: state.liveMap,
                geodesic: true,
                strokeColor: '#2563eb',
                strokeOpacity: 0.95,
                strokeWeight: 5,
                zIndex: 4,
            });
        }
        state.liveRouteLine.setPath(path);
        state.liveRouteLine.setMap(state.liveMap);
        const bounds = new google.maps.LatLngBounds();
        path.forEach(function (p) { bounds.extend(p); });
        if (pickup) bounds.extend(pickup);
        if (dropoff) bounds.extend(dropoff);
        state.liveMap.fitBounds(bounds, 64);
        return true;
    }

    function fetchLiveRoute(pickup, dropoff) {
        if (!pickup || !dropoff) return Promise.resolve(null);
        const osrmUrl = 'https://router.project-osrm.org/route/v1/driving/'
            + pickup.lng + ',' + pickup.lat + ';'
            + dropoff.lng + ',' + dropoff.lat
            + '?overview=full&geometries=polyline';
        const osrmPromise = fetch(osrmUrl, { headers: { Accept: 'application/json' } })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (payload) {
                const route = payload && Array.isArray(payload.routes) && payload.routes[0]
                    ? payload.routes[0]
                    : null;
                if (!route || !route.geometry) return null;
                const path = decodeOsrmPolyline(route.geometry);
                return path.length >= 2 ? path : null;
            })
            .catch(function () { return null; });

        return osrmPromise.then(function (path) {
            if (path) return path;
            if (!window.google || !google.maps || !google.maps.DirectionsService) return null;
            const service = new google.maps.DirectionsService();
            return new Promise(function (resolve) {
                service.route({
                    origin: pickup,
                    destination: dropoff,
                    travelMode: google.maps.TravelMode.DRIVING,
                    region: 'nl',
                }, function (result, status) {
                    if (status !== 'OK' || !result || !result.routes || !result.routes[0]) {
                        resolve(null);
                        return;
                    }
                    const overview = result.routes[0].overview_path || [];
                    resolve(overview.map(function (ll) {
                        return {
                            lat: typeof ll.lat === 'function' ? ll.lat() : ll.lat,
                            lng: typeof ll.lng === 'function' ? ll.lng() : ll.lng,
                        };
                    }));
                });
            }).catch(function () { return null; });
        });
    }

    async function syncLiveRoute(ride) {
        const points = liveRidePoints(ride);
        if (!points.pickup) return;
        updateLiveEndpointMarkers(points.pickup, points.dropoff);
        if (!points.dropoff) {
            if (state.liveMap) state.liveMap.panTo(points.pickup);
            return;
        }
        const key = points.pickup.lat.toFixed(5) + ',' + points.pickup.lng.toFixed(5)
            + '>' + points.dropoff.lat.toFixed(5) + ',' + points.dropoff.lng.toFixed(5);
        if (key === state.liveRouteKey && state.liveRouteLine) return;
        const seq = ++state.liveRouteFetchSeq;
        let path = await fetchLiveRoute(points.pickup, points.dropoff);
        if (seq !== state.liveRouteFetchSeq) return;
        if (!path || path.length < 2) {
            path = [points.pickup, points.dropoff];
        }
        if (drawLiveRoutePath(path, points.pickup, points.dropoff)) {
            state.liveRouteKey = key;
        }
    }

    function fleetOrigin() {
        if (state.pickup && isFinite(state.pickup.lat) && isFinite(state.pickup.lng)) {
            return { lat: state.pickup.lat, lng: state.pickup.lng };
        }
        if (state.map && typeof state.map.getCenter === 'function') {
            const c = state.map.getCenter();
            if (c) {
                return {
                    lat: typeof c.lat === 'function' ? c.lat() : c.lat,
                    lng: typeof c.lng === 'function' ? c.lng() : c.lng,
                };
            }
        }
        return { lat: cfg.centerLat, lng: cfg.centerLng };
    }

    function taxiStyleKey(style) {
        const key = String(style || 'sedan').toLowerCase();
        if (key === 'bus' || key === 'minibus' || key === 'coach') return 'bus';
        if (key === 'van' || key === 'busje') return 'van';
        return 'sedan';
    }

    function taxiIconUrl(style) {
        const key = taxiStyleKey(style);
        if (key === 'bus') return cfg.taxiBusUrl;
        if (key === 'van') return cfg.taxiVanUrl;
        return cfg.taxiCarUrl;
    }

    const FLEET_CAR_COLOR = '#f7e125';
    const taxiTintCache = {};
    const taxiAssetCache = {};

    function taxiHexToRgb(hex) {
        let h = String(hex || '').replace('#', '');
        if (h.length === 3) {
            h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
        }
        if (h.length !== 6) return { r: 247, g: 225, b: 37 };
        return {
            r: parseInt(h.slice(0, 2), 16),
            g: parseInt(h.slice(2, 4), 16),
            b: parseInt(h.slice(4, 6), 16),
        };
    }

    function taxiIsMagentaPixel(r, g, b, a) {
        if (a < 16) return false;
        const magenta = (r + b) / 2 - g;
        return magenta > 28 && g < 170 && r > 40 && b > 40;
    }

    function taxiTintSource(source, hex) {
        const canvas = document.createElement('canvas');
        canvas.width = source.naturalWidth || source.width;
        canvas.height = source.naturalHeight || source.height;
        const ctx = canvas.getContext('2d');
        if (!ctx || !canvas.width) return '';
        ctx.drawImage(source, 0, 0);
        const image = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const px = image.data;
        const rgb = taxiHexToRgb(hex);
        for (let i = 0; i < px.length; i += 4) {
            if (px[i + 3] < 16) {
                px[i] = 0;
                px[i + 1] = 0;
                px[i + 2] = 0;
                px[i + 3] = 0;
                continue;
            }
            if (!taxiIsMagentaPixel(px[i], px[i + 1], px[i + 2], px[i + 3])) continue;
            let shade = Math.max(px[i], px[i + 2]) / 255;
            shade = Math.max(0.18, Math.min(1, shade));
            px[i] = Math.round(rgb.r * shade);
            px[i + 1] = Math.round(rgb.g * shade);
            px[i + 2] = Math.round(rgb.b * shade);
        }
        ctx.putImageData(image, 0, 0);
        return canvas.toDataURL('image/png');
    }

    function applyTaxiTint(imgEl, style, color) {
        if (!imgEl) return;
        const key = taxiStyleKey(style) + '|' + String(color || FLEET_CAR_COLOR).toLowerCase();
        if (taxiTintCache[key]) {
            imgEl.src = taxiTintCache[key];
            return;
        }
        const src = taxiIconUrl(style);
        if (!src) return;
        const run = function (asset) {
            if (!asset) return;
            try {
                const url = taxiTintSource(asset, color || FLEET_CAR_COLOR);
                if (url) {
                    taxiTintCache[key] = url;
                    imgEl.src = url;
                }
            } catch (e) { /* keep original */ }
        };
        if (taxiAssetCache[src] && taxiAssetCache[src].complete) {
            run(taxiAssetCache[src]);
            return;
        }
        const img = new Image();
        img.onload = function () {
            taxiAssetCache[src] = img;
            run(img);
        };
        img.onerror = function () {};
        img.src = src;
    }

    function tintedTaxiIconUrl(style, done) {
        const key = taxiStyleKey(style) + '|' + FLEET_CAR_COLOR;
        if (taxiTintCache[key]) {
            done(taxiTintCache[key]);
            return;
        }
        const src = taxiIconUrl(style);
        const finish = function (asset) {
            if (!asset) {
                done(src);
                return;
            }
            try {
                const url = taxiTintSource(asset, FLEET_CAR_COLOR);
                if (url) {
                    taxiTintCache[key] = url;
                    done(url);
                    return;
                }
            } catch (e) {}
            done(src);
        };
        if (taxiAssetCache[src] && taxiAssetCache[src].complete) {
            finish(taxiAssetCache[src]);
            return;
        }
        const img = new Image();
        img.onload = function () {
            taxiAssetCache[src] = img;
            finish(img);
        };
        img.onerror = function () { finish(null); };
        img.src = src;
    }

    function ensureCustomerTaxiOverlayClass() {
        if (CustomerTaxiOverlay || !window.google || !google.maps || !google.maps.OverlayView) {
            return CustomerTaxiOverlay;
        }
        CustomerTaxiOverlay = function (map, taxi, heading, iconUrl) {
            this.taxiId = taxi.id;
            this.position = new google.maps.LatLng(taxi.lat, taxi.lng);
            this.heading = heading || 0;
            this.iconUrl = iconUrl;
            this.carStyle = taxiStyleKey(taxi && taxi.car_style);
            this.setMap(map);
        };
        CustomerTaxiOverlay.prototype = new google.maps.OverlayView();
        CustomerTaxiOverlay.prototype.onAdd = function () {
            this.div = document.createElement('div');
            this.div.className = 'customer-live-taxi';
            const wrap = document.createElement('div');
            wrap.className = 'customer-live-taxi__car'
                + (this.carStyle === 'van' ? ' is-van' : '')
                + (this.carStyle === 'bus' ? ' is-bus' : '');
            const img = document.createElement('img');
            img.src = this.iconUrl;
            img.alt = '';
            img.draggable = false;
            applyTaxiTint(img, this.carStyle, FLEET_CAR_COLOR);
            wrap.appendChild(img);
            this.carEl = wrap;
            this.div.appendChild(wrap);
            const panes = this.getPanes();
            if (panes && panes.overlayMouseTarget) {
                panes.overlayMouseTarget.appendChild(this.div);
            }
        };
        CustomerTaxiOverlay.prototype.draw = function () {
            if (!this.div) return;
            const proj = this.getProjection();
            if (!proj) return;
            const point = proj.fromLatLngToDivPixel(this.position);
            if (!point) return;
            this.div.style.left = point.x + 'px';
            this.div.style.top = point.y + 'px';
            if (this.carEl) {
                this.carEl.style.transform = 'rotate(' + this.heading + 'deg)';
            }
        };
        CustomerTaxiOverlay.prototype.onRemove = function () {
            if (this.div && this.div.parentNode) {
                this.div.parentNode.removeChild(this.div);
            }
            this.div = null;
            this.carEl = null;
        };
        CustomerTaxiOverlay.prototype.updateTaxi = function (taxi, heading) {
            this.position = new google.maps.LatLng(taxi.lat, taxi.lng);
            this.heading = heading || this.heading || 0;
            this.draw();
        };
        return CustomerTaxiOverlay;
    }

    function headingBetween(a, b) {
        if (!a || !b) return 0;
        const dLng = (b.lng - a.lng) * Math.PI / 180;
        const lat1 = a.lat * Math.PI / 180;
        const lat2 = b.lat * Math.PI / 180;
        const y = Math.sin(dLng) * Math.cos(lat2);
        const x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLng);
        return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
    }

    function updateFleetRadiusCircle() {
        if (!state.map || !window.google || !google.maps) return;
        const origin = fleetOrigin();
        if (!origin) return;
        if (!state.fleetRadiusCircle) {
            state.fleetRadiusCircle = new google.maps.Circle({
                map: state.map,
                strokeColor: '#2563eb',
                strokeOpacity: 0.45,
                strokeWeight: 1.5,
                fillColor: '#2563eb',
                fillOpacity: 0.08,
                clickable: false,
                zIndex: 1,
            });
        }
        state.fleetRadiusCircle.setCenter(origin);
        state.fleetRadiusCircle.setRadius(cfg.fleetRadiusKm * 1000);
        state.fleetRadiusCircle.setMap(state.map);
    }

    function maybeFitFleetViewport(vehicles) {
        if (state.fleetDidFit || !state.map || !window.google || !google.maps) return;
        const list = Array.isArray(vehicles) ? vehicles : [];
        if (!list.length) return;
        if (state.dropoff) {
            // Route staat al op de kaart: alleen opnieuw kaderen zodat de dichtstbijzijnde taxi zichtbaar is.
            state.fleetDidFit = true;
            refitBookRouteBounds();
            return;
        }
        const bounds = new google.maps.LatLngBounds();
        const origin = fleetOrigin();
        bounds.extend(origin);
        list.slice(0, 12).forEach(function (taxi) {
            const lat = Number(taxi.lat);
            const lng = Number(taxi.lng);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                bounds.extend({ lat: lat, lng: lng });
            }
        });
        state.fleetDidFit = true;
        state.map.fitBounds(bounds, 48);
        google.maps.event.addListenerOnce(state.map, 'idle', function () {
            if (!state.map) return;
            const z = state.map.getZoom();
            if (z != null && z > 15) state.map.setZoom(15);
            if (z != null && z < 11) state.map.setZoom(11);
        });
    }

    function clearFleetOverlays() {
        Object.keys(state.taxiOverlays).forEach(function (id) {
            if (state.taxiOverlays[id]) {
                state.taxiOverlays[id].setMap(null);
            }
            delete state.taxiOverlays[id];
        });
        Object.keys(state.taxiMarkers).forEach(function (id) {
            if (state.taxiMarkers[id] && state.taxiMarkers[id].setMap) {
                state.taxiMarkers[id].setMap(null);
            }
            delete state.taxiMarkers[id];
        });
        state.taxiPrev = {};
    }

    function upsertFleet(vehicles) {
        if (isFollowingAssignedTaxi()) return;
        if (!state.map || !window.google || !google.maps) return;
        const Overlay = ensureCustomerTaxiOverlayClass();
        const next = {};
        const list = Array.isArray(vehicles) ? vehicles : [];
        list.forEach(function (taxi) {
            if (!taxi || !taxi.id) return;
            const lat = Number(taxi.lat);
            const lng = Number(taxi.lng);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            const pos = { lat: lat, lng: lng };
            next[taxi.id] = true;
            const prev = state.taxiPrev[taxi.id];
            const heading = Number.isFinite(Number(taxi.heading))
                ? Number(taxi.heading)
                : headingBetween(prev, pos);
            state.taxiPrev[taxi.id] = pos;
            if (Overlay) {
                if (state.taxiOverlays[taxi.id]) {
                    state.taxiOverlays[taxi.id].updateTaxi(taxi, heading);
                } else {
                    state.taxiOverlays[taxi.id] = new Overlay(
                        state.map,
                        taxi,
                        heading,
                        taxiIconUrl(taxi.car_style)
                    );
                }
            } else {
                // Fallback als OverlayView nog niet beschikbaar is.
                if (state.taxiMarkers[taxi.id]) {
                    state.taxiMarkers[taxi.id].setPosition(pos);
                } else {
                    const style = taxiStyleKey(taxi.car_style);
                    const size = style === 'bus'
                        ? { w: 34, h: 56 }
                        : (style === 'van' ? { w: 30, h: 42 } : { w: 28, h: 38 });
                    const marker = new google.maps.Marker({
                        map: state.map,
                        position: pos,
                        icon: {
                            url: taxiIconUrl(taxi.car_style),
                            scaledSize: new google.maps.Size(size.w, size.h),
                            anchor: new google.maps.Point(size.w / 2, size.h / 2),
                        },
                        title: 'Beschikbare taxi',
                        zIndex: 5,
                    });
                    state.taxiMarkers[taxi.id] = marker;
                    tintedTaxiIconUrl(taxi.car_style, function (url) {
                        if (!state.taxiMarkers[taxi.id]) return;
                        marker.setIcon({
                            url: url,
                            scaledSize: new google.maps.Size(size.w, size.h),
                            anchor: new google.maps.Point(size.w / 2, size.h / 2),
                        });
                    });
                }
            }
        });
        Object.keys(state.taxiOverlays).forEach(function (id) {
            if (!next[id]) {
                state.taxiOverlays[id].setMap(null);
                delete state.taxiOverlays[id];
                delete state.taxiPrev[id];
            }
        });
        Object.keys(state.taxiMarkers).forEach(function (id) {
            if (!next[id]) {
                state.taxiMarkers[id].setMap(null);
                delete state.taxiMarkers[id];
                delete state.taxiPrev[id];
            }
        });
        const count = Object.keys(next).length;
        state.nearestTaxi = null;
        for (let i = 0; i < list.length; i++) {
            const lat = Number(list[i] && list[i].lat);
            const lng = Number(list[i] && list[i].lng);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                state.nearestTaxi = { lat: lat, lng: lng };
                break;
            }
        }
        const status = el('map-fleet-status');
        if (status) {
            status.textContent = count === 0
                ? 'Geen taxi’s online'
                : (count === 1 ? '1 taxi online' : count + ' taxi’s online');
        }
        updateFleetRadiusCircle();
        maybeFitFleetViewport(list);
        const countChanged = state.fleetCount !== null && state.fleetCount !== count;
        state.fleetCount = count;
        if (countChanged && state.pickup && state.dropoff) {
            maybeQuote();
        }
    }

    async function fetchNearbyTaxis() {
        if (isFollowingAssignedTaxi()) return;
        if (!cfg.nearbyTaxisUrl) return;
        const origin = fleetOrigin();
        const params = new URLSearchParams({
            section_key: cfg.marketplaceSectionKey,
            radius_km: String(cfg.fleetRadiusKm),
            lat: String(origin.lat),
            lng: String(origin.lng),
        });
        try {
            const res = await fetch(cfg.nearbyTaxisUrl + '?' + params.toString(), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();
            upsertFleet(data && data.vehicles);
        } catch (e) {
            /* laat laatste posities staan */
        }
    }

    function startFleetPolling() {
        if (isFollowingAssignedTaxi()) {
            syncBookFollow(state.liveRide);
            return;
        }
        stopFleetPolling();
        const status = el('map-fleet-status');
        if (status) status.textContent = 'Taxi’s zoeken…';
        fetchNearbyTaxis();
        state.fleetTimer = setInterval(fetchNearbyTaxis, FLEET_POLL_MS);
    }

    function stopFleetPolling() {
        if (state.fleetTimer) clearInterval(state.fleetTimer);
        state.fleetTimer = null;
    }

    function clearRouteLine() {
        if (state.routeLine) {
            state.routeLine.setMap(null);
            state.routeLine = null;
        }
        state.routeMetricsCache = null;
    }

    function decodeOsrmPolyline(encoded) {
        const str = String(encoded || '');
        if (!str) return [];
        if (window.google && google.maps && google.maps.geometry && google.maps.geometry.encoding) {
            return google.maps.geometry.encoding.decodePath(str).map(function (ll) {
                return {
                    lat: typeof ll.lat === 'function' ? ll.lat() : ll.lat,
                    lng: typeof ll.lng === 'function' ? ll.lng() : ll.lng,
                };
            });
        }
        let index = 0;
        let lat = 0;
        let lng = 0;
        const coordinates = [];
        while (index < str.length) {
            let result = 0;
            let shift = 0;
            let b;
            do {
                b = str.charCodeAt(index++) - 63;
                result |= (b & 0x1f) << shift;
                shift += 5;
            } while (b >= 0x20);
            const dlat = (result & 1) ? ~(result >> 1) : (result >> 1);
            lat += dlat;
            result = 0;
            shift = 0;
            do {
                b = str.charCodeAt(index++) - 63;
                result |= (b & 0x1f) << shift;
                shift += 5;
            } while (b >= 0x20);
            const dlng = (result & 1) ? ~(result >> 1) : (result >> 1);
            lng += dlng;
            coordinates.push({ lat: lat / 1e5, lng: lng / 1e5 });
        }
        return coordinates;
    }

    function drawRoutePath(pathPoints) {
        if (!state.map || !window.google || !google.maps) return false;
        const path = Array.isArray(pathPoints) ? pathPoints.filter(function (p) {
            return p && isFinite(p.lat) && isFinite(p.lng);
        }) : [];
        if (path.length < 2) return false;
        if (!state.routeLine) {
            state.routeLine = new google.maps.Polyline({
                map: state.map,
                geodesic: true,
                strokeColor: '#2563eb',
                strokeOpacity: 0.95,
                strokeWeight: 5,
                zIndex: 4,
            });
        }
        state.routeLine.setPath(path);
        state.routeLine.setMap(state.map);
        const bounds = new google.maps.LatLngBounds();
        path.forEach(function (p) { bounds.extend(p); });
        if (state.pickup) bounds.extend({ lat: state.pickup.lat, lng: state.pickup.lng });
        if (state.dropoff) bounds.extend({ lat: state.dropoff.lat, lng: state.dropoff.lng });
        if (state.nearestTaxi) bounds.extend(state.nearestTaxi);
        state.map.fitBounds(bounds, 56);
        syncMapFitButton();
        return true;
    }

    function fetchRouteViaOsrm() {
        if (!state.pickup || !state.dropoff) return Promise.resolve(null);
        const url = 'https://router.project-osrm.org/route/v1/driving/'
            + state.pickup.lng + ',' + state.pickup.lat + ';'
            + state.dropoff.lng + ',' + state.dropoff.lat
            + '?overview=full&geometries=polyline';
        return fetch(url, { headers: { Accept: 'application/json' } })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (payload) {
                const route = payload && Array.isArray(payload.routes) && payload.routes[0]
                    ? payload.routes[0]
                    : null;
                if (!route || !route.geometry) return null;
                return {
                    path: decodeOsrmPolyline(route.geometry),
                    distance_meters: Math.max(50, Math.round(Number(route.distance) || 0)),
                    duration_seconds: Math.max(60, Math.round(Number(route.duration) || 0)),
                };
            })
            .catch(function () { return null; });
    }

    function fetchRouteViaGoogleDirections() {
        if (!state.pickup || !state.dropoff || !window.google || !google.maps || !google.maps.DirectionsService) {
            return Promise.resolve(null);
        }
        const service = new google.maps.DirectionsService();
        return new Promise(function (resolve) {
            service.route({
                origin: { lat: state.pickup.lat, lng: state.pickup.lng },
                destination: { lat: state.dropoff.lat, lng: state.dropoff.lng },
                travelMode: google.maps.TravelMode.DRIVING,
                region: 'nl',
            }, function (result, status) {
                if (status !== 'OK' || !result || !result.routes || !result.routes[0]) {
                    resolve(null);
                    return;
                }
                const route = result.routes[0];
                const leg = route.legs && route.legs[0] ? route.legs[0] : null;
                const path = (route.overview_path || []).map(function (ll) {
                    return {
                        lat: typeof ll.lat === 'function' ? ll.lat() : ll.lat,
                        lng: typeof ll.lng === 'function' ? ll.lng() : ll.lng,
                    };
                });
                resolve({
                    path: path,
                    distance_meters: Math.max(50, Math.round(leg && leg.distance ? leg.distance.value : 0)),
                    duration_seconds: Math.max(60, Math.round(leg && leg.duration ? leg.duration.value : 0)),
                });
            });
        }).catch(function () { return null; });
    }

    async function refreshBookRoute() {
        if (!state.map || !state.pickup || !state.dropoff) {
            clearRouteLine();
            return null;
        }
        const seq = ++state.routeFetchSeq;
        let route = await fetchRouteViaOsrm();
        if (!route) route = await fetchRouteViaGoogleDirections();
        if (seq !== state.routeFetchSeq) return null;
        if (!route || !route.path || route.path.length < 2) {
            // Fallback: rechte lijn zodat de kaart wél een route toont.
            const fallbackPath = [
                { lat: state.pickup.lat, lng: state.pickup.lng },
                { lat: state.dropoff.lat, lng: state.dropoff.lng },
            ];
            drawRoutePath(fallbackPath);
            state.routeMetricsCache = null;
            pulseMapRouteReveal();
            return null;
        }
        drawRoutePath(route.path);
        state.routeMetricsCache = {
            distance_meters: route.distance_meters,
            duration_seconds: route.duration_seconds,
        };
        pulseMapRouteReveal();
        return state.routeMetricsCache;
    }

    /** Direct naar de kaart scrollen (bij adresklik), keyboard dicht. */
    function scrollBookToMap() {
        const screen = el('screen-book');
        if (!screen || screen.hidden) return;
        const content = screen.querySelector('.content');
        const mapWrap = screen.querySelector('.map-wrap');

        const ae = document.activeElement;
        if (ae && typeof ae.blur === 'function' && screen.contains(ae)) {
            ae.blur();
        }
        syncTabbarKeyboardVisibility();

        function jumpToMap() {
            // Kaart staat bovenaan in .content — dat is de scrollcontainer.
            if (!content) return;
            try {
                content.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (e) {
                content.scrollTop = 0;
            }
        }

        jumpToMap();
        clearTimeout(scrollBookToMap._t1);
        clearTimeout(scrollBookToMap._t2);
        // Na keyboard-dicht / layout-shift: opnieuw, forceer als smooth niet greep.
        scrollBookToMap._t1 = setTimeout(function () {
            jumpToMap();
            if (content && content.scrollTop > 16) content.scrollTop = 0;
        }, 100);
        scrollBookToMap._t2 = setTimeout(function () {
            if (content && content.scrollTop > 16) content.scrollTop = 0;
            if (state.map && window.google && google.maps) {
                try { google.maps.event.trigger(state.map, 'resize'); } catch (e) {}
            }
        }, 350);
    }

    /** Korte highlight + map refit nadat de route is getekend. */
    function pulseMapRouteReveal() {
        const screen = el('screen-book');
        if (!screen || screen.hidden) return;
        const mapWrap = screen.querySelector('.map-wrap');
        if (!mapWrap) return;

        mapWrap.classList.remove('map-wrap--route-reveal');
        void mapWrap.offsetWidth;
        mapWrap.classList.add('map-wrap--route-reveal');
        clearTimeout(pulseMapRouteReveal._timer);
        pulseMapRouteReveal._timer = setTimeout(function () {
            mapWrap.classList.remove('map-wrap--route-reveal');
        }, 1400);

        clearTimeout(pulseMapRouteReveal._refitTimer);
        pulseMapRouteReveal._refitTimer = setTimeout(function () {
            if (!state.map || !window.google || !google.maps) return;
            try {
                google.maps.event.trigger(state.map, 'resize');
            } catch (e) {}
            refitBookRouteBounds();
        }, 380);
    }

    function revealBookRouteOnMap() {
        scrollBookToMap();
        pulseMapRouteReveal();
    }

    function refitBookRouteBounds() {
        if (!state.map || !state.routeLine || !window.google || !google.maps) return;
        const path = state.routeLine.getPath();
        if (!path || typeof path.getLength !== 'function' || path.getLength() < 2) return;
        const bounds = new google.maps.LatLngBounds();
        path.forEach(function (ll) { bounds.extend(ll); });
        if (state.pickup) bounds.extend({ lat: state.pickup.lat, lng: state.pickup.lng });
        if (state.dropoff) bounds.extend({ lat: state.dropoff.lat, lng: state.dropoff.lng });
        if (state.nearestTaxi) bounds.extend(state.nearestTaxi);
        state.map.fitBounds(bounds, 56);
    }

    function updateMapMarkers() {
        if (!state.map) return;
        if (state.pickup) {
            const p = { lat: state.pickup.lat, lng: state.pickup.lng };
            if (!state.pickupMarker) {
                state.pickupMarker = new google.maps.Marker({
                    map: state.map,
                    title: 'Ophalen',
                    icon: pickupMarkerIcon(),
                    zIndex: 10,
                });
            } else {
                state.pickupMarker.setIcon(pickupMarkerIcon());
            }
            state.pickupMarker.setPosition(p);
            state.pickupMarker.setMap(state.map);
        } else if (state.pickupMarker) {
            state.pickupMarker.setMap(null);
        }
        if (state.dropoff) {
            const d = { lat: state.dropoff.lat, lng: state.dropoff.lng };
            if (!state.dropoffMarker) {
                state.dropoffMarker = new google.maps.Marker({
                    map: state.map,
                    title: 'Bestemming',
                    icon: dropoffMarkerIcon(),
                    zIndex: 11,
                });
            } else {
                state.dropoffMarker.setIcon(dropoffMarkerIcon());
            }
            state.dropoffMarker.setPosition(d);
            state.dropoffMarker.setMap(state.map);
        } else if (state.dropoffMarker) {
            state.dropoffMarker.setMap(null);
        }

        if (state.pickup && state.dropoff) {
            return refreshBookRoute();
        }

        clearRouteLine();
        if (state.pickup) {
            state.map.panTo({ lat: state.pickup.lat, lng: state.pickup.lng });
            state.map.setZoom(14);
        }
        return Promise.resolve(null);
    }

    async function detectLocation() {
        hidePickupLocationNotice();
        el('pickup').value = 'Locatie bepalen…';
        el('pickup-hint').textContent = 'Even geduld…';
        syncAddressClearButton('pickup');
        if (!navigator.geolocation) {
            el('pickup').value = '';
            el('pickup').readOnly = false;
            syncAddressClearButton('pickup');
            showPickupLocationNotice({
                code: 0,
                message: 'Je browser ondersteunt geen locatiebepaling. Vul je ophaaladres handmatig in.',
                canRetry: false,
            });
            return;
        }
        try {
            const pos = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 10000,
                });
            });
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            state.pickup = { lat: lat, lng: lng, address: '' };
            state.fleetDidFit = false;
            state.nearestTaxi = null;
            updateMapMarkers();
            updateFleetRadiusCircle();
            fetchNearbyTaxis();
            let address = '';
            try {
                const res = await fetch(
                    cfg.addressSearchUrl + '?lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng),
                    { headers: { Accept: 'application/json' } }
                );
                if (res.ok) {
                    const data = await res.json();
                    address = formatNominatimRowAddress(data);
                }
            } catch (e) {}
            if (!address) {
                address = await reverseGeocodeGoogle(lat, lng);
            }
            if (!address) {
                address = lat.toFixed(5) + ', ' + lng.toFixed(5);
            }
            state.pickup.address = address;
            el('pickup').value = address;
            el('pickup-hint').textContent = 'Automatisch gedetecteerd · tik om te wijzigen';
            el('pickup').readOnly = false;
            syncAddressClearButton('pickup');
            hidePickupLocationNotice();
            maybeQuote();
        } catch (e) {
            el('pickup').value = '';
            el('pickup').readOnly = false;
            syncAddressClearButton('pickup');
            const code = Number(e && e.code) || 0;
            showPickupLocationNotice({
                code: code,
                message: e && e.message ? String(e.message) : '',
                canRetry: true,
            });
            fetchNearbyTaxis();
        }
    }

    function browserLocationHelp() {
        return 'Zet de locatievoorzieningen aan voor je browser, zodat we je ophaallocatie automatisch kunnen uitlezen. '
            + 'Daarna kun je opnieuw proberen, of vul je ophaaladres handmatig in.';
    }

    async function queryGeolocationPermissionState() {
        try {
            if (!navigator.permissions || !navigator.permissions.query) return null;
            const status = await navigator.permissions.query({ name: 'geolocation' });
            return status && status.state ? String(status.state) : null;
        } catch (e) {
            return null;
        }
    }

    function hidePickupLocationNotice() {
        const notice = el('pickup-location-notice');
        const help = el('pickup-location-notice-help');
        const hint = el('pickup-hint');
        if (notice) notice.hidden = true;
        if (help) {
            help.hidden = true;
            help.textContent = '';
            help.innerHTML = '';
        }
        if (hint && hint.hidden) hint.hidden = false;
    }

    async function showPickupLocationNotice(options) {
        const opts = options || {};
        const code = Number(opts.code) || 0;
        const notice = el('pickup-location-notice');
        const textEl = el('pickup-location-notice-text');
        const helpEl = el('pickup-location-notice-help');
        const retryBtn = el('pickup-location-retry');
        const hint = el('pickup-hint');
        if (!notice || !textEl) return;

        const perm = await queryGeolocationPermissionState();
        const denied = code === 1 || perm === 'denied';
        const unavailable = code === 2;
        const timeout = code === 3;

        let title = 'Kon je locatie niet bepalen. Vul je ophaaladres handmatig in, of zet locatievoorzieningen aan voor je browser.';
        if (!navigator.geolocation || code === 0 && !opts.canRetry) {
            title = 'Locatiebepaling is niet beschikbaar in deze browser. Vul je ophaaladres handmatig in.';
        } else if (denied) {
            title = 'Locatievoorzieningen staan uit of zijn geblokkeerd voor deze browser. Zet ze aan om je ophaallocatie automatisch uit te lezen.';
        } else if (timeout) {
            title = 'Locatie bepalen duurde te lang. Probeer opnieuw, of vul je ophaaladres handmatig in.';
        } else if (unavailable) {
            title = 'Je locatie kon niet worden uitgelezen. Zet locatievoorzieningen aan voor je browser.';
        }

        textEl.textContent = title;
        if (hint) {
            hint.textContent = denied
                ? 'Locatievoorzieningen uit · vul handmatig in of zet aan voor je browser'
                : 'Kon locatie niet bepalen. Vul je ophaaladres in.';
            hint.hidden = true;
        }

        if (retryBtn) {
            retryBtn.hidden = opts.canRetry === false;
            retryBtn.textContent = denied ? 'Opnieuw toestaan' : 'Opnieuw proberen';
        }

        if (helpEl) {
            helpEl.textContent = browserLocationHelp();
            helpEl.hidden = false;
        }

        notice.hidden = false;
        toast(denied
            ? 'Zet locatievoorzieningen aan voor je browser.'
            : 'Kon locatie niet bepalen. Vul je ophaaladres in.');
    }

    function bindPickupLocationNotice() {
        const retryBtn = el('pickup-location-retry');
        if (!retryBtn || retryBtn.dataset.bound === '1') return;
        retryBtn.dataset.bound = '1';
        retryBtn.addEventListener('click', function () {
            detectLocation();
        });
    }

    function normalizeAddressQuery(query) {
        let s = String(query || '').trim();
        if (!s) return s;
        s = s.replace(/\btreinstations?\b/gi, 'station');
        s = s.replace(/\btrein\s+station\b/gi, 'station');
        s = s.replace(/\bns\s+station\b/gi, 'station');
        return s.replace(/\s+/g, ' ').trim();
    }

    function queryWantsStation(query) {
        const q = String(query || '').toLowerCase();
        return /\bstation\b/.test(q) || /\bcentraal\b/.test(q) || /\bcs\b/.test(q);
    }

    function isStationTypes(types) {
        return (Array.isArray(types) ? types : []).some(function (t) {
            return /^(train_station|transit_station|subway_station|light_rail_station|bus_station)$/i.test(String(t));
        });
    }

    function ensureStationLabel(name) {
        const n = String(name || '').trim();
        if (!n) return n;
        if (/^station\b/i.test(n)) return n;
        return 'Station ' + n;
    }

    function formatGooglePredictionItem(item, query) {
        const description = String((item && item.description) || '').trim();
        const main = String((item && item.structured_formatting && item.structured_formatting.main_text) || '').trim();
        const secondary = String((item && item.structured_formatting && item.structured_formatting.secondary_text) || '').trim();
        const types = Array.isArray(item && item.types) ? item.types : [];
        const station = isStationTypes(types);
        let label = description;
        if (station) {
            const name = ensureStationLabel(main || description.split(',')[0] || description);
            label = secondary ? (name + ', ' + secondary) : name;
        } else if (queryWantsStation(query) && main && !/station/i.test(description)) {
            // Google toont soms alleen de plaatsnaam voor een stationshit.
            // Houd description als fallback-adres, maar maak het label duidelijker als types ontbreken.
            label = description;
        }
        return {
            label: label,
            address: label,
            place_id: (item && item.place_id) || '',
            types: types,
            is_station: station,
            lat: null,
            lng: null,
        };
    }

    function isNlPostcodeToken(value) {
        return /^\d{4}\s*[A-Z]{2}$/i.test(String(value || '').trim());
    }

    function cityFromNominatimRow(row) {
        const a = row && row.address && typeof row.address === 'object' ? row.address : null;
        const fromAddress = a
            ? String(a.city || a.town || a.village || a.municipality || '').trim()
            : '';
        if (fromAddress) return fromAddress;
        const parts = String((row && row.display_name) || '')
            .split(',')
            .map(function (p) { return p.trim(); })
            .filter(function (p) {
                return p && !isNlPostcodeToken(p)
                    && !/^(nederland|netherlands|overijssel|gelderland|noord-holland|zuid-holland|utrecht|limburg|noord-brabant|friesland|groningen|drenthe|zeeland|flevoland)$/i.test(p);
            });
        return parts.length >= 2 ? parts[parts.length - 1] : '';
    }

    function streetPartsFromNominatimRow(row) {
        const displayName = String((row && row.display_name) || '').trim();
        const poi = String((row && row.name) || '').trim().toLowerCase();
        const a = row && row.address && typeof row.address === 'object' ? row.address : null;
        let street = a
            ? String(a.road || a.pedestrian || a.residential || a.footway || a.cycleway || a.path || a.railway || '').trim()
            : '';
        let number = a ? String(a.house_number || '').trim() : '';
        if (street) return { street: street, number: number };

        const parts = displayName.split(',').map(function (p) { return p.trim(); }).filter(Boolean)
            .filter(function (p) { return !poi || p.toLowerCase() !== poi; });
        if (!parts.length) return { street: '', number: number };
        if (/^\d+[a-zA-Z\-]*$/.test(parts[0]) && parts[1] && !isNlPostcodeToken(parts[1])) {
            return { street: parts[1], number: parts[0] };
        }
        const head = parts[0] || '';
        if (head && !isNlPostcodeToken(head) && !/^\d+[a-zA-Z\-]*$/.test(head)) {
            const withNr = head.match(/^(.*\D)\s+(\d+[a-zA-Z\-]*)$/);
            if (withNr && withNr[1] && withNr[1].trim()) {
                return { street: withNr[1].trim(), number: number || withNr[2] };
            }
            if (!number && parts[1] && /^\d+[a-zA-Z\-]*$/.test(parts[1])) {
                return { street: head, number: parts[1] };
            }
            return { street: head, number: number };
        }
        const streetPart = parts.find(function (p) {
            return p && !isNlPostcodeToken(p) && !/^\d+[a-zA-Z\-]*$/.test(p)
                && !/^(nederland|netherlands|overijssel|gelderland)$/i.test(p);
        });
        if (!number) {
            const nrPart = parts.find(function (p) { return /^\d+[a-zA-Z\-]*$/.test(p); });
            if (nrPart) number = nrPart;
        }
        return { street: streetPart || '', number: number };
    }

    function formatNominatimPredictionItem(row) {
        if (!row) return null;
        const placeType = String(row.type || row.addresstype || '').toLowerCase();
        const category = String(row.category || row.class || '').toLowerCase();
        if (placeType === 'postcode') return null;
        const parts = streetPartsFromNominatimRow(row);
        const poiName = String(row.name || '').trim();
        if (!parts.street && (!poiName || isNlPostcodeToken(poiName))) return null;
        const label = formatNominatimRowAddress(row);
        if (!label || isNlPostcodeToken(label.split(',')[0])) return null;
        const isStation = category === 'railway' || placeType === 'station' || placeType === 'halt';
        const lat = row.lat != null ? parseFloat(row.lat) : NaN;
        const lng = row.lon != null ? parseFloat(row.lon) : NaN;
        return {
            label: label,
            address: label,
            place_id: '',
            types: isStation ? ['train_station'] : [],
            is_station: isStation,
            lat: isFinite(lat) ? lat : null,
            lng: isFinite(lng) ? lng : null,
        };
    }

    /**
     * Nominatim zet huisnummer vaak vooraan ("155, Deurningerstraat, …").
     * NL-weergave: straat + huisnummer ("Deurningerstraat 155, …").
     */
    function formatNominatimRowAddress(row) {
        if (!row || typeof row !== 'object') return '';
        const displayName = String(row.display_name || '').trim();
        const poiName = String(row.name || '').trim();
        const a = row.address && typeof row.address === 'object' ? row.address : null;
        const parts = streetPartsFromNominatimRow(row);
        const street = parts.street;
        const number = parts.number;
        const city = cityFromNominatimRow(row);
        const postcode = a ? (a.postcode || '') : '';
        const addressType = String(row.addresstype || '').toLowerCase();
        const category = String(row.category || row.class || '').toLowerCase();
        const placeType = String(row.type || '').toLowerCase();
        const isStreetAddress = !!number || addressType === 'road' || addressType === 'house'
            || addressType === 'house_number' || category === 'highway';
        const streetPart = [street, number].filter(Boolean).join(' ').trim();
        let lead;
        if (category === 'railway' || placeType === 'station' || placeType === 'halt') {
            lead = poiName
                ? (/^station\b/i.test(poiName) ? poiName : ('Station ' + poiName))
                : (streetPart || displayName);
        } else if (poiName && !isNlPostcodeToken(poiName) && !isStreetAddress
            && poiName.toLowerCase() !== String(street).toLowerCase()) {
            lead = streetPart ? (poiName + ', ' + streetPart) : poiName;
        } else {
            lead = streetPart || (!isNlPostcodeToken(poiName) ? poiName : '');
        }
        if (!lead || isNlPostcodeToken(lead)) return '';
        if (city && lead.toLowerCase().indexOf(String(city).toLowerCase()) < 0) {
            return lead + ', ' + city;
        }
        if (postcode && !city) {
            return lead + ', ' + postcode;
        }
        return lead;
    }

    /** Zet "155, Straatnaam, …" om naar "Straatnaam 155, …". */
    function normalizeNlAddressLabel(raw) {
        const text = String(raw || '').trim();
        if (!text) return '';
        const parts = text.split(',').map(function (p) { return p.trim(); }).filter(Boolean);
        if (!parts.length) return text;
        let first = parts[0] || '';
        const second = parts[1] || '';
        if (/^\d+[a-zA-Z\-]*$/.test(first) && second && !/^\d/.test(second)) {
            first = second + ' ' + first;
            parts[0] = first;
            parts.splice(1, 1);
        } else if (second && /^\d+[a-zA-Z\-]*$/.test(second) && first && !/\d/.test(first)) {
            first = first + ' ' + second;
            parts[0] = first;
            parts.splice(1, 1);
        }
        return parts.join(', ');
    }

    function formatGoogleGeocodeAddress(result) {
        if (!result) return '';
        const components = result.address_components || [];
        const byType = {};
        components.forEach(function (c) {
            (c.types || []).forEach(function (t) {
                if (!byType[t]) byType[t] = c.long_name;
            });
        });
        const street = byType.route || byType.pedestrian || '';
        const number = byType.street_number || '';
        const streetPart = [street, number].filter(Boolean).join(' ').trim();
        const postcode = byType.postal_code || '';
        const city = byType.locality || byType.postal_town || byType.administrative_area_level_2 || '';
        const second = [postcode, city].filter(Boolean).join(' ').trim();
        const value = [streetPart, second].filter(Boolean).join(', ').trim();
        return value || normalizeNlAddressLabel(result.formatted_address || '');
    }

    function mergeAddressSuggestions(googleItems, nominatimItems, query, businessItems) {
        const wantsStation = queryWantsStation(query);
        const out = [];
        const seen = {};
        function keyOf(item) {
            return String((item && (item.place_id || item.label)) || '').toLowerCase();
        }
        function push(item) {
            if (!item || !item.label) return;
            const key = keyOf(item);
            if (!key || seen[key]) return;
            seen[key] = true;
            out.push(item);
        }

        const google = Array.isArray(googleItems) ? googleItems : [];
        const nomi = Array.isArray(nominatimItems) ? nominatimItems : [];

        const businesses = Array.isArray(businessItems) ? businessItems : [];
        businesses.forEach(push);
        if (wantsStation) {
            google.filter(function (i) { return i.is_station; }).forEach(push);
            nomi.filter(function (i) { return i.is_station; }).forEach(push);
            google.forEach(push);
            nomi.forEach(push);
        } else {
            google.forEach(push);
            nomi.forEach(push);
        }
        return out.slice(0, 8);
    }

    function reverseGeocodeGoogle(lat, lng) {
        return loadGoogleMapsSdk().then(function () {
            return new Promise(function (resolve) {
                if (!window.google || !google.maps || !google.maps.Geocoder) {
                    resolve('');
                    return;
                }
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: { lat: lat, lng: lng } }, function (results, status) {
                    if (status !== 'OK' || !Array.isArray(results) || !results[0]) {
                        resolve('');
                        return;
                    }
                    resolve(formatGoogleGeocodeAddress(results[0]));
                });
            });
        }).catch(function () { return ''; });
    }

    function rememberAddressCache(key, items) {
        const keys = Object.keys(state.addressCache);
        if (keys.length >= ADDRESS_CACHE_MAX) {
            delete state.addressCache[keys[0]];
        }
        state.addressCache[key] = items;
    }

    function fetchGoogleAddressPredictions(query, options) {
        const opts = options || {};
        if (!cfg.googleMapsApiKey) return Promise.resolve([]);
        return loadGoogleMapsSdk().then(function () {
            if (!window.google || !google.maps || !google.maps.places || !google.maps.places.AutocompleteService) {
                return [];
            }
            if (!state.placesService) {
                state.placesService = new google.maps.places.AutocompleteService();
            }
            // Bij stationszoekopdracht expliciet "Station …" meegeven zodat Google
            // het treinstation teruggeeft i.p.v. alleen de plaatsnaam.
            let input = String(query || '').trim();
            if (!opts.types && queryWantsStation(input) && !/^station\b/i.test(input)) {
                input = 'Station ' + input.replace(/\bstation\b/gi, ' ').replace(/\s+/g, ' ').trim();
            }
            const request = {
                input: input,
                language: 'nl',
                componentRestrictions: { country: 'nl' },
            };
            if (opts.types) {
                request.types = opts.types;
            }
            const biasLat = state.pickup && isFinite(state.pickup.lat) ? state.pickup.lat : cfg.centerLat;
            const biasLng = state.pickup && isFinite(state.pickup.lng) ? state.pickup.lng : cfg.centerLng;
            if (isFinite(biasLat) && isFinite(biasLng) && google.maps.LatLng) {
                request.location = new google.maps.LatLng(biasLat, biasLng);
                request.radius = 50000;
            }
            return new Promise(function (resolve) {
                state.placesService.getPlacePredictions(request, function (results, status) {
                    if (status !== google.maps.places.PlacesServiceStatus.OK || !Array.isArray(results)) {
                        resolve([]);
                        return;
                    }
                    resolve(results.slice(0, 8).map(function (item) {
                        return formatGooglePredictionItem(item, query);
                    }).filter(function (item) { return !!item.address; }));
                });
            });
        }).catch(function () { return []; });
    }

    function fetchNominatimAddressPredictions(query) {
        const base = String(cfg.addressSearchUrl || '').trim();
        if (!base) return Promise.resolve([]);
        const params = new URLSearchParams({
            q: query,
            limit: '8',
            countrycodes: 'nl',
            'accept-language': 'nl',
            format: 'jsonv2',
            addressdetails: '1',
        });
        return fetch(base + (base.indexOf('?') >= 0 ? '&' : '?') + params.toString(), {
            headers: { Accept: 'application/json' },
        }).then(function (res) {
            return res.ok ? res.json() : [];
        }).then(function (rows) {
            if (!Array.isArray(rows)) return [];
            return rows.slice(0, 8).map(formatNominatimPredictionItem).filter(Boolean);
        }).catch(function () { return []; });
    }

    function resolveSuggestionCoordinates(suggestion) {
        if (!suggestion) return Promise.resolve(null);
        if (suggestion.lat != null && suggestion.lng != null
            && isFinite(suggestion.lat) && isFinite(suggestion.lng)) {
            return Promise.resolve({ lat: suggestion.lat, lng: suggestion.lng });
        }
        const placeId = String(suggestion.place_id || '').trim();
        const address = String(suggestion.address || '').trim();
        return loadGoogleMapsSdk().then(function () {
            return new Promise(function (resolve) {
                if (!window.google || !google.maps || !google.maps.Geocoder) {
                    resolve(null);
                    return;
                }
                const geocoder = new google.maps.Geocoder();
                const request = placeId ? { placeId: placeId } : { address: address, region: 'nl' };
                geocoder.geocode(request, function (results, status) {
                    if (status !== 'OK' || !Array.isArray(results) || !results[0] || !results[0].geometry) {
                        resolve(null);
                        return;
                    }
                    const loc = results[0].geometry.location;
                    resolve({
                        lat: typeof loc.lat === 'function' ? loc.lat() : parseFloat(loc.lat),
                        lng: typeof loc.lng === 'function' ? loc.lng() : parseFloat(loc.lng),
                    });
                });
            });
        }).catch(function () {
            return null;
        }).then(function (coords) {
            if (coords && isFinite(coords.lat) && isFinite(coords.lng)) {
                return coords;
            }
            if (!address) return null;
            return fetchNominatimAddressPredictions(address).then(function (items) {
                const first = items && items[0];
                if (first && first.lat != null && first.lng != null) {
                    return { lat: first.lat, lng: first.lng };
                }
                return null;
            });
        });
    }

    function syncAddressClearButton(inputId) {
        const input = el(inputId);
        if (!input) return;
        const wrap = input.closest('.field-suggest-input-wrap');
        if (!wrap) return;
        const hasValue = String(input.value || '').trim() !== '';
        wrap.classList.toggle('has-value', hasValue);
    }

    function clearAddressField(inputId) {
        const input = el(inputId);
        const list = el(inputId + '-suggestions');
        if (!input) return;

        input.value = '';
        input.readOnly = false;
        if (list) {
            list.hidden = true;
            list.innerHTML = '';
        }

        if (inputId === 'pickup') {
            state.pickup = null;
            state.fleetDidFit = false;
            state.nearestTaxi = null;
            const hint = el('pickup-hint');
            if (hint) {
                hint.hidden = false;
                hint.textContent = 'Vul je ophaaladres in';
            }
            hidePickupLocationNotice();
        } else if (inputId === 'dropoff') {
            state.dropoff = null;
        }

        syncAddressClearButton(inputId);
        clearRouteLine();
        Promise.resolve(updateMapMarkers()).then(function () {
            if (inputId === 'pickup') {
                updateFleetRadiusCircle();
                fetchNearbyTaxis();
            }
            maybeQuote();
        });
        input.focus();
    }

    function bindAddressClearButtons() {
        ['pickup', 'dropoff'].forEach(function (inputId) {
            const input = el(inputId);
            const clearBtn = el(inputId + '-clear');
            if (!input || !clearBtn) return;

            syncAddressClearButton(inputId);

            clearBtn.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });
            clearBtn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                clearAddressField(inputId);
            });

            input.addEventListener('input', function () {
                syncAddressClearButton(inputId);
            });
            input.addEventListener('change', function () {
                syncAddressClearButton(inputId);
            });
        });
    }

    function bindAddressField(inputId, listId, onPicked) {
        const input = el(inputId);
        const list = el(listId);
        if (!input || !list) return;

        let searchTimer = null;
        let requestSeq = 0;
        let hideTimer = null;
        let latestSuggestions = [];

        function hideList() {
            list.hidden = true;
            list.innerHTML = '';
        }

        function renderSuggestions(items) {
            latestSuggestions = Array.isArray(items) ? items : [];
            list.innerHTML = '';
            latestSuggestions.forEach(function (item, index) {
                const li = document.createElement('li');
                li.textContent = item.label || item.address || '';
                li.setAttribute('role', 'option');
                li.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                });
                li.addEventListener('click', function () {
                    selectSuggestion(item);
                });
                if (index === 0) li.setAttribute('aria-selected', 'true');
                list.appendChild(li);
            });
            list.hidden = !list.children.length;
        }

        async function selectSuggestion(item) {
            hideList();
            const address = String((item && (item.address || item.label)) || '').trim();
            if (!address) return;
            input.value = address;
            syncAddressClearButton(inputId);

            // Direct omhoog bij klik — niet wachten op geocode/route.
            const willShowRoute = (inputId === 'dropoff' && state.pickup)
                || (inputId === 'pickup' && state.dropoff)
                || inputId === 'dropoff';
            if (willShowRoute) {
                scrollBookToMap();
            }

            const coords = await resolveSuggestionCoordinates(item);
            if (!coords || !isFinite(coords.lat) || !isFinite(coords.lng)) {
                toast('Adres gevonden, maar locatie kon niet worden bepaald. Probeer een ander adres.');
                return;
            }
            onPicked({ lat: coords.lat, lng: coords.lng, address: address });
        }

        async function runSearch() {
            const q = normalizeAddressQuery(input.value);
            if (q.length < 2) {
                hideList();
                latestSuggestions = [];
                return;
            }
            const cacheKey = inputId + '::' + q.toLowerCase();
            if (state.addressCache[cacheKey]) {
                renderSuggestions(state.addressCache[cacheKey]);
                return;
            }
            const seq = ++requestSeq;

            const predictions = await Promise.all([
                fetchGoogleAddressPredictions(q),
                fetchGoogleAddressPredictions(q, { types: ['establishment'] }),
            ]);
            if (seq !== requestSeq) return;
            const googleItems = predictions[0];
            const businessItems = predictions[1];

            let nominatimItems = [];
            // Altijd meenemen: bedrijven uit de serverzoekopdracht en stations die Google mist.
            nominatimItems = await fetchNominatimAddressPredictions(q);
            if (seq !== requestSeq) return;

            const merged = mergeAddressSuggestions(googleItems, nominatimItems, q, businessItems);
            rememberAddressCache(cacheKey, merged);
            renderSuggestions(merged);
        }

        input.addEventListener('input', function () {
            clearTimeout(searchTimer);
            const q = normalizeAddressQuery(input.value);
            if (inputId === 'pickup') {
                state.pickup = null;
                clearRouteLine();
            } else if (inputId === 'dropoff') {
                state.dropoff = null;
                clearRouteLine();
            }
            if (q.length < 2) {
                hideList();
                return;
            }
            searchTimer = setTimeout(runSearch, 120);
        });

        input.addEventListener('focus', function () {
            if (normalizeAddressQuery(input.value).length >= 2) {
                runSearch();
            }
        });

        input.addEventListener('blur', function () {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(hideList, 180);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                hideList();
                return;
            }
            if (event.key === 'Enter' && latestSuggestions.length > 0) {
                event.preventDefault();
                selectSuggestion(latestSuggestions[0]);
            }
        });
    }

    function bindAddressSearch() {
        bindAddressField('pickup', 'pickup-suggestions', function (point) {
            state.pickup = point;
            state.fleetDidFit = false;
            state.nearestTaxi = null;
            hidePickupLocationNotice();
            const hint = el('pickup-hint');
            if (hint) {
                hint.hidden = false;
                hint.textContent = 'Handmatig gekozen · tik om te wijzigen';
            }
            syncAddressClearButton('pickup');
            Promise.resolve(updateMapMarkers()).then(function () {
                updateFleetRadiusCircle();
                fetchNearbyTaxis();
                maybeQuote();
            });
        });
        bindAddressField('dropoff', 'dropoff-suggestions', function (point) {
            state.dropoff = point;
            syncAddressClearButton('dropoff');
            Promise.resolve(updateMapMarkers()).then(function () {
                maybeQuote();
            });
        });
    }

    function bindPickupDateTimePicker() {
        const overlay = el('pickup-datetime-overlay');
        const openBtn = el('pickup-at-display');
        const daysEl = el('dt-days');
        const hoursEl = el('dt-hours');
        const minutesEl = el('dt-minutes');
        if (!overlay || !openBtn || !daysEl || !hoursEl || !minutesEl) return;

        let draft = parseLocalInputValue(el('pickup-at').value || defaultPickupAt());
        let dayButtons = [];
        let hourItems = [];
        let minuteItems = [];
        let syncingScroll = false;

        function pad(n) { return String(n).padStart(2, '0'); }

        function startOfDay(d) {
            return new Date(d.getFullYear(), d.getMonth(), d.getDate());
        }

        function buildDays() {
            daysEl.innerHTML = '';
            dayButtons = [];
            const today = startOfDay(new Date());
            for (let i = 0; i < 14; i++) {
                const day = new Date(today);
                day.setDate(today.getDate() + i);
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'dt-day';
                btn.dataset.ymd = day.getFullYear() + '-' + pad(day.getMonth() + 1) + '-' + pad(day.getDate());
                const wd = i === 0 ? 'Vandaag' : (i === 1 ? 'Morgen' : day.toLocaleDateString('nl-NL', { weekday: 'short' }).replace(/\./g, ''));
                const month = day.toLocaleDateString('nl-NL', { month: 'short' }).replace(/\./g, '').toLowerCase();
                btn.innerHTML = '<span class="dt-day__wd">' + wd + '</span>'
                    + '<span class="dt-day__nr">' + day.getDate() + '</span>'
                    + '<span class="dt-day__month">' + month + '</span>';
                btn.addEventListener('click', function () {
                    draft = clampToFuture(new Date(
                        day.getFullYear(), day.getMonth(), day.getDate(),
                        draft.getHours(), draft.getMinutes(), 0, 0
                    ));
                    syncUi({ scroll: true });
                });
                daysEl.appendChild(btn);
                dayButtons.push(btn);
            }
        }

        function buildTimeColumns() {
            hoursEl.innerHTML = '';
            minutesEl.innerHTML = '';
            hourItems = [];
            minuteItems = [];
            for (let h = 0; h < 24; h++) {
                const item = document.createElement('div');
                item.className = 'dt-item';
                item.dataset.value = String(h);
                item.textContent = pad(h);
                item.addEventListener('click', function () {
                    draft = clampToFuture(new Date(
                        draft.getFullYear(), draft.getMonth(), draft.getDate(),
                        h, draft.getMinutes(), 0, 0
                    ));
                    syncUi({ scroll: true });
                });
                hoursEl.appendChild(item);
                hourItems.push(item);
            }

            for (let m = 0; m < 60; m += 5) {
                const item = document.createElement('div');
                item.className = 'dt-item';
                item.dataset.value = String(m);
                item.textContent = pad(m);
                item.addEventListener('click', function () {
                    draft = clampToFuture(new Date(
                        draft.getFullYear(), draft.getMonth(), draft.getDate(),
                        draft.getHours(), m, 0, 0
                    ));
                    syncUi({ scroll: true });
                });
                minutesEl.appendChild(item);
                minuteItems.push(item);
            }
        }

        function nearestMinuteIndex(mins) {
            let best = 0;
            let bestDiff = 99;
            minuteItems.forEach(function (item, idx) {
                const v = parseInt(item.dataset.value, 10);
                const diff = Math.abs(v - mins);
                if (diff < bestDiff) {
                    bestDiff = diff;
                    best = idx;
                }
            });
            return best;
        }

        function scrollToActive(col, items, index) {
            const target = items[index];
            if (!target) return;
            const top = target.offsetTop - (col.clientHeight / 2) + (target.clientHeight / 2);
            col.scrollTop = Math.max(0, top);
        }

        function syncUi(options) {
            const opts = options || {};
            const ymd = draft.getFullYear() + '-' + pad(draft.getMonth() + 1) + '-' + pad(draft.getDate());
            dayButtons.forEach(function (btn) {
                btn.classList.toggle('is-active', btn.dataset.ymd === ymd);
            });
            hourItems.forEach(function (item) {
                item.classList.toggle('is-active', parseInt(item.dataset.value, 10) === draft.getHours());
            });
            const minIdx = nearestMinuteIndex(draft.getMinutes());
            const snappedMin = minuteItems[minIdx] ? parseInt(minuteItems[minIdx].dataset.value, 10) : draft.getMinutes();
            draft.setMinutes(snappedMin);
            minuteItems.forEach(function (item, idx) {
                item.classList.toggle('is-active', idx === minIdx);
            });
            document.querySelectorAll('#dt-quick .dt-chip').forEach(function (chip) {
                const mins = parseInt(chip.dataset.quick, 10) || 0;
                const active = sameMinute(draft, presetDate(mins));
                chip.classList.toggle('is-active', active);
                chip.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            const preview = el('dt-preview');
            if (preview) preview.textContent = formatPickupLabel(toLocalInputValue(draft));
            if (opts.scroll !== false) {
                syncingScroll = true;
                scrollToActive(hoursEl, hourItems, draft.getHours());
                scrollToActive(minutesEl, minuteItems, minIdx);
                const activeDay = daysEl.querySelector('.dt-day.is-active');
                if (activeDay && daysEl.clientWidth) {
                    const rowBox = daysEl.getBoundingClientRect();
                    const dayBox = activeDay.getBoundingClientRect();
                    const delta = (dayBox.left + dayBox.width / 2) - (rowBox.left + rowBox.width / 2);
                    daysEl.scrollLeft += delta;
                }
                setTimeout(function () { syncingScroll = false; }, 160);
            }
        }

        function readScrollColumn(col, items) {
            const mid = col.scrollTop + col.clientHeight / 2;
            let best = 0;
            let bestDist = Infinity;
            items.forEach(function (item, idx) {
                const center = item.offsetTop + item.clientHeight / 2;
                const dist = Math.abs(center - mid);
                if (dist < bestDist) {
                    bestDist = dist;
                    best = idx;
                }
            });
            return best;
        }

        function onScrollSnap(col, items, apply) {
            let timer = null;
            col.addEventListener('scroll', function () {
                if (syncingScroll) return;
                clearTimeout(timer);
                timer = setTimeout(function () {
                    const idx = readScrollColumn(col, items);
                    apply(idx);
                    syncUi({ scroll: true });
                }, 80);
            });
        }

        function openPicker() {
            draft = parseLocalInputValue(el('pickup-at').value || defaultPickupAt());
            if (Number.isNaN(draft.getTime())) draft = presetDate(10);
            draft = clampToFuture(draft);
            overlay.hidden = false;
            const paint = function () { syncUi({ scroll: true }); };
            paint();
            requestAnimationFrame(paint);
            setTimeout(paint, 80);
        }

        function closePicker() {
            overlay.hidden = true;
        }

        buildDays();
        buildTimeColumns();
        onScrollSnap(hoursEl, hourItems, function (idx) {
            const h = hourItems[idx] ? parseInt(hourItems[idx].dataset.value, 10) : idx;
            draft = clampToFuture(new Date(
                draft.getFullYear(), draft.getMonth(), draft.getDate(),
                h, draft.getMinutes(), 0, 0
            ));
        });
        onScrollSnap(minutesEl, minuteItems, function (idx) {
            const item = minuteItems[idx];
            const m = item ? parseInt(item.dataset.value, 10) : draft.getMinutes();
            draft = clampToFuture(new Date(
                draft.getFullYear(), draft.getMonth(), draft.getDate(),
                draft.getHours(), m, 0, 0
            ));
        });

        openBtn.addEventListener('click', openPicker);
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) closePicker();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !overlay.hidden) closePicker();
        });
        el('dt-confirm').addEventListener('click', function () {
            draft = clampToFuture(draft);
            setPickupAtValue(toLocalInputValue(draft));
            closePicker();
        });
        document.querySelectorAll('#dt-quick .dt-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                const mins = parseInt(chip.dataset.quick, 10) || 10;
                draft = presetDate(mins);
                syncUi({ scroll: true });
            });
        });
    }

    async function routeMetrics() {
        if (!state.pickup || !state.dropoff) return null;
        if (state.routeMetricsCache
            && state.routeMetricsCache.distance_meters
            && state.routeMetricsCache.duration_seconds) {
            return state.routeMetricsCache;
        }
        if (window.google && google.maps && google.maps.geometry) {
            const from = new google.maps.LatLng(state.pickup.lat, state.pickup.lng);
            const to = new google.maps.LatLng(state.dropoff.lat, state.dropoff.lng);
            const meters = google.maps.geometry.spherical.computeDistanceBetween(from, to);
            const roadMeters = Math.max(50, Math.round(meters * 1.25));
            const seconds = Math.max(60, Math.round((roadMeters / 1000) / 30 * 3600));
            return { distance_meters: roadMeters, duration_seconds: seconds };
        }
        const R = 6371000;
        const toRad = (d) => d * Math.PI / 180;
        const dLat = toRad(state.dropoff.lat - state.pickup.lat);
        const dLng = toRad(state.dropoff.lng - state.pickup.lng);
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(toRad(state.pickup.lat)) * Math.cos(toRad(state.dropoff.lat)) * Math.sin(dLng / 2) ** 2;
        const meters = 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        const roadMeters = Math.max(50, Math.round(meters * 1.25));
        return {
            distance_meters: roadMeters,
            duration_seconds: Math.max(60, Math.round((roadMeters / 1000) / 30 * 3600)),
        };
    }

    async function maybeQuote() {
        const metrics = await routeMetrics();
        if (!metrics || !state.pickup) {
            el('quote-card').hidden = true;
            state.quote = null;
            return;
        }
        try {
            const data = await api(cfg.quoteUrl, {
                method: 'POST',
                body: JSON.stringify({
                    distance_meters: metrics.distance_meters,
                    duration_seconds: metrics.duration_seconds,
                    passengers: parseInt(el('passengers').value, 10) || 1,
                    pickup_lat: state.pickup.lat,
                    pickup_lng: state.pickup.lng,
                    pickup_at: el('pickup-at').value
                        ? toPickupAtPayload(el('pickup-at').value)
                        : null,
                    ...collectBaggagePayload(),
                }),
            });
            state.quote = data;
            const offer = (data.offers && data.offers[0]) || null;
            if (offer) {
                const km = (metrics.distance_meters / 1000).toFixed(1).replace('.', ',');
                const mins = Math.max(1, Math.round(metrics.duration_seconds / 60));
                const routeEl = el('quote-route');
                if (routeEl) routeEl.textContent = km + ' km · ca. ' + mins + ' min';
                el('quote-price').textContent = offer.price_label
                    || ('€ ' + Number(offer.price || 0).toFixed(2).replace('.', ','));
                const cand = (data.marketplace && data.marketplace.candidate_count) || 0;
                el('quote-meta').textContent = cand > 0
                    ? (cand + ' taxi' + (cand === 1 ? '' : '’s') + ' in de buurt')
                    : 'Geen vrije taxi in de buurt';
                const warn = el('quote-warn');
                const unavailable = data.marketplace && data.marketplace.unavailable_message;
                if (warn) {
                    if (cand === 0) {
                        warn.hidden = false;
                        warn.textContent = unavailable
                            || 'Geen taxi’s beschikbaar. We sturen geen rit uit. Je krijgt een melding zodra er weer een taxi beschikbaar is.';
                    } else {
                        warn.hidden = true;
                        warn.textContent = '';
                    }
                }
                el('quote-card').hidden = false;
                updatePaymentUi(data.payment || null);
                syncMapFitButton();
            }
        } catch (e) {
            el('quote-card').hidden = true;
            const payCard = el('payment-card');
            if (payCard) payCard.hidden = true;
            state.quote = null;
        }
    }

    async function bookRide() {
        unlockAudio();
        persistProfileDraft();
        if (!state.pickup || !state.pickup.address) {
            toast('Bepaal eerst je ophaallocatie.');
            return;
        }
        if (!state.dropoff || !state.dropoff.address) {
            toast('Vul een bestemming in.');
            return;
        }
        const first = el('first-name').value.trim();
        const last = el('last-name').value.trim();
        const phone = el('phone').value.trim();
        const email = el('email').value.trim();
        const paymentMethod = selectedPaymentMethod();
        if (first.length < 2 || last.length < 2) {
            toast('Vul voor- en achternaam in.');
            return;
        }
        if (phone.length < 8) {
            toast('Vul een geldig telefoonnummer in.');
            return;
        }
        if (paymentMethod === 'booking' && (!email || email.indexOf('@') < 1)) {
            toast('Vul een e-mailadres in voor de online betaling.');
            return;
        }
        const metrics = await routeMetrics();
        if (!metrics) {
            toast('Kon de route niet berekenen.');
            return;
        }
        const offer = state.quote && state.quote.offers && state.quote.offers[0];
        const payload = {
            distance_meters: metrics.distance_meters,
            duration_seconds: metrics.duration_seconds,
            passengers: parseInt(el('passengers').value, 10) || 1,
            pickup_address: state.pickup.address,
            dropoff_address: state.dropoff.address,
            pickup_lat: state.pickup.lat,
            pickup_lng: state.pickup.lng,
            dropoff_lat: state.dropoff.lat,
            dropoff_lng: state.dropoff.lng,
            pickup_at: toPickupAtPayload(el('pickup-at').value || defaultPickupAt()),
            first_name: first,
            last_name: last,
            phone: phone,
            email: email || null,
            remarks: el('remarks').value.trim() || null,
            selected_offer_id: offer ? offer.id : null,
            payment_method: paymentMethod,
            return_url: window.location.origin + '/taxi/klant',
            ...collectBaggagePayload(),
        };
        const btn = el('btn-book');
        btn.disabled = true;
        btn.textContent = paymentMethod === 'booking' ? 'Door naar betaling…' : 'Bezig…';
        try {
            const url = state.token ? (cfg.apiBase + '/book') : (cfg.apiBase + '/book/guest');
            const data = await api(url, { method: 'POST', body: JSON.stringify(payload) });
            setTrackToken(data.track_token);
            saveGuestRide(data.ride_request_id, data.track_token, {
                from: state.pickup && state.pickup.address ? state.pickup.address : '',
                to: state.dropoff && state.dropoff.address ? state.dropoff.address : '',
                phase: (data.live && data.live.phase) || (data.payment_required ? 'awaiting_payment' : 'searching'),
                status_label: (data.live && data.live.status_label) || (data.payment_required ? 'Wacht op betaling' : 'Zoeken…'),
                pickup_at: (data.live && (data.live.pickup_at_label || data.live.pickup_at)) || payload.pickup_at,
                passengers: payload.passengers,
                baggage_summary: baggageSummaryFromRide(data.live) || '',
                baggage: (data.live && data.live.baggage) || null,
                distance_km: (data.live && data.live.distance_km != null)
                    ? data.live.distance_km
                    : (payload.distance_meters ? Math.round(payload.distance_meters / 100) / 10 : null),
                duration_seconds: (data.live && data.live.duration_seconds != null)
                    ? data.live.duration_seconds
                    : payload.duration_seconds,
                quoted_price: data.live && data.live.quoted_price != null ? data.live.quoted_price : null,
            });
            if (data.payment_required && data.checkout_url) {
                toast('Je wordt doorgestuurd naar de betaling…');
                window.location.href = data.checkout_url;
                return;
            }
            state.acceptedNotified = false;
            openLive(data.live || null);
            toast('Rit aangevraagd op de marktplaats.');
        } catch (e) {
            toast(e.message || 'Boeken mislukt.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Taxi aanvragen';
        }
    }

    function openLive(initial) {
        unlockAudio();
        if (state.trackToken) state.expandedRideToken = state.trackToken;
        stopFleetPolling();
        showScreen('rides');
        state.liveRouteKey = '';
        state.liveRouteFetchSeq += 1;
        if (initial && initial.phase && initial.phase !== 'searching') {
            state.acceptedNotified = true;
            state.livePhase = initial.phase;
        }
        if (initial) {
            renderLive(initial);
            syncBookFollow(initial);
        }
        startLivePoll();
        loadRides();
    }

    function startLivePoll() {
        stopLivePoll();
        const tick = async () => {
            if (!state.trackToken) return;
            try {
                const data = await api(cfg.liveUrl + '?token=' + encodeURIComponent(state.trackToken));
                const prevPhase = state.livePhase;
                if (data && data.ride) {
                    renderLive(data.ride);
                    if (prevPhase === 'awaiting_payment' && data.ride.phase === 'searching') {
                        toast('Betaling ontvangen. We zoeken een taxi…');
                    }
                }
                const ms = (data && data.ride && data.ride.poll_interval_ms) || 3000;
                state.liveTimer = setTimeout(tick, ms);
            } catch (e) {
                state.liveTimer = setTimeout(tick, 2500);
            }
        };
        tick();
    }

    function stopLivePoll() {
        if (state.liveTimer) clearTimeout(state.liveTimer);
        state.liveTimer = null;
    }

    function formatStatusUpdateAt(value) {
        const d = value ? new Date(value) : null;
        if (!d || !Number.isFinite(d.getTime())) return '';
        try {
            return d.toLocaleString('nl-NL', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            });
        } catch (e) {
            return '';
        }
    }

    function renderLiveStatusUpdates(ride) {
        const box = el('live-status-updates');
        if (!box) return;
        const phase = ride && ride.phase;
        const raw = ride && Array.isArray(ride.status_updates) ? ride.status_updates.slice() : [];
        if (!raw.length || (phase !== 'accepted' && phase !== 'cancelled' && phase !== 'completed')) {
            box.hidden = true;
            box.innerHTML = '';
            return;
        }
        const ranked = raw.map(function (update, index) {
            return { update: update, index: index };
        }).sort(function (a, b) {
            const aMs = Date.parse(a.update && a.update.at) || 0;
            const bMs = Date.parse(b.update && b.update.at) || 0;
            if (bMs !== aMs) return bMs - aMs;
            return b.index - a.index;
        });
        box.innerHTML = '<p class="ride-card__stop-label">Statusupdates</p>'
            + '<div class="live-status-updates__list">'
            + ranked.map(function (row, idx) {
                const u = row.update || {};
                const event = String(u.event || '');
                const isCurrent = idx === 0 && event !== 'cancelled' && event !== 'declined';
                const at = formatStatusUpdateAt(u.at);
                return '<div class="live-status-updates__row">'
                    + '<span class="live-status-updates__dot' + (isCurrent ? ' is-current' : '') + '" aria-hidden="true"></span>'
                    + '<div>'
                    + '<p class="live-status-updates__label' + (isCurrent ? ' is-current' : '') + '">'
                    + escapeHtml(String(u.label || event || 'Update'))
                    + '</p>'
                    + (at ? ('<p class="live-status-updates__at">' + escapeHtml(at) + '</p>') : '')
                    + '</div></div>';
            }).join('')
            + '</div>';
        box.hidden = false;
    }

    function renderLive(ride) {
        const banner = el('live-banner');
        const pill = el('live-pill');
        const title = el('live-title');
        const text = el('live-text');
        const details = el('live-details');
        const note = el('live-note');
        const actions = el('live-actions');
        const btnCancel = el('btn-live-cancel');
        const btnWait = el('btn-live-wait');
        const btnPay = el('btn-live-pay');
        const btnInvoice = el('btn-live-invoice');
        const routeBox = el('live-route');
        const statsBox = el('live-stats');
        const metaWrap = el('live-meta-wrap');
        const pickupAtEl = el('live-pickup-at');
        const priceWrap = el('live-price-wrap');
        const priceEl = el('live-price');
        const priceAfter = el('live-price-after');
        const priceAfterValue = el('live-price-after-value');

        state.liveRide = ride || null;
        if (banner) {
            banner.classList.toggle('is-accepted', !!(ride && ride.phase === 'accepted'));
            banner.classList.toggle('is-cancelled', !!(ride && ride.phase === 'cancelled'));
            banner.classList.toggle('is-searching', !!(ride && ride.phase === 'searching'));
        }

        const fromRaw = ride && (ride.pickup_address || ride.from);
        const toRaw = ride && (ride.dropoff_address || ride.to);
        if (routeBox) {
            const hasRoute = !!(String(fromRaw || '').trim() && String(toRaw || '').trim());
            routeBox.innerHTML = hasRoute ? rideStopsHtml(fromRaw, toRaw) : '';
            routeBox.hidden = !hasRoute;
        }
        const when = formatRideWhen(ride && (ride.pickup_at_label || ride.pickup_at || ride.at || ride.at_ts));
        const priceLabel = ride && ride.quoted_price != null
            ? formatEuroNl(ride.quoted_price)
            : (ride && ride.amount ? String(ride.amount) : '');
        const guestForStats = state.trackToken
            ? (guestRides().find(function (r) { return r && r.token === state.trackToken; })
                || (state.ridesCache || []).find(function (r) { return r && r.token === state.trackToken; }))
            : null;
        const statsRide = ride ? Object.assign({}, guestForStats || {}, ride, {
            distance_km: ride.distance_km != null
                ? ride.distance_km
                : (guestForStats && guestForStats.distance_km != null ? guestForStats.distance_km : null),
            duration_seconds: ride.duration_seconds != null
                ? ride.duration_seconds
                : (guestForStats && guestForStats.duration_seconds != null ? guestForStats.duration_seconds : null),
            passengers: ride.passengers != null
                ? ride.passengers
                : (guestForStats && guestForStats.passengers != null ? guestForStats.passengers : null),
            baggage: ride.baggage || (guestForStats && guestForStats.baggage) || null,
            baggage_summary: baggageSummaryFromRide(ride)
                || (guestForStats && guestForStats.baggage_summary)
                || '',
        }) : ride;
        const passengers = statsRide && statsRide.passengers != null ? Number(statsRide.passengers) : 0;
        const baggageSummary = baggageSummaryFromRide(statsRide);
        const statsHtml = rideStatsHtml(statsRide);
        if (statsBox) {
            statsBox.innerHTML = statsHtml;
            statsBox.hidden = !statsHtml;
        }
        if (pickupAtEl) pickupAtEl.textContent = when || '—';
        if (priceEl) priceEl.textContent = priceLabel || '—';
        if (priceWrap) priceWrap.hidden = !priceLabel;
        if (metaWrap) {
            metaWrap.hidden = !(when || priceLabel);
        }
        if (priceAfter && priceAfterValue) {
            const showPriceAfter = !!(ride && ride.phase === 'accepted' && priceLabel);
            priceAfterValue.textContent = priceLabel || '—';
            priceAfter.hidden = !showPriceAfter;
        }

        if (state.trackToken && ride) {
            updateGuestRideByToken(state.trackToken, {
                id: ride.id || undefined,
                from: ride.pickup_address || undefined,
                to: ride.dropoff_address || undefined,
                phase: ride.phase || 'other',
                status_label: ride.status_label || ride.status || '',
                payment_failure_status: ride.payment_failure_status || null,
                rating: ride.rating || null,
                pickup_at: ride.pickup_at_label || ride.pickup_at || undefined,
                amount: priceLabel || undefined,
                passengers: passengers > 0 ? passengers : undefined,
                baggage_summary: baggageSummary || undefined,
                baggage: ride.baggage || undefined,
                distance_km: ride.distance_km != null ? Number(ride.distance_km) : undefined,
                duration_seconds: ride.duration_seconds != null ? Number(ride.duration_seconds) : undefined,
                pickup_confirmation: ride.pickup_confirmation || null,
            });
            syncRideCardFromLive(state.trackToken, ride);
        }

        if (ride && ride.phase !== 'completed') {
            hideLiveRating();
        }

        if (note) {
            note.hidden = true;
            note.classList.remove('is-refund');
            note.textContent = '';
        }
        const pickupBox = el('live-pickup-confirm');
        if (pickupBox) {
            const html = pickupConfirmHtml(ride, state.trackToken);
            pickupBox.innerHTML = html;
            pickupBox.hidden = !html;
        }
        if (text && ride && ride.phase !== 'accepted') {
            text.hidden = false;
        }
        if (ride && ride.phase !== 'accepted' && ride.phase !== 'cancelled' && ride.phase !== 'completed') {
            renderLiveStatusUpdates(null);
        }
        const liveDriverCard = el('live-driver-card');
        if (liveDriverCard && ride.phase !== 'accepted') {
            liveDriverCard.hidden = true;
            liveDriverCard.innerHTML = '';
        }
        const liveEtaWrap = el('live-eta-wrap');
        const liveEtaValue = el('live-eta-value');
        if (liveEtaWrap && ride.phase !== 'accepted') {
            liveEtaWrap.hidden = true;
            if (liveEtaValue) liveEtaValue.textContent = '—';
        }
        if (priceAfter && (!ride || ride.phase !== 'accepted')) {
            priceAfter.hidden = true;
        }

        if (ride.phase === 'awaiting_payment') {
            setLiveSearchingTitleVisible(false);
            const paymentFailed = !!ride.payment_failure_status;
            pill.className = 'status-pill warn';
            pill.textContent = paymentFailed ? 'Betaling mislukt' : 'Betaling';
            title.textContent = paymentFailed
                ? 'Betaling mislukt'
                : (ride.payment_error ? 'Wachten op betaling' : 'Betaling controleren…');
            text.textContent = ride.payment_error
                || (paymentFailed
                    ? 'De betaling is mislukt. Betaal opnieuw om de rit te activeren, of annuleer de rit.'
                    : 'We controleren je betaling. Dit duurt meestal maar een paar seconden.');
            details.hidden = true;
            if (note && (paymentFailed || ride.can_retry_payment)) {
                note.hidden = false;
                note.textContent = paymentFailed
                    ? 'Zolang er niet betaald is, wordt deze rit niet uitgezet naar chauffeurs.'
                    : 'Even geduld — zodra de betaling binnen is starten we met zoeken.';
            }
        } else if (ride.phase === 'searching') {
            pill.className = 'status-pill is-searching';
            pill.textContent = 'Zoeken…';
            details.hidden = true;
            if (ride.needs_unaccepted_decision) {
                setLiveSearchingTitleVisible(false);
                title.textContent = 'Nog geen taxi gevonden';
                text.textContent = 'Wil je blijven wachten tot er een taxi beschikbaar is, of deze rit annuleren?';
                if (note) {
                    note.hidden = false;
                    note.textContent = ride.decision_deadline_label
                        ? ('Geen reactie vóór ' + ride.decision_deadline_label
                            + '? Dan annuleren we automatisch. Vooraf betaalde bedragen worden teruggestort'
                            + ' (doorgaans binnen ' + (ride.refund_business_days || 10) + ' werkdagen).')
                        : ('Kies hieronder. Vooraf betaalde bedragen worden teruggestort bij annuleren'
                            + ' (doorgaans binnen ' + (ride.refund_business_days || 10) + ' werkdagen).');
                }
            } else if (ride.waiting_for_taxi) {
                setLiveSearchingTitleVisible(false);
                title.textContent = 'We zoeken verder';
                text.textContent = 'Je rit blijft op de marktplaats tot een taxi accepteert. Je kunt alsnog annuleren.';
            } else {
                setLiveSearchingTitleVisible(true);
                title.textContent = 'We zoeken een taxi';
                text.textContent = 'Je rit is aangeboden aan aangesloten taxibedrijven in de buurt.';
            }
        } else if (ride.phase === 'accepted') {
            setLiveSearchingTitleVisible(false);
            if (!state.acceptedNotified && state.livePhase === 'searching') {
                state.acceptedNotified = true;
                playAcceptedSignal();
                try {
                    if (window.Notification && Notification.permission === 'granted') {
                        new Notification('Taxi onderweg', {
                            body: (ride.company && ride.company.name ? ride.company.name + ' · ' : '')
                                + (ride.eta_label || 'Onderweg'),
                        });
                    }
                } catch (e) {}
            } else {
                state.acceptedNotified = true;
            }
            pill.className = 'status-pill ok';
            pill.textContent = 'Geaccepteerd';
            title.textContent = ride.status_label || 'Chauffeur toegewezen';
            if (text) {
                const fromShort = shortAddress(ride.pickup_address || ride.from);
                const toShort = shortAddress(ride.dropoff_address || ride.to);
                text.hidden = false;
                text.textContent = (fromShort && toShort
                    && fromShort !== 'Onbekend' && toShort !== 'Onbekend')
                    ? (fromShort + ' → ' + toShort)
                    : '';
            }
            if (liveEtaWrap && liveEtaValue) {
                if (ride.eta_label) {
                    liveEtaValue.textContent = ride.eta_label;
                    liveEtaWrap.hidden = false;
                } else {
                    liveEtaValue.textContent = '—';
                    liveEtaWrap.hidden = true;
                }
            }
            const driverCard = el('live-driver-card');
            const hasDriverMedia = !!(ride.driver || ride.vehicle);
            if (driverCard) {
                if (hasDriverMedia) {
                    let html = '';
                    if (ride.driver && ride.driver.company_logo_url) {
                        html += '<img class="live-driver-card__logo" src="'
                            + escapeHtml(ride.driver.company_logo_url)
                            + '" alt="" decoding="async">';
                    }
                    html += '<div class="live-driver-card__row">';
                    if (ride.driver && ride.driver.photo_url) {
                        html += '<button type="button" class="live-driver-card__photo-btn" data-photo-preview="'
                            + escapeHtml(ride.driver.photo_url)
                            + '" aria-label="Profielfoto vergroten">'
                            + '<img class="live-driver-card__avatar" src="'
                            + escapeHtml(ride.driver.photo_url)
                            + '" alt="" decoding="async"></button>';
                    }
                    html += '<div class="live-driver-card__meta">';
                    if (ride.driver && ride.driver.name) {
                        html += '<p class="live-driver-card__name">' + escapeHtml(ride.driver.name) + '</p>';
                    }
                    if (ride.driver && ride.driver.rating_avg != null) {
                        html += '<p class="live-driver-card__sub"><span class="live-driver-card__star">★</span> '
                            + Number(ride.driver.rating_avg).toFixed(1)
                            + (ride.driver.rating_count ? ' (' + ride.driver.rating_count + ')' : '')
                            + '</p>';
                    }
                    if (ride.vehicle && ride.vehicle.license_plate) {
                        html += '<p class="live-driver-card__sub">'
                            + escapeHtml(ride.vehicle.license_plate) + '</p>';
                    }
                    if (ride.vehicle && ride.vehicle.name) {
                        html += '<p class="live-driver-card__sub">'
                            + escapeHtml(ride.vehicle.name) + '</p>';
                    }
                    html += '</div>';
                    if (ride.vehicle && ride.vehicle.photo_url) {
                        html += '<button type="button" class="live-driver-card__photo-btn" data-photo-preview="'
                            + escapeHtml(ride.vehicle.photo_url)
                            + '" aria-label="Autofoto vergroten">'
                            + '<img class="live-driver-card__vehicle" src="'
                            + escapeHtml(ride.vehicle.photo_url)
                            + '" alt="" decoding="async"></button>';
                    }
                    html += '</div>';
                    driverCard.innerHTML = html;
                    driverCard.hidden = false;
                } else {
                    driverCard.innerHTML = '';
                    driverCard.hidden = true;
                }
            }
            const rows = [];
            if (ride.company && ride.company.name) rows.push(['Centrale', ride.company.name]);
            if (!hasDriverMedia) {
                if (ride.vehicle && ride.vehicle.license_plate) rows.push(['Kenteken', ride.vehicle.license_plate]);
                if (ride.vehicle && ride.vehicle.name) rows.push(['Auto', ride.vehicle.name]);
                if (ride.driver && ride.driver.name) rows.push(['Chauffeur', ride.driver.name]);
            }
            details.innerHTML = rows.map(function (row) {
                return '<div class="kv__row">'
                    + '<p class="ride-card__stop-label">' + escapeHtml(row[0]) + '</p>'
                    + '<p class="ride-card__stop-value">' + escapeHtml(row[1]) + '</p>'
                    + '</div>';
            }).join('');
            details.hidden = rows.length === 0;
            renderLiveStatusUpdates(ride);
        } else if (ride.phase === 'completed') {
            setLiveSearchingTitleVisible(false);
            pill.className = 'status-pill ok';
            pill.textContent = 'Voltooid';
            title.textContent = ride.status_label || 'Rit afgerond';
            text.textContent = 'Bedankt voor je rit met Nexa Taxi. Beoordeel je rit hieronder of download je factuur.';
            details.hidden = true;
            renderLiveRating(ride);
            renderLiveStatusUpdates(ride);
            stopLivePoll();
        } else if (ride.phase === 'cancelled') {
            setLiveSearchingTitleVisible(false);
            pill.className = 'status-pill warn';
            pill.textContent = 'Geannuleerd';
            title.textContent = 'Rit geannuleerd';
            if (text) {
                text.hidden = false;
                text.textContent = 'Deze rit is geannuleerd.';
            }
            if (note && ride.refund_message) {
                note.hidden = false;
                note.classList.add('is-refund');
                note.innerHTML = '<span class="live-note__label">Terugbetaling</span>'
                    + escapeHtml(ride.refund_message);
            }
            details.hidden = true;
            renderLiveStatusUpdates(ride);
            stopLivePoll();
        } else {
            setLiveSearchingTitleVisible(false);
            pill.className = 'status-pill';
            pill.textContent = ride.status_label || ride.status;
            title.textContent = 'Statusupdate';
            text.textContent = ride.status_label || '';
        }

        const showWait = !!(ride && ride.can_cancel && ride.needs_unaccepted_decision);
        const showCancel = !!(ride && ride.can_cancel);
        const showPay = !!(ride && ride.phase === 'awaiting_payment' && ride.can_retry_payment);
        const showInvoice = !!(ride && ride.can_download_invoice);
        if (btnPay) {
            btnPay.hidden = !showPay;
            btnPay.disabled = false;
            btnPay.textContent = 'Opnieuw betalen';
        }
        if (btnWait) {
            btnWait.hidden = !showWait;
            btnWait.disabled = false;
            btnWait.textContent = 'Blijven wachten';
        }
        if (btnCancel) {
            btnCancel.hidden = !showCancel;
            btnCancel.disabled = false;
            btnCancel.textContent = showPay ? 'Rit annuleren' : 'Rit annuleren';
        }
        if (btnInvoice) {
            btnInvoice.hidden = !showInvoice;
            btnInvoice.disabled = false;
            btnInvoice.textContent = 'Factuur downloaden';
        }
        if (actions) {
            actions.hidden = !(showWait || showCancel || showInvoice || showPay);
        }

        if (ride && ride.phase) {
            state.livePhase = ride.phase;
        }

        ensureLiveMap().then(function () {
            syncLiveRoute(ride);
            if (ride.phase === 'accepted' && ride.vehicle_location) {
                updateVehicleOnMap(ride);
            }
        });
        syncBookFollow(ride);
        mountExpandedFollow();
    }

    async function waitLiveRide() {
        const ride = state.liveRide;
        if (!ride || !ride.can_cancel) return;

        const btn = el('btn-live-wait');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Bezig…';
        }

        try {
            let data;
            if (state.trackToken) {
                data = await api(cfg.apiBase + '/live/wait', {
                    method: 'POST',
                    body: JSON.stringify({ token: state.trackToken }),
                });
            } else if (ride.id && state.token) {
                data = await api(cfg.apiBase + '/rides/' + ride.id + '/wait', { method: 'POST' });
            } else {
                throw new Error('Geen geldige rit.');
            }
            if (data && data.ride) renderLive(data.ride);
            toast(data.message || 'We blijven zoeken.');
        } catch (e) {
            toast(e.message || 'Keuze opslaan mislukt.');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Blijven wachten';
            }
        }
    }

    async function payLiveRide() {
        const ride = state.liveRide;
        if (!ride || !ride.can_retry_payment) return;

        const btn = el('btn-live-pay');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Bezig…';
        }

        try {
            const returnUrl = window.location.origin + '/taxi/klant';
            let data;
            if (state.trackToken) {
                data = await api(cfg.apiBase + '/live/pay', {
                    method: 'POST',
                    body: JSON.stringify({
                        token: state.trackToken,
                        return_url: returnUrl,
                    }),
                });
            } else if (ride.id && state.token) {
                data = await api(cfg.apiBase + '/rides/' + ride.id + '/pay', {
                    method: 'POST',
                    body: JSON.stringify({ return_url: returnUrl }),
                });
            } else {
                throw new Error('Geen geldige rit.');
            }
            if (data && data.track_token) setTrackToken(data.track_token);
            if (data && data.checkout_url) {
                toast('Je wordt doorgestuurd naar de betaling…');
                window.location.href = data.checkout_url;
                return;
            }
            throw new Error('Geen betaallink ontvangen.');
        } catch (e) {
            toast(e.message || 'Betaling starten mislukt.');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Opnieuw betalen';
            }
        }
    }

    async function cancelRideByToken(token, btn) {
        const track = String(token || state.trackToken || '').trim();
        if (!track && !(state.liveRide && state.liveRide.id && state.token)) return;
        if (!window.confirm('Weet je zeker dat je deze rit wilt annuleren?')) return;

        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Annuleren…';
        }

        try {
            let data;
            if (track) {
                data = await api(cfg.apiBase + '/live/cancel', {
                    method: 'POST',
                    body: JSON.stringify({ token: track }),
                });
            } else if (state.liveRide && state.liveRide.id && state.token) {
                data = await api(cfg.apiBase + '/rides/' + state.liveRide.id + '/cancel', { method: 'POST' });
            } else {
                throw new Error('Geen geldige rit om te annuleren.');
            }
            if (data && data.ride) renderLive(data.ride);
            toast(data.message || 'Rit geannuleerd. Deze rit is niet meer geldig.');
            loadRides();
        } catch (e) {
            toast(e.message || 'Annuleren mislukt.');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Rit annuleren';
            }
        }
    }

    async function cancelLiveRide() {
        const ride = state.liveRide;
        if (!ride || !ride.can_cancel) return;
        const btn = el('btn-live-cancel');
        await cancelRideByToken(state.trackToken, btn);
    }

    function ensureSearchingLiveHost(rides) {
        const list = rides || [];
        const searching = list.filter(function (r) {
            return r && r.phase === 'searching' && r.token;
        });
        if (!searching.length) return;

        // Niet overschrijven als de gebruiker een andere (bijv. geaccepteerde) rit heeft opengeklapt
        if (state.expandedRideToken) {
            const expanded = list.find(function (r) {
                return r && r.token === state.expandedRideToken;
            });
            if (expanded && expanded.phase !== 'searching') return;
        }

        const preferred = searching.find(function (r) { return r.token === state.trackToken; })
            || searching.find(function (r) { return r.token === state.expandedRideToken; })
            || searching[0];
        if (!preferred || !preferred.token) return;
        const changed = state.expandedRideToken !== preferred.token || state.trackToken !== preferred.token;
        state.expandedRideToken = preferred.token;
        if (state.trackToken !== preferred.token) {
            setTrackToken(preferred.token);
        }
        if (changed) startLivePoll();
    }

    function rideChevronSvg(expanded) {
        return '<svg class="ride-card__chevron-icon" viewBox="0 0 24 24" aria-hidden="true">'
            + (expanded
                ? '<path d="M6 14.5L12 8.5l6 6"/>'
                : '<path d="M6 9.5l6 6 6-6"/>')
            + '</svg>';
    }

    async function downloadRideInvoice(opts) {
        const options = opts || {};
        const rideId = options.id || (state.liveRide && state.liveRide.id) || null;
        const token = options.token || state.trackToken || '';
        let url = '';
        if (token) {
            url = cfg.apiBase + '/live/invoice?token=' + encodeURIComponent(token);
        } else if (rideId && state.token) {
            url = cfg.apiBase + '/rides/' + rideId + '/invoice';
        } else {
            toast('Factuur niet beschikbaar.');
            return;
        }

        const btn = el('btn-live-invoice');
        if (btn && !options.fromList) {
            btn.disabled = true;
            btn.textContent = 'Downloaden…';
        }

        try {
            const headers = { Accept: 'application/pdf' };
            if (state.token) headers.Authorization = 'Bearer ' + state.token;
            const res = await fetch(url, { headers });
            if (!res.ok) {
                let message = 'Factuur downloaden mislukt.';
                try {
                    const err = await res.json();
                    if (err && err.message) message = err.message;
                } catch (e) {}
                throw new Error(message);
            }
            const blob = await res.blob();
            const objectUrl = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = objectUrl;
            a.download = 'factuur-rit-' + (rideId || 'download') + '.pdf';
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(function () { URL.revokeObjectURL(objectUrl); }, 2000);
            toast('Factuur gedownload.');
        } catch (e) {
            toast(e.message || 'Factuur downloaden mislukt.');
        } finally {
            if (btn && !options.fromList) {
                btn.disabled = false;
                btn.textContent = 'Factuur downloaden';
            }
        }
    }

    function updateVehicleOnMap(ride) {
        if (!state.liveMap || !ride || !ride.vehicle_location) return;
        const pos = { lat: ride.vehicle_location.lat, lng: ride.vehicle_location.lng };
        if (!isFinite(pos.lat) || !isFinite(pos.lng)) return;
        if (!state.vehicleMarker) {
            state.vehicleMarker = new google.maps.Marker({
                map: state.liveMap,
                title: 'Taxi',
                icon: {
                    url: taxiIconUrl('sedan'),
                    scaledSize: new google.maps.Size(28, 38),
                    anchor: new google.maps.Point(14, 19),
                },
            });
            tintedTaxiIconUrl('sedan', function (url) {
                if (!state.vehicleMarker) return;
                state.vehicleMarker.setIcon({
                    url: url,
                    scaledSize: new google.maps.Size(28, 38),
                    anchor: new google.maps.Point(14, 19),
                });
            });
        }
        state.vehicleMarker.setPosition(pos);
        state.vehicleMarker.setMap(state.liveMap);
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function shortAddress(value) {
        const raw = String(value || '').trim();
        if (!raw) return 'Onbekend';
        const normalized = normalizeNlAddressLabel(raw);
        return normalized.split(',')[0].trim() || normalized || raw;
    }

    function formatRideWhen(at) {
        if (at == null || at === '') return '';
        const weekdays = ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'];
        const months = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
        if (typeof at !== 'number') {
            const raw = String(at).trim();
            if (!raw) return '';
            const labeled = raw.match(/^(\d{2})-(\d{2})-(\d{4})[,\s]+(\d{2}):(\d{2})/);
            if (labeled) {
                const day = Number(labeled[1]);
                const month = Number(labeled[2]);
                const year = Number(labeled[3]);
                const date = new Date(year, month - 1, day);
                return weekdays[date.getDay()] + ' ' + day + ' ' + months[month - 1] + ' ' + year
                    + ', ' + labeled[4] + ':' + labeled[5];
            }
            if (/^[a-z]{2} \d{1,2} [a-z]{3} \d{4}, \d{2}:\d{2}$/i.test(raw)) return raw;
            const parsed = new Date(raw);
            if (Number.isNaN(parsed.getTime())) return raw;
            at = parsed.getTime();
        }
        try {
            const parts = new Intl.DateTimeFormat('nl-NL', {
                timeZone: 'Europe/Amsterdam',
                weekday: 'short',
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hourCycle: 'h23',
            }).formatToParts(new Date(at));
            const get = function (type) {
                const part = parts.find(function (row) { return row.type === type; });
                return part ? part.value : '';
            };
            const weekday = get('weekday').replace(/\./g, '').slice(0, 2).toLowerCase();
            const day = Number(get('day'));
            const month = get('month').replace(/\./g, '').toLowerCase();
            const hour = get('hour').padStart(2, '0');
            const minute = get('minute').padStart(2, '0');
            if (!day || !get('year')) return '';
            return weekday + ' ' + day + ' ' + month + ' ' + get('year') + ', ' + hour + ':' + minute;
        } catch (e) {
            return '';
        }
    }

    function splitRideAddress(address) {
        const text = String(address || '').trim();
        if (!text) return { main: '—', sub: '' };
        const comma = text.indexOf(',');
        if (comma > 0 && comma < text.length - 1) {
            return { main: text.slice(0, comma).trim(), sub: text.slice(comma + 1).trim() };
        }
        return { main: text, sub: '' };
    }

    function formatTripDistance(km) {
        const n = Number(km);
        if (!Number.isFinite(n) || n <= 0) return '';
        return n.toFixed(1).replace('.', ',') + ' km';
    }

    function formatTripDuration(seconds) {
        const total = Number(seconds);
        if (!Number.isFinite(total) || total < 0) return '';
        const mins = Math.max(0, Math.round(total / 60));
        const hours = Math.floor(mins / 60);
        const rest = mins % 60;
        if (hours <= 0) return rest + ' min';
        if (rest <= 0) return hours + ' u';
        return hours + ' u ' + rest + ' min';
    }

    function baggageChipRows(ride) {
        const items = ride && ride.baggage && ride.baggage.items;
        if (Array.isArray(items) && items.length) {
            return items.map(function (item) {
                const key = String(item && item.key || '').trim();
                const label = String(item && item.label || '').trim();
                const qty = Number(item && item.qty || 0);
                if (!label || qty <= 0) return null;
                return { key: key, label: label, qty: qty };
            }).filter(Boolean);
        }
        const summary = baggageSummaryFromRide(ride);
        if (!summary || /^geen$/i.test(summary)) return [];
        return summary.split(',').map(function (part) {
            const text = String(part || '').trim();
            if (!text) return null;
            const match = text.match(/^(.*?)(?:\s*[×x]\s*(\d+))?$/i);
            const label = String(match && match[1] || text).trim();
            const qty = match && match[2] ? Number(match[2]) : 1;
            return label ? { key: '', label: label, qty: qty > 0 ? qty : 1 } : null;
        }).filter(Boolean);
    }

    function baggageChipLines(ride) {
        return baggageChipRows(ride).map(function (row) {
            return row.label + ' × ' + row.qty;
        });
    }

    function baggageKeyFromLabel(label) {
        const t = String(label || '').toLowerCase();
        if (/rolstoel|wheelchair/.test(t)) return 'wheelchair';
        if (/huisdier|pet/.test(t)) return 'pets';
        if (/winter/.test(t)) return 'winter';
        if (/golf/.test(t)) return 'golf';
        if (/hand/.test(t)) return 'hand';
        if (/klein/.test(t)) return 'small';
        if (/groot|ruim/.test(t)) return 'large';
        return '';
    }

    function baggageIconSvg(key) {
        const k = String(key || '');
        if (k === 'wheelchair') {
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="5" r="2"/><path d="M10 8v5l4 5"/><path d="M7 13h6"/><circle cx="8" cy="18" r="2.5"/><circle cx="16.5" cy="18" r="2.5"/><path d="M10.5 15.5l3 3h4"/></svg>';
        }
        if (k === 'pets') {
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="7" cy="9" r="1.6"/><circle cx="11" cy="7.5" r="1.6"/><circle cx="15.5" cy="7.5" r="1.6"/><circle cx="19" cy="9" r="1.6"/><path d="M8.5 14c0-2 2-3.5 4-3.5s4 1.5 4 3.5-2.2 4-4 4-4-2-4-4z"/></svg>';
        }
        if (k === 'hand') {
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 10V8a4 4 0 0 1 8 0v2"/><rect x="6" y="10" width="12" height="10" rx="2"/><path d="M10 14h4"/></svg>';
        }
        if (k === 'small') {
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8V6.5A2.5 2.5 0 0 1 10.5 4h3A2.5 2.5 0 0 1 16 6.5V8"/><rect x="5" y="8" width="14" height="12" rx="2"/><path d="M5 13h14"/></svg>';
        }
        if (k === 'winter' || k === 'golf') {
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><rect x="6" y="7" width="12" height="13" rx="2"/><path d="M6 12h12"/><path d="M12 7v13"/></svg>';
        }
        // large / default suitcase
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 7V5.5A2.5 2.5 0 0 1 10.5 3h3A2.5 2.5 0 0 1 16 5.5V7"/><rect x="4" y="7" width="16" height="13" rx="2"/><path d="M4 12h16"/><path d="M9 12v4"/><path d="M15 12v4"/></svg>';
    }

    function rideStopsHtml(from, to) {
        const pickup = splitRideAddress(from);
        const dropoff = splitRideAddress(to);
        function stop(kind, label, parts) {
            return '<div class="ride-stop">'
                + '<div class="ride-stop__head">'
                + '<span class="ride-stop__dot is-' + kind + '"></span>'
                + '<span class="ride-stop__label">' + escapeHtml(label) + '</span>'
                + '</div>'
                + '<p class="ride-stop__main">' + escapeHtml(parts.main) + '</p>'
                + (parts.sub ? ('<p class="ride-stop__sub">' + escapeHtml(parts.sub) + '</p>') : '')
                + '</div>';
        }
        return '<div class="ride-stops">'
            + stop('pickup', 'Ophalen', pickup)
            + stop('dropoff', 'Afzetten', dropoff)
            + '</div>';
    }

    function rideStatsHtml(ride) {
        const distance = formatTripDistance(ride && ride.distance_km);
        const duration = formatTripDuration(ride && ride.duration_seconds);
        const passengers = ride && ride.passengers != null ? Number(ride.passengers) : 0;
        const chipRows = baggageChipRows(ride);
        if (!distance && !duration && !(passengers > 0) && !chipRows.length) return '';
        function metric(icon, value, label) {
            if (!value) return '';
            return '<div class="ride-stats__metric">'
                + icon
                + '<strong>' + escapeHtml(value) + '</strong>'
                + '<span>' + escapeHtml(label) + '</span>'
                + '</div>';
        }
        const metrics = [
            metric(statsIcon('navigate'), distance, 'afstand'),
            metric(statsIcon('time'), duration, 'rijtijd'),
            metric(statsIcon('people'), passengers > 0 ? String(passengers) : '', passengers === 1 ? 'persoon' : 'personen'),
        ].filter(Boolean).join('<span class="ride-stats__divider"></span>');
        const bags = chipRows.length
            ? ('<div class="ride-stats__bags' + (metrics ? ' is-spaced' : '') + '">'
                + '<p class="ride-stats__bags-title">Bagage</p>'
                + '<div class="ride-stats__chips">'
                + chipRows.map(function (row) {
                    const iconKey = row.key || baggageKeyFromLabel(row.label);
                    return '<span class="ride-stats__chip">'
                        + baggageIconSvg(iconKey)
                        + '<span>' + escapeHtml(row.label + ' × ' + row.qty) + '</span>'
                        + '</span>';
                }).join('')
                + '</div></div>')
            : '';
        return '<div class="ride-stats">'
            + (metrics ? ('<div class="ride-stats__metrics">' + metrics + '</div>') : '')
            + bags
            + '</div>';
    }

    function statsIcon(name) {
        if (name === 'time') {
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg>';
        }
        if (name === 'people') {
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><circle cx="17" cy="9" r="2.2"/><path d="M16 19a4.2 4.2 0 0 1 5-4"/></svg>';
        }
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l6 14-6-3-6 3z"/></svg>';
    }

    const SEARCHING_MESSAGES = ['We zoeken een taxi', 'Wacht op bevestiging'];
    let searchingTypeState = {
        active: false,
        messageIndex: 0,
        typed: '',
        dots: 0,
        holding: false,
        typeTimer: null,
        dotsTimer: null,
        holdTimer: null,
    };

    function clearSearchingTypeTimers() {
        if (searchingTypeState.typeTimer) clearInterval(searchingTypeState.typeTimer);
        if (searchingTypeState.dotsTimer) clearInterval(searchingTypeState.dotsTimer);
        if (searchingTypeState.holdTimer) clearTimeout(searchingTypeState.holdTimer);
        searchingTypeState.typeTimer = null;
        searchingTypeState.dotsTimer = null;
        searchingTypeState.holdTimer = null;
    }

    function searchingTypedNodes() {
        return Array.prototype.slice.call(document.querySelectorAll('.js-searching-typed')).filter(function (node) {
            const liveWrap = node.closest('#live-searching-title');
            if (liveWrap && liveWrap.hidden) return false;
            return true;
        });
    }

    function paintSearchingTyped() {
        const text = searchingTypeState.typed
            + (searchingTypeState.holding ? '.'.repeat(Math.max(1, searchingTypeState.dots)) : '');
        // Non-breaking space houdt de regelhoogte vast bij leeg / wissel
        const paint = text || '\u00A0';
        searchingTypedNodes().forEach(function (node) {
            node.textContent = paint;
        });
    }

    function formatEuroNl(amount) {
        const n = Number(amount);
        if (!Number.isFinite(n)) return '';
        try {
            return new Intl.NumberFormat('nl-NL', {
                style: 'currency',
                currency: 'EUR',
            }).format(n);
        } catch (e) {
            return '€ ' + n.toFixed(2).replace('.', ',');
        }
    }

    function baggageSummaryFromRide(ride) {
        if (!ride) return '';
        if (ride.baggage && ride.baggage.summary) return String(ride.baggage.summary);
        return String(ride.baggage_summary || '').trim();
    }

    function stopSearchingTypewriter() {
        searchingTypeState.active = false;
        clearSearchingTypeTimers();
        searchingTypeState.typed = '';
        searchingTypeState.dots = 0;
        searchingTypeState.holding = false;
        searchingTypeState.messageIndex = 0;
    }

    function runSearchingTypeCycle() {
        clearSearchingTypeTimers();
        const full = SEARCHING_MESSAGES[searchingTypeState.messageIndex % SEARCHING_MESSAGES.length];
        searchingTypeState.typed = '';
        searchingTypeState.dots = 0;
        searchingTypeState.holding = false;
        paintSearchingTyped();
        let i = 0;
        searchingTypeState.typeTimer = setInterval(function () {
            if (!searchingTypeState.active) {
                clearSearchingTypeTimers();
                return;
            }
            i += 1;
            searchingTypeState.typed = full.slice(0, i);
            paintSearchingTyped();
            if (i >= full.length) {
                clearInterval(searchingTypeState.typeTimer);
                searchingTypeState.typeTimer = null;
                searchingTypeState.holding = true;
                searchingTypeState.dots = 1;
                paintSearchingTyped();
                searchingTypeState.dotsTimer = setInterval(function () {
                    if (!searchingTypeState.active) {
                        clearSearchingTypeTimers();
                        return;
                    }
                    searchingTypeState.dots = searchingTypeState.dots >= 3 ? 1 : searchingTypeState.dots + 1;
                    paintSearchingTyped();
                }, 420);
                searchingTypeState.holdTimer = setTimeout(function () {
                    if (!searchingTypeState.active) return;
                    searchingTypeState.messageIndex = (searchingTypeState.messageIndex + 1) % SEARCHING_MESSAGES.length;
                    runSearchingTypeCycle();
                }, 3000);
            }
        }, 38);
    }

    function syncSearchingTypewriter() {
        const nodes = searchingTypedNodes();
        if (!nodes.length) {
            stopSearchingTypewriter();
            return;
        }
        if (!searchingTypeState.active) {
            searchingTypeState.active = true;
            searchingTypeState.messageIndex = 0;
            runSearchingTypeCycle();
            return;
        }
        paintSearchingTyped();
    }

    function setLiveSearchingTitleVisible(visible) {
        const wrap = el('live-searching-title');
        const title = el('live-title');
        if (wrap) wrap.hidden = !visible;
        if (title) title.hidden = !!visible;
        syncSearchingTypewriter();
    }

    function ridePhaseFromStatus(status, fallback) {
        const s = String(status || '').toLowerCase();
        if (fallback && isActiveRidePhase(fallback)) return fallback;
        if (s === 'cancelled') return 'cancelled';
        if (s === 'completed') return 'completed';
        if (['accepted', 'assigned'].indexOf(s) >= 0) return 'accepted';
        if (s === 'pending_payment') return 'awaiting_payment';
        if (['pending_dispatch', 'offered', 'quoted'].indexOf(s) >= 0) return 'searching';
        if (fallback) return fallback;
        return 'other';
    }

    function normalizeRideCard(raw) {
        const phase = ridePhaseFromStatus(raw.phase || raw.status, raw.phase);
        const amount = raw.amount
            || (raw.quoted_price != null ? formatEuroNl(raw.quoted_price) : '');
        const distanceKm = raw.distance_km != null ? Number(raw.distance_km) : null;
        const durationSeconds = raw.duration_seconds != null ? Number(raw.duration_seconds) : null;
        return {
            id: raw.id || 0,
            token: raw.token || '',
            from: raw.from || raw.pickup_address || '',
            to: raw.to || raw.dropoff_address || '',
            at: raw.at || formatRideWhen(raw.at_ts || raw.pickup_at_iso) || '',
            at_ts: raw.at_ts || null,
            pickup_at: raw.pickup_at || raw.pickup_at_label || raw.pickup_at_iso || '',
            phase: phase,
            status: raw.status || '',
            status_label: raw.status_label || '',
            amount: amount,
            quoted_price: raw.quoted_price != null ? Number(raw.quoted_price) : null,
            passengers: raw.passengers != null ? Number(raw.passengers) : null,
            baggage_summary: baggageSummaryFromRide(raw) || '',
            baggage: raw.baggage || null,
            distance_km: Number.isFinite(distanceKm) ? distanceKm : null,
            duration_seconds: Number.isFinite(durationSeconds) ? durationSeconds : null,
            payment_failure_status: raw.payment_failure_status || null,
            can_download_invoice: !!(raw.can_download_invoice || phase === 'completed'),
            can_cancel: !!raw.can_cancel,
            pickup_confirmation: raw.pickup_confirmation || null,
            active: isActiveRidePhase(phase),
        };
    }

    function ridePillClass(phase) {
        if (phase === 'searching') return 'is-searching';
        if (phase === 'accepted') return 'is-accepted';
        if (phase === 'cancelled') return 'is-cancelled';
        if (phase === 'completed') return 'is-done';
        return 'is-done';
    }

    function ridePillLabel(ride) {
        if (ride.phase === 'awaiting_payment') {
            if (ride.payment_failure_status || (ride.status_label && /mislukt/i.test(ride.status_label))) {
                return 'Betaling mislukt';
            }
            return 'Wacht op betaling';
        }
        if (ride.phase === 'searching') return 'Zoeken';
        if (ride.phase === 'accepted') return 'Geaccepteerd';
        if (ride.phase === 'cancelled') return 'Geannuleerd';
        if (ride.phase === 'completed') return 'Afgerond';
        if (ride.status_label) return ride.status_label;
        return 'Rit';
    }

    function invoiceIconSvg() {
        return '<svg viewBox="0 0 24 24" aria-hidden="true">'
            + '<path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/>'
            + '<path d="M14 3v5h5"/>'
            + '<path d="M9 13h6"/>'
            + '<path d="M9 17h6"/>'
            + '</svg>';
    }

    function archiveIconSvg() {
        return '<svg viewBox="0 0 24 24" aria-hidden="true">'
            + '<path d="M3 7h18v3H3z"/>'
            + '<path d="M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9"/>'
            + '<path d="M10 14h4"/>'
            + '</svg>';
    }

    function unarchiveIconSvg() {
        return '<svg viewBox="0 0 24 24" aria-hidden="true">'
            + '<path d="M3 7h18v3H3z"/>'
            + '<path d="M5 10v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-9"/>'
            + '<path d="M12 13v5"/>'
            + '<path d="M9 15l3-3 3 3"/>'
            + '</svg>';
    }

    function updateRidesScreenTitle() {
        const title = document.querySelector('#screen-rides .app-nav-title');
        if (title) title.textContent = state.showArchivedRides ? 'Archief' : 'Ritten';
        const archiveBack = el('btn-rides-book');
        if (archiveBack) archiveBack.hidden = !state.showArchivedRides;
    }

    function isFollowingAssignedTaxi(ride) {
        const current = ride || state.liveRide;
        return !!(
            state.trackToken
            && current
            && current.accepted
            && isActiveRidePhase(current.phase)
        );
    }

    function parkFollowBanner() {
        const banner = el('live-banner');
        const park = el('live-banner-park');
        if (banner && park && banner.parentNode !== park) {
            park.appendChild(banner);
        }
    }

    function mountExpandedFollow() {
        const banner = el('live-banner');
        const token = state.expandedRideToken;
        if (!banner) return;
        if (!token) {
            parkFollowBanner();
            return;
        }
        const host = document.querySelector('.ride-follow-host[data-token="' + token.replace(/"/g, '') + '"]');
        if (host && banner.parentNode !== host) {
            host.appendChild(banner);
        }
    }

    function syncMapFitButton() {
        const fit = el('map-zoom-fit');
        if (!fit) return;
        const following = isFollowingAssignedTaxi();
        const loc = state.liveRide && state.liveRide.vehicle_location;
        const showFollow = !!(following && loc && isFinite(Number(loc.lat)) && isFinite(Number(loc.lng)));
        const showRoute = !!(state.pickup && state.dropoff && !following);
        fit.hidden = !(showRoute || showFollow);
        fit.setAttribute('aria-label', showFollow ? 'Volg taxi' : 'Toon route');
    }

    function placeBookTaxiMarker(lat, lng) {
        if (!state.map || !window.google || !google.maps) return;
        const pos = { lat: lat, lng: lng };
        if (!state.bookVehicleMarker) {
            state.bookVehicleMarker = new google.maps.Marker({
                map: state.map,
                title: 'Jouw taxi',
                zIndex: 20,
                icon: {
                    url: taxiIconUrl('sedan'),
                    scaledSize: new google.maps.Size(28, 38),
                    anchor: new google.maps.Point(14, 19),
                },
            });
            tintedTaxiIconUrl('sedan', function (url) {
                if (!state.bookVehicleMarker) return;
                state.bookVehicleMarker.setIcon({
                    url: url,
                    scaledSize: new google.maps.Size(28, 38),
                    anchor: new google.maps.Point(14, 19),
                });
            });
        }
        state.bookVehicleMarker.setPosition(pos);
        state.bookVehicleMarker.setMap(state.map);
    }

    function syncBookFollow(ride) {
        const following = isFollowingAssignedTaxi(ride);
        const status = el('map-fleet-status');
        if (!following) {
            state.followDidCenter = false;
            if (state.bookVehicleMarker) state.bookVehicleMarker.setMap(null);
            if (state.routeLine && state.map && state.pickup && state.dropoff) {
                state.routeLine.setMap(state.map);
            }
            if (state.screen === 'book' && !state.fleetTimer) startFleetPolling();
            syncMapFitButton();
            return;
        }
        stopFleetPolling();
        clearFleetOverlays();
        if (state.fleetRadiusCircle) state.fleetRadiusCircle.setMap(null);
        if (state.routeLine) state.routeLine.setMap(null);
        const loc = ride && ride.vehicle_location;
        const lat = loc ? Number(loc.lat) : NaN;
        const lng = loc ? Number(loc.lng) : NaN;
        const hasLoc = Number.isFinite(lat) && Number.isFinite(lng) && !(lat === 0 && lng === 0);
        if (status) {
            status.textContent = hasLoc ? 'Jouw taxi · live volgen' : 'Chauffeur gekoppeld…';
        }
        if (hasLoc) {
            placeBookTaxiMarker(lat, lng);
            if (state.map && !state.followDidCenter) {
                state.followDidCenter = true;
                state.map.panTo({ lat: lat, lng: lng });
                state.map.setZoom(15);
            }
        }
        syncMapFitButton();
    }

    function zoomBookMap(delta) {
        if (!state.map || typeof state.map.getZoom !== 'function') return;
        const next = Math.max(3, Math.min(20, (state.map.getZoom() || 14) + delta));
        state.map.setZoom(next);
    }

    function setPassengerCount(count) {
        const next = Math.max(1, Math.min(20, count));
        const input = el('passengers');
        if (input) input.value = String(next);
        const label = el('passenger-qty');
        if (label) label.textContent = String(next);
        const minus = el('passenger-minus');
        const plus = el('passenger-plus');
        if (minus) minus.disabled = next <= 1;
        if (plus) plus.disabled = next >= 20;
    }

    function pickupConfirmHtml(ride, token) {
        const confirm = ride && ride.pickup_confirmation;
        if (!confirm || !confirm.can_answer) return '';
        const prompt = confirm.prompt || 'De chauffeur zegt dat je bent opgehaald. Klopt dat?';
        const track = String(token || ride.token || '');
        return '<div class="pickup-confirm">'
            + '<p class="pickup-confirm__text">' + escapeHtml(prompt) + '</p>'
            + '<div class="pickup-confirm__actions">'
            + '<button type="button" class="btn btn-primary" data-pickup-answer="yes" data-pickup-token="'
            + escapeHtml(track) + '">Ja</button>'
            + '<button type="button" class="btn btn-outline" data-pickup-answer="no" data-pickup-token="'
            + escapeHtml(track) + '">Nee</button>'
            + '</div></div>';
    }

    async function submitPickupAnswer(token, answer) {
        const track = String(token || '').trim();
        if (!track || (answer !== 'yes' && answer !== 'no')) return;
        try {
            const data = await api(cfg.apiBase + '/live/pickup-confirm', {
                method: 'POST',
                body: JSON.stringify({ token: track, answer: answer }),
            });
            if (data && data.ride) {
                if (state.trackToken === track) renderLive(data.ride);
                updateGuestRideByToken(track, {
                    pickup_confirmation: data.ride.pickup_confirmation || null,
                    phase: data.ride.phase || undefined,
                    status_label: data.ride.status_label || undefined,
                });
                syncRideCardFromLive(track, data.ride);
            }
            if (data && data.message) toast(data.message);
        } catch (e) {
            toast((e && e.message) || 'Bevestigen mislukt.');
        }
    }

    function renderSearchingRideBlockHtml(ride) {
        const token = String(ride.token || '');
        const when = formatRideWhen(ride.pickup_at || (typeof ride.at === 'string' ? ride.at : ''));
        const priceLabel = ride.quoted_price != null
            ? formatEuroNl(ride.quoted_price)
            : (ride.amount ? String(ride.amount) : '');
        const isLiveHost = !!(token && (state.expandedRideToken === token || state.trackToken === token));
        const attrs = token
            ? (' data-token="' + escapeHtml(token) + '"')
            : (' data-id="' + escapeHtml(String(ride.id || '')) + '"');

        // Actuele rit: alleen de live-bannerbox (geen buitenste kaart eromheen)
        if (isLiveHost && token) {
            return '<article class="ride-card ride-card--live-only is-expanded"'
                + attrs + ' data-live-host="1" aria-expanded="true">'
                + '<div class="ride-follow-host" data-token="' + escapeHtml(token) + '"></div>'
                + '</article>';
        }

        const metaHtml = (when || priceLabel)
            ? ('<div class="live-meta"><div class="live-meta__row">'
                + '<div><p class="ride-card__stop-label">Datum</p>'
                + '<p class="ride-card__when">' + escapeHtml(when || '—') + '</p></div>'
                + (priceLabel
                    ? ('<div><p class="ride-card__stop-label">Prijs</p>'
                        + '<p class="ride-card__stop-value">' + escapeHtml(priceLabel) + '</p></div>')
                    : '')
                + '</div></div>')
            : '';

        return '<article class="ride-card ride-card--searching-full"' + attrs + '>'
            + '<div class="status-pill is-searching">Zoeken…</div>'
            + '<p class="ride-card__title ride-card__title--searching">'
            + '<span class="searching-title__dot" aria-hidden="true"></span>'
            + '<span class="js-searching-typed" aria-label="'
            + escapeHtml(SEARCHING_MESSAGES.join('. ') + '.')
            + '">\u00A0</span></p>'
            + '<p class="live-search-desc">Je rit is aangeboden aan aangesloten taxibedrijven in de buurt.</p>'
            + metaHtml
            + rideStopsHtml(ride.from, ride.to)
            + rideStatsHtml(ride)
            + (token
                ? ('<button type="button" class="btn btn-danger ride-card__cancel" data-ride-cancel-token="'
                    + escapeHtml(token) + '">Rit annuleren</button>')
                : '')
            + '</article>';
    }

    function renderRideCardHtml(ride, options) {
        const opts = options || {};
        const showArchiveAction = !!opts.archiveAction;
        const token = String(ride.token || '');
        const expanded = !!(token && state.expandedRideToken === token);
        const from = shortAddress(ride.from);
        const to = shortAddress(ride.to);
        const when = formatRideWhen(ride.pickup_at || (typeof ride.at === 'string' ? ride.at : ''));
        const pill = ridePillLabel(ride);
        const amount = ride.amount ? String(ride.amount) : '';
        const searching = ride.phase === 'searching';

        if (searching) {
            return renderSearchingRideBlockHtml(ride);
        }

        const title = ride.phase === 'awaiting_payment'
            ? 'Wacht op betaling'
            : (ride.status_label || pill);
        const attrs = token
            ? (' data-token="' + escapeHtml(token) + '"')
            : (' data-id="' + escapeHtml(String(ride.id || '')) + '"');

        // Geaccepteerde rit open: één live-banner (native-layout), geen dubbele kaart
        if (ride.phase === 'accepted' && expanded && token) {
            return '<article class="ride-card ride-card--live-only is-expanded is-accepted"'
                + attrs + ' data-live-host="1" aria-expanded="true">'
                + '<button type="button" class="ride-card__collapse" data-ride-collapse="'
                + escapeHtml(token) + '" aria-label="Inklappen">'
                + rideChevronSvg(true)
                + '</button>'
                + '<div class="ride-follow-host" data-token="' + escapeHtml(token) + '"></div>'
                + '</article>';
        }

        const invoiceBtn = ride.can_download_invoice
            ? ('<button type="button" class="ride-card__invoice-icon" aria-label="Factuur downloaden"'
                + ' data-invoice="1" data-ride-id="' + escapeHtml(String(ride.id || '')) + '"'
                + (ride.token ? (' data-invoice-token="' + escapeHtml(ride.token) + '"') : '')
                + '>' + invoiceIconSvg() + '</button>')
            : '';
        const archiveBtn = showArchiveAction
            ? ('<button type="button" class="ride-card__archive-icon" aria-label="'
                + (state.showArchivedRides ? 'Terugzetten uit archief' : 'Archiveren') + '"'
                + ' data-archive="' + (state.showArchivedRides ? '0' : '1') + '"'
                + ' data-ride-id="' + escapeHtml(String(ride.id || '')) + '"'
                + (ride.token ? (' data-archive-token="' + escapeHtml(ride.token) + '"') : '')
                + '>'
                + (state.showArchivedRides ? unarchiveIconSvg() : archiveIconSvg())
                + '</button>')
            : '';

        const expandedBlock = expanded
            ? ('<div class="ride-card__expanded">'
                + rideStopsHtml(ride.from, ride.to)
                + rideStatsHtml(ride)
                + (amount
                    ? ('<p class="ride-card__stop-label" style="margin-top:10px">Prijs</p>'
                        + '<p class="ride-card__stop-value">' + escapeHtml(amount) + '</p>')
                    : '')
                + (token
                    ? ('<div class="ride-follow-host" data-token="' + escapeHtml(token) + '"></div>')
                    : '')
                + '</div>')
            : '';

        const footMeta = '<div class="ride-card__foot ride-card__foot--meta">'
            + '<div><p class="ride-card__stop-label">Datum</p>'
            + '<p class="ride-card__when">' + escapeHtml(when || '—') + '</p></div>'
            + (amount
                ? ('<div><p class="ride-card__stop-label">Prijs</p>'
                    + '<p class="ride-card__stop-value">' + escapeHtml(amount) + '</p></div>')
                : '')
            + '</div>';

        return '<article class="ride-card'
            + (ride.phase === 'accepted' ? ' is-accepted' : '')
            + (ride.phase === 'cancelled' ? ' is-cancelled' : '')
            + (expanded ? ' is-expanded' : '')
            + '"' + attrs + ' role="button" tabindex="0" aria-expanded="' + (expanded ? 'true' : 'false') + '">'
            + '<div class="ride-card__top">'
            + '<span class="ride-card__status">'
            + '<span class="ride-card__pill ' + ridePillClass(ride.phase) + '">'
            + escapeHtml(pill)
            + '</span></span>'
            + '<span class="ride-card__status">'
            + invoiceBtn
            + archiveBtn
            + '<span class="ride-card__chevron" aria-hidden="true">' + rideChevronSvg(expanded) + '</span>'
            + '</span></div>'
            + (ride.phase === 'cancelled' ? '' : ('<p class="ride-card__title">' + escapeHtml(title) + '</p>'))
            + pickupConfirmHtml(ride, token)
            + '<p class="ride-card__line">' + escapeHtml(from) + ' → ' + escapeHtml(to) + '</p>'
            + (ride.rating && ride.rating.rated && ride.rating.stars
                ? ('<p class="ride-card__rating" style="margin:8px 0 0;font-size:.85rem;color:var(--muted)">'
                    + 'Jouw beoordeling: ' + '★'.repeat(Number(ride.rating.stars))
                    + '☆'.repeat(Math.max(0, 5 - Number(ride.rating.stars)))
                    + (ride.rating.comment ? (' — ' + escapeHtml(String(ride.rating.comment))) : '')
                    + '</p>')
                : '')
            + footMeta
            + expandedBlock
            + '</article>';
    }

    function renderRidesEmptyHtml(message) {
        const opts = message || {};
        const title = opts.title || 'Nog geen ritten';
        const text = opts.text || 'Nog geen ritten. Boek een taxi via Boeken; openstaande en eerdere ritten zie je hier.';
        const showBook = opts.showBook !== false;
        return '<div class="rides-empty">'
            + '<div class="rides-empty__icon" aria-hidden="true">'
            + '<svg viewBox="0 0 24 24"><path d="M8 17h8"/><path d="M5 17l1.5-8h11L19 17"/>'
            + '<path d="M7 17a1.5 1.5 0 1 0 0 .01"/><path d="M17 17a1.5 1.5 0 1 0 0 .01"/>'
            + '<path d="M7 9l1-3h8l1 3"/></svg></div>'
            + '<h3 class="rides-empty__title">' + escapeHtml(title) + '</h3>'
            + '<p class="rides-empty__text">' + escapeHtml(text) + '</p>'
            + (showBook
                ? '<button type="button" class="btn btn-primary" id="rides-empty-book">Taxi boeken</button>'
                : '')
            + '</div>';
    }

    function bindRideListClicks(box) {
        if (!box) return;
        const emptyBook = el('rides-empty-book');
        if (emptyBook) {
            emptyBook.addEventListener('click', function () { enterBook(); });
        }
        const openArchive = el('rides-open-archive');
        if (openArchive) {
            openArchive.addEventListener('click', function () {
                state.showArchivedRides = true;
                renderRidesOverview(state.ridesCache);
            });
        }
        box.querySelectorAll('.ride-card__invoice-icon').forEach(function (btn) {
            btn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                downloadRideInvoice({
                    id: btn.dataset.rideId || null,
                    token: btn.dataset.invoiceToken || '',
                    fromList: true,
                });
            });
        });
        box.querySelectorAll('.ride-card__archive-icon').forEach(function (btn) {
            btn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                const ride = {
                    id: btn.dataset.rideId || 0,
                    token: btn.dataset.archiveToken || '',
                };
                const archive = btn.dataset.archive === '1';
                if (!setRideArchived(ride, archive)) {
                    toast('Deze rit kan niet worden gearchiveerd.');
                    return;
                }
                toast(archive ? 'Rit gearchiveerd.' : 'Rit teruggezet.');
                renderRidesOverview(state.ridesCache);
            });
        });
        box.querySelectorAll('[data-ride-cancel-token]').forEach(function (btn) {
            btn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                cancelRideByToken(btn.getAttribute('data-ride-cancel-token'), btn);
            });
        });
        box.querySelectorAll('[data-ride-collapse]').forEach(function (btn) {
            btn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                state.expandedRideToken = '';
                renderRidesOverview(state.ridesCache);
            });
        });
        box.querySelectorAll('.ride-card[data-id], .ride-card[data-token]')
            .forEach(function (btn) {
                async function openRideCard() {
                    if (btn.classList.contains('ride-card--live-only')) return;
                    if (btn.dataset.token) {
                        if (btn.classList.contains('ride-card--searching-full')) {
                            setTrackToken(btn.dataset.token);
                            state.expandedRideToken = btn.dataset.token;
                            const guest = guestRides().find(function (r) { return r && r.token === btn.dataset.token; });
                            state.acceptedNotified = !!(guest && guest.phase && guest.phase !== 'searching');
                            state.livePhase = (guest && guest.phase) || null;
                            startLivePoll();
                            renderRidesOverview(state.ridesCache);
                            return;
                        }
                        if (state.expandedRideToken === btn.dataset.token) {
                            state.expandedRideToken = '';
                            renderRidesOverview(state.ridesCache);
                            return;
                        }
                        setTrackToken(btn.dataset.token);
                        state.expandedRideToken = btn.dataset.token;
                        const guest = guestRides().find(function (r) { return r && r.token === btn.dataset.token; });
                        state.acceptedNotified = !!(guest && guest.phase && guest.phase !== 'searching');
                        state.livePhase = (guest && guest.phase) || null;
                        startLivePoll();
                        renderRidesOverview(state.ridesCache);
                        return;
                    }
                    if (!btn.dataset.id) return;
                    try {
                        const detail = await api(cfg.apiBase + '/rides/' + btn.dataset.id);
                        if (detail.track_token) {
                            setTrackToken(detail.track_token);
                            saveGuestRide(
                                detail.live && detail.live.id ? detail.live.id : btn.dataset.id,
                                detail.track_token,
                                {
                                    from: detail.live && detail.live.pickup_address,
                                    to: detail.live && detail.live.dropoff_address,
                                    phase: detail.live && detail.live.phase,
                                    status_label: detail.live && detail.live.status_label,
                                }
                            );
                            openLive(detail.live);
                        } else {
                            toast('Deze rit heeft geen live-tracking.');
                        }
                    } catch (e) {
                        toast(e.message || 'Kon rit niet laden.');
                    }
                }
                btn.addEventListener('click', function (ev) {
                    if (ev.target && ev.target.closest && ev.target.closest(
                        '.ride-card__expanded, .ride-card__archive-icon, .ride-card__invoice-icon, .ride-card__collapse, [data-ride-cancel-token], [data-ride-collapse], .live-banner, .ride-follow-host, .pickup-confirm, [data-pickup-answer]'
                    )) {
                        return;
                    }
                    openRideCard();
                });
                btn.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Enter' || ev.key === ' ') {
                        ev.preventDefault();
                        openRideCard();
                    }
                });
            });
    }

    function syncRideCardFromLive(token, ride) {
        const key = String(token || '').trim();
        if (!key || !ride || !Array.isArray(state.ridesCache) || !state.ridesCache.length) return;
        if (state.screen !== 'rides' && state.screen !== 'live') return;
        let changed = false;
        const next = state.ridesCache.map(function (card) {
            if (!card || String(card.token || '') !== key) return card;
            const phase = ride.phase || card.phase;
            const label = ride.status_label || ride.status || card.status_label || '';
            const pickup = ride.pickup_at_label || ride.pickup_at || card.pickup_at || '';
            const distanceKm = ride.distance_km != null ? Number(ride.distance_km) : card.distance_km;
            const durationSeconds = ride.duration_seconds != null
                ? Number(ride.duration_seconds)
                : card.duration_seconds;
            const passengers = ride.passengers != null ? Number(ride.passengers) : card.passengers;
            const baggage = ride.baggage != null ? ride.baggage : card.baggage;
            const baggageSummary = baggageSummaryFromRide(ride) || card.baggage_summary || '';
            const amount = ride.quoted_price != null
                ? formatEuroNl(ride.quoted_price)
                : card.amount;
            const pickupConfirm = ride.pickup_confirmation || null;
            const prevConfirm = card.pickup_confirmation && card.pickup_confirmation.status;
            const nextConfirm = pickupConfirm && pickupConfirm.status;
            if (
                phase === card.phase
                && label === (card.status_label || '')
                && pickup === (card.pickup_at || '')
                && distanceKm === card.distance_km
                && durationSeconds === card.duration_seconds
                && passengers === card.passengers
                && baggageSummary === (card.baggage_summary || '')
                && prevConfirm === nextConfirm
                && !!(card.pickup_confirmation && card.pickup_confirmation.can_answer)
                    === !!(pickupConfirm && pickupConfirm.can_answer)
            ) {
                return card;
            }
            changed = true;
            return Object.assign({}, card, {
                phase: phase,
                status: ride.status || card.status,
                status_label: label,
                pickup_at: pickup,
                distance_km: distanceKm,
                duration_seconds: durationSeconds,
                passengers: passengers,
                baggage: baggage,
                baggage_summary: baggageSummary,
                quoted_price: ride.quoted_price != null ? Number(ride.quoted_price) : card.quoted_price,
                amount: amount,
                pickup_confirmation: pickupConfirm,
                active: isActiveRidePhase(phase),
            });
        });
        if (changed) renderRidesOverview(next);
    }

    function startOpenRidesPoll() {
        if (state.openRidesTimer) return;
        const tick = async () => {
            state.openRidesTimer = null;
            if (state.screen !== 'rides' && state.screen !== 'live') return;
            const rides = guestRides().filter(function (ride) {
                return ride && ride.token && (
                    isActiveRidePhase(ride.phase)
                    || (ride.pickup_confirmation && ride.pickup_confirmation.can_answer)
                );
            });
            for (let i = 0; i < rides.length; i += 1) {
                const ride = rides[i];
                if (state.trackToken && ride.token === state.trackToken && state.liveTimer) continue;
                try {
                    const data = await api(cfg.liveUrl + '?token=' + encodeURIComponent(ride.token));
                    if (data && data.ride) {
                        updateGuestRideByToken(ride.token, {
                            id: data.ride.id || undefined,
                            from: data.ride.pickup_address || undefined,
                            to: data.ride.dropoff_address || undefined,
                            phase: data.ride.phase || ride.phase,
                            status_label: data.ride.status_label || data.ride.status || '',
                            pickup_at: data.ride.pickup_at_label || data.ride.pickup_at || undefined,
                            rating: data.ride.rating || null,
                            pickup_confirmation: data.ride.pickup_confirmation || null,
                        });
                        syncRideCardFromLive(ride.token, data.ride);
                    }
                } catch (e) {
                    /* volgende rit */
                }
            }
            if (state.screen === 'rides' || state.screen === 'live') {
                state.openRidesTimer = setTimeout(tick, 2500);
            }
        };
        state.openRidesTimer = setTimeout(tick, 400);
    }

    function renderRidesOverview(rawRides) {
        const box = el('rides-list');
        if (!box) return;
        parkFollowBanner();
        const rides = (rawRides || []).map(normalizeRideCard);
        state.ridesCache = rides;
        updateRidesScreenTitle();

        if (state.showArchivedRides) {
            const archived = rides.filter(function (r) { return !r.active && isRideArchived(r); });
            if (!archived.length) {
                box.innerHTML = renderRidesEmptyHtml({
                    title: 'Archief is leeg',
                    text: 'Gearchiveerde ritten verschijnen hier. Tik op het archieficoon bij een eerdere rit om die te bewaren.',
                    showBook: false,
                });
                bindRideListClicks(box);
                refreshActiveRideUi();
                syncSearchingTypewriter();
                return;
            }
            let archiveHtml = '<div class="rides-section">Gearchiveerde ritten</div>';
            archiveHtml += archived.map(function (r) {
                return renderRideCardHtml(r, { archiveAction: true });
            }).join('');
            box.innerHTML = archiveHtml;
            bindRideListClicks(box);
            mountExpandedFollow();
            refreshActiveRideUi();
            syncSearchingTypewriter();
            return;
        }

        if (!rides.length) {
            box.innerHTML = renderRidesEmptyHtml();
            bindRideListClicks(box);
            refreshActiveRideUi();
            syncSearchingTypewriter();
            return;
        }
        const active = rides.filter(function (r) { return r.active; });
        const past = rides.filter(function (r) { return !r.active; });
        const visiblePast = past.filter(function (r) { return !isRideArchived(r); });
        const archivedPast = past.filter(function (r) { return isRideArchived(r); });
        ensureSearchingLiveHost(active);
        let html = '';
        if (active.length) {
            html += '<div class="rides-section">Openstaand</div>';
            html += active.map(function (r) { return renderRideCardHtml(r); }).join('');
        }
        if (visiblePast.length) {
            html += '<div class="rides-section">' + (active.length ? 'Eerdere ritten' : 'Alle ritten') + '</div>';
            html += visiblePast.map(function (r) {
                return renderRideCardHtml(r, { archiveAction: true });
            }).join('');
        }
        if (archivedPast.length) {
            html += '<button type="button" class="rides-archive-link" id="rides-open-archive">'
                + '<span class="rides-archive-link__meta">Archief · ' + archivedPast.length
                + (archivedPast.length === 1 ? ' rit' : ' ritten') + '</span>'
                + '<span class="rides-archive-link__cta">Bekijken →</span>'
                + '</button>';
        }
        if (!html) {
            html = renderRidesEmptyHtml();
        }
        box.innerHTML = html;
        bindRideListClicks(box);
        mountExpandedFollow();
        refreshActiveRideUi();
        syncSearchingTypewriter();
    }

    async function loadRides() {
        parkFollowBanner();
        const box = el('rides-list');
        if (state.token) {
            try {
                box.innerHTML = '<div class="rides-loading"><span class="pulse"></span>Ritten laden…</div>';
                const data = await api(cfg.apiBase + '/rides');
                const rides = (data.rides || []).map(function (r) {
                    return {
                        id: r.id,
                        from: r.from,
                        to: r.to,
                        at: r.at,
                        status: r.status,
                        status_label: r.status_label,
                        amount: r.amount,
                        phase: ridePhaseFromStatus(r.status),
                        can_download_invoice: !!r.can_download_invoice || r.status === 'completed',
                        can_cancel: !!r.can_cancel,
                    };
                });
                renderRidesOverview(rides);
                return;
            } catch (e) {
                if (e.status === 401) {
                    setToken('');
                    setMode('guest');
                }
            }
        }

        let guests = guestRides().filter(function (r) { return r && r.token; });
        if (!guests.length) {
            renderRidesOverview([]);
            return;
        }

        box.innerHTML = '<div class="rides-loading"><span class="pulse"></span>Ritten laden…</div>';
        const refreshed = await Promise.all(guests.slice(0, 12).map(async function (r) {
            try {
                const data = await api(cfg.liveUrl + '?token=' + encodeURIComponent(r.token));
                const ride = data && data.ride;
                if (!ride) return r;
                const amount = ride.quoted_price != null
                    ? formatEuroNl(ride.quoted_price)
                    : (r.amount || '');
                const next = {
                    id: ride.id || r.id,
                    token: r.token,
                    at: r.at || Date.now(),
                    from: ride.pickup_address || r.from || '',
                    to: ride.dropoff_address || r.to || '',
                    phase: ride.phase || r.phase || 'other',
                    status_label: ride.status_label || r.status_label || '',
                    payment_failure_status: ride.payment_failure_status || r.payment_failure_status || null,
                    amount: amount,
                    quoted_price: ride.quoted_price != null ? Number(ride.quoted_price) : r.quoted_price,
                    passengers: ride.passengers != null ? Number(ride.passengers) : r.passengers,
                    baggage_summary: baggageSummaryFromRide(ride) || r.baggage_summary || '',
                    baggage: ride.baggage || r.baggage || null,
                    distance_km: ride.distance_km != null ? Number(ride.distance_km) : (r.distance_km != null ? r.distance_km : null),
                    duration_seconds: ride.duration_seconds != null ? Number(ride.duration_seconds) : (r.duration_seconds != null ? r.duration_seconds : null),
                    rating: ride.rating || r.rating || null,
                    pickup_at: ride.pickup_at_label || ride.pickup_at || r.pickup_at || '',
                };
                updateGuestRideByToken(r.token, next);
                return next;
            } catch (e) {
                return r;
            }
        }));

        guests = refreshed.concat(guests.slice(12));
        writeGuestRides(guests);
        renderRidesOverview(guests.map(function (r) {
            return {
                id: r.id,
                token: r.token,
                from: r.from,
                to: r.to,
                at_ts: r.at,
                status_label: r.status_label,
                phase: r.phase,
                payment_failure_status: r.payment_failure_status || null,
                amount: r.amount || '',
                quoted_price: r.quoted_price,
                passengers: r.passengers,
                baggage_summary: r.baggage_summary || '',
                baggage: r.baggage || null,
                distance_km: r.distance_km,
                duration_seconds: r.duration_seconds,
                rating: r.rating || null,
                pickup_at: r.pickup_at || '',
                can_download_invoice: r.phase === 'completed',
            };
        }));
    }

    function syncThemeRows() {
        const pref = (typeof window.nexaPwaThemePref === 'function' && window.nexaPwaThemePref())
            || document.documentElement.getAttribute('data-theme-pref')
            || 'dark';
        document.querySelectorAll('[data-theme-choice]').forEach(function (btn) {
            btn.classList.toggle('is-on', btn.getAttribute('data-theme-choice') === pref);
        });
    }

    function showProfile() {
        stopFleetPolling();
        showScreen('profile');
        const guest = !state.token;
        if (guest) {
            try {
                const d = JSON.parse(localStorage.getItem(PROFILE_DRAFT_KEY) || '{}');
                if (el('prof-first')) el('prof-first').value = d.first_name || (el('first-name') && el('first-name').value) || '';
                if (el('prof-last')) el('prof-last').value = d.last_name || (el('last-name') && el('last-name').value) || '';
                if (el('prof-phone')) el('prof-phone').value = d.phone || (el('phone') && el('phone').value) || '';
                if (el('prof-email')) {
                    el('prof-email').readOnly = false;
                    el('prof-email').value = d.email || (el('email') && el('email').value) || '';
                }
            } catch (e) {}
        } else if (state.user) {
            applyUserToForm(state.user);
            if (el('prof-email')) el('prof-email').readOnly = true;
        }
        const emailHint = el('prof-email-hint');
        if (emailHint) emailHint.hidden = guest;
        setProfileEditMode(true);
        if (el('profile-guest')) el('profile-guest').hidden = !guest;
        if (el('profile-account-actions')) el('profile-account-actions').hidden = guest;
        const cancel = el('btn-cancel-profile');
        if (cancel) cancel.hidden = guest;
        syncThemeRows();
    }

    async function bootAuth() {
        if (!state.token) return false;
        try {
            const data = await api(cfg.apiBase + '/me');
            state.user = data.user;
            setMode('auth');
            applyUserToForm(state.user);
            return true;
        } catch (e) {
            setToken('');
            return false;
        }
    }

    async function enterBook() {
        showScreen('book');
        setPickupAtValue(defaultPickupAt(), { silent: true });
        if (state.user) applyUserToForm(state.user);
        else fillProfileDraft();
        await ensureBookMap();
        if (isFollowingAssignedTaxi()) syncBookFollow(state.liveRide);
        else startFleetPolling();
        refreshActiveRideUi();
        syncMapFitButton();
        if (!state.pickup && !isFollowingAssignedTaxi()) detectLocation();
        if (window.Notification && Notification.permission === 'default') {
            try { Notification.requestPermission(); } catch (e) {}
        }
    }

    // Events
    el('btn-guest').addEventListener('click', () => {
        setMode('guest');
        enterBook();
    });
    el('btn-show-login').addEventListener('click', () => {
        showScreen('auth');
        el('auth-title').textContent = 'Inloggen';
        el('auth-login-form').hidden = false;
        el('auth-register-form').hidden = true;
    });
    el('btn-auth-back').addEventListener('click', () => showScreen('welcome'));
    const welcomeBack = el('btn-welcome-back');
    if (welcomeBack) {
        welcomeBack.addEventListener('click', () => {
            window.location.href = cfg.launcherUrl + '?switch=1';
        });
    }
    const profileBack = el('btn-profile-back');
    if (profileBack) {
        profileBack.addEventListener('click', () => {
            window.location.href = cfg.launcherUrl + '?switch=1';
        });
    }
    const ridesHome = el('btn-rides-home');
    if (ridesHome) {
        ridesHome.addEventListener('click', () => {
            window.location.href = cfg.launcherUrl + '?switch=1';
        });
    }
    el('btn-show-register').addEventListener('click', () => {
        el('auth-title').textContent = 'Registreren';
        el('auth-login-form').hidden = true;
        el('auth-register-form').hidden = false;
    });
    el('btn-show-login2').addEventListener('click', () => {
        el('auth-title').textContent = 'Inloggen';
        el('auth-login-form').hidden = false;
        el('auth-register-form').hidden = true;
    });
    el('btn-login').addEventListener('click', async () => {
        try {
            const data = await api(cfg.loginUrl, {
                method: 'POST',
                body: JSON.stringify({
                    email: el('login-email').value.trim(),
                    password: el('login-password').value,
                }),
            });
            setToken(data.token);
            setMode('auth');
            state.user = data.user;
            applyUserToForm(data.user);
            toast('Ingelogd.');
            enterBook();
        } catch (e) {
            toast(e.message || 'Inloggen mislukt.');
        }
    });
    el('btn-register').addEventListener('click', async () => {
        try {
            const data = await api(cfg.registerUrl, {
                method: 'POST',
                body: JSON.stringify({
                    first_name: el('reg-first').value.trim(),
                    last_name: el('reg-last').value.trim(),
                    email: el('reg-email').value.trim(),
                    phone: el('reg-phone').value.trim(),
                    password: el('reg-password').value,
                    password_confirmation: el('reg-password2').value,
                }),
            });
            setToken(data.token);
            setMode('auth');
            state.user = data.user;
            applyUserToForm(data.user);
            toast('Account aangemaakt.');
            enterBook();
        } catch (e) {
            const msg = e.data && e.data.errors
                ? Object.values(e.data.errors).flat().join(' ')
                : (e.message || 'Registreren mislukt.');
            toast(msg);
        }
    });
    el('btn-book').addEventListener('click', bookRide);
    el('passengers').addEventListener('change', maybeQuote);
    const passengerMinus = el('passenger-minus');
    const passengerPlus = el('passenger-plus');
    if (passengerMinus) {
        passengerMinus.addEventListener('click', function () {
            setPassengerCount((parseInt(el('passengers').value, 10) || 1) - 1);
            maybeQuote();
        });
    }
    if (passengerPlus) {
        passengerPlus.addEventListener('click', function () {
            setPassengerCount((parseInt(el('passengers').value, 10) || 1) + 1);
            maybeQuote();
        });
    }
    setPassengerCount(parseInt(el('passengers').value, 10) || 1);
    const zoomIn = el('map-zoom-in');
    const zoomOut = el('map-zoom-out');
    const zoomFit = el('map-zoom-fit');
    if (zoomIn) zoomIn.addEventListener('click', function () { zoomBookMap(1); });
    if (zoomOut) zoomOut.addEventListener('click', function () { zoomBookMap(-1); });
    if (zoomFit) {
        zoomFit.addEventListener('click', function () {
            if (isFollowingAssignedTaxi() && state.bookVehicleMarker && state.map) {
                const pos = state.bookVehicleMarker.getPosition();
                if (pos) {
                    state.map.panTo(pos);
                    state.map.setZoom(15);
                }
                return;
            }
            refitBookRouteBounds();
        });
    }
    const locateBtn = el('btn-pickup-locate');
    if (locateBtn) {
        locateBtn.addEventListener('click', function () {
            detectLocation();
        });
    }
    document.querySelectorAll('[data-theme-choice]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const choice = btn.getAttribute('data-theme-choice');
            if (typeof window.nexaPwaSetTheme === 'function') window.nexaPwaSetTheme(choice);
            syncThemeRows();
        });
    });
    syncThemeRows();
    el('btn-live-back').addEventListener('click', () => {
        stopLivePoll();
        state.showArchivedRides = false;
        showScreen('rides');
        loadRides();
    });
    const btnLiveWait = el('btn-live-wait');
    if (btnLiveWait) btnLiveWait.addEventListener('click', waitLiveRide);
    const btnLivePay = el('btn-live-pay');
    if (btnLivePay) btnLivePay.addEventListener('click', payLiveRide);
    const btnLiveCancel = el('btn-live-cancel');
    if (btnLiveCancel) btnLiveCancel.addEventListener('click', cancelLiveRide);
    document.addEventListener('click', function (ev) {
        const btn = ev.target && ev.target.closest ? ev.target.closest('[data-pickup-answer]') : null;
        if (!btn) return;
        ev.preventDefault();
        ev.stopPropagation();
        submitPickupAnswer(btn.getAttribute('data-pickup-token'), btn.getAttribute('data-pickup-answer'));
    });
    const btnLiveInvoice = el('btn-live-invoice');
    if (btnLiveInvoice) {
        btnLiveInvoice.addEventListener('click', function () {
            downloadRideInvoice({ fromList: false });
        });
    }
    const liveRatingStars = el('live-rating-stars');
    if (liveRatingStars) {
        liveRatingStars.addEventListener('click', function (ev) {
            const btn = ev.target && ev.target.closest ? ev.target.closest('[data-stars]') : null;
            if (!btn) return;
            liveRateStars = Number(btn.getAttribute('data-stars') || 0);
            syncLiveRatingStars(liveRateStars);
        });
    }
    const btnLiveRate = el('btn-live-rate');
    if (btnLiveRate) btnLiveRate.addEventListener('click', submitLiveRating);
    el('btn-rides-book').addEventListener('click', () => {
        if (state.showArchivedRides) {
            state.showArchivedRides = false;
            renderRidesOverview(state.ridesCache);
            return;
        }
        enterBook();
    });
    const activeRideBanner = el('active-ride-banner');
    if (activeRideBanner) {
        activeRideBanner.addEventListener('click', openActiveRide);
    }
    el('btn-switch-role').addEventListener('click', () => {
        window.location.href = cfg.launcherUrl + '?switch=1';
    });
    el('btn-profile-switch').addEventListener('click', () => {
        window.location.href = cfg.launcherUrl + '?switch=1';
    });
    el('btn-profile-login').addEventListener('click', () => {
        showScreen('auth');
        el('auth-login-form').hidden = false;
        el('auth-register-form').hidden = true;
    });
    el('btn-logout').addEventListener('click', async () => {
        try { if (state.token) await api(cfg.apiBase + '/logout', { method: 'POST' }); } catch (e) {}
        setToken('');
        state.user = null;
        setMode('guest');
        stopFleetPolling();
        toast('Uitgelogd.');
        showScreen('welcome');
    });
    el('btn-edit-profile').addEventListener('click', () => {
        if (state.user) applyUserToForm(state.user);
        setProfileEditMode(true);
    });
    el('btn-cancel-profile').addEventListener('click', () => {
        if (state.user) applyUserToForm(state.user);
        setProfileEditMode(false);
    });
    el('btn-save-profile').addEventListener('click', async () => {
        if (!state.token) {
            if (el('first-name')) el('first-name').value = (el('prof-first').value || '').trim();
            if (el('last-name')) el('last-name').value = (el('prof-last').value || '').trim();
            if (el('phone')) el('phone').value = (el('prof-phone').value || '').trim();
            if (el('email')) el('email').value = (el('prof-email').value || '').trim();
            persistProfileDraft();
            const saved = el('profile-saved');
            if (saved) {
                saved.hidden = false;
                setTimeout(function () { saved.hidden = true; }, 2000);
            }
            return;
        }
        try {
            const data = await api(cfg.apiBase + '/profile', {
                method: 'PUT',
                body: JSON.stringify({
                    first_name: el('prof-first').value.trim(),
                    last_name: el('prof-last').value.trim(),
                    phone: el('prof-phone').value.trim(),
                }),
            });
            state.user = data.user;
            applyUserToForm(data.user);
            setProfileEditMode(false);
            toast('Profiel opgeslagen.');
        } catch (e) {
            toast(e.message || 'Opslaan mislukt.');
        }
    });

    document.querySelectorAll('#app-tabbar .tab[data-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            const name = tab.dataset.tab;
            if (name === 'book') enterBook();
            else if (name === 'rides') {
                stopFleetPolling();
                state.showArchivedRides = false;
                const active = activeGuestRide();
                if (active && active.token && isActiveRidePhase(active.phase)) {
                    state.expandedRideToken = active.token;
                    setTrackToken(active.token);
                    if (!state.liveTimer) startLivePoll();
                }
                showScreen('rides');
                loadRides();
            } else if (name === 'profile') {
                showProfile();
            }
        });
    });

    window.addEventListener('nexa-pwa-theme-change', function () {
        if (state.map && !cfg.googleMapsMapId) {
            state.map.setOptions({ styles: mapStylesForTheme() });
        }
        if (state.liveMap && !cfg.googleMapsMapId) {
            state.liveMap.setOptions({ styles: mapStylesForTheme() });
        }
    });

    function openPhotoPreview(url) {
        const overlay = el('photo-preview-overlay');
        const img = el('photo-preview-image');
        if (!overlay || !img || !url) return;
        img.src = url;
        overlay.hidden = false;
    }

    function closePhotoPreview() {
        const overlay = el('photo-preview-overlay');
        const img = el('photo-preview-image');
        if (img) img.removeAttribute('src');
        if (overlay) overlay.hidden = true;
    }

    function bindPhotoPreview() {
        document.addEventListener('click', function (ev) {
            const btn = ev.target && ev.target.closest
                ? ev.target.closest('[data-photo-preview]')
                : null;
            if (btn) {
                ev.preventDefault();
                openPhotoPreview(btn.getAttribute('data-photo-preview'));
                return;
            }
            const overlay = el('photo-preview-overlay');
            if (!overlay || overlay.hidden) return;
            if (ev.target === overlay || (ev.target && ev.target.id === 'photo-preview-image')) {
                closePhotoPreview();
            }
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') closePhotoPreview();
        });
    }

    bindPickupLocationNotice();
    bindAddressClearButtons();
    bindAddressSearch();
    bindPickupDateTimePicker();
    bindBaggageSteppers();
    bindKeyboardTabbarVisibility();
    bindPhotoPreview();
    setPickupAtValue(defaultPickupAt(), { silent: true });
    // Prefetch Maps SDK early.
    loadGoogleMapsSdk().catch(function () {});

    async function restoreActiveGuestRide() {
        const active = activeGuestRide();
        if (!active || !active.token) {
            refreshActiveRideUi();
            return;
        }
        setTrackToken(active.token);
        try {
            const data = await api(cfg.liveUrl + '?token=' + encodeURIComponent(active.token));
            if (data && data.ride) {
                updateGuestRideByToken(active.token, {
                    id: data.ride.id,
                    from: data.ride.pickup_address,
                    to: data.ride.dropoff_address,
                    phase: data.ride.phase,
                    status_label: data.ride.status_label,
                });
                if (isActiveRidePhase(data.ride.phase)) {
                    state.liveRide = data.ride;
                    refreshActiveRideUi();
                    syncBookFollow(data.ride);
                    startLivePoll();
                    return;
                }
            }
        } catch (e) { /* banner/list blijven beschikbaar */ }
        refreshActiveRideUi();
    }

    function readSavedScreen() {
        const hash = String(window.location.hash || '').replace(/^#/, '').trim();
        if (hash && screens[hash]) return hash;
        const saved = readSession(SCREEN_KEY);
        if (saved && screens[saved]) return saved;
        return '';
    }

    async function restorePersistedScreen(authed) {
        const saved = readSavedScreen();
        if (!saved) return false;

        if (!state.trackToken) {
            const savedTrack = readSession(TRACK_KEY);
            if (savedTrack) setTrackToken(savedTrack);
        }
        if (!state.trackToken) {
            const active = activeGuestRide();
            if (active && active.token) setTrackToken(active.token);
        }

        if (!authed && state.mode !== 'guest' && saved !== 'welcome' && saved !== 'auth') {
            setMode('guest');
        }

        if (saved === 'live') {
            if (!state.trackToken) return false;
            openLive(null);
            return true;
        }
        if (saved === 'rides') {
            stopFleetPolling();
            stopLivePoll();
            showScreen('rides');
            loadRides();
            return true;
        }
        if (saved === 'profile') {
            stopLivePoll();
            showProfile();
            return true;
        }
        if (saved === 'auth') {
            if (authed) {
                await enterBook();
                return true;
            }
            showScreen('auth');
            return true;
        }
        if (saved === 'book') {
            await enterBook();
            return true;
        }
        if (saved === 'welcome') {
            showScreen('welcome');
            return true;
        }
        return false;
    }

    (async function boot() {
        const params = new URLSearchParams(window.location.search);
        const bookingResult = params.get('boeking');
        const returnToken = params.get('token');
        if (returnToken) {
            setTrackToken(returnToken);
            setMode(state.mode || 'guest');
        }
        const authed = await bootAuth();
        if (bookingResult === 'betaald' && (returnToken || state.trackToken)) {
            if (!authed) setMode('guest');
            if (returnToken) {
                saveGuestRide(0, returnToken, {
                    phase: 'searching',
                    status_label: 'Zoeken…',
                });
            }
            state.acceptedNotified = false;
            openLive(null);
            toast('Betaling ontvangen. We zoeken een taxi…');
            try {
                const clean = window.location.pathname + '#live';
                window.history.replaceState({}, '', clean);
            } catch (e) {}
            return;
        }
        if (bookingResult === 'betaling-bezig' && (returnToken || state.trackToken)) {
            if (!authed) setMode('guest');
            if (returnToken) {
                saveGuestRide(0, returnToken, {
                    phase: 'awaiting_payment',
                    status_label: 'Betaling controleren…',
                });
            }
            state.acceptedNotified = false;
            openLive(null);
            toast('Betaling wordt bevestigd…');
            try {
                const clean = window.location.pathname + '#live';
                window.history.replaceState({}, '', clean);
            } catch (e) {}
            return;
        }
        if (bookingResult === 'betaling-mislukt') {
            const reden = params.get('reden') || '';
            let failMsg = 'Betaling mislukt. Betaal opnieuw om de rit te activeren, of annuleer de rit.';
            if (reden === 'mislukt') failMsg = 'Betaling mislukt. Betaal opnieuw om de rit te activeren, of annuleer de rit.';
            else if (reden === 'geannuleerd') failMsg = 'Betaling mislukt (afgebroken). Betaal opnieuw om de rit te activeren, of annuleer de rit.';
            else if (reden === 'verlopen') failMsg = 'Betaling mislukt (verlopen). Betaal opnieuw om de rit te activeren, of annuleer de rit.';
            else if (reden === 'afgebroken') failMsg = 'Betaling niet afgerond. Betaal opnieuw om de rit te activeren, of annuleer de rit.';

            if (returnToken || state.trackToken) {
                if (!authed) setMode('guest');
                if (returnToken) {
                    saveGuestRide(0, returnToken, {
                        phase: 'awaiting_payment',
                        status_label: 'Betaling mislukt',
                        payment_failure_status: reden === 'verlopen' ? 'expired'
                            : (reden === 'mislukt' ? 'failed' : (reden ? 'canceled' : 'failed')),
                    });
                }
                openLive(null);
                toast(failMsg);
            } else {
                toast(failMsg);
            }
            try {
                const clean = window.location.pathname + ((returnToken || state.trackToken) ? '#live' : '');
                window.history.replaceState({}, '', clean);
            } catch (e) {}
            if (returnToken || state.trackToken) return;
        }
        if (params.get('login') === '1' && !authed) {
            showScreen('auth');
            return;
        }

        const restored = await restorePersistedScreen(authed);
        if (restored) {
            if (readSavedScreen() !== 'live') {
                await restoreActiveGuestRide();
            }
            return;
        }

        if (params.get('guest') === '1' || state.mode === 'guest' || authed) {
            if (!authed) setMode('guest');
            await enterBook();
            await restoreActiveGuestRide();
            return;
        }
        showScreen('welcome');
    })();
})();
