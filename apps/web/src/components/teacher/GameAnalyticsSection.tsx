/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useTranslations } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { ScrollArea } from '@/components/ui/scroll-area';
import { 
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { 
  Gamepad2, 
  Trophy, 
  Target, 
  Clock, 
  Users, 
  TrendingUp, 
  TrendingDown, 
  Star, 
  Award, 
  Zap, 
  BarChart3, 
  PieChart, 
  Activity, 
  Calendar, 
  Filter,
  Download,
  RefreshCw
} from 'lucide-react';

// Types for game analytics data
interface GameAnalytics {
  overview: GameOverview;
  topPerformers: TopPerformer[];
  gameStats: GameStats[];
  engagementMetrics: EngagementMetrics;
  progressTrends: ProgressTrend[];
}

interface GameOverview {
  totalSessions: number;
  averageScore: number;
  completionRate: number;
  activeStudents: number;
  totalPlayTime: number;
  highestScore: number;
}

interface TopPerformer {
  id: string;
  name: string;
  avatar?: string;
  score: number;
  gamesPlayed: number;
  averageScore: number;
  rank: number;
  badges: string[];
}

interface GameStats {
  gameType: string;
  sessionsPlayed: number;
  averageScore: number;
  completionRate: number;
  averageTime: number;
  difficulty: 'easy' | 'medium' | 'hard';
  popularity: number;
}

interface EngagementMetrics {
  dailyActive: number;
  weeklyActive: number;
  monthlyActive: number;
  retentionRate: number;
  averageSessionTime: number;
  peakHours: { hour: number; sessions: number }[];
}

interface ProgressTrend {
  date: string;
  sessions: number;
  averageScore: number;
  completions: number;
}

interface GameFilters {
  timeRange: string;
  gameType: string;
  difficulty: string;
  studentGroup: string;
}

/**
 * Fetch game analytics from API
 * @param filters - Filter criteria
 * @returns Promise with game analytics data
 */
const fetchGameAnalytics = async (filters: GameFilters): Promise<GameAnalytics> => {
  const params = new URLSearchParams();
  
  if (filters.timeRange && filters.timeRange !== 'all') params.append('time_range', filters.timeRange);
  if (filters.gameType && filters.gameType !== 'all') params.append('game_type', filters.gameType);
  if (filters.difficulty && filters.difficulty !== 'all') params.append('difficulty', filters.difficulty);
  if (filters.studentGroup && filters.studentGroup !== 'all') params.append('student_group', filters.studentGroup);
  
  const response = await fetch(`/api/teacher/game-analytics?${params.toString()}`, {
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json',
    },
  });
  
  if (!response.ok) {
    throw new Error('Failed to fetch game analytics');
  }
  
  const data = await response.json();
  return data.analytics;
};

/**
 * Get difficulty badge styling
 * @param difficulty - Game difficulty level
 * @returns CSS classes for badge
 */
function getDifficultyBadge(difficulty: string) {
  switch (difficulty) {
    case 'easy':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    case 'medium':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    case 'hard':
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
  }
}

/**
 * Format time duration
 * @param minutes - Time in minutes
 * @returns Formatted time string
 */
function formatTime(minutes: number): string {
  if (minutes < 60) {
    return `${Math.round(minutes)}m`;
  }
  const hours = Math.floor(minutes / 60);
  const remainingMinutes = Math.round(minutes % 60);
  return `${hours}h ${remainingMinutes}m`;
}

/**
 * Overview metrics component
 * @param overview - Overview data
 */
interface OverviewMetricsProps {
  overview: GameOverview;
}

