/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React from 'react';
import Link from 'next/link';
import { useAuth } from '@/contexts/AuthContext';
import OfflineIndicator from './OfflineIndicator';
import { useTranslations } from 'next-intl';
import LanguageSwitcher from './LanguageSwitcher';
import { usePathname } from 'next/navigation';

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
  const pathname = usePathname();
  const [isMobileNavOpen, setIsMobileNavOpen] = React.useState(false);

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

  const navLinks = React.useMemo(() => {
    const links = [
      { href: '/docs', label: t('docs') },
      { href: '/dashboard', label: t('dashboard') },
    ];

    if (isStudent) {
      links.push(
        { href: '/classes', label: t('myClasses') },
        { href: '/assignments', label: t('assignments') },
        { href: '/leaderboard', label: t('leaderboard') }
      );
    }

    if (isTeacher) {
      links.push(
        { href: '/teacher/classes', label: t('myClasses') },
        { href: '/teacher/assignments', label: t('assignments') },
        { href: '/teacher/submissions', label: t('submissions') },
        { href: '/teacher-oversight', label: t('teacherOversight') }
      );
    }

    return links.filter((link, index) => links.findIndex(item => item.href === link.href) === index);
  }, [isStudent, isTeacher, t]);

  const toggleMobileNav = () => {
    setIsMobileNavOpen(prev => !prev);
  };

  React.useEffect(() => {
    setIsMobileNavOpen(false);
  }, [pathname]);

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
              aria-label="AlFawz Qur&apos;an Institute - Go to homepage"
            >
              <div className="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center">
                <span className="text-white font-bold text-lg">ق</span>
              </div>
              <span className="text-xl font-bold text-gray-900">
                AlFawz Qur&apos;an Institute
              </span>
            </Link>

            {/* Navigation */}
            {isAuthenticated && (
              <nav id="navigation" className="hidden md:flex space-x-8" role="navigation" aria-label="Main navigation">
                {navLinks.map(link => (
                  <Link
                    key={link.href}
                    href={link.href}
                    className="text-gray-700 hover:text-green-600 px-3 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                  >
                    {link.label}
                  </Link>
                ))}
              </nav>
            )}

            {/* User Menu */}
            <div className="flex items-center space-x-4">
              {isAuthenticated && (
                <button
                  type="button"
                  onClick={toggleMobileNav}
                  className="md:hidden inline-flex items-center justify-center rounded-md p-2 text-gray-600 hover:text-green-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                  aria-controls="mobile-navigation"
                  aria-expanded={isMobileNavOpen}
                >
                  <span className="sr-only">
                    {isMobileNavOpen ? t('closeMenu') : t('openMenu')}
                  </span>
                  {isMobileNavOpen ? (
                    <svg className="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor" aria-hidden="true">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  ) : (
                    <svg className="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor" aria-hidden="true">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 5.25h16.5M3.75 12h16.5m-16.5 6.75h16.5" />
                    </svg>
                  )}
                </button>
              )}
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

      {isAuthenticated && (
        <div
          id="mobile-navigation"
          className={`${isMobileNavOpen ? 'block' : 'hidden'} md:hidden border-b border-gray-200 bg-white`}
        >
          <nav className="space-y-1 px-4 py-4" role="navigation" aria-label={t('mainNavigation')}>
            {navLinks.map(link => (
              <Link
                key={link.href}
                href={link.href}
                onClick={() => setIsMobileNavOpen(false)}
                className="block rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
              >
                {link.label}
              </Link>
            ))}
          </nav>
        </div>
      )}

      {/* Main Content */}
      <main id="main-content" className="flex-1" role="main" aria-label="Main content">
        {children}
      </main>

      {/* Footer */}
      <footer className="bg-white border-t" role="contentinfo">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="text-center text-gray-500 text-sm">
            <p>&copy; 2025 AlFawz Qur&apos;an Institute. All rights reserved.</p>
            <p className="mt-2">
              Built with ❤️ for the Ummah • May Allah accept our efforts
            </p>
          </div>
        </div>
      </footer>
    </div>
  );
}