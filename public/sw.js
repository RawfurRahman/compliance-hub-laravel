const CACHE_NAME = 'compliancehub-v1';
const PRECACHE_URLS = [
  '/',
  '/dashboard',
  '/dashboard/kpis',
  '/dashboard/heatmap',
  '/dashboard/top-risks',
  '/dashboard/control-effectiveness',
  '/dashboard/compliance-scorecard',
  '/dashboard/audit-findings',
  '/dashboard/risk-by-department',
  '/dashboard/issues-and-remediation',
  '/dashboard/risk-acceptance-split',
  '/js/app.js',
  '/css/app.css',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_URLS).then(() => {
        self.skipWaiting();
      });
    })
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((name) => {
          if (name !== CACHE_NAME) {
            return caches.delete(name);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip non-HTML/JSON requests for offline support
  if (!event.request.url.startsWith(self.location.origin)) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }

      return fetch(event.request).then((networkResponse) => {
        // Don't cache non-200 responses
        if (networkResponse.status !== 200 || networkResponse.type !== 'basic') {
          return networkResponse;
        }

        // Clone the response
        const responseToCache = networkResponse.clone();

        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });

        return networkResponse;
      }).catch(() => {
        // If offline and no cache, return offline fallback
        if (navigator.onLine === false) {
          return caches.match('/').then((response) => {
            return response || new Response('Offline mode - some features may be limited.', { status: 503 });
          });
        }
        throw error;
      });
    })
  );
});