function OverviewMetrics({ overview }: OverviewMetricsProps) {
  const t = useTranslations('teacher.gameAnalytics');
  
  const metrics = [
    {
      label: t('metrics.totalSessions', { defaultValue: 'Total Sessions' }),
      value: overview.totalSessions.toLocaleString(),
      icon: Gamepad2,
      color: 'text-blue-600 dark:text-blue-400',
      bgColor: 'bg-blue-50 dark:bg-blue-900/20',
    },
    {
      label: t('metrics.averageScore', { defaultValue: 'Average Score' }),
      value: `${Math.round(overview.averageScore)}%`,
      icon: Target,
      color: 'text-emerald-600 dark:text-emerald-400',
      bgColor: 'bg-emerald-50 dark:bg-emerald-900/20',
    },
    {
      label: t('metrics.completionRate', { defaultValue: 'Completion Rate' }),
      value: `${Math.round(overview.completionRate)}%`,
      icon: Trophy,
      color: 'text-yellow-600 dark:text-yellow-400',
      bgColor: 'bg-yellow-50 dark:bg-yellow-900/20',
    },
    {
      label: t('metrics.activeStudents', { defaultValue: 'Active Students' }),
      value: overview.activeStudents.toString(),
      icon: Users,
      color: 'text-purple-600 dark:text-purple-400',
      bgColor: 'bg-purple-50 dark:bg-purple-900/20',
    },
    {
      label: t('metrics.totalPlayTime', { defaultValue: 'Total Play Time' }),
      value: formatTime(overview.totalPlayTime),
      icon: Clock,
      color: 'text-indigo-600 dark:text-indigo-400',
      bgColor: 'bg-indigo-50 dark:bg-indigo-900/20',
    },
    {
      label: t('metrics.highestScore', { defaultValue: 'Highest Score' }),
      value: `${overview.highestScore}%`,
      icon: Star,
      color: 'text-orange-600 dark:text-orange-400',
      bgColor: 'bg-orange-50 dark:bg-orange-900/20',
    },
  ];
  
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      {metrics.map((metric, index) => {
        const Icon = metric.icon;
        return (
          <motion.div
            key={metric.label}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: index * 0.1 }}
          >
            <Card className="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                      {metric.label}
                    </p>
                    <p className="text-2xl font-bold text-gray-900 dark:text-white">
                      {metric.value}
                    </p>
                  </div>
                  <div className={`p-3 rounded-lg ${metric.bgColor}`}>
                    <Icon className={`h-6 w-6 ${metric.color}`} />
                  </div>
                </div>
              </CardContent>
            </Card>
          </motion.div>
        );
      })}
    </div>
  );
}

/**
 * Top performers leaderboard component
 * @param performers - Top performer data
 */
interface TopPerformersProps {
  performers: TopPerformer[];
}

function TopPerformers({ performers }: TopPerformersProps) {
  const t = useTranslations('teacher.gameAnalytics');
  
  return (
    <Card className="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-gray-200 dark:border-gray-700">
      <CardHeader>
        <CardTitle className="flex items-center text-gray-900 dark:text-white">
          <Trophy className="h-5 w-5 mr-2 text-yellow-500" />
          {t('topPerformers.title', { defaultValue: 'Top Performers' })}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <ScrollArea className="h-80">
          <div className="space-y-3">
            {performers.map((performer, index) => (
              <motion.div
                key={performer.id}
                initial={{ opacity: 0, x: -20 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: index * 0.1 }}
                className="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              >
                <div className="flex items-center space-x-3">
                  <div className="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 text-white font-bold text-sm">
                    {performer.rank}
                  </div>
                  
                  <div className="flex-1">
                    <p className="font-medium text-gray-900 dark:text-white">
                      {performer.name}
                    </p>
                    <div className="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                      <span>{performer.gamesPlayed} games</span>
                      <span>Avg: {Math.round(performer.averageScore)}%</span>
                    </div>
                  </div>
                </div>
                
                <div className="text-right">
                  <div className="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                    {performer.score}%
                  </div>
                  <div className="flex items-center space-x-1">
                    {performer.badges.slice(0, 3).map((badge, badgeIndex) => (
                      <Award key={badgeIndex} className="h-4 w-4 text-yellow-500" />
                    ))}
                    {performer.badges.length > 3 && (
                      <span className="text-xs text-gray-500">+{performer.badges.length - 3}</span>
                    )}
                  </div>
                </div>
              </motion.div>
            ))}
          </div>
        </ScrollArea>
      </CardContent>
    </Card>
  );
}

/**
 * Game statistics component
 * @param gameStats - Game statistics data
 */
interface GameStatsProps {
  gameStats: GameStats[];
}

