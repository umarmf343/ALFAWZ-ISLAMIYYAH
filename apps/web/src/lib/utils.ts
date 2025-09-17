/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import { type ClassValue, clsx } from "clsx";
import { twMerge } from "tailwind-merge";

/**
 * Utility function to merge Tailwind CSS classes with clsx and tailwind-merge.
 * @param inputs Class values to merge
 * @returns Merged class string
 */
export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * Compute hasanat for a given Arabic string.
 * Hasanat = number of Arabic letters * 10 (ignores diacritics and whitespace).
 * @param text Arabic text input
 * @returns number total hasanat
 */
export function computeHasanat(text: string): number {
  const base = text.normalize("NFKD").replace(/[\u064B-\u065F\u0670\u06D6-\u06ED]/g, "");
  const letters = base.match(/[\u0621-\u064A]/g)?.length ?? 0;
  return letters * 10;
}

/**
 * Format a date to a readable string.
 * @param date Date to format
 * @returns Formatted date string
 */
export function formatDate(date: Date): string {
  return new Intl.DateTimeFormat("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  }).format(date);
}

/**
 * Format a number with commas for thousands separator.
 * @param num Number to format
 * @returns Formatted number string
 */
export function formatNumber(num: number): string {
  return new Intl.NumberFormat("en-US").format(num);
}