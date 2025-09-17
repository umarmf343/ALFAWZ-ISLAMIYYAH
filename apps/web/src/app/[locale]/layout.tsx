/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import { NextIntlClientProvider } from 'next-intl';
import { getMessages } from 'next-intl/server';
import { notFound } from 'next/navigation';
import { locales } from '@/i18n';

type Props = {
  children: React.ReactNode;
  params: { locale: string };
};

/**
 * Locale-specific layout for internationalized routes.
 * Validates locale and provides messages to child components.
 */
export default async function LocaleLayout({
  children,
  params: { locale }
}: Props) {
  // Validate that the incoming `locale` parameter is valid
  if (!locales.includes(locale as any)) {
    notFound();
  }

  // Providing all messages to the client
  // side is the easiest way to get started
  const messages = await getMessages();

  return (
    <NextIntlClientProvider locale={locale} messages={messages}>
      {children}
    </NextIntlClientProvider>
  );
}

/**
 * Generate static params for all supported locales.
 * This enables static generation for each locale.
 */
export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}