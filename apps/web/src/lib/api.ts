/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

/**
 * API client configuration and utilities for AlFawz Qur'an Institute.
 * Handles authentication, request/response interceptors, and error handling.
 */

import type { User } from '@/types/auth';

const API_BASE = process.env.NEXT_PUBLIC_API_BASE || 'http://localhost:8000/api';
const TOKEN_STORAGE_KEY = 'auth_token';
const TOKEN_META_STORAGE_KEY = 'auth_token_meta';
const REFRESH_ENDPOINT = '/auth/refresh';
const DEFAULT_RETRY_CONFIG = { retryOnUnauthorized: true } as const;

/**
 * Generic API response type
 */
export interface ApiResponse<T = any> {
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
  [key: string]: unknown;
}

export interface TokenMeta {
  expiresAt: string | null;
  refreshExpiresAt: string | null;
}

export type TokenRefreshHandler = (token: string, meta: TokenMeta, user?: User) => void;

interface RequestConfig {
  retryOnUnauthorized?: boolean;
}

export class ApiError extends Error {
  public status: number;
  public payload?: unknown;

  constructor(message: string, status: number, payload?: unknown) {
    super(message);
    this.status = status;
    this.payload = payload;
    Object.setPrototypeOf(this, ApiError.prototype);
  }
}

/**
 * API client class with authentication support
 */
class ApiClient {
  private baseURL: string;
  private token: string | null = null;
  private tokenMeta: TokenMeta | null = null;
  private refreshPromise: Promise<boolean> | null = null;
  private tokenRefreshedHandler?: TokenRefreshHandler;
  private unauthorizedHandler?: () => void;

  constructor(baseURL: string) {
    this.baseURL = baseURL;
    this.loadToken();
  }

  /**
   * Load authentication token from localStorage
   */
  private loadToken(): void {
    if (typeof window === 'undefined') {
      return;
    }

    this.token = localStorage.getItem(TOKEN_STORAGE_KEY);

    const storedMeta = localStorage.getItem(TOKEN_META_STORAGE_KEY);
    if (storedMeta) {
      try {
        this.tokenMeta = JSON.parse(storedMeta) as TokenMeta;
      } catch (error) {
        console.warn('Failed to parse stored token metadata', error);
        localStorage.removeItem(TOKEN_META_STORAGE_KEY);
        this.tokenMeta = null;
      }
    }
  }

  private persistToken(token: string | null): void {
    if (typeof window === 'undefined') {
      return;
    }

    if (token) {
      localStorage.setItem(TOKEN_STORAGE_KEY, token);
    } else {
      localStorage.removeItem(TOKEN_STORAGE_KEY);
    }
  }

  private persistTokenMeta(meta: TokenMeta | null): void {
    if (typeof window === 'undefined') {
      return;
    }

    if (meta) {
      localStorage.setItem(TOKEN_META_STORAGE_KEY, JSON.stringify(meta));
    } else {
      localStorage.removeItem(TOKEN_META_STORAGE_KEY);
    }
  }

  /**
   * Set authentication token and persist to localStorage
   */
  setToken(token: string, meta?: TokenMeta): void {
    this.token = token;

    if (meta !== undefined) {
      this.tokenMeta = meta;
    }

    this.persistToken(token);
    this.persistTokenMeta(meta ?? this.tokenMeta);
  }

  /**
   * Clear authentication token
   */
  clearToken(): void {
    this.token = null;
    this.tokenMeta = null;
    this.persistToken(null);
    this.persistTokenMeta(null);
  }

  getToken(): string | null {
    return this.token;
  }

  getTokenMeta(): TokenMeta | null {
    return this.tokenMeta;
  }

  onTokenRefreshed(handler: TokenRefreshHandler): () => void {
    this.tokenRefreshedHandler = handler;
    return () => {
      if (this.tokenRefreshedHandler === handler) {
        this.tokenRefreshedHandler = undefined;
      }
    };
  }

  onUnauthorized(handler: () => void): () => void {
    this.unauthorizedHandler = handler;
    return () => {
      if (this.unauthorizedHandler === handler) {
        this.unauthorizedHandler = undefined;
      }
    };
  }

