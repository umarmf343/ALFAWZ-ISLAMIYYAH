'use client';

import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { motion } from 'framer-motion';
import { useTranslations } from 'next-intl';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { api } from '@/lib/api';
import { Gamepad2, Target, Trophy, Users, BarChart3 } from 'lucide-react';

interface RawGameAnalytics {
  total_sessions?: number;
  average_session_duration?: string;
  most_popular_game?: string;
  top_performers?: { name: string; score: number; level: string }[];
  weekly_activity?: { day: string; sessions: number }[];
  game_types?: { name: string; sessions: number; avg_score: number }[];
}

interface GameAnalyticsSummary {
  totalSessions: number;
  averageSessionDuration: string;
  mostPopularGame: string;
  topPerformers: { name: string; score: number; level: string }[];
  weeklyActivity: { day: string; sessions: number }[];
  gameTypes: { name: string; sessions: number; avgScore: number }[];
}

const fetchGameAnalytics = async (): Promise<GameAnalyticsSummary> => {
  const response = await api.get('/teacher/game-analytics');
  const payload = response as RawGameAnalytics;

  return {
    totalSessions: Number(payload.total_sessions ?? 0),
    averageSessionDuration: payload.average_session_duration ?? '0 minutes',
    mostPopularGame: payload.most_popular_game ?? 'N/A',
    topPerformers: Array.isArray(payload.top_performers) ? payload.top_performers : [],
    weeklyActivity: Array.isArray(payload.weekly_activity) ? payload.weekly_activity : [],
    gameTypes: Array.isArray(payload.game_types)
      ? payload.game_types.map((type) => ({
          name: type.name,
          sessions: Number(type.sessions ?? 0),
          avgScore: Number(type.avg_score ?? 0),
        }))
      : [],
  };
};

