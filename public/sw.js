// PropertyKoi Service Worker - v2
// Network-First for dynamic HTML pages (including Turbo Drive & SPA navigations)
// Cache-First only for genuine static assets (CSS, JS, Fonts, Images)

const CACHE_NAME = 'propertykoi-cache-v2';
const STATIC_ASSETS = [
    '/manifest.webmanifest',
    '/assets/icons/icon-192x192.png',
    '/assets/icons/icon-512x512.png',
    '/assets/images/og-default.jpg'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => {
                    console.log('[SW] Purging old cache:', key);
                    return caches.delete(key);
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // Bypass Service Worker completely for internal administrative / authenticated / API routes
    if (
        url.pathname.startsWith('/backend') ||
        url.pathname.startsWith('/appcenter') ||
        url.pathname.startsWith('/buyer-dashboard') ||
        url.pathname.startsWith('/api') ||
        url.pathname.startsWith('/install')
    ) {
        return;
    }

    // Identify HTML / Page requests (both native browser navigation and Turbo Drive / AJAX page fetches)
    const acceptHeader = event.request.headers.get('accept') || '';
    const isHtmlRequest =
        event.request.mode === 'navigate' ||
        acceptHeader.includes('text/html') ||
        event.request.destination === 'document';

    if (isHtmlRequest) {
        // ALWAYS Network-First for HTML: Real estate listings must always be fresh from server
        event.respondWith(
            fetch(event.request)
                .then((networkResponse) => {
                    return networkResponse;
                })
                .catch(() => {
                    // Only fallback to cache if network is completely unreachable
                    return caches.match(event.request).then((cached) => {
                        return cached || caches.match('/assets/images/og-default.jpg');
                    });
                })
        );
        return;
    }

    // Check if this is a genuine static asset (images, fonts, compiled CSS/JS)
    const isStaticAsset =
        url.pathname.match(/\.(png|jpe?g|webp|gif|svg|ico|css|js|woff2?|ttf|eot|webmanifest)(\?.*)?$/i) ||
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/assets/');

    if (isStaticAsset) {
        // Cache-First for static assets
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    // Ignore offline static asset failures
                });
            })
        );
    }
});
