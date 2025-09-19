/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React from 'react';
import { useLocale } from 'next-intl';
import { useRouter, usePathname } from 'next-intl/client';
import { useSearchParams } from 'next/navigation';
import { locales, type Locale } from '@/i18n';

/**
 * Language switcher component for toggling between Arabic and English.
 * Provides a dropdown or toggle interface for language selection.
 */
const LanguageSwitcher: React.FC = () => {
  const locale = useLocale() as Locale;
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  const getLocalizedPath = React.useCallback(
    () => ({
      pathname,
      query: Object.fromEntries(searchParams.entries()),
    }),
    [pathname, searchParams]
  );

  /**
   * Handle language change by navigating to the same path with new locale.
   * @param {Locale} newLocale - The target locale to switch to
   */
  const handleLanguageChange = (newLocale: Locale) => {
    if (newLocale === locale) return;
    const url = getLocalizedPath();

    router.replace(url, { locale: newLocale });

    if (typeof window !== 'undefined') {
      const hash = window.location.hash;
      if (hash) {
        requestAnimationFrame(() => {
          window.location.hash = hash;
        });
      }
    }
  };

  /**
   * Get the display name for a locale.
   * @param {Locale} locale - The locale code
   * @returns {string} - The display name
   */
  const getLocaleDisplayName = (locale: Locale): string => {
    const names: Record<Locale, string> = {
      en: 'English',
      ar: 'العربية'
    };
    return names[locale];
  };

  /**
   * Get the flag emoji for a locale.
   * @param {Locale} locale - The locale code
   * @returns {string} - The flag emoji
   */
  const getLocaleFlag = (locale: Locale): string => {
    const flags: Record<Locale, string> = {
      en: '🇺🇸',
      ar: '🇸🇦'
    };
    return flags[locale];
  };

  return (
    <div className="relative inline-block text-left" role="group" aria-label="Language selection">
      {/* Simple Toggle Button */}
      <button
        onClick={() => handleLanguageChange(locale === 'en' ? 'ar' : 'en')}
        className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-maroon-500 transition-colors duration-200"
        aria-label={`Switch to ${locale === 'en' ? 'Arabic' : 'English'}`}
        aria-pressed={locale === 'en' ? 'false' : 'true'}
      >
        <span className="mr-2" role="img" aria-label={`${getLocaleDisplayName(locale)} flag`}>{getLocaleFlag(locale)}</span>
        <span className="hidden sm:inline">{getLocaleDisplayName(locale)}</span>
        <svg
          className="ml-2 -mr-1 h-4 w-4"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            fillRule="evenodd"
            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
            clipRule="evenodd"
          />
        </svg>
      </button>
    </div>
  );
};

/**
 * Compact language switcher for mobile or constrained spaces.
 * Shows only flags without text labels.
 */
export const CompactLanguageSwitcher: React.FC = () => {
  const locale = useLocale() as Locale;
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();

  const getLocalizedPath = React.useCallback(
    () => ({
      pathname,
      query: Object.fromEntries(searchParams.entries()),
    }),
    [pathname, searchParams]
  );

  const handleLanguageChange = (newLocale: Locale) => {
    if (newLocale === locale) return;
    const url = getLocalizedPath();

    router.replace(url, { locale: newLocale });

    if (typeof window !== 'undefined') {
      const hash = window.location.hash;
      if (hash) {
        requestAnimationFrame(() => {
          window.location.hash = hash;
        });
      }
    }
  };

  return (
    <div className="flex items-center space-x-1" role="group" aria-label="Language selection">
      {locales.map((loc) => (
        <button
          key={loc}
          onClick={() => handleLanguageChange(loc)}
          className={`p-2 rounded-md text-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-maroon-500 focus:ring-offset-2 ${
            locale === loc
              ? 'bg-maroon-100 text-maroon-700 ring-2 ring-maroon-500'
              : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'
          }`}
          aria-label={`Switch to ${loc === 'en' ? 'English' : 'Arabic'}`}
          aria-pressed={locale === loc}
        >
          <span role="img" aria-label={`${loc === 'en' ? 'United States' : 'Saudi Arabia'} flag`}>
            {loc === 'en' ? '🇺🇸' : '🇸🇦'}
          </span>
        </button>
      ))}
    </div>
  );
};

/**
 * Dropdown language switcher with all available locales.
 * Provides a more comprehensive interface for multiple languages.
 */
export const DropdownLanguageSwitcher: React.FC = () => {
  const [isOpen, setIsOpen] = React.useState(false);
  const locale = useLocale() as Locale;
  const router = useRouter();
  const pathname = usePathname();
  const dropdownRef = React.useRef<HTMLDivElement>(null);
  const searchParams = useSearchParams();

  const getLocalizedPath = React.useCallback(
    () => ({
      pathname,
      query: Object.fromEntries(searchParams.entries()),
    }),
    [pathname, searchParams]
  );

  // Close dropdown when clicking outside or pressing Escape
  React.useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    };

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, []);

  const handleLanguageChange = (newLocale: Locale) => {
    if (newLocale === locale) {
      setIsOpen(false);
      return;
    }

    const url = getLocalizedPath();

    router.replace(url, { locale: newLocale });

    if (typeof window !== 'undefined') {
      const hash = window.location.hash;
      if (hash) {
        requestAnimationFrame(() => {
          window.location.hash = hash;
        });
      }
    }
    setIsOpen(false);
  };

  const getLocaleDisplayName = (locale: Locale): string => {
    const names: Record<Locale, string> = {
      en: 'English',
      ar: 'العربية'
    };
    return names[locale];
  };

  const getLocaleFlag = (locale: Locale): string => {
    const flags: Record<Locale, string> = {
      en: '🇺🇸',
      ar: '🇸🇦'
    };
    return flags[locale];
  };

  return (
    <div className="relative inline-block text-left" ref={dropdownRef}>
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-maroon-500 transition-colors duration-200"
        aria-expanded={isOpen}
        aria-haspopup="menu"
        aria-label={`Current language: ${getLocaleDisplayName(locale)}. Click to change language`}
      >
        <span className="mr-2" role="img" aria-label={`${getLocaleDisplayName(locale)} flag`}>{getLocaleFlag(locale)}</span>
        <span>{getLocaleDisplayName(locale)}</span>
        <svg
          className={`ml-2 -mr-1 h-4 w-4 transition-transform duration-200 ${
            isOpen ? 'rotate-180' : ''
          }`}
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            fillRule="evenodd"
            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
            clipRule="evenodd"
          />
        </svg>
      </button>

      {isOpen && (
        <div className="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
          <div className="py-1" role="menu" aria-orientation="vertical" aria-label="Language options">
            {locales.map((loc) => (
              <button
                key={loc}
                onClick={() => handleLanguageChange(loc)}
                className={`w-full text-left px-4 py-2 text-sm flex items-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-maroon-500 focus:ring-inset ${
                  locale === loc
                    ? 'bg-maroon-100 text-maroon-900'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                }`}
                role="menuitem"
                aria-current={locale === loc ? 'true' : undefined}
              >
                <span className="mr-3" role="img" aria-label={`${getLocaleDisplayName(loc)} flag`}>{getLocaleFlag(loc)}</span>
                <span>{getLocaleDisplayName(loc)}</span>
                {locale === loc && (
                  <svg
                    className="ml-auto h-4 w-4 text-maroon-600"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                  >
                    <path
                      fillRule="evenodd"
                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                      clipRule="evenodd"
                    />
                  </svg>
                )}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default LanguageSwitcher;