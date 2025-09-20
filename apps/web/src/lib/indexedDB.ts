/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

/**
 * IndexedDB service for offline caching of dashboard data.
 * Provides methods to store, retrieve, and manage cached data.
 */

interface CacheItem {
  id: string;
  data: unknown;
  timestamp: number;
  expiresAt?: number;
}

interface DashboardCache {
  userStats?: unknown;
  recommendations?: unknown;
  ayahOfDay?: unknown;
  weeklyProgress?: unknown;
  leaderboard?: unknown;
}

interface MemorizationPlan {
  id: number;
  title: string;
  surah_id: number;
  ayah_start: number;
  ayah_end: number;
  target_date: string;
  status: 'active' | 'completed' | 'paused';
  created_at: string;
  updated_at: string;
}

interface DueReview {
  id: number;
  memorization_plan_id: number;
  surah_id: number;
  ayah_id: number;
  due_date: string;
  easiness_factor: number;
  interval_days: number;
  repetitions: number;
  last_reviewed_at?: string;
}

interface OfflineReview {
  id: string;
  memorization_plan_id: number;
  surah_id: number;
  ayah_id: number;
  quality: number;
  audio_blob?: Blob;
  reviewed_at: string;
  synced: boolean;
}

class IndexedDBService {
  private dbName = 'alfawz-quran-cache';
  private version = 1;
  private db: IDBDatabase | null = null;

  /**
   * Initialize IndexedDB connection and create object stores.
   * @returns Promise<void>
   */
  async init(): Promise<void> {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.version);

      request.onerror = () => reject(request.error);
      request.onsuccess = () => {
        this.db = request.result;
        resolve();
      };

