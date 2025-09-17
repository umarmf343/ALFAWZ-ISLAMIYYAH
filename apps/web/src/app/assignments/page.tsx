/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useAuth } from '@/contexts/AuthContext';
import { api } from '@/lib/api';
import { Assignment } from '@/types';

/**
 * Assignments page component that displays different views based on user role:
 * - Teachers: Can create, edit, and manage assignments
 * - Students: Can view their assigned work and submit responses
 */
export default function AssignmentsPage() {
  const { user, isAuthenticated } = useAuth();
  const [assignments, setAssignments] = useState<Assignment[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [classes, setClasses] = useState<any[]>([]);
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    class_id: '',
    due_at: ''
  });
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  /**
   * Fetch assignments based on user role
   */
  const fetchAssignments = async () => {
    try {
      setLoading(true);
      const response = await api.get('/assignments');
      setAssignments(response.data || []);
    } catch (err: any) {
      setError(err.message || 'Failed to fetch assignments');
    } finally {
      setLoading(false);
    }
  };

  /**
   * Fetch classes for assignment creation (teachers only)
   */
  const fetchClasses = async () => {
    try {
      const response = await api.get('/classes');
      setClasses(response.data || []);
    } catch (err: any) {
      console.error('Failed to fetch classes:', err);
    }
  };

  /**
   * Create a new assignment (teachers only)
   */
  const handleCreateAssignment = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setError('');
      setSuccess('');
      
      const payload = {
        ...formData,
        class_id: formData.class_id ? parseInt(formData.class_id) : null,
        due_at: formData.due_at || null
      };
      
      const response = await api.post('/assignments', payload);
      setAssignments([...assignments, response.data]);
      setFormData({ title: '', description: '', class_id: '', due_at: '' });
      setShowCreateForm(false);
      setSuccess('Assignment created successfully!');
    } catch (err: any) {
      setError(err.message || 'Failed to create assignment');
    }
  };

  /**
   * Publish an assignment (teachers only)
   */
  const handlePublishAssignment = async (assignmentId: number) => {
    try {
      await api.post(`/assignments/${assignmentId}/publish`);
      setAssignments(assignments.map(a => 
        a.id === assignmentId ? { ...a, status: 'published' } : a
      ));
      setSuccess('Assignment published successfully!');
    } catch (err: any) {
      setError(err.message || 'Failed to publish assignment');
    }
  };

  /**
   * Delete an assignment (teachers only)
   */
  const handleDeleteAssignment = async (assignmentId: number) => {
    if (!confirm('Are you sure you want to delete this assignment?')) return;
    
    try {
      await api.delete(`/assignments/${assignmentId}`);
      setAssignments(assignments.filter(a => a.id !== assignmentId));
      setSuccess('Assignment deleted successfully!');
    } catch (err: any) {
      setError(err.message || 'Failed to delete assignment');
    }
  };

  /**
   * Format due date for display
   */
  const formatDueDate = (dueAt: string | null) => {
    if (!dueAt) return 'No due date';
    return new Date(dueAt).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  /**
   * Get status badge color
   */
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'published': return 'bg-green-100 text-green-800';
      case 'draft': return 'bg-yellow-100 text-yellow-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  useEffect(() => {
    if (isAuthenticated) {
      fetchAssignments();
      if (user?.role === 'teacher') {
        fetchClasses();
      }
    }
  }, [isAuthenticated, user]);

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Access Denied</h1>
          <p className="text-gray-600">Please log in to view assignments.</p>
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
      </div>
    );
  }

  const isTeacher = user?.role === 'teacher';

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">
                {isTeacher ? 'My Assignments' : 'My Assignments'}
              </h1>
              <p className="text-gray-600 mt-2">
                {isTeacher 
                  ? 'Create and manage assignments for your students'
                  : 'View your assignments and submit your work'
                }
              </p>
            </div>
            
            {isTeacher && (
              <button
                onClick={() => setShowCreateForm(true)}
                className="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors"
              >
                Create Assignment
              </button>
            )}
          </div>
        </div>

        {/* Success/Error Messages */}
        {success && (
          <div className="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {success}
          </div>
        )}
        {error && (
          <div className="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {error}
          </div>
        )}

        {/* Create Assignment Modal */}
        {showCreateForm && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-8 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
              <h2 className="text-2xl font-bold text-gray-900 mb-6">Create New Assignment</h2>
              
              <form onSubmit={handleCreateAssignment}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">
                    Assignment Title
                  </label>
                  <input
                    type="text"
                    value={formData.title}
                    onChange={(e) => setFormData({...formData, title: e.target.value})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500"
                    required
                  />
                </div>
                
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">
                    Description
                  </label>
                  <textarea
                    value={formData.description}
                    onChange={(e) => setFormData({...formData, description: e.target.value})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500 h-24"
                    rows={3}
                  />
                </div>
                
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">
                    Class (Optional)
                  </label>
                  <select
                    value={formData.class_id}
                    onChange={(e) => setFormData({...formData, class_id: e.target.value})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500"
                  >
                    <option value="">Select a class (or leave empty for individual assignment)</option>
                    {classes.map((cls) => (
                      <option key={cls.id} value={cls.id}>
                        {cls.title} (Level {cls.level})
                      </option>
                    ))}
                  </select>
                </div>
                
                <div className="mb-6">
                  <label className="block text-gray-700 text-sm font-bold mb-2">
                    Due Date (Optional)
                  </label>
                  <input
                    type="datetime-local"
                    value={formData.due_at}
                    onChange={(e) => setFormData({...formData, due_at: e.target.value})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500"
                  />
                </div>
                
                <div className="flex gap-4">
                  <button
                    type="submit"
                    className="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors"
                  >
                    Create Assignment
                  </button>
                  <button
                    type="button"
                    onClick={() => setShowCreateForm(false)}
                    className="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg font-semibold hover:bg-gray-400 transition-colors"
                  >
                    Cancel
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Assignments Grid */}
        {assignments.length === 0 ? (
          <div className="text-center py-12">
            <div className="text-6xl mb-4">📝</div>
            <h3 className="text-xl font-semibold text-gray-900 mb-2">
              {isTeacher ? 'No Assignments Created Yet' : 'No Assignments Available'}
            </h3>
            <p className="text-gray-600">
              {isTeacher 
                ? 'Create your first assignment to start giving students work'
                : 'Your teacher will assign work that will appear here'
              }
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {assignments.map((assignment) => (
              <div key={assignment.id} className="bg-white rounded-lg shadow-lg overflow-hidden">
                <div className="p-6">
                  <div className="flex justify-between items-start mb-4">
                    <div className="flex-1">
                      <h3 className="text-xl font-semibold text-gray-900 mb-2">
                        {assignment.title}
                      </h3>
                      <div className="flex gap-2 mb-2">
                        <span className={`inline-block text-xs px-2 py-1 rounded-full ${getStatusColor(assignment.status)}`}>
                          {assignment.status}
                        </span>
                        {assignment.class_title && (
                          <span className="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                            {assignment.class_title}
                          </span>
                        )}
                      </div>
                    </div>
                    
                    {isTeacher && (
                      <div className="flex gap-1">
                        {assignment.status === 'draft' && (
                          <button
                            onClick={() => handlePublishAssignment(assignment.id)}
                            className="text-green-600 hover:text-green-800 transition-colors p-1"
                            title="Publish assignment"
                          >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                          </button>
                        )}
                        <button
                          onClick={() => handleDeleteAssignment(assignment.id)}
                          className="text-red-600 hover:text-red-800 transition-colors p-1"
                          title="Delete assignment"
                        >
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
                      </div>
                    )}
                  </div>
                  
                  <p className="text-gray-600 mb-4 line-clamp-3">
                    {assignment.description || 'No description provided'}
                  </p>
                  
                  <div className="text-sm text-gray-500 mb-4">
                    <div className="flex justify-between items-center">
                      <span>📅 Due: {formatDueDate(assignment.due_date || null)}</span>
                      {!isTeacher && (
                        <span className="text-blue-600 font-medium">
                          {assignment.submission_status || 'Not submitted'}
                        </span>
                      )}
                    </div>
                    {isTeacher && (
                      <div className="mt-2">
                        <span>📊 {assignment.submissions_count || 0} submissions</span>
                      </div>
                    )}
                  </div>
                  
                  <div className="flex gap-2">
                    <Link
                      href={`/assignments/${assignment.id}`}
                      className="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors text-center"
                    >
                      {isTeacher ? 'Manage' : 'View & Submit'}
                    </Link>
                    {isTeacher && (
                      <Link
                        href={`/assignments/${assignment.id}/edit`}
                        className="bg-blue-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                      >
                        Edit
                      </Link>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}