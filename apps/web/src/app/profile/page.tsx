/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useState, useEffect, useCallback } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { ApiError, api } from '@/lib/api';
import { formatHasanat, getHasanatBadge } from '@/lib/hasanat';

interface ProfileStats {
  total_hasanat: number;
  classes_count: number;
  assignments_completed: number;
  current_streak: number;
  total_recitations: number;
  memorized_ayahs: number;
}

interface TeacherStudent {
  id: number;
  name: string;
  email: string;
  total_hasanat: number;
  last_active: string;
}

/**
 * Profile page component that displays user information, statistics,
 * and role-specific data (teachers/students for teachers, teachers for students)
 */
export default function ProfilePage() {
  const { user, isAuthenticated } = useAuth();
  const [stats, setStats] = useState<ProfileStats | null>(null);
  const [connections, setConnections] = useState<TeacherStudent[]>([]);
  const [loading, setLoading] = useState(true);
  const [editMode, setEditMode] = useState(false);
  const [profileData, setProfileData] = useState({
    name: '',
    email: ''
  });
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const getErrorMessage = useCallback((caught: unknown, fallback: string) => {
    if (caught instanceof ApiError) {
      return caught.message;
    }

    if (caught instanceof Error) {
      return caught.message;
    }

    return fallback;
  }, []);

  /**
   * Fetch user profile data and statistics
   */
  const fetchProfileData = useCallback(async () => {
    try {
      setLoading(true);
      setError('');
      
      // Fetch user profile
      const profileResponse = await api.get('/me');
      const userData = profileResponse.data;
      
      setProfileData({
        name: userData.name || '',
        email: userData.email || ''
      });
      
      // Mock stats for now (would come from API)
      setStats({
        total_hasanat: userData.total_hasanat || 0,
        classes_count: userData.classes_count || 0,
        assignments_completed: userData.assignments_completed || 0,
        current_streak: userData.current_streak || 0,
        total_recitations: userData.total_recitations || 0,
        memorized_ayahs: userData.memorized_ayahs || 0
      });
      
      // Fetch connections based on role
      if (user?.role === 'teacher') {
        const studentsResponse = await api.get('/my-students');
        setConnections(studentsResponse.data || []);
      } else {
        const teachersResponse = await api.get('/my-teachers');
        setConnections(teachersResponse.data || []);
      }
      
    } catch (err: unknown) {
      setError(getErrorMessage(err, 'Failed to fetch profile data'));
    } finally {
      setLoading(false);
    }
  }, [getErrorMessage, user]);

  /**
   * Update user profile
   */
  const handleUpdateProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      setError('');
      setSuccess('');
      
      await api.put('/me', profileData);
      setEditMode(false);
      setSuccess('Profile updated successfully!');
      
    } catch (err: unknown) {
      setError(getErrorMessage(err, 'Failed to update profile'));
    }
  };

  /**
   * Format last active date
   */
  const formatLastActive = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));
    
    if (diffInHours < 1) return 'Active now';
    if (diffInHours < 24) return `${diffInHours}h ago`;
    if (diffInHours < 168) return `${Math.floor(diffInHours / 24)}d ago`;
    return date.toLocaleDateString();
  };

  /**
   * Get user avatar initials
   */
  const getUserAvatar = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
  };

  useEffect(() => {
    if (isAuthenticated && user) {
      fetchProfileData();
    }
  }, [fetchProfileData, isAuthenticated, user]);

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Access Denied</h1>
          <p className="text-gray-600">Please log in to view your profile.</p>
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

  const isTeacher = user?.role === 'teacher';

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Success/Error Messages */}
        {success && (
          <div className="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {success}
          </div>
        )}
        {error && (
          <div className="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {error}
          </div>
        )}

        {/* Profile Header */}
        <div className="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
          <div className="bg-gradient-to-r from-green-500 to-blue-500 px-6 py-8">
            <div className="flex items-center space-x-6">
              {/* Avatar */}
              <div className="w-24 h-24 bg-white rounded-full flex items-center justify-center text-green-600 font-bold text-2xl">
                {getUserAvatar(user?.name || 'User')}
              </div>
              
              {/* User Info */}
              <div className="text-white">
                <h1 className="text-3xl font-bold mb-2">{user?.name}</h1>
                <p className="text-green-100 mb-2">{user?.email}</p>
                <div className="flex items-center space-x-4">
                  <span className="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                    {isTeacher ? '👨‍🏫 Teacher' : '👨‍🎓 Student'}
                  </span>
                  {stats && (
                    <span className="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                      {(() => {
                        const badge = getHasanatBadge(stats.total_hasanat);
                        return (
                          <span className={badge.color}>
                            {badge.icon} {badge.name}
                          </span>
                        );
                      })()}
                    </span>
                  )}
                </div>
              </div>
              
              {/* Edit Button */}
              <div className="ml-auto">
                <button
                  onClick={() => setEditMode(true)}
                  className="bg-white text-green-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
                >
                  Edit Profile
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Edit Profile Modal */}
        {editMode && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-8 max-w-md w-full mx-4">
              <h2 className="text-2xl font-bold text-gray-900 mb-6">Edit Profile</h2>
              
              <form onSubmit={handleUpdateProfile}>
                <div className="mb-4">
                  <label className="block text-gray-700 text-sm font-bold mb-2">
                    Full Name
                  </label>
                  <input
                    type="text"
                    value={profileData.name}
                    onChange={(e) => setProfileData({...profileData, name: e.target.value})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500"
                    required
                  />
                </div>
                
                <div className="mb-6">
                  <label className="block text-gray-700 text-sm font-bold mb-2">
                    Email Address
                  </label>
                  <input
                    type="email"
                    value={profileData.email}
                    onChange={(e) => setProfileData({...profileData, email: e.target.value})}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-500"
                    required
                  />
                </div>
                
                <div className="flex gap-4">
                  <button
                    type="submit"
                    className="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors"
                  >
                    Save Changes
                  </button>
                  <button
                    type="button"
                    onClick={() => setEditMode(false)}
                    className="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg font-semibold hover:bg-gray-400 transition-colors"
                  >
                    Cancel
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Statistics */}
          <div className="lg:col-span-2">
            <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
              <h2 className="text-xl font-bold text-gray-900 mb-6">📊 Statistics</h2>
              
              {stats && (
                <div className="grid grid-cols-2 md:grid-cols-3 gap-6">
                  <div className="text-center">
                    <div className="text-3xl font-bold text-green-600 mb-1">
                      {formatHasanat(stats.total_hasanat)}
                    </div>
                    <div className="text-sm text-gray-600">Total Hasanat</div>
                  </div>
                  
                  <div className="text-center">
                    <div className="text-3xl font-bold text-blue-600 mb-1">
                      {stats.classes_count}
                    </div>
                    <div className="text-sm text-gray-600">
                      {isTeacher ? 'Classes Teaching' : 'Classes Enrolled'}
                    </div>
                  </div>
                  
                  <div className="text-center">
                    <div className="text-3xl font-bold text-purple-600 mb-1">
                      {stats.assignments_completed}
                    </div>
                    <div className="text-sm text-gray-600">
                      {isTeacher ? 'Assignments Created' : 'Assignments Completed'}
                    </div>
                  </div>
                  
                  <div className="text-center">
                    <div className="text-3xl font-bold text-orange-600 mb-1">
                      {stats.current_streak}
                    </div>
                    <div className="text-sm text-gray-600">Day Streak</div>
                  </div>
                  
                  <div className="text-center">
                    <div className="text-3xl font-bold text-red-600 mb-1">
                      {stats.total_recitations}
                    </div>
                    <div className="text-sm text-gray-600">Total Recitations</div>
                  </div>
                  
                  <div className="text-center">
                    <div className="text-3xl font-bold text-indigo-600 mb-1">
                      {stats.memorized_ayahs}
                    </div>
                    <div className="text-sm text-gray-600">Memorized Ayahs</div>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Connections */}
          <div>
            <div className="bg-white rounded-lg shadow-sm p-6">
              <h2 className="text-xl font-bold text-gray-900 mb-6">
                {isTeacher ? '👨‍🎓 My Students' : '👨‍🏫 My Teachers'}
              </h2>
              
              {connections.length === 0 ? (
                <div className="text-center py-8">
                  <div className="text-4xl mb-2">
                    {isTeacher ? '👨‍🎓' : '👨‍🏫'}
                  </div>
                  <p className="text-gray-600 text-sm">
                    {isTeacher ? 'No students yet' : 'No teachers assigned'}
                  </p>
                </div>
              ) : (
                <div className="space-y-4">
                  {connections.map((connection) => (
                    <div key={connection.id} className="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                      <div className="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                        {getUserAvatar(connection.name)}
                      </div>
                      
                      <div className="flex-1 min-w-0">
                        <div className="font-medium text-gray-900 truncate">
                          {connection.name}
                        </div>
                        <div className="text-sm text-gray-500">
                          {formatHasanat(connection.total_hasanat)}
                        </div>
                      </div>
                      
                      <div className="text-xs text-gray-400">
                        {formatLastActive(connection.last_active)}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Recent Activity */}
        <div className="bg-white rounded-lg shadow-sm p-6 mt-8">
          <h2 className="text-xl font-bold text-gray-900 mb-6">📈 Recent Activity</h2>
          
          <div className="text-center py-8 text-gray-500">
            <div className="text-4xl mb-2">📊</div>
            <p>Activity tracking coming soon...</p>
          </div>
        </div>

        {/* Achievements */}
        <div className="bg-white rounded-lg shadow-sm p-6 mt-8">
          <h2 className="text-xl font-bold text-gray-900 mb-6">🏆 Achievements</h2>
          
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center p-4 bg-yellow-50 rounded-lg">
              <div className="text-2xl mb-2">🥇</div>
              <div className="text-sm font-medium text-gray-900">First Submission</div>
              <div className="text-xs text-gray-500">Complete your first assignment</div>
            </div>
            
            <div className="text-center p-4 bg-green-50 rounded-lg">
              <div className="text-2xl mb-2">📚</div>
              <div className="text-sm font-medium text-gray-900">Quran Explorer</div>
              <div className="text-xs text-gray-500">Read 100 ayahs</div>
            </div>
            
            <div className="text-center p-4 bg-blue-50 rounded-lg">
              <div className="text-2xl mb-2">🔥</div>
              <div className="text-sm font-medium text-gray-900">Streak Master</div>
              <div className="text-xs text-gray-500">7-day learning streak</div>
            </div>
            
            <div className="text-center p-4 bg-purple-50 rounded-lg">
              <div className="text-2xl mb-2">⭐</div>
              <div className="text-sm font-medium text-gray-900">Rising Star</div>
              <div className="text-xs text-gray-500">Earn 1000 hasanat</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}