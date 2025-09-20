/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import type { IconType } from 'react-icons';
import Image from 'next/image';
import {
  getMemorizationStudents,
  getMemorizationAnalytics,
  getAudioReviews,
  reviewAudioSubmission
} from '@/lib/api';
import {
  FaUser,
  FaChartLine,
  FaBell,
  FaBookOpen,
  FaStar,
  FaTrophy,
  FaExclamationTriangle,
  FaCheckCircle,
  FaVolumeUp,
  FaPlay,
  FaPause
} from 'react-icons/fa';
import { CircularProgressbar, buildStyles } from 'react-circular-progressbar';
import 'react-circular-progressbar/dist/styles.css';

interface StudentMemorizationProgress {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  memorization_stats: {
    total_ayahs_memorized: number;
    current_streak: number;
    average_confidence: number;
    tajweed_score: number;
    last_review_date: string;
    due_reviews: number;
  };
  recent_plans: {
    id: number;
    surah_name: string;
    ayah_range: string;
    progress_percentage: number;
    status: 'active' | 'completed' | 'paused';
    created_at: string;
  }[];
  alerts: {
    type: 'warning' | 'success' | 'info' | 'error';
    message: string;
    timestamp: string;
  }[];
  performance_trend: 'up' | 'down' | 'stable';
}

interface MemorizationAnalytics {
  total_students: number;
  active_memorizers: number;
  average_progress: number;
  total_ayahs_memorized: number;
  average_tajweed_score: number;
  completion_rate: number;
  weekly_reviews: number;
  monthly_completions: number;
}

interface AudioReview {
  id: number;
  student_name: string;
  surah_name: string;
  ayah_range: string;
  audio_url: string;
  tajweed_analysis: {
    overall_score: number;
    pronunciation_score: number;
    fluency_score: number;
    mistakes: string[];
    suggestions: string[];
  };
  submitted_at: string;
  status: 'pending' | 'reviewed' | 'approved';
}

/**
 * Teacher memorization oversight component for tracking student progress and reviewing submissions
 */
type TabKey = 'overview' | 'students' | 'reviews';

const TAB_CONFIG: Array<{ key: TabKey; label: string; icon: IconType }> = [
  { key: 'overview', label: 'Overview', icon: FaChartLine },
  { key: 'students', label: 'Students', icon: FaUser },
  { key: 'reviews', label: 'Audio Reviews', icon: FaVolumeUp }
];

