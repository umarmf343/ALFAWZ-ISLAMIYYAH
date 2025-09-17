'use client';

import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { motion } from 'framer-motion';
import { useTranslations } from 'next-intl';
import { api } from '@/lib/api';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
  Users,
  TrendingUp,
  Target,
  Zap,
  Award,
  BookOpen,
  ClipboardList,
  Clock,
} from 'lucide-react';
import { CircularProgressbar, buildStyles } from 'react-circular-progressbar';
import 'react-circular-progressbar/dist/styles.css';

interface RawAnalytics {
  total_students?: number;
  completion_rate?: number;
  hotspot_interactions?: number;
  game_sessions?: number;
  high_scores?: number;
  average_score?: number;
  active_assignments?: number;
  pending_submissions?: number;
  last_updated?: string | null;
}

interface AnalyticsSummary {
  totalStudents: number;
  completionRate: number;
  hotspotInteractions: number;
  gameSessions: number;
  highScores: number;
  averageScore: number;
  activeAssignments: number;
  pendingSubmissions: number;
  lastUpdated?: string | null;
}

const fetchAnalytics = async (): Promise<AnalyticsSummary> => {
  const response = await api.get('/teacher/dashboard');
  const payload = ((response as any)?.analytics ?? (response as any)?.data?.analytics ?? {}) as RawAnalytics;

  return {
    totalStudents: Number(payload.total_students ?? 0),
    completionRate: Number(payload.completion_rate ?? 0),
    hotspotInteractions: Number(payload.hotspot_interactions ?? 0),
    gameSessions: Number(payload.game_sessions ?? 0),
    highScores: Number(payload.high_scores ?? 0),
    averageScore: Number(payload.average_score ?? 0),
    activeAssignments: Number(payload.active_assignments ?? 0),
    pendingSubmissions: Number(payload.pending_submissions ?? 0),
    lastUpdated: payload.last_updated ?? null,
  };
};

interface MetricCardProps {
  title: string;
  value: number | string;
  percentage: number;
  icon: React.ElementType;
  color: string;
  helper?: string;
}

function MetricCard({ title, value, percentage, icon: Icon, color, helper }: MetricCardProps) {
  return (
    <motion.div whileHover={{ scale: 1.02, y: -2 }} whileTap={{ scale: 0.98 }} transition={{ duration: 0.2 }}>
      <Card className="bg-white/60 dark:bg-gray-800/60 border-gray-200 dark:border-gray-700 backdrop-blur-sm hover:shadow-lg transition-all duration-300">
        <CardContent className="p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center space-x-3">
              <div className={`p-2 rounded-lg ${color}`}>
                <Icon className="h-5 w-5 text-white" />
              </div>
              <div>
                <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">{title}</h3>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">{value}</p>
              </div>
            </div>
            <div className="w-16 h-16">
              <CircularProgressbar
                value={Math.max(0, Math.min(percentage, 100))}
                text={`${Math.round(Math.max(0, Math.min(percentage, 100)))}%`}
                styles={buildStyles({
                  textSize: '18px',
                  pathColor: '#10b981',
                  textColor: '#1f2937',
                  trailColor: '#e5e7eb',
                  backgroundColor: '#f3f4f6',
                })}
              />
            </div>
          </div>
          {helper && (
            <p className="text-xs text-gray-500 dark:text-gray-400">{helper}</p>
          )}
        </CardContent>
      </Card>
    </motion.div>
  );
}

