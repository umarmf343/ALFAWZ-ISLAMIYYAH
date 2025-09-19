'use client';

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import type { LucideIcon } from 'lucide-react';
import {
  BookOpen,
  Users,
  Award,
  Calendar,
  Bell,
  Settings,
  User,
  LogOut,
  Menu,
  X,
  Home,
  Target,
  TrendingUp,
  Clock,
  Star,
  Heart,
  Sparkles
} from 'lucide-react';
import { SpiritualThemeProvider, useSpiritualTheme, SpiritualCard, SpiritualButton } from '@/components/providers/SpiritualThemeProvider';
import { StudentAssignmentDashboard } from '@/components/assignment/StudentAssignmentDashboard';
import { TeacherAssignmentPanel } from '@/components/assignment/TeacherAssignmentPanel';
import { HasanatTracker } from '@/components/gamification/HasanatTracker';
import { AIFeedbackPanel } from '@/components/assignment/AIFeedbackPanel';

/**
 * User interface for authentication context.
 */
interface User {
  id: string;
  name: string;
  email: string;
  role: 'student' | 'teacher' | 'admin';
  avatar?: string;
  level?: number;
  hasanat?: number;
}

/**
 * Navigation item interface for sidebar menu.
 */
type DashboardSectionComponent = React.ComponentType<{ userId?: string }>;

interface NavItem {
  id: string;
  label: string;
  icon: LucideIcon;
  component: DashboardSectionComponent;
  roles: Array<User['role']>;
}

/**
 * Props for MainDashboard component.
 */
interface MainDashboardProps {
  user?: User;
  onLogout?: () => void;
  className?: string;
}

/**
 * Main dashboard component with spiritual theme integration.
 * Provides navigation and content management for the Al-Fawz Qur'an Institute.
 */
