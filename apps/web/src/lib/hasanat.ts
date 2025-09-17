/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

/**
 * Hasanat calculation utilities for Arabic Qur'an text.
 * Hasanat = spiritual rewards earned for reciting Qur'an.
 */

/**
 * Compute hasanat for a given Arabic string.
 * Hasanat = number of Arabic letters * 10 (ignores diacritics and whitespace).
 * @param text Arabic text input
 * @returns number total hasanat
 */
export function computeHasanat(text: string): number {
  if (!text || typeof text !== 'string') {
    return 0;
  }

  // Normalize text and remove diacritics (tashkeel)
  const base = text
    .normalize('NFKD')
    .replace(/[\u064B-\u065F\u0670\u06D6-\u06ED]/g, '');
  
  // Count Arabic letters (excluding spaces, punctuation, and non-Arabic characters)
  const letters = base.match(/[\u0621-\u064A]/g)?.length ?? 0;
  
  return letters * 10;
}

/**
 * Format hasanat number with appropriate suffix
 * @param hasanat number of hasanat
 * @returns formatted string with suffix
 */
export function formatHasanat(hasanat: number): string {
  if (hasanat >= 1000000) {
    return `${(hasanat / 1000000).toFixed(1)}M`;
  }
  if (hasanat >= 1000) {
    return `${(hasanat / 1000).toFixed(1)}K`;
  }
  return hasanat.toString();
}

/**
 * Calculate hasanat for a range of ayahs
 * @param ayahs array of ayah objects with text_uthmani property
 * @returns total hasanat for all ayahs
 */
export function calculateAyahRangeHasanat(ayahs: { text_uthmani: string }[]): number {
  return ayahs.reduce((total, ayah) => {
    return total + computeHasanat(ayah.text_uthmani);
  }, 0);
}

/**
 * Get hasanat badge based on total hasanat earned
 * @param totalHasanat total hasanat accumulated
 * @returns badge name and color
 */
export function getHasanatBadge(totalHasanat: number): {
  name: string;
  color: string;
  icon: string;
} {
  if (totalHasanat >= 1000000) {
    return { name: 'Diamond Reciter', color: 'text-blue-600', icon: '💎' };
  }
  if (totalHasanat >= 500000) {
    return { name: 'Gold Reciter', color: 'text-yellow-600', icon: '🥇' };
  }
  if (totalHasanat >= 100000) {
    return { name: 'Silver Reciter', color: 'text-gray-600', icon: '🥈' };
  }
  if (totalHasanat >= 50000) {
    return { name: 'Bronze Reciter', color: 'text-orange-600', icon: '🥉' };
  }
  if (totalHasanat >= 10000) {
    return { name: 'Dedicated Student', color: 'text-green-600', icon: '📚' };
  }
  if (totalHasanat >= 1000) {
    return { name: 'Active Learner', color: 'text-purple-600', icon: '🌟' };
  }
  return { name: 'New Student', color: 'text-gray-500', icon: '🌱' };
}