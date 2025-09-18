/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useRef,
  useState,
} from 'react';
import { api, type TokenMeta } from '@/lib/api';
import {
  User,
  AuthContextType,
  LoginCredentials,
  RegisterData,
  AuthResponse,
} from '@/types/auth';

const REFRESH_LEEWAY_MS = 60 * 1000; // refresh one minute before expiry

const AuthContext = createContext<AuthContextType | undefined>(undefined);

/**
 * Authentication context provider component.
 * Manages user authentication state and provides auth methods.
 */
export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [tokenMeta, setTokenMeta] = useState<TokenMeta | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const refreshTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const refreshExpiryTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const clearRefreshTimer = useCallback(() => {
    if (refreshTimerRef.current) {
      clearTimeout(refreshTimerRef.current);
      refreshTimerRef.current = null;
    }
    if (refreshExpiryTimerRef.current) {
      clearTimeout(refreshExpiryTimerRef.current);
      refreshExpiryTimerRef.current = null;
    }
  }, []);

  const handleUnauthorized = useCallback(() => {
    clearRefreshTimer();
    setUser(null);
    setToken(null);
    setTokenMeta(null);
    api.clearToken();
  }, [clearRefreshTimer]);

  const scheduleRefresh = useCallback(
    (meta: TokenMeta | null) => {
      clearRefreshTimer();

      if (!meta) {
        return;
      }

      const parseTimestamp = (value?: string | null) => {
        if (!value) {
          return Number.NaN;
        }
        const parsed = Date.parse(value);
        return Number.isNaN(parsed) ? Number.NaN : parsed;
      };

      const accessExpiry = parseTimestamp(meta.expiresAt ?? null);
      const refreshExpiry = parseTimestamp(meta.refreshExpiresAt ?? null);
      const now = Date.now();

      if (!Number.isNaN(refreshExpiry) && refreshExpiry <= now) {
        handleUnauthorized();
        return;
      }

      const candidateTargets: number[] = [];

      if (!Number.isNaN(accessExpiry)) {
        candidateTargets.push(accessExpiry - REFRESH_LEEWAY_MS);
      }

      if (!Number.isNaN(refreshExpiry)) {
        candidateTargets.push(refreshExpiry - REFRESH_LEEWAY_MS);
      }

      if (candidateTargets.length > 0) {
        const nextRefreshAt = Math.min(...candidateTargets);
        const delay = Math.max(nextRefreshAt - now, 0);

        refreshTimerRef.current = setTimeout(async () => {
          const refreshed = await api.refreshAccessToken();
          if (!refreshed) {
            handleUnauthorized();
          }
        }, delay);
      }

      if (!Number.isNaN(refreshExpiry)) {
        const logoutDelay = Math.max(refreshExpiry - now, 0);
        refreshExpiryTimerRef.current = setTimeout(() => {
          handleUnauthorized();
        }, logoutDelay);
      }
    },
    [clearRefreshTimer, handleUnauthorized]
  );

  const processAuthResponse = useCallback(
    (response: AuthResponse) => {
      const { user: userData, token: authToken, token_expires_at, refresh_expires_at } = response;

      const meta: TokenMeta = {
        expiresAt: token_expires_at ?? null,
        refreshExpiresAt: refresh_expires_at ?? null,
      };

      setUser(userData);
      setToken(authToken);
      setTokenMeta(meta);
      api.setToken(authToken, meta);
      scheduleRefresh(meta);
    },
    [scheduleRefresh]
  );

  useEffect(() => {
    const unsubscribeRefresh = api.onTokenRefreshed((newToken, meta, refreshedUser) => {
      setToken(newToken);
      setTokenMeta(meta);
      if (refreshedUser) {
        setUser(refreshedUser);
      }
      scheduleRefresh(meta);
    });

    const unsubscribeUnauthorized = api.onUnauthorized(() => {
      handleUnauthorized();
    });

    const bootstrap = async () => {
      try {
        const storedToken = api.getToken();
        const storedMeta = api.getTokenMeta();

        if (storedToken) {
          const refreshExpiry = storedMeta?.refreshExpiresAt
            ? Date.parse(storedMeta.refreshExpiresAt)
            : Number.NaN;

          if (!Number.isNaN(refreshExpiry) && refreshExpiry <= Date.now()) {
            handleUnauthorized();
            return;
          }

          setToken(storedToken);
          setTokenMeta(storedMeta ?? null);
          scheduleRefresh(storedMeta ?? null);

          const profile = await api.get('/auth/me');
          const profileUser = (profile as any)?.user ?? (profile as any)?.data ?? null;
          if (profileUser) {
            setUser(profileUser as User);
          }
        }
      } catch (error) {
        console.error('Failed to bootstrap auth state:', error);
        handleUnauthorized();
      } finally {
        setIsLoading(false);
      }
    };

    bootstrap();

    return () => {
      unsubscribeRefresh();
      unsubscribeUnauthorized();
      clearRefreshTimer();
    };
  }, [clearRefreshTimer, handleUnauthorized, scheduleRefresh]);

  /**
   * Login user with email and password
   */
  const login = useCallback(
    async (credentials: LoginCredentials) => {
      try {
        const response = await api.post<AuthResponse>('/auth/login', credentials);
        processAuthResponse(response);
      } catch (error) {
        console.error('Login failed:', error);
        throw error;
      }
    },
    [processAuthResponse]
  );

  /**
   * Register new user account
   */
  const register = useCallback(
    async (data: RegisterData) => {
      try {
        const response = await api.post<AuthResponse>('/auth/register', data);
        processAuthResponse(response);
      } catch (error) {
        console.error('Registration failed:', error);
        throw error;
      }
    },
    [processAuthResponse]
  );

  /**
   * Logout user and clear authentication state
   */
  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout', undefined, { retryOnUnauthorized: false });
    } catch (error) {
      console.warn('Logout request failed:', error);
    } finally {
      handleUnauthorized();
    }
  }, [handleUnauthorized]);

  const refreshSession = useCallback(async () => {
    const refreshed = await api.refreshAccessToken();
    if (!refreshed) {
      handleUnauthorized();
    }
    return refreshed;
  }, [handleUnauthorized]);

  const value: AuthContextType = {
    user,
    token,
    isLoading,
    login,
    register,
    logout,
    refreshSession,
    tokenExpiresAt: tokenMeta?.expiresAt ?? null,
    refreshExpiresAt: tokenMeta?.refreshExpiresAt ?? null,
    isAuthenticated: Boolean(user && token),
    isTeacher: user?.role === 'teacher',
    isStudent: user?.role === 'student',
    isAdmin: user?.role === 'admin',
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

/**
 * Hook to access authentication context
 */
export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
