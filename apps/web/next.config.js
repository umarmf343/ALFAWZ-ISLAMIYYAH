/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

const createNextIntlPlugin = require('next-intl/plugin');

// Wrap your Next.js config with Next Intl plugin
const withNextIntl = createNextIntlPlugin();

/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export', // For static export
  trailingSlash: true, // Ensure all routes end with a slash
  images: {
    unoptimized: true, // Disable Next.js image optimization for static export
  },

  // Configure webpack for better performance and static export compatibility
  webpack: (config, { isServer }) => {
    if (!isServer) {
      config.resolve.fallback = {
        ...config.resolve.fallback,
        fs: false, // Avoid issues with fs module in client-side code
      };
    }
    return config;
  },
};

module.exports = withNextIntl(nextConfig);