const MainDashboardContent: React.FC<MainDashboardProps> = ({
  user,
  onLogout,
  className = ''
}) => {
  const { theme, animations, styles } = useSpiritualTheme();
  const [activeTab, setActiveTab] = useState('dashboard');
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [notifications, setNotifications] = useState(3);
  const [isDesktop, setIsDesktop] = useState(false);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    const handleResize = () => {
      setIsDesktop(window.innerWidth >= 768);
    };

    handleResize();

    window.addEventListener('resize', handleResize);
    return () => {
      window.removeEventListener('resize', handleResize);
    };
  }, []);

  // Mock user data if not provided
  const currentUser: User = user || {
    id: 'user-1',
    name: 'Ahmad Al-Qari',
    email: 'ahmad@alfawz.edu',
    role: 'student',
    level: 12,
    hasanat: 12450
  };

  // Navigation items based on user role
  const navItems: NavItem[] = [
    {
      id: 'dashboard',
      label: 'Dashboard',
      icon: Home,
      component: DashboardOverview,
      roles: ['student', 'teacher', 'admin']
    },
    {
      id: 'assignments',
      label: currentUser.role === 'teacher' ? 'Manage Assignments' : 'My Assignments',
      icon: Target,
      component: currentUser.role === 'teacher' ? TeacherAssignmentPanel : StudentAssignmentDashboard,
      roles: ['student', 'teacher']
    },
    {
      id: 'hasanat',
      label: 'Hasanat Tracker',
      icon: Award,
      component: HasanatTracker,
      roles: ['student', 'teacher']
    },
    {
      id: 'classes',
      label: currentUser.role === 'teacher' ? 'My Classes' : 'My Class',
      icon: Users,
      component: ClassesView,
      roles: ['student', 'teacher']
    },
    {
      id: 'quran',
      label: 'Qur\'an Study',
      icon: BookOpen,
      component: QuranStudyView,
      roles: ['student', 'teacher']
    }
  ];

  // Filter navigation items by user role
  const availableNavItems = navItems.filter(item => 
    item.roles.includes(currentUser.role)
  );

  /**
   * Handle navigation item click.
   */
  const handleNavClick = (itemId: string) => {
    setActiveTab(itemId);
    setSidebarOpen(false);
  };

  /**
   * Get active component to render.
   */
  const getActiveComponent = () => {
    const activeItem = availableNavItems.find(item => item.id === activeTab);
    if (!activeItem) return <DashboardOverview />;
    
    const Component = activeItem.component;
    return <Component userId={currentUser.id} />;
  };

  return (
    <div className={`min-h-screen flex ${className}`} style={{ backgroundColor: theme.colors.milk[50] }}>
      {/* Sidebar */}
      <AnimatePresence>
        {(sidebarOpen || isDesktop) && (
          <motion.aside
            className="fixed md:relative inset-y-0 left-0 z-50 w-64 flex flex-col"
            style={{ backgroundColor: theme.colors.maroon[900] }}
            initial={{ x: -256 }}
            animate={{ x: 0 }}
            exit={{ x: -256 }}
            transition={{ type: 'spring', damping: 25, stiffness: 200 }}
          >
            {/* Sidebar Header */}
            <div className="p-6 border-b" style={{ borderColor: theme.colors.maroon[800] }}>
              <div className="flex items-center justify-between">
                <motion.div 
                  className="flex items-center space-x-3"
                  {...animations.fadeInUp}
                >
                  <div 
                    className="p-2 rounded-lg"
                    style={{ background: theme.gradients.goldMaroon }}
                  >
                    <BookOpen className="w-6 h-6 text-white" />
                  </div>
                  <div>
                    <h1 className="text-lg font-bold text-white">Al-Fawz</h1>
                    <p className="text-xs" style={{ color: theme.colors.gold[300] }}>
                      Qur&apos;an Institute
                    </p>
                  </div>
                </motion.div>
                
                <button
                  onClick={() => setSidebarOpen(false)}
                  className="md:hidden text-white hover:text-gold-300 transition-colors"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>
            </div>

            {/* User Profile */}
            <div className="p-6 border-b" style={{ borderColor: theme.colors.maroon[800] }}>
              <motion.div 
                className="flex items-center space-x-3"
                {...animations.slideInLeft}
              >
                <div 
                  className="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold"
                  style={{ background: theme.gradients.maroonGold }}
                >
                  {currentUser.name.charAt(0)}
                </div>
                <div className="flex-1">
                  <h3 className="font-semibold text-white">{currentUser.name}</h3>
                  <p className="text-sm" style={{ color: theme.colors.gold[300] }}>
                    {currentUser.role.charAt(0).toUpperCase() + currentUser.role.slice(1)}
                  </p>
                  {currentUser.level && (
                    <div className="flex items-center space-x-1 mt-1">
                      <Star className="w-3 h-3" style={{ color: theme.colors.gold[400] }} />
                      <span className="text-xs" style={{ color: theme.colors.gold[400] }}>
                        Level {currentUser.level}
                      </span>
                    </div>
                  )}
                </div>
              </motion.div>
            </div>

            {/* Navigation */}
            <nav className="flex-1 p-4 space-y-2">
              {availableNavItems.map((item, index) => {
                const IconComponent = item.icon;
                const isActive = activeTab === item.id;
                
                return (
                  <motion.button
                    key={item.id}
                    onClick={() => handleNavClick(item.id)}
                    className={`w-full flex items-center space-x-3 px-4 py-3 rounded-lg font-medium transition-all ${
                      isActive ? 'shadow-lg' : ''
                    }`}
                    style={{
                      backgroundColor: isActive ? theme.colors.gold[600] : 'transparent',
                      color: isActive ? 'white' : theme.colors.gold[300]
                    }}
                    whileHover={{ 
                      backgroundColor: isActive ? theme.colors.gold[600] : theme.colors.maroon[800],
                      scale: 1.02
                    }}
                    whileTap={{ scale: 0.98 }}
                    {...animations.fadeInUp}
                    transition={{ delay: index * 0.05 }}
                  >
                    <IconComponent className="w-5 h-5" />
                    <span>{item.label}</span>
                  </motion.button>
                );
              })}
            </nav>

            {/* Sidebar Footer */}
            <div className="p-4 border-t" style={{ borderColor: theme.colors.maroon[800] }}>
              <button
                onClick={onLogout}
                className="w-full flex items-center space-x-3 px-4 py-3 rounded-lg font-medium transition-colors"
                style={{ color: theme.colors.gold[300] }}
              >
                <LogOut className="w-5 h-5" />
                <span>Sign Out</span>
              </button>
            </div>
          </motion.aside>
        )}
      </AnimatePresence>

      {/* Main Content */}
      <div className="flex-1 flex flex-col min-h-screen">
        {/* Top Bar */}
        <header 
          className="flex items-center justify-between p-4 border-b shadow-sm"
          style={{ 
            backgroundColor: 'white',
            borderColor: theme.colors.gold[200]
          }}
        >
          <div className="flex items-center space-x-4">
            <button
              onClick={() => setSidebarOpen(true)}
              className="md:hidden p-2 rounded-lg transition-colors"
              style={{
                color: theme.colors.maroon[600],
                ':hover': { backgroundColor: theme.colors.maroon[50] }
              }}
            >
              <Menu className="w-6 h-6" />
            </button>
            
            <motion.h2 
              className="text-xl font-semibold"
              style={{ color: theme.colors.maroon[800] }}
              {...animations.fadeInUp}
            >
              {availableNavItems.find(item => item.id === activeTab)?.label || 'Dashboard'}
            </motion.h2>
          </div>

          <div className="flex items-center space-x-4">
            {/* Notifications */}
            <motion.button
              className="relative p-2 rounded-lg transition-colors"
              style={{
                color: theme.colors.maroon[600],
                ':hover': { backgroundColor: theme.colors.maroon[50] }
              }}
              whileHover={{ scale: 1.05 }}
              whileTap={{ scale: 0.95 }}
            >
              <Bell className="w-6 h-6" />
              {notifications > 0 && (
                <motion.span
                  className="absolute -top-1 -right-1 w-5 h-5 rounded-full text-xs font-bold flex items-center justify-center text-white"
                  style={{ backgroundColor: theme.colors.accent.crimson }}
                  initial={{ scale: 0 }}
                  animate={{ scale: 1 }}
                  transition={{ type: 'spring' }}
                >
                  {notifications}
                </motion.span>
              )}
            </motion.button>

            {/* Settings */}
            <motion.button
              className="p-2 rounded-lg transition-colors"
              style={{
                color: theme.colors.maroon[600],
                ':hover': { backgroundColor: theme.colors.maroon[50] }
              }}
              whileHover={{ scale: 1.05, rotate: 90 }}
              whileTap={{ scale: 0.95 }}
            >
              <Settings className="w-6 h-6" />
            </motion.button>
          </div>
        </header>

        {/* Page Content */}
        <main className="flex-1 p-6 overflow-y-auto">
          <AnimatePresence mode="wait">
            <motion.div
              key={activeTab}
              {...animations.pageTransition}
            >
              {getActiveComponent()}
            </motion.div>
          </AnimatePresence>
        </main>
      </div>

      {/* Mobile Sidebar Overlay */}
      {sidebarOpen && (
        <motion.div
          className="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          onClick={() => setSidebarOpen(false)}
        />
      )}
    </div>
  );
};

