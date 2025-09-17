/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  Award,
  Star,
  Trophy,
  Crown,
  Zap,
  Target,
  TrendingUp,
  Calendar,
  Clock,
  BookOpen,
  Heart,
  Sparkles,
  Gift,
  Medal,
  Flame
} from 'lucide-react';
import { useSpiritualTheme, SpiritualCard, SpiritualButton } from '@/components/providers/SpiritualThemeProvider';

/**
 * Hasanat progress interface for tracking spiritual rewards.
 */
interface HasanatProgress {
  totalHasanat: number;
  dailyHasanat: number;
  weeklyHasanat: number;
  monthlyHasanat: number;
  streak: number;
  level: number;
  nextLevelThreshold: number;
  achievements: Achievement[];
  recentActivities: HasanatActivity[];
}

/**
 * Achievement interface for spiritual milestones.
 */
interface Achievement {
  id: string;
  title: string;
  description: string;
  icon: string;
  hasanat: number;
  unlockedAt?: string;
  isUnlocked: boolean;
  category: 'recitation' | 'consistency' | 'accuracy' | 'milestone';
}

/**
 * Hasanat activity interface for tracking earning history.
 */
interface HasanatActivity {
  id: string;
  type: 'recitation' | 'assignment' | 'bonus' | 'streak';
  hasanat: number;
  description: string;
  timestamp: string;
  surahName?: string;
  ayahCount?: number;
}

/**
 * Props for HasanatTracker component.
 */
interface HasanatTrackerProps {
  userId: string;
  progress?: HasanatProgress;
  onActivityClick?: (activity: HasanatActivity) => void;
  className?: string;
}

/**
 * Hasanat tracking and gamification component with spiritual theme.
 * Displays progress, achievements, and rewards for Qur'an recitation.
 */
