/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

/**
 * Offline-aware API service that falls back to IndexedDB cache when network is unavailable.
 * Provides seamless offline experience for dashboard data.
 */

import { indexedDBService, CACHE_KEYS, cacheDashboardData, getCachedDashboardData } from './indexedDB';

interface ApiResponse<T = any> {
  data: T;
  fromCache: boolean;
  timestamp: number;
}

interface NetworkStatus {
  online: boolean;
  lastOnline: number;
}

class OfflineApiService {
  private baseUrl: string;
  private token: string | null = null;
  private networkStatus: NetworkStatus = {
    online: navigator.onLine,
    lastOnline: Date.now()
  };

  constructor() {
    this.baseUrl = process.env.NEXT_PUBLIC_API_BASE || 'http://localhost:8000/api';
    
    // Initialize IndexedDB
    indexedDBService.init().catch(console.error);
    
    // Listen for network status changes
    this.setupNetworkListeners();
  }

  /**
   * Set authentication token for API requests.
   * @param token Bearer token
   */
  setToken(token: string | null): void {
    this.token = token;
  }

  /**
   * Setup network status listeners.
   */
  private setupNetworkListeners(): void {
    window.addEventListener('online', () => {
      this.networkStatus.online = true;
      this.networkStatus.lastOnline = Date.now();
      this.syncOfflineQueue();
    });

    window.addEventListener('offline', () => {
      this.networkStatus.online = false;
    });
  }

  /**
   * Get current network status.
   * @returns NetworkStatus
   */
  getNetworkStatus(): NetworkStatus {
    return { ...this.networkStatus };
  }