  /**
   * Make authenticated API request
   */
  async request<T = any>(
    endpoint: string,
    options: RequestInit = {},
    config: RequestConfig = DEFAULT_RETRY_CONFIG
  ): Promise<T> {
    const url = `${this.baseURL}${endpoint}`;
    const requestInit: RequestInit = {
      ...options,
      headers: this.createAuthHeaders(options.headers, options.body ?? null),
    };

    let response: Response;

    try {
      response = await fetch(url, requestInit);
    } catch (error) {
      throw this.buildNetworkError(error);
    }

    const payload = await this.parseResponseBody(response);

    if (response.ok) {
      return payload as T;
    }

    if (response.status === 401) {
      const shouldRetry = config.retryOnUnauthorized !== false;
      if (shouldRetry) {
        const refreshed = await this.refreshAccessToken();
        if (refreshed) {
          return this.request<T>(endpoint, options, {
            ...config,
            retryOnUnauthorized: false,
          });
        }
      }

      this.handleUnauthorized();
      throw this.buildError(response, payload);
    }

    throw this.buildError(response, payload);
  }

  /**
   * GET request helper
   */
  async get<T = any>(endpoint: string, config?: RequestConfig): Promise<T> {
    return this.request<T>(endpoint, { method: 'GET' }, config ?? DEFAULT_RETRY_CONFIG);
  }

  /**
   * POST request helper
   */
  async post<T = any>(endpoint: string, data?: any, config?: RequestConfig): Promise<T> {
    const body = this.isFormData(data) ? data : data !== undefined ? JSON.stringify(data) : undefined;
    return this.request<T>(
      endpoint,
      {
        method: 'POST',
        body,
      },
      config ?? DEFAULT_RETRY_CONFIG
    );
  }

  /**
   * PUT request helper
   */
  async put<T = any>(endpoint: string, data?: any, config?: RequestConfig): Promise<T> {
    const body = this.isFormData(data) ? data : data !== undefined ? JSON.stringify(data) : undefined;
    return this.request<T>(
      endpoint,
      {
        method: 'PUT',
        body,
      },
      config ?? DEFAULT_RETRY_CONFIG
    );
  }

  /**
   * DELETE request helper
   */
  async delete<T = any>(endpoint: string, config?: RequestConfig): Promise<T> {
    return this.request<T>(endpoint, { method: 'DELETE' }, config ?? DEFAULT_RETRY_CONFIG);
  }

  async refreshAccessToken(): Promise<boolean> {
    if (!this.token) {
      return false;
    }

    if (this.refreshPromise) {
      return this.refreshPromise;
    }

    this.refreshPromise = this.executeRefresh()
      .then(({ token, meta, user }) => {
        this.setToken(token, meta);
        this.tokenRefreshedHandler?.(token, meta, user);
        return true;
      })
      .catch((error) => {
        console.error('Token refresh failed', error);
        return false;
      })
      .finally(() => {
        this.refreshPromise = null;
      });

    return this.refreshPromise;
  }

  private async executeRefresh(): Promise<{ token: string; meta: TokenMeta; user?: User }> {
    const response = await fetch(`${this.baseURL}${REFRESH_ENDPOINT}`, {
      method: 'POST',
      headers: this.createAuthHeaders(undefined, null),
    });

    const payload = await this.parseResponseBody(response);

    if (!response.ok) {
      throw this.buildError(response, payload);
    }

    const token = (payload as ApiResponse).token as string | undefined;
    if (!token) {
      throw new ApiError('Refresh response missing token', response.status, payload);
    }

    const meta: TokenMeta = {
      expiresAt: (payload as ApiResponse).token_expires_at as string | null ?? null,
      refreshExpiresAt: (payload as ApiResponse).refresh_expires_at as string | null ?? null,
    };

    const user = (payload as ApiResponse).user as User | undefined;

    return { token, meta, user };
  }

  private async parseResponseBody(response: Response): Promise<ApiResponse> {
    const contentType = response.headers.get('content-type');

    if (contentType && contentType.includes('application/json')) {
      const raw = await response.text();
      if (!raw) {
        return {};
      }

      try {
        return JSON.parse(raw) as ApiResponse;
      } catch (error) {
        console.warn('Failed to parse JSON response', error);
        return {};
      }
    }

    return {};
  }

