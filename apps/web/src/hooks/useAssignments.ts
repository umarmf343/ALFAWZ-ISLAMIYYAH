import { useCallback, useState } from 'react';

import { Assignment, Submission } from '../types/assignment';

interface UseAssignmentsResult {
  assignments: Assignment[];
  submissions: Submission[];
  loading: boolean;
  error: string | null;
  fetchAssignments: () => Promise<void>;
}

const parseAssignments = (payload: unknown): Assignment[] => {
  if (!payload) return [];
  if (Array.isArray(payload)) return payload as Assignment[];
  if (typeof payload === 'object' && Array.isArray((payload as { data?: Assignment[] }).data)) {
    return (payload as { data: Assignment[] }).data;
  }
  return [];
};

const parseSubmissions = (payload: unknown): Submission[] => {
  if (!payload) return [];
  if (Array.isArray(payload)) return payload as Submission[];
  if (typeof payload === 'object' && Array.isArray((payload as { data?: Submission[] }).data)) {
    return (payload as { data: Submission[] }).data;
  }
  return [];
};

export const useAssignments = (): UseAssignmentsResult => {
  const [assignments, setAssignments] = useState<Assignment[]>([]);
  const [submissions, setSubmissions] = useState<Submission[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchAssignments = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      const token = typeof window !== 'undefined' ? localStorage.getItem('token') : null;
      const headers: HeadersInit = {
        'Content-Type': 'application/json',
      };

      if (token) {
        headers.Authorization = `Bearer ${token}`;
      }

      const [assignmentsResponse, submissionsResponse] = await Promise.all([
        fetch('/api/assignments', { headers }),
        fetch('/api/submissions', { headers }),
      ]);

      if (!assignmentsResponse.ok) {
        throw new Error(`Failed to load assignments: ${assignmentsResponse.statusText}`);
      }

      if (!submissionsResponse.ok) {
        throw new Error(`Failed to load submissions: ${submissionsResponse.statusText}`);
      }

      const assignmentsData = await assignmentsResponse.json();
      const submissionsData = await submissionsResponse.json();

      setAssignments(parseAssignments(assignmentsData));
      setSubmissions(parseSubmissions(submissionsData));
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Unable to load assignments';
      setError(message);
      console.error('Assignments fetch error:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  return {
    assignments,
    submissions,
    loading,
    error,
    fetchAssignments,
  };
};

export default useAssignments;
