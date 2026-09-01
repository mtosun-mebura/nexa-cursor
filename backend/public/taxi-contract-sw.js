/* PWA service worker for contract portal. */
const CACHE = 'nexa-taxi-contract-v2';
const CONTRACT_URL = '/taxi/contract';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key.startsWith('nexa-taxi-contract-') && key !== CACHE)
                    .map((key) => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }
    const url = new URL(event.request.url);
    if (url.pathname.startsWith('/api/')) {
        return;
    }
    if (
        url.pathname === '/taxi/contract' ||
        url.pathname === '/taxi/contract/handleiding' ||
        url.pathname.startsWith('/assets/js/taxi-contract-app')
    ) {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(event.request))
        );
    }
});
