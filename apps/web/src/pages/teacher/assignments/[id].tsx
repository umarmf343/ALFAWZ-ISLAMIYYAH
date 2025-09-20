/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect, useCallback } from 'react';
import Image from 'next/image';
import { motion, AnimatePresence } from 'framer-motion';
import { useRouter } from 'next/router';
import {
  ArrowLeft,
  Edit,
  Users,
  Calendar,
  Clock,
  AlertCircle,
  Play,
  Pause,
  Send,
  Search,
  FileText
} from 'lucide-react';
import Layout from '../../../components/Layout';
import { Assignment, Submission, Feedback, SubmissionStatus } from '../../../types/assignment';
import HotspotComponent from '../../../components/assignment/HotspotComponent';

interface SubmissionWithStudent extends Submission {
  student: {
    id: number;
    name: string;
    email: string;
    avatar_url?: string;
  };
  feedback?: Feedback[];
}

interface AssignmentWithSubmissions extends Assignment {
  submissions: SubmissionWithStudent[];
  submissions_count: number;
  pending_submissions: number;
  average_score: number | null;
}

/**
 * Teacher assignment detail page with submission review and feedback.
 * Allows teachers to view assignment details, review submissions, and provide feedback.
 */
const TeacherAssignmentDetailPage: React.FC = () => {
  const router = useRouter();
  const { id } = router.query;
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [assignment, setAssignment] = useState<AssignmentWithSubmissions | null>(null);
  const [selectedSubmission, setSelectedSubmission] = useState<SubmissionWithStudent | null>(null);
  const [feedbackText, setFeedbackText] = useState('');
  const [feedbackScore, setFeedbackScore] = useState<number>(0);
  const [submittingFeedback, setSubmittingFeedback] = useState(false);
  const [statusFilter, setStatusFilter] = useState<SubmissionStatus | 'all'>('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [currentAudio, setCurrentAudio] = useState<HTMLAudioElement | null>(null);
  const [playingAudio, setPlayingAudio] = useState<string | null>(null);

  /**
   * Fetch assignment details with submissions.
   */
  const fetchAssignment = useCallback(async () => {
    if (!id) return;
    try {
      setLoading(true);
      setError(null);
      
      const token = localStorage.getItem('token');
      if (!token) {
        throw new Error('Authentication token not found');
      }
      
      const response = await fetch(`/api/assignments/${id}?include=submissions,hotspots`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });
      
      if (!response.ok) {
        throw new Error('Failed to fetch assignment');
      }
      
      const data = await response.json();
      setAssignment(data.data);
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch assignment');
    } finally {
      setLoading(false);
    }
  }, [id]);

  /**
   * Submit feedback for a submission.
   * @param submissionId Submission ID
   */
  const submitFeedback = async (submissionId: number) => {
    try {
      setSubmittingFeedback(true);
      
      const token = localStorage.getItem('token');
      if (!token) {
        throw new Error('Authentication token not found');
      }
      
      const response = await fetch(`/api/submissions/${submissionId}/feedback`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          note: feedbackText,
          score: feedbackScore
        })
      });
      
      if (!response.ok) {
        throw new Error('Failed to submit feedback');
      }
      
      // Refresh assignment data
      await fetchAssignment();
      
      // Reset form
      setFeedbackText('');
      setFeedbackScore(0);
      setSelectedSubmission(null);
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to submit feedback');
    } finally {
      setSubmittingFeedback(false);
    }
  };

  /**
   * Play audio file.
   * @param audioUrl Audio file URL
   * @param audioId Unique identifier for the audio
   */
  const playAudio = (audioUrl: string, audioId: string) => {
    // Stop current audio if playing
    if (currentAudio) {
      currentAudio.pause();
      currentAudio.currentTime = 0;
    }
    
    const audio = new Audio(audioUrl);
    audio.addEventListener('ended', () => {
      setPlayingAudio(null);
      setCurrentAudio(null);
    });
    
    audio.play();
    setCurrentAudio(audio);
    setPlayingAudio(audioId);
  };

  /**
   * Stop currently playing audio.
   */
  const stopAudio = () => {
    if (currentAudio) {
      currentAudio.pause();
      currentAudio.currentTime = 0;
      setCurrentAudio(null);
      setPlayingAudio(null);
    }
  };

  /**
   * Filter submissions based on status and search term.
   */
  const getFilteredSubmissions = () => {
    if (!assignment) return [];
    
    let filtered = assignment.submissions;
    
    // Filter by status
    if (statusFilter !== 'all') {
      filtered = filtered.filter(sub => sub.status === statusFilter);
    }
    
    // Filter by search term
    if (searchTerm.trim()) {
      const term = searchTerm.toLowerCase();
      filtered = filtered.filter(sub => 
        sub.student.name.toLowerCase().includes(term) ||
        sub.student.email.toLowerCase().includes(term)
      );
    }
    
    return filtered;
  };

  /**
   * Get score color based on value.
   * @param score Score value (0-100)
   */
  const getScoreColor = (score: number) => {
    if (score >= 90) return 'text-green-600';
    if (score >= 80) return 'text-blue-600';
    if (score >= 70) return 'text-yellow-600';
    return 'text-red-600';
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
    if (id) {
      fetchAssignment();
    }
  }, [id, fetchAssignment]);

  useEffect(() => {
    // Cleanup audio on unmount
    return () => {
      if (currentAudio) {
        currentAudio.pause();
      }
    };
  }, [currentAudio]);

  if (loading) {
    return (
      <Layout>
        <div className="min-h-screen bg-gradient-to-br from-milk-50 to-gold-50 flex items-center justify-center">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-maroon-600 mx-auto mb-4"></div>
            <p className="text-gray-600">Loading assignment...</p>
          </div>
        </div>
      </Layout>
    );
  }

  if (error || !assignment) {
    return (
      <Layout>
        <div className="min-h-screen bg-gradient-to-br from-milk-50 to-gold-50 flex items-center justify-center">
          <div className="text-center">
            <AlertCircle className="h-16 w-16 text-red-500 mx-auto mb-4" />
            <h2 className="text-2xl font-bold text-gray-800 mb-2">Assignment Not Found</h2>
            <p className="text-gray-600 mb-6">{error || 'The assignment you are looking for does not exist.'}</p>
            <button
              onClick={() => router.push('/teacher/assignments')}
              className="px-6 py-3 bg-maroon-600 text-white rounded-lg hover:bg-maroon-700"
            >
              Back to Assignments
            </button>
          </div>
        </div>
      </Layout>
    );
  }

  const filteredSubmissions = getFilteredSubmissions();

  return (
    <Layout>
      <div className="min-h-screen bg-gradient-to-br from-milk-50 to-gold-50">
        {/* Header */}
        <div className="bg-white shadow-sm border-b">
          <div className="max-w-7xl mx-auto px-6 py-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-4">
                <button
                  onClick={() => router.push('/teacher/assignments')}
                  className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  <ArrowLeft className="h-5 w-5 text-gray-600" />
                </button>
                <div>
                  <h1 className="text-2xl font-bold text-maroon-800">
                    {assignment.title}
                  </h1>
                  <div className="flex items-center space-x-4 text-sm text-gray-600 mt-1">
                    {assignment.class && (
                      <div className="flex items-center space-x-1">
                        <Users className="h-4 w-4" />
                        <span>{assignment.class.name}</span>
                      </div>
                    )}
                    {assignment.due_at && (
                      <div className="flex items-center space-x-1">
                        <Calendar className="h-4 w-4" />
                        <span>Due: {formatDate(assignment.due_at)}</span>
                      </div>
                    )}
                    <div className="flex items-center space-x-1">
                      <Clock className="h-4 w-4" />
                      <span>Created: {formatDate(assignment.created_at)}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="flex items-center space-x-3">
                <button
                  onClick={() => router.push(`/teacher/assignments/${assignment.id}/edit`)}
                  className="flex items-center space-x-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                >
                  <Edit className="h-4 w-4" />
                  <span>Edit</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-6 py-8">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Assignment Preview */}
            <div className="lg:col-span-2 space-y-6">
              {/* Assignment Image with Hotspots */}
              <div className="bg-white rounded-lg shadow-sm overflow-hidden">
                <div className="p-4 border-b border-gray-200">
                  <h3 className="text-lg font-semibold text-maroon-800">
                    Assignment Preview
                  </h3>
                </div>
                
                <div className="relative">
                  {assignment.image_url ? (
                    <div className="relative">
                      <Image
                        src={assignment.image_url}
                        alt={assignment.title}
                        width={1200}
                        height={800}
                        className="w-full h-auto"
                        unoptimized
                      />
                      
                      {/* Render Hotspots */}
                      {assignment.hotspots?.map((hotspot) => (
                        <HotspotComponent
                          key={hotspot.id}
                          hotspot={hotspot}
                          onInteraction={() => {}}
                          disabled={false}
                        />
                      ))}
                    </div>
                  ) : (
                    <div className="flex items-center justify-center h-64 bg-gray-50">
                      <div className="text-center text-gray-500">
                        <FileText className="h-12 w-12 mx-auto mb-4" />
                        <p>No image available</p>
                      </div>
                    </div>
                  )}
                </div>
              </div>

              {/* Assignment Description */}
              {assignment.description && (
                <div className="bg-white rounded-lg shadow-sm p-6">
                  <h3 className="text-lg font-semibold text-maroon-800 mb-4">
                    Description
                  </h3>
                  <p className="text-gray-700 leading-relaxed">
                    {assignment.description}
                  </p>
                </div>
              )}

              {/* Statistics */}
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h3 className="text-lg font-semibold text-maroon-800 mb-4">
                  Statistics
                </h3>
                
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div className="text-center">
                    <div className="text-2xl font-bold text-maroon-600">
                      {assignment.submissions_count}
                    </div>
                    <div className="text-sm text-gray-600">Total Submissions</div>
                  </div>
                  
                  <div className="text-center">
                    <div className="text-2xl font-bold text-orange-600">
                      {assignment.pending_submissions}
                    </div>
                    <div className="text-sm text-gray-600">Pending Review</div>
                  </div>
                  
                  <div className="text-center">
                    <div className="text-2xl font-bold text-green-600">
                      {assignment.submissions_count - assignment.pending_submissions}
                    </div>
                    <div className="text-sm text-gray-600">Graded</div>
                  </div>
                  
                  <div className="text-center">
                    <div className="text-2xl font-bold text-blue-600">
                      {assignment.average_score ? `${assignment.average_score.toFixed(1)}%` : 'N/A'}
                    </div>
                    <div className="text-sm text-gray-600">Average Score</div>
                  </div>
                </div>
              </div>
            </div>

            {/* Submissions Panel */}
            <div className="lg:col-span-1">
              <div className="bg-white rounded-lg shadow-sm">
                <div className="p-4 border-b border-gray-200">
                  <h3 className="text-lg font-semibold text-maroon-800 mb-4">
                    Submissions ({assignment.submissions_count})
                  </h3>
                  
                  {/* Filters */}
                  <div className="space-y-3">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                      <input
                        type="text"
                        placeholder="Search students..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500 focus:border-transparent"
                      />
                    </div>
                    
                    <select
                      value={statusFilter}
                      onChange={(e) => setStatusFilter(e.target.value as SubmissionStatus | 'all')}
                      className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500 focus:border-transparent"
                    >
                      <option value="all">All Status</option>
                      <option value="pending">Pending</option>
                      <option value="graded">Graded</option>
                    </select>
                  </div>
                </div>
                
                {/* Submissions List */}
                <div className="max-h-96 overflow-y-auto">
                  {filteredSubmissions.length === 0 ? (
                    <div className="p-6 text-center text-gray-500">
                      <FileText className="h-8 w-8 mx-auto mb-2" />
                      <p className="text-sm">
                        {assignment.submissions_count === 0 
                          ? 'No submissions yet'
                          : 'No submissions match your filters'
                        }
                      </p>
                    </div>
                  ) : (
                    <div className="space-y-1">
                      {filteredSubmissions.map((submission) => (
                        <div
                          key={submission.id}
                          className={`p-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors ${
                            selectedSubmission?.id === submission.id ? 'bg-maroon-50 border-maroon-200' : ''
                          }`}
                          onClick={() => setSelectedSubmission(submission)}
                        >
                          <div className="flex items-center justify-between mb-2">
                            <div className="font-medium text-sm text-gray-800">
                              {submission.student.name}
                            </div>
                            <div className="flex items-center space-x-2">
                              {submission.status === 'pending' ? (
                                <span className="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full">
                                  Pending
                                </span>
                              ) : (
                                <span className={`px-2 py-1 bg-green-100 text-xs rounded-full ${
                                  getScoreColor(submission.score || 0)
                                }`}>
                                  {submission.score}%
                                </span>
                              )}
                            </div>
                          </div>
                          
                          <div className="text-xs text-gray-500">
                            {formatDate(submission.created_at)}
                          </div>
                          
                          {submission.audio_url && (
                            <div className="mt-2">
                              <button
                                onClick={(e) => {
                                  e.stopPropagation();
                                  if (playingAudio === `submission-${submission.id}`) {
                                    stopAudio();
                                  } else {
                                    playAudio(submission.audio_url!, `submission-${submission.id}`);
                                  }
                                }}
                                className="flex items-center space-x-1 text-xs text-maroon-600 hover:text-maroon-700"
                              >
                                {playingAudio === `submission-${submission.id}` ? (
                                  <Pause className="h-3 w-3" />
                                ) : (
                                  <Play className="h-3 w-3" />
                                )}
                                <span>Audio</span>
                              </button>
                            </div>
                          )}
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Feedback Modal */}
        <AnimatePresence>
          {selectedSubmission && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
              onClick={() => setSelectedSubmission(null)}
            >
              <motion.div
                initial={{ scale: 0.95, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                exit={{ scale: 0.95, opacity: 0 }}
                className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                onClick={(e) => e.stopPropagation()}
              >
                <div className="p-6">
                  <div className="flex items-center justify-between mb-6">
                    <h3 className="text-xl font-semibold text-maroon-800">
                      Review Submission
                    </h3>
                    <button
                      onClick={() => setSelectedSubmission(null)}
                      className="text-gray-400 hover:text-gray-600"
                    >
                      ×
                    </button>
                  </div>
                  
                  {/* Student Info */}
                  <div className="bg-gray-50 rounded-lg p-4 mb-6">
                    <div className="flex items-center space-x-3">
                      <div className="w-10 h-10 bg-maroon-600 rounded-full flex items-center justify-center text-white font-semibold">
                        {selectedSubmission.student.name.charAt(0).toUpperCase()}
                      </div>
                      <div>
                        <div className="font-medium text-gray-800">
                          {selectedSubmission.student.name}
                        </div>
                        <div className="text-sm text-gray-600">
                          {selectedSubmission.student.email}
                        </div>
                      </div>
                    </div>
                    
                    <div className="mt-3 text-sm text-gray-600">
                      Submitted: {formatDate(selectedSubmission.created_at)}
                    </div>
                  </div>
                  
                  {/* Audio Submission */}
                  {selectedSubmission.audio_url && (
                    <div className="mb-6">
                      <h4 className="font-medium text-gray-800 mb-3">Audio Submission</h4>
                      <div className="bg-gray-50 rounded-lg p-4">
                        <audio controls className="w-full">
                          <source src={selectedSubmission.audio_url} />
                          Your browser does not support the audio element.
                        </audio>
                      </div>
                    </div>
                  )}
                  
                  {/* Existing Feedback */}
                  {selectedSubmission.feedback && selectedSubmission.feedback.length > 0 && (
                    <div className="mb-6">
                      <h4 className="font-medium text-gray-800 mb-3">Previous Feedback</h4>
                      <div className="space-y-3">
                        {selectedSubmission.feedback.map((feedback, index) => (
                          <div key={index} className="bg-blue-50 rounded-lg p-4">
                            <div className="flex items-center justify-between mb-2">
                              <div className="font-medium text-blue-800">
                                Score: {selectedSubmission.score}%
                              </div>
                              <div className="text-sm text-blue-600">
                                {formatDate(feedback.created_at)}
                              </div>
                            </div>
                            {feedback.note && (
                              <p className="text-blue-700 text-sm">{feedback.note}</p>
                            )}
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                  
                  {/* Feedback Form */}
                  {selectedSubmission.status === 'pending' && (
                    <div className="space-y-4">
                      <h4 className="font-medium text-gray-800">Provide Feedback</h4>
                      
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Score (0-100)
                        </label>
                        <input
                          type="number"
                          min="0"
                          max="100"
                          value={feedbackScore}
                          onChange={(e) => setFeedbackScore(Number(e.target.value))}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500 focus:border-transparent"
                        />
                      </div>
                      
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Feedback Notes
                        </label>
                        <textarea
                          value={feedbackText}
                          onChange={(e) => setFeedbackText(e.target.value)}
                          rows={4}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500 focus:border-transparent"
                          placeholder="Provide detailed feedback on the student's performance..."
                        />
                      </div>
                      
                      <div className="flex justify-end space-x-3">
                        <button
                          onClick={() => setSelectedSubmission(null)}
                          className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                        >
                          Cancel
                        </button>
                        <button
                          onClick={() => submitFeedback(selectedSubmission.id)}
                          disabled={submittingFeedback || feedbackScore < 0 || feedbackScore > 100}
                          className="flex items-center space-x-2 px-6 py-2 bg-maroon-600 text-white rounded-lg hover:bg-maroon-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          <Send className="h-4 w-4" />
                          <span>{submittingFeedback ? 'Submitting...' : 'Submit Feedback'}</span>
                        </button>
                      </div>
                    </div>
                  )}
                </div>
              </motion.div>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </Layout>
  );
};

export default TeacherAssignmentDetailPage;