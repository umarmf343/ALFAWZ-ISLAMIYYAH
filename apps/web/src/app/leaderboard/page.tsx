/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { ApiError, api } from '@/lib/api';
import { computeHasanat, formatHasanat, getHasanatBadge } from '@/lib/hasanat';
import { LeaderboardEntry } from '@/types';

/**
 * Leaderboard page component that displays student rankings based on hasanat earned
 * with filtering options for scope (global/class) and time period (weekly/monthly)
 */
export default function LeaderboardPage() {
  const { user, isAuthenticated } = useAuth();
  const [leaderboard, setLeaderboard] = useState<LeaderboardEntry[]>([]);
  const [loading, setLoading] = useState(true);
  const [scope, setScope] = useState<'global' | 'class'>('global');
  const [period, setPeriod] = useState<'weekly' | 'monthly'>('weekly');
  const [error, setError] = useState('');
  const [userRank, setUserRank] = useState<number | null>(null);

  const getErrorMessage = (error: unknown, fallback: string) => {
    if (error instanceof ApiError) {
      return error.message;
    }

    if (error instanceof Error) {
      return error.message;
    }

    return fallback;
  };

  /**
   * Fetch leaderboard data based on current filters
   */
  const fetchLeaderboard = async () => {
    try {
      setLoading(true);
      setError('');

      const params = new URLSearchParams({
        scope,
        period
      });

      const response = await api.get<LeaderboardEntry[]>(`/leaderboard?${params}`);
      const entries = response.data ?? [];

      setLeaderboard(entries);

      // Find current user's rank
      const currentUserEntry = entries.find((entry) => entry.user_id === user?.id);
      setUserRank(currentUserEntry ? currentUserEntry.rank : null);

    } catch (err: unknown) {
      setError(getErrorMessage(err, 'Failed to fetch leaderboard'));
    } finally {
      setLoading(false);
    }
  };

  /**
   * Get rank display with appropriate styling
   */
  const getRankDisplay = (rank: number) => {
    if (rank === 1) return { emoji: '🥇', color: 'text-yellow-600' };
    if (rank === 2) return { emoji: '🥈', color: 'text-gray-500' };
    if (rank === 3) return { emoji: '🥉', color: 'text-orange-600' };
    return { emoji: `#${rank}`, color: 'text-gray-700' };
  };

  /**
   * Get user avatar or initials
   */
  const getUserAvatar = (name: string) => {
    const initials = name.split(' ').map(n => n[0]).join('').toUpperCase();
    return initials.substring(0, 2);
  };

  /**
   * Format time period for display
   */
  const getPeriodLabel = () => {
    return period === 'weekly' ? 'This Week' : 'This Month';
  };

  /**
   * Get scope label for display
   */
  const getScopeLabel = () => {
    return scope === 'global' ? 'Global Leaderboard' : 'Class Leaderboard';
  };

  useEffect(() => {
    if (isAuthenticated) {
      fetchLeaderboard();
    }
  }, [isAuthenticated, scope, period]);

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Access Denied</h1>
          <p className="text-gray-600">Please log in to view the leaderboard.</p>
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
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">
            🏆 {getScopeLabel()}
          </h1>
          <p className="text-gray-600">
            Top performers for {getPeriodLabel().toLowerCase()}
          </p>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
          <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
            {/* Scope Filter */}
            <div className="flex bg-gray-100 rounded-lg p-1">
              <button
                onClick={() => setScope('global')}
                className={`px-4 py-2 rounded-md font-medium transition-colors ${
                  scope === 'global'
                    ? 'bg-white text-green-600 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                🌍 Global
              </button>
              <button
                onClick={() => setScope('class')}
                className={`px-4 py-2 rounded-md font-medium transition-colors ${
                  scope === 'class'
                    ? 'bg-white text-green-600 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                🏫 Class
              </button>
            </div>

            {/* Period Filter */}
            <div className="flex bg-gray-100 rounded-lg p-1">
              <button
                onClick={() => setPeriod('weekly')}
                className={`px-4 py-2 rounded-md font-medium transition-colors ${
                  period === 'weekly'
                    ? 'bg-white text-green-600 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                📅 Weekly
              </button>
              <button
                onClick={() => setPeriod('monthly')}
                className={`px-4 py-2 rounded-md font-medium transition-colors ${
                  period === 'monthly'
                    ? 'bg-white text-green-600 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                📆 Monthly
              </button>
            </div>
          </div>
        </div>

        {/* Error Message */}
        {error && (
          <div className="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {error}
          </div>
        )}

        {/* User's Current Rank */}
        {userRank && (
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div className="flex items-center justify-center">
              <span className="text-blue-800 font-medium">
                🎯 Your current rank: #{userRank}
              </span>
            </div>
          </div>
        )}

        {/* Leaderboard */}
        {leaderboard.length === 0 ? (
          <div className="text-center py-12">
            <div className="text-6xl mb-4">🏆</div>
            <h3 className="text-xl font-semibold text-gray-900 mb-2">
              No Rankings Available
            </h3>
            <p className="text-gray-600">
              Complete assignments and earn hasanat to appear on the leaderboard!
            </p>
          </div>
        ) : (
          <div className="space-y-4">
            {/* Top 3 Podium */}
            {leaderboard.length >= 3 && (
              <div className="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg p-6 mb-8">
                <div className="flex justify-center items-end space-x-8">
                  {/* 2nd Place */}
                  <div className="text-center">
                    <div className="w-16 h-16 bg-gray-400 rounded-full flex items-center justify-center text-white font-bold text-lg mb-2">
                      {getUserAvatar(leaderboard[1].user_name)}
                    </div>
                    <div className="text-2xl mb-1">🥈</div>
                    <div className="font-semibold text-gray-900">{leaderboard[1].user_name}</div>
                    <div className="text-sm text-gray-600">{formatHasanat(leaderboard[1].total_hasanat)}</div>
                    <div className="text-xs text-gray-500">
                      {(() => {
                        const badge = getHasanatBadge(leaderboard[1].total_hasanat);
                        return (
                          <span className={badge.color}>
                            {badge.icon} {badge.name}
                          </span>
                        );
                      })()}
                    </div>
                  </div>

                  {/* 1st Place */}
                  <div className="text-center">
                    <div className="w-20 h-20 bg-yellow-500 rounded-full flex items-center justify-center text-white font-bold text-xl mb-2">
                      {getUserAvatar(leaderboard[0].user_name)}
                    </div>
                    <div className="text-3xl mb-1">🥇</div>
                    <div className="font-bold text-gray-900 text-lg">{leaderboard[0].user_name}</div>
                    <div className="text-sm text-gray-600 font-medium">{formatHasanat(leaderboard[0].total_hasanat)}</div>
                    <div className="text-xs text-gray-500">
                      {(() => {
                        const badge = getHasanatBadge(leaderboard[0].total_hasanat);
                        return (
                          <span className={badge.color}>
                            {badge.icon} {badge.name}
                          </span>
                        );
                      })()}
                    </div>
                  </div>

                  {/* 3rd Place */}
                  <div className="text-center">
                    <div className="w-16 h-16 bg-orange-500 rounded-full flex items-center justify-center text-white font-bold text-lg mb-2">
                      {getUserAvatar(leaderboard[2].user_name)}
                    </div>
                    <div className="text-2xl mb-1">🥉</div>
                    <div className="font-semibold text-gray-900">{leaderboard[2].user_name}</div>
                    <div className="text-sm text-gray-600">{formatHasanat(leaderboard[2].total_hasanat)}</div>
                    <div className="text-xs text-gray-500">
                      {(() => {
                        const badge = getHasanatBadge(leaderboard[2].total_hasanat);
                        return (
                          <span className={badge.color}>
                            {badge.icon} {badge.name}
                          </span>
                        );
                      })()}
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Full Rankings List */}
            <div className="bg-white rounded-lg shadow-sm overflow-hidden">
              <div className="px-6 py-4 bg-gray-50 border-b">
                <h3 className="text-lg font-semibold text-gray-900">Full Rankings</h3>
              </div>
              
              <div className="divide-y divide-gray-200">
                {leaderboard.map((entry, index) => {
                  const rankDisplay = getRankDisplay(entry.rank);
                  const isCurrentUser = entry.user_id === user?.id;
                  
                  return (
                    <div
                      key={entry.user_id}
                      className={`px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors ${
                        isCurrentUser ? 'bg-blue-50 border-l-4 border-blue-500' : ''
                      }`}
                    >
                      <div className="flex items-center space-x-4">
                        {/* Rank */}
                        <div className={`text-2xl font-bold ${rankDisplay.color} min-w-[3rem] text-center`}>
                          {rankDisplay.emoji}
                        </div>
                        
                        {/* Avatar */}
                        <div className="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">
                          {getUserAvatar(entry.user_name)}
                        </div>
                        
                        {/* User Info */}
                        <div>
                          <div className={`font-semibold ${isCurrentUser ? 'text-blue-900' : 'text-gray-900'}`}>
                            {entry.user_name}
                            {isCurrentUser && <span className="ml-2 text-blue-600 text-sm">(You)</span>}
                          </div>
                          <div className="text-sm text-gray-500">
                            {(() => {
                              const badge = getHasanatBadge(entry.total_hasanat);
                              return (
                                <span className={badge.color}>
                                  {badge.icon} {badge.name}
                                </span>
                              );
                            })()}
                          </div>
                        </div>
                      </div>
                      
                      {/* Hasanat */}
                      <div className="text-right">
                        <div className="font-bold text-green-600 text-lg">
                          {formatHasanat(entry.total_hasanat)}
                        </div>
                        <div className="text-sm text-gray-500">
                          hasanat earned
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        )}

        {/* Motivation Section */}
        <div className="mt-12 bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 text-center">
          <h3 className="text-2xl font-bold text-gray-900 mb-4">
            Keep Striving for Excellence! 🌟
          </h3>
          <p className="text-gray-600 mb-6">
            Every ayah you recite, every assignment you complete, brings you closer to Allah and higher on the leaderboard.
          </p>
          <div className="text-sm text-gray-500 italic">
            &ldquo;And whoever strives only strives for [the benefit of] himself.&rdquo; - Quran 29:6
          </div>
        </div>
      </div>
    </div>
  );
}