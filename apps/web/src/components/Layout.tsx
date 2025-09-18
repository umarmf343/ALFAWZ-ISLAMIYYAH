/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React from 'react';
import Link from 'next/link';
import { useAuth } from '@/contexts/AuthContext';
import { formatHasanat, getHasanatBadge } from '@/lib/hasanat';
import OfflineIndicator from './OfflineIndicator';
import { useTranslations } from 'next-intl';
import LanguageSwitcher from './LanguageSwitcher';

interface LayoutProps {
  children: React.ReactNode;
}

/**
 * Main layout component with navigation and user info.
 * Provides consistent header, navigation, and footer across all pages.
 */
export default function Layout({ children }: LayoutProps) {
  const { user, logout, isAuthenticated, isTeacher, isStudent } = useAuth();
  const t = useTranslations('navigation');

  /**
   * Handle user logout
   */
  const handleLogout = async () => {
    try {
      await logout();
    } catch (error) {
      console.error('Logout failed:', error);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b" role="banner">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            {/* Logo */}
            <Link 
              href="/" 
              className="flex items-center space-x-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 rounded-md px-2 py-1"
              aria-label="AlFawz Qur'an Institute - Go to homepage"
            >
              <div className="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center">
                <span className="text-white font-bold text-lg">ق</span>
              </div>
              <span className="text-xl font-bold text-gray-900">
                AlFawz Qur'an Institute
              </span>
            </Link>

            {/* Navigation */}
            {isAuthenticated && (
              <nav id="navigation" className="hidden md:flex space-x-8" role="navigation" aria-label="Main navigation">
                <Link
                  href="/docs"
                  className="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                >
                  {t('docs')}
                </Link>
                <Link
                  href="/dashboard"
                  className="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                >
                  {t('dashboard')}
                </Link>
                
                {isStudent && (
                  <>
                    <Link
                      href="/classes"
                      className="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                      My Classes
                    </Link>
                    <Link
                      href="/assignments"
                      className="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                      Assignments
                    </Link>
                    <Link
                      href="/leaderboard"
                      className="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                      Leaderboard
                    </Link>
                  </>
                )}
                
                {isTeacher && (
                  <>
                    <Link
                      href="/teacher/classes"
                      className="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                      My Classes
                    </Link>
                    <Link
                      href="/teacher/assignments"
                      className="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                      Assignments
                    </Link>
                    <Link
                      href="/teacher/submissions"
                      className="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                      Submissions
                    </Link>
                    <Link
                      href="/teacher-oversight"
                      className="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                      {t('teacherOversight')}
                    </Link>
                  </>
                )}
              </nav>
            )}

            {/* User Menu */}
            <div className="flex items-center space-x-4">
              <LanguageSwitcher />
              {/* Offline Indicator */}
              {isAuthenticated && <OfflineIndicator />}
              {isAuthenticated && user ? (
                <div className="flex items-center space-x-4">
                  {/* User Info */}
                  <div className="text-right">
                    <div className="text-sm font-medium text-gray-900">
                      {user.name}
                    </div>
                    <div className="text-xs text-gray-500 capitalize">
                      {user.role}
                    </div>
                  </div>
                  
                  {/* Avatar */}
                  <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                    <span className="text-green-600 font-medium text-sm">
                      {user.name.charAt(0).toUpperCase()}
                    </span>
                  </div>
                  
                  {/* Logout Button */}
                  <button
                    onClick={handleLogout}
                    className="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                    aria-label="Sign out of your account"
                  >
                    {t('logout')}
                  </button>
                </div>
              ) : (
                <div className="flex items-center space-x-4" role="group" aria-label="Authentication options">
                  <Link
                    href="/docs"
                    className="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                  >
                    {t('docs')}
                  </Link>
                  <Link
                    href="/login"
                    className="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                  >
                    {t('login')}
                  </Link>
                  <Link
                    href="/register"
                    className="bg-green-600 text-white hover:bg-green-700 px-4 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                  >
                    {t('register')}
                  </Link>
                </div>
              )}
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main id="main-content" className="flex-1" role="main" aria-label="Main content">
        {children}
      </main>

      {/* Footer */}
      <footer className="bg-white border-t" role="contentinfo">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="text-center text-gray-500 text-sm">
            <p>&copy; 2025 AlFawz Qur'an Institute. All rights reserved.</p>
            <p className="mt-2">
              Built with ❤️ for the Ummah • May Allah accept our efforts
            </p>
          </div>
        </div>
      </footer>
    </div>
  );
}