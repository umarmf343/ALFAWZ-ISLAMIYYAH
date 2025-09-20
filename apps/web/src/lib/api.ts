/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

/**
 * API client configuration and utilities for AlFawz Qur'an Institute.
 * Handles authentication, request/response interceptors, and error handling.
 */

const API_BASE = process.env.NEXT_PUBLIC_API_BASE || 'http://localhost:8000/api';

/**
 * Generic API response type
 */
export interface ApiResponse<T = unknown> {
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export class ApiError extends Error {
  status: number;
  data: unknown;
  response: {
    status: number;
    data: unknown;
    headers: Record<string, string>;
  };

  constructor(message: string, status: number, data: unknown, headers: Headers) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.data = data;
    const headerObject: Record<string, string> = {};
    headers.forEach((value, key) => {
      headerObject[key] = value;
    });
    this.response = {
      status,
      data,
      headers: headerObject,
    };
  }
}

/**
 * API client class with authentication support
 */
class ApiClient {
  private baseURL: string;
  private token: string | null = null;

  constructor(baseURL: string) {
    this.baseURL = baseURL;
    this.loadToken();
  }

  /**
   * Load authentication token from localStorage
   */
  private loadToken(): void {
    if (typeof window !== 'undefined') {
      this.token = localStorage.getItem('auth_token');
    }
  }

  /**
   * Set authentication token and persist to localStorage
   */
  setToken(token: string): void {
    this.token = token;
    if (typeof window !== 'undefined') {
      localStorage.setItem('auth_token', token);
    }
  }

  /**
   * Clear authentication token
   */
  clearToken(): void {
    this.token = null;
    if (typeof window !== 'undefined') {
      localStorage.removeItem('auth_token');
    }
  }

  /**
   * Make authenticated API request
   */
  async request<T = unknown>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    const url = `${this.baseURL}${endpoint}`;
    const { headers: providedHeaders, ...requestInit } = options;
    const headers = this.normalizeHeaders(providedHeaders);
    const isFormData =
      typeof FormData !== 'undefined' && requestInit.body instanceof FormData;

    if (!this.hasHeader(headers, 'Accept')) {
      headers['Accept'] = 'application/json';
    }

    if (!isFormData && !this.hasHeader(headers, 'Content-Type')) {
      headers['Content-Type'] = 'application/json';
    }

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    try {
      const response = await fetch(url, {
        ...requestInit,
        headers,
      });

      const isJson = response.headers.get('content-type')?.includes('application/json');
      const isEmptyBody = response.status === 204 || response.status === 205;
      let parsedBody: unknown = null;

      if (!isEmptyBody) {
        try {
          if (isJson) {
            parsedBody = await response.json();
          } else {
            const textBody = await response.text();
            parsedBody = textBody ? textBody : null;
          }
        } catch (_error) {
          void _error;
          parsedBody = null;
        }
      }

      if (!response.ok) {
        const message =
          (parsedBody && (parsedBody.message || parsedBody.error)) ||
          `Request failed with status ${response.status}`;
        throw new ApiError(message, response.status, parsedBody, response.headers);
      }

      const normalized = this.normalizeResponse<T>(parsedBody);
      return normalized;
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }

  private normalizeResponse<T>(body: unknown): ApiResponse<T> {
    if (body === null || body === undefined) {
      return { data: undefined };
    }

    if (
      typeof body === 'object' &&
      body !== null &&
      'data' in body &&
      (body as Record<string, unknown>).data !== undefined
    ) {
      return body as ApiResponse<T>;
    }

    return { data: body as T };
  }

  private normalizeHeaders(headers?: HeadersInit): Record<string, string> {
    if (!headers) {
      return {};
    }

    if (headers instanceof Headers) {
      const normalized: Record<string, string> = {};
      headers.forEach((value, key) => {
        normalized[key] = value;
      });
      return normalized;
    }

    if (Array.isArray(headers)) {
      return headers.reduce<Record<string, string>>((acc, [key, value]) => {
        acc[key] = value;
        return acc;
      }, {});
    }

    return { ...headers };
  }

  private hasHeader(headers: Record<string, string>, name: string): boolean {
    const target = name.toLowerCase();
    return Object.keys(headers).some((key) => key.toLowerCase() === target);
  }

  /**
   * GET request helper
   */
  async get<T = unknown>(endpoint: string): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: 'GET' });
  }

  /**
   * POST request helper
   */
  async post<T = unknown>(endpoint: string, data?: unknown): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  /**
   * POST helper for multipart/form-data requests
   */
  async postFormData<T = unknown>(endpoint: string, data: FormData): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: data,
    });
  }

  /**
   * PUT request helper
   */
  async put<T = unknown>(endpoint: string, data?: unknown): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  /**
   * DELETE request helper
   */
  async delete<T = unknown>(endpoint: string): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: 'DELETE' });
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

export interface AudioSurah {
  id: number;
  name: string;
  arabic_name?: string;
  verses?: number;
  reciter?: string;
  duration_seconds?: number;
  audio_url: string;
  description?: string;
}

export interface AudioProgressRecord {
  id: number;
  user_id: number;
  surah_id: number;
  surah_name: string;
  position_seconds: number;
  duration_seconds: number | null;
  created_at: string;
  updated_at: string;
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
export const submitReview = async (reviewId: number, submission: ReviewSubmission): Promise<ApiResponse<SrsReview>> => {
  const formData = new FormData();
  formData.append('confidence', submission.confidence.toString());

  if (submission.audio_file) {
    formData.append('audio_file', submission.audio_file);
  }

  return api.postFormData<SrsReview>(`/student/memorization/reviews/${reviewId}`, formData);
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
export const reviewAudioSubmission = (reviewId: number, action: 'approve' | 'reject', feedback?: string) =>
  api.post(`/teacher/memorization/reviews/${reviewId}/${action}`, { feedback });

/**
 * Fetch curated surah audio list.
 */
export const getAudioSurahs = () =>
  api.get<{ surahs: AudioSurah[] }>('/student/audio/surahs');

/**
 * Fetch saved audio progress for all surahs.
 */
export const getAudioProgressList = () =>
  api.get<{ progress: AudioProgressRecord[] }>('/student/audio/progress');

/**
 * Fetch saved audio progress for a specific surah.
 */
export const getAudioProgress = (surahId: number) =>
  api.get<AudioProgressRecord | null>(`/student/audio/progress/${surahId}`);

/**
 * Persist the learner's current listening position.
 */
export const saveAudioProgress = (payload: {
  surah_id: number;
  position_seconds: number;
  duration_seconds?: number | null;
}) => api.post<AudioProgressRecord>('/student/audio/progress', payload);
