/**
 * Google Places + Nominatim-adreszoeker voor admin-formulieren.
 * Bind op [data-admin-address-autocomplete]; vult optioneel lat/lng hidden fields.
 */

let mapsLoadPromise = null;
let autocompleteService = null;

function debounce(fn, wait) {
    let timer = null;
    return function debounced(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), wait);
    };
}

function loadGoogleMaps(apiKey) {
    if (!apiKey) {
        return Promise.reject(new Error('Geen Google Maps API key'));
    }
    if (window.google?.maps?.places) {
        return Promise.resolve();
    }
    if (mapsLoadPromise) {
        return mapsLoadPromise;
    }

    mapsLoadPromise = new Promise((resolve, reject) => {
        const callbackName = `adminAddressMapsInit_${Date.now()}`;
        window[callbackName] = () => {
            delete window[callbackName];
            resolve();
        };

        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places&callback=${callbackName}`;
        script.async = true;
        script.onerror = () => {
            mapsLoadPromise = null;
            reject(new Error('Google Maps laden mislukt'));
        };
        document.head.appendChild(script);
    });

    return mapsLoadPromise;
}

function getAutocompleteService() {
    if (!window.google?.maps?.places?.AutocompleteService) {
        return null;
    }
    if (!autocompleteService) {
        autocompleteService = new window.google.maps.places.AutocompleteService();
    }
    return autocompleteService;
}

function fetchGooglePredictions(query, apiKey) {
    return loadGoogleMaps(apiKey)
        .then(() => {
            const service = getAutocompleteService();
            if (!service) {
                return [];
            }

            return new Promise((resolve) => {
                service.getPlacePredictions({ input: query }, (results, status) => {
                    if (status !== window.google.maps.places.PlacesServiceStatus.OK || !Array.isArray(results)) {
                        resolve([]);
                        return;
                    }
                    resolve(
                        results.slice(0, 8).map((item) => ({
                            label: item.description || '',
                            value: item.description || '',
                            place_id: item.place_id || '',
                        })).filter((item) => item.value)
                    );
                });
            });
        })
        .catch(() => []);
}

async function fetchNominatimPredictions(query, searchUrl) {
    const base = String(searchUrl || '').trim();
    if (!base) {
        return [];
    }

    try {
        const url = `${base}?${new URLSearchParams({ q: query, limit: '8' }).toString()}`;
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
            place_id: '',
            lat: row.lat ?? null,
            lng: row.lon ?? null,
        })).filter((item) => item.value);
    } catch {
        return [];
    }
}

function fetchPlaceCoordinates(placeId, apiKey) {
    const normalized = String(placeId || '').trim();
    if (!normalized) {
        return Promise.resolve(null);
    }

    return loadGoogleMaps(apiKey)
        .then(() => new Promise((resolve) => {
            if (!window.google?.maps?.Geocoder) {
                resolve(null);
                return;
            }
            const geocoder = new window.google.maps.Geocoder();
            geocoder.geocode({ placeId: normalized }, (results, status) => {
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
        }))
        .catch(() => null);
}

function geocodeAddress(address, apiKey) {
    const query = String(address || '').trim();
    if (!query) {
        return Promise.resolve(null);
    }

    return loadGoogleMaps(apiKey)
        .then(() => new Promise((resolve) => {
            if (!window.google?.maps?.Geocoder) {
                resolve(null);
                return;
            }
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
        }))
        .catch(() => null);
}

function bindAdminAddressAutocomplete(root) {
    if (!root || root.dataset.adminAddressBound === '1') {
        return;
    }

    const input = root.querySelector('[data-admin-address-input]');
    const panel = root.querySelector('[data-admin-address-suggestions]');
    const latInput = root.querySelector('[data-admin-address-lat]');
    const lngInput = root.querySelector('[data-admin-address-lng]');
    const apiKey = String(root.dataset.googleMapsKey || '').trim();
    const searchUrl = String(root.dataset.addressSearchUrl || '').trim();

    if (!input || !panel) {
        return;
    }

    let suggestions = [];
    let requestId = 0;
    let hideTimer = null;

    function setCoords(lat, lng) {
        if (latInput) {
            latInput.value = lat != null && lat !== '' ? String(lat) : '';
        }
        if (lngInput) {
            lngInput.value = lng != null && lng !== '' ? String(lng) : '';
        }
    }

    function clearCoords() {
        setCoords('', '');
    }

    function hidePanel() {
        panel.innerHTML = '';
        panel.classList.add('hidden');
    }

    function showPanel() {
        panel.classList.remove('hidden');
    }

    function renderPanel() {
        panel.innerHTML = '';
        if (!suggestions.length) {
            hidePanel();
            return;
        }

        suggestions.forEach((item, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'admin-address-suggestion';
            button.setAttribute('role', 'option');
            button.textContent = item.label || item.value;
            button.addEventListener('mousedown', (event) => {
                event.preventDefault();
            });
            button.addEventListener('click', () => {
                selectSuggestion(item);
            });
            if (index === 0) {
                button.setAttribute('aria-selected', 'true');
            }
            panel.appendChild(button);
        });
        showPanel();
    }

    async function selectSuggestion(item) {
        input.value = item.value || '';
        hidePanel();
        suggestions = [];

        if (item.place_id && apiKey) {
            const coords = await fetchPlaceCoordinates(item.place_id, apiKey);
            if (coords) {
                setCoords(coords.lat, coords.lng);
                return;
            }
        }

        if (item.lat != null && item.lng != null) {
            setCoords(item.lat, item.lng);
            return;
        }

        if (apiKey) {
            const coords = await geocodeAddress(item.value, apiKey);
            if (coords) {
                setCoords(coords.lat, coords.lng);
                return;
            }
        }

        clearCoords();
    }

    async function runSearch() {
        const query = String(input.value || '').trim();
        if (query.length < 2) {
            suggestions = [];
            hidePanel();
            return;
        }

        const currentRequest = ++requestId;
        const [googleResults, nominatimResults] = await Promise.all([
            fetchGooglePredictions(query, apiKey),
            fetchNominatimPredictions(query, searchUrl),
        ]);

        if (currentRequest !== requestId) {
            return;
        }

        suggestions = googleResults.length > 0 ? googleResults : nominatimResults;
        renderPanel();
    }

    const debouncedSearch = debounce(runSearch, 220);

    input.addEventListener('input', () => {
        clearCoords();
        debouncedSearch();
    });

    input.addEventListener('focus', () => {
        if (String(input.value || '').trim().length >= 2) {
            runSearch();
        }
    });

    input.addEventListener('blur', () => {
        if (hideTimer) {
            clearTimeout(hideTimer);
        }
        hideTimer = setTimeout(() => {
            hidePanel();
        }, 200);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hidePanel();
            return;
        }
        if (event.key === 'Enter' && suggestions.length > 0) {
            event.preventDefault();
            selectSuggestion(suggestions[0]);
        }
    });

    root.dataset.adminAddressBound = '1';
}

export function initAdminAddressAutocompletes(scope) {
    const container = scope && scope.querySelectorAll ? scope : document;
    container.querySelectorAll('[data-admin-address-autocomplete]').forEach(bindAdminAddressAutocomplete);
}

document.addEventListener('DOMContentLoaded', () => {
    initAdminAddressAutocompletes(document);
});

window.initAdminAddressAutocompletes = initAdminAddressAutocompletes;
