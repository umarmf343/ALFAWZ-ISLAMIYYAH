/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import { useState, useEffect, useCallback } from 'react';
import { toast } from '@/components/ui/use-toast';

interface MemorizationPlan {
  id: string;
  title: string;
  surahs: number[];
  daily_target: number;
  start_date: string;
  end_date?: string;
  status: 'active' | 'completed' | 'paused';
  progress_percentage: number;
  average_confidence: number;
  due_today_count: number;
  is_completed: boolean;
  estimated_completion?: string;
  created_at: string;
}

interface DueReview {
  id: string;
  plan_id: string;
  plan_title: string;
  surah_id: number;
  ayah_id: number;
  due_date: string;
  confidence_score: number;
  difficulty_level: 'easy' | 'medium' | 'hard';
  ayah_text: string;
  is_mastered: boolean;
}

interface CreatePlanData {
  title: string;
  surahs: number[];
  daily_target: number;
  start_date: string;
  end_date?: string;
  is_teacher_visible: boolean;
}

interface ReviewSubmission {
  srs_id: string;
  confidence_score: number;
  notes?: string;
  audio_file?: Blob;
}

/**
 * Custom hook for managing memorization plans and SRS reviews.
 * Provides API integration for creating plans, fetching due reviews, and submitting reviews.
 */
export function useMemorization() {
  const [plans, setPlans] = useState<MemorizationPlan[]>([]);
  const [dueReviews, setDueReviews] = useState<DueReview[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  /**
   * Get authentication token from localStorage.
   */
  const getAuthToken = useCallback(() => {
    return localStorage.getItem('token');
  }, []);

  /**
   * Make authenticated API request.
   */
  const apiRequest = useCallback(async (url: string, options: RequestInit = {}) => {
    const token = getAuthToken();
    if (!token) {
      throw new Error('No authentication token found');
    }

    const response = await fetch(`${process.env.NEXT_PUBLIC_API_BASE}${url}`, {
      ...options,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        ...options.headers,
      },
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ message: 'Request failed' }));
      throw new Error(errorData.message || `HTTP ${response.status}`);
    }

    return response.json();
  }, [getAuthToken]);

  /**
   * Fetch memorization plans from API.
   */
  const fetchPlans = useCallback(async () => {
    try {
      setIsLoading(true);
      setError(null);
      const data = await apiRequest('/memorization/plans');
      setPlans(data.plans || []);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch plans';
      setError(errorMessage);
      console.error('Failed to fetch plans:', err);
    } finally {
      setIsLoading(false);
    }
  }, [apiRequest]);

  /**
   * Fetch due reviews from API.
   */
  const fetchDueReviews = useCallback(async () => {
    try {
      setIsLoading(true);
      setError(null);
      const data = await apiRequest('/memorization/due-reviews');
      setDueReviews(data.due_reviews || []);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch due reviews';
      setError(errorMessage);
      console.error('Failed to fetch due reviews:', err);
    } finally {
      setIsLoading(false);
    }
  }, [apiRequest]);

  /**
   * Create a new memorization plan.
   */
  const createPlan = useCallback(async (planData: CreatePlanData) => {
    try {
      setIsLoading(true);
      setError(null);
      const data = await apiRequest('/memorization/plans', {
        method: 'POST',
        body: JSON.stringify(planData),
      });
      
      // Refresh plans after creation
      await fetchPlans();
      
      return data;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to create plan';
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [apiRequest, fetchPlans]);

  /**
   * Submit a review for an SRS item.
   */
  const submitReview = useCallback(async (reviewData: ReviewSubmission) => {
    try {
      setIsLoading(true);
      setError(null);
      
      const formData = new FormData();
      formData.append('srs_id', reviewData.srs_id);
      formData.append('confidence_score', reviewData.confidence_score.toString());
      
      if (reviewData.notes) {
        formData.append('notes', reviewData.notes);
      }
      
      if (reviewData.audio_file) {
        formData.append('audio_file', reviewData.audio_file, 'recitation.wav');
      }

      const token = getAuthToken();
      if (!token) {
        throw new Error('No authentication token found');
      }

      const response = await fetch(`${process.env.NEXT_PUBLIC_API_BASE}/memorization/review`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
        body: formData,
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ message: 'Request failed' }));
        throw new Error(errorData.message || `HTTP ${response.status}`);
      }

      const data = await response.json();
      
      // Refresh due reviews after submission
      await fetchDueReviews();
      
      return data;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to submit review';
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [apiRequest, fetchDueReviews, getAuthToken]);

  /**
   * Get plan statistics.
   */
  const getPlanStats = useCallback(async (planId: string) => {
    try {
      setError(null);
      const data = await apiRequest(`/memorization/plans/${planId}/stats`);
      return data;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch plan stats';
      setError(errorMessage);
      throw err;
    }
  }, [apiRequest]);

  /**
   * Refresh all data.
   */
  const refreshData = useCallback(async () => {
    await Promise.all([
      fetchPlans(),
      fetchDueReviews()
    ]);
  }, [fetchPlans, fetchDueReviews]);

  /**
   * Initialize data on mount.
   */
  useEffect(() => {
    refreshData();
  }, [refreshData]);

  return {
    plans,
    dueReviews,
    isLoading,
    error,
    createPlan,
    submitReview,
    getPlanStats,
    refreshData,
    fetchPlans,
    fetchDueReviews,
  };
}

export type { MemorizationPlan, DueReview, CreatePlanData, ReviewSubmission };