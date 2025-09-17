/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import createMiddleware from 'next-intl/middleware';
import { locales, defaultLocale } from './i18n';

/**
 * Next.js middleware for internationalization.
 * Handles locale detection and routing for Arabic and English.
 */
export default createMiddleware({
  // A list of all locales that are supported
  locales,

  // Used when no locale matches
  defaultLocale,

  // Locale detection strategy
  localeDetection: true,

  // Pathname prefixing strategy
  localePrefix: 'as-needed', // Only add prefix for non-default locale

  // Domains configuration (optional)
  // domains: [
  //   {
  //     domain: 'alfawz-ar.com',
  //     defaultLocale: 'ar'
  //   },
  //   {
  //     domain: 'alfawz.com',
  //     defaultLocale: 'en'
  //   }
  // ],

  // Alternate links configuration
  alternateLinks: true,

  // Path names configuration for localized routes
  pathnames: {
    '/': '/',
    '/dashboard': {
      en: '/dashboard',
      ar: '/لوحة-التحكم'
    },
    '/teacher-oversight': {
      en: '/teacher-oversight',
      ar: '/مراقبة-المعلم'
    },
    '/profile': {
      en: '/profile',
      ar: '/الملف-الشخصي'
    },
    '/settings': {
      en: '/settings',
      ar: '/الإعدادات'
    }
  }
});

/**
 * Matcher configuration for the middleware.
 * Applies to all routes except API routes, static files, and internal Next.js routes.
 */
export const config = {
  // Match only internationalized pathnames
  matcher: [
    // Enable a redirect to a matching locale at the root
    '/',

    // Set a cookie to remember the previous locale for
    // all requests that have a locale prefix
    '/(ar|en)/:path*',

    // Enable redirects that add missing locales
    // (e.g. `/pathnames` -> `/en/pathnames`)
    '/((?!_next|_vercel|.*\\..*).*)',

    // However, match all pathnames within `/[locale]`, optionally with a
    // pathname at the end
    '/([\\w-]+)?/users/(.+)'
  ]
};