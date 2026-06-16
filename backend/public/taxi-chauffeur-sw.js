/* PWA service worker: cache + telefoonmeldingen voor nieuwe ritten. */
const CACHE = 'nexa-taxi-chauffeur-v8';
const DEFAULT_ICON = '/favicon.ico';
const CHAUFFEUR_URL = '/taxi/chauffeur';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key.startsWith('nexa-taxi-chauffeur-') && key !== CACHE)
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
        url.pathname === '/taxi/chauffeur' ||
        url.pathname.startsWith('/assets/js/taxi-driver-app')
    ) {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(event.request))
        );
        return;
    }
});

self.addEventListener('message', (event) => {
    const data = event.data;
    if (!data || data.type !== 'SHOW_RIDE_NOTIFICATION') {
        return;
    }
    const title = data.title || 'Nieuwe rit beschikbaar';
    const body = data.body || 'Reageer snel om de rit te accepteren.';
    const icon = data.icon || DEFAULT_ICON;
    const tag = data.tag || 'nexa-new-ride-offer';
    const url = data.url || CHAUFFEUR_URL;

    event.waitUntil(
        self.registration.showNotification(title, {
            body: body,
            icon: icon,
            badge: icon,
            tag: tag,
            renotify: true,
            vibrate: [200, 100, 200, 100, 200],
            data: { url: url },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) || CHAUFFEUR_URL;
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if (client.url.indexOf('/taxi/chauffeur') !== -1 && 'focus' in client) {
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
            return undefined;
        })
    );
});
