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
        centerLat: parseFloat(app.dataset.centerLat || '52.3676'),
        centerLng: parseFloat(app.dataset.centerLng || '4.9041'),
        marketplaceSectionKey: 'component:taxi.algemene_boekingsmodule',
        fleetRadiusKm: 10,
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
        });
        writeGuestRides(list);
        refreshActiveRideUi();
    }

    function updateGuestRideByToken(trackToken, patch) {
        const token = String(trackToken || '').trim();
        if (!token) return;
        const clean = {};
        Object.keys(patch || {}).forEach(function (key) {
            if (patch[key] !== undefined && patch[key] !== null && patch[key] !== '') {
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

    function isActiveRidePhase(phase) {
        return phase === 'searching' || phase === 'accepted'
            || phase === 'awaiting_payment' || phase === 'other';
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
            badge.classList.toggle('is-on', !!active && isActiveRidePhase(active.phase));
            badge.textContent = '1';
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
                ? 'Taxi onderweg'
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

    function defaultPickupAt() {
        const d = new Date(Date.now() + 10 * 60 * 1000);
        d.setSeconds(0, 0);
        // Rond af naar dichtstbijzijnde 5 minuten.
        d.setMinutes(Math.ceil(d.getMinutes() / 5) * 5);
        return toLocalInputValue(d);
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
        const time = d.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' });
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
        // Niet herpositioneren als er al een route/bestemming op de kaart staat.
        if (state.dropoff) return;
        const list = Array.isArray(vehicles) ? vehicles : [];
        if (!list.length) return;
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
        const status = el('map-fleet-status');
        if (status) {
            status.textContent = count === 0
                ? 'Geen taxi’s online in de straal'
                : (count === 1 ? '1 taxi live in de straal' : count + ' taxi’s live in de straal');
        }
        updateFleetRadiusCircle();
        maybeFitFleetViewport(list);
    }

    async function fetchNearbyTaxis() {
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
        state.map.fitBounds(bounds, 56);
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
            return null;
        }
        drawRoutePath(route.path);
        state.routeMetricsCache = {
            distance_meters: route.distance_meters,
            duration_seconds: route.duration_seconds,
        };
        return state.routeMetricsCache;
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
        el('pickup').value = 'Locatie bepalen…';
        el('pickup-hint').textContent = 'Even geduld…';
        if (!navigator.geolocation) {
            el('pickup').value = '';
            el('pickup-hint').textContent = 'Locatie niet beschikbaar. Vul handmatig in.';
            el('pickup').readOnly = false;
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
                    address = (data && (data.display_name || data.name)) || '';
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
            maybeQuote();
        } catch (e) {
            el('pickup').value = '';
            el('pickup').readOnly = false;
            el('pickup-hint').textContent = 'Kon locatie niet bepalen. Vul je ophaaladres in.';
            toast('Locatie toegang nodig om automatisch te detecteren.');
            fetchNearbyTaxis();
        }
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

    function formatNominatimPredictionItem(row) {
        if (!row) return null;
        const displayName = String(row.display_name || '').trim();
        const poiName = String(row.name || '').trim();
        const category = String(row.category || row.class || '').toLowerCase();
        const placeType = String(row.type || '').toLowerCase();
        const isStation = category === 'railway' || placeType === 'station' || placeType === 'halt';
        const a = row.address && typeof row.address === 'object' ? row.address : null;
        const city = a
            ? (a.city || a.town || a.village || a.municipality || a.suburb || '')
            : '';
        const postcode = a ? (a.postcode || '') : '';
        let lead;
        if (isStation) {
            lead = ensureStationLabel(poiName || (displayName.split(',')[0] || displayName));
        } else {
            lead = poiName || displayName;
        }
        const second = [postcode, city].filter(Boolean).join(' ').trim();
        let label = second ? (lead + ', ' + second) : lead;
        if (!label) label = displayName;
        if (!label) return null;
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

    function mergeAddressSuggestions(googleItems, nominatimItems, query) {
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

        if (wantsStation) {
            google.filter(function (i) { return i.is_station; }).forEach(push);
            nomi.filter(function (i) { return i.is_station; }).forEach(push);
            google.forEach(push);
            nomi.forEach(push);
        } else {
            google.forEach(push);
            if (!out.length) nomi.forEach(push);
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
                    resolve(String(results[0].formatted_address || '').trim());
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

    function fetchGoogleAddressPredictions(query) {
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
            if (queryWantsStation(input) && !/^station\b/i.test(input)) {
                input = 'Station ' + input.replace(/\bstation\b/gi, ' ').replace(/\s+/g, ' ').trim();
            }
            const request = {
                input: input,
                language: 'nl',
                componentRestrictions: { country: 'nl' },
            };
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

            const googleItems = await fetchGoogleAddressPredictions(q);
            if (seq !== requestSeq) return;

            let nominatimItems = [];
            // Bij stations altijd Nominatim meenemen: Google toont vaak alleen de plaatsnaam.
            if (queryWantsStation(q) || !googleItems.length) {
                nominatimItems = await fetchNominatimAddressPredictions(q);
                if (seq !== requestSeq) return;
            }

            const merged = mergeAddressSuggestions(googleItems, nominatimItems, q);
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
            el('pickup-hint').textContent = 'Handmatig gekozen · tik om te wijzigen';
            Promise.resolve(updateMapMarkers()).then(function () {
                updateFleetRadiusCircle();
                fetchNearbyTaxis();
                maybeQuote();
            });
        });
        bindAddressField('dropoff', 'dropoff-suggestions', function (point) {
            state.dropoff = point;
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
                const wd = i === 0 ? 'Vandaag' : (i === 1 ? 'Morgen' : day.toLocaleDateString('nl-NL', { weekday: 'short' }));
                btn.innerHTML = '<span class="dt-day__wd">' + wd + '</span>'
                    + '<span class="dt-day__nr">' + day.getDate() + '</span>';
                btn.addEventListener('click', function () {
                    draft = new Date(day.getFullYear(), day.getMonth(), day.getDate(), draft.getHours(), draft.getMinutes(), 0, 0);
                    syncUi();
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
            const makeSpacer = function () {
                const node = document.createElement('div');
                node.className = 'dt-item';
                node.style.visibility = 'hidden';
                node.textContent = '00';
                return node;
            };
            hoursEl.appendChild(makeSpacer());
            for (let h = 0; h < 24; h++) {
                const item = document.createElement('div');
                item.className = 'dt-item';
                item.dataset.value = String(h);
                item.textContent = pad(h);
                item.addEventListener('click', function () {
                    draft.setHours(h);
                    syncUi({ scroll: true });
                });
                hoursEl.appendChild(item);
                hourItems.push(item);
            }
            hoursEl.appendChild(makeSpacer());

            minutesEl.appendChild(makeSpacer());
            for (let m = 0; m < 60; m += 5) {
                const item = document.createElement('div');
                item.className = 'dt-item';
                item.dataset.value = String(m);
                item.textContent = pad(m);
                item.addEventListener('click', function () {
                    draft.setMinutes(m);
                    syncUi({ scroll: true });
                });
                minutesEl.appendChild(item);
                minuteItems.push(item);
            }
            minutesEl.appendChild(makeSpacer());
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
                chip.classList.remove('is-active');
            });
            if (opts.scroll !== false) {
                syncingScroll = true;
                scrollToActive(hoursEl, hourItems, draft.getHours());
                scrollToActive(minutesEl, minuteItems, minIdx);
                setTimeout(function () { syncingScroll = false; }, 50);
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
            if (Number.isNaN(draft.getTime())) draft = parseLocalInputValue(defaultPickupAt());
            overlay.hidden = false;
            syncUi({ scroll: true });
            const activeDay = daysEl.querySelector('.dt-day.is-active');
            if (activeDay) {
                activeDay.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }

        function closePicker() {
            overlay.hidden = true;
        }

        buildDays();
        buildTimeColumns();
        onScrollSnap(hoursEl, hourItems, function (idx) {
            draft.setHours(idx);
        });
        onScrollSnap(minutesEl, minuteItems, function (idx) {
            const item = minuteItems[idx];
            if (item) draft.setMinutes(parseInt(item.dataset.value, 10));
        });

        openBtn.addEventListener('click', openPicker);
        el('dt-close').addEventListener('click', closePicker);
        el('dt-cancel').addEventListener('click', closePicker);
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) closePicker();
        });
        el('dt-confirm').addEventListener('click', function () {
            const now = new Date();
            if (draft.getTime() < now.getTime() - 60000) {
                toast('Kies een ophaaltijd in de toekomst.');
                return;
            }
            setPickupAtValue(toLocalInputValue(draft));
            closePicker();
        });
        document.querySelectorAll('#dt-quick .dt-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                const mins = parseInt(chip.dataset.quick, 10) || 10;
                draft = new Date(Date.now() + mins * 60 * 1000);
                draft.setSeconds(0, 0);
                draft.setMinutes(Math.ceil(draft.getMinutes() / 5) * 5);
                document.querySelectorAll('#dt-quick .dt-chip').forEach(function (c) {
                    c.classList.toggle('is-active', c === chip);
                });
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
                el('quote-price').textContent = offer.price_label
                    || ('€ ' + Number(offer.price || 0).toFixed(2).replace('.', ','));
                const cand = (data.marketplace && data.marketplace.candidate_count) || 0;
                el('quote-meta').textContent = cand + ' taxi' + (cand === 1 ? '' : 's') + ' in straal';
                el('quote-card').hidden = false;
                updatePaymentUi(data.payment || null);
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
        stopFleetPolling();
        showScreen('live');
        state.liveRouteKey = '';
        state.liveRouteFetchSeq += 1;
        if (initial && initial.phase && initial.phase !== 'searching') {
            state.acceptedNotified = true;
            state.livePhase = initial.phase;
        }
        ensureLiveMap().then(function () {
            if (initial) renderLive(initial);
            startLivePoll();
        });
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

    function renderLive(ride) {
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
        const routeFrom = el('live-route-from');
        const routeTo = el('live-route-to');
        const pickupWrap = el('live-pickup-wrap');
        const pickupAtEl = el('live-pickup-at');

        state.liveRide = ride || null;

        const fromRaw = ride && (ride.pickup_address || ride.from);
        const toRaw = ride && (ride.dropoff_address || ride.to);
        const fromAddr = shortAddress(fromRaw);
        const toAddr = shortAddress(toRaw);
        if (routeBox && routeFrom && routeTo) {
            const hasRoute = !!(String(fromRaw || '').trim() && String(toRaw || '').trim());
            routeFrom.textContent = hasRoute ? fromAddr : '—';
            routeTo.textContent = hasRoute ? toAddr : '—';
            routeBox.hidden = !hasRoute;
            const routeActive = !!(ride && isActiveRidePhase(ride.phase));
            routeBox.classList.toggle('is-live-active', routeActive);
        }
        if (pickupWrap && pickupAtEl) {
            const when = formatRideWhen(ride && (ride.pickup_at_label || ride.pickup_at || ride.at || ride.at_ts));
            pickupAtEl.textContent = when || '—';
            pickupWrap.hidden = !when;
            pickupWrap.classList.toggle('is-live-active', !!(ride && isActiveRidePhase(ride.phase)));
        }

        if (state.trackToken && ride) {
            updateGuestRideByToken(state.trackToken, {
                id: ride.id || undefined,
                from: ride.pickup_address || undefined,
                to: ride.dropoff_address || undefined,
                phase: ride.phase || 'other',
                status_label: ride.status_label || ride.status || '',
                payment_failure_status: ride.payment_failure_status || null,
            });
        }

        if (note) {
            note.hidden = true;
            note.textContent = '';
        }

        if (ride.phase === 'awaiting_payment') {
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
            pill.className = 'status-pill';
            pill.innerHTML = '<span class="pulse"></span> Zoeken…';
            details.hidden = true;
            if (ride.needs_unaccepted_decision) {
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
                title.textContent = 'We zoeken verder';
                text.textContent = 'Je rit blijft op de marktplaats tot een taxi accepteert. Je kunt alsnog annuleren.';
            } else {
                title.textContent = 'We zoeken een taxi';
                text.textContent = 'Je rit staat op de marktplaats voor aangesloten taxibedrijven in de buurt.';
                if (note && ride.auto_cancel_label) {
                    note.hidden = false;
                    note.textContent = 'Geen taxi gevonden vóór ' + ride.auto_cancel_label
                        + '? Dan vragen we of je wilt wachten of annuleren.';
                }
            }
        } else if (ride.phase === 'accepted') {
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
            title.textContent = ride.eta_label ? ('Arriveert ' + ride.eta_label) : 'Taxi onderweg';
            text.textContent = 'Details van je toegewezen taxi:';
            const rows = [];
            if (ride.company && ride.company.name) rows.push(['Bedrijf', ride.company.name]);
            if (ride.vehicle && ride.vehicle.license_plate) rows.push(['Kenteken', ride.vehicle.license_plate]);
            if (ride.vehicle && ride.vehicle.name) rows.push(['Auto', ride.vehicle.name]);
            if (ride.driver && ride.driver.name) rows.push(['Chauffeur', ride.driver.name]);
            if (ride.eta_label) rows.push(['ETA', ride.eta_label]);
            details.innerHTML = rows.map(([k, v]) => '<dt>' + k + '</dt><dd>' + escapeHtml(v) + '</dd>').join('');
            details.hidden = rows.length === 0;
        } else if (ride.phase === 'completed') {
            pill.className = 'status-pill ok';
            pill.textContent = 'Voltooid';
            title.textContent = 'Rit afgerond';
            text.textContent = 'Bedankt voor je rit met Nexa Taxi. Je kunt hieronder je factuur downloaden.';
            details.hidden = true;
            stopLivePoll();
        } else if (ride.phase === 'cancelled') {
            pill.className = 'status-pill warn';
            pill.textContent = 'Geannuleerd';
            title.textContent = 'Rit geannuleerd';
            text.textContent = 'Deze rit is geannuleerd.'
                + (ride.refund_business_days
                    ? ' Indien vooraf betaald wordt het bedrag teruggestort (doorgaans binnen '
                        + ride.refund_business_days + ' werkdagen).'
                    : '');
            details.hidden = true;
            stopLivePoll();
        } else {
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

    async function cancelLiveRide() {
        const ride = state.liveRide;
        if (!ride || !ride.can_cancel) return;
        if (!window.confirm('Weet je zeker dat je deze rit wilt annuleren?')) return;

        const btn = el('btn-live-cancel');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Annuleren…';
        }

        try {
            let data;
            if (state.trackToken) {
                data = await api(cfg.apiBase + '/live/cancel', {
                    method: 'POST',
                    body: JSON.stringify({ token: state.trackToken }),
                });
            } else if (ride.id && state.token) {
                data = await api(cfg.apiBase + '/rides/' + ride.id + '/cancel', { method: 'POST' });
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
        return raw.split(',')[0].trim() || raw;
    }

    function formatRideWhen(at) {
        if (at == null || at === '') return '';
        let date = null;
        if (typeof at === 'number' && Number.isFinite(at)) {
            date = new Date(at);
        } else {
            const raw = String(at).trim();
            if (!raw) return '';
            // Al geformatteerd (lijst): laat staan.
            if (/^\d{2}-\d{2}-\d{4}/.test(raw) || /^\d{1,2}\s+\w{3}/i.test(raw)) return raw;
            const parsed = new Date(raw);
            if (!Number.isNaN(parsed.getTime())) date = parsed;
            else return raw;
        }
        try {
            return date.toLocaleString('nl-NL', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            });
        } catch (e) {
            return '';
        }
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
        return {
            id: raw.id || 0,
            token: raw.token || '',
            from: raw.from || raw.pickup_address || '',
            to: raw.to || raw.dropoff_address || '',
            at: raw.at || formatRideWhen(raw.at_ts || raw.pickup_at_iso) || '',
            at_ts: raw.at_ts || null,
            phase: phase,
            status: raw.status || '',
            status_label: raw.status_label || '',
            amount: raw.amount || '',
            payment_failure_status: raw.payment_failure_status || null,
            can_download_invoice: !!(raw.can_download_invoice || phase === 'completed'),
            can_cancel: !!raw.can_cancel,
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
        if (ride.status_label) return ride.status_label;
        if (ride.phase === 'searching') return 'Zoeken…';
        if (ride.phase === 'accepted') return 'Onderweg';
        if (ride.phase === 'cancelled') return 'Geannuleerd';
        if (ride.phase === 'completed') return 'Voltooid';
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
        if (title) title.textContent = state.showArchivedRides ? 'Archief' : 'Mijn ritten';
    }

    function renderRideCardHtml(ride, options) {
        const opts = options || {};
        const asHero = !!opts.hero;
        const showArchiveAction = !!opts.archiveAction;
        const from = shortAddress(ride.from);
        const to = shortAddress(ride.to);
        const when = formatRideWhen(ride.at || ride.at_ts);
        const pill = ridePillLabel(ride);
        const amount = ride.amount ? String(ride.amount) : '';
        const attrs = ride.token
            ? (' data-token="' + escapeHtml(ride.token) + '"')
            : (' data-id="' + escapeHtml(String(ride.id || '')) + '"');
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

        if (asHero) {
            return '<button type="button" class="rides-hero'
                + (ride.phase === 'accepted' ? ' is-accepted' : '')
                + '"' + attrs + '>'
                + '<div class="rides-hero__eyebrow">'
                + (ride.phase === 'searching' ? '<span class="pulse"></span>' : '')
                + escapeHtml(ride.phase === 'accepted' ? 'Taxi onderweg' : 'Openstaande rit')
                + '</div>'
                + '<h3 class="rides-hero__title">' + escapeHtml(pill) + '</h3>'
                + '<div class="live-route ride-card__route" style="margin-top:10px;padding-top:0;border:0">'
                + '<div class="ride-card__spine" aria-hidden="true">'
                + '<span class="ride-card__dot is-from"></span>'
                + '<span class="ride-card__dot is-to"></span>'
                + '</div>'
                + '<div class="ride-card__stops">'
                + '<div><p class="ride-card__stop-label">Van</p>'
                + '<p class="ride-card__stop-value">' + escapeHtml(from) + '</p></div>'
                + '<div><p class="ride-card__stop-label">Naar</p>'
                + '<p class="ride-card__stop-value">' + escapeHtml(to) + '</p></div>'
                + '</div></div>'
                + '<div class="rides-hero__foot">'
                + '<span class="ride-card__when">' + escapeHtml(when || 'Nu') + '</span>'
                + '<span class="rides-hero__cta">Volgen →</span>'
                + '</div></button>';
        }

        return '<div class="ride-card-wrap">'
            + '<article class="ride-card'
            + (ride.active ? (ride.phase === 'accepted' ? ' is-accepted' : ' is-active') : '')
            + '"' + attrs + ' role="button" tabindex="0">'
            + '<div class="ride-card__top">'
            + '<span class="ride-card__status">'
            + '<span class="ride-card__pill ' + ridePillClass(ride.phase) + '">'
            + (ride.phase === 'searching' ? '<span class="pulse"></span>' : '')
            + escapeHtml(pill)
            + '</span>'
            + invoiceBtn
            + archiveBtn
            + '</span>'
            + (amount ? ('<span class="ride-card__amount">' + escapeHtml(amount) + '</span>') : '')
            + '</div>'
            + '<div class="ride-card__route">'
            + '<div class="ride-card__spine" aria-hidden="true">'
            + '<span class="ride-card__dot is-from"></span>'
            + '<span class="ride-card__dot is-to"></span>'
            + '</div>'
            + '<div class="ride-card__stops">'
            + '<div><p class="ride-card__stop-label">Van</p>'
            + '<p class="ride-card__stop-value">' + escapeHtml(from) + '</p></div>'
            + '<div><p class="ride-card__stop-label">Naar</p>'
            + '<p class="ride-card__stop-value">' + escapeHtml(to) + '</p></div>'
            + '</div></div>'
            + '<div class="ride-card__foot">'
            + '<span class="ride-card__when">' + escapeHtml(when || '—') + '</span>'
            + '<span class="ride-card__action">'
            + (ride.active ? 'Volgen' : 'Details') + ' →'
            + '</span></div></article>'
            + '</div>';
    }

    function renderRidesEmptyHtml(message) {
        const opts = message || {};
        const title = opts.title || 'Nog geen ritten';
        const text = opts.text || 'Boek een taxi en volg hier je openstaande en eerdere ritten.';
        const showBook = opts.showBook !== false;
        return '<div class="rides-empty">'
            + '<div class="rides-empty__icon" aria-hidden="true">'
            + '<svg viewBox="0 0 24 24"><path d="M8 17h8"/><path d="M5 17l1.5-8h11L19 17"/>'
            + '<path d="M7 17a1.5 1.5 0 1 0 0 .01"/><path d="M17 17a1.5 1.5 0 1 0 0 .01"/>'
            + '<path d="M7 9l1-3h8l1 3"/></svg></div>'
            + '<h3 class="rides-empty__title">' + escapeHtml(title) + '</h3>'
            + '<p class="rides-empty__text">' + escapeHtml(text) + '</p>'
            + (showBook
                ? '<button type="button" class="btn btn-primary" id="rides-empty-book">Nieuwe rit boeken</button>'
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
        box.querySelectorAll('.ride-card[data-id], .rides-hero[data-id], .ride-card[data-token], .rides-hero[data-token]')
            .forEach(function (btn) {
                async function openRideCard() {
                    if (btn.dataset.token) {
                        setTrackToken(btn.dataset.token);
                        const guest = guestRides().find(function (r) { return r && r.token === btn.dataset.token; });
                        state.acceptedNotified = !!(guest && guest.phase && guest.phase !== 'searching');
                        state.livePhase = (guest && guest.phase) || null;
                        openLive(null);
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
                btn.addEventListener('click', openRideCard);
                btn.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Enter' || ev.key === ' ') {
                        ev.preventDefault();
                        openRideCard();
                    }
                });
            });
    }

    function renderRidesOverview(rawRides) {
        const box = el('rides-list');
        if (!box) return;
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
                return;
            }
            let archiveHtml = '<div class="rides-section">Gearchiveerde ritten</div>';
            archiveHtml += archived.map(function (r) {
                return renderRideCardHtml(r, { archiveAction: true });
            }).join('');
            box.innerHTML = archiveHtml;
            bindRideListClicks(box);
            refreshActiveRideUi();
            return;
        }

        if (!rides.length) {
            box.innerHTML = renderRidesEmptyHtml();
            bindRideListClicks(box);
            refreshActiveRideUi();
            return;
        }
        const active = rides.filter(function (r) { return r.active; });
        const past = rides.filter(function (r) { return !r.active; });
        const visiblePast = past.filter(function (r) { return !isRideArchived(r); });
        const archivedPast = past.filter(function (r) { return isRideArchived(r); });
        let html = '';
        if (active.length) {
            html += renderRideCardHtml(active[0], { hero: true });
            if (active.length > 1) {
                html += '<div class="rides-section">Nog actief</div>';
                html += active.slice(1).map(function (r) { return renderRideCardHtml(r); }).join('');
            }
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
        refreshActiveRideUi();
    }

    async function loadRides() {
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
                const next = {
                    id: ride.id || r.id,
                    token: r.token,
                    at: r.at || Date.now(),
                    from: ride.pickup_address || r.from || '',
                    to: ride.dropoff_address || r.to || '',
                    phase: ride.phase || r.phase || 'other',
                    status_label: ride.status_label || r.status_label || '',
                    payment_failure_status: ride.payment_failure_status || r.payment_failure_status || null,
                    amount: r.amount || '',
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
                can_download_invoice: r.phase === 'completed',
            };
        }));
    }

    function showProfile() {
        stopFleetPolling();
        showScreen('profile');
        const guest = !state.token;
        el('profile-guest').hidden = !guest;
        if (el('profile-account-actions')) el('profile-account-actions').hidden = guest;
        if (guest) {
            if (el('profile-view')) el('profile-view').hidden = true;
            if (el('profile-form')) el('profile-form').hidden = true;
            return;
        }
        if (state.user) applyUserToForm(state.user);
        setProfileEditMode(false);
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
        startFleetPolling();
        refreshActiveRideUi();
        detectLocation();
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
        profileBack.addEventListener('click', () => enterBook());
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
    const btnLiveInvoice = el('btn-live-invoice');
    if (btnLiveInvoice) {
        btnLiveInvoice.addEventListener('click', function () {
            downloadRideInvoice({ fromList: false });
        });
    }
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
                stopLivePoll();
                state.showArchivedRides = false;
                showScreen('rides');
                loadRides();
            } else if (name === 'profile') {
                stopLivePoll();
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

    bindAddressSearch();
    bindPickupDateTimePicker();
    bindBaggageSteppers();
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
                    // Blijf op boeken met banner; gebruiker kiest Volgen.
                    refreshActiveRideUi();
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
