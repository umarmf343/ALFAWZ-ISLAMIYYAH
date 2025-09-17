/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useRouter } from 'next/router';
import {
  Plus,
  Search,
  Filter,
  MoreVertical,
  Edit,
  Trash2,
  Eye,
  Users,
  Calendar,
  Clock,
  CheckCircle,
  AlertCircle,
  FileText,
  Image as ImageIcon,
  Volume2
} from 'lucide-react';
import Layout from '../../../components/Layout';
import { useAuth } from '../../../hooks/useAuth';
import { Assignment, AssignmentStatus, AssignmentFilters } from '../../../types/assignment';

interface AssignmentWithStats extends Assignment {
  submissions_count: number;
  pending_submissions: number;
  average_score: number | null;
}

/**
 * Teacher assignment management dashboard.
 * Displays all assignments created by the teacher with management options.
 */
const TeacherAssignmentsPage: React.FC = () => {
  const router = useRouter();
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [assignments, setAssignments] = useState<AssignmentWithStats[]>([]);
  const [filteredAssignments, setFilteredAssignments] = useState<AssignmentWithStats[]>([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<AssignmentStatus | 'all'>('all');
  const [showFilters, setShowFilters] = useState(false);
  const [selectedAssignment, setSelectedAssignment] = useState<string | null>(null);

  /**
   * Fetch assignments from API.
   */
  const fetchAssignments = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const token = localStorage.getItem('token');
      if (!token) {
        throw new Error('Authentication token not found');
      }
      
      const response = await fetch('/api/assignments?teacher=true', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });
      
      if (!response.ok) {
        throw new Error('Failed to fetch assignments');
      }
      
      const data = await response.json();
      setAssignments(data.data || []);
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch assignments');
    } finally {
      setLoading(false);
    }
  };

  /**
   * Filter assignments based on search term and status.
   */
  const filterAssignments = () => {
    let filtered = assignments;
    
    // Filter by search term
    if (searchTerm.trim()) {
      const term = searchTerm.toLowerCase();
      filtered = filtered.filter(assignment => 
        assignment.title.toLowerCase().includes(term) ||
        assignment.description?.toLowerCase().includes(term) ||
        assignment.class?.name?.toLowerCase().includes(term)
      );
    }
    
    // Filter by status
    if (statusFilter !== 'all') {
      filtered = filtered.filter(assignment => assignment.status === statusFilter);
    }
    
    setFilteredAssignments(filtered);
  };

  /**
   * Delete assignment.
   * @param assignmentId Assignment ID to delete
   */
  const deleteAssignment = async (assignmentId: number) => {
    if (!confirm('Are you sure you want to delete this assignment? This action cannot be undone.')) {
      return;
    }
    
    try {
      const token = localStorage.getItem('token');
      if (!token) {
        throw new Error('Authentication token not found');
      }
      
      const response = await fetch(`/api/assignments/${assignmentId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      
      if (!response.ok) {
        throw new Error('Failed to delete assignment');
      }
      
      // Remove from local state
      setAssignments(prev => prev.filter(a => a.id !== assignmentId));
      setSelectedAssignment(null);
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to delete assignment');
    }
  };

  /**
   * Toggle assignment status between draft and published.
   * @param assignmentId Assignment ID
   * @param currentStatus Current status
   */
  const toggleAssignmentStatus = async (assignmentId: number, currentStatus: AssignmentStatus) => {
    try {
      const token = localStorage.getItem('token');
      if (!token) {
        throw new Error('Authentication token not found');
      }
      
      const newStatus = currentStatus === 'draft' ? 'published' : 'draft';
      const endpoint = newStatus === 'published' 
        ? `/api/assignments/${assignmentId}/publish`
        : `/api/assignments/${assignmentId}`;
      
      const response = await fetch(endpoint, {
        method: newStatus === 'published' ? 'POST' : 'PATCH',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: newStatus === 'draft' ? JSON.stringify({ status: 'draft' }) : undefined
      });
      
      if (!response.ok) {
        throw new Error(`Failed to ${newStatus === 'published' ? 'publish' : 'unpublish'} assignment`);
      }
      
      // Update local state
      setAssignments(prev => prev.map(a => 
        a.id === assignmentId ? { ...a, status: newStatus } : a
      ));
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update assignment status');
    }
  };

  /**
   * Get status badge color and text.
   * @param status Assignment status
   */
  const getStatusBadge = (status: AssignmentStatus) => {
    switch (status) {
      case 'published':
        return { color: 'bg-green-100 text-green-800', text: 'Published' };
      case 'draft':
        return { color: 'bg-gray-100 text-gray-800', text: 'Draft' };
      default:
        return { color: 'bg-gray-100 text-gray-800', text: status };
    }
  };

  /**
   * Format date for display.
   * @param dateString ISO date string
   */
  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  useEffect(() => {
    fetchAssignments();
  }, []);

  useEffect(() => {
    filterAssignments();
  }, [assignments, searchTerm, statusFilter]);

  if (loading) {
    return (
      <Layout>
        <div className="min-h-screen bg-gradient-to-br from-milk-50 to-gold-50 flex items-center justify-center">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-maroon-600 mx-auto mb-4"></div>
            <p className="text-gray-600">Loading assignments...</p>
          </div>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="min-h-screen bg-gradient-to-br from-milk-50 to-gold-50">
        {/* Header */}
        <div className="bg-white shadow-sm border-b">
          <div className="max-w-7xl mx-auto px-6 py-6">
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-3xl font-bold text-maroon-800">
                  My Assignments
                </h1>
                <p className="text-gray-600 mt-1">
                  Manage and track your assignment progress
                </p>
              </div>
              
              <button
                onClick={() => router.push('/teacher/assignments/create')}
                className="flex items-center space-x-2 px-6 py-3 bg-maroon-600 text-white rounded-lg hover:bg-maroon-700 transition-colors"
              >
                <Plus className="h-5 w-5" />
                <span>Create Assignment</span>
              </button>
            </div>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-6 py-8">
          {/* Search and Filters */}
          <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div className="flex flex-col sm:flex-row gap-4">
              {/* Search */}
              <div className="flex-1 relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search assignments..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500 focus:border-transparent"
                />
              </div>
              
              {/* Status Filter */}
              <div className="flex items-center space-x-4">
                <select
                  value={statusFilter}
                  onChange={(e) => setStatusFilter(e.target.value as AssignmentStatus | 'all')}
                  className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500 focus:border-transparent"
                >
                  <option value="all">All Status</option>
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
                </select>
                
                <button
                  onClick={() => setShowFilters(!showFilters)}
                  className="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                >
                  <Filter className="h-4 w-4" />
                  <span>Filters</span>
                </button>
              </div>
            </div>
          </div>

          {/* Assignments Grid */}
          {error ? (
            <div className="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
              <AlertCircle className="h-12 w-12 text-red-500 mx-auto mb-4" />
              <h3 className="text-lg font-semibold text-red-800 mb-2">Error Loading Assignments</h3>
              <p className="text-red-600 mb-4">{error}</p>
              <button
                onClick={fetchAssignments}
                className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
              >
                Try Again
              </button>
            </div>
          ) : filteredAssignments.length === 0 ? (
            <div className="bg-white rounded-lg shadow-sm p-12 text-center">
              <FileText className="h-16 w-16 text-gray-400 mx-auto mb-4" />
              <h3 className="text-xl font-semibold text-gray-800 mb-2">
                {assignments.length === 0 ? 'No assignments yet' : 'No assignments match your filters'}
              </h3>
              <p className="text-gray-600 mb-6">
                {assignments.length === 0 
                  ? 'Create your first assignment to get started'
                  : 'Try adjusting your search or filter criteria'
                }
              </p>
              {assignments.length === 0 && (
                <button
                  onClick={() => router.push('/teacher/assignments/create')}
                  className="px-6 py-3 bg-maroon-600 text-white rounded-lg hover:bg-maroon-700"
                >
                  Create First Assignment
                </button>
              )}
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <AnimatePresence>
                {filteredAssignments.map((assignment) => {
                  const statusBadge = getStatusBadge(assignment.status);
                  
                  return (
                    <motion.div
                      key={assignment.id}
                      layout
                      initial={{ opacity: 0, y: 20 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, y: -20 }}
                      className="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden"
                    >
                      {/* Assignment Image */}
                      <div className="relative h-48 bg-gradient-to-br from-maroon-100 to-gold-100">
                        {assignment.image_url ? (
                          <img
                            src={assignment.image_url}
                            alt={assignment.title}
                            className="w-full h-full object-cover"
                          />
                        ) : (
                          <div className="flex items-center justify-center h-full">
                            <ImageIcon className="h-12 w-12 text-maroon-400" />
                          </div>
                        )}
                        
                        {/* Status Badge */}
                        <div className="absolute top-3 left-3">
                          <span className={`px-2 py-1 rounded-full text-xs font-medium ${statusBadge.color}`}>
                            {statusBadge.text}
                          </span>
                        </div>
                        
                        {/* Actions Menu */}
                        <div className="absolute top-3 right-3">
                          <div className="relative">
                            <button
                              onClick={() => setSelectedAssignment(
                                selectedAssignment === assignment.id.toString() ? null : assignment.id.toString()
                              )}
                              className="p-2 bg-white bg-opacity-90 rounded-lg hover:bg-opacity-100 transition-colors"
                            >
                              <MoreVertical className="h-4 w-4 text-gray-600" />
                            </button>
                            
                            <AnimatePresence>
                              {selectedAssignment === assignment.id.toString() && (
                                <motion.div
                                  initial={{ opacity: 0, scale: 0.95 }}
                                  animate={{ opacity: 1, scale: 1 }}
                                  exit={{ opacity: 0, scale: 0.95 }}
                                  className="absolute right-0 top-full mt-2 w-48 bg-white rounded-lg shadow-lg border z-10"
                                >
                                  <div className="py-1">
                                    <button
                                      onClick={() => {
                                        router.push(`/teacher/assignments/${assignment.id}`);
                                        setSelectedAssignment(null);
                                      }}
                                      className="w-full flex items-center space-x-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                      <Eye className="h-4 w-4" />
                                      <span>View Details</span>
                                    </button>
                                    
                                    <button
                                      onClick={() => {
                                        router.push(`/teacher/assignments/${assignment.id}/edit`);
                                        setSelectedAssignment(null);
                                      }}
                                      className="w-full flex items-center space-x-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                      <Edit className="h-4 w-4" />
                                      <span>Edit</span>
                                    </button>
                                    
                                    <button
                                      onClick={() => {
                                        toggleAssignmentStatus(assignment.id, assignment.status);
                                        setSelectedAssignment(null);
                                      }}
                                      className="w-full flex items-center space-x-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                      {assignment.status === 'draft' ? (
                                        <>
                                          <CheckCircle className="h-4 w-4" />
                                          <span>Publish</span>
                                        </>
                                      ) : (
                                        <>
                                          <Clock className="h-4 w-4" />
                                          <span>Unpublish</span>
                                        </>
                                      )}
                                    </button>
                                    
                                    <hr className="my-1" />
                                    
                                    <button
                                      onClick={() => {
                                        deleteAssignment(assignment.id);
                                        setSelectedAssignment(null);
                                      }}
                                      className="w-full flex items-center space-x-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                                    >
                                      <Trash2 className="h-4 w-4" />
                                      <span>Delete</span>
                                    </button>
                                  </div>
                                </motion.div>
                              )}
                            </AnimatePresence>
                          </div>
                        </div>
                      </div>
                      
                      {/* Assignment Info */}
                      <div className="p-6">
                        <h3 className="text-lg font-semibold text-maroon-800 mb-2 line-clamp-2">
                          {assignment.title}
                        </h3>
                        
                        {assignment.description && (
                          <p className="text-gray-600 text-sm mb-4 line-clamp-2">
                            {assignment.description}
                          </p>
                        )}
                        
                        {/* Stats */}
                        <div className="space-y-2 mb-4">
                          <div className="flex items-center justify-between text-sm">
                            <span className="text-gray-600">Submissions:</span>
                            <span className="font-medium">
                              {assignment.submissions_count || 0}
                            </span>
                          </div>
                          
                          {assignment.pending_submissions > 0 && (
                            <div className="flex items-center justify-between text-sm">
                              <span className="text-gray-600">Pending:</span>
                              <span className="font-medium text-orange-600">
                                {assignment.pending_submissions}
                              </span>
                            </div>
                          )}
                          
                          {assignment.average_score !== null && (
                            <div className="flex items-center justify-between text-sm">
                              <span className="text-gray-600">Avg Score:</span>
                              <span className="font-medium text-green-600">
                                {assignment.average_score.toFixed(1)}%
                              </span>
                            </div>
                          )}
                        </div>
                        
                        {/* Meta Info */}
                        <div className="space-y-2 text-xs text-gray-500">
                          {assignment.class && (
                            <div className="flex items-center space-x-1">
                              <Users className="h-3 w-3" />
                              <span>{assignment.class.name}</span>
                            </div>
                          )}
                          
                          {assignment.due_at && (
                            <div className="flex items-center space-x-1">
                              <Calendar className="h-3 w-3" />
                              <span>Due: {formatDate(assignment.due_at)}</span>
                            </div>
                          )}
                          
                          <div className="flex items-center space-x-1">
                            <Clock className="h-3 w-3" />
                            <span>Created: {formatDate(assignment.created_at)}</span>
                          </div>
                        </div>
                      </div>
                    </motion.div>
                  );
                })}
              </AnimatePresence>
            </div>
          )}
        </div>
      </div>
    </Layout>
  );
};

export default TeacherAssignmentsPage;