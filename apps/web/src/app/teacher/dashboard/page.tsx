/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React from 'react';
import { motion } from 'framer-motion';
import { useTranslations } from 'next-intl';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import AnalyticsSection from '@/components/teacher/AnalyticsSection';
import NotificationSection from '@/components/teacher/NotificationSection';
import ClassSection from '@/components/teacher/ClassSection';
import SubmissionSection from '@/components/teacher/SubmissionSection';
import GameAnalyticsSection from '@/components/teacher/GameAnalyticsSection';
import MemorizationOversightSection from '@/components/teacher/MemorizationOversightSection';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  BookOpen, 
  Users, 
  TrendingUp, 
  Bell, 
  Calendar,
  Settings,
  RefreshCw,
  Brain
} from 'lucide-react';

// Create a query client instance
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
      cacheTime: 10 * 60 * 1000, // 10 minutes
      refetchOnWindowFocus: false,
    },
  },
});

/**
 * Teacher Dashboard Page Component
 * Main dashboard interface for teachers with analytics, notifications, and management tools
 */
function TeacherDashboardContent() {
  const t = useTranslations('teacher');

  // Animation variants for staggered entrance
  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1,
        delayChildren: 0.2,
      },
    },
  };

  const itemVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: {
      opacity: 1,
      y: 0,
      transition: {
        duration: 0.5,
        ease: 'easeOut',
      },
    },
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
      {/* Header Section */}
      <motion.div 
        className="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-b border-emerald-200 dark:border-gray-700 sticky top-0 z-10"
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.6 }}
      >
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="p-2 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-lg">
                <BookOpen className="h-6 w-6 text-white" />
              </div>
              <div>
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                  {t('dashboard.title', { defaultValue: 'Teacher Dashboard' })}
                </h1>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  {t('dashboard.subtitle', { defaultValue: 'Manage your classes and track student progress' })}
                </p>
              </div>
            </div>
            
            <div className="flex items-center space-x-3">
              <Badge variant="secondary" className="bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                <Calendar className="h-3 w-3 mr-1" />
                {new Date().toLocaleDateString('en-US', { 
                  weekday: 'short', 
                  month: 'short', 
                  day: 'numeric' 
                })}
              </Badge>
              
              <Button variant="outline" size="sm" className="hidden sm:flex">
                <Settings className="h-4 w-4 mr-2" />
                {t('dashboard.settings', { defaultValue: 'Settings' })}
              </Button>
              
              <Button 
                variant="outline" 
                size="sm"
                onClick={() => queryClient.invalidateQueries()}
                className="hidden sm:flex"
              >
                <RefreshCw className="h-4 w-4 mr-2" />
                {t('dashboard.refresh', { defaultValue: 'Refresh' })}
              </Button>
            </div>
          </div>
        </div>
      </motion.div>

      {/* Main Content */}
      <motion.div 
        className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
        variants={containerVariants}
        initial="hidden"
        animate="visible"
      >
        {/* Analytics Overview */}
        <motion.div variants={itemVariants} className="mb-8">
          <Card className="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-emerald-200 dark:border-gray-700 shadow-lg">
            <CardHeader className="pb-4">
              <CardTitle className="flex items-center text-lg font-semibold text-gray-900 dark:text-white">
                <TrendingUp className="h-5 w-5 mr-2 text-emerald-600" />
                {t('dashboard.analytics', { defaultValue: 'Analytics Overview' })}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <AnalyticsSection />
            </CardContent>
          </Card>
        </motion.div>

        {/* Two Column Layout */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          {/* Notifications */}
          <motion.div variants={itemVariants}>
            <Card className="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-emerald-200 dark:border-gray-700 shadow-lg h-full">
              <CardHeader className="pb-4">
                <CardTitle className="flex items-center text-lg font-semibold text-gray-900 dark:text-white">
                  <Bell className="h-5 w-5 mr-2 text-amber-600" />
                  {t('dashboard.notifications', { defaultValue: 'Recent Notifications' })}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <NotificationSection />
              </CardContent>
            </Card>
          </motion.div>

          {/* Class Management */}
          <motion.div variants={itemVariants}>
            <Card className="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-emerald-200 dark:border-gray-700 shadow-lg h-full">
              <CardHeader className="pb-4">
                <CardTitle className="flex items-center text-lg font-semibold text-gray-900 dark:text-white">
                  <Users className="h-5 w-5 mr-2 text-blue-600" />
                  {t('dashboard.classes', { defaultValue: 'Class Management' })}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <ClassSection />
              </CardContent>
            </Card>
          </motion.div>
        </div>

        {/* Submissions, Memorization Oversight and Game Analytics */}
        <div className="grid grid-cols-1 xl:grid-cols-3 gap-8">
          {/* Submissions */}
          <motion.div variants={itemVariants}>
            <Card className="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-emerald-200 dark:border-gray-700 shadow-lg">
              <CardHeader className="pb-4">
                <CardTitle className="flex items-center text-lg font-semibold text-gray-900 dark:text-white">
                  <BookOpen className="h-5 w-5 mr-2 text-purple-600" />
                  {t('dashboard.submissions', { defaultValue: 'Recent Submissions' })}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <SubmissionSection />
              </CardContent>
            </Card>
          </motion.div>

          {/* Memorization Oversight */}
          <motion.div variants={itemVariants}>
            <Card className="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-emerald-200 dark:border-gray-700 shadow-lg">
              <CardHeader className="pb-4">
                <CardTitle className="flex items-center text-lg font-semibold text-gray-900 dark:text-white">
                  <Brain className="h-5 w-5 mr-2 text-purple-600" />
                  {t('dashboard.memorizationOversight', { defaultValue: 'Memorization Oversight' })}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <MemorizationOversightSection />
              </CardContent>
            </Card>
          </motion.div>

          {/* Game Analytics */}
          <motion.div variants={itemVariants}>
            <Card className="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-emerald-200 dark:border-gray-700 shadow-lg">
              <CardHeader className="pb-4">
                <CardTitle className="flex items-center text-lg font-semibold text-gray-900 dark:text-white">
                  <TrendingUp className="h-5 w-5 mr-2 text-indigo-600" />
                  {t('dashboard.gameAnalytics', { defaultValue: 'Game Analytics' })}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <GameAnalyticsSection />
              </CardContent>
            </Card>
          </motion.div>
        </div>
      </motion.div>
    </div>
  );
}

/**
 * Main Teacher Dashboard Page with Query Client Provider
 * Wraps the dashboard content with React Query provider for data management
 */
export default function TeacherDashboardPage() {
  return (
    <QueryClientProvider client={queryClient}>
      <TeacherDashboardContent />
      <ReactQueryDevtools initialIsOpen={false} />
    </QueryClientProvider>
  );
}