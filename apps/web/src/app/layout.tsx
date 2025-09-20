/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React from 'react';
import type { Metadata } from 'next';
import './globals.css';
import { AuthProvider } from '@/contexts/AuthContext';
import Layout from '@/components/Layout';
import ServiceWorkerProvider from '@/components/ServiceWorkerProvider';
import { NextIntlClientProvider } from 'next-intl';
import { getLocale, getMessages } from 'next-intl/server';
import { getDirection, getFontClass } from '@/i18n';
import { SkipLink } from '@/lib/accessibility';

// Local fonts (use local font paths instead of next/font/google)
const geistSans = localFont({
  src: [
    { path: '../../public/fonts/geist-sans/Geist-100.ttf', weight: '100', style: 'normal' },
    { path: '../../public/fonts/geist-sans/Geist-200.ttf', weight: '200', style: 'normal' },
    { path: '../../public/fonts/geist-sans/Geist-300.ttf', weight: '300', style: 'normal' },
    { path: '../../public/fonts/geist-sans/Geist-400.ttf', weight: '400', style: 'normal' },
    { path: '../../public/fonts/geist-sans/Geist-500.ttf', weight: '500', style: 'normal' },
    { path: '../../public/fonts/geist-sans/Geist-600.ttf', weight: '600', style: 'normal' },
    { path: '../../public/fonts/geist-sans/Geist-700.ttf', weight: '700', style: 'normal' },
    { path: '../../public/fonts/geist-sans/Geist-800.ttf', weight: '800', style: 'normal' },
    { path: '../../public/fonts/geist-sans/Geist-900.ttf', weight: '900', style: 'normal' },
  ],
  variable: '--font-geist-sans',
  display: 'swap',
  preload: true,
});

const geistMono = localFont({
  src: [
    { path: '../../public/fonts/geist-mono/GeistMono-100.ttf', weight: '100', style: 'normal' },
    { path: '../../public/fonts/geist-mono/GeistMono-200.ttf', weight: '200', style: 'normal' },
    { path: '../../public/fonts/geist-mono/GeistMono-300.ttf', weight: '300', style: 'normal' },
    { path: '../../public/fonts/geist-mono/GeistMono-400.ttf', weight: '400', style: 'normal' },
    { path: '../../public/fonts/geist-mono/GeistMono-500.ttf', weight: '500', style: 'normal' },
    { path: '../../public/fonts/geist-mono/GeistMono-600.ttf', weight: '600', style: 'normal' },
    { path: '../../public/fonts/geist-mono/GeistMono-700.ttf', weight: '700', style: 'normal' },
    { path: '../../public/fonts/geist-mono/GeistMono-800.ttf', weight: '800', style: 'normal' },
    { path: '../../public/fonts/geist-mono/GeistMono-900.ttf', weight: '900', style: 'normal' },
  ],
  variable: '--font-geist-mono',
  display: 'swap',
  preload: true,
});

export const metadata: Metadata = {
  title: 'AlFawz Qur\'an Institute',
  description: 'Digital Qur\'an learning platform with interactive features',
};

/**
 * Root layout component that wraps the entire application.
 * Provides authentication context and consistent layout structure.
 */
export default async function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const locale = await getLocale();
  const messages = await getMessages();
  const direction = getDirection(locale as 'en' | 'ar');
  const fontClass = getFontClass(locale as 'en' | 'ar');

  return (
    <html lang={locale} dir={direction}>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="theme-color" content="#800020" />
        <meta name="description" content="AlFawz Qur'an Institute - Digital Qur'an learning platform" />
        
        {/* PWA Manifest */}
        <link rel="manifest" href="/manifest.json" />
        
        {/* Icons */}
        <link rel="icon" href="/icon-192x192.svg" type="image/svg+xml" />
        <link rel="apple-touch-icon" href="/icon-192x192.svg" />
        
        {/* Apple PWA */}
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="default" />
        <meta name="apple-mobile-web-app-title" content="AlFawz Qur'an" />
        
        {/* Microsoft PWA */}
        <meta name="msapplication-TileColor" content="#800020" />
        <meta name="msapplication-config" content="/browserconfig.xml" />
      </head>
      <body className={`${geistSans.variable} ${geistMono.variable} ${fontClass} antialiased`}>
        <SkipLink href="#main-content">Skip to main content</SkipLink>
        <SkipLink href="#navigation">Skip to navigation</SkipLink>
        <NextIntlClientProvider locale={locale} messages={messages}>
          <ServiceWorkerProvider>
            <AuthProvider>
              <Layout>
                {children}
              </Layout>
            </AuthProvider>
          </ServiceWorkerProvider>
        </NextIntlClientProvider>
      </body>
    </html>
  );
}
