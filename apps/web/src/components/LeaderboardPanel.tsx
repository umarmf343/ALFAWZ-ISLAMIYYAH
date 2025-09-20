/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useCallback, useEffect, useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  FaTrophy,
  FaMedal,
  FaCrown,
  FaUsers,
  FaUserPlus,
  FaEye,
  FaEyeSlash,
  FaCog,
  FaCalendarWeek,
  FaCalendarAlt,
  FaClock,
  FaStar
} from 'react-icons/fa';

interface LeaderboardEntry {
  rank: number;
  user: {
    id: number;
    name: string;
    avatar?: string;
  };
  hasanat: number;
  surahs_completed: number;
  total_score: number;
  last_active: string;
}

interface LeaderboardInvite {
  id: number;
  sender: {
    id: number;
    name: string;
    avatar?: string;
  };
  message?: string;
  created_at: string;
}

interface LeaderboardPanelProps {
  className?: string;
}

type TimeFrame = 'all_time' | 'monthly' | 'weekly';

interface CommunityStats {
  total_participants: number;
  active_this_week: number;
}

interface LeaderboardApiResponse {
  leaderboard?: LeaderboardEntry[];
  user_rank?: number | null;
  user_entry?: LeaderboardEntry | null;
}

const isLeaderboardUser = (value: unknown): value is LeaderboardEntry['user'] => {
  if (typeof value !== 'object' || value === null) {
    return false;
  }

  const user = value as Record<string, unknown>;
  return (
    typeof user.id === 'number' &&
    typeof user.name === 'string' &&
    (user.avatar === undefined || typeof user.avatar === 'string')
  );
};

const isLeaderboardEntry = (value: unknown): value is LeaderboardEntry => {
  if (typeof value !== 'object' || value === null) {
    return false;
  }

  const entry = value as Record<string, unknown>;
  return (
    typeof entry.rank === 'number' &&
    isLeaderboardUser(entry.user) &&
    typeof entry.hasanat === 'number' &&
    typeof entry.surahs_completed === 'number' &&
    typeof entry.total_score === 'number' &&
    typeof entry.last_active === 'string'
  );
};

const isLeaderboardEntryArray = (value: unknown): value is LeaderboardEntry[] => {
  return Array.isArray(value) && value.every(isLeaderboardEntry);
};

const isCommunityStats = (value: unknown): value is CommunityStats => {
  if (typeof value !== 'object' || value === null) {
    return false;
  }

  const stats = value as Record<string, unknown>;
  return (
    typeof stats.total_participants === 'number' &&
    typeof stats.active_this_week === 'number'
  );
};

const parseLeaderboardResponse = (value: unknown): LeaderboardApiResponse => {
  if (typeof value !== 'object' || value === null) {
    return {};
  }

  const record = value as Record<string, unknown>;
  const response: LeaderboardApiResponse = {};

  if (isLeaderboardEntryArray(record.leaderboard)) {
    response.leaderboard = record.leaderboard;
  }

  if (
    record.user_rank === null ||
    typeof record.user_rank === 'number'
  ) {
    response.user_rank = record.user_rank ?? null;
  }

  if (
    record.user_entry === null ||
    isLeaderboardEntry(record.user_entry)
  ) {
    response.user_entry = (record.user_entry as LeaderboardEntry) ?? null;
  }

  return response;
};

/**
 * Comprehensive leaderboard panel with rankings, community features, and gamification.
 */
