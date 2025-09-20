/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useState, useEffect, useCallback } from 'react';
import { motion } from 'framer-motion';
import { useAuth } from '@/contexts/AuthContext';
import { ApiError, api } from '@/lib/api';
import {
  FaChartLine,
  FaBell,
  FaBookOpen,
  FaStar,
  FaFire,
  FaExclamationTriangle,
  FaCheckCircle,
  FaGraduationCap
} from 'react-icons/fa';

interface StudentProgress {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  total_hasanat: number;
  daily_goal_progress: number;
  current_streak: number;
  last_active: string;
  recent_activity: {
    verses_read_today: number;
    assignments_completed: number;
    attendance_rate: number;
  };
  alerts: {
    type: 'warning' | 'success' | 'info';
    message: string;
    timestamp: string;
  }[];
  performance_trend: 'up' | 'down' | 'stable';
}

interface ClassOverview {
  id: number;
  title: string;
  student_count: number;
  active_students_today: number;
  average_progress: number;
  pending_submissions: number;
}

interface OversightNotification {
  id: string;
  type: string;
  title: string;
  message: string;
  timestamp: string;
  read?: boolean;
}

const getErrorMessage = (caught: unknown, fallback: string) => {
  if (caught instanceof ApiError) {
    return caught.message;
  }

  if (caught instanceof Error) {
    return caught.message;
  }

  return fallback;
};

const isRecord = (value: unknown): value is Record<string, unknown> =>
  typeof value === 'object' && value !== null;

const getStringValue = (record: Record<string, unknown>, keys: string[], fallback = ''): string => {
  for (const key of keys) {
    const value = record[key];
    if (typeof value === 'string') {
      return value;
    }

    if (typeof value === 'number') {
      return value.toString();
    }
  }

  return fallback;
};

const toNotificationArray = (value: unknown): OversightNotification[] => {
  if (Array.isArray(value)) {
    return value
      .filter(isRecord)
      .map((item) => ({
        id: getStringValue(item, ['id']),
        type: getStringValue(item, ['type'], 'info'),
        title: getStringValue(item, ['title'], 'Notification'),
        message: getStringValue(item, ['message']),
        timestamp: getStringValue(item, ['timestamp', 'created_at'], new Date().toISOString()),
        read: Boolean(item.read ?? item.read_at),
      }));
  }

  if (isRecord(value) && Array.isArray(value.notifications)) {
    return toNotificationArray(value.notifications);
  }

  return [];
};

/**
 * Teacher oversight dashboard for tracking student progress and receiving notifications
 */
