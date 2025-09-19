/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

/**
 * Service Worker for offline functionality and background sync.
 * Handles caching strategies and background synchronization.
 */

const CACHE_NAME = 'alfawz-quran-v1';
const STATIC_CACHE = 'alfawz-static-v1';
const API_CACHE = 'alfawz-api-v1';

// Assets to cache on install
const STATIC_ASSETS = [
  '/',
  '/dashboard',
  '/manifest.json',
  // Add other critical assets
];

/**
 * Install event - cache static assets.
 */
self.addEventListener('install', (event) => {
  console.log('Service Worker installing...');
  
  event.waitUntil(
    Promise.all([
      caches.open(STATIC_CACHE).then((cache) => {
        return cache.addAll(STATIC_ASSETS);
      }),
      caches.open(API_CACHE)
    ]).then(() => {
      console.log('Service Worker installed successfully');
      return self.skipWaiting();
    })
  );
});

/**
 * Activate event - clean up old caches.
 */
self.addEventListener('activate', (event) => {
  console.log('Service Worker activating...');
  
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== STATIC_CACHE && cacheName !== API_CACHE) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('Service Worker activated');
      return self.clients.claim();
    })
  );
});

/**
 * Fetch event - implement caching strategies.
 */
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Handle API requests
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(handleApiRequest(request));
    return;
  }

  // Handle static assets
  if (request.destination === 'document' || 
      request.destination === 'script' || 
      request.destination === 'style' ||
      request.destination === 'image') {
    event.respondWith(handleStaticRequest(request));
    return;
  }

  // Default: network first
  event.respondWith(fetch(request));
});

/**
 * Handle API requests with network-first strategy.
 * Falls back to cache if network fails.
 * @param {Request} request - The fetch request
 * @returns {Promise<Response>} - The response
 */
async function handleApiRequest(request) {
  const cache = await caches.open(API_CACHE);
  
  try {
    // Try network first
    const networkResponse = await fetch(request);
    
    // Cache successful GET requests
    if (networkResponse.ok && request.method === 'GET') {
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Network failed, trying cache:', error);
    
    // Fallback to cache
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      // Add header to indicate cached response
      const response = cachedResponse.clone();
      response.headers.set('X-From-Cache', 'true');
      return response;
    }
    
    // Return offline page or error response
    return new Response(
      JSON.stringify({ 
        error: 'No network connection and no cached data available',
        offline: true 
      }),
      {
        status: 503,
        statusText: 'Service Unavailable',
        headers: { 'Content-Type': 'application/json' }
      }
    );
  }
}

/**
 * Handle static requests with cache-first strategy.
 * Falls back to network if not in cache.
 * @param {Request} request - The fetch request
 * @returns {Promise<Response>} - The response
 */
async function handleStaticRequest(request) {
  const cache = await caches.open(STATIC_CACHE);
  
  // Try cache first
  const cachedResponse = await cache.match(request);
  
  if (cachedResponse) {
    return cachedResponse;
  }
  
  try {
    // Fallback to network
    const networkResponse = await fetch(request);
    
    // Cache successful responses
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Failed to fetch static asset:', error);
    
    // Return offline fallback for documents
    if (request.destination === 'document') {
      return caches.match('/') || new Response('Offline', { status: 503 });
    }
    
    throw error;
  }
}

/**
 * Background sync event - sync offline data when online.
 */
self.addEventListener('sync', (event) => {
  console.log('Background sync triggered:', event.tag);
  
  if (event.tag === 'background-sync') {
    event.waitUntil(syncOfflineData());
  }
});

/**
 * Sync offline data with server.
 * @returns {Promise<void>}
 */
async function syncOfflineData() {
  try {
    // Get offline queue from IndexedDB
    const db = await openIndexedDB();
    const transaction = db.transaction(['offlineQueue'], 'readonly');
    const store = transaction.objectStore('offlineQueue');
    const queue = await getAllFromStore(store);
    
    if (queue.length === 0) {
      console.log('No offline data to sync');
      return;
    }
    
    console.log(`Syncing ${queue.length} offline requests...`);
    
    // Process each queued request
    const syncPromises = queue.map(async (item) => {
      try {
        const response = await fetch(item.url, {
          method: item.method,
          headers: item.headers,
          body: item.body
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        
        return { success: true, id: item.id };
      } catch (error) {
        console.error('Failed to sync request:', error);
        return { success: false, id: item.id, error };
      }
    });
    
    const results = await Promise.allSettled(syncPromises);
    
    // Remove successfully synced items
    const successfulIds = results
      .filter(result => result.status === 'fulfilled' && result.value.success)
      .map(result => result.value.id);
    
    if (successfulIds.length > 0) {
      await removeFromOfflineQueue(successfulIds);
      console.log(`Successfully synced ${successfulIds.length} requests`);
    }
    
    // Notify clients about sync completion
    const clients = await self.clients.matchAll();
    clients.forEach(client => {
      client.postMessage({
        type: 'SYNC_COMPLETE',
        synced: successfulIds.length,
        failed: results.length - successfulIds.length
      });
    });
    
  } catch (error) {
    console.error('Background sync failed:', error);
  }
}

/**
 * Open IndexedDB connection.
 * @returns {Promise<IDBDatabase>}
 */
function openIndexedDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('alfawz-quran-cache', 1);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

/**
 * Get all items from IndexedDB store.
 * @param {IDBObjectStore} store - The object store
 * @returns {Promise<any[]>}
 */
function getAllFromStore(store) {
  return new Promise((resolve, reject) => {
    const request = store.getAll();
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

/**
 * Remove items from offline queue.
 * @param {number[]} ids - Array of item IDs to remove
 * @returns {Promise<void>}
 */
async function removeFromOfflineQueue(ids) {
  const db = await openIndexedDB();
  const transaction = db.transaction(['offlineQueue'], 'readwrite');
  const store = transaction.objectStore('offlineQueue');
  
  const deletePromises = ids.map(id => {
    return new Promise((resolve, reject) => {
      const request = store.delete(id);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  });
  
  await Promise.all(deletePromises);
}

/**
 * Message event - handle messages from clients.
 */
self.addEventListener('message', (event) => {
  const { type } = event.data || {};

  switch (type) {
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;
      
    case 'GET_VERSION':
      event.ports[0].postMessage({ version: CACHE_NAME });
      break;
      
    case 'CLEAR_CACHE':
      clearAllCaches().then(() => {
        event.ports[0].postMessage({ success: true });
      }).catch((error) => {
        event.ports[0].postMessage({ success: false, error: error.message });
      });
      break;
      
    default:
      console.log('Unknown message type:', type);
  }
});

/**
 * Clear all caches.
 * @returns {Promise<void>}
 */
async function clearAllCaches() {
  const cacheNames = await caches.keys();
  await Promise.all(cacheNames.map(name => caches.delete(name)));
  console.log('All caches cleared');
}