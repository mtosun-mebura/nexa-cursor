const GEOLOCATION_ACCURACY_WARN_METERS = 80;

function formatGeolocationAccuracyHint(accuracyMeters) {
    if (!Number.isFinite(accuracyMeters) || accuracyMeters <= GEOLOCATION_ACCURACY_WARN_METERS) {
        return '';
    }

    return `Locatie is bij benadering (±${Math.round(accuracyMeters)} m). Controleer het adres.`;
}

function haversineMeters(lat1, lng1, lat2, lng2) {
    const toRad = Math.PI / 180;
    const dLat = (lat2 - lat1) * toRad;
    const dLng = (lng2 - lng1) * toRad;
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos(lat1 * toRad) * Math.cos(lat2 * toRad) * Math.sin(dLng / 2) ** 2;
    return 6371000 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function googleResultHasStreetNumber(result) {
    return (result.address_components || []).some((c) => (c.types || []).includes('street_number'));
}

function formatGoogleGeocodeAddress(result) {
    if (!result) {
        return '';
    }

    const byType = {};
    (result.address_components || []).forEach((c) => {
        (c.types || []).forEach((t) => {
            if (!byType[t]) {
                byType[t] = c.long_name;
            }
        });
    });

    const street = byType.route || byType.pedestrian || '';
    const number = byType.street_number || '';
    const streetPart = [street, number].filter(Boolean).join(' ').trim();
    const postcode = byType.postal_code || '';
    const city = byType.locality || byType.postal_town || byType.administrative_area_level_2 || '';
    const second = [postcode, city].filter(Boolean).join(' ').trim();
    const value = [streetPart, second].filter(Boolean).join(', ').trim();

    return value || String(result.formatted_address || '').trim();
}

function formatNominatimReverseAddress(row) {
    if (!row || typeof row !== 'object') {
        return null;
    }

    const displayName = String(row.display_name || '').trim();
    if (!row.address) {
        return displayName ? { label: displayName, hasHouseNumber: false, lat: parseFloat(row.lat), lng: parseFloat(row.lon) } : null;
    }

    const a = row.address;
    const street = a.road || a.pedestrian || a.footway || a.cycleway || a.path || '';
    const number = a.house_number || '';
    const city = a.city || a.town || a.village || a.hamlet || a.city_district || a.suburb || a.municipality || '';
    const postcode = a.postcode || '';
    const streetPart = [street, number].filter(Boolean).join(' ').trim();
    const second = [postcode, city].filter(Boolean).join(' ').trim();
    const label = [streetPart, second].filter(Boolean).join(', ').trim() || displayName;

    if (!label) {
        return null;
    }

    const rLat = parseFloat(row.lat);
    const rLng = parseFloat(row.lon);

    return {
        label,
        hasHouseNumber: !!number,
        lat: Number.isFinite(rLat) ? rLat : null,
        lng: Number.isFinite(rLng) ? rLng : null,
    };
}

function pickBestReverseGeocodeResult(results, lat, lng) {
    if (!Array.isArray(results) || results.length === 0) {
        return null;
    }

    const typePenalty = { ROOFTOP: 0, RANGE_INTERPOLATED: 10, GEOMETRIC_CENTER: 70, APPROXIMATE: 100 };
    let best = null;
    let bestScore = Infinity;

    for (const result of results) {
        if (!result.geometry?.location) {
            continue;
        }

        const loc = result.geometry.location;
        const rLat = typeof loc.lat === 'function' ? loc.lat() : parseFloat(loc.lat);
        const rLng = typeof loc.lng === 'function' ? loc.lng() : parseFloat(loc.lng);
        if (!Number.isFinite(rLat) || !Number.isFinite(rLng)) {
            continue;
        }

        const dist = haversineMeters(lat, lng, rLat, rLng);
        const penalty = typePenalty[result.geometry.location_type] || 45;
        const types = result.types || [];
        const isAddress = types.includes('street_address') || types.includes('premise') || types.includes('subpremise');
        let score = dist + penalty;
        if (!isAddress) {
            score += 55;
        }
        if (!googleResultHasStreetNumber(result)) {
            score += 75;
        }
        if (score < bestScore) {
            bestScore = score;
            best = result;
        }
    }

    return best || results[0];
}

function scoreReverseCandidate(candidate, gpsLat, gpsLng) {
    const dist = (Number.isFinite(candidate.lat) && Number.isFinite(candidate.lng))
        ? haversineMeters(gpsLat, gpsLng, candidate.lat, candidate.lng)
        : 0;
    let score = dist;
    if (!candidate.hasHouseNumber) {
        score += 80;
    }
    if (dist > 45) {
        score += 120;
    }
    candidate.distFromGps = dist;
    candidate.score = score;
    return score;
}

function pickBestReverseLabel(lat, lng, candidates) {
    const usable = (candidates || []).filter((c) => c && c.label);
    if (usable.length === 0) {
        return null;
    }

    usable.forEach((c) => scoreReverseCandidate(c, lat, lng));
    usable.sort((a, b) => a.score - b.score);
    return usable[0];
}

function normalizeAddressSearchQuery(query) {
    let value = String(query || '').trim();
    if (value === '') {
        return '';
    }

    const patterns = [
        /^(?:ik\s+)?(?:wil|moet|ga|wilt)\s+(?:graag\s+)?(?:naar|to)\s+/iu,
        /^(?:kan|kun)\s+ik\s+(?:ook\s+)?(?:naar|to)\s+/iu,
        /^(?:wat\s+kost\s+(?:een\s+)?(?:rit|taxirit)\s+)?(?:naar|to)\s+/iu,
        /^(?:boek(?:\s+(?:een|een\s+))?(?:rit|taxirit)|rit\s+boeken)\s+(?:naar|to)\s+/iu,
        /^(?:taxi|taxirit|rit)\s+naar\s+/iu,
    ];

    for (const pattern of patterns) {
        if (pattern.test(value)) {
            value = value.replace(pattern, '').trim();
            break;
        }
    }

    return value;
}

export function registerAiChatbot(Alpine) {
    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-ai-chat-toggle]')) {
            return;
        }
        event.preventDefault();
        window.dispatchEvent(new CustomEvent('ai-chat-toggle'));
    });

    Alpine.data('aiChatbot', (config) => ({
        config: config || {},
        isOpen: false,
        isExpanded: false,
        isTyping: false,
        newMessage: '',
        messages: [],
        sessionId: '',
        addressQuery: '',
        addressSuggestions: [],
        addressSuggestionsOpen: false,
        addressLoading: false,
        addressGeolocationLoading: false,
        addressLocationError: '',
        addressLocationWarning: '',
        addressSelectedFromSuggestions: false,
        addressSelectedPlaceId: '',
        addressSelectedLat: null,
        addressSelectedLng: null,
        pendingQuoteAddress: null,
        pendingQuoteBaggage: null,
        baggageQty: {},
        specialBaggageQty: {},
        baggageShowSpecial: false,
        remarksValue: '',
        datetimeValue: '',
        numberValue: '',
        mapsReady: false,
        mapsLoading: false,
        _placesService: null,
        _addressDebounce: null,

        init() {
            const greeting = this.config.greeting || 'Hallo! Hoe kan ik je helpen?';
            const storageKey = this.config.storageKey || 'ai-chat-messages';
            this.config.storageKey = storageKey;
            const sessionStorageKey = `${storageKey}-session`;
            this.sessionId = localStorage.getItem(sessionStorageKey) || '';
            if (!this.sessionId) {
                this.sessionId = typeof crypto !== 'undefined' && crypto.randomUUID
                    ? crypto.randomUUID()
                    : `sess-${Date.now()}`;
                localStorage.setItem(sessionStorageKey, this.sessionId);
            }

            this._onToggle = () => this.toggleChat();
            this._onEscape = (event) => {
                if (event.key === 'Escape' && this.isOpen) {
                    this.closeChat();
                }
            };
            window.addEventListener('ai-chat-toggle', this._onToggle);
            document.addEventListener('keydown', this._onEscape);

            const savedMessages = localStorage.getItem(storageKey);
            if (savedMessages) {
                try {
                    this.messages = JSON.parse(savedMessages);
                } catch (error) {
                    this.messages = [];
                }
            }

            if (!Array.isArray(this.messages) || this.messages.length === 0) {
                this.messages = [this.createGreetingMessage(greeting)];
            }

            this.resetStructuredInputs();
            this.$nextTick(() => {
                this.applyStructuredInputPrefill(this.activeQuoteInput());
            });
            this.bindMobileViewportListeners();
        },

        isMobileChatViewport() {
            return window.matchMedia('(max-width: 767px)').matches;
        },

        bindMobileViewportListeners() {
            if (this._mobileViewportBound) {
                return;
            }
            this._mobileViewportBound = true;
            this._onViewportChange = () => this.syncMobileViewport();
            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', this._onViewportChange);
                window.visualViewport.addEventListener('scroll', this._onViewportChange);
            }
            window.addEventListener('resize', this._onViewportChange);
        },

        syncMobileViewport() {
            const panel = this.$refs.chatPanel;
            if (!panel || !this.isMobileChatViewport() || !this.isOpen) {
                this.resetMobileViewport();
                return;
            }

            const visualViewport = window.visualViewport;
            if (!visualViewport) {
                return;
            }

            const keyboardLikelyOpen = visualViewport.height < window.innerHeight * 0.85;
            if (keyboardLikelyOpen) {
                const top = Math.max(0, visualViewport.offsetTop);
                panel.classList.add('ai-chat-panel--keyboard');
                panel.style.setProperty('--ai-chat-panel-top', `${top}px`);
                panel.style.setProperty('--ai-chat-panel-height', `${visualViewport.height}px`);
            } else {
                panel.classList.remove('ai-chat-panel--keyboard');
                panel.style.removeProperty('--ai-chat-panel-top');
                panel.style.removeProperty('--ai-chat-panel-height');
            }

            this.$nextTick(() => this.scrollToBottom());
        },

        resetMobileViewport() {
            const panel = this.$refs.chatPanel;
            if (!panel) {
                return;
            }
            panel.classList.remove('ai-chat-panel--keyboard');
            panel.style.removeProperty('--ai-chat-panel-top');
            panel.style.removeProperty('--ai-chat-panel-height');
        },

        onInputFocus() {
            if (!this.isMobileChatViewport()) {
                return;
            }
            setTimeout(() => this.syncMobileViewport(), 50);
            setTimeout(() => this.syncMobileViewport(), 300);
        },

        onInputBlur() {
            if (!this.isMobileChatViewport()) {
                return;
            }
            setTimeout(() => this.syncMobileViewport(), 100);
            setTimeout(() => this.syncMobileViewport(), 350);
        },

        activeQuoteInput() {
            for (let i = this.messages.length - 1; i >= 0; i -= 1) {
                const message = this.messages[i];
                if (message.sender === 'ai') {
                    return message.input || null;
                }
            }

            return null;
        },

        applyStructuredInputPrefill(input) {
            if (!input) {
                return;
            }

            if (input.type === 'address') {
                this.addressQuery = String(input.prefill || '').trim();
                this.addressSelectedFromSuggestions = false;
                this.addressSelectedPlaceId = '';
                this.addressSelectedLat = null;
                this.addressSelectedLng = null;
                if (this.addressQuery.length >= 2) {
                    this.onAddressInput();
                }
                if (String(this.config.googleMapsApiKey || '').trim() !== '') {
                    this.ensureGoogleMaps().catch(() => {});
                }
                return;
            }

            if (input.type === 'datetime') {
                this.datetimeValue = '';
                return;
            }

            if (input.type === 'number') {
                this.numberValue = '';
            }

            if (input.type === 'baggage') {
                this.resetBaggageInputs(input);
            }

            if (input.type === 'text') {
                this.remarksValue = '';
            }
        },

        resetStructuredInputs() {
            this.clearStructuredInputFields();
        },

        clearStructuredInputFields() {
            this.addressQuery = '';
            this.addressSuggestions = [];
            this.addressSuggestionsOpen = false;
            this.addressLoading = false;
            this.addressGeolocationLoading = false;
            this.addressLocationError = '';
            this.addressLocationWarning = '';
            this.addressSelectedFromSuggestions = false;
            this.addressSelectedPlaceId = '';
            this.addressSelectedLat = null;
            this.addressSelectedLng = null;
            this.pendingQuoteBaggage = null;
            this.baggageQty = {};
            this.specialBaggageQty = {};
            this.baggageShowSpecial = false;
            this.remarksValue = '';
            this.datetimeValue = '';
            this.numberValue = '';
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            this.syncHeaderTriggerState();
            if (this.isOpen) {
                if (this.isMobileChatViewport()) {
                    this.isExpanded = false;
                }
                this.$nextTick(() => {
                    this.scrollToBottom();
                    this.focusActiveInput();
                    this.syncMobileViewport();
                    if (this.activeQuoteInput()?.type === 'address') {
                        this.ensureGoogleMaps().catch(() => {});
                    }
                });
            } else {
                this.resetMobileViewport();
            }
        },

        closeChat() {
            if (!this.isOpen) {
                return;
            }
            this.isOpen = false;
            this.isExpanded = false;
            this.resetMobileViewport();
            this.syncHeaderTriggerState();
        },

        toggleExpand() {
            if (this.isMobileChatViewport()) {
                return;
            }
            this.isExpanded = !this.isExpanded;
            this.$nextTick(() => {
                this.scrollToBottom();
            });
        },

        clearChat() {
            if (this.isTyping) {
                return;
            }

            const greeting = this.config.greeting || 'Hallo! Hoe kan ik je helpen?';
            this.messages = [this.createGreetingMessage(greeting)];
            this.newMessage = '';
            this.resetStructuredInputs();

            const storageKey = this.config.storageKey || 'ai-chat-messages';
            const sessionStorageKey = `${storageKey}-session`;
            this.sessionId = typeof crypto !== 'undefined' && crypto.randomUUID
                ? crypto.randomUUID()
                : `sess-${Date.now()}`;
            localStorage.setItem(sessionStorageKey, this.sessionId);
            this.saveMessages();
            this.$nextTick(() => {
                this.scrollToBottom();
            });
        },

        createGreetingMessage(text) {
            return {
                id: Date.now(),
                sender: 'ai',
                text,
                time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }),
            };
        },

        syncHeaderTriggerState() {
            window.dispatchEvent(new CustomEvent('ai-chat-open-changed', {
                detail: { isOpen: this.isOpen, isTyping: this.isTyping },
            }));
            document.querySelectorAll('[data-ai-chat-toggle]').forEach((button) => {
                button.setAttribute('aria-expanded', this.isOpen ? 'true' : 'false');
                button.classList.toggle('bg-gray-100', this.isOpen);
                button.classList.toggle('dark:bg-gray-800', this.isOpen);
                button.classList.toggle('text-gray-900', this.isOpen);
                button.classList.toggle('dark:text-white', this.isOpen);
            });
        },

        focusActiveInput() {
            const active = this.activeQuoteInput();
            if (!active) {
                this.$refs.messageInput?.focus();
                return;
            }

            if (active.type === 'address') {
                this.$refs.addressInput?.focus();
            } else if (active.type === 'datetime') {
                this.$refs.datetimeInput?.focus();
            } else if (active.type === 'number') {
                this.$refs.numberInput?.focus();
            } else if (active.type === 'text') {
                this.$refs.remarksInput?.focus();
            }
        },

        canSubmitStructuredInput() {
            const active = this.activeQuoteInput();
            if (!active || this.isTyping) {
                return false;
            }

            if (active.type === 'address') {
                const query = this.addressQuery.trim();
                if (query.length < 3) {
                    return false;
                }

                if (String(this.config.googleMapsApiKey || '').trim() !== '') {
                    return this.addressSelectedFromSuggestions;
                }

                return true;
            }

            if (active.type === 'datetime') {
                return this.datetimeValue.trim() !== '';
            }

            if (active.type === 'number') {
                return this.numberValue !== '' && this.numberValue !== null;
            }

            if (active.type === 'baggage') {
                return true;
            }

            if (active.type === 'text') {
                const value = this.remarksValue.trim();
                if (active.required === false || active.step === 'remarks') {
                    return true;
                }

                return value.length > 0;
            }

            return false;
        },

        canSubmitTextInput() {
            return !this.activeQuoteInput() && this.newMessage.trim() !== '' && !this.isTyping;
        },

        async submitStructuredInput() {
            const active = this.activeQuoteInput();
            if (!active) {
                return;
            }

            let outgoing = '';
            if (active.type === 'address') {
                outgoing = this.addressQuery.trim();
            } else if (active.type === 'datetime') {
                outgoing = this.datetimeValue.trim();
            } else if (active.type === 'number') {
                outgoing = String(this.numberValue);
            } else if (active.type === 'baggage') {
                outgoing = this.buildBaggageSummaryText(active);
                this.pendingQuoteBaggage = this.buildBaggagePayload();
            } else if (active.type === 'text') {
                const textValue = this.remarksValue.trim();
                if (active.step === 'remarks' || (active.required === false && textValue === '')) {
                    outgoing = textValue !== '' ? textValue : 'geen';
                } else {
                    outgoing = textValue;
                }
            }

            if (active.type !== 'baggage' && active.type !== 'text' && !outgoing) {
                return;
            }

            if (active.type === 'address') {
                this.pendingQuoteAddress = {
                    label: outgoing,
                    place_id: this.addressSelectedPlaceId || null,
                    lat: this.addressSelectedLat,
                    lng: this.addressSelectedLng,
                };
            } else {
                this.pendingQuoteAddress = null;
            }

            if (active.type !== 'baggage') {
                this.pendingQuoteBaggage = null;
            }

            await this.dispatchUserMessage(outgoing);
        },

        async sendMessage() {
            if (!this.newMessage.trim()) {
                return;
            }

            await this.dispatchUserMessage(this.newMessage.trim());
            this.newMessage = '';
        },

        async dispatchUserMessage(outgoing) {
            if (this.config.requiresTenant) {
                this.messages.push({
                    id: Date.now(),
                    sender: 'ai',
                    text: this.config.tenantRequiredMessage
                        || 'Selecteer eerst een bedrijf in de tenant-kiezer.',
                    time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }),
                });
                this.saveMessages();
                this.scrollToBottom();
                return;
            }

            const userMessage = {
                id: Date.now(),
                sender: 'user',
                text: outgoing,
                time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }),
            };

            this.messages.push(userMessage);
            this.isTyping = true;
            this.syncHeaderTriggerState();
            const quoteAddressPayload = this.pendingQuoteAddress;
            const quoteBaggagePayload = this.pendingQuoteBaggage;
            this.clearStructuredInputFields();
            this.saveMessages();
            this.scrollToBottom();

            try {
                const response = await this.callAssistantAPI(outgoing, quoteAddressPayload, quoteBaggagePayload);
                this.messages.push({
                    id: Date.now() + 1,
                    sender: 'ai',
                    text: response.reply,
                    input: response.input || null,
                    time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }),
                });
                this.applyStructuredInputPrefill(response.input || null);
            } catch (error) {
                const fallback = 'Sorry, er is een fout opgetreden. Probeer het later opnieuw.';
                const detail = error instanceof Error && error.message && error.message !== 'Assistant request failed'
                    ? error.message
                    : fallback;
                this.messages.push({
                    id: Date.now() + 1,
                    sender: 'ai',
                    text: detail,
                    time: new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }),
                });
            } finally {
                this.isTyping = false;
                this.pendingQuoteAddress = null;
                this.pendingQuoteBaggage = null;
                this.syncHeaderTriggerState();
                this.saveMessages();
                this.scrollToBottom();
                this.$nextTick(() => {
                    this.focusActiveInput();
                    if (this.activeQuoteInput()?.type === 'address') {
                        this.ensureGoogleMaps().catch(() => {});
                    }
                });
            }
        },

        onAddressInput() {
            this.addressLocationError = '';
            this.addressLocationWarning = '';
            const query = normalizeAddressSearchQuery(this.addressQuery);
            this.addressSelectedFromSuggestions = false;
            this.addressSelectedPlaceId = '';
            this.addressSelectedLat = null;
            this.addressSelectedLng = null;
            if (this._addressDebounce) {
                clearTimeout(this._addressDebounce);
            }

            if (query.length < 2) {
                this.addressSuggestions = [];
                this.addressSuggestionsOpen = false;
                this.addressLoading = false;
                return;
            }

            this.addressLoading = true;
            this._addressDebounce = setTimeout(() => {
                this.fetchAddressSuggestions(query).finally(() => {
                    this.addressLoading = false;
                });
            }, 220);
        },

        async fetchAddressSuggestions(query) {
            let nominatimResults = [];
            let googleSettled = false;

            const nominatimTask = this.fetchNominatimSuggestions(query).then((results) => {
                nominatimResults = Array.isArray(results) ? results : [];
                if (!googleSettled && nominatimResults.length > 0) {
                    this.addressSuggestions = nominatimResults;
                    this.addressSuggestionsOpen = true;
                }
            });

            const googleTask = this.fetchGoogleAddressSuggestions(query).then((results) => {
                googleSettled = true;
                const googleSuggestions = Array.isArray(results) ? results : [];
                if (googleSuggestions.length > 0) {
                    this.addressSuggestions = googleSuggestions;
                    this.addressSuggestionsOpen = true;
                    return;
                }
                if (nominatimResults.length === 0) {
                    this.addressSuggestions = [];
                    this.addressSuggestionsOpen = false;
                }
            });

            await Promise.all([nominatimTask, googleTask]);
        },

        async fetchGoogleAddressSuggestions(query) {
            const apiKey = String(this.config.googleMapsApiKey || '').trim();
            if (!apiKey) {
                return [];
            }

            try {
                await this.ensureGoogleMaps();
            } catch (error) {
                return [];
            }

            const service = this.getPlacesService();
            if (!service || !window.google?.maps?.places) {
                return [];
            }

            return new Promise((resolve) => {
                service.getPlacePredictions({ input: query }, (results, status) => {
                    if (status !== window.google.maps.places.PlacesServiceStatus.OK || !Array.isArray(results)) {
                        resolve([]);
                        return;
                    }

                    resolve(results.slice(0, 8).map((item) => ({
                        label: item.description || '',
                        value: item.description || '',
                        place_id: item.place_id || '',
                    })).filter((item) => item.value));
                });
            });
        },

        async fetchNominatimSuggestions(query) {
            const baseUrl = String(this.config.addressSearchUrl || '').trim();
            if (!baseUrl) {
                return [];
            }

            try {
                const url = `${baseUrl}?${new URLSearchParams({ q: query, limit: '8' }).toString()}`;
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) {
                    return [];
                }

                const rows = await response.json();
                if (!Array.isArray(rows)) {
                    return [];
                }

                return rows.slice(0, 8).map((row) => ({
                    label: row.display_name || row.name || '',
                    value: row.display_name || row.name || '',
                })).filter((item) => item.value);
            } catch (error) {
                return [];
            }
        },

        async selectAddressSuggestion(item) {
            this.addressQuery = item.value;
            this.addressSelectedFromSuggestions = true;
            this.addressSelectedPlaceId = String(item.place_id || '').trim();
            this.addressSelectedLat = null;
            this.addressSelectedLng = null;
            this.addressSuggestions = [];
            this.addressSuggestionsOpen = false;

            if (this.addressSelectedPlaceId) {
                const coords = await this.fetchPlaceCoordinates(this.addressSelectedPlaceId);
                if (coords) {
                    this.addressSelectedLat = coords.lat;
                    this.addressSelectedLng = coords.lng;
                }
            } else if (String(this.config.googleMapsApiKey || '').trim() !== '') {
                const coords = await this.fetchAddressCoordinates(item.value);
                if (coords) {
                    this.addressSelectedLat = coords.lat;
                    this.addressSelectedLng = coords.lng;
                }
            }

            this.$nextTick(() => {
                this.$refs.addressInput?.focus();
            });
        },

        canUseCurrentLocationForAddress() {
            const active = this.activeQuoteInput();

            return active?.type === 'address'
                && active?.step === 'pickup'
                && typeof navigator !== 'undefined'
                && !!navigator.geolocation;
        },

        geolocationErrorMessage(error) {
            const code = error?.code;
            if (code === 1) {
                return 'Locatietoegang geweigerd. Sta locatie toe in je browser of vul het adres handmatig in.';
            }
            if (code === 2) {
                return 'Je locatie kon niet worden bepaald. Probeer het opnieuw of vul het adres handmatig in.';
            }
            if (code === 3) {
                return 'Locatie ophalen duurde te lang. Probeer het opnieuw of vul het adres handmatig in.';
            }

            return 'Je huidige locatie kon niet worden gebruikt. Vul het adres handmatig in.';
        },

        getCurrentPosition() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('unsupported'));
                    return;
                }

                const geoOptions = { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 };
                let best = null;
                let settled = false;
                let watchId = null;
                const deadline = setTimeout(() => {
                    if (settled) {
                        return;
                    }
                    settled = true;
                    if (watchId !== null) {
                        navigator.geolocation.clearWatch(watchId);
                    }
                    if (best) {
                        resolve(best);
                    } else {
                        reject(Object.assign(new Error('timeout'), { code: 3 }));
                    }
                }, 15000);

                const consider = (position) => {
                    if (!best || position.coords.accuracy < best.coords.accuracy) {
                        best = position;
                    }
                    if (position.coords.accuracy <= 35) {
                        if (settled) {
                            return;
                        }
                        settled = true;
                        clearTimeout(deadline);
                        if (watchId !== null) {
                            navigator.geolocation.clearWatch(watchId);
                        }
                        resolve(best);
                    }
                };

                watchId = navigator.geolocation.watchPosition(
                    consider,
                    (error) => {
                        if (settled) {
                            return;
                        }
                        settled = true;
                        clearTimeout(deadline);
                        if (watchId !== null) {
                            navigator.geolocation.clearWatch(watchId);
                        }
                        if (best) {
                            resolve(best);
                        } else {
                            reject(error);
                        }
                    },
                    geoOptions,
                );
            });
        },

        async reverseGeocodeWithGoogle(lat, lng) {
            try {
                await this.ensureGoogleMaps();
            } catch (error) {
                return null;
            }

            if (!window.google?.maps?.Geocoder) {
                return null;
            }

            return new Promise((resolve) => {
                const geocoder = new window.google.maps.Geocoder();
                geocoder.geocode({ location: { lat, lng }, language: 'nl', region: 'NL' }, (results, status) => {
                    const best = status === 'OK' ? pickBestReverseGeocodeResult(results, lat, lng) : null;
                    if (!best?.geometry?.location) {
                        resolve(null);
                        return;
                    }

                    const loc = best.geometry.location;
                    const rLat = typeof loc.lat === 'function' ? loc.lat() : parseFloat(loc.lat);
                    const rLng = typeof loc.lng === 'function' ? loc.lng() : parseFloat(loc.lng);
                    resolve({
                        label: formatGoogleGeocodeAddress(best),
                        place_id: '',
                        hasHouseNumber: googleResultHasStreetNumber(best),
                        lat: Number.isFinite(rLat) ? rLat : null,
                        lng: Number.isFinite(rLng) ? rLng : null,
                    });
                });
            });
        },

        async reverseGeocodeWithNominatim(lat, lng) {
            const baseUrl = String(this.config.addressSearchUrl || '').trim();
            if (!baseUrl) {
                return null;
            }

            try {
                const url = `${baseUrl}?${new URLSearchParams({
                    lat: String(lat),
                    lon: String(lng),
                }).toString()}`;
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) {
                    return null;
                }

                const row = await response.json();
                const parsed = formatNominatimReverseAddress(row);
                if (!parsed?.label) {
                    return null;
                }

                return {
                    label: parsed.label,
                    place_id: '',
                    hasHouseNumber: parsed.hasHouseNumber,
                    lat: parsed.lat,
                    lng: parsed.lng,
                };
            } catch (error) {
                return null;
            }
        },

        async reverseGeocodeCoordinates(lat, lng) {
            const [nominatimResult, googleResult] = await Promise.all([
                this.reverseGeocodeWithNominatim(lat, lng),
                String(this.config.googleMapsApiKey || '').trim() ? this.reverseGeocodeWithGoogle(lat, lng) : Promise.resolve(null),
            ]);
            const best = pickBestReverseLabel(lat, lng, [nominatimResult, googleResult]);
            if (!best?.label) {
                return null;
            }

            return {
                label: best.label,
                place_id: '',
                distFromGps: best.distFromGps || 0,
                hasHouseNumber: !!best.hasHouseNumber,
            };
        },

        async useCurrentLocationForAddress() {
            if (!this.canUseCurrentLocationForAddress() || this.addressGeolocationLoading || this.isTyping) {
                return;
            }

            this.addressLocationError = '';
            this.addressLocationWarning = '';
            this.addressGeolocationLoading = true;
            this.addressSuggestionsOpen = false;

            try {
                const position = await this.getCurrentPosition();
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const resolved = await this.reverseGeocodeCoordinates(lat, lng);

                if (!resolved?.label) {
                    this.addressLocationError = 'Kon je adres niet bepalen. Vul het ophaaladres handmatig in.';
                    return;
                }

                this.addressQuery = resolved.label;
                this.addressSelectedFromSuggestions = true;
                this.addressSelectedPlaceId = '';
                this.addressSelectedLat = lat;
                this.addressSelectedLng = lng;
                this.addressSuggestions = [];
                this.addressSuggestionsOpen = false;

                let warning = formatGeolocationAccuracyHint(position.coords.accuracy);
                if (!warning && resolved.distFromGps > 45) {
                    warning = 'Het ingevulde adres kan enkele huizen verderop liggen. Controleer het adres.';
                } else if (!warning && !resolved.hasHouseNumber) {
                    warning = 'Kon geen huisnummer bepalen. Vul het adres aan indien nodig.';
                }
                this.addressLocationWarning = warning;
            } catch (error) {
                this.addressLocationError = this.geolocationErrorMessage(error);
            } finally {
                this.addressGeolocationLoading = false;
                this.$nextTick(() => {
                    this.$refs.addressInput?.focus();
                });
            }
        },

        async fetchAddressCoordinates(address) {
            const query = String(address || '').trim();
            if (!query) {
                return null;
            }

            try {
                await this.ensureGoogleMaps();
            } catch (error) {
                return null;
            }

            if (!window.google?.maps?.Geocoder) {
                return null;
            }

            return new Promise((resolve) => {
                const geocoder = new window.google.maps.Geocoder();
                geocoder.geocode({ address: query }, (results, status) => {
                    if (status !== 'OK' || !Array.isArray(results) || !results[0]?.geometry?.location) {
                        resolve(null);
                        return;
                    }

                    const location = results[0].geometry.location;
                    resolve({
                        lat: typeof location.lat === 'function' ? location.lat() : parseFloat(location.lat),
                        lng: typeof location.lng === 'function' ? location.lng() : parseFloat(location.lng),
                    });
                });
            });
        },

        async fetchPlaceCoordinates(placeId) {
            const normalizedPlaceId = String(placeId || '').trim();
            if (!normalizedPlaceId) {
                return null;
            }

            try {
                await this.ensureGoogleMaps();
            } catch (error) {
                return null;
            }

            if (!window.google?.maps?.Geocoder) {
                return null;
            }

            return new Promise((resolve) => {
                const geocoder = new window.google.maps.Geocoder();
                geocoder.geocode({ placeId: normalizedPlaceId }, (results, status) => {
                    if (status !== 'OK' || !Array.isArray(results) || !results[0]?.geometry?.location) {
                        resolve(null);
                        return;
                    }

                    const location = results[0].geometry.location;
                    resolve({
                        lat: typeof location.lat === 'function' ? location.lat() : parseFloat(location.lat),
                        lng: typeof location.lng === 'function' ? location.lng() : parseFloat(location.lng),
                    });
                });
            });
        },

        ensureGoogleMaps() {
            if (this.mapsReady && window.google?.maps?.places) {
                return Promise.resolve();
            }

            if (this.mapsLoading) {
                return new Promise((resolve, reject) => {
                    const wait = setInterval(() => {
                        if (this.mapsReady) {
                            clearInterval(wait);
                            resolve();
                        }
                    }, 50);
                    setTimeout(() => {
                        clearInterval(wait);
                        reject(new Error('Google Maps timeout'));
                    }, 10000);
                });
            }

            const apiKey = String(this.config.googleMapsApiKey || '').trim();
            if (!apiKey) {
                return Promise.reject(new Error('Geen Google Maps API key'));
            }

            this.mapsLoading = true;

            return new Promise((resolve, reject) => {
                if (window.google?.maps?.places) {
                    this.mapsReady = true;
                    this.mapsLoading = false;
                    resolve();
                    return;
                }

                const callbackName = `aiChatMapsInit_${Date.now()}`;
                window[callbackName] = () => {
                    this.mapsReady = true;
                    this.mapsLoading = false;
                    delete window[callbackName];
                    resolve();
                };

                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places&callback=${callbackName}`;
                script.async = true;
                script.onerror = () => {
                    this.mapsLoading = false;
                    delete window[callbackName];
                    reject(new Error('Google Maps laden mislukt'));
                };
                document.head.appendChild(script);
            });
        },

        getPlacesService() {
            if (this._placesService) {
                return this._placesService;
            }

            if (!window.google?.maps?.places) {
                return null;
            }

            this._placesService = new window.google.maps.places.AutocompleteService();

            return this._placesService;
        },

        buildHistory() {
            return this.messages
                .filter((message) => message.sender === 'user' || message.sender === 'ai')
                .slice(-10)
                .map((message) => ({
                    role: message.sender === 'user' ? 'user' : 'assistant',
                    text: message.text,
                }));
        },

        defaultBaggageItems() {
            return [
                { key: 'large', title: 'Grote ruimbagage', subtitle: '85cm x 55cm x 35cm', max: 6 },
                { key: 'small', title: 'Kleine ruimbagage', subtitle: '55cm x 45cm x 25cm', max: 6 },
                { key: 'hand', title: 'Handbagage', subtitle: 'Handtas, rugzak, etc.', max: 6 },
            ];
        },

        baggageInputItems(input = null) {
            const source = input ?? this.activeQuoteInput();
            const items = source?.items;
            if (Array.isArray(items) && items.length > 0) {
                return items;
            }
            if (source?.type === 'baggage') {
                return this.defaultBaggageItems();
            }

            return [];
        },

        baggageSpecialItems(input = null) {
            const source = input ?? this.activeQuoteInput();
            const items = source?.special_items;

            return Array.isArray(items) ? items : [];
        },

        resetBaggageInputs(input) {
            const baggage = {};
            const special = {};
            this.baggageInputItems(input).forEach((item) => {
                if (item?.key) {
                    baggage[item.key] = 0;
                }
            });
            this.baggageSpecialItems(input).forEach((item) => {
                if (item?.key) {
                    special[item.key] = 0;
                }
            });
            this.baggageQty = baggage;
            this.specialBaggageQty = special;
            this.baggageShowSpecial = false;
        },

        adjustBaggageQty(key, delta, max = 6) {
            const current = parseInt(this.baggageQty[key] || 0, 10);
            const limit = parseInt(max || 6, 10);
            this.baggageQty[key] = Math.max(0, Math.min(limit, current + delta));
        },

        adjustSpecialBaggageQty(key, delta, max = 6) {
            const current = parseInt(this.specialBaggageQty[key] || 0, 10);
            const limit = parseInt(max || 6, 10);
            this.specialBaggageQty[key] = Math.max(0, Math.min(limit, current + delta));
        },

        buildBaggagePayload() {
            const baggage = {};
            const special = {};
            Object.keys(this.baggageQty || {}).forEach((key) => {
                const qty = parseInt(this.baggageQty[key] || 0, 10);
                if (qty > 0) {
                    baggage[key] = qty;
                }
            });
            if (this.baggageShowSpecial) {
                Object.keys(this.specialBaggageQty || {}).forEach((key) => {
                    const qty = parseInt(this.specialBaggageQty[key] || 0, 10);
                    if (qty > 0) {
                        special[key] = qty;
                    }
                });
            }

            return { baggage, special_baggage: special };
        },

        buildBaggageSummaryText(input) {
            const parts = [];
            this.baggageInputItems(input).forEach((item) => {
                const qty = parseInt(this.baggageQty[item.key] || 0, 10);
                if (qty > 0) {
                    parts.push(`${item.title}: ${qty}`);
                }
            });
            if (this.baggageShowSpecial) {
                this.baggageSpecialItems(input).forEach((item) => {
                    const qty = parseInt(this.specialBaggageQty[item.key] || 0, 10);
                    if (qty > 0) {
                        parts.push(`${item.title}: ${qty}`);
                    }
                });
            }

            return parts.length > 0 ? parts.join(', ') : 'Geen bagage';
        },

        async callAssistantAPI(message, quoteAddress = null, quoteBaggage = null) {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const controller = new AbortController();
            const timeoutId = window.setTimeout(() => controller.abort(), 60000);
            let response;

            try {
                response = await fetch(this.config.endpoint || '/ai-chat/message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        message: message,
                        history: this.buildHistory(),
                        module: this.config.module || 'default',
                        sessionId: this.sessionId,
                        quoteAddress: quoteAddress || undefined,
                        quoteBaggage: quoteBaggage || undefined,
                    }),
                    signal: controller.signal,
                });
            } catch (error) {
                if (error instanceof Error && error.name === 'AbortError') {
                    throw new Error('Het antwoord duurde te lang. Probeer het opnieuw.');
                }

                throw error;
            } finally {
                window.clearTimeout(timeoutId);
            }

            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success || !data.reply) {
                throw new Error(data.error || 'Assistant request failed');
            }

            return {
                reply: data.reply,
                input: data.input || null,
            };
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        saveMessages() {
            localStorage.setItem(this.config.storageKey || 'ai-chat-messages', JSON.stringify(this.messages));
        },

        formatChatMessage(text) {
            if (!text || typeof text !== 'string') {
                return '';
            }

            return this.formatChatBlocks(this.applyChatLinks(text));
        },

        applyChatLinks(text) {
            const escaped = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            return escaped.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (match, label, url) => {
                const safeUrl = this.sanitizeChatUrl(url);
                if (!safeUrl) {
                    return label;
                }

                const safeLabel = label
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                const external = safeUrl.startsWith('http') && !this.isSameOriginUrl(safeUrl);
                const attrs = external
                    ? ' target="_blank" rel="noopener noreferrer"'
                    : '';

                return `<a href="${safeUrl}" class="ai-chat-link"${attrs}>${safeLabel}</a>`;
            });
        },

        formatChatBlocks(text) {
            const lines = text.split('\n');
            const parts = [];
            let bulletItems = [];

            const flushBullets = () => {
                if (bulletItems.length === 0) {
                    return;
                }

                parts.push(
                    `<ul class="ai-chat-list">${bulletItems.map((item) => `<li>${item}</li>`).join('')}</ul>`,
                );
                bulletItems = [];
            };

            for (const rawLine of lines) {
                const line = rawLine.trimEnd();
                const bulletMatch = line.match(/^[-*•–]\s+(.+)$/);

                if (bulletMatch) {
                    bulletItems.push(bulletMatch[1]);
                    continue;
                }

                flushBullets();

                if (line.trim() === '') {
                    parts.push('');
                } else {
                    parts.push(line);
                }
            }

            flushBullets();

            return parts.join('<br>').replace(/(<br>){3,}/g, '<br><br>');
        },

        sanitizeChatUrl(url) {
            const trimmed = String(url).trim();
            if (!/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(trimmed)) {
                return null;
            }

            return trimmed.replace(/"/g, '%22');
        },

        isSameOriginUrl(url) {
            if (url.startsWith('/') || url.startsWith('#')) {
                return true;
            }

            try {
                const parsed = new URL(url, window.location.origin);
                return parsed.origin === window.location.origin;
            } catch (error) {
                return false;
            }
        },
    }));
}