/**
 * Dashboard overview component.
 */
const DashboardOverview: React.FC<{ userId?: string }> = ({ userId }) => {
  const { theme, animations } = useSpiritualTheme();
  
  return (
    <motion.div className="space-y-6" {...animations.pageTransition}>
      {/* Welcome Section */}
      <SpiritualCard className="p-6" glow>
        <motion.div {...animations.fadeInUp}>
          <div className="flex items-center space-x-4 mb-4">
            <div 
              className="p-3 rounded-full"
              style={{ background: theme.gradients.maroonGold }}
            >
              <Heart className="w-8 h-8 text-white" />
            </div>
            <div>
              <h1 className="text-2xl font-bold" style={{ color: theme.colors.maroon[800] }}>
                Assalamu Alaikum
              </h1>
              <p style={{ color: theme.colors.maroon[600] }}>
                Welcome back to your Qur&apos;an learning journey
              </p>
            </div>
          </div>
          
          <motion.div
            className="flex items-center space-x-2 text-sm"
            style={{ color: theme.colors.gold[600] }}
            animate={{ opacity: [0.7, 1, 0.7] }}
            transition={{ duration: 2, repeat: Infinity }}
          >
            <Sparkles className="w-4 h-4" />
            <span>May Allah bless your studies today</span>
          </motion.div>
        </motion.div>
      </SpiritualCard>

      {/* Quick Stats */}
      <motion.div 
        className="grid grid-cols-1 md:grid-cols-3 gap-6"
        {...animations.staggerChildren}
      >
        {[
          { label: 'Today\'s Progress', value: '3 Assignments', icon: Target, color: theme.colors.maroon[600] },
          { label: 'Hasanat Earned', value: '1,240', icon: Award, color: theme.colors.gold[600] },
          { label: 'Streak', value: '7 Days', icon: TrendingUp, color: theme.colors.accent.emerald }
        ].map((stat, index) => {
          const IconComponent = stat.icon;
          return (
            <motion.div
              key={stat.label}
              {...animations.fadeInUp}
              transition={{ delay: index * 0.1 }}
            >
              <SpiritualCard className="p-6 text-center" hover>
                <div 
                  className="p-3 rounded-full mx-auto mb-4 w-fit"
                  style={{ 
                    backgroundColor: `${stat.color}20`,
                    color: stat.color
                  }}
                >
                  <IconComponent className="w-8 h-8" />
                </div>
                <div className="text-2xl font-bold mb-2" style={{ color: theme.colors.maroon[800] }}>
                  {stat.value}
                </div>
                <div style={{ color: theme.colors.maroon[600] }}>
                  {stat.label}
                </div>
              </SpiritualCard>
            </motion.div>
          );
        })}
      </motion.div>

      {/* Recent Activity */}
      <SpiritualCard className="p-6">
        <h3 className="text-lg font-semibold mb-4" style={{ color: theme.colors.maroon[800] }}>
          Recent Activity
        </h3>
        <div className="space-y-3">
          {[
            { action: 'Completed Surah Al-Fatiha recitation', time: '2 hours ago', hasanat: 120 },
            { action: 'Submitted Assignment: Tajweed Practice', time: '1 day ago', hasanat: 200 },
            { action: 'Achieved 7-day streak milestone', time: '2 days ago', hasanat: 500 }
          ].map((activity, index) => (
            <motion.div
              key={index}
              className="flex items-center justify-between p-3 rounded-lg"
              style={{ backgroundColor: theme.colors.milk[50] }}
              {...animations.fadeInUp}
              transition={{ delay: index * 0.1 }}
            >
              <div>
                <div className="font-medium" style={{ color: theme.colors.maroon[800] }}>
                  {activity.action}
                </div>
                <div className="text-sm" style={{ color: theme.colors.maroon[600] }}>
                  {activity.time}
                </div>
              </div>
              <div 
                className="font-bold"
                style={{ color: theme.colors.gold[600] }}
              >
                +{activity.hasanat}
              </div>
            </motion.div>
          ))}
        </div>
      </SpiritualCard>
    </motion.div>
  );
};

