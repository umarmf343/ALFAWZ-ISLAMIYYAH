/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

"use client";

import React, { useState, useEffect } from "react";
import { motion } from "framer-motion";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Calendar, Users, BookOpen, Trophy, TrendingUp, Brain } from "lucide-react";

interface MemorizationStats {
  totalMemorizers: number;
  totalAyahsMemorized: number;
  averageProgress: number;
  topMemorizers: Array<{
    id: number;
    name: string;
    ayahsMemorized: number;
    currentStreak: number;
  }>;
  weeklyProgress: Array<{
    date: string;
    ayahs: number;
    users: number;
  }>;
}

/**
 * Admin dashboard page with memorization analytics and system overview.
 * Displays key metrics, top memorizers, and weekly progress trends.
 */
export default function AdminDashboardPage() {
  const [memorizationStats, setMemorizationStats] = useState<MemorizationStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  /**
   * Fetch memorization analytics data from the API.
   * Handles loading states and error management.
   */
  const fetchMemorizationStats = async () => {
    try {
      setLoading(true);
      const response = await fetch('/api/admin/memorization-analytics', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error('Failed to fetch memorization analytics');
      }

      const data = await response.json();
      setMemorizationStats(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unknown error occurred');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMemorizationStats();
  }, []);

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-emerald-50 to-teal-100 dark:from-gray-900 dark:to-gray-800 p-6">
        <div className="max-w-7xl mx-auto">
          <div className="animate-pulse space-y-6">
            <div className="h-8 bg-gray-300 rounded w-1/3"></div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              {[...Array(4)].map((_, i) => (
                <div key={i} className="h-32 bg-gray-300 rounded"></div>
              ))}
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-emerald-50 to-teal-100 dark:from-gray-900 dark:to-gray-800 p-6">
        <div className="max-w-7xl mx-auto">
          <Card className="border-red-200 bg-red-50">
            <CardContent className="p-6">
              <p className="text-red-600">Error loading analytics: {error}</p>
              <Button onClick={fetchMemorizationStats} className="mt-4">
                Retry
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-emerald-50 to-teal-100 dark:from-gray-900 dark:to-gray-800 p-6">
      <div className="max-w-7xl mx-auto space-y-8">
        {/* Header */}
        <motion.div
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          className="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl p-6 shadow-lg border border-white/20"
        >
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Admin Dashboard
              </h1>
              <p className="text-gray-600 dark:text-gray-300">
                Memorization Analytics & System Overview
              </p>
            </div>
            <Badge variant="outline" className="flex items-center gap-2">
              <Calendar className="h-4 w-4" />
              {new Date().toLocaleDateString()}
            </Badge>
          </div>
        </motion.div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }}>
            <Card className="bg-gradient-to-br from-blue-500 to-blue-600 text-white border-0">
              <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <Users className="h-5 w-5" />
                  Total Memorizers
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold">{memorizationStats?.totalMemorizers || 0}</div>
                <p className="text-blue-100 text-sm mt-1">Active students</p>
              </CardContent>
            </Card>
          </motion.div>

          <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }}>
            <Card className="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white border-0">
              <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <BookOpen className="h-5 w-5" />
                  Ayahs Memorized
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold">{memorizationStats?.totalAyahsMemorized || 0}</div>
                <p className="text-emerald-100 text-sm mt-1">Total verses</p>
              </CardContent>
            </Card>
          </motion.div>

          <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }}>
            <Card className="bg-gradient-to-br from-purple-500 to-purple-600 text-white border-0">
              <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <TrendingUp className="h-5 w-5" />
                  Average Progress
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold">{memorizationStats?.averageProgress || 0}%</div>
                <p className="text-purple-100 text-sm mt-1">Completion rate</p>
              </CardContent>
            </Card>
          </motion.div>

          <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.4 }}>
            <Card className="bg-gradient-to-br from-orange-500 to-orange-600 text-white border-0">
              <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-lg">
                  <Brain className="h-5 w-5" />
                  Weekly Growth
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold">
                  +{memorizationStats?.weeklyProgress?.reduce((acc, day) => acc + day.ayahs, 0) || 0}
                </div>
                <p className="text-orange-100 text-sm mt-1">Ayahs this week</p>
              </CardContent>
            </Card>
          </motion.div>
        </div>

        {/* Top Memorizers */}
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.5 }}>
          <Card className="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Trophy className="h-5 w-5 text-yellow-500" />
                Top Memorizers
              </CardTitle>
              <CardDescription>
                Students with the highest memorization achievements
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {memorizationStats?.topMemorizers?.map((student, index) => (
                  <div key={student.id} className="flex items-center justify-between p-4 rounded-lg bg-gray-50 dark:bg-gray-700">
                    <div className="flex items-center gap-3">
                      <div className={`w-8 h-8 rounded-full flex items-center justify-center text-white font-bold ${
                        index === 0 ? 'bg-yellow-500' : index === 1 ? 'bg-gray-400' : index === 2 ? 'bg-orange-400' : 'bg-gray-300'
                      }`}>
                        {index + 1}
                      </div>
                      <div>
                        <p className="font-semibold text-gray-900 dark:text-white">{student.name}</p>
                        <p className="text-sm text-gray-600 dark:text-gray-300">
                          {student.ayahsMemorized} ayahs memorized
                        </p>
                      </div>
                    </div>
                    <Badge variant="outline" className="flex items-center gap-1">
                      🔥 {student.currentStreak} day streak
                    </Badge>
                  </div>
                )) || (
                  <p className="text-gray-500 dark:text-gray-400 text-center py-8">
                    No memorization data available
                  </p>
                )}
              </div>
            </CardContent>
          </Card>
        </motion.div>
      </div>
    </div>
  );
}