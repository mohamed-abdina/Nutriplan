// Service Worker for NutriPlan PWA
const CACHE_NAME = 'nutriplan-v1';
const STATIC_ASSETS = [
  '/',
  '/index.php',
  '/login.php',
  '/register.php',
  '/dashboard.php',
  '/search.php',
  '/shopping.php',
  '/profile.php',
  '/assets/css/style.css',
  '/assets/js/main.js',
  '/manifest.json'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('Service Worker installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('Caching app shell');
      return cache.addAll(STATIC_ASSETS);
    })
  );
  
  self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('Service Worker activating...');
  
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  
  return self.clients.claim();
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip cross-origin requests
  if (url.origin !== location.origin) {
    return;
  }
  
  // API requests - network first
  if (url.pathname.includes('/api/')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response.ok) {
            return response;
          }
          throw new Error('API error');
        })
        .catch(() => {
          return caches.match(request);
        })
    );
    return;
  }
  
  // Static assets - cache first
  event.respondWith(
    caches.match(request).then((cacheResponse) => {
      if (cacheResponse) {
        return cacheResponse;
      }
      
      return fetch(request).then((networkResponse) => {
        if (!networkResponse || networkResponse.status !== 200 || networkResponse.type === 'error') {
          return networkResponse;
        }
        
        // Clone the response
        const responseClone = networkResponse.clone();
        
        // Cache successful responses
        if (request.method === 'GET') {
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(request, responseClone);
          });
        }
        
        return networkResponse;
      }).catch(() => {
        // Return offline page or cached version
        return caches.match(request) || new Response('Offline - page not cached');
      });
    })
  );
});

// Background sync for offline actions
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-shopping') {
    event.waitUntil(
      // Sync shopping list changes
      fetch('/api/shopping_action.php', { method: 'POST' })
        .then(() => console.log('Shopping list synced'))
        .catch(err => console.error('Sync failed:', err))
    );
  }
});

// Message handler for client communication
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

console.log('Service Worker loaded and ready');
