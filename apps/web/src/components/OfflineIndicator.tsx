/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { WifiIcon, CloudIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';
import { offlineApi } from '@/lib/offlineApi';

interface OfflineIndicatorProps {
  className?: string;
}

interface CacheInfo {
  count: number;
  size: number;
  networkStatus: {
    online: boolean;
    lastOnline: number;
  };
}

/**
 * Offline status indicator component.
 * Shows network status and cached data information to users.
 */
export default function OfflineIndicator({ className = '' }: OfflineIndicatorProps) {
  const [isOnline, setIsOnline] = useState(navigator.onLine);
  const [showDetails, setShowDetails] = useState(false);
  const [cacheInfo, setCacheInfo] = useState<CacheInfo | null>(null);
  const [lastSync, setLastSync] = useState<Date | null>(null);

  /**
   * Update network status and cache information.
   */
  const updateStatus = async () => {
    setIsOnline(navigator.onLine);
    
    try {
      const info = await offlineApi.getCacheInfo();
      setCacheInfo(info);
      
      if (info.networkStatus.online) {
        setLastSync(new Date());
      }
    } catch (error) {
      console.error('Failed to get cache info:', error);
    }
  };

  /**
   * Setup network event listeners and initial status check.
   */
  useEffect(() => {
    updateStatus();

    const handleOnline = () => {
      setIsOnline(true);
      setLastSync(new Date());
      updateStatus();
    };

    const handleOffline = () => {
      setIsOnline(false);
      updateStatus();
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    // Update status every 30 seconds
    const interval = setInterval(updateStatus, 30000);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
      clearInterval(interval);
    };
  }, []);

  /**
   * Format cache size for display.
   * @param bytes Size in bytes
   * @returns Formatted string
   */
  const formatCacheSize = (bytes: number): string => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  };

  /**
   * Format time since last sync.
   * @param date Last sync date
   * @returns Formatted string
   */
  const formatLastSync = (date: Date): string => {
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    
    const days = Math.floor(hours / 24);
    return `${days}d ago`;
  };

  /**
   * Clear cached data.
   */
  const handleClearCache = async () => {
    try {
      await offlineApi.clearCache();
      await updateStatus();
    } catch (error) {
      console.error('Failed to clear cache:', error);
    }
  };

  return (
    <div className={`relative ${className}`}>
      {/* Status Indicator */}
      <motion.button
        onClick={() => setShowDetails(!showDetails)}
        className={`flex items-center space-x-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
          isOnline
            ? 'bg-green-100 text-green-800 hover:bg-green-200'
            : 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'
        }`}
        whileHover={{ scale: 1.02 }}
        whileTap={{ scale: 0.98 }}
      >
        {isOnline ? (
          <WifiIcon className="w-4 h-4" />
        ) : (
          <ExclamationTriangleIcon className="w-4 h-4" />
        )}
        <span>{isOnline ? 'Online' : 'Offline'}</span>
        {!isOnline && cacheInfo && (
          <span className="text-xs opacity-75">
            ({cacheInfo.count} cached)
          </span>
        )}
      </motion.button>

      {/* Details Panel */}
      <AnimatePresence>
        {showDetails && (
          <motion.div
            initial={{ opacity: 0, y: -10, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -10, scale: 0.95 }}
            className="absolute top-full right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 p-4 z-50"
          >
            <div className="space-y-4">
              {/* Network Status */}
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-2">
                  {isOnline ? (
                    <WifiIcon className="w-5 h-5 text-green-600" />
                  ) : (
                    <ExclamationTriangleIcon className="w-5 h-5 text-yellow-600" />
                  )}
                  <span className="font-medium">
                    {isOnline ? 'Connected' : 'Offline Mode'}
                  </span>
                </div>
                <div className={`w-3 h-3 rounded-full ${
                  isOnline ? 'bg-green-500' : 'bg-yellow-500'
                }`} />
              </div>

              {/* Last Sync */}
              {lastSync && (
                <div className="flex items-center justify-between text-sm text-gray-600">
                  <span>Last sync:</span>
                  <span>{formatLastSync(lastSync)}</span>
                </div>
              )}

              {/* Cache Information */}
              {cacheInfo && (
                <div className="space-y-2">
                  <div className="flex items-center space-x-2">
                    <CloudIcon className="w-4 h-4 text-gray-500" />
                    <span className="text-sm font-medium">Cached Data</span>
                  </div>
                  
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <span className="text-gray-500">Items:</span>
                      <div className="font-medium">{cacheInfo.count}</div>
                    </div>
                    <div>
                      <span className="text-gray-500">Size:</span>
                      <div className="font-medium">{formatCacheSize(cacheInfo.size)}</div>
                    </div>
                  </div>
                </div>
              )}

              {/* Offline Message */}
              {!isOnline && (
                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                  <div className="flex items-start space-x-2">
                    <ExclamationTriangleIcon className="w-4 h-4 text-yellow-600 mt-0.5 flex-shrink-0" />
                    <div className="text-sm text-yellow-800">
                      <p className="font-medium">You&apos;re offline</p>
                      <p className="mt-1">
                        Showing cached data. Changes will sync when you&apos;re back online.
                      </p>
                    </div>
                  </div>
                </div>
              )}

              {/* Actions */}
              <div className="flex space-x-2 pt-2 border-t border-gray-200">
                {cacheInfo && cacheInfo.count > 0 && (
                  <button
                    onClick={handleClearCache}
                    className="flex-1 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md transition-colors"
                  >
                    Clear Cache
                  </button>
                )}
                
                {isOnline && (
                  <button
                    onClick={() => offlineApi.preloadDashboardData()}
                    className="flex-1 px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
                  >
                    Preload Data
                  </button>
                )}
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Click outside to close */}
      {showDetails && (
        <div
          className="fixed inset-0 z-40"
          onClick={() => setShowDetails(false)}
        />
      )}
    </div>
  );
}

/**
 * Simple offline badge component for minimal display.
 */
export function OfflineBadge({ className = '' }: { className?: string }) {
  const [isOnline, setIsOnline] = useState(navigator.onLine);

  useEffect(() => {
    const handleOnline = () => setIsOnline(true);
    const handleOffline = () => setIsOnline(false);

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  if (isOnline) return null;

  return (
    <motion.div
      initial={{ opacity: 0, scale: 0.8 }}
      animate={{ opacity: 1, scale: 1 }}
      className={`inline-flex items-center space-x-1 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full ${className}`}
    >
      <ExclamationTriangleIcon className="w-3 h-3" />
      <span>Offline</span>
    </motion.div>
  );
}