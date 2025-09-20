/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect, useCallback } from 'react';
import AdminLayout from '../../components/admin/AdminLayout';
import type { ComponentType, SVGProps } from 'react';
import {
  ChartBarIcon,
  TrendingUpIcon,
  UsersIcon,
  AcademicCapIcon,
  ClipboardListIcon,
  BookOpenIcon,
  RefreshIcon
} from '@heroicons/react/outline';

interface AnalyticsData {
  overview: {
    total_users: number;
    active_users: number;
    new_users: number;
    total_classes: number;
    total_assignments: number;
    total_submissions: number;
  };
  users: {
    by_role: Record<string, number>;
    by_level: Record<string, number>;
    active_in_period: number;
  };
  classes: {
    total_classes: number;
    classes_by_level: Record<string, number>;
    new_classes: number;
  };
  assignments: {
    total_assignments: number;
    published_assignments: number;
    draft_assignments: number;
    new_assignments: number;
  };
  submissions: {
    total_submissions: number;
    pending_submissions: number;
    graded_submissions: number;
    new_submissions: number;
    average_score: number;
  };
  quran_progress: {
    total_progress_records: number;
    total_hasanat: number;
    average_memorization_confidence: number;
    active_readers: number;
  };
  engagement: {
    daily_active_users: number;
    submission_rate: number;
    class_participation: number;
  };
  generated_at: string;
  period: string;
  scope: string;
}

type OutlineIcon = ComponentType<SVGProps<SVGSVGElement>>;

interface StatCardProps {
  title: string;
  value: string | number;
  change?: string;
  changeType?: 'increase' | 'decrease' | 'neutral';
  icon: OutlineIcon;
  color: string;
}

/**
 * Stat card component for displaying key metrics.
 */
function StatCard({ title, value, change, changeType, icon: Icon, color }: StatCardProps) {
  const getChangeColor = () => {
    switch (changeType) {
      case 'increase': return 'text-green-600';
      case 'decrease': return 'text-red-600';
      default: return 'text-gray-600';
    }
  };

  return (
    <div className="bg-white overflow-hidden shadow rounded-lg">
      <div className="p-5">
        <div className="flex items-center">
          <div className="flex-shrink-0">
            <Icon className={`h-6 w-6 ${color}`} />
          </div>
          <div className="ml-5 w-0 flex-1">
            <dl>
              <dt className="text-sm font-medium text-gray-500 truncate">{title}</dt>
              <dd className="flex items-baseline">
                <div className="text-2xl font-semibold text-gray-900">
                  {typeof value === 'number' ? value.toLocaleString() : value}
                </div>
                {change && (
                  <div className={`ml-2 flex items-baseline text-sm font-semibold ${getChangeColor()}`}>
                    {change}
                  </div>
                )}
              </dd>
            </dl>
          </div>
        </div>
      </div>
    </div>
  );
}

/**
 * Simple bar chart component for displaying data.
 */
interface SimpleBarChartProps {
  data: Record<string, number>;
  title: string;
  color: string;
}