      request.onupgradeneeded = (event) => {
        const db = (event.target as IDBOpenDBRequest).result;
        
        // Create cache store
        if (!db.objectStoreNames.contains('cache')) {
          const cacheStore = db.createObjectStore('cache', { keyPath: 'id' });
          cacheStore.createIndex('timestamp', 'timestamp', { unique: false });
        }

        // Create offline queue store for pending requests
        if (!db.objectStoreNames.contains('offlineQueue')) {
          db.createObjectStore('offlineQueue', { keyPath: 'id', autoIncrement: true });
        }

        // Create memorization plans store
        if (!db.objectStoreNames.contains('memorizationPlans')) {
          const plansStore = db.createObjectStore('memorizationPlans', { keyPath: 'id' });
          plansStore.createIndex('status', 'status', { unique: false });
        }

        // Create due reviews store
        if (!db.objectStoreNames.contains('dueReviews')) {
          const reviewsStore = db.createObjectStore('dueReviews', { keyPath: 'id' });
          reviewsStore.createIndex('due_date', 'due_date', { unique: false });
          reviewsStore.createIndex('memorization_plan_id', 'memorization_plan_id', { unique: false });
        }

        // Create offline reviews store
        if (!db.objectStoreNames.contains('offlineReviews')) {
          const offlineStore = db.createObjectStore('offlineReviews', { keyPath: 'id' });
          offlineStore.createIndex('synced', 'synced', { unique: false });
          offlineStore.createIndex('memorization_plan_id', 'memorization_plan_id', { unique: false });
        }
      };
    });
  }

  /**
   * Store data in cache with optional expiration.
   * @param key Cache key identifier
   * @param data Data to cache
   * @param ttlMinutes Time to live in minutes (default: 30)
   * @returns Promise<void>
   */
  async setCache(key: string, data: unknown, ttlMinutes: number = 30): Promise<void> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['cache'], 'readwrite');
    const store = transaction.objectStore('cache');
    
    const cacheItem: CacheItem = {
      id: key,
      data,
      timestamp: Date.now(),
      expiresAt: Date.now() + (ttlMinutes * 60 * 1000)
    };

    return new Promise((resolve, reject) => {
      const request = store.put(cacheItem);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  /**
   * Retrieve data from cache if not expired.
   * @param key Cache key identifier
   * @returns Promise<any | null>
   */
  async getCache<T = unknown>(key: string): Promise<T | null> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['cache'], 'readonly');
    const store = transaction.objectStore('cache');

    return new Promise((resolve, reject) => {
      const request = store.get(key);
      
      request.onsuccess = () => {
        const result = request.result as CacheItem;
        
        if (!result) {
          resolve(null);
          return;
        }

        // Check if expired
        if (result.expiresAt && Date.now() > result.expiresAt) {
          this.deleteCache(key); // Clean up expired data
          resolve(null);
          return;
        }

        resolve(result.data as T);
      };
      
      request.onerror = () => reject(request.error);
    });
  }

  /**
   * Delete specific cache entry.
   * @param key Cache key identifier
   * @returns Promise<void>
   */
  async deleteCache(key: string): Promise<void> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['cache'], 'readwrite');
    const store = transaction.objectStore('cache');

    return new Promise((resolve, reject) => {
      const request = store.delete(key);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  /**
   * Clear all cached data.
   * @returns Promise<void>
   */
  async clearCache(): Promise<void> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['cache'], 'readwrite');
    const store = transaction.objectStore('cache');

    return new Promise((resolve, reject) => {
      const request = store.clear();
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  /**
   * Add request to offline queue for later processing.
   * @param url Request URL
   * @param method HTTP method
   * @param body Request body
   * @param headers Request headers
   * @returns Promise<void>
   */
  async addToOfflineQueue(
    url: string,
    method: string,
    body?: unknown,
    headers?: Record<string, string>
  ): Promise<void> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['offlineQueue'], 'readwrite');
    const store = transaction.objectStore('offlineQueue');
    
    const queueItem: OfflineQueueItem = {
      url,
      method,
      body,
      headers,
      timestamp: Date.now()
    };

    return new Promise((resolve, reject) => {
      const request = store.add(queueItem);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  /**
   * Get all pending offline requests.
   * @returns Promise<any[]>
   */
  async getOfflineQueue(): Promise<OfflineQueueItem[]> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['offlineQueue'], 'readonly');
    const store = transaction.objectStore('offlineQueue');

    return new Promise((resolve, reject) => {
      const request = store.getAll();
      request.onsuccess = () => resolve(request.result as OfflineQueueItem[]);
      request.onerror = () => reject(request.error);
    });
  }

  /**
   * Clear offline queue after successful sync.
   * @returns Promise<void>
   */
  async clearOfflineQueue(): Promise<void> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['offlineQueue'], 'readwrite');
    const store = transaction.objectStore('offlineQueue');

    return new Promise((resolve, reject) => {
      const request = store.clear();
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  /**
   * Get cache size and statistics.
   * @returns Promise<{count: number, size: number}>
   */
  async getCacheStats(): Promise<{count: number, size: number}> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['cache'], 'readonly');
    const store = transaction.objectStore('cache');

    return new Promise((resolve, reject) => {
      const request = store.getAll();
      
      request.onsuccess = () => {
        const items = request.result;
        const size = JSON.stringify(items).length;
        resolve({ count: items.length, size });
      };
      
      request.onerror = () => reject(request.error);
    });
  }

  // Memorization-specific methods

  /**
   * Cache memorization plans for offline access.
   * @param plans Array of memorization plans
   * @returns Promise<void>
   */
  async cacheMemorizationPlans(plans: MemorizationPlan[]): Promise<void> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['memorizationPlans'], 'readwrite');
    const store = transaction.objectStore('memorizationPlans');

    return new Promise((resolve, reject) => {
      const promises = plans.map(plan => {
        return new Promise<void>((res, rej) => {
          const request = store.put(plan);
          request.onsuccess = () => res();
          request.onerror = () => rej(request.error);
        });
      });

      Promise.all(promises)
        .then(() => resolve())
        .catch(reject);
    });
  }

  /**
   * Get cached memorization plans.
   * @returns Promise<MemorizationPlan[]>
   */
  async getCachedMemorizationPlans(): Promise<MemorizationPlan[]> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['memorizationPlans'], 'readonly');
    const store = transaction.objectStore('memorizationPlans');

    return new Promise((resolve, reject) => {
      const request = store.getAll();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  /**
   * Cache due reviews for offline access.
   * @param reviews Array of due reviews
   * @returns Promise<void>
   */
  async cacheDueReviews(reviews: DueReview[]): Promise<void> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['dueReviews'], 'readwrite');
    const store = transaction.objectStore('dueReviews');

    return new Promise((resolve, reject) => {
      const promises = reviews.map(review => {
        return new Promise<void>((res, rej) => {
          const request = store.put(review);
          request.onsuccess = () => res();
          request.onerror = () => rej(request.error);
        });
      });

      Promise.all(promises)
        .then(() => resolve())
        .catch(reject);
    });
  }

  /**
   * Get cached due reviews.
   * @returns Promise<DueReview[]>
   */
  async getCachedDueReviews(): Promise<DueReview[]> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['dueReviews'], 'readonly');
    const store = transaction.objectStore('dueReviews');

    return new Promise((resolve, reject) => {
      const request = store.getAll();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  /**
   * Store offline review for later sync.
   * @param review Offline review data
   * @returns Promise<void>
   */
  async storeOfflineReview(review: OfflineReview): Promise<void> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['offlineReviews'], 'readwrite');
    const store = transaction.objectStore('offlineReviews');

    return new Promise((resolve, reject) => {
      const request = store.put(review);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  /**
   * Get unsynced offline reviews.
   * @returns Promise<OfflineReview[]>
   */
  async getUnsyncedReviews(): Promise<OfflineReview[]> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['offlineReviews'], 'readonly');
    const store = transaction.objectStore('offlineReviews');
    const index = store.index('synced');

    return new Promise((resolve, reject) => {
      const request = index.getAll(false);
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  /**
   * Mark offline review as synced.
   * @param reviewId Review ID to mark as synced
   * @returns Promise<void>
   */
  async markReviewSynced(reviewId: string): Promise<void> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['offlineReviews'], 'readwrite');
    const store = transaction.objectStore('offlineReviews');

    return new Promise((resolve, reject) => {
      const getRequest = store.get(reviewId);
      
      getRequest.onsuccess = () => {
        const review = getRequest.result;
        if (review) {
          review.synced = true;
          const putRequest = store.put(review);
          putRequest.onsuccess = () => resolve();
          putRequest.onerror = () => reject(putRequest.error);
        } else {
          resolve();
        }
      };
      
      getRequest.onerror = () => reject(getRequest.error);
    });
  }

  /**
   * Clear all memorization-related cached data.
   * @returns Promise<void>
   */
  async clearMemorizationCache(): Promise<void> {
    if (!this.db) await this.init();

    const transaction = this.db!.transaction(['memorizationPlans', 'dueReviews', 'offlineReviews'], 'readwrite');
    
    const promises = [
      new Promise<void>((resolve, reject) => {
        const request = transaction.objectStore('memorizationPlans').clear();
        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
      }),
      new Promise<void>((resolve, reject) => {
        const request = transaction.objectStore('dueReviews').clear();
        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
      }),
      new Promise<void>((resolve, reject) => {
        const request = transaction.objectStore('offlineReviews').clear();
        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
      })
    ];

    return Promise.all(promises).then(() => {});
  }
}

// Export singleton instance
export const indexedDBService = new IndexedDBService();

// Cache keys constants
export const CACHE_KEYS = {
  DASHBOARD_STATS: 'dashboard-stats',
  RECOMMENDATIONS: 'recommendations',
  AYAH_OF_DAY: 'ayah-of-day',
  WEEKLY_PROGRESS: 'weekly-progress',
  LEADERBOARD: 'leaderboard',
  STUDENT_MESSAGES: 'student-messages',
  USER_PROFILE: 'user-profile',
  MEMORIZATION_PLANS: 'memorization-plans',
  DUE_REVIEWS: 'due-reviews',
  QURAN_AYAHS: 'quran-ayahs'
} as const;

/**
 * Helper function to cache dashboard data.
 * @param data Dashboard data object
 * @returns Promise<void>
 */
export async function cacheDashboardData(data: DashboardCache): Promise<void> {
  const promises = [];
  
  if (data.userStats) {
    promises.push(indexedDBService.setCache(CACHE_KEYS.DASHBOARD_STATS, data.userStats, 15));
  }
  
  if (data.recommendations) {
    promises.push(indexedDBService.setCache(CACHE_KEYS.RECOMMENDATIONS, data.recommendations, 60));
  }
  
  if (data.ayahOfDay) {
    promises.push(indexedDBService.setCache(CACHE_KEYS.AYAH_OF_DAY, data.ayahOfDay, 1440)); // 24 hours
  }
  
  if (data.weeklyProgress) {
    promises.push(indexedDBService.setCache(CACHE_KEYS.WEEKLY_PROGRESS, data.weeklyProgress, 30));
  }
  
  if (data.leaderboard) {
    promises.push(indexedDBService.setCache(CACHE_KEYS.LEADERBOARD, data.leaderboard, 10));
  }

  await Promise.all(promises);
}

/**
 * Helper function to get cached dashboard data.
 * @returns Promise<DashboardCache>
 */
export async function getCachedDashboardData(): Promise<DashboardCache> {
  const [userStats, recommendations, ayahOfDay, weeklyProgress, leaderboard] = await Promise.all([
    indexedDBService.getCache(CACHE_KEYS.DASHBOARD_STATS),
    indexedDBService.getCache(CACHE_KEYS.RECOMMENDATIONS),
    indexedDBService.getCache(CACHE_KEYS.AYAH_OF_DAY),
    indexedDBService.getCache(CACHE_KEYS.WEEKLY_PROGRESS),
    indexedDBService.getCache(CACHE_KEYS.LEADERBOARD)
  ]);

  return {
    userStats,
    recommendations,
    ayahOfDay,
    weeklyProgress,
    leaderboard
  };
}

/**
 * Helper function to cache Quran ayah data for offline access.
 * @param surahId Surah ID
 * @param ayahs Array of ayah data
 * @returns Promise<void>
 */
export async function cacheQuranAyahs(surahId: number, ayahs: unknown[]): Promise<void> {
  const cacheKey = `${CACHE_KEYS.QURAN_AYAHS}-${surahId}`;
  await indexedDBService.setCache(cacheKey, ayahs, 1440); // Cache for 24 hours
}

/**
 * Helper function to get cached Quran ayah data.
 * @param surahId Surah ID
 * @returns Promise<any[] | null>
 */
export async function getCachedQuranAyahs<T = unknown>(surahId: number): Promise<T[] | null> {
  const cacheKey = `${CACHE_KEYS.QURAN_AYAHS}-${surahId}`;
  return await indexedDBService.getCache<T[]>(cacheKey);
}

/**
 * Helper function to handle offline memorization review.
 * @param planId Memorization plan ID
 * @param surahId Surah ID
 * @param ayahId Ayah ID
 * @param quality Review quality (0-5)
 * @param audioBlob Optional audio recording
 * @returns Promise<void>
 */
export async function handleOfflineReview(
  planId: number,
  surahId: number,
  ayahId: number,
  quality: number,
  audioBlob?: Blob
): Promise<void> {
  const offlineReview: OfflineReview = {
    id: `offline-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
    memorization_plan_id: planId,
    surah_id: surahId,
    ayah_id: ayahId,
    quality,
    audio_blob: audioBlob,
    reviewed_at: new Date().toISOString(),
    synced: false
  };

  await indexedDBService.storeOfflineReview(offlineReview);
}

/**
 * Helper function to sync offline reviews when online.
 * @param apiEndpoint API endpoint for syncing reviews
 * @returns Promise<number> Number of reviews synced
 */
export async function syncOfflineReviews(apiEndpoint: string): Promise<number> {
  const unsyncedReviews = await indexedDBService.getUnsyncedReviews();
  let syncedCount = 0;

  for (const review of unsyncedReviews) {
    try {
      const formData = new FormData();
      formData.append('memorization_plan_id', review.memorization_plan_id.toString());
      formData.append('surah_id', review.surah_id.toString());
      formData.append('ayah_id', review.ayah_id.toString());
      formData.append('quality', review.quality.toString());
      formData.append('reviewed_at', review.reviewed_at);
      
      if (review.audio_blob) {
        formData.append('audio', review.audio_blob, 'review-audio.webm');
      }

      const response = await fetch(apiEndpoint, {
        method: 'POST',
        body: formData,
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        }
      });

      if (response.ok) {
        await indexedDBService.markReviewSynced(review.id);
        syncedCount++;
      }
    } catch (error) {
      console.error('Failed to sync review:', error);
      // Continue with other reviews
    }
  }

  return syncedCount;
}

/**
 * Helper function to check if the app is offline.
 * @returns boolean
 */
export function isOffline(): boolean {
  return !navigator.onLine;
}

/**
 * Helper function to initialize offline support for memorization.
 * Sets up event listeners for online/offline status.
 * @param onOnline Callback when going online
 * @param onOffline Callback when going offline
 * @returns void
 */
export function initializeOfflineSupport(
  onOnline?: () => void,
  onOffline?: () => void
): void {
  window.addEventListener('online', () => {
    console.warn('App is now online');
    onOnline?.();
  });

  window.addEventListener('offline', () => {
    console.warn('App is now offline');
    onOffline?.();
  });

  // Initialize IndexedDB
  indexedDBService.init().catch(console.error);
}
interface OfflineQueueItem {
  id?: number;
  url: string;
  method: string;
  body?: unknown;
  headers?: Record<string, string>;
  timestamp: number;
}
