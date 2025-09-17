/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React from 'react';
import { motion } from 'framer-motion';
import { useTranslations } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
import { CircularProgressbar, buildStyles } from 'react-circular-progressbar';
import 'react-circular-progressbar/dist/styles.css';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { 
  Users, 
  TrendingUp, 
  Award, 
  Clock,
  BookOpen,
  Target,
  Zap,
  Star
} from 'lucide-react';

// Types for analytics data
interface AnalyticsData {
  totalStudents: number;
  completionRate: number;
  hotspotInteractions: number;
  gameSessions: number;
  highScores: number;
  averageScore: number;
  weeklyProgress: number;
  monthlyGrowth: number;
}

/**
 * Fetch teacher analytics data from API
 * @returns Promise with analytics data
 */
const fetchAnalytics = async (): Promise<AnalyticsData> => {
  const response = await fetch('/api/teacher/dashboard', {
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json',
    },
  });
  
  if (!response.ok) {
    throw new Error('Failed to fetch analytics data');
  }
  
  const data = await response.json();
  return data.analytics;
};

/**
 * Individual metric card component with circular progress
 * @param title - Metric title
 * @param value - Current value
 * @param percentage - Progress percentage (0-100)
 * @param icon - Lucide icon component
 * @param color - Theme color for progress bar
 * @param trend - Optional trend indicator
 */
interface MetricCardProps {
  title: string;
  value: number | string;
  percentage: number;
  icon: React.ElementType;
  color: string;
  trend?: {
    value: number;
    isPositive: boolean;
  };
}

function MetricCard({ title, value, percentage, icon: Icon, color, trend }: MetricCardProps) {
  return (
    <motion.div
      whileHover={{ scale: 1.02, y: -2 }}
      whileTap={{ scale: 0.98 }}
      transition={{ duration: 0.2 }}
    >
      <Card className="bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
        <CardContent className="p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center space-x-3">
              <div className={`p-2 rounded-lg bg-gradient-to-r ${color}`}>
                <Icon className="h-5 w-5 text-white" />
              </div>
              <div>
                <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">
                  {title}
                </h3>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                  {value}
                </p>
              </div>
            </div>
            
            {/* Circular Progress */}
            <div className="w-16 h-16">
              <CircularProgressbar
                value={percentage}
                text={`${Math.round(percentage)}%`}
                styles={buildStyles({
                  textSize: '20px',
                  pathColor: color.includes('emerald') ? '#10b981' : 
                           color.includes('blue') ? '#3b82f6' :
                           color.includes('purple') ? '#8b5cf6' :
                           color.includes('amber') ? '#f59e0b' : '#10b981',
                  textColor: '#374151',
                  trailColor: '#e5e7eb',
                  backgroundColor: '#f3f4f6',
                })}
              />
            </div>
          </div>
          
          {/* Trend Indicator */}
          {trend && (
            <div className="flex items-center space-x-2">
              <Badge 
                variant={trend.isPositive ? "default" : "destructive"}
                className={`text-xs ${
                  trend.isPositive 
                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' 
                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                }`}
              >
                {trend.isPositive ? '↗' : '↘'} {Math.abs(trend.value)}%
              </Badge>
              <span className="text-xs text-gray-500 dark:text-gray-400">
                vs last month
              </span>
            </div>
          )}
        </CardContent>
      </Card>
    </motion.div>
  );
}

/**
 * Loading skeleton for analytics cards
 */
