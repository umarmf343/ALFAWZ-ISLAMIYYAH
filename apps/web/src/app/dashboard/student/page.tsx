/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
  BookOpen, 
  Calendar, 
  Clock, 
  Star, 
  TrendingUp, 
  Users, 
  Award,
  Target,
  Brain,
  Flame,
  Trophy
} from 'lucide-react';
import MemorizationSection from '@/components/MemorizationSection';
import LeaderboardPanel from '@/components/LeaderboardPanel';

/**
 * Student Dashboard page component.
 * Provides comprehensive overview of student progress, assignments, and memorization activities.
 */
export default function StudentDashboard() {
  // Mock data - in real implementation, this would come from API
  const studentStats = {
    totalHasanat: 12450,
    weeklyStreak: 7,
    completedAssignments: 23,
    averageScore: 87,
    rank: 5,
    totalStudents: 45
  };

  const recentAssignments = [
    {
      id: 1,
      title: 'Surah Al-Fatiha Recitation',
      dueDate: '2024-01-15',
      status: 'completed',
      score: 95
    },
    {
      id: 2,
      title: 'Tajweed Rules Quiz',
      dueDate: '2024-01-18',
      status: 'pending',
      score: null
    },
    {
      id: 3,
      title: 'Surah Al-Baqarah Verses 1-10',
      dueDate: '2024-01-20',
      status: 'in_progress',
      score: null
    }
  ];

  const upcomingClasses = [
    {
      id: 1,
      title: 'Advanced Tajweed',
      time: '10:00 AM',
      date: '2024-01-15',
      teacher: 'Ustadh Ahmad'
    },
    {
      id: 2,
      title: 'Quran Memorization',
      time: '2:00 PM',
      date: '2024-01-15',
      teacher: 'Ustadha Fatima'
    },
    {
      id: 3,
      title: 'Islamic Studies',
      time: '4:00 PM',
      date: '2024-01-16',
      teacher: 'Ustadh Omar'
    }
  ];

  /**
   * Get status badge variant based on assignment status.
   */
  const getStatusVariant = (status: string) => {
    switch (status) {
      case 'completed': return 'default';
      case 'in_progress': return 'secondary';
      case 'pending': return 'outline';
      default: return 'outline';
    }
  };

  /**
   * Get status color for assignments.
   */
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed': return 'text-green-600';
      case 'in_progress': return 'text-blue-600';
      case 'pending': return 'text-orange-600';
      default: return 'text-gray-600';
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 p-6">
      <div className="max-w-7xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
              Student Dashboard
            </h1>
            <p className="text-gray-600 mt-2">
              Welcome back! Continue your Quranic learning journey.
            </p>
          </div>
          <div className="flex items-center gap-4">
            <Badge className="bg-gradient-to-r from-yellow-500 to-orange-500 text-white px-4 py-2">
              <Flame className="h-4 w-4 mr-1" />
              {studentStats.weeklyStreak} Day Streak
            </Badge>
            <Badge className="bg-gradient-to-r from-green-500 to-teal-500 text-white px-4 py-2">
              <Star className="h-4 w-4 mr-1" />
              {studentStats.totalHasanat.toLocaleString()} Hasanat
            </Badge>
          </div>
        </div>

        {/* Quick Stats */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200 hover:shadow-lg transition-all duration-300">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-blue-600 text-sm font-medium">Total Hasanat</p>
                  <p className="text-3xl font-bold text-blue-700">
                    {studentStats.totalHasanat.toLocaleString()}
                  </p>
                </div>
                <div className="p-3 bg-blue-500 rounded-full">
                  <Star className="h-6 w-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200 hover:shadow-lg transition-all duration-300">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-green-600 text-sm font-medium">Weekly Streak</p>
                  <p className="text-3xl font-bold text-green-700">
                    {studentStats.weeklyStreak} days
                  </p>
                </div>
                <div className="p-3 bg-green-500 rounded-full">
                  <Flame className="h-6 w-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200 hover:shadow-lg transition-all duration-300">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-purple-600 text-sm font-medium">Average Score</p>
                  <p className="text-3xl font-bold text-purple-700">
                    {studentStats.averageScore}%
                  </p>
                </div>
                <div className="p-3 bg-purple-500 rounded-full">
                  <TrendingUp className="h-6 w-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200 hover:shadow-lg transition-all duration-300">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-orange-600 text-sm font-medium">Class Rank</p>
                  <p className="text-3xl font-bold text-orange-700">
                    #{studentStats.rank}
                  </p>
                  <p className="text-xs text-orange-600">of {studentStats.totalStudents}</p>
                </div>
                <div className="p-3 bg-orange-500 rounded-full">
                  <Trophy className="h-6 w-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Main Content Tabs */}
        <Tabs defaultValue="overview" className="space-y-6">
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="memorization">Memorization</TabsTrigger>
            <TabsTrigger value="assignments">Assignments</TabsTrigger>
            <TabsTrigger value="schedule">Schedule</TabsTrigger>
          </TabsList>

          <TabsContent value="overview" className="space-y-6">
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {/* Recent Activity */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Clock className="h-5 w-5" />
                    Recent Activity
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                      <div className="p-2 bg-green-500 rounded-full">
                        <BookOpen className="h-4 w-4 text-white" />
                      </div>
                      <div className="flex-1">
                        <p className="font-medium">Completed Surah Al-Fatiha</p>
                        <p className="text-sm text-gray-600">2 hours ago</p>
                      </div>
                      <Badge variant="default">+150 Hasanat</Badge>
                    </div>
                    
                    <div className="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                      <div className="p-2 bg-blue-500 rounded-full">
                        <Brain className="h-4 w-4 text-white" />
                      </div>
                      <div className="flex-1">
                        <p className="font-medium">Memorization Review</p>
                        <p className="text-sm text-gray-600">5 hours ago</p>
                      </div>
                      <Badge variant="secondary">+75 Hasanat</Badge>
                    </div>
                    
                    <div className="flex items-center gap-3 p-3 bg-purple-50 rounded-lg">
                      <div className="p-2 bg-purple-500 rounded-full">
                        <Award className="h-4 w-4 text-white" />
                      </div>
                      <div className="flex-1">
                        <p className="font-medium">Achieved 7-day streak</p>
                        <p className="text-sm text-gray-600">1 day ago</p>
                      </div>
                      <Badge variant="outline">Milestone</Badge>
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Progress Overview */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Target className="h-5 w-5" />
                    Weekly Progress
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div>
                      <div className="flex justify-between text-sm mb-2">
                        <span>Daily Goals</span>
                        <span>5/7 days</span>
                      </div>
                      <Progress value={71} className="h-2" />
                    </div>
                    
                    <div>
                      <div className="flex justify-between text-sm mb-2">
                        <span>Memorization</span>
                        <span>12/15 ayahs</span>
                      </div>
                      <Progress value={80} className="h-2" />
                    </div>
                    
                    <div>
                      <div className="flex justify-between text-sm mb-2">
                        <span>Assignments</span>
                        <span>3/4 completed</span>
                      </div>
                      <Progress value={75} className="h-2" />
                    </div>
                    
                    <div className="pt-4 border-t">
                      <div className="flex items-center justify-between">
                        <span className="text-sm font-medium">Overall Progress</span>
                        <Badge className="bg-gradient-to-r from-green-500 to-teal-500 text-white">
                          75% Complete
                        </Badge>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Leaderboard Panel */}
              <LeaderboardPanel className="lg:col-span-1" />
            </div>
          </TabsContent>

          <TabsContent value="memorization">
            <MemorizationSection className="w-full" />
          </TabsContent>

          <TabsContent value="assignments" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <BookOpen className="h-5 w-5" />
                  Recent Assignments
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {recentAssignments.map((assignment) => (
                    <div key={assignment.id} className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                      <div className="flex-1">
                        <h4 className="font-medium">{assignment.title}</h4>
                        <p className="text-sm text-gray-600">
                          Due: {new Date(assignment.dueDate).toLocaleDateString()}
                        </p>
                      </div>
                      <div className="flex items-center gap-3">
                        {assignment.score && (
                          <Badge variant="default">
                            {assignment.score}%
                          </Badge>
                        )}
                        <Badge variant={getStatusVariant(assignment.status)}>
                          <span className={getStatusColor(assignment.status)}>
                            {assignment.status.replace('_', ' ')}
                          </span>
                        </Badge>
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          <TabsContent value="schedule" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Calendar className="h-5 w-5" />
                  Upcoming Classes
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {upcomingClasses.map((class_item) => (
                    <div key={class_item.id} className="flex items-center gap-4 p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                      <div className="p-3 bg-blue-100 rounded-full">
                        <Users className="h-5 w-5 text-blue-600" />
                      </div>
                      <div className="flex-1">
                        <h4 className="font-medium">{class_item.title}</h4>
                        <p className="text-sm text-gray-600">
                          {class_item.teacher} • {class_item.time}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-medium">
                          {new Date(class_item.date).toLocaleDateString()}
                        </p>
                        <p className="text-xs text-gray-500">{class_item.time}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}