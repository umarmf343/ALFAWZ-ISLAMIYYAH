/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import { useState, useEffect, useCallback } from 'react';
import { Assignment, Submission } from '../types/assignment';

interface UseAssignmentReturn {
  assignment: Assignment | null;
  submission: Submission | null;
  loading: boolean;
  error: string | null;
  submitAssignment: () => Promise<void>;
  uploadAudio: (audioBlob: Blob) => Promise<void>;
  refreshAssignment: () => Promise<void>;
}

/**
 * Custom hook for managing assignment data and operations.
 * Handles fetching assignment details, submissions, and audio uploads.
 * @param assignmentId ID of the assignment to manage
 */
export const useAssignment = (assignmentId: number): UseAssignmentReturn => {
  const [assignment, setAssignment] = useState<Assignment | null>(null);
  const [submission, setSubmission] = useState<Submission | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  /**
   * Fetch assignment details from API.
   */
  const fetchAssignment = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      const token = localStorage.getItem('token');
      if (!token) {
        throw new Error('Authentication token not found');
      }

      const response = await fetch(`/api/assignments/${assignmentId}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error(`Failed to fetch assignment: ${response.statusText}`);
      }

      const data = await response.json();
      setAssignment(data.data || data);
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch assignment');
      console.error('Assignment fetch error:', err);
    } finally {
      setLoading(false);
    }
  }, [assignmentId]);

  /**
   * Fetch user's submission for this assignment.
   */
  const fetchSubmission = useCallback(async () => {
    try {
      const token = localStorage.getItem('token');
      if (!token) return;

      const response = await fetch(`/api/submissions?assignment_id=${assignmentId}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.ok) {
        const data = await response.json();
        const submissions = data.data || data;
        if (submissions && submissions.length > 0) {
          setSubmission(submissions[0]);
        }
      }
    } catch (err) {
      console.error('Submission fetch error:', err);
    }
  }, [assignmentId]);

  /**
   * Upload audio file for assignment submission.
   * @param audioBlob Recorded audio blob
   */
  const uploadAudio = async (audioBlob: Blob): Promise<void> => {
    try {
      const token = localStorage.getItem('token');
      if (!token) {
        throw new Error('Authentication token not found');
      }

      const formData = new FormData();
      formData.append('audio', audioBlob, 'recording.webm');
      formData.append('assignment_id', assignmentId.toString());

      const response = await fetch('/api/submissions', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: formData
      });

      if (!response.ok) {
        throw new Error(`Failed to upload audio: ${response.statusText}`);
      }

      const data = await response.json();
      setSubmission(data.data || data);
      
    } catch (err) {
      throw new Error(err instanceof Error ? err.message : 'Failed to upload audio');
    }
  };

  /**
   * Submit assignment for grading.
   */
  const submitAssignment = async (): Promise<void> => {
    try {
      if (!submission) {
        throw new Error('No submission found to submit');
      }

      const token = localStorage.getItem('token');
      if (!token) {
        throw new Error('Authentication token not found');
      }

      const response = await fetch(`/api/submissions/${submission.id}`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          status: 'submitted'
        })
      });

      if (!response.ok) {
        throw new Error(`Failed to submit assignment: ${response.statusText}`);
      }

      const data = await response.json();
      setSubmission(data.data || data);
      
    } catch (err) {
      throw new Error(err instanceof Error ? err.message : 'Failed to submit assignment');
    }
  };

  /**
   * Refresh assignment data.
   */
  const refreshAssignment = useCallback(async (): Promise<void> => {
    await Promise.all([
      fetchAssignment(),
      fetchSubmission()
    ]);
  }, [fetchAssignment, fetchSubmission]);

  // Initial data fetch
  useEffect(() => {
    if (assignmentId) {
      fetchAssignment();
      fetchSubmission();
    }
  }, [assignmentId, fetchAssignment, fetchSubmission]);

  return {
    assignment,
    submission,
    loading,
    error,
    submitAssignment,
    uploadAudio,
    refreshAssignment
  };
};

/**
 * Hook for fetching assignments list with filtering and pagination.
 * @param filters Optional filters for assignments
 */
export const useAssignments = (filters?: {
  status?: string;
  search?: string;
  page?: number;
  limit?: number;
}) => {
  const [assignments, setAssignments] = useState<Assignment[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });

  /**
   * Fetch assignments list from API.
   */
  const fetchAssignments = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      
      const token = localStorage.getItem('token');
      if (!token) {
        throw new Error('Authentication token not found');
      }

      const params = new URLSearchParams();
      if (filters?.status) params.append('status', filters.status);
      if (filters?.search) params.append('search', filters.search);
      if (filters?.page) params.append('page', filters.page.toString());
      if (filters?.limit) params.append('limit', filters.limit.toString());

      const response = await fetch(`/api/assignments?${params.toString()}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error(`Failed to fetch assignments: ${response.statusText}`);
      }

      const data = await response.json();
      setAssignments(data.data || data.assignments || []);
      
      if (data.meta) {
        setPagination({
          current_page: data.meta.current_page,
          last_page: data.meta.last_page,
          per_page: data.meta.per_page,
          total: data.meta.total
        });
      }
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch assignments');
      console.error('Assignments fetch error:', err);
    } finally {
      setLoading(false);
    }
  }, [
    filters?.status,
    filters?.search,
    filters?.page,
    filters?.limit
  ]);

  // Fetch assignments when filters change
  useEffect(() => {
    fetchAssignments();
  }, [fetchAssignments]);

  return {
    assignments,
    loading,
    error,
    pagination,
    refreshAssignments: fetchAssignments
  };
};