export default function TeacherOversightPage() {
  const { user, isAuthenticated } = useAuth();
  const [students, setStudents] = useState<StudentProgress[]>([]);
  const [classes, setClasses] = useState<ClassOverview[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedClass, setSelectedClass] = useState<number | null>(null);
  const [notifications, setNotifications] = useState<OversightNotification[]>([]);
  const [error, setError] = useState('');

  /**
   * Fetch teacher oversight data including student progress and notifications
   */
  const fetchOversightData = useCallback(async () => {
    try {
      setLoading(true);
      setError('');

      // Fetch classes overview
      const classesResponse = await api.get<ClassOverview[]>('/teacher/classes-overview');
      setClasses(classesResponse.data ?? []);

      // Fetch student progress data
      const studentsEndpoint = selectedClass
        ? `/teacher/students-progress?class_id=${selectedClass}`
        : '/teacher/students-progress';
      const studentsResponse = await api.get<StudentProgress[]>(studentsEndpoint);
      setStudents(studentsResponse.data ?? []);

      // Fetch notifications
      const notificationsResponse = await api.get<unknown>('/teacher/notifications');
      setNotifications(toNotificationArray(notificationsResponse.data));

    } catch (err: unknown) {
      setError(getErrorMessage(err, 'Failed to fetch oversight data'));
      // Mock data for development
      setClasses([
        {
          id: 1,
          title: 'Beginner Quran Class',
          student_count: 15,
          active_students_today: 12,
          average_progress: 78,
          pending_submissions: 3
        },
        {
          id: 2,
          title: 'Advanced Tajweed',
          student_count: 8,
          active_students_today: 7,
          average_progress: 85,
          pending_submissions: 1
        }
      ]);
      
      setStudents([
        {
          id: 1,
          name: 'Ahmed Hassan',
          email: 'ahmed@example.com',
          total_hasanat: 2450,
          daily_goal_progress: 85,
          current_streak: 7,
          last_active: '2025-01-13T10:30:00Z',
          recent_activity: {
            verses_read_today: 15,
            assignments_completed: 2,
            attendance_rate: 92
          },
          alerts: [
            {
              type: 'success',
              message: 'Completed daily goal for 7 days straight!',
              timestamp: '2025-01-13T09:00:00Z'
            }
          ],
          performance_trend: 'up'
        },
        {
          id: 2,
          name: 'Fatima Ali',
          email: 'fatima@example.com',
          total_hasanat: 1890,
          daily_goal_progress: 45,
          current_streak: 2,
          last_active: '2025-01-12T16:45:00Z',
          recent_activity: {
            verses_read_today: 8,
            assignments_completed: 1,
            attendance_rate: 78
          },
          alerts: [
            {
              type: 'warning',
              message: 'Behind on daily reading goal',
              timestamp: '2025-01-13T08:00:00Z'
            }
          ],
          performance_trend: 'down'
        }
      ]);
      
      setNotifications([
        {
          id: '1',
          type: 'assignment',
          title: 'New submission from Ahmed Hassan',
          message: 'Surah Al-Fatiha recitation assignment',
          timestamp: '2025-01-13T11:30:00Z',
          read: false,
        },
        {
          id: '2',
          type: 'alert',
          title: 'Student needs attention',
          message: 'Fatima Ali has missed 2 consecutive daily goals',
          timestamp: '2025-01-13T09:15:00Z',
          read: false,
        },
      ]);
    } finally {
      setLoading(false);
    }
  }, [selectedClass]);

  /**
   * Get performance trend icon and color
   */
  const getTrendIcon = (trend: string) => {
    switch (trend) {
      case 'up':
        return <FaChartLine className="text-green-500" />;
      case 'down':
        return <FaChartLine className="text-red-500 transform rotate-180" />;
      default:
        return <FaChartLine className="text-gray-500" />;
    }
  };

  /**
   * Get alert icon based on type
   */
  const getAlertIcon = (type: string) => {
    switch (type) {
      case 'success':
        return <FaCheckCircle className="text-green-500" />;
      case 'warning':
        return <FaExclamationTriangle className="text-yellow-500" />;
      default:
        return <FaBell className="text-blue-500" />;
    }
  };

  /**
   * Format time ago
   */
  const formatTimeAgo = (timestamp: string) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));
    
    if (diffInHours < 1) return 'Just now';
    if (diffInHours < 24) return `${diffInHours}h ago`;
    return `${Math.floor(diffInHours / 24)}d ago`;
  };

  useEffect(() => {
    if (isAuthenticated && user?.role === 'teacher') {
      void fetchOversightData();
    }
  }, [fetchOversightData, isAuthenticated, user]);

  if (!isAuthenticated || user?.role !== 'teacher') {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Access Denied</h1>
          <p className="text-gray-600">This page is only accessible to teachers.</p>
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <motion.div 
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          className="mb-8"
        >
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Student Oversight Dashboard</h1>
          <p className="text-gray-600">Monitor student progress and receive important notifications</p>
        </motion.div>

        {/* Error Message */}
        {error && (
          <div className="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {error}
          </div>
        )}

        {/* Classes Overview */}
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="mb-8"
        >
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Classes Overview</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {classes.map((classItem) => (
              <motion.div
                key={classItem.id}
                whileHover={{ scale: 1.02 }}
                className={`bg-white rounded-xl p-6 shadow-sm border-2 cursor-pointer transition-all ${
                  selectedClass === classItem.id 
                    ? 'border-green-500 bg-green-50' 
                    : 'border-gray-200 hover:border-green-300'
                }`}
                onClick={() => setSelectedClass(selectedClass === classItem.id ? null : classItem.id)}
              >
                <div className="flex items-center justify-between mb-4">
                  <h3 className="font-semibold text-gray-900">{classItem.title}</h3>
                  <FaGraduationCap className="text-green-600" />
                </div>
                
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-gray-600">Total Students:</span>
                    <span className="font-medium">{classItem.student_count}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Active Today:</span>
                    <span className="font-medium text-green-600">{classItem.active_students_today}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Avg Progress:</span>
                    <span className="font-medium">{classItem.average_progress}%</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Pending:</span>
                    <span className="font-medium text-orange-600">{classItem.pending_submissions}</span>
                  </div>
                </div>
              </motion.div>
            ))}
          </div>
        </motion.div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Student Progress List */}
          <div className="lg:col-span-2">
            <motion.div 
              initial={{ opacity: 0, x: -20 }}
              animate={{ opacity: 1, x: 0 }}
              className="bg-white rounded-xl shadow-sm"
            >
              <div className="p-6 border-b border-gray-200">
                <h2 className="text-xl font-semibold text-gray-900">Student Progress</h2>
                <p className="text-gray-600 text-sm mt-1">
                  {selectedClass ? 'Filtered by selected class' : 'All students'}
                </p>
              </div>
              
              <div className="divide-y divide-gray-200">
                {students.map((student) => (
                  <motion.div
                    key={student.id}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    className="p-6 hover:bg-gray-50 transition-colors"
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex items-center space-x-4">
                        {/* Avatar */}
                        <div className="w-12 h-12 bg-gradient-to-br from-green-400 to-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                          {student.name.split(' ').map(n => n[0]).join('').toUpperCase()}
                        </div>
                        
                        {/* Student Info */}
                        <div>
                          <h3 className="font-semibold text-gray-900">{student.name}</h3>
                          <p className="text-gray-600 text-sm">{student.email}</p>
                          <div className="flex items-center space-x-4 mt-2 text-sm">
                            <span className="flex items-center space-x-1">
                              <FaStar className="text-yellow-500" />
                              <span>{student.total_hasanat.toLocaleString()}</span>
                            </span>
                            <span className="flex items-center space-x-1">
                              <FaFire className="text-orange-500" />
                              <span>{student.current_streak} days</span>
                            </span>
                            <span className="text-gray-500">
                              {formatTimeAgo(student.last_active)}
                            </span>
                          </div>
                        </div>
                      </div>
                      
                      {/* Performance Trend */}
                      <div className="flex items-center space-x-2">
                        {getTrendIcon(student.performance_trend)}
                        <span className="text-sm font-medium">
                          {student.daily_goal_progress}%
                        </span>
                      </div>
                    </div>
                    
                    {/* Progress Bar */}
                    <div className="mt-4">
                      <div className="flex justify-between text-sm text-gray-600 mb-1">
                        <span>Daily Goal Progress</span>
                        <span>{student.daily_goal_progress}%</span>
                      </div>
                      <div className="w-full bg-gray-200 rounded-full h-2">
                        <motion.div 
                          initial={{ width: 0 }}
                          animate={{ width: `${student.daily_goal_progress}%` }}
                          transition={{ duration: 1, delay: 0.2 }}
                          className={`h-2 rounded-full ${
                            student.daily_goal_progress >= 80 
                              ? 'bg-green-500' 
                              : student.daily_goal_progress >= 50 
                              ? 'bg-yellow-500' 
                              : 'bg-red-500'
                          }`}
                        />
                      </div>
                    </div>
                    
                    {/* Recent Activity */}
                    <div className="mt-4 grid grid-cols-3 gap-4 text-sm">
                      <div className="text-center">
                        <div className="font-semibold text-gray-900">{student.recent_activity.verses_read_today}</div>
                        <div className="text-gray-600">Verses Today</div>
                      </div>
                      <div className="text-center">
                        <div className="font-semibold text-gray-900">{student.recent_activity.assignments_completed}</div>
                        <div className="text-gray-600">Assignments</div>
                      </div>
                      <div className="text-center">
                        <div className="font-semibold text-gray-900">{student.recent_activity.attendance_rate}%</div>
                        <div className="text-gray-600">Attendance</div>
                      </div>
                    </div>
                    
                    {/* Alerts */}
                    {student.alerts.length > 0 && (
                      <div className="mt-4 space-y-2">
                        {student.alerts.map((alert, index) => (
                          <div key={index} className="flex items-center space-x-2 text-sm">
                            {getAlertIcon(alert.type)}
                            <span className="text-gray-700">{alert.message}</span>
                            <span className="text-gray-500 text-xs">
                              {formatTimeAgo(alert.timestamp)}
                            </span>
                          </div>
                        ))}
                      </div>
                    )}
                  </motion.div>
                ))}
              </div>
            </motion.div>
          </div>
          
          {/* Notifications Panel */}
          <div>
            <motion.div 
              initial={{ opacity: 0, x: 20 }}
              animate={{ opacity: 1, x: 0 }}
              className="bg-white rounded-xl shadow-sm"
            >
              <div className="p-6 border-b border-gray-200">
                <div className="flex items-center justify-between">
                  <h2 className="text-xl font-semibold text-gray-900">Notifications</h2>
                  <FaBell className="text-gray-400" />
                </div>
              </div>
              
              <div className="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                {notifications.map((notification) => (
                  <motion.div
                    key={notification.id}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    className={`p-4 hover:bg-gray-50 transition-colors ${
                      !notification.read ? 'bg-blue-50 border-l-4 border-blue-500' : ''
                    }`}
                  >
                    <div className="flex items-start space-x-3">
                      <div className="flex-shrink-0 mt-1">
                        {notification.type === 'assignment' ? (
                          <FaBookOpen className="text-green-500" />
                        ) : (
                          <FaExclamationTriangle className="text-yellow-500" />
                        )}
                      </div>
                      <div className="flex-1">
                        <h4 className="font-medium text-gray-900 text-sm">
                          {notification.title}
                        </h4>
                        <p className="text-gray-600 text-sm mt-1">
                          {notification.message}
                        </p>
                        <p className="text-gray-500 text-xs mt-2">
                          {formatTimeAgo(notification.timestamp)}
                        </p>
                      </div>
                    </div>
                  </motion.div>
                ))}
              </div>
            </motion.div>
          </div>
        </div>
      </div>
    </div>
  );
}