export default function MemorizationOversight() {
  const [students, setStudents] = useState<StudentMemorizationProgress[]>([]);
  const [analytics, setAnalytics] = useState<MemorizationAnalytics | null>(null);
  const [audioReviews, setAudioReviews] = useState<AudioReview[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedTab, setSelectedTab] = useState<TabKey>('overview');
  const [playingAudio, setPlayingAudio] = useState<string | null>(null);
  const [error, setError] = useState('');

  /**
   * Fetch memorization oversight data from API
   */
  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const [studentsRes, analyticsRes, reviewsRes] = await Promise.all([
          getMemorizationStudents(),
          getMemorizationAnalytics(),
          getAudioReviews()
        ]);
        
        setStudents(studentsRes.data || []);
        setAnalytics(analyticsRes.data || null);
        setAudioReviews(reviewsRes.data || []);
      } catch (err) {
        console.error('Failed to fetch memorization data:', err);
        setError('Failed to load memorization oversight data');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  /**
   * Handle audio playback for review submissions
   */
  const handleAudioPlay = (audioUrl: string) => {
    if (playingAudio === audioUrl) {
      setPlayingAudio(null);
    } else {
      setPlayingAudio(audioUrl);
    }
  };

  /**
   * Approve or reject audio review submission
   */
  const handleReviewAction = async (reviewId: number, action: 'approve' | 'reject', feedback?: string) => {
    try {
      await reviewAudioSubmission(reviewId, action, feedback);
      
      // Update local state
      setAudioReviews(prev => prev.map(review => 
        review.id === reviewId 
          ? { ...review, status: action === 'approve' ? 'approved' : 'reviewed' }
          : review
      ));
    } catch (err) {
      console.error(`Failed to ${action} review:`, err);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-4">
        <div className="flex items-center">
          <FaExclamationTriangle className="text-red-500 mr-2" />
          <span className="text-red-700">{error}</span>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-gradient-to-br from-indigo-50 via-white to-purple-50 rounded-xl shadow-xl p-6 border border-indigo-100 space-y-6 relative overflow-hidden">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
            Memorization Oversight
          </h2>
          <p className="text-gray-600 mt-1">Track student memorization progress and review AI-powered submissions</p>
        </div>
        
        {/* Tab Navigation */}
        <div className="flex space-x-1 bg-gradient-to-r from-indigo-100 to-purple-100 rounded-lg p-1 shadow-inner">
          {TAB_CONFIG.map(({ key, label, icon: Icon }, index) => (
            <button
              key={key}
              onClick={() => setSelectedTab(key)}
              className={`flex items-center px-4 py-2 rounded-md text-sm font-medium transition-all duration-300 transform hover:scale-105 ${
                selectedTab === key
                  ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white shadow-lg animate-pulse'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-white hover:shadow-md'
              }`}
              style={{ animationDelay: `${index * 0.1}s` }}
            >
              <Icon className={`mr-2 ${selectedTab === key ? 'animate-bounce' : ''}`} />
              {label}
            </button>
          ))}
        </div>
      </div>

      {/* Overview Tab */}
      {selectedTab === 'overview' && analytics && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-6"
        >
          {/* Analytics Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <motion.div 
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.1 }}
              className="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:scale-105"
            >
              <div className="flex items-center">
                <div className="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-md">
                  <FaUser className="text-white text-lg" />
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-blue-700">Active Memorizers</p>
                  <p className="text-2xl font-bold text-blue-900">
                    {analytics.active_memorizers}/{analytics.total_students}
                  </p>
                </div>
              </div>
            </motion.div>

            <motion.div 
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.2 }}
              className="bg-gradient-to-br from-green-50 to-green-100 rounded-lg shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:scale-105"
            >
              <div className="flex items-center">
                <div className="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-md">
                  <FaBookOpen className="text-white text-lg" />
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-green-700">Total Ayahs</p>
                  <p className="text-2xl font-bold text-green-900">{analytics.total_ayahs_memorized}</p>
                </div>
              </div>
            </motion.div>

            <motion.div 
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.3 }}
              className="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:scale-105"
            >
              <div className="flex items-center">
                <div className="p-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-md">
                  <FaStar className="text-white text-lg" />
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-purple-700">Avg Tajweed Score</p>
                  <p className="text-2xl font-bold text-purple-900">{analytics.average_tajweed_score}%</p>
                </div>
              </div>
            </motion.div>

            <motion.div 
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: 0.4 }}
              className="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg shadow-lg p-6 hover:shadow-xl transition-all duration-300 transform hover:scale-105"
            >
              <div className="flex items-center">
                <div className="p-3 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg shadow-md">
                  <FaTrophy className="text-white text-lg" />
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-orange-700">Completion Rate</p>
                  <p className="text-2xl font-bold text-orange-900">{analytics.completion_rate}%</p>
                </div>
              </div>
            </motion.div>
          </div>

          {/* Progress Chart */}
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Class Progress Overview</h3>
            <div className="flex items-center justify-center">
              <div className="w-32 h-32">
                <CircularProgressbar
                  value={analytics.average_progress}
                  text={`${analytics.average_progress}%`}
                  styles={buildStyles({
                    textColor: '#1f2937',
                    pathColor: '#3b82f6',
                    trailColor: '#e5e7eb'
                  })}
                />
              </div>
            </div>
            <p className="text-center text-gray-600 mt-4">Average Class Progress</p>
          </div>
        </motion.div>
      )}

      {/* Students Tab */}
      {selectedTab === 'students' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-4"
        >
          {students.map((student) => (
            <div key={student.id} className="bg-white rounded-lg shadow p-6">
              <div className="flex items-start justify-between">
                <div className="flex items-center">
                  <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    {student.avatar ? (
                      <Image
                        src={student.avatar}
                        alt={student.name}
                        width={48}
                        height={48}
                        className="h-12 w-12 rounded-full object-cover"
                      />
                    ) : (
                      <FaUser className="text-blue-600" />
                    )}
                  </div>
                  <div className="ml-4">
                    <h4 className="text-lg font-semibold text-gray-900">{student.name}</h4>
                    <p className="text-gray-600">{student.email}</p>
                  </div>
                </div>
                
                <div className="flex items-center space-x-4">
                  <div className="text-center">
                    <p className="text-2xl font-bold text-blue-600">{student.memorization_stats.total_ayahs_memorized}</p>
                    <p className="text-sm text-gray-600">Ayahs</p>
                  </div>
                  <div className="text-center">
                    <p className="text-2xl font-bold text-green-600">{student.memorization_stats.tajweed_score}%</p>
                    <p className="text-sm text-gray-600">Tajweed</p>
                  </div>
                  <div className="text-center">
                    <p className="text-2xl font-bold text-orange-600">{student.memorization_stats.current_streak}</p>
                    <p className="text-sm text-gray-600">Streak</p>
                  </div>
                </div>
              </div>

              {/* Student Alerts */}
              {student.alerts.length > 0 && (
                <div className="mt-4 space-y-2">
                  {student.alerts.map((alert, index) => (
                    <div key={index} className={`flex items-center p-3 rounded-lg ${
                      alert.type === 'warning' ? 'bg-yellow-50 text-yellow-800' :
                      alert.type === 'error' ? 'bg-red-50 text-red-800' :
                      alert.type === 'success' ? 'bg-green-50 text-green-800' :
                      'bg-blue-50 text-blue-800'
                    }`}>
                      <FaBell className="mr-2" />
                      <span className="text-sm">{alert.message}</span>
                    </div>
                  ))}
                </div>
              )}

              {/* Recent Plans */}
              {student.recent_plans.length > 0 && (
                <div className="mt-4">
                  <h5 className="text-sm font-medium text-gray-700 mb-2">Recent Memorization Plans</h5>
                  <div className="space-y-2">
                    {student.recent_plans.slice(0, 3).map((plan) => (
                      <div key={plan.id} className="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <div>
                          <p className="text-sm font-medium">{plan.surah_name} - {plan.ayah_range}</p>
                          <p className="text-xs text-gray-600">{plan.status}</p>
                        </div>
                        <div className="w-16">
                          <div className="bg-gray-200 rounded-full h-2">
                            <div 
                              className="bg-blue-600 h-2 rounded-full" 
                              style={{ width: `${plan.progress_percentage}%` }}
                            ></div>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          ))}
        </motion.div>
      )}

      {/* Audio Reviews Tab */}
      {selectedTab === 'reviews' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="space-y-4"
        >
          {audioReviews.map((review) => (
            <div key={review.id} className="bg-white rounded-lg shadow p-6">
              <div className="flex items-start justify-between">
                <div>
                  <h4 className="text-lg font-semibold text-gray-900">{review.student_name}</h4>
                  <p className="text-gray-600">{review.surah_name} - {review.ayah_range}</p>
                  <p className="text-sm text-gray-500">Submitted {new Date(review.submitted_at).toLocaleDateString()}</p>
                </div>
                
                <div className="flex items-center space-x-2">
                  <button
                    onClick={() => handleAudioPlay(review.audio_url)}
                    className="flex items-center px-3 py-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors"
                  >
                    {playingAudio === review.audio_url ? <FaPause className="mr-1" /> : <FaPlay className="mr-1" />}
                    {playingAudio === review.audio_url ? 'Pause' : 'Play'}
                  </button>
                  
                  {review.status === 'pending' && (
                    <div className="flex space-x-2">
                      <button
                        onClick={() => handleReviewAction(review.id, 'approve')}
                        className="px-3 py-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-colors"
                      >
                        <FaCheckCircle className="mr-1" />
                        Approve
                      </button>
                      <button
                        onClick={() => handleReviewAction(review.id, 'reject')}
                        className="px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors"
                      >
                        <FaExclamationTriangle className="mr-1" />
                        Reject
                      </button>
                    </div>
                  )}
                </div>
              </div>

              {/* Tajweed Analysis */}
              {review.tajweed_analysis && (
                <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                  <h5 className="text-sm font-medium text-gray-700 mb-2">Tajweed Analysis</h5>
                  <div className="grid grid-cols-3 gap-4 mb-3">
                    <div className="text-center">
                      <p className="text-lg font-bold text-blue-600">{review.tajweed_analysis.overall_score}%</p>
                      <p className="text-xs text-gray-600">Overall</p>
                    </div>
                    <div className="text-center">
                      <p className="text-lg font-bold text-green-600">{review.tajweed_analysis.pronunciation_score}%</p>
                      <p className="text-xs text-gray-600">Pronunciation</p>
                    </div>
                    <div className="text-center">
                      <p className="text-lg font-bold text-purple-600">{review.tajweed_analysis.fluency_score}%</p>
                      <p className="text-xs text-gray-600">Fluency</p>
                    </div>
                  </div>
                  
                  {review.tajweed_analysis.mistakes.length > 0 && (
                    <div className="mb-2">
                      <p className="text-sm font-medium text-red-600">Mistakes:</p>
                      <ul className="text-sm text-gray-600 list-disc list-inside">
                        {review.tajweed_analysis.mistakes.map((mistake, index) => (
                          <li key={index}>{mistake}</li>
                        ))}
                      </ul>
                    </div>
                  )}
                  
                  {review.tajweed_analysis.suggestions.length > 0 && (
                    <div>
                      <p className="text-sm font-medium text-blue-600">Suggestions:</p>
                      <ul className="text-sm text-gray-600 list-disc list-inside">
                        {review.tajweed_analysis.suggestions.map((suggestion, index) => (
                          <li key={index}>{suggestion}</li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>
              )}
            </div>
          ))}
        </motion.div>
      )}
    </div>
  );
}