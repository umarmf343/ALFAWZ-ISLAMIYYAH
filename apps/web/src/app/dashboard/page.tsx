/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useEffect, useState } from 'react';
import Link from 'next/link';
import { useAuth } from '@/contexts/AuthContext';
import { api } from '@/lib/api';
import { formatHasanat, getHasanatBadge } from '@/lib/hasanat';
import { Class, Assignment, Submission } from '@/types';
import { motion, AnimatePresence } from 'framer-motion';
import { CircularProgressbar, buildStyles } from 'react-circular-progressbar';
import 'react-circular-progressbar/dist/styles.css';
import Confetti from 'react-confetti';
import { FaShare, FaFire, FaStar, FaBook, FaClock, FaTrophy, FaAward, FaChevronRight, FaBookmark, FaClipboardList, FaCheckCircle } from 'react-icons/fa';
import HasanatCounter from '@/components/HasanatCounter';
import OfflineIndicator, { OfflineBadge } from '@/components/OfflineIndicator';
import { offlineApi, isDataStale } from '@/lib/offlineApi';
import { useTranslations } from 'next-intl';
import MemorizationSection from '@/components/MemorizationSection';
import MessageSection from '@/components/student/MessageSection';
import MemorizationOversight from '@/components/MemorizationOversight';

interface DashboardStats {
  classes_count?: number;
  assignments_count?: number;
  submissions_count?: number;
  total_hasanat?: number;
  pending_submissions?: number;
  recent_classes?: Class[];
  recent_assignments?: Assignment[];
  recent_submissions?: Submission[];
}

interface StudentDashboardData {
  greeting: string;
  hasanat_total: number;
  daily_progress: {
    verses_read: number;
    daily_goal: number;
    time_spent: number;
    streak_days: number;
    goal_achieved: boolean;
    progress_percentage: number;
    hasanat_earned: number;
  };
  weekly_stats: {
    verses: number;
    hasanat: number;
    time_spent: number;
    days_active: number;
  };
  recent_surahs: Array<{
    surah_id: number;
    ayah_number: number;
    last_seen_at: string;
    hasanat: number;
  }>;
  badges: Array<{
    name: string;
    icon: string;
  }>;
}

interface AyahOfDay {
  surah_id: number;
  ayah_number: number;
  surah_name: string;
  surah_name_arabic: string;
  text_arabic: string;
  text_english: string;
  reference: string;
}

interface Recommendation {
  surah_id: number;
  title: string;
  reason: string;
  priority: string;
}

/**
 * Main dashboard page showing user-specific overview and quick actions.
 * Displays different content based on user role (student/teacher).
 */