export default function LeaderboardPanel({ className = '' }: LeaderboardPanelProps) {
  const [leaderboard, setLeaderboard] = useState<LeaderboardEntry[]>([]);
  const [invites, setInvites] = useState<LeaderboardInvite[]>([]);
  const [timeframe, setTimeframe] = useState<TimeFrame>('all_time');
  const [isPublic, setIsPublic] = useState(false);
  const [userRank, setUserRank] = useState<number | null>(null);
  const [userEntry, setUserEntry] = useState<LeaderboardEntry | null>(null);
  const [loading, setLoading] = useState(true);
  const [showInvites, setShowInvites] = useState(false);
  const [showSettings, setShowSettings] = useState(false);
  const [inviteUserId, setInviteUserId] = useState('');
  const [inviteMessage, setInviteMessage] = useState('');
  const [communityStats, setCommunityStats] = useState<CommunityStats | null>(null);

  /**
   * Fetch leaderboard data from API.
   */
  const fetchLeaderboard = useCallback(async () => {
    try {
      setLoading(true);
      const response = await fetch(`/api/student/leaderboard?timeframe=${timeframe}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      
      if (response.ok) {
        const data = parseLeaderboardResponse(await response.json());
        setLeaderboard(data.leaderboard ?? []);
        setUserRank(data.user_rank ?? null);
        setUserEntry(data.user_entry ?? null);
      }
    } catch (error) {
      console.error('Failed to fetch leaderboard:', error);
    } finally {
      setLoading(false);
    }
  }, [timeframe]);

  /**
   * Fetch user's leaderboard invites.
   */
  const fetchInvites = useCallback(async () => {
    try {
      const response = await fetch('/api/student/leaderboard/invites', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      
      if (response.ok) {
        const data = await response.json();
        setInvites(data.data || []);
      }
    } catch (error) {
      console.error('Failed to fetch invites:', error);
    }
  }, []);

  /**
   * Fetch community statistics.
   */
  const fetchCommunityStats = useCallback(async () => {
    try {
      const response = await fetch('/api/student/leaderboard/community', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        }
      });
      
      if (response.ok) {
        const data = await response.json();
        setCommunityStats(isCommunityStats(data) ? data : null);
      }
    } catch (error) {
      console.error('Failed to fetch community stats:', error);
    }
  }, []);

  /**
   * Send leaderboard invite to another user.
   */
  const sendInvite = async () => {
    if (!inviteUserId.trim()) return;
    
    try {
      const response = await fetch('/api/student/leaderboard/invites', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          receiver_id: parseInt(inviteUserId),
          message: inviteMessage.trim() || undefined
        })
      });
      
      if (response.ok) {
        setInviteUserId('');
        setInviteMessage('');
        fetchInvites();
      }
    } catch (error) {
      console.error('Failed to send invite:', error);
    }
  };

  /**
   * Respond to a leaderboard invite.
   */
  const respondToInvite = async (inviteId: number, action: 'accept' | 'decline') => {
    try {
      const response = await fetch(`/api/student/leaderboard/invites/${inviteId}/respond`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ action })
      });
      
      if (response.ok) {
        fetchInvites();
        if (action === 'accept') {
          fetchLeaderboard();
        }
      }
    } catch (error) {
      console.error('Failed to respond to invite:', error);
    }
  };

  /**
   * Update leaderboard preferences.
   */
  interface LeaderboardPreferences {
    is_public: boolean;
  }

  const updatePreferences = async (preferences: LeaderboardPreferences) => {
    try {
      const response = await fetch('/api/student/leaderboard/preferences', {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(preferences)
      });
      
      if (response.ok) {
        fetchLeaderboard();
      }
    } catch (error) {
      console.error('Failed to update preferences:', error);
    }
  };

  /**
   * Get rank icon based on position.
   */
  const getRankIcon = (rank: number) => {
    switch (rank) {
      case 1:
        return <FaCrown className="text-yellow-500 text-xl" />;
      case 2:
        return <FaMedal className="text-gray-400 text-xl" />;
      case 3:
        return <FaMedal className="text-amber-600 text-xl" />;
      default:
        return <span className="text-gray-500 font-bold">#{rank}</span>;
    }
  };

  /**
   * Get rank background color.
   */
  const getRankBgColor = (rank: number) => {
    switch (rank) {
      case 1:
        return 'bg-gradient-to-r from-yellow-400 to-yellow-600';
      case 2:
        return 'bg-gradient-to-r from-gray-300 to-gray-500';
      case 3:
        return 'bg-gradient-to-r from-amber-400 to-amber-600';
      default:
        return 'bg-gradient-to-r from-blue-50 to-blue-100';
    }
  };

  useEffect(() => {
    fetchLeaderboard();
    fetchInvites();
    fetchCommunityStats();
  }, [fetchLeaderboard, fetchInvites, fetchCommunityStats]);

  return (
    <div className={`bg-white rounded-2xl shadow-lg overflow-hidden ${className}`}>
      {/* Header */}
      <div className="bg-gradient-to-r from-purple-600 to-blue-600 p-6 text-white">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center space-x-3">
            <FaTrophy className="text-2xl text-yellow-300" />
            <h2 className="text-2xl font-bold">Leaderboard</h2>
          </div>
          <div className="flex items-center space-x-2">
            <button
              onClick={() => setShowInvites(!showInvites)}
              className="p-2 bg-white/20 rounded-lg hover:bg-white/30 transition-colors relative"
            >
              <FaUsers className="text-lg" />
              {invites.length > 0 && (
                <span className="absolute -top-1 -right-1 bg-red-500 text-xs rounded-full w-5 h-5 flex items-center justify-center">
                  {invites.length}
                </span>
              )}
            </button>
            <button
              onClick={() => setShowSettings(!showSettings)}
              className="p-2 bg-white/20 rounded-lg hover:bg-white/30 transition-colors"
            >
              <FaCog className="text-lg" />
            </button>
          </div>
        </div>

        {/* Timeframe Selector */}
        <div className="flex space-x-2">
          {[
            { key: 'all_time', label: 'All Time', icon: FaClock },
            { key: 'monthly', label: 'Monthly', icon: FaCalendarAlt },
            { key: 'weekly', label: 'Weekly', icon: FaCalendarWeek }
          ].map(({ key, label, icon: Icon }) => (
            <button
              key={key}
              onClick={() => setTimeframe(key as TimeFrame)}
              className={`flex items-center space-x-2 px-4 py-2 rounded-lg transition-colors ${
                timeframe === key
                  ? 'bg-white text-purple-600 font-semibold'
                  : 'bg-white/20 hover:bg-white/30'
              }`}
            >
              <Icon className="text-sm" />
              <span className="text-sm">{label}</span>
            </button>
          ))}
        </div>
      </div>

      {/* Community Stats */}
      {communityStats && (
        <div className="p-4 bg-gray-50 border-b">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div>
              <div className="text-2xl font-bold text-purple-600">{communityStats.total_participants}</div>
              <div className="text-sm text-gray-600">Total Participants</div>
            </div>
            <div>
              <div className="text-2xl font-bold text-green-600">{communityStats.active_this_week}</div>
              <div className="text-sm text-gray-600">Active This Week</div>
            </div>
            <div>
              <div className="text-2xl font-bold text-orange-600">{userRank || 'N/A'}</div>
              <div className="text-sm text-gray-600">Your Rank</div>
            </div>
            <div>
              <div className="text-2xl font-bold text-blue-600">{userEntry?.hasanat || 0}</div>
              <div className="text-sm text-gray-600">Your Hasanat</div>
            </div>
          </div>
        </div>
      )}

      {/* Settings Panel */}
      <AnimatePresence>
        {showSettings && (
          <motion.div
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: 'auto', opacity: 1 }}
            exit={{ height: 0, opacity: 0 }}
            className="border-b bg-gray-50 overflow-hidden"
          >
            <div className="p-4">
              <h3 className="font-semibold mb-3">Leaderboard Settings</h3>
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-sm">Public Profile</span>
                  <button
                    onClick={() => {
                      const newIsPublic = !isPublic;
                      setIsPublic(newIsPublic);
                      updatePreferences({ is_public: newIsPublic });
                    }}
                    className={`flex items-center space-x-2 px-3 py-1 rounded-full transition-colors ${
                      isPublic ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'
                    }`}
                  >
                    {isPublic ? <FaEye /> : <FaEyeSlash />}
                    <span className="text-sm">{isPublic ? 'Public' : 'Private'}</span>
                  </button>
                </div>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Invites Panel */}
      <AnimatePresence>
        {showInvites && (
          <motion.div
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: 'auto', opacity: 1 }}
            exit={{ height: 0, opacity: 0 }}
            className="border-b bg-blue-50 overflow-hidden"
          >
            <div className="p-4">
              <h3 className="font-semibold mb-3">Community Invites</h3>
              
              {/* Send Invite */}
              <div className="mb-4 p-3 bg-white rounded-lg">
                <h4 className="text-sm font-medium mb-2">Invite Someone</h4>
                <div className="space-y-2">
                  <input
                    type="number"
                    placeholder="User ID"
                    value={inviteUserId}
                    onChange={(e) => setInviteUserId(e.target.value)}
                    className="w-full px-3 py-2 border rounded-lg text-sm"
                  />
                  <textarea
                    placeholder="Optional message..."
                    value={inviteMessage}
                    onChange={(e) => setInviteMessage(e.target.value)}
                    className="w-full px-3 py-2 border rounded-lg text-sm h-20 resize-none"
                  />
                  <button
                    onClick={sendInvite}
                    className="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
                  >
                    <FaUserPlus className="inline mr-2" />
                    Send Invite
                  </button>
                </div>
              </div>

              {/* Received Invites */}
              {invites.length > 0 && (
                <div>
                  <h4 className="text-sm font-medium mb-2">Received Invites</h4>
                  <div className="space-y-2">
                    {invites.map((invite) => (
                      <div key={invite.id} className="bg-white p-3 rounded-lg">
                        <div className="flex items-center justify-between">
                          <div>
                            <div className="font-medium text-sm">{invite.sender.name}</div>
                            {invite.message && (
                              <div className="text-xs text-gray-600 mt-1">{invite.message}</div>
                            )}
                          </div>
                          <div className="flex space-x-2">
                            <button
                              onClick={() => respondToInvite(invite.id, 'accept')}
                              className="px-3 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700 transition-colors"
                            >
                              Accept
                            </button>
                            <button
                              onClick={() => respondToInvite(invite.id, 'decline')}
                              className="px-3 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-700 transition-colors"
                            >
                              Decline
                            </button>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Leaderboard List */}
      <div className="p-4">
        {loading ? (
          <div className="text-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div>
            <p className="text-gray-600 mt-2">Loading leaderboard...</p>
          </div>
        ) : leaderboard.length === 0 ? (
          <div className="text-center py-8">
            <FaUsers className="text-4xl text-gray-400 mx-auto mb-2" />
            <p className="text-gray-600">No participants yet</p>
            <p className="text-sm text-gray-500">Be the first to join the leaderboard!</p>
          </div>
        ) : (
          <div className="space-y-3">
            {leaderboard.map((entry, index) => (
              <motion.div
                key={entry.user.id}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: index * 0.1 }}
                className={`p-4 rounded-xl ${getRankBgColor(entry.rank)} ${entry.rank <= 3 ? 'text-white' : 'text-gray-800'} shadow-sm`}
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-4">
                    <div className="flex-shrink-0">
                      {getRankIcon(entry.rank)}
                    </div>
                    <div className="flex-1">
                      <div className="font-semibold">{entry.user.name}</div>
                      <div className={`text-sm ${entry.rank <= 3 ? 'text-white/80' : 'text-gray-600'}`}>
                        {entry.surahs_completed} Surahs • Active {entry.last_active}
                      </div>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="flex items-center space-x-2">
                      <FaStar className={`text-sm ${entry.rank <= 3 ? 'text-yellow-300' : 'text-yellow-500'}`} />
                      <span className="font-bold">{entry.hasanat.toLocaleString()}</span>
                    </div>
                    <div className={`text-sm ${entry.rank <= 3 ? 'text-white/80' : 'text-gray-600'}`}>
                      Score: {entry.total_score.toLocaleString()}
                    </div>
                  </div>
                </div>
              </motion.div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}