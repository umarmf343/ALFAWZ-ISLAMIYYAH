/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

/**
 * Service Worker registration and management utilities.
 * Handles registration, updates, and communication with the service worker.
 */

/**
 * Register the service worker.
 * @returns {Promise<ServiceWorkerRegistration | null>}
 */
export async function registerServiceWorker(): Promise<ServiceWorkerRegistration | null> {
  if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
    console.warn('Service Worker not supported');
    return null;
  }

  try {
    const registration = await navigator.serviceWorker.register('/sw.js', {
      scope: '/'
    });

    console.warn('Service Worker registered successfully:', registration);

    // Handle updates
    registration.addEventListener('updatefound', () => {
      const newWorker = registration.installing;
      if (newWorker) {
        newWorker.addEventListener('statechange', () => {
          if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
            // New service worker is available
            console.warn('New service worker available');
            notifyUpdate();
          }
        });
      }
    });

    // Listen for messages from service worker
    navigator.serviceWorker.addEventListener('message', handleServiceWorkerMessage);

    // Register for background sync if supported
    if ('sync' in window.ServiceWorkerRegistration.prototype) {
      console.warn('Background sync supported');
    }

    return registration;
  } catch (error) {
    console.error('Service Worker registration failed:', error);
    return null;
  }
}

/**
 * Handle messages from service worker.
 * @param {MessageEvent} event - The message event
 */
function handleServiceWorkerMessage(event: MessageEvent) {
  const { type, data } = event.data;

  switch (type) {
    case 'SYNC_COMPLETE':
      console.warn(`Background sync completed: ${data.synced} synced, ${data.failed} failed`);
      // Notify UI about sync completion
      window.dispatchEvent(new CustomEvent('sw-sync-complete', { detail: data }));
      break;

    case 'CACHE_UPDATED':
      console.warn('Cache updated');
      window.dispatchEvent(new CustomEvent('sw-cache-updated'));
      break;

    default:
      console.warn('Unknown service worker message:', type);
  }
}

/**
 * Notify user about service worker update.
 */
function notifyUpdate() {
  // Dispatch custom event for UI to handle
  window.dispatchEvent(new CustomEvent('sw-update-available'));
}

/**
 * Update service worker to the latest version.
 * @returns {Promise<void>}
 */
export async function updateServiceWorker(): Promise<void> {
  if (!navigator.serviceWorker.controller) {
    return;
  }

  const registration = await navigator.serviceWorker.getRegistration();
  if (registration?.waiting) {
    // Tell the waiting service worker to skip waiting
    registration.waiting.postMessage({ type: 'SKIP_WAITING' });
    
    // Reload the page to activate the new service worker
    window.location.reload();
  }
}

/**
 * Unregister service worker.
 * @returns {Promise<boolean>}
 */
export async function unregisterServiceWorker(): Promise<boolean> {
  if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
    return false;
  }

  try {
    const registration = await navigator.serviceWorker.getRegistration();
    if (registration) {
      const result = await registration.unregister();
      console.warn('Service Worker unregistered:', result);
      return result;
    }
    return false;
  } catch (error) {
    console.error('Service Worker unregistration failed:', error);
    return false;
  }
}

/**
 * Check if the app is running offline.
 * @returns {boolean}
 */
export function isOffline(): boolean {
  return !navigator.onLine;
}

/**
 * Get service worker version.
 * @returns {Promise<string | null>}
 */
export async function getServiceWorkerVersion(): Promise<string | null> {
  if (!navigator.serviceWorker.controller) {
    return null;
  }

  return new Promise((resolve) => {
    const messageChannel = new MessageChannel();
    messageChannel.port1.onmessage = (event) => {
      resolve(event.data.version || null);
    };

    navigator.serviceWorker.controller.postMessage(
      { type: 'GET_VERSION' },
      [messageChannel.port2]
    );

    // Timeout after 5 seconds
    setTimeout(() => resolve(null), 5000);
  });
}

/**
 * Clear all service worker caches.
 * @returns {Promise<boolean>}
 */
export async function clearServiceWorkerCache(): Promise<boolean> {
  if (!navigator.serviceWorker.controller) {
    return false;
  }

  return new Promise((resolve) => {
    const messageChannel = new MessageChannel();
    messageChannel.port1.onmessage = (event) => {
      resolve(event.data.success || false);
    };

    navigator.serviceWorker.controller.postMessage(
      { type: 'CLEAR_CACHE' },
      [messageChannel.port2]
    );

    // Timeout after 10 seconds
    setTimeout(() => resolve(false), 10000);
  });
}

/**
 * Request background sync.
 * @param {string} tag - Sync tag identifier
 * @returns {Promise<void>}
 */
export async function requestBackgroundSync(tag: string = 'background-sync'): Promise<void> {
  if (!('serviceWorker' in navigator) || !('sync' in window.ServiceWorkerRegistration.prototype)) {
    console.warn('Background sync not supported');
    return;
  }

  try {
    const registration = await navigator.serviceWorker.ready;
    await registration.sync.register(tag);
    console.warn('Background sync registered:', tag);
  } catch (error) {
    console.error('Background sync registration failed:', error);
  }
}

/**
 * React hook for service worker functionality.
 * @returns {object} Service worker utilities and state
 */
export function useServiceWorker() {
  const [isSupported, setIsSupported] = React.useState(false);
  const [isRegistered, setIsRegistered] = React.useState(false);
  const [updateAvailable, setUpdateAvailable] = React.useState(false);
  const [isOnline, setIsOnline] = React.useState(true);

  React.useEffect(() => {
    // Check support
    setIsSupported('serviceWorker' in navigator);
    setIsOnline(navigator.onLine);

    // Register service worker
    if ('serviceWorker' in navigator) {
      registerServiceWorker().then((registration) => {
        setIsRegistered(!!registration);
      });
    }

    // Listen for online/offline events
    const handleOnline = () => {
      setIsOnline(true);
      // Request background sync when coming back online
      requestBackgroundSync();
    };
    const handleOffline = () => setIsOnline(false);

    // Listen for service worker events
    const handleUpdateAvailable = () => setUpdateAvailable(true);
    const handleSyncComplete = (event: CustomEvent) => {
      console.warn('Sync completed:', event.detail);
      // Refresh data or show notification
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    window.addEventListener('sw-update-available', handleUpdateAvailable);
    window.addEventListener('sw-sync-complete', handleSyncComplete as EventListener);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
      window.removeEventListener('sw-update-available', handleUpdateAvailable);
      window.removeEventListener('sw-sync-complete', handleSyncComplete as EventListener);
    };
  }, []);

  return {
    isSupported,
    isRegistered,
    updateAvailable,
    isOnline,
    updateServiceWorker,
    clearCache: clearServiceWorkerCache,
    requestSync: requestBackgroundSync
  };
}

// Import React for the hook
import React from 'react';