  private buildError(response: Response, payload: unknown): ApiError {
    const message =
      (payload as ApiResponse)?.message || response.statusText || 'API request failed';

    return new ApiError(message, response.status, payload);
  }

  private buildNetworkError(error: unknown): ApiError {
    if (error instanceof ApiError) {
      return error;
    }

    const message = error instanceof Error ? error.message : 'Network request failed';
    return new ApiError(message, 0, error);
  }

  private createAuthHeaders(init?: HeadersInit, body?: BodyInit | null): Headers {
    const headers = new Headers(init);
    headers.set('Accept', 'application/json');

    if (!this.isFormData(body) && !headers.has('Content-Type')) {
      headers.set('Content-Type', 'application/json');
    }

    if (this.token) {
      headers.set('Authorization', `Bearer ${this.token}`);
    }

    return headers;
  }

  private handleUnauthorized(): void {
    const hadToken = Boolean(this.token);
    this.clearToken();

    if (hadToken) {
      this.unauthorizedHandler?.();
    }
  }

  private isFormData(value: unknown): value is FormData {
    return typeof FormData !== 'undefined' && value instanceof FormData;
  }
}

// Export singleton instance
export const api = new ApiClient(API_BASE);

// Memorization API Types
export interface MemorizationPlan {
  id: number;
  title: string;
  surahs: number[];
  daily_target: number;
  start_date: string;
  end_date: string;
  status: 'active' | 'completed' | 'paused';
  completion_percentage: number;
  created_at: string;
  updated_at: string;
}

export interface SrsReview {
  id: number;
  plan_id: number;
  surah_id: number;
  ayah_id: number;
  ease_factor: number;
  interval: number;
  repetitions: number;
  confidence_score: number;
  due_at: string;
  last_reviewed_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface ReviewSubmission {
  confidence: 1 | 2 | 3 | 4 | 5;
  audio_file?: File;
}

/**
 * Health check endpoint
 */
export const checkHealth = () => api.get('/health');

/**
 * Create a new memorization plan.
 */
export const createMemorizationPlan = (planData: {
  title: string;
  surahs: number[];
  daily_target: number;
  start_date: string;
  end_date: string;
}) => api.post<MemorizationPlan>('/student/memorization/plans', planData);

/**
 * Get user's memorization plans.
 */
export const getMemorizationPlans = () => api.get<MemorizationPlan[]>('/student/memorization/plans');

/**
 * Get due reviews for memorization.
 */
export const getDueReviews = (limit: number = 10) =>
  api.get<SrsReview[]>(`/student/memorization/due-reviews?limit=${limit}`);

/**
 * Submit a review for an ayah with file upload support.
 */
export const submitReview = (
  reviewId: number,
  submission: ReviewSubmission,
): Promise<ApiResponse<SrsReview>> => {
  const formData = new FormData();
  formData.append('confidence', submission.confidence.toString());

  if (submission.audio_file) {
    formData.append('audio_file', submission.audio_file);
  }

  return api.post<ApiResponse<SrsReview>>(
    `/student/memorization/reviews/${reviewId}`,
    formData,
  );
};

/**
 * Get Quran surah information.
 */
export const getSurahInfo = (surahId: number) => api.get(`/quran/surah?id=${surahId}`);

/**
 * Get user profile information.
 */
export const getUserProfile = () => api.get('/me');

/**
 * Get memorization students for teacher oversight
 */
export const getMemorizationStudents = () => api.get('/teacher/memorization/students');

/**
 * Get memorization analytics for teacher dashboard
 */
export const getMemorizationAnalytics = () => api.get('/teacher/memorization/analytics');

/**
 * Get audio reviews for teacher oversight
 */
export const getAudioReviews = () => api.get('/teacher/memorization/audio-reviews');

/**
 * Approve or reject audio review submission
 */
export const reviewAudioSubmission = (
  reviewId: number,
  action: 'approve' | 'reject',
  feedback?: string,
) => api.post(`/teacher/memorization/reviews/${reviewId}/${action}`, { feedback });