function GameStatsGrid({ gameStats }: GameStatsProps) {
  const t = useTranslations('teacher.gameAnalytics');
  
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
      {gameStats.map((game, index) => (
        <motion.div
          key={game.gameType}
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: index * 0.1 }}
        >
          <Card className="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <CardTitle className="text-lg font-semibold text-gray-900 dark:text-white capitalize">
                  {game.gameType.replace('_', ' ')}
                </CardTitle>
                <Badge className={getDifficultyBadge(game.difficulty)}>
                  {t(`difficulty.${game.difficulty}`, { defaultValue: game.difficulty })}
                </Badge>
              </div>
            </CardHeader>
            
            <CardContent>
              <div className="grid grid-cols-2 gap-4">
                <div className="text-center">
                  <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {game.sessionsPlayed}
                  </div>
                  <div className="text-xs text-gray-600 dark:text-gray-400">
                    {t('gameStats.sessions', { defaultValue: 'Sessions' })}
                  </div>
                </div>
                
                <div className="text-center">
                  <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                    {Math.round(game.averageScore)}%
                  </div>
                  <div className="text-xs text-gray-600 dark:text-gray-400">
                    {t('gameStats.avgScore', { defaultValue: 'Avg Score' })}
                  </div>
                </div>
                
                <div className="text-center">
                  <div className="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                    {Math.round(game.completionRate)}%
                  </div>
                  <div className="text-xs text-gray-600 dark:text-gray-400">
                    {t('gameStats.completion', { defaultValue: 'Completion' })}
                  </div>
                </div>
                
                <div className="text-center">
                  <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    {formatTime(game.averageTime)}
                  </div>
                  <div className="text-xs text-gray-600 dark:text-gray-400">
                    {t('gameStats.avgTime', { defaultValue: 'Avg Time' })}
                  </div>
                </div>
              </div>
              
              {/* Popularity Bar */}
              <div className="mt-4">
                <div className="flex items-center justify-between text-sm mb-1">
                  <span className="text-gray-600 dark:text-gray-400">
                    {t('gameStats.popularity', { defaultValue: 'Popularity' })}
                  </span>
                  <span className="text-gray-900 dark:text-white font-medium">
                    {Math.round(game.popularity)}%
                  </span>
                </div>
                <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                  <motion.div
                    className="bg-gradient-to-r from-emerald-500 to-blue-500 h-2 rounded-full"
                    initial={{ width: 0 }}
                    animate={{ width: `${game.popularity}%` }}
                    transition={{ duration: 1, delay: index * 0.2 }}
                  />
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      ))}
    </div>
  );
}

/**
 * Engagement metrics component
 * @param metrics - Engagement metrics data
 */
interface EngagementMetricsProps {
  metrics: EngagementMetrics;
}

