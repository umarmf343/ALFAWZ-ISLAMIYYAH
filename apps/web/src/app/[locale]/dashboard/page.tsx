/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import { redirect } from 'next/navigation';

type LocalizedParams = {
  params?: { locale?: string };
  searchParams?: Record<string, unknown>;
};

/**
 * Localized dashboard page.
 * Redirects to the main dashboard with proper locale handling.
 */
export default function LocalizedDashboard({}: LocalizedParams) {
  // Redirect to the main dashboard page
  // The middleware will handle locale prefixing
  redirect('/dashboard');
}

/**
 * Generate metadata for the localized dashboard page.
 */
export function generateMetadata({ params }: LocalizedParams) {
  const locale = typeof params?.locale === 'string' ? params.locale : 'en';
  const titles = {
    en: 'Dashboard - AlFawz Qur\'an Institute',
    ar: 'لوحة التحكم - معهد الفوز للقرآن'
  };

  const descriptions = {
    en: 'Your personal Qur\'an learning dashboard with progress tracking and recommendations',
    ar: 'لوحة التحكم الشخصية لتعلم القرآن مع تتبع التقدم والتوصيات'
  };

  return {
    title: titles[locale as keyof typeof titles] || titles.en,
    description: descriptions[locale as keyof typeof descriptions] || descriptions.en,
  };
}