function AnalyticsLoading() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      {Array.from({ length: 4 }).map((_, index) => (
        <Card key={index} className="bg-white/60 dark:bg-gray-800/60 border-gray-200 dark:border-gray-700">
          <CardContent className="p-6 space-y-4">
            <div className="flex items-center space-x-3">
              <Skeleton className="h-10 w-10 rounded-lg" />
              <div className="space-y-2">
                <Skeleton className="h-4 w-24" />
                <Skeleton className="h-6 w-16" />
              </div>
            </div>
            <Skeleton className="h-6 w-full" />
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

function AnalyticsError({ error }: { error: Error }) {
  return (
    <Card className="bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
      <CardContent className="p-6 text-center">
        <div className="text-red-600 dark:text-red-400 mb-2">
          <TrendingUp className="h-8 w-8 mx-auto" />
        </div>
        <h3 className="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">Failed to load analytics</h3>
        <p className="text-sm text-red-600 dark:text-red-400">{error.message}</p>
      </CardContent>
    </Card>
  );
}

export default function AnalyticsSection() {
  const t = useTranslations('teacher.analytics');
  const { data: analytics, isLoading, error } = useQuery({
    queryKey: ['teacher-analytics'],
    queryFn: fetchAnalytics,
    staleTime: 2 * 60 * 1000,
    refetchInterval: 5 * 60 * 1000,
  });

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
      transition: { duration: 0.4, ease: 'easeOut' },
    },
  };

  if (isLoading) {
    return <AnalyticsLoading />;
  }

  if (error || !analytics) {
    return <AnalyticsError error={(error ?? new Error('Unknown error')) as Error} />;
  }

  const studentsPercentage = analytics.totalStudents > 0 ? Math.min((analytics.totalStudents / 50) * 100, 100) : 0;
  const completionPercentage = Math.min(analytics.completionRate, 100);
  const hotspotPercentage = analytics.hotspotInteractions > 0 ? Math.min((analytics.hotspotInteractions / 1000) * 100, 100) : 0;
  const sessionsPercentage = analytics.gameSessions > 0 ? Math.min((analytics.gameSessions / 100) * 100, 100) : 0;

  return (
    <motion.div
      className="space-y-6"
      variants={containerVariants}
      initial="hidden"
      animate="visible"
    >
      <motion.div variants={itemVariants} className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <MetricCard
          title={t('totalStudents', { defaultValue: 'Total Students' })}
          value={analytics.totalStudents}
          percentage={studentsPercentage}
          icon={Users}
          color="bg-emerald-500"
          helper={t('totalStudentsHelper', { defaultValue: 'Across all active classes' })}
        />
        <MetricCard
          title={t('completionRate', { defaultValue: 'Completion Rate' })}
          value={`${analytics.completionRate.toFixed(1)}%`}
          percentage={completionPercentage}
          icon={Target}
          color="bg-blue-500"
          helper={t('completionRateHelper', { defaultValue: 'Assignments completed this week' })}
        />
        <MetricCard
          title={t('hotspotInteractions', { defaultValue: 'Hotspot Interactions' })}
          value={analytics.hotspotInteractions.toLocaleString()}
          percentage={hotspotPercentage}
          icon={Zap}
          color="bg-purple-500"
          helper={t('hotspotHelper', { defaultValue: 'Student engagement with interactive content' })}
        />
        <MetricCard
          title={t('gameSessions', { defaultValue: 'Game Sessions' })}
          value={analytics.gameSessions}
          percentage={sessionsPercentage}
          icon={Award}
          color="bg-amber-500"
          helper={t('gameSessionsHelper', { defaultValue: 'Sessions recorded across learning games' })}
        />
      </motion.div>

      <motion.div variants={itemVariants} className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card className="bg-gradient-to-r from-emerald-500/10 to-teal-500/10 border-emerald-200 dark:border-emerald-800">
          <CardContent className="p-6 flex items-center space-x-3">
            <div className="p-2 bg-emerald-500 rounded-lg">
              <BookOpen className="h-5 w-5 text-white" />
            </div>
            <div>
              <h3 className="text-sm font-medium text-emerald-700 dark:text-emerald-300">
                {t('averageScore', { defaultValue: 'Average Score' })}
              </h3>
              <p className="text-2xl font-bold text-emerald-900 dark:text-emerald-100">
                {analytics.averageScore.toFixed(1)}%
              </p>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gradient-to-r from-blue-500/10 to-indigo-500/10 border-blue-200 dark:border-blue-800">
          <CardContent className="p-6 flex items-center space-x-3">
            <div className="p-2 bg-blue-500 rounded-lg">
              <ClipboardList className="h-5 w-5 text-white" />
            </div>
            <div>
              <h3 className="text-sm font-medium text-blue-700 dark:text-blue-300">
                {t('activeAssignments', { defaultValue: 'Active Assignments' })}
              </h3>
              <p className="text-2xl font-bold text-blue-900 dark:text-blue-100">
                {analytics.activeAssignments}
              </p>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gradient-to-r from-yellow-500/10 to-orange-500/10 border-yellow-200 dark:border-yellow-800">
          <CardContent className="p-6 flex items-center space-x-3">
            <div className="p-2 bg-amber-500 rounded-lg">
              <TrendingUp className="h-5 w-5 text-white" />
            </div>
            <div>
              <h3 className="text-sm font-medium text-amber-700 dark:text-amber-300">
                {t('highScores', { defaultValue: 'High Scores' })}
              </h3>
              <p className="text-2xl font-bold text-amber-900 dark:text-amber-100">
                {analytics.highScores}
              </p>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gradient-to-r from-rose-500/10 to-red-500/10 border-rose-200 dark:border-rose-800">
          <CardContent className="p-6 flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <div className="p-2 bg-rose-500 rounded-lg">
                <Clock className="h-5 w-5 text-white" />
              </div>
              <div>
                <h3 className="text-sm font-medium text-rose-700 dark:text-rose-300">
                  {t('pendingReviews', { defaultValue: 'Pending Reviews' })}
                </h3>
                <p className="text-2xl font-bold text-rose-900 dark:text-rose-100">
                  {analytics.pendingSubmissions}
                </p>
              </div>
            </div>
            {analytics.lastUpdated && (
              <Badge variant="secondary" className="text-xs bg-white/60 dark:bg-gray-900/60">
                {t('updated', { defaultValue: 'Updated' })}{' '}
                {new Date(analytics.lastUpdated).toLocaleTimeString()}
              </Badge>
            )}
          </CardContent>
        </Card>
      </motion.div>
    </motion.div>
  );
}