export const HasanatTracker: React.FC<HasanatTrackerProps> = ({
  userId,
  progress,
  onActivityClick,
  className = ''
}) => {
  const { theme, animations, styles } = useSpiritualTheme();
  const [selectedTab, setSelectedTab] = useState<'overview' | 'achievements' | 'activities'>('overview');
  const [showLevelUp, setShowLevelUp] = useState(false);
  const [newAchievements, setNewAchievements] = useState<Achievement[]>([]);

  // Mock data if no progress provided
  const defaultProgress: HasanatProgress = {
    totalHasanat: 12450,
    dailyHasanat: 340,
    weeklyHasanat: 2180,
    monthlyHasanat: 8920,
    streak: 7,
    level: 12,
    nextLevelThreshold: 15000,
    achievements: [
      {
        id: 'first-surah',
        title: 'First Steps',
        description: 'Complete your first surah recitation',
        icon: 'BookOpen',
        hasanat: 100,
        unlockedAt: '2024-01-15T10:30:00Z',
        isUnlocked: true,
        category: 'milestone'
      },
      {
        id: 'week-streak',
        title: 'Consistent Reader',
        description: 'Maintain a 7-day recitation streak',
        icon: 'Flame',
        hasanat: 500,
        unlockedAt: '2024-01-22T18:45:00Z',
        isUnlocked: true,
        category: 'consistency'
      },
      {
        id: 'perfect-tajweed',
        title: 'Tajweed Master',
        description: 'Achieve 95%+ tajweed accuracy in 10 assignments',
        icon: 'Crown',
        hasanat: 1000,
        isUnlocked: false,
        category: 'accuracy'
      }
    ],
    recentActivities: [
      {
        id: 'activity-1',
        type: 'recitation',
        hasanat: 120,
        description: 'Recited Surah Al-Fatiha with excellent tajweed',
        timestamp: '2024-01-23T14:30:00Z',
        surahName: 'Al-Fatiha',
        ayahCount: 7
      },
      {
        id: 'activity-2',
        type: 'assignment',
        hasanat: 200,
        description: 'Completed assignment: Memorization Test',
        timestamp: '2024-01-23T10:15:00Z'
      },
      {
        id: 'activity-3',
        type: 'streak',
        hasanat: 50,
        description: 'Daily streak bonus (Day 7)',
        timestamp: '2024-01-23T09:00:00Z'
      }
    ]
  };

  const currentProgress = progress || defaultProgress;

  /**
   * Calculate progress percentage to next level.
   */
  const levelProgress = (currentProgress.totalHasanat / currentProgress.nextLevelThreshold) * 100;

  /**
   * Get icon component by name.
   */
  const getIcon = (iconName: string) => {
    const icons: { [key: string]: React.ComponentType<any> } = {
      BookOpen,
      Flame,
      Crown,
      Trophy,
      Star,
      Medal,
      Award,
      Target
    };
    return icons[iconName] || Star;
  };

  /**
   * Format hasanat number with commas.
   */
  const formatHasanat = (num: number) => {
    return num.toLocaleString();
  };

  /**
   * Get activity type color.
   */
  const getActivityColor = (type: HasanatActivity['type']) => {
    switch (type) {
      case 'recitation': return theme.colors.maroon[600];
      case 'assignment': return theme.colors.gold[600];
      case 'bonus': return theme.colors.accent.emerald;
      case 'streak': return theme.colors.accent.crimson;
      default: return theme.colors.maroon[600];
    }
  };

  /**
   * Get activity type icon.
   */
  const getActivityIcon = (type: HasanatActivity['type']) => {
    switch (type) {
      case 'recitation': return BookOpen;
      case 'assignment': return Target;
      case 'bonus': return Gift;
      case 'streak': return Flame;
      default: return Star;
    }
  };

  /**
   * Handle level up animation.
   */
  useEffect(() => {
    if (currentProgress.totalHasanat >= currentProgress.nextLevelThreshold) {
      setShowLevelUp(true);
      setTimeout(() => setShowLevelUp(false), 3000);
    }
  }, [currentProgress.totalHasanat, currentProgress.nextLevelThreshold]);

  return (
    <motion.div 
      className={`max-w-4xl mx-auto p-6 space-y-6 ${className}`}
      {...animations.pageTransition}
    >
      {/* Header */}
      <SpiritualCard className="p-6" glow>
        <motion.div {...animations.fadeInUp}>
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center space-x-4">
              <div 
                className="p-3 rounded-full"
                style={{ 
                  background: theme.gradients.maroonGold,
                  color: 'white'
                }}
              >
                <Award className="w-8 h-8" />
              </div>
              <div>
                <h1 className="text-2xl font-bold" style={{ color: theme.colors.maroon[800] }}>
                  Hasanat Tracker
                </h1>
                <p style={{ color: theme.colors.maroon[600] }}>
                  Your spiritual journey and rewards
                </p>
              </div>
            </div>
            
            {/* Level Badge */}
            <motion.div
              className="flex items-center space-x-2 px-4 py-2 rounded-full"
              style={{ 
                background: theme.gradients.goldMaroon,
                color: 'white'
              }}
              whileHover={{ scale: 1.05 }}
            >
              <Crown className="w-5 h-5" />
              <span className="font-bold">Level {currentProgress.level}</span>
            </motion.div>
          </div>

          {/* Progress Bar */}
          <div className="space-y-2">
            <div className="flex items-center justify-between text-sm">
              <span style={{ color: theme.colors.maroon[600] }}>
                Progress to Level {currentProgress.level + 1}
              </span>
              <span style={{ color: theme.colors.maroon[600] }}>
                {formatHasanat(currentProgress.totalHasanat)} / {formatHasanat(currentProgress.nextLevelThreshold)}
              </span>
            </div>
            <div 
              className="h-3 rounded-full overflow-hidden"
              style={{ backgroundColor: theme.colors.gold[200] }}
            >
              <motion.div
                className="h-full rounded-full"
                style={{ background: theme.gradients.maroonGold }}
                initial={{ width: 0 }}
                animate={{ width: `${Math.min(levelProgress, 100)}%` }}
                transition={{ duration: 1, ease: 'easeOut' }}
              />
            </div>
          </div>
        </motion.div>
      </SpiritualCard>

      {/* Stats Cards */}
      <motion.div 
        className="grid grid-cols-2 md:grid-cols-4 gap-4"
        {...animations.staggerChildren}
      >
        {[
          { label: 'Total Hasanat', value: formatHasanat(currentProgress.totalHasanat), icon: Star, color: theme.colors.maroon[600] },
          { label: 'Daily', value: formatHasanat(currentProgress.dailyHasanat), icon: Calendar, color: theme.colors.gold[600] },
          { label: 'Streak', value: `${currentProgress.streak} days`, icon: Flame, color: theme.colors.accent.crimson },
          { label: 'Level', value: currentProgress.level.toString(), icon: Trophy, color: theme.colors.accent.emerald }
        ].map((stat, index) => {
          const IconComponent = stat.icon;
          return (
            <motion.div
              key={stat.label}
              {...animations.fadeInUp}
              transition={{ delay: index * 0.1 }}
            >
              <SpiritualCard className="p-4 text-center" hover>
                <div 
                  className="p-2 rounded-lg mx-auto mb-2 w-fit"
                  style={{ 
                    backgroundColor: `${stat.color}20`,
                    color: stat.color
                  }}
                >
                  <IconComponent className="w-6 h-6" />
                </div>
                <div className="text-2xl font-bold mb-1" style={{ color: theme.colors.maroon[800] }}>
                  {stat.value}
                </div>
                <div className="text-sm" style={{ color: theme.colors.maroon[600] }}>
                  {stat.label}
                </div>
              </SpiritualCard>
            </motion.div>
          );
        })}
      </motion.div>

      {/* Tab Navigation */}
      <SpiritualCard className="p-1">
        <div className="flex space-x-1">
          {[
            { id: 'overview', label: 'Overview', icon: TrendingUp },
            { id: 'achievements', label: 'Achievements', icon: Trophy },
            { id: 'activities', label: 'Activities', icon: Clock }
          ].map(tab => {
            const IconComponent = tab.icon;
            return (
              <button
                key={tab.id}
                onClick={() => setSelectedTab(tab.id as any)}
                className={`flex-1 flex items-center justify-center space-x-2 px-4 py-3 rounded-lg font-medium transition-all ${
                  selectedTab === tab.id ? 'shadow-md' : ''
                }`}
                style={{
                  backgroundColor: selectedTab === tab.id ? theme.colors.maroon[600] : 'transparent',
                  color: selectedTab === tab.id ? 'white' : theme.colors.maroon[600]
                }}
              >
                <IconComponent className="w-4 h-4" />
                <span>{tab.label}</span>
              </button>
            );
          })}
        </div>
      </SpiritualCard>

      {/* Tab Content */}
      <AnimatePresence mode="wait">
        {selectedTab === 'overview' && (
          <motion.div
            key="overview"
            {...animations.fadeInUp}
            className="grid grid-cols-1 md:grid-cols-2 gap-6"
          >
            {/* Weekly Progress */}
            <SpiritualCard className="p-6">
              <h3 className="text-lg font-semibold mb-4" style={{ color: theme.colors.maroon[800] }}>
                Weekly Progress
              </h3>
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <span style={{ color: theme.colors.maroon[600] }}>This Week</span>
                  <span className="font-bold" style={{ color: theme.colors.maroon[800] }}>
                    {formatHasanat(currentProgress.weeklyHasanat)}
                  </span>
                </div>
                <div 
                  className="h-2 rounded-full"
                  style={{ backgroundColor: theme.colors.gold[200] }}
                >
                  <motion.div
                    className="h-full rounded-full"
                    style={{ background: theme.gradients.goldMaroon }}
                    initial={{ width: 0 }}
                    animate={{ width: '75%' }}
                    transition={{ duration: 1 }}
                  />
                </div>
                <p className="text-sm" style={{ color: theme.colors.maroon[500] }}>
                  75% of weekly goal achieved
                </p>
              </div>
            </SpiritualCard>

            {/* Recent Achievements */}
            <SpiritualCard className="p-6">
              <h3 className="text-lg font-semibold mb-4" style={{ color: theme.colors.maroon[800] }}>
                Recent Achievements
              </h3>
              <div className="space-y-3">
                {currentProgress.achievements
                  .filter(a => a.isUnlocked)
                  .slice(0, 3)
                  .map(achievement => {
                    const IconComponent = getIcon(achievement.icon);
                    return (
                      <motion.div
                        key={achievement.id}
                        className="flex items-center space-x-3 p-3 rounded-lg"
                        style={{ backgroundColor: theme.colors.gold[50] }}
                        whileHover={{ scale: 1.02 }}
                      >
                        <div 
                          className="p-2 rounded-lg"
                          style={{ 
                            backgroundColor: theme.colors.gold[600],
                            color: 'white'
                          }}
                        >
                          <IconComponent className="w-4 h-4" />
                        </div>
                        <div className="flex-1">
                          <div className="font-medium" style={{ color: theme.colors.maroon[800] }}>
                            {achievement.title}
                          </div>
                          <div className="text-sm" style={{ color: theme.colors.maroon[600] }}>
                            +{formatHasanat(achievement.hasanat)} hasanat
                          </div>
                        </div>
                      </motion.div>
                    );
                  })}
              </div>
            </SpiritualCard>
          </motion.div>
        )}

        {selectedTab === 'achievements' && (
          <motion.div
            key="achievements"
            {...animations.fadeInUp}
            className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
          >
            {currentProgress.achievements.map((achievement, index) => {
              const IconComponent = getIcon(achievement.icon);
              return (
                <motion.div
                  key={achievement.id}
                  {...animations.fadeInUp}
                  transition={{ delay: index * 0.1 }}
                >
                  <SpiritualCard 
                    className={`p-6 text-center transition-all ${
                      achievement.isUnlocked ? 'border-2' : 'opacity-60'
                    }`}
                    style={{
                      borderColor: achievement.isUnlocked ? theme.colors.gold[400] : 'transparent'
                    }}
                    hover={achievement.isUnlocked}
                  >
                    <div 
                      className="p-4 rounded-full mx-auto mb-4 w-fit"
                      style={{
                        backgroundColor: achievement.isUnlocked 
                          ? theme.colors.gold[600] 
                          : theme.colors.maroon[300],
                        color: 'white'
                      }}
                    >
                      <IconComponent className="w-8 h-8" />
                    </div>
                    <h4 className="font-semibold mb-2" style={{ color: theme.colors.maroon[800] }}>
                      {achievement.title}
                    </h4>
                    <p className="text-sm mb-3" style={{ color: theme.colors.maroon[600] }}>
                      {achievement.description}
                    </p>
                    <div 
                      className="text-lg font-bold"
                      style={{ color: achievement.isUnlocked ? theme.colors.gold[600] : theme.colors.maroon[400] }}
                    >
                      +{formatHasanat(achievement.hasanat)}
                    </div>
                    {achievement.isUnlocked && achievement.unlockedAt && (
                      <p className="text-xs mt-2" style={{ color: theme.colors.maroon[500] }}>
                        Unlocked {new Date(achievement.unlockedAt).toLocaleDateString()}
                      </p>
                    )}
                  </SpiritualCard>
                </motion.div>
              );
            })}
          </motion.div>
        )}

        {selectedTab === 'activities' && (
          <motion.div
            key="activities"
            {...animations.fadeInUp}
          >
            <SpiritualCard className="p-6">
              <h3 className="text-lg font-semibold mb-4" style={{ color: theme.colors.maroon[800] }}>
                Recent Activities
              </h3>
              <div className="space-y-4">
                {currentProgress.recentActivities.map((activity, index) => {
                  const IconComponent = getActivityIcon(activity.type);
                  const activityColor = getActivityColor(activity.type);
                  
                  return (
                    <motion.div
                      key={activity.id}
                      className="flex items-center space-x-4 p-4 rounded-lg cursor-pointer transition-all"
                      style={{ backgroundColor: theme.colors.milk[50] }}
                      whileHover={{ 
                        scale: 1.02,
                        backgroundColor: theme.colors.gold[50]
                      }}
                      onClick={() => onActivityClick?.(activity)}
                      {...animations.fadeInUp}
                      transition={{ delay: index * 0.05 }}
                    >
                      <div 
                        className="p-2 rounded-lg"
                        style={{ 
                          backgroundColor: `${activityColor}20`,
                          color: activityColor
                        }}
                      >
                        <IconComponent className="w-5 h-5" />
                      </div>
                      
                      <div className="flex-1">
                        <div className="font-medium" style={{ color: theme.colors.maroon[800] }}>
                          {activity.description}
                        </div>
                        <div className="text-sm" style={{ color: theme.colors.maroon[600] }}>
                          {new Date(activity.timestamp).toLocaleString()}
                          {activity.surahName && ` • ${activity.surahName}`}
                          {activity.ayahCount && ` • ${activity.ayahCount} ayahs`}
                        </div>
                      </div>
                      
                      <div 
                        className="font-bold text-lg"
                        style={{ color: activityColor }}
                      >
                        +{formatHasanat(activity.hasanat)}
                      </div>
                    </motion.div>
                  );
                })}
              </div>
            </SpiritualCard>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Level Up Animation */}
      <AnimatePresence>
        {showLevelUp && (
          <motion.div
            className="fixed inset-0 flex items-center justify-center z-50 pointer-events-none"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
          >
            <motion.div
              className="text-center p-8 rounded-2xl shadow-2xl"
              style={{ 
                background: theme.gradients.maroonGold,
                color: 'white'
              }}
              initial={{ scale: 0, rotate: -180 }}
              animate={{ scale: 1, rotate: 0 }}
              exit={{ scale: 0, rotate: 180 }}
              transition={{ type: 'spring', duration: 0.8 }}
            >
              <motion.div
                animate={{ rotate: 360 }}
                transition={{ duration: 2, repeat: Infinity, ease: 'linear' }}
              >
                <Crown className="w-16 h-16 mx-auto mb-4" />
              </motion.div>
              <h2 className="text-3xl font-bold mb-2">Level Up!</h2>
              <p className="text-xl">You've reached Level {currentProgress.level}!</p>
              <motion.div
                className="flex justify-center mt-4"
                animate={{ scale: [1, 1.2, 1] }}
                transition={{ duration: 0.5, repeat: Infinity }}
              >
                <Sparkles className="w-8 h-8" />
              </motion.div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </motion.div>
  );
};

export default HasanatTracker;