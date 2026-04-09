/**
 * KhaboonForum Service Worker - FIXED VERSION for PWA
 * Proper caching strategy: Network-first for dynamic content, cache for static assets
 */

const CACHE_NAME = 'khaboonforum-pwa-v2';
const OFFLINE_URL = '/offline.html';

// Static assets to cache on install (CSS, JS, fonts, icons)
const STATIC_ASSETS = [
    '/css/style.css',
    '/js/main.js',
    '/offline.html',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap'
];

// Dynamic pages that should NOT be cached (network-first)
const DYNAMIC_PAGES = [
    '/index.php',
    '/post.php',
    '/create.php',
    '/api/'
];

// Install event - precache static assets only
self.addEventListener('install', event => {
    console.log('Service Worker: Installing and caching static assets');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('Service Worker: Activating and cleaning old caches');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Service Worker: Deleting old cache', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - Smart caching strategy
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }
    
    // Check if this is a dynamic page that should NOT be cached
    const isDynamicPage = DYNAMIC_PAGES.some(page => url.pathname.includes(page));
    
    // Check if this is an uploaded image
    const isUploadedImage = url.pathname.includes('/uploads/');
    
    // Strategy 1: Dynamic pages (PHP files) - Network first, no cache
    if (isDynamicPage) {
        console.log('Service Worker: Dynamic page - network only', url.pathname);
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    // If network fails and it's a navigation request, show offline page
                    if (event.request.mode === 'navigate') {
                        return caches.match(OFFLINE_URL);
                    }
                    return new Response('Network error', { status: 503 });
                })
        );
        return;
    }
    
    // Strategy 2: Uploaded images - Cache first, network fallback
    if (isUploadedImage) {
        console.log('Service Worker: Uploaded image - cache first', url.pathname);
        event.respondWith(
            caches.match(event.request)
                .then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    
                    return fetch(event.request)
                        .then(response => {
                            // Cache the image for future offline use
                            if (response.ok) {
                                const responseClone = response.clone();
                                caches.open(CACHE_NAME)
                                    .then(cache => {
                                        cache.put(event.request, responseClone);
                                    });
                            }
                            return response;
                        })
                        .catch(() => {
                            // Return a placeholder image if offline
                            return new Response(
                                '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"><rect width="200" height="200" fill="#f0f0f0"/><text x="100" y="100" text-anchor="middle" dy=".3em" fill="#999" font-family="Arial" font-size="14">Image offline</text></svg>',
                                { headers: { 'Content-Type': 'image/svg+xml' } }
                            );
                        });
                })
        );
        return;
    }
    
    // Strategy 3: Static assets - Cache first, network fallback
    console.log('Service Worker: Static asset - cache first', url.pathname);
    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                
                return fetch(event.request)
                    .then(response => {
                        // Don't cache API responses or non-OK responses
                        if (!response.ok || url.pathname.includes('/api/')) {
                            return response;
                        }
                        
                        // Cache static assets
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseClone);
                            });
                        
                        return response;
                    })
                    .catch(() => {
                        // For navigation requests, show offline page
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_URL);
                        }
                        
                        // For other requests, return error
                        return new Response('Offline', {
                            status: 503,
                            statusText: 'Service Unavailable'
                        });
                    });
            })
    );
});

// Background sync for offline posts (future enhancement)
self.addEventListener('sync', event => {
    if (event.tag === 'sync-posts') {
        console.log('Service Worker: Background sync for posts');
        event.waitUntil(syncPendingPosts());
    }
});

// Message event for communication with client
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        caches.delete(CACHE_NAME);
    }
});

// Helper function to sync pending posts (simplified)
async function syncPendingPosts() {
    // This would sync posts created while offline
    // For now, just log that sync was attempted
    console.log('Service Worker: Attempting to sync pending posts');
    return Promise.resolve();
}

// Periodic cache cleanup for old images
self.addEventListener('periodicsync', event => {
    if (event.tag === 'cleanup-cache') {
        event.waitUntil(cleanupOldCacheEntries());
    }
});

async function cleanupOldCacheEntries() {
    const cache = await caches.open(CACHE_NAME);
    const requests = await cache.keys();
    const oneWeekAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
    
    for (const request of requests) {
        const url = new URL(request.url);
        if (url.pathname.includes('/uploads/')) {
            const response = await cache.match(request);
            if (response) {
                const dateHeader = response.headers.get('date');
                if (dateHeader) {
                    const responseDate = new Date(dateHeader).getTime();
                    if (responseDate < oneWeekAgo) {
                        await cache.delete(request);
                        console.log('Service Worker: Cleaned up old image', url.pathname);
                    }
                }
            }
        }
    }
}