export default function DashboardPage() {
  const { user, token, isAuthenticated, isLoading, isStudent, isTeacher } = useAuth();
  const t = useTranslations('dashboard');
  const [stats, setStats] = useState<DashboardStats>({});
  const [isLoadingStats, setIsLoadingStats] = useState(true);
  
  // Student Dashboard specific state
  const [studentData, setStudentData] = useState<StudentDashboardData | null>(null);
  const [ayahOfDay, setAyahOfDay] = useState<AyahOfDay | null>(null);
  const [recommendations, setRecommendations] = useState<Recommendation[]>([]);
  const [showConfetti, setShowConfetti] = useState(false);
  const [isReciting, setIsReciting] = useState(false);
  const [fromCache, setFromCache] = useState(false);
  const [lastUpdated, setLastUpdated] = useState<number | null>(null);

  /**
   * Fetch dashboard statistics based on user role with offline support
   */
  useEffect(() => {
    const fetchStats = async () => {
      if (!isAuthenticated || !user) return;

      try {
        setIsLoadingStats(true);
        
        // Set auth token for offline API
        offlineApi.setToken(token ?? null);
        
        if (isStudent) {
          // Fetch student-specific dashboard data with offline support
          const response = await offlineApi.getDashboardData();
          
          setStudentData(response.data.studentData);
          setAyahOfDay(response.data.ayahOfDay);
          setRecommendations(response.data.recommendations || []);
          setFromCache(response.fromCache);
          setLastUpdated(response.timestamp);
          
          setStats({
            classes_count: response.data.stats?.classes_count || 0,
            assignments_count: response.data.stats?.assignments_count || 0,
            submissions_count: response.data.stats?.submissions_count || 0,
            recent_classes: response.data.stats?.recent_classes || [],
            recent_assignments: response.data.stats?.recent_assignments || [],
            recent_submissions: response.data.stats?.recent_submissions || [],
          });
        } else if (isTeacher) {
          const [classesRes, assignmentsRes, submissionsRes] = await Promise.all([
            api.get('/classes'),
            api.get('/assignments?limit=5'),
            api.get('/submissions?status=pending&limit=5')
          ]);
          
          setStats({
            classes_count: classesRes.data?.data?.length || 0,
            assignments_count: assignmentsRes.data?.total || 0,
            pending_submissions: submissionsRes.data?.total || 0,
            recent_classes: classesRes.data?.data?.slice(0, 3) || [],
            recent_assignments: assignmentsRes.data?.data || [],
            recent_submissions: submissionsRes.data?.data || [],
          });
        }
      } catch (error) {
        console.error('Failed to fetch dashboard stats:', error);
      } finally {
        setIsLoadingStats(false);
      }
    };

    fetchStats();
  }, [isAuthenticated, user, isStudent, isTeacher, token]);

  /**
   * Handle recitation update
   */
  const handleRecitation = async (surahId: number, ayahNumber: number) => {
    if (isReciting) return;
    
    try {
      setIsReciting(true);
      const response = await api.post('/student/recite', {
        surah_id: surahId,
        ayah_number: ayahNumber,
        time_spent: 60
      });
      
      // Show confetti if goal achieved
      if (response.data.goal_achieved && !studentData?.daily_progress.goal_achieved) {
        setShowConfetti(true);
        setTimeout(() => setShowConfetti(false), 5000);
      }
      
      // Refresh dashboard data
      const dashboardRes = await api.get('/student/dashboard');
      setStudentData(dashboardRes.data);
      
    } catch (error) {
      console.error('Failed to update recitation:', error);
    } finally {
      setIsReciting(false);
    }
  };

  /**
   * Share Ayah of the Day
   */
  const shareAyah = async () => {
    if (!ayahOfDay) return;
    
    const shareData = {
      title: 'Ayah of the Day',
      text: `${ayahOfDay.text_english} (${ayahOfDay.reference})`,
      url: window.location.href
    };
    
    try {
      if (navigator.share) {
        await navigator.share(shareData);
      } else {
        // Fallback: copy to clipboard
        await navigator.clipboard.writeText(`${shareData.text} - ${shareData.url}`);
        alert('Ayah copied to clipboard!');
      }
    } catch (error) {
      console.error('Failed to share ayah:', error);
    }
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Please log in</h1>
          <Link
            href="/login"
            className="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700"
          >
            Go to Login
          </Link>
        </div>
      </div>
    );
  }

  const badge = getHasanatBadge(stats.total_hasanat || 0);

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Welcome Header with Offline Indicator */}
        <div className="mb-8 flex justify-between items-start">
          <div>
            <div className="flex items-center space-x-3 mb-2">
              <h1 className="text-3xl font-bold text-gray-900">
                {t('welcome', { name: user?.name })}
              </h1>
              <OfflineBadge />
            </div>
            <p className="text-gray-600">
              {t('subtitle')}
            </p>
            {fromCache && lastUpdated && (
              <div className="mt-2 flex items-center space-x-2 text-sm text-yellow-600">
                <span>📱</span>
                <span>
                  Showing cached data {isDataStale(lastUpdated, 15) ? '(may be outdated)' : '(recent)'}
                </span>
              </div>
            )}
          </div>
          <OfflineIndicator />
        </div>

        {/* Student Dashboard */}
        {isStudent && (
          <div className="space-y-6">
            {/* Confetti Animation */}
            {showConfetti && (
              <Confetti
                width={window.innerWidth}
                height={window.innerHeight}
                recycle={false}
                numberOfPieces={200}
              />
            )}

            {/* Enhanced Welcome Section with Greeting */}
            <motion.div 
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="bg-gradient-to-r from-emerald-500 via-teal-600 to-cyan-600 rounded-2xl p-8 text-white relative overflow-hidden"
            >
              <div className="absolute inset-0 bg-black/10"></div>
              <div className="relative z-10">
                <h1 className="text-3xl font-bold mb-2">
                  {studentData?.greeting || t('greeting', { name: user?.name })}
                </h1>
                <p className="text-emerald-100 text-lg">
                  {t('continueJourney')} • {new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                </p>
                {studentData?.daily_progress.streak_days > 0 && (
                   <div className="mt-4 flex items-center space-x-2">
                     <FaFire className="text-orange-300" />
                     <span className="text-emerald-100">
                       {studentData.daily_progress.streak_days} day streak!
                     </span>
                   </div>
                 )}
              </div>
            </motion.div>

            {/* Hasanat Counter & Daily Progress */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Real-time Hasanat Counter */}
              <HasanatCounter 
                currentHasanat={studentData?.hasanat_total || 0}
                previousHasanat={studentData?.previous_hasanat || 0}
              />

              {/* Daily Progress Circle */}
              <motion.div 
                initial={{ opacity: 0, scale: 0.9 }}
                animate={{ opacity: 1, scale: 1 }}
                className="bg-white rounded-2xl p-6 shadow-lg border border-gray-100"
                role="region"
                aria-labelledby="daily-progress-heading"
              >
                <h3 id="daily-progress-heading" className="text-lg font-semibold text-gray-900 mb-4">{t('dailyProgress')}</h3>
                <div className="flex items-center justify-center">
                  <div className="w-32 h-32">
                    <CircularProgressbar
                      value={studentData?.daily_progress.progress_percentage || 0}
                      text={`${studentData?.daily_progress.progress_percentage || 0}%`}
                      styles={buildStyles({
                        pathColor: '#10b981',
                        textColor: '#10b981',
                        trailColor: '#e5e7eb'
                      })}
                    />
                  </div>
                </div>
                <div className="mt-4 text-center">
                  <p className="text-sm text-gray-600">
                     {t('versesProgress', { read: studentData?.daily_progress.verses_read || 0, goal: studentData?.daily_progress.daily_goal || 10 })}
                   </p>
                  {studentData?.daily_progress.goal_achieved && (
                    <p className="text-green-600 font-medium mt-1">🎉 {t('goalAchieved')}</p>
                  )}
                </div>
              </motion.div>
            </div>

            {/* Quick Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <motion.div 
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.1 }}
                className="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
              >
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-gray-600">{t('classes')}</p>
                    <p className="text-2xl font-bold text-gray-900">
                      {isLoadingStats ? '...' : stats.classes_count || 0}
                    </p>
                  </div>
                  <div className="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <FaBook className="h-6 w-6 text-blue-600" />
                  </div>
                </div>
              </motion.div>

              <motion.div 
                 initial={{ opacity: 0, y: 20 }}
                 animate={{ opacity: 1, y: 0 }}
                 transition={{ delay: 0.2 }}
                 className="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
               >
                 <div className="flex items-center justify-between">
                   <div>
                     <p className="text-sm font-medium text-gray-600">{t('todaysHasanat')}</p>
                     <p className="text-2xl font-bold text-gray-900">
                       {studentData?.daily_progress.hasanat_earned || 0}
                     </p>
                   </div>
                   <div className="h-12 w-12 bg-amber-100 rounded-lg flex items-center justify-center">
                     <FaStar className="h-6 w-6 text-amber-600" />
                   </div>
                 </div>
               </motion.div>

              <motion.div 
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.3 }}
                className="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
              >
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-gray-600">{t('streak')}</p>
                     <p className="text-2xl font-bold text-gray-900">
                       {studentData?.daily_progress.streak_days || 0}
                     </p>
                  </div>
                  <div className="h-12 w-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <FaFire className="h-6 w-6 text-orange-600" />
                  </div>
                </div>
              </motion.div>

              <motion.div 
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.4 }}
                className="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
              >
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-gray-600">{t('badges')}</p>
                    <p className="text-2xl font-bold text-gray-900">
                      {studentData?.badges?.length || 0}
                    </p>
                  </div>
                  <div className="h-12 w-12 bg-purple-100 rounded-lg flex items-center justify-center">
                     <FaAward className="h-6 w-6 text-purple-600" />
                   </div>
                </div>
              </motion.div>
            </div>

            {/* Ayah of the Day */}
            {ayahOfDay && (
              <motion.div 
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-8 text-white"
              >
                <div className="flex items-start justify-between mb-4">
                  <h3 className="text-xl font-semibold">{t('ayahOfTheDay')}</h3>
                  <button 
                    onClick={shareAyah}
                    className="p-2 bg-white/20 rounded-lg hover:bg-white/30 transition-colors focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-transparent"
                    aria-label={t('shareAyah')}
                  >
                    <FaShare className="h-4 w-4" aria-hidden="true" />
                  </button>
                </div>
                <div className="mb-4">
                  <p className="text-2xl font-arabic mb-3 leading-relaxed">
                    {ayahOfDay.text_arabic}
                  </p>
                  <p className="text-lg text-indigo-100 mb-2">
                    {ayahOfDay.text_english}
                  </p>
                  <p className="text-indigo-200 font-medium">
                    {ayahOfDay.reference}
                  </p>
                </div>
                <button
                  onClick={() => handleRecitation(ayahOfDay.surah_id, ayahOfDay.ayah_number)}
                  disabled={isReciting}
                  className="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-colors disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-transparent"
                  aria-label={isReciting ? t('recording') : t('markAsRead')}
                  aria-pressed={isReciting}
                >
                  {isReciting ? t('recording') : t('markAsRead')}
                </button>
              </motion.div>
            )}

            {/* Recent Activity & Recommendations */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Recent Surahs */}
              <motion.div 
                initial={{ opacity: 0, x: -20 }}
                animate={{ opacity: 1, x: 0 }}
                className="bg-white rounded-2xl p-6 shadow-lg border border-gray-100"
              >
                <h3 className="text-lg font-semibold text-gray-900 mb-4">{t('recentSurahs')}</h3>
                {studentData?.recent_surahs && studentData.recent_surahs.length > 0 ? (
                  <div className="space-y-3">
                    {studentData.recent_surahs.map((surah: any, index: number) => (
                      <motion.div 
                        key={index}
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: index * 0.1 }}
                        className="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                        onClick={() => handleRecitation(surah.surah_id, surah.ayah_number)}
                        onKeyDown={(e) => {
                          if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            handleRecitation(surah.surah_id, surah.ayah_number);
                          }
                        }}
                        tabIndex={0}
                        role="button"
                        aria-label={`Recite Surah ${surah.surah_id}, Ayah ${surah.ayah_number}, earn ${surah.hasanat} hasanat`}
                      >
                        <div>
                          <p className="font-medium text-gray-900">Surah {surah.surah_id}</p>
                          <p className="text-sm text-gray-600">Ayah {surah.ayah_number}</p>
                        </div>
                        <div className="flex items-center space-x-2">
                          <span className="text-xs bg-emerald-100 text-emerald-800 px-2 py-1 rounded-full">
                            +{surah.hasanat} Hasanat
                          </span>
                        </div>
                      </motion.div>
                    ))}
                  </div>
                ) : (
                  <p className="text-gray-500 text-center py-8">{t('startRecitation')}</p>
                )}
              </motion.div>

              {/* Recommendations */}
              <motion.div 
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                className="bg-white rounded-2xl p-6 shadow-lg border border-gray-100"
              >
                <h3 className="text-lg font-semibold text-gray-900 mb-4">{t('recommendedForYou')}</h3>
                {recommendations && recommendations.length > 0 ? (
                  <div className="space-y-3">
                    {recommendations.map((rec: Recommendation, index: number) => (
                      <motion.div 
                        key={index}
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: index * 0.1 }}
                        className="p-4 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border border-emerald-100"
                      >
                        <div className="flex items-start justify-between">
                          <div>
                            <p className="font-medium text-gray-900 mb-1">{rec.title}</p>
                            <p className="text-sm text-gray-600 mb-2">{rec.reason}</p>
                            <span className={`inline-block px-2 py-1 rounded-full text-xs font-medium ${
                              rec.priority === 'high' ? 'bg-red-100 text-red-800' :
                              rec.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                              'bg-green-100 text-green-800'
                            }`}>
                              {rec.priority} priority
                            </span>
                          </div>
                        </div>
                      </motion.div>
                    ))}
                  </div>
                ) : (
                  <p className="text-gray-500 text-center py-8">{t('noRecommendations')}</p>
                )}
              </motion.div>
            </div>

            {/* Message Center & Memorization */}
            <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.5 }}
                className="h-full"
              >
                <MessageSection />
              </motion.div>

              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.65 }}
              >
                <MemorizationSection />
              </motion.div>
            </div>
          </div>
        )}

        {/* Teacher Dashboard */}
        {isTeacher && (
          <div className="space-y-6">
            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="bg-white rounded-lg shadow p-6">
                <div className="flex items-center">
                  <div className="p-2 bg-blue-100 rounded-lg">
                    <span className="text-blue-600 text-xl">🏫</span>
                  </div>
                  <div className="ml-4">
                    <p className="text-sm font-medium text-gray-600">{t('myClasses')}</p>
                    <p className="text-2xl font-bold text-gray-900">
                      {isLoadingStats ? '...' : stats.classes_count}
                    </p>
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-lg shadow p-6">
                <div className="flex items-center">
                  <div className="p-2 bg-green-100 rounded-lg">
                    <span className="text-green-600 text-xl">📋</span>
                  </div>
                  <div className="ml-4">
                    <p className="text-sm font-medium text-gray-600">{t('assignments')}</p>
                    <p className="text-2xl font-bold text-gray-900">
                      {isLoadingStats ? '...' : stats.assignments_count}
                    </p>
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-lg shadow p-6">
                <div className="flex items-center">
                  <div className="p-2 bg-orange-100 rounded-lg">
                    <span className="text-orange-600 text-xl">⏳</span>
                  </div>
                  <div className="ml-4">
                    <p className="text-sm font-medium text-gray-600">{t('pendingReviews')}</p>
                    <p className="text-2xl font-bold text-gray-900">
                      {isLoadingStats ? '...' : stats.pending_submissions}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {/* Leaderboard Oversight */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.4 }}
              className="bg-white rounded-2xl p-6 shadow-lg border border-gray-100"
            >
              <div className="flex items-center justify-between mb-6">
                <h3 className="text-lg font-semibold text-gray-900 flex items-center">
                  <FaTrophy className="h-5 w-5 text-yellow-500 mr-2" />
                  {t('leaderboardOversight')}
                </h3>
                <Link
                  href="/teacher/leaderboard"
                  className="text-green-600 hover:text-green-700 text-sm font-medium flex items-center"
                >
                  {t('viewAll')}
                  <FaChevronRight className="h-3 w-3 ml-1" />
                </Link>
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div className="bg-gradient-to-r from-yellow-50 to-amber-50 rounded-xl p-4 border border-yellow-200">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-yellow-800">{t('topPerformer')}</p>
                      <p className="text-lg font-bold text-yellow-900">Ahmad Ali</p>
                      <p className="text-xs text-yellow-700">2,450 Hasanat</p>
                    </div>
                    <div className="h-10 w-10 bg-yellow-200 rounded-full flex items-center justify-center">
                      <FaTrophy className="h-5 w-5 text-yellow-600" />
                    </div>
                  </div>
                </div>
                
                <div className="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-green-800">{t('activeStudents')}</p>
                      <p className="text-lg font-bold text-green-900">24/30</p>
                      <p className="text-xs text-green-700">{t('thisWeek')}</p>
                    </div>
                    <div className="h-10 w-10 bg-green-200 rounded-full flex items-center justify-center">
                      <FaFire className="h-5 w-5 text-green-600" />
                    </div>
                  </div>
                </div>
                
                <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-blue-800">{t('avgProgress')}</p>
                      <p className="text-lg font-bold text-blue-900">78%</p>
                      <p className="text-xs text-blue-700">{t('classAverage')}</p>
                    </div>
                    <div className="h-10 w-10 bg-blue-200 rounded-full flex items-center justify-center">
                      <FaBook className="h-5 w-5 text-blue-600" />
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="space-y-3">
                <h4 className="text-sm font-medium text-gray-700">{t('recentLeaderboardActivity')}</h4>
                <div className="space-y-2">
                  {[
                    { name: 'Fatima Hassan', action: 'completed Surah Al-Fatiha', hasanat: 150, time: '2 hours ago' },
                    { name: 'Omar Khalil', action: 'achieved 7-day streak', hasanat: 100, time: '4 hours ago' },
                    { name: 'Aisha Mohamed', action: 'memorized 5 new ayahs', hasanat: 200, time: '6 hours ago' }
                  ].map((activity, index) => (
                    <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                      <div className="flex-1">
                        <p className="text-sm font-medium text-gray-900">{activity.name}</p>
                        <p className="text-xs text-gray-600">{activity.action}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-xs font-medium text-green-600">+{activity.hasanat} Hasanat</p>
                        <p className="text-xs text-gray-500">{activity.time}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </motion.div>

            {/* Memorization Oversight */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.5 }}
            >
              <MemorizationOversight />
            </motion.div>
          </div>
        )}

        {/* Quick Actions */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Recent Activity */}
          <div className="bg-white rounded-lg shadow">
            <div className="p-6 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">
                {isStudent ? t('recentAssignments') : t('recentActivity')}
              </h2>
            </div>
            <div className="p-6">
              {isLoadingStats ? (
                <div className="animate-pulse space-y-4">
                  {[1, 2, 3].map(i => (
                    <div key={i} className="h-4 bg-gray-200 rounded"></div>
                  ))}
                </div>
              ) : stats.recent_assignments?.length ? (
                <div className="space-y-4">
                  {stats.recent_assignments.map(assignment => (
                    <div key={assignment.id} className="flex items-center justify-between">
                      <div>
                        <p className="font-medium text-gray-900">{assignment.title}</p>
                        <p className="text-sm text-gray-500">
                          {assignment.class?.name}
                        </p>
                      </div>
                      <Link
                        href={`/assignments/${assignment.id}`}
                        className="text-green-600 hover:text-green-700 text-sm font-medium"
                      >
                        View
                      </Link>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-gray-500 text-center py-4">
                  {t('noRecentAssignments')}
                </p>
              )}
            </div>
          </div>

          {/* Quick Actions */}
          <div className="bg-white rounded-lg shadow">
            <div className="p-6 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">{t('quickActions')}</h2>
            </div>
            <div className="p-6">
              <div className="space-y-4">
                {isStudent && (
                  <>
                    <Link
                      href="/classes"
                      className="block w-full text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex items-center">
                        <span className="text-2xl mr-3">📚</span>
                        <div>
                          <p className="font-medium text-gray-900">{t('browseClasses')}</p>
                          <p className="text-sm text-gray-500">{t('browseClassesDesc')}</p>
                        </div>
                      </div>
                    </Link>
                    <Link
                      href="/assignments"
                      className="block w-full text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex items-center">
                        <span className="text-2xl mr-3">📝</span>
                        <div>
                          <p className="font-medium text-gray-900">{t('viewAssignments')}</p>
                          <p className="text-sm text-gray-500">{t('viewAssignmentsDesc')}</p>
                        </div>
                      </div>
                    </Link>
                    <Link
                      href="/leaderboard"
                      className="block w-full text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex items-center">
                        <span className="text-2xl mr-3">🏆</span>
                        <div>
                          <p className="font-medium text-gray-900">{t('leaderboard')}</p>
                          <p className="text-sm text-gray-500">{t('leaderboardDesc')}</p>
                        </div>
                      </div>
                    </Link>
                  </>
                )}
                
                {isTeacher && (
                  <>
                    <Link
                      href="/teacher/classes"
                      className="block w-full text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex items-center">
                        <span className="text-2xl mr-3">🏫</span>
                        <div>
                          <p className="font-medium text-gray-900">{t('manageClasses')}</p>
                          <p className="text-sm text-gray-500">{t('manageClassesDesc')}</p>
                        </div>
                      </div>
                    </Link>
                    <Link
                      href="/teacher/assignments"
                      className="block w-full text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex items-center">
                        <span className="text-2xl mr-3">📋</span>
                        <div>
                          <p className="font-medium text-gray-900">{t('createAssignment')}</p>
                          <p className="text-sm text-gray-500">{t('createAssignmentDesc')}</p>
                        </div>
                      </div>
                    </Link>
                    <Link
                      href="/teacher/submissions"
                      className="block w-full text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex items-center">
                        <span className="text-2xl mr-3">✅</span>
                        <div>
                          <p className="font-medium text-gray-900">{t('reviewSubmissions')}</p>
                          <p className="text-sm text-gray-500">{t('reviewSubmissionsDesc')}</p>
                        </div>
                      </div>
                    </Link>
                    <Link
                      href="/teacher/leaderboard"
                      className="block w-full text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <div className="flex items-center">
                        <span className="text-2xl mr-3">🏆</span>
                        <div>
                          <p className="font-medium text-gray-900">{t('manageLeaderboard')}</p>
                          <p className="text-sm text-gray-500">{t('manageLeaderboardDesc')}</p>
                        </div>
                      </div>
                    </Link>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}