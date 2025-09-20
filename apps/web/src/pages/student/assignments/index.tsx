/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Clock, BookOpen, CheckCircle, AlertCircle, Play, Pause } from 'lucide-react';
import { useRouter } from 'next/router';
import Image from 'next/image';
import Layout from '../../../components/Layout';
import { useAuth } from '../../../hooks/useAuth';
import { useAssignments } from '../../../hooks/useAssignments';
import { Assignment, Submission } from '../../../types/assignment';

/**
 * Student Assignment Dashboard - displays all assignments with status and interactions.
 * Includes task management, audio recording capabilities, and hotspot interactions.
 */
const StudentAssignmentDashboard: React.FC = () => {
  const router = useRouter();
  const { user } = useAuth();
  const { assignments, submissions, loading, error, fetchAssignments } = useAssignments();
  const [filter, setFilter] = useState<'all' | 'pending' | 'completed' | 'overdue'>('all');
  const [searchTerm, setSearchTerm] = useState('');

  useEffect(() => {
    if (user) {
      void fetchAssignments();
    }
  }, [user, fetchAssignments]);

  /**
   * Filter assignments based on current filter and search term.
   * @param assignments Array of assignments to filter
   * @returns Filtered assignments array
   */
  const filteredAssignments = assignments.filter((assignment: Assignment) => {
    const matchesSearch = assignment.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         assignment.description?.toLowerCase().includes(searchTerm.toLowerCase());
    
    if (!matchesSearch) return false;

    const submission = submissions.find((s: Submission) => s.assignment_id === assignment.id);
    const now = new Date();
    const dueDate = assignment.due_at ? new Date(assignment.due_at) : null;
    const isOverdue = dueDate && now > dueDate && (!submission || submission.status === 'pending');
    const isCompleted = submission?.status === 'graded';
    const isPending = !submission || submission.status === 'pending';

    switch (filter) {
      case 'pending': return isPending && !isOverdue;
      case 'completed': return isCompleted;
      case 'overdue': return isOverdue;
      default: return true;
    }
  });

  /**
   * Get assignment status with appropriate styling.
   * @param assignment Assignment object
   * @returns Status object with label, color, and icon
   */
  const getAssignmentStatus = (assignment: Assignment) => {
    const submission = submissions.find((s: Submission) => s.assignment_id === assignment.id);
    const now = new Date();
    const dueDate = assignment.due_at ? new Date(assignment.due_at) : null;
    
    if (submission?.status === 'graded') {
      return {
        label: 'Completed',
        color: 'text-green-600 bg-green-50',
        icon: CheckCircle
      };
    }
    
    if (dueDate && now > dueDate) {
      return {
        label: 'Overdue',
        color: 'text-red-600 bg-red-50',
        icon: AlertCircle
      };
    }
    
    return {
      label: 'Pending',
      color: 'text-yellow-600 bg-yellow-50',
      icon: Clock
    };
  };

  /**
   * Navigate to assignment detail page.
   * @param assignmentId Assignment ID to navigate to
   */
  const handleAssignmentClick = (assignmentId: number) => {
    router.push(`/student/assignments/${assignmentId}`);
  };

  if (loading) {
    return (
      <Layout>
        <div className="flex items-center justify-center min-h-screen">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-maroon-600"></div>
        </div>
      </Layout>
    );
  }

  if (error) {
    return (
      <Layout>
        <div className="flex items-center justify-center min-h-screen">
          <div className="text-red-600 text-center">
            <AlertCircle className="h-12 w-12 mx-auto mb-4" />
            <p>Error loading assignments: {error}</p>
          </div>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="min-h-screen bg-gradient-to-br from-milk-50 to-gold-50 p-6">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="max-w-7xl mx-auto"
        >
          {/* Header */}
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-maroon-800 mb-2">
              My Assignments
            </h1>
            <p className="text-maroon-600">
              Complete your Qur&apos;an recitation assignments and track your progress
            </p>
          </div>

          {/* Filters and Search */}
          <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
              <div className="flex gap-2">
                {(['all', 'pending', 'completed', 'overdue'] as const).map((filterOption) => (
                  <button
                    key={filterOption}
                    onClick={() => setFilter(filterOption)}
                    className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                      filter === filterOption
                        ? 'bg-maroon-600 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    }`}
                  >
                    {filterOption.charAt(0).toUpperCase() + filterOption.slice(1)}
                  </button>
                ))}
              </div>
              <input
                type="text"
                placeholder="Search assignments..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500 focus:border-transparent"
              />
            </div>
          </div>

          {/* Assignment Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredAssignments.map((assignment: Assignment) => {
              const status = getAssignmentStatus(assignment);
              const StatusIcon = status.icon;
              const submission = submissions.find((s: Submission) => s.assignment_id === assignment.id);

              return (
                <motion.div
                  key={assignment.id}
                  initial={{ opacity: 0, scale: 0.95 }}
                  animate={{ opacity: 1, scale: 1 }}
                  whileHover={{ scale: 1.02 }}
                  className="bg-white rounded-lg shadow-sm hover:shadow-md transition-all cursor-pointer"
                  onClick={() => handleAssignmentClick(assignment.id)}
                >
                  {/* Assignment Image */}
                  {assignment.image_s3_url && (
                    <div className="relative h-48 rounded-t-lg overflow-hidden">
                      <Image
                        src={assignment.image_s3_url}
                        alt={assignment.title}
                        fill
                        className="object-cover"
                        sizes="(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw"
                      />
                      <div className="absolute top-4 right-4">
                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${status.color}`}>
                          <StatusIcon className="h-3 w-3 mr-1" />
                          {status.label}
                        </span>
                      </div>
                    </div>
                  )}

                  <div className="p-6">
                    <h3 className="text-lg font-semibold text-maroon-800 mb-2">
                      {assignment.title}
                    </h3>
                    
                    {assignment.description && (
                      <p className="text-gray-600 text-sm mb-4 line-clamp-2">
                        {assignment.description}
                      </p>
                    )}

                    {/* Assignment Details */}
                    <div className="space-y-2 text-sm">
                      {assignment.due_at && (
                        <div className="flex items-center text-gray-500">
                          <Clock className="h-4 w-4 mr-2" />
                          Due: {new Date(assignment.due_at).toLocaleDateString()}
                        </div>
                      )}
                      
                      <div className="flex items-center text-gray-500">
                        <BookOpen className="h-4 w-4 mr-2" />
                        {assignment.hotspots?.length || 0} Hotspots
                      </div>

                      {submission && (
                        <div className="flex items-center text-gray-500">
                          {submission.audio_s3_url ? (
                            <Play className="h-4 w-4 mr-2" />
                          ) : (
                            <Pause className="h-4 w-4 mr-2" />
                          )}
                          {submission.audio_s3_url ? 'Recording submitted' : 'No recording yet'}
                        </div>
                      )}
                    </div>

                    {/* Progress Bar */}
                    {submission && (
                      <div className="mt-4">
                        <div className="flex justify-between text-xs text-gray-500 mb-1">
                          <span>Progress</span>
                          <span>{submission.status === 'graded' ? '100%' : '50%'}</span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-2">
                          <div
                            className="bg-maroon-600 h-2 rounded-full transition-all"
                            style={{
                              width: submission.status === 'graded' ? '100%' : '50%'
                            }}
                          ></div>
                        </div>
                      </div>
                    )}
                  </div>
                </motion.div>
              );
            })}
          </div>

          {/* Empty State */}
          {filteredAssignments.length === 0 && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              className="text-center py-12"
            >
              <BookOpen className="h-16 w-16 text-gray-400 mx-auto mb-4" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">
                No assignments found
              </h3>
              <p className="text-gray-500">
                {searchTerm || filter !== 'all'
                  ? 'Try adjusting your search or filter criteria'
                  : 'Your teacher hasn\'t assigned any tasks yet'}
              </p>
            </motion.div>
          )}
        </motion.div>
      </div>
    </Layout>
  );
};

export default StudentAssignmentDashboard;