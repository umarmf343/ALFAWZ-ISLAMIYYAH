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
export interface ApiResponse<T = any> {
  data?: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export class ApiError extends Error {
  status: number;
  data: any;
  response: {
    status: number;
    data: any;
    headers: Record<string, string>;
  };

  constructor(message: string, status: number, data: any, headers: Headers) {
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
  async request<T = any>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    const url = `${this.baseURL}${endpoint}`;
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    // Merge additional headers if provided
    if (options.headers) {
      Object.assign(headers, options.headers);
    }

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers,
      });

      const isJson = response.headers.get('content-type')?.includes('application/json');
      const isEmptyBody = response.status === 204 || response.status === 205;
      let parsedBody: any = null;

      if (!isEmptyBody) {
        try {
          if (isJson) {
            parsedBody = await response.json();
          } else {
            const textBody = await response.text();
            parsedBody = textBody ? textBody : null;
          }
        } catch (parseError) {
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

  private normalizeResponse<T>(body: any): ApiResponse<T> {
    if (body === null || body === undefined) {
      return { data: undefined };
    }

    if (typeof body === 'object' && body !== null && 'data' in body) {
      return body as ApiResponse<T>;
    }

    return { data: body };
  }

  /**
   * GET request helper
   */
  async get<T = any>(endpoint: string): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: 'GET' });
  }

  /**
   * POST request helper
   */
  async post<T = any>(endpoint: string, data?: any): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  /**
   * PUT request helper
   */
  async put<T = any>(endpoint: string, data?: any): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  /**
   * DELETE request helper
   */
  async delete<T = any>(endpoint: string): Promise<ApiResponse<T>> {
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

  const token = api['token'] || (typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null);
  const response = await fetch(`${API_BASE}/student/memorization/reviews/${reviewId}`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      ...(token && { 'Authorization': `Bearer ${token}` }),
    },
    body: formData,
  });

  if (!response.ok) {
    throw new Error(`Review submission failed: ${response.status}`);
  }

  return response.json();
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