function EngagementMetrics({ metrics }: EngagementMetricsProps) {
  const t = useTranslations('teacher.gameAnalytics');
  
  return (
    <Card className="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-gray-200 dark:border-gray-700">
      <CardHeader>
        <CardTitle className="flex items-center text-gray-900 dark:text-white">
          <Activity className="h-5 w-5 mr-2 text-emerald-500" />
          {t('engagement.title', { defaultValue: 'Engagement Metrics' })}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div className="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
              {metrics.dailyActive}
            </div>
            <div className="text-sm text-blue-700 dark:text-blue-300">
              {t('engagement.daily', { defaultValue: 'Daily Active' })}
            </div>
          </div>
          
          <div className="text-center p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
            <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {metrics.weeklyActive}
            </div>
            <div className="text-sm text-emerald-700 dark:text-emerald-300">
              {t('engagement.weekly', { defaultValue: 'Weekly Active' })}
            </div>
          </div>
          
          <div className="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">
              {metrics.monthlyActive}
            </div>
            <div className="text-sm text-purple-700 dark:text-purple-300">
              {t('engagement.monthly', { defaultValue: 'Monthly Active' })}
            </div>
          </div>
          
          <div className="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <div className="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
              {Math.round(metrics.retentionRate)}%
            </div>
            <div className="text-sm text-yellow-700 dark:text-yellow-300">
              {t('engagement.retention', { defaultValue: 'Retention' })}
            </div>
          </div>
        </div>
        
        {/* Peak Hours Chart */}
        <div>
          <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            {t('engagement.peakHours', { defaultValue: 'Peak Activity Hours' })}
          </h4>
          <div className="flex items-end space-x-1 h-32">
            {metrics.peakHours.map((hour, index) => {
              const maxSessions = Math.max(...metrics.peakHours.map(h => h.sessions));
              const height = (hour.sessions / maxSessions) * 100;
              
              return (
                <div key={hour.hour} className="flex-1 flex flex-col items-center">
                  <motion.div
                    className="w-full bg-gradient-to-t from-emerald-500 to-blue-500 rounded-t"
                    initial={{ height: 0 }}
                    animate={{ height: `${height}%` }}
                    transition={{ duration: 1, delay: index * 0.05 }}
                  />
                  <div className="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    {hour.hour}:00
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

/**
 * Loading skeleton for game analytics
 */
function GameAnalyticsLoading() {
  return (
    <div className="space-y-6">
      {/* Overview Metrics Skeleton */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {Array.from({ length: 6 }).map((_, index) => (
          <Card key={index} className="bg-white/70 dark:bg-gray-800/70">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div className="flex-1">
                  <Skeleton className="h-4 w-24 mb-2" />
                  <Skeleton className="h-8 w-16" />
                </div>
                <Skeleton className="h-12 w-12 rounded-lg" />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
      
      {/* Content Skeleton */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card className="bg-white/70 dark:bg-gray-800/70">
          <CardHeader>
            <Skeleton className="h-6 w-32" />
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {Array.from({ length: 5 }).map((_, index) => (
                <div key={index} className="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                  <div className="flex items-center space-x-3">
                    <Skeleton className="h-8 w-8 rounded-full" />
                    <div>
                      <Skeleton className="h-4 w-24 mb-1" />
                      <Skeleton className="h-3 w-32" />
                    </div>
                  </div>
                  <Skeleton className="h-6 w-12" />
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
        
        <Card className="bg-white/70 dark:bg-gray-800/70">
          <CardHeader>
            <Skeleton className="h-6 w-40" />
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-2 gap-4 mb-6">
              {Array.from({ length: 4 }).map((_, index) => (
                <div key={index} className="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                  <Skeleton className="h-8 w-12 mx-auto mb-2" />
                  <Skeleton className="h-3 w-16 mx-auto" />
                </div>
              ))}
            </div>
            <Skeleton className="h-32 w-full" />
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

/**
 * Empty state component
 */
function EmptyGameAnalytics() {
  const t = useTranslations('teacher.gameAnalytics');
  
  return (
    <div className="text-center py-12">
      <div className="text-gray-400 dark:text-gray-600 mb-4">
        <Gamepad2 className="h-16 w-16 mx-auto" />
      </div>
      <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
        {t('empty.title', { defaultValue: 'No Game Data Yet' })}
      </h3>
      <p className="text-gray-600 dark:text-gray-400 max-w-md mx-auto">
        {t('empty.description', { 
          defaultValue: 'Game analytics will appear here once students start playing educational games.' 
        })}
      </p>
    </div>
  );
}

/**
 * Main Game Analytics Section Component
 * Displays game performance metrics and student engagement data
 */
export default function GameAnalyticsSection() {
  const t = useTranslations('teacher.gameAnalytics');
  
  const [filters, setFilters] = useState<GameFilters>({
    timeRange: 'week',
    gameType: 'all',
    difficulty: 'all',
    studentGroup: 'all',
  });

  const { data: analytics, isLoading, error, refetch } = useQuery({
    queryKey: ['teacher-game-analytics', filters],
    queryFn: () => fetchGameAnalytics(filters),
    refetchInterval: 5 * 60 * 1000, // Refetch every 5 minutes
  });

  const updateFilter = (key: keyof GameFilters, value: string) => {
    setFilters(prev => ({ ...prev, [key]: value }));
  };

  if (isLoading) {
    return <GameAnalyticsLoading />;
  }

  if (error) {
    return (
      <Card className="bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
        <CardContent className="p-6 text-center">
          <div className="text-red-600 dark:text-red-400 mb-2">
            <Gamepad2 className="h-8 w-8 mx-auto" />
          </div>
          <h3 className="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
            {t('error.title', { defaultValue: 'Failed to Load Game Analytics' })}
          </h3>
          <p className="text-red-600 dark:text-red-400 text-sm mb-4">
            {(error as Error).message}
          </p>
          <Button 
            onClick={() => refetch()} 
            variant="outline" 
            size="sm"
            className="border-red-300 text-red-700 hover:bg-red-50"
          >
            <RefreshCw className="h-4 w-4 mr-2" />
            {t('error.retry', { defaultValue: 'Try Again' })}
          </Button>
        </CardContent>
      </Card>
    );
  }

  if (!analytics || analytics.overview.totalSessions === 0) {
    return <EmptyGameAnalytics />;
  }

  return (
    <div className="space-y-6">
      {/* Header & Filters */}
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('title', { defaultValue: 'Game Analytics' })}
          </h3>
          <p className="text-sm text-gray-600 dark:text-gray-400">
            {t('subtitle', { 
              defaultValue: `${analytics.overview.totalSessions} total game sessions` 
            })}
          </p>
        </div>
        
        <div className="flex items-center space-x-2">
          <Button 
            onClick={() => refetch()} 
            variant="outline" 
            size="sm"
          >
            <RefreshCw className="h-4 w-4 mr-2" />
            {t('refresh', { defaultValue: 'Refresh' })}
          </Button>
          
          <Button variant="outline" size="sm">
            <Download className="h-4 w-4 mr-2" />
            {t('export', { defaultValue: 'Export' })}
          </Button>
        </div>
      </div>
      
      {/* Filter Controls */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Select value={filters.timeRange} onValueChange={(value) => updateFilter('timeRange', value)}>
          <SelectTrigger>
            <SelectValue placeholder={t('filters.timeRange', { defaultValue: 'Time Range' })} />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="day">{t('timeRange.day', { defaultValue: 'Today' })}</SelectItem>
            <SelectItem value="week">{t('timeRange.week', { defaultValue: 'This Week' })}</SelectItem>
            <SelectItem value="month">{t('timeRange.month', { defaultValue: 'This Month' })}</SelectItem>
            <SelectItem value="quarter">{t('timeRange.quarter', { defaultValue: 'This Quarter' })}</SelectItem>
          </SelectContent>
        </Select>
        
        <Select value={filters.gameType} onValueChange={(value) => updateFilter('gameType', value)}>
          <SelectTrigger>
            <SelectValue placeholder={t('filters.gameType', { defaultValue: 'Game Type' })} />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">{t('gameType.all', { defaultValue: 'All Games' })}</SelectItem>
            <SelectItem value="memory">{t('gameType.memory', { defaultValue: 'Memory Games' })}</SelectItem>
            <SelectItem value="recitation">{t('gameType.recitation', { defaultValue: 'Recitation Games' })}</SelectItem>
            <SelectItem value="quiz">{t('gameType.quiz', { defaultValue: 'Quiz Games' })}</SelectItem>
          </SelectContent>
        </Select>
        
        <Select value={filters.difficulty} onValueChange={(value) => updateFilter('difficulty', value)}>
          <SelectTrigger>
            <SelectValue placeholder={t('filters.difficulty', { defaultValue: 'Difficulty' })} />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">{t('difficulty.all', { defaultValue: 'All Levels' })}</SelectItem>
            <SelectItem value="easy">{t('difficulty.easy', { defaultValue: 'Easy' })}</SelectItem>
            <SelectItem value="medium">{t('difficulty.medium', { defaultValue: 'Medium' })}</SelectItem>
            <SelectItem value="hard">{t('difficulty.hard', { defaultValue: 'Hard' })}</SelectItem>
          </SelectContent>
        </Select>
        
        <Select value={filters.studentGroup} onValueChange={(value) => updateFilter('studentGroup', value)}>
          <SelectTrigger>
            <SelectValue placeholder={t('filters.studentGroup', { defaultValue: 'Student Group' })} />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">{t('studentGroup.all', { defaultValue: 'All Students' })}</SelectItem>
            {/* Add dynamic class options here */}
          </SelectContent>
        </Select>
      </div>

      {/* Overview Metrics */}
      <OverviewMetrics overview={analytics.overview} />

      {/* Main Content Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Top Performers */}
        <TopPerformers performers={analytics.topPerformers} />
        
        {/* Engagement Metrics */}
        <EngagementMetrics metrics={analytics.engagementMetrics} />
      </div>

      {/* Game Statistics */}
      <div>
        <h4 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          {t('gameStats.title', { defaultValue: 'Game Performance' })}
        </h4>
        <GameStatsGrid gameStats={analytics.gameStats} />
      </div>
    </div>
  );
}