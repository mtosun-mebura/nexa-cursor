(function () {
    'use strict';

    const cfg = window.NEXA_TAXI_DRIVER || {};
    const STORAGE_KEY = 'nexa_taxi_driver_token';
    const COMPANY_KEY = 'nexa_taxi_driver_company_id';
    const ONLINE_KEY = 'nexa_taxi_driver_online';
    const NOTIFICATIONS_HINT_DISMISSED_KEY = 'nexa_taxi_dismiss_notifications_hint';
    const IOS_AWAKE_HINT_DISMISSED_KEY = 'nexa_taxi_dismiss_ios_awake_hint';
    const INSTALL_HINT_DISMISSED_KEY = 'nexa_taxi_dismiss_install_hint';

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

    let token = sessionStorage.getItem(STORAGE_KEY) || '';
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
    let archivedRideExpanded = {};
    let archivedSelectedIds = {};
    let activeRideStops = [];
    let activeRideStopsProgress = null;
    let activeRideInboxCollapsed = true;
    let parkedAssignedRides = [];
    let viewingActiveRideId = null;
    const STOP_ARRIVE_RADIUS_M = 120;
    let stopGeofenceWatchId = null;
    let stopGeofenceAutoArrivePending = {};
    let stopGeofenceAvailable = null;
    let stopArrivedAnimationIds = {};
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
    let declinedOffers = [];
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

    function showPickupProposalAlert(alert) {
        if (!alert || !alert.message) {
            return;
        }
        showAbsenceAlert({ message: alert.message });
        try {
            if (window.Notification && Notification.permission === 'granted') {
                new Notification('Ophaalvoorstel', { body: alert.message });
            }
        } catch (e) {
            /* ignore */
        }
        vibrate(120);
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
        const offerStrip = $('#offer-strip');
        const declinedStrip = $('#declined-strip');
        const overdueStrip = $('#overdue-strip');
        const archivedStrip = $('#archived-strip');
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
            (contract ? ' is-contract-ride' : '') +
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
            (contract ? contractBadgeHtml(ride) : '') +
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
            '">Alsnog accepteren</button>' +
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
            return Promise.resolve(
                window.confirm(
                    (n === 1
                        ? 'Weet u het zeker?\n\nU staat op het punt deze gearchiveerde rit permanent te verwijderen.'
                        : 'Weet u het zeker?\n\nU staat op het punt deze ' +
                          n +
                          ' gearchiveerde ritten permanent te verwijderen.') +
                        ' Deze wijziging kan niet meer ongedaan worden gemaakt.'
                )
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
            setMainTab(mainTab || 'requests');
        }
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

    function updateInstallHint() {
        const hint = $('#install-app-hint');
        const hintText = $('#install-app-hint-text');
        const btn = $('#btn-install-app');
        if (!hint) {
            return;
        }
        if (isStandalonePwa() || isInstallHintDismissed()) {
            hint.hidden = true;
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
            return;
        }
        hint.hidden = true;
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
    function syncToolbarNavVisibility() {
        const toolbarNav = $('#toolbar-nav');
        if (toolbarNav) {
            // Altijd zichtbaar op Aanvragen, Ritten, Inkomsten en Profiel (zolang online).
            toolbarNav.hidden = !isOnline;
        }
    }
    function setMainTab(tab) {
        let next = ['requests', 'trips', 'earnings', 'profile'].indexOf(tab) !== -1 ? tab : 'requests';
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
            if (isSecondaryInboxView(inboxView)) {
                setInboxView(inboxView);
            } else {
                setInboxView('offers');
            }
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
        syncActiveRideJumpButton();
    }

    function tripsListHasContent() {
        const overdueOnly = (overdueScheduledRides || []).filter(function (ride) {
            if (!ride || ride.id == null) {
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
            inboxLoading = false;
            inboxHasLoaded = false;
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
                banner.textContent = 'Ophaalmoment verlopen — accepteer of weiger zo snel mogelijk';
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
        strip.classList.toggle('is-contract-ride', isContract);
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
        return (
            '<div class="card offer-card active-ride-collapsed-banner parked-assigned-ride-card' +
            (isActive ? ' is-active-ride' : '') +
            '" data-ride-id="' +
            escapeHtml(rideId) +
            '">' +
            '<div class="offer-card-top">' +
            '<div class="offer-badge-row">' +
            activeBadge +
            contractBadgeHtml(ride) +
            returnTripBadgeHtml(ride) +
            '</div>' +
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
            return window.confirm(
                'Bevestig: klant heeft ' +
                    formatEuro(amount) +
                    ' contant betaald? Dit bedrag wordt vastgelegd.'
            )
                ? executeRideCashPayment(amount)
                : undefined;
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
            if (
                window.confirm(
                    'Bevestig: klant heeft ' +
                        formatEuro(amount) +
                        ' contant betaald? Dit bedrag wordt vastgelegd.'
                )
            ) {
                void executeRideCashPayment(amount);
            }
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
            badge.classList.toggle('is-muted', label !== 'Nieuw' && label !== 'Groep' && label !== 'Verlopen');
            badge.classList.toggle('is-success', label === 'Nieuw');
            badge.classList.toggle('is-danger', label === 'Verlopen');
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
        return rides
            .filter(function (ride) {
                return isContractRideVisibleInScheduledInbox(ride);
            })
            .sort(function (a, b) {
                const aKey = contractRideCalendarDateKey(a) || '';
                const bKey = contractRideCalendarDateKey(b) || '';
                if (aKey !== bKey) {
                    return aKey.localeCompare(bKey);
                }
                return String(a.id || '').localeCompare(String(b.id || ''));
            });
    }

    function prepareScheduledRidesForInbox(scheduled) {
        return filterScheduledRidesForInbox(Array.isArray(scheduled) ? scheduled : []);
    }

    function contractBadgeHtml(ride) {
        if (!isContractRide(ride)) {
            return '';
        }
        return '<span class="contract-ride-badge">Contract</span>';
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
            return !scheduledRides.some(function (item) {
                return String(item.id) === String(ride.id);
            });
        });
        const tripsRides = scheduledRides.concat(overdueOnly);
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
                    (isContractRide(ride) ? ' is-contract-ride' : '') +
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
                    contractBadgeHtml(ride) +
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
            if (completeBtn) {
                completeBtn.disabled = false;
                delete completeBtn.dataset.rideId;
            }
            setCompleteRideButtonVisible(false);
            syncTripsEmptyState();
            return;
        }
        currentActiveRide = ride;
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
            const contractBadge = contractBadgeHtml(ride);
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
            el.innerHTML =
                '<div class="offer-card-top">' +
                '<div class="offer-badge-row">' +
                '<span class="offer-badge">Actief</span>' +
                '<span class="offer-badge is-success" role="status">' +
                escapeHtml(acceptedText) +
                '</span>' +
                '</div>' +
                '<div class="offer-card-meta-right">' +
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
            const res = await api('/dispatch/inbox');
            const active = res.data && res.data.active_ride;
            const scheduled = (res.data && res.data.scheduled_rides) || [];
            overdueScheduledRides = (res.data && res.data.overdue_scheduled_rides) || [];
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
        try {
            const res = await api('/dispatch/inbox');
            const offers = (res.data && res.data.offers) || [];
            declinedOffers = (res.data && res.data.declined_offers) || [];
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
            showPickupProposalAlert(res.data && res.data.pickup_proposal_alert);
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
            // Badge bij “Open” = alleen openstaande aanbiedingen (niet geplande ritten op Ritten).
            mainInboxRideCount = offers.length;
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
        applyEarningsPermissions(data.permissions || {});
        renderProfileUser(data.user || null);
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

            offerAcceptInFlight = true;
            document.body.classList.add('driver-accept-in-flight');
            setOfferActionButtonsDisabled(true, activeBtn);

            await api('/dispatch/offers/' + offerId + '/accept', {
                method: 'POST',
                body: acceptBody,
            });
            showNewRideAlert(false);
            vibrate(100);
            if (acceptBody.pickup_at) {
                alert('Nieuw ophaalmoment voorgesteld aan de klant via WhatsApp.');
            }
            // Altijd via offers-pad refreshen zodat geaccepteerde ritten in Ritten landen.
            inboxView = 'offers';
            await refreshInbox();
            if (fromDeclinedView || fromOverdueView) {
                setMainTab('trips');
                setInboxView('offers');
            }
        } catch (e) {
            alert(e.message || 'Accepteren mislukt.');
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
            document.body.classList.remove('driver-dialog-open');
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
            await api('/dispatch/offers/' + offerId + '/archive', { method: 'POST' });
            vibrate(40);
            await refreshInbox();
            setMainTab('requests');
            setInboxView('overdue');
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
            alert((res && res.message) || 'Rit verwijderd.');
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
            alert((res && res.message) || 'Ritten verwijderd.');
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
            });
        const pickupChoice = await promptPickupAdjustment(ridePickupInstant(ride), {
            requireNewTime: true,
        });
        if (pickupChoice === undefined || pickupChoice === false) {
            return;
        }
        setButtonLoading(btn, true, 'Versturen…');
        try {
            const res = await api('/dispatch/rides/' + rideId + '/propose-pickup', {
                method: 'POST',
                body: { pickup_at: pickupChoice },
            });
            vibrate(80);
            alert((res && res.message) || 'Voorstel verstuurd naar de klant.');
            setMainTab('trips');
            await refreshInbox();
        } catch (e) {
            alert(e.message || 'Voorstel versturen mislukt.');
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
        if (
            !window.confirm(
                'Weet je zeker dat je deze contractrit wilt afronden? Openstaande stops worden als niet uitgevoerd gemarkeerd.'
            )
        ) {
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
        if (!window.confirm('Weet je zeker dat je deze rit wilt vrijgeven? Een andere chauffeur kan hem dan overnemen.')) {
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
        if (
            !window.confirm(
                'Retour vrijgeven? Een andere chauffeur kan de terugweg overnemen. De heenrit blijft geregistreerd.'
            )
        ) {
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
            const res = await api('/dispatch/rides/' + rideId + '/complete', { method: 'POST' });
            vibrate(100);
            if (res && res.data && res.data.outbound_completed && res.data.ride) {
                activeRideAcceptedMessage = res.message || 'Heenrit afgerond.';
                renderActiveRide(res.data.ride);
                await refreshInbox();
                updateEmptyState();
                return;
            }
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
        if (!isOnline) {
            inboxLoading = false;
            inboxHasLoaded = false;
        }
        setOnlineUi();
        updateProfileOnlineStatus();
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
            applyEarningsPermissions(me.permissions || {});
            renderProfileUser(me.user || null);
            const active = me.user && me.user.is_account_active !== false;
            setAccountInactive(!active);
            applyOnlineStateFromServer(me.user && me.user.is_online);
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
            token = '';
            sessionStorage.removeItem(STORAGE_KEY);
            sessionStorage.removeItem(COMPANY_KEY);
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
            await refreshScheduledRidesOnly();
            updateEmptyState();
        }
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
                setMainTab(tab);
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
                    alert((err && err.message) || 'Accepteren mislukt.');
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

    const btnDismissNotificationsHint = $('#btn-dismiss-notifications-hint');
    if (btnDismissNotificationsHint) {
        btnDismissNotificationsHint.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            dismissNotificationsHint();
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
    });

    initCashConfirmDialog();
    initArchiveDeleteConfirmDialog();
    initDeclineReasonDialog();
    initPickupAdjustDialog();

    bootstrap();
})();