function AnalyticsLoading() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      {Array.from({ length: 4 }).map((_, index) => (
        <Card key={index} className="bg-white/50 dark:bg-gray-800/50">
          <CardContent className="p-6">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center space-x-3">
                <Skeleton className="h-9 w-9 rounded-lg" />
                <div>
                  <Skeleton className="h-4 w-20 mb-2" />
                  <Skeleton className="h-6 w-16" />
                </div>
              </div>
              <Skeleton className="h-16 w-16 rounded-full" />
            </div>
            <Skeleton className="h-6 w-24" />
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

/**
 * Error state component
 */
function AnalyticsError({ error }: { error: Error }) {
  return (
    <Card className="bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
      <CardContent className="p-6 text-center">
        <div className="text-red-600 dark:text-red-400 mb-2">
          <TrendingUp className="h-8 w-8 mx-auto" />
        </div>
        <h3 className="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
          Failed to Load Analytics
        </h3>
        <p className="text-red-600 dark:text-red-400 text-sm">
          {error.message}
        </p>
      </CardContent>
    </Card>
  );
}

/**
 * Main Analytics Section Component
 * Displays teacher dashboard analytics with circular progress indicators
 */
export default function AnalyticsSection() {
  const t = useTranslations('teacher.analytics');
  
  const { data: analytics, isLoading, error } = useQuery({
    queryKey: ['teacher-analytics'],
    queryFn: fetchAnalytics,
    refetchInterval: 5 * 60 * 1000, // Refetch every 5 minutes
    staleTime: 2 * 60 * 1000, // Consider data stale after 2 minutes
  });

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

  if (isLoading) {
    return <AnalyticsLoading />;
  }

  if (error) {
    return <AnalyticsError error={error as Error} />;
  }

  if (!analytics) {
    return null;
  }

  // Calculate percentages for progress bars
  const completionPercentage = Math.min(analytics.completionRate, 100);
  const studentsPercentage = Math.min((analytics.totalStudents / 50) * 100, 100); // Assuming max 50 students
  const interactionsPercentage = Math.min((analytics.hotspotInteractions / 1000) * 100, 100);
  const sessionsPercentage = Math.min((analytics.gameSessions / 100) * 100, 100);

  return (
    <motion.div
      variants={containerVariants}
      initial="hidden"
      animate="visible"
      className="space-y-6"
    >
      {/* Main Metrics Grid */}
      <motion.div 
        variants={itemVariants}
        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"
      >
        <MetricCard
          title={t('totalStudents', { defaultValue: 'Total Students' })}
          value={analytics.totalStudents}
          percentage={studentsPercentage}
          icon={Users}
          color="from-emerald-500 to-teal-500"
          trend={{
            value: analytics.monthlyGrowth,
            isPositive: analytics.monthlyGrowth > 0,
          }}
        />
        
        <MetricCard
          title={t('completionRate', { defaultValue: 'Completion Rate' })}
          value={`${analytics.completionRate}%`}
          percentage={completionPercentage}
          icon={Target}
          color="from-blue-500 to-indigo-500"
          trend={{
            value: analytics.weeklyProgress,
            isPositive: analytics.weeklyProgress > 0,
          }}
        />
        
        <MetricCard
          title={t('hotspotInteractions', { defaultValue: 'Hotspot Interactions' })}
          value={analytics.hotspotInteractions.toLocaleString()}
          percentage={interactionsPercentage}
          icon={Zap}
          color="from-purple-500 to-pink-500"
        />
        
        <MetricCard
          title={t('gameSessions', { defaultValue: 'Game Sessions' })}
          value={analytics.gameSessions}
          percentage={sessionsPercentage}
          icon={Award}
          color="from-amber-500 to-orange-500"
        />
      </motion.div>

      {/* Additional Metrics */}
      <motion.div 
        variants={itemVariants}
        className="grid grid-cols-1 md:grid-cols-3 gap-6"
      >
        <Card className="bg-gradient-to-r from-emerald-500/10 to-teal-500/10 border-emerald-200 dark:border-emerald-800">
          <CardContent className="p-6">
            <div className="flex items-center space-x-3">
              <div className="p-2 bg-emerald-500 rounded-lg">
                <Star className="h-5 w-5 text-white" />
              </div>
              <div>
                <h3 className="text-sm font-medium text-emerald-700 dark:text-emerald-300">
                  {t('averageScore', { defaultValue: 'Average Score' })}
                </h3>
                <p className="text-2xl font-bold text-emerald-900 dark:text-emerald-100">
                  {analytics.averageScore}%
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-r from-blue-500/10 to-indigo-500/10 border-blue-200 dark:border-blue-800">
          <CardContent className="p-6">
            <div className="flex items-center space-x-3">
              <div className="p-2 bg-blue-500 rounded-lg">
                <BookOpen className="h-5 w-5 text-white" />
              </div>
              <div>
                <h3 className="text-sm font-medium text-blue-700 dark:text-blue-300">
                  {t('highScores', { defaultValue: 'High Scores' })}
                </h3>
                <p className="text-2xl font-bold text-blue-900 dark:text-blue-100">
                  {analytics.highScores}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-r from-purple-500/10 to-pink-500/10 border-purple-200 dark:border-purple-800">
          <CardContent className="p-6">
            <div className="flex items-center space-x-3">
              <div className="p-2 bg-purple-500 rounded-lg">
                <Clock className="h-5 w-5 text-white" />
              </div>
              <div>
                <h3 className="text-sm font-medium text-purple-700 dark:text-purple-300">
                  {t('weeklyProgress', { defaultValue: 'Weekly Progress' })}
                </h3>
                <p className="text-2xl font-bold text-purple-900 dark:text-purple-100">
                  +{analytics.weeklyProgress}%
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>
    </motion.div>
  );
}