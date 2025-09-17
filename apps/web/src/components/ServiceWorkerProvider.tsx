/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import { useEffect } from 'react';
import { registerServiceWorker } from '@/lib/serviceWorker';

/**
 * Client component to handle service worker registration.
 * Separated from the main layout to allow server-side rendering.
 */
const ServiceWorkerProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  useEffect(() => {
    if (typeof window !== 'undefined') {
      registerServiceWorker();
    }
  }, []);

  return <>{children}</>;
};

export default ServiceWorkerProvider;