  /**
   * Make API request with offline fallback.
   * @param endpoint API endpoint
   * @param options Request options
   * @param cacheKey Cache key for offline storage
   * @param cacheTTL Cache TTL in minutes
   * @returns Promise<ApiResponse>
   */
  async request<T = any>(
    endpoint: string,
    options: RequestInit = {},
    cacheKey?: string,
    cacheTTL: number = 30
  ): Promise<ApiResponse<T>> {
    const url = `${this.baseUrl}${endpoint}`;
    
    // Add authentication header
    const headers = {
      'Content-Type': 'application/json',
      ...options.headers,
      ...(this.token && { Authorization: `Bearer ${this.token}` })
    };

    // Try network request first
    if (this.networkStatus.online) {
      try {
        const response = await fetch(url, {
          ...options,
          headers
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        
        // Cache successful GET requests
        if (cacheKey && options.method !== 'POST' && options.method !== 'PUT' && options.method !== 'DELETE') {
          await indexedDBService.setCache(cacheKey, data, cacheTTL);
        }

        return {
          data,
          fromCache: false,
          timestamp: Date.now()
        };
      } catch (error) {
        console.warn('Network request failed, trying cache:', error);
        this.networkStatus.online = false;
      }
    }

    // Fallback to cache for GET requests
    if (cacheKey && (!options.method || options.method === 'GET')) {
      const cachedData = await indexedDBService.getCache(cacheKey);
      
      if (cachedData) {
        return {
          data: cachedData,
          fromCache: true,
          timestamp: Date.now()
        };
      }
    }

    // Queue non-GET requests for later
    if (options.method && options.method !== 'GET') {
      await indexedDBService.addToOfflineQueue(url, options.method, options.body, headers);
      throw new Error('Request queued for when online');
    }

    throw new Error('No network connection and no cached data available');
  }

  /**
   * Sync offline queue when network becomes available.
   */
  private async syncOfflineQueue(): Promise<void> {
    try {
      const queue = await indexedDBService.getOfflineQueue();
      
      if (queue.length === 0) return;

      console.log(`Syncing ${queue.length} offline requests...`);

      const syncPromises = queue.map(async (item) => {
        try {
          await fetch(item.url, {
            method: item.method,
            headers: item.headers,
            body: item.body
          });
        } catch (error) {
          console.error('Failed to sync offline request:', error);
          throw error;
        }
      });

      await Promise.all(syncPromises);
      await indexedDBService.clearOfflineQueue();
      
      console.log('Offline queue synced successfully');
    } catch (error) {
      console.error('Failed to sync offline queue:', error);
    }
  }

  /**
   * Get dashboard data with offline support.
   * @returns Promise<ApiResponse>
   */
  async getDashboardData(): Promise<ApiResponse> {
    return this.request('/student/dashboard', {}, CACHE_KEYS.DASHBOARD_STATS, 15);
  }

  /**
   * Get recommendations with offline support.
   * @returns Promise<ApiResponse>
   */
  async getRecommendations(): Promise<ApiResponse> {
    return this.request('/student/recommendations', {}, CACHE_KEYS.RECOMMENDATIONS, 60);
  }

  /**
   * Get Ayah of the Day with offline support.
   * @returns Promise<ApiResponse>
   */
  async getAyahOfDay(): Promise<ApiResponse> {
    return this.request('/student/ayah-of-day', {}, CACHE_KEYS.AYAH_OF_DAY, 1440);
  }

  /**
   * Get weekly progress with offline support.
   * @returns Promise<ApiResponse>
   */
  async getWeeklyProgress(): Promise<ApiResponse> {
    return this.request('/student/weekly-progress', {}, CACHE_KEYS.WEEKLY_PROGRESS, 30);
  }

  /**
   * Get leaderboard with offline support.
   * @returns Promise<ApiResponse>
   */
  async getLeaderboard(scope: string = 'global', period: string = 'weekly'): Promise<ApiResponse> {
    return this.request(
      `/leaderboard?scope=${scope}&period=${period}`,
      {},
      CACHE_KEYS.LEADERBOARD,
      10
    );
  }

  /**
   * Update recitation progress (will be queued if offline).
   * @param surahId Surah ID
   * @param ayahId Ayah ID
   * @param hasanat Hasanat earned
   * @returns Promise<ApiResponse>
   */
  async updateRecitation(surahId: number, ayahId: number, hasanat: number): Promise<ApiResponse> {
    return this.request('/student/update-recitation', {
      method: 'POST',
      body: JSON.stringify({ surah_id: surahId, ayah_id: ayahId, hasanat })
    });
  }

  /**
   * Get cache statistics.
   * @returns Promise<{count: number, size: number, networkStatus: NetworkStatus}>
   */
  async getCacheInfo(): Promise<{count: number, size: number, networkStatus: NetworkStatus}> {
    const stats = await indexedDBService.getCacheStats();
    return {
      ...stats,
      networkStatus: this.getNetworkStatus()
    };
  }

  /**
   * Clear all cached data.
   * @returns Promise<void>
   */
  async clearCache(): Promise<void> {
    await indexedDBService.clearCache();
  }

  /**
   * Preload dashboard data for offline use.
   * @returns Promise<void>
   */
  async preloadDashboardData(): Promise<void> {
    if (!this.networkStatus.online) return;

    try {
      const [dashboard, recommendations, ayahOfDay, weeklyProgress, leaderboard] = await Promise.all([
        this.getDashboardData(),
        this.getRecommendations(),
        this.getAyahOfDay(),
        this.getWeeklyProgress(),
        this.getLeaderboard()
      ]);

      await cacheDashboardData({
        userStats: dashboard.data,
        recommendations: recommendations.data,
        ayahOfDay: ayahOfDay.data,
        weeklyProgress: weeklyProgress.data,
        leaderboard: leaderboard.data
      });

      console.log('Dashboard data preloaded for offline use');
    } catch (error) {
      console.error('Failed to preload dashboard data:', error);
    }
  }
}

// Export singleton instance
export const offlineApi = new OfflineApiService();

/**
 * Hook for React components to use offline-aware API.
 * @returns OfflineApiService instance and network status
 */
export function useOfflineApi() {
  return {
    api: offlineApi,
    networkStatus: offlineApi.getNetworkStatus()
  };
}

/**
 * Utility function to check if data is stale.
 * @param timestamp Data timestamp
 * @param maxAgeMinutes Maximum age in minutes
 * @returns boolean
 */
export function isDataStale(timestamp: number, maxAgeMinutes: number = 30): boolean {
  return Date.now() - timestamp > maxAgeMinutes * 60 * 1000;
}