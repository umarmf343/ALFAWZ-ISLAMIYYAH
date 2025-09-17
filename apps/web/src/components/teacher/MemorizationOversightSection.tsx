/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Brain,
  Clock,
  TrendingUp,
  User,
  BookOpen,
  CheckCircle,
  AlertCircle,
  Calendar,
} from 'lucide-react';
import { useTranslations } from 'next-intl';
import { api } from '@/lib/api';

interface MemorizationProgress {
  id: number;
  student_name: string;
  student_id: number;
  plan_title: string;
  total_ayahs: number;
  memorized_ayahs: number;
  due_reviews: number;
  last_activity: string;
  confidence_avg: number;
  streak_days: number;
  hasanat_earned: number;
}

interface StudentMemorizationStats {
  student_id: number;
  student_name: string;
  active_plans: number;
  total_memorized: number;
  weekly_reviews: number;
  avg_confidence: number;
  last_seen: string;
}

/**
 * Fetch memorization progress data for teacher oversight
 * @param classId Optional class filter
 * @returns Promise with memorization progress data
 */
const fetchMemorizationProgress = async (classId?: string): Promise<MemorizationProgress[]> => {
  const params = classId ? `?class_id=${classId}` : '';
  const response = await api.get(`/teacher/memorization-progress${params}`);
  const payload = (response?.data ?? response) as unknown;
  if (Array.isArray(payload)) {
    return payload as MemorizationProgress[];
  }
  if (Array.isArray((payload as { data?: MemorizationProgress[] }).data)) {
    return (payload as { data: MemorizationProgress[] }).data;
  }
  return [];
};

/**
 * Fetch student memorization statistics
 * @param classId Optional class filter
 * @returns Promise with student stats
 */
const fetchStudentStats = async (classId?: string): Promise<StudentMemorizationStats[]> => {
  const params = classId ? `?class_id=${classId}` : '';
  const response = await api.get(`/teacher/memorization-stats${params}`);
  const payload = (response?.data ?? response) as unknown;
  if (Array.isArray(payload)) {
    return payload as StudentMemorizationStats[];
  }
  if (Array.isArray((payload as { data?: StudentMemorizationStats[] }).data)) {
    return (payload as { data: StudentMemorizationStats[] }).data;
  }
  return [];
};

/**
 * Teacher Memorization Oversight Section Component
 * Displays student memorization progress, due reviews, and performance metrics
 */