function GameAnalyticsLoading() {
  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
      {Array.from({ length: 3 }).map((_, index) => (
        <Card key={index} className="bg-white/70 dark:bg-gray-800/70 border-gray-200 dark:border-gray-700">
          <CardContent className="p-6 space-y-4">
            <Skeleton className="h-6 w-1/2" />
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-4 w-3/4" />
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

function WeeklyActivityList({ activity }: { activity: { day: string; sessions: number }[] }) {
  return (
    <div className="space-y-2">
      {activity.map((entry) => (
        <div key={entry.day} className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
          <span>{entry.day}</span>
          <Badge variant="outline" className="bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-200">
            {entry.sessions} sessions
          </Badge>
        </div>
      ))}
    </div>
  );
}

function GameTypeList({ gameTypes }: { gameTypes: { name: string; sessions: number; avgScore: number }[] }) {
  return (
    <div className="space-y-3">
      {gameTypes.map((game) => (
        <div key={game.name} className="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-gray-900 dark:text-white">{game.name}</p>
            <p className="text-xs text-gray-500 dark:text-gray-400">{game.sessions} sessions</p>
          </div>
          <Badge className="bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200">
            {Math.round(game.avgScore)}%
          </Badge>
        </div>
      ))}
    </div>
  );
}

export default function GameAnalyticsSection() {
  const t = useTranslations('teacher.gameAnalytics');
  const { data, isLoading, error } = useQuery({
    queryKey: ['teacher-game-analytics'],
    queryFn: fetchGameAnalytics,
    staleTime: 5 * 60 * 1000,
  });

  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1,
        delayChildren: 0.1,
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
    return <GameAnalyticsLoading />;
  }

  if (error || !data) {
    return (
      <Card className="bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
        <CardContent className="p-6 text-center">
          <div className="text-red-600 dark:text-red-400 mb-2">
            <BarChart3 className="h-8 w-8 mx-auto" />
          </div>
          <h3 className="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
            {t('error.title', { defaultValue: 'Unable to load game analytics' })}
          </h3>
          <p className="text-sm text-red-600 dark:text-red-400">{(error as Error)?.message ?? 'Unknown error'}</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <motion.div variants={containerVariants} initial="hidden" animate="visible" className="space-y-6">
      <motion.div variants={itemVariants} className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card className="bg-white/70 dark:bg-gray-800/70 border-gray-200 dark:border-gray-700">
          <CardHeader className="pb-2">
            <CardTitle className="flex items-center text-sm font-medium text-gray-900 dark:text-white">
              <Gamepad2 className="h-4 w-4 mr-2 text-emerald-600" />
              {t('summary.sessions', { defaultValue: 'Total Sessions' })}
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-0">
            <p className="text-2xl font-bold text-gray-900 dark:text-white">{data.totalSessions.toLocaleString()}</p>
            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
              {t('summary.sessionsHelper', { defaultValue: 'Sessions recorded this month' })}
            </p>
          </CardContent>
        </Card>

        <Card className="bg-white/70 dark:bg-gray-800/70 border-gray-200 dark:border-gray-700">
          <CardHeader className="pb-2">
            <CardTitle className="flex items-center text-sm font-medium text-gray-900 dark:text-white">
              <Target className="h-4 w-4 mr-2 text-blue-600" />
              {t('summary.duration', { defaultValue: 'Average Session Duration' })}
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-0">
            <p className="text-2xl font-bold text-gray-900 dark:text-white">{data.averageSessionDuration}</p>
            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
              {t('summary.durationHelper', { defaultValue: 'Across all games' })}
            </p>
          </CardContent>
        </Card>

        <Card className="bg-white/70 dark:bg-gray-800/70 border-gray-200 dark:border-gray-700">
          <CardHeader className="pb-2">
            <CardTitle className="flex items-center text-sm font-medium text-gray-900 dark:text-white">
              <Trophy className="h-4 w-4 mr-2 text-amber-600" />
              {t('summary.popularGame', { defaultValue: 'Most Popular Game' })}
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-0">
            <p className="text-lg font-semibold text-gray-900 dark:text-white">{data.mostPopularGame}</p>
            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
              {t('summary.popularGameHelper', { defaultValue: 'Highest engagement this week' })}
            </p>
          </CardContent>
        </Card>
      </motion.div>

      <motion.div variants={itemVariants} className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card className="bg-white/70 dark:bg-gray-800/70 border-gray-200 dark:border-gray-700">
          <CardHeader>
            <CardTitle className="text-sm font-semibold text-gray-900 dark:text-white">
              {t('topPerformers.title', { defaultValue: 'Top Performers' })}
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {data.topPerformers.map((performer) => (
              <div
                key={performer.name}
                className="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60"
              >
                <div>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">{performer.name}</p>
                  <p className="text-xs text-gray-500 dark:text-gray-400">{performer.level}</p>
                </div>
                <Badge className="bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200">
                  {performer.score}
                </Badge>
              </div>
            ))}
            {data.topPerformers.length === 0 && (
              <p className="text-sm text-gray-500 dark:text-gray-400">
                {t('topPerformers.empty', { defaultValue: 'No game data available yet.' })}
              </p>
            )}
          </CardContent>
        </Card>

        <Card className="bg-white/70 dark:bg-gray-800/70 border-gray-200 dark:border-gray-700">
          <CardHeader>
            <CardTitle className="text-sm font-semibold text-gray-900 dark:text-white">
              {t('weeklyActivity.title', { defaultValue: 'Weekly Activity' })}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <WeeklyActivityList activity={data.weeklyActivity} />
          </CardContent>
        </Card>
      </motion.div>

      <motion.div variants={itemVariants}>
        <Card className="bg-white/70 dark:bg-gray-800/70 border-gray-200 dark:border-gray-700">
          <CardHeader>
            <CardTitle className="flex items-center text-sm font-semibold text-gray-900 dark:text-white">
              <Users className="h-4 w-4 mr-2 text-purple-600" />
              {t('gameTypes.title', { defaultValue: 'Game Types Overview' })}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <GameTypeList gameTypes={data.gameTypes} />
          </CardContent>
        </Card>
      </motion.div>
    </motion.div>
  );
}