/**
 * Placeholder component for classes view.
 */
const ClassesView: React.FC<{ userId?: string }> = () => {
  const { theme } = useSpiritualTheme();
  
  return (
    <SpiritualCard className="p-8 text-center">
      <Users className="w-16 h-16 mx-auto mb-4" style={{ color: theme.colors.maroon[400] }} />
      <h3 className="text-xl font-semibold mb-2" style={{ color: theme.colors.maroon[800] }}>
        Classes Management
      </h3>
      <p style={{ color: theme.colors.maroon[600] }}>
        Class management features coming soon...
      </p>
    </SpiritualCard>
  );
};

/**
 * Placeholder component for Quran study view.
 */
const QuranStudyView: React.FC<{ userId?: string }> = () => {
  const { theme } = useSpiritualTheme();
  
  return (
    <SpiritualCard className="p-8 text-center">
      <BookOpen className="w-16 h-16 mx-auto mb-4" style={{ color: theme.colors.maroon[400] }} />
      <h3 className="text-xl font-semibold mb-2" style={{ color: theme.colors.maroon[800] }}>
        Qur&apos;an Study
      </h3>
      <p style={{ color: theme.colors.maroon[600] }}>
        Interactive Qur&apos;an study features coming soon...
      </p>
    </SpiritualCard>
  );
};

/**
 * Main dashboard with spiritual theme provider wrapper.
 */
export const MainDashboard: React.FC<MainDashboardProps> = (props) => {
  return (
    <SpiritualThemeProvider>
      <MainDashboardContent {...props} />
    </SpiritualThemeProvider>
  );
};

export default MainDashboard;