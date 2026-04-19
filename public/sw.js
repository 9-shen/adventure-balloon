/**
 * Booklix Service Worker
 * Strategy:
 *  - Pre-cache offline fallback page on install
 *  - Cache-first for static assets (CSS, JS, images, fonts)
 *  - Network-first for navigation (HTML pages)
 *  - Serve offline.html when navigation fails while offline
 */

const CACHE_NAME = 'booklix-v1';
const OFFLINE_URL = '/offline.html';

// Assets to pre-cache on install
const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-512x512.png',
];

// Install: pre-cache critical assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS);
        })
    );
    // Activate immediately without waiting for existing tabs to close
    self.skipWaiting();
});

// Activate: clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );
    // Take control of all open tabs immediately
    self.clients.claim();
});

// Fetch: strategy depends on request type
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Skip non-GET requests (form submissions, API calls, etc.)
    if (request.method !== 'GET') return;

    // Skip cross-origin requests
    if (!request.url.startsWith(self.location.origin)) return;

    // Skip Livewire/wire requests (they need fresh data)
    if (request.url.includes('/livewire/')) return;

    // Navigation requests: network-first with offline fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    return response;
                })
                .catch(() => {
                    return caches.match(OFFLINE_URL);
                })
        );
        return;
    }

    // Static assets: cache-first (CSS, JS, images, fonts)
    if (isStaticAsset(request.url)) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    // Return cached version, but also update cache in background
                    fetchAndCache(request);
                    return cachedResponse;
                }
                return fetchAndCache(request);
            })
        );
        return;
    }
});

/**
 * Check if a URL is a static asset worth caching
 */
function isStaticAsset(url) {
    return /\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)(\?.*)?$/i.test(url);
}

/**
 * Fetch from network and update cache
 */
function fetchAndCache(request) {
    return fetch(request).then((response) => {
        // Only cache successful responses
        if (response && response.status === 200 && response.type === 'basic') {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
                cache.put(request, responseClone);
            });
        }
        return response;
    });
}