export default function MemorizationOversightSection() {
  const t = useTranslations('teacher.memorization');
  const [selectedClass, setSelectedClass] = useState<string>('all');
  const [viewMode, setViewMode] = useState<'progress' | 'stats'>('progress');

  // Fetch memorization progress data
  const { data: progressData, isLoading: progressLoading } = useQuery({
    queryKey: ['memorization-progress', selectedClass],
    queryFn: () => fetchMemorizationProgress(selectedClass === 'all' ? undefined : selectedClass),
    refetchInterval: 30000, // Refresh every 30 seconds
  });

  // Fetch student statistics
  const { data: statsData, isLoading: statsLoading } = useQuery({
    queryKey: ['memorization-stats', selectedClass],
    queryFn: () => fetchStudentStats(selectedClass === 'all' ? undefined : selectedClass),
    refetchInterval: 60000, // Refresh every minute
  });

  /**
   * Get confidence level badge variant based on score
   * @param confidence Confidence score (0-1)
   * @returns Badge variant string
   */
  const getConfidenceBadge = (confidence: number) => {
    if (confidence >= 0.8) return { variant: 'default' as const, color: 'bg-green-100 text-green-800' };
    if (confidence >= 0.6) return { variant: 'secondary' as const, color: 'bg-yellow-100 text-yellow-800' };
    return { variant: 'destructive' as const, color: 'bg-red-100 text-red-800' };
  };

  /**
   * Format last activity timestamp
   * @param timestamp ISO timestamp string
   * @returns Formatted relative time
   */
  const formatLastActivity = (timestamp: string) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diffHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));
    
    if (diffHours < 1) return t('justNow', { defaultValue: 'Just now' });
    if (diffHours < 24) {
      return t('hoursAgo', { defaultValue: '{{hours}}h ago', hours: diffHours });
    }
    const diffDays = Math.floor(diffHours / 24);
    return t('daysAgo', { defaultValue: '{{days}}d ago', days: diffDays });
  };

  if (progressLoading || statsLoading) {
    return (
      <div className="space-y-4">
        <div className="animate-pulse">
          <div className="h-8 bg-gray-200 rounded w-1/3 mb-4"></div>
          <div className="space-y-3">
            {[...Array(3)].map((_, i) => (
              <div key={i} className="h-16 bg-gray-200 rounded"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Controls */}
      <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <div className="flex items-center gap-4">
          <Select value={selectedClass} onValueChange={setSelectedClass}>
            <SelectTrigger className="w-48">
              <SelectValue placeholder={t('memorization.selectClass', { defaultValue: 'Select Class' })} />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">{t('memorization.allClasses', { defaultValue: 'All Classes' })}</SelectItem>
              <SelectItem value="1">{t('memorization.class1', { defaultValue: 'Class 1' })}</SelectItem>
              <SelectItem value="2">{t('memorization.class2', { defaultValue: 'Class 2' })}</SelectItem>
              <SelectItem value="3">{t('memorization.class3', { defaultValue: 'Class 3' })}</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div className="flex gap-2">
          <Button
            variant={viewMode === 'progress' ? 'default' : 'outline'}
            size="sm"
            onClick={() => setViewMode('progress')}
          >
            <BookOpen className="h-4 w-4 mr-2" />
            {t('memorization.progress', { defaultValue: 'Progress' })}
          </Button>
          <Button
            variant={viewMode === 'stats' ? 'default' : 'outline'}
            size="sm"
            onClick={() => setViewMode('stats')}
          >
            <TrendingUp className="h-4 w-4 mr-2" />
            {t('memorization.statistics', { defaultValue: 'Statistics' })}
          </Button>
        </div>
      </div>

      {/* Progress View */}
      {viewMode === 'progress' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3 }}
        >
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Brain className="h-5 w-5 mr-2 text-purple-600" />
                {t('memorization.studentProgress', { defaultValue: 'Student Memorization Progress' })}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>{t('memorization.student', { defaultValue: 'Student' })}</TableHead>
                    <TableHead>{t('memorization.plan', { defaultValue: 'Plan' })}</TableHead>
                    <TableHead>{t('memorization.memorized', { defaultValue: 'Memorized' })}</TableHead>
                    <TableHead>{t('memorization.dueReviews', { defaultValue: 'Due Reviews' })}</TableHead>
                    <TableHead>{t('memorization.confidence', { defaultValue: 'Confidence' })}</TableHead>
                    <TableHead>{t('memorization.lastActivity', { defaultValue: 'Last Activity' })}</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {progressData?.map((progress) => {
                    const confidenceBadge = getConfidenceBadge(progress.confidence_avg);
                    return (
                      <TableRow key={progress.id}>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <User className="h-4 w-4 text-gray-500" />
                            <span className="font-medium">{progress.student_name}</span>
                          </div>
                        </TableCell>
                        <TableCell>
                          <span className="text-sm text-gray-600">{progress.plan_title}</span>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <span className="font-medium">
                              {progress.memorized_ayahs}/{progress.total_ayahs}
                            </span>
                            <div className="w-16 bg-gray-200 rounded-full h-2">
                              <div
                                className="bg-green-500 h-2 rounded-full"
                                style={{
                                  width: `${(progress.memorized_ayahs / progress.total_ayahs) * 100}%`
                                }}
                              />
                            </div>
                          </div>
                        </TableCell>
                        <TableCell>
                          {progress.due_reviews > 0 ? (
                            <Badge variant="destructive" className="flex items-center gap-1 w-fit">
                              <AlertCircle className="h-3 w-3" />
                              {progress.due_reviews}
                            </Badge>
                          ) : (
                            <Badge variant="default" className="flex items-center gap-1 w-fit bg-green-100 text-green-800">
                              <CheckCircle className="h-3 w-3" />
                              {t('memorization.upToDate', { defaultValue: 'Up to date' })}
                            </Badge>
                          )}
                        </TableCell>
                        <TableCell>
                          <Badge className={confidenceBadge.color}>
                            {Math.round(progress.confidence_avg * 100)}%
                          </Badge>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-1 text-sm text-gray-500">
                            <Clock className="h-3 w-3" />
                            {formatLastActivity(progress.last_activity)}
                          </div>
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </motion.div>
      )}

      {/* Statistics View */}
      {viewMode === 'stats' && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3 }}
          className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
        >
          {statsData?.map((student) => (
            <Card key={student.student_id} className="hover:shadow-lg transition-shadow">
              <CardHeader className="pb-3">
                <CardTitle className="flex items-center text-lg">
                  <User className="h-5 w-5 mr-2 text-blue-600" />
                  {student.student_name}
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="grid grid-cols-2 gap-3 text-sm">
                  <div className="flex items-center gap-2">
                    <BookOpen className="h-4 w-4 text-purple-600" />
                    <span className="text-gray-600">{t('memorization.activePlans', { defaultValue: 'Active Plans' })}</span>
                  </div>
                  <span className="font-semibold">{student.active_plans}</span>
                  
                  <div className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-green-600" />
                    <span className="text-gray-600">{t('memorization.memorized', { defaultValue: 'Memorized' })}</span>
                  </div>
                  <span className="font-semibold">{student.total_memorized}</span>
                  
                  <div className="flex items-center gap-2">
                    <TrendingUp className="h-4 w-4 text-orange-600" />
                    <span className="text-gray-600">{t('memorization.weeklyReviews', { defaultValue: 'Weekly Reviews' })}</span>
                  </div>
                  <span className="font-semibold">{student.weekly_reviews}</span>
                  
                  <div className="flex items-center gap-2">
                    <Brain className="h-4 w-4 text-indigo-600" />
                    <span className="text-gray-600">{t('memorization.avgConfidence', { defaultValue: 'Avg Confidence' })}</span>
                  </div>
                  <Badge className={getConfidenceBadge(student.avg_confidence).color}>
                    {Math.round(student.avg_confidence * 100)}%
                  </Badge>
                </div>
                
                <div className="pt-2 border-t border-gray-200">
                  <div className="flex items-center gap-2 text-xs text-gray-500">
                    <Calendar className="h-3 w-3" />
                    {t('memorization.lastSeen', { defaultValue: 'Last seen' })}: {formatLastActivity(student.last_seen)}
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </motion.div>
      )}

      {/* Empty State */}
      {((viewMode === 'progress' && (!progressData || progressData.length === 0)) ||
        (viewMode === 'stats' && (!statsData || statsData.length === 0))) && (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Brain className="h-12 w-12 text-gray-400 mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">
              {t('memorization.noData', { defaultValue: 'No memorization data available' })}
            </h3>
            <p className="text-gray-500 text-center max-w-md">
              {t('memorization.noDataDescription', {
                defaultValue: 'Students haven\'t started any memorization plans yet. Encourage them to begin their Qur\'an memorization journey!'
              })}
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  );
}