function SimpleBarChart({ data, title, color }: SimpleBarChartProps) {
  const entries = Object.entries(data);
  const maxValue = entries.length > 0 ? Math.max(...entries.map(([, value]) => value)) : 0;

  return (
    <div className="bg-white shadow rounded-lg p-6">
      <h3 className="text-lg font-medium text-gray-900 mb-4">{title}</h3>
      {entries.length === 0 ? (
        <p className="text-sm text-gray-500">No data available for this period.</p>
      ) : (
        <div className="space-y-3">
          {entries.map(([key, value]) => (
            <div key={key} className="flex items-center">
              <div className="w-24 text-sm text-gray-600 capitalize">{key}</div>
              <div className="flex-1 mx-4">
                <div className="bg-gray-200 rounded-full h-4">
                  <div
                    className={`h-4 rounded-full ${color}`}
                    style={{ width: `${maxValue > 0 ? (value / maxValue) * 100 : 0}%` }}
                  ></div>
                </div>
              </div>
              <div className="w-12 text-sm font-medium text-gray-900 text-right">
                {value.toLocaleString()}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

/**
 * Admin analytics dashboard page with comprehensive metrics and visualizations.
 */
export default function AdminAnalyticsPage() {
  const [analyticsData, setAnalyticsData] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [period, setPeriod] = useState('daily');
  const [scope, setScope] = useState('global');
  const [refreshing, setRefreshing] = useState(false);

  /**
   * Fetch analytics data from the API.
   */
  const fetchAnalytics = useCallback(async (forceRefresh: boolean = false) => {
    try {
      if (forceRefresh) {
        setRefreshing(true);
      } else {
        setLoading(true);
      }
      
      const token = localStorage.getItem('auth_token');
      const params = new URLSearchParams({
        period,
        scope,
        force_refresh: forceRefresh.toString()
      });

      const response = await fetch(`${process.env.NEXT_PUBLIC_API_BASE}/admin/analytics?${params}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error('Failed to fetch analytics data');
      }

      const data = await response.json();
      setAnalyticsData(data);
    } catch (error) {
      console.error('Error fetching analytics:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [period, scope]);

  /**
   * Format percentage value.
   */
  const formatPercentage = (value: number): string => {
    return `${value.toFixed(1)}%`;
  };

  /**
   * Format date for display.
   */
  const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  // Fetch analytics on component mount and when filters change
  useEffect(() => {
    fetchAnalytics();
  }, [fetchAnalytics]);

  if (loading) {
    return (
      <AdminLayout title="Analytics Dashboard">
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-maroon-600"></div>
        </div>
      </AdminLayout>
    );
  }

  if (!analyticsData) {
    return (
      <AdminLayout title="Analytics Dashboard">
        <div className="text-center py-12">
          <p className="text-gray-500">Failed to load analytics data.</p>
          <button
            onClick={() => fetchAnalytics()}
            className="mt-4 bg-maroon-600 text-white px-4 py-2 rounded-md hover:bg-maroon-700"
          >
            Retry
          </button>
        </div>
      </AdminLayout>
    );
  }

  return (
    <AdminLayout title="Analytics Dashboard">
      <div className="space-y-6">
        {/* Header with filters and refresh */}
        <div className="bg-white shadow rounded-lg p-6">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
            <div>
              <h2 className="text-lg font-medium text-gray-900">Analytics Overview</h2>
              <p className="text-sm text-gray-500">
                Last updated: {formatDate(analyticsData.generated_at)}
              </p>
            </div>
            
            <div className="flex items-center space-x-4">
              <select
                className="border border-gray-300 rounded-md px-3 py-2 focus:ring-maroon-500 focus:border-maroon-500"
                value={period}
                onChange={(e) => setPeriod(e.target.value)}
              >
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
              </select>
              
              <select
                className="border border-gray-300 rounded-md px-3 py-2 focus:ring-maroon-500 focus:border-maroon-500"
                value={scope}
                onChange={(e) => setScope(e.target.value)}
              >
                <option value="global">Global</option>
                <option value="class">By Class</option>
                <option value="user">By User</option>
              </select>
              
              <button
                onClick={() => fetchAnalytics(true)}
                disabled={refreshing}
                className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-maroon-500 disabled:opacity-50"
              >
                <RefreshIcon className={`h-4 w-4 mr-2 ${refreshing ? 'animate-spin' : ''}`} />
                Refresh
              </button>
            </div>
          </div>
        </div>

        {/* Overview Stats */}
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
          <StatCard
            title="Total Users"
            value={analyticsData.overview.total_users}
            icon={UsersIcon}
            color="text-blue-600"
          />
          <StatCard
            title="Active Users"
            value={analyticsData.overview.active_users}
            icon={TrendingUpIcon}
            color="text-green-600"
          />
          <StatCard
            title="New Users"
            value={analyticsData.overview.new_users}
            icon={UsersIcon}
            color="text-purple-600"
          />
          <StatCard
            title="Total Classes"
            value={analyticsData.overview.total_classes}
            icon={AcademicCapIcon}
            color="text-indigo-600"
          />
          <StatCard
            title="Total Assignments"
            value={analyticsData.overview.total_assignments}
            icon={ClipboardListIcon}
            color="text-yellow-600"
          />
          <StatCard
            title="Total Submissions"
            value={analyticsData.overview.total_submissions}
            icon={BookOpenIcon}
            color="text-red-600"
          />
        </div>

        {/* Charts Row 1 */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <SimpleBarChart
            data={analyticsData.users.by_role}
            title="Users by Role"
            color="bg-blue-500"
          />
          <SimpleBarChart
            data={analyticsData.classes.classes_by_level}
            title="Classes by Level"
            color="bg-indigo-500"
          />
        </div>

        {/* Engagement Metrics */}
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
          <StatCard
            title="Submission Rate"
            value={formatPercentage(analyticsData.engagement.submission_rate)}
            icon={ChartBarIcon}
            color="text-green-600"
          />
          <StatCard
            title="Class Participation"
            value={formatPercentage(analyticsData.engagement.class_participation)}
            icon={AcademicCapIcon}
            color="text-blue-600"
          />
          <StatCard
            title="Average Score"
            value={formatPercentage(analyticsData.submissions.average_score || 0)}
            icon={TrendingUpIcon}
            color="text-purple-600"
          />
        </div>

        {/* Quran Progress Metrics */}
        <div className="bg-white shadow rounded-lg p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-6">Quran Progress</h3>
          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div className="text-center">
              <div className="text-2xl font-bold text-maroon-600">
                {analyticsData.quran_progress.total_hasanat.toLocaleString()}
              </div>
              <div className="text-sm text-gray-500">Total Hasanat</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-gold-600">
                {formatPercentage(analyticsData.quran_progress.average_memorization_confidence * 100)}
              </div>
              <div className="text-sm text-gray-500">Avg. Memorization</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-green-600">
                {analyticsData.quran_progress.active_readers.toLocaleString()}
              </div>
              <div className="text-sm text-gray-500">Active Readers</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-blue-600">
                {analyticsData.quran_progress.total_progress_records.toLocaleString()}
              </div>
              <div className="text-sm text-gray-500">Progress Records</div>
            </div>
          </div>
        </div>

        {/* Assignment & Submission Status */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white shadow rounded-lg p-6">
            <h3 className="text-lg font-medium text-gray-900 mb-4">Assignment Status</h3>
            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">Published</span>
                <span className="text-sm font-medium text-green-600">
                  {analyticsData.assignments.published_assignments}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">Draft</span>
                <span className="text-sm font-medium text-yellow-600">
                  {analyticsData.assignments.draft_assignments}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">New This Period</span>
                <span className="text-sm font-medium text-blue-600">
                  {analyticsData.assignments.new_assignments}
                </span>
              </div>
            </div>
          </div>
          
          <div className="bg-white shadow rounded-lg p-6">
            <h3 className="text-lg font-medium text-gray-900 mb-4">Submission Status</h3>
            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">Pending Review</span>
                <span className="text-sm font-medium text-yellow-600">
                  {analyticsData.submissions.pending_submissions}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">Graded</span>
                <span className="text-sm font-medium text-green-600">
                  {analyticsData.submissions.graded_submissions}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">New This Period</span>
                <span className="text-sm font-medium text-blue-600">
                  {analyticsData.submissions.new_submissions}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}