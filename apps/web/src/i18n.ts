/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import { notFound } from 'next/navigation';
import { getRequestConfig } from 'next-intl/server';

// Can be imported from a shared config
export const locales = ['en', 'ar'] as const;
export type Locale = (typeof locales)[number];

/**
 * Configuration for next-intl internationalization.
 * Loads messages based on the current locale.
 */
export default getRequestConfig(async ({ locale }) => {
  // Validate that the incoming `locale` parameter is valid
  if (!locales.includes(locale as any)) notFound();

  return {
    messages: (await import(`../messages/${locale}.json`)).default,
    timeZone: 'Asia/Riyadh', // Default to Saudi Arabia timezone
    now: new Date(),
    formats: {
      dateTime: {
        short: {
          day: 'numeric',
          month: 'short',
          year: 'numeric'
        },
        long: {
          day: 'numeric',
          month: 'long',
          year: 'numeric',
          weekday: 'long'
        }
      },
      number: {
        precise: {
          maximumFractionDigits: 2
        }
      }
    }
  };
});

/**
 * Get the direction for a given locale.
 * @param {string} locale - The locale code
 * @returns {string} - 'rtl' for Arabic, 'ltr' for others
 */
export function getDirection(locale: string): 'ltr' | 'rtl' {
  return locale === 'ar' ? 'rtl' : 'ltr';
}

/**
 * Get the font class for a given locale.
 * @param {string} locale - The locale code
 * @returns {string} - Font class name
 */
export function getFontClass(locale: string): string {
  return locale === 'ar' ? 'font-arabic' : 'font-latin';
}

/**
 * Default locale configuration.
 */
export const defaultLocale: Locale = 'en';