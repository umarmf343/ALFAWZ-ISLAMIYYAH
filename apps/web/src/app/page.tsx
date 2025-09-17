/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useEffect } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';

/**
 * Home page component that serves as landing page for unauthenticated users
 * and redirects authenticated users to their dashboard.
 */
export default function HomePage() {
  const router = useRouter();
  const { isAuthenticated, isLoading } = useAuth();

  // Redirect authenticated users to dashboard
  useEffect(() => {
    if (!isLoading && isAuthenticated) {
      router.push('/dashboard');
    }
  }, [isAuthenticated, isLoading, router]);

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
      </div>
    );
  }

  if (isAuthenticated) {
    return null; // Will redirect to dashboard
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-green-50 to-blue-50">
      {/* Hero Section */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16">
        <div className="text-center">
          {/* Logo */}
          <div className="mx-auto w-24 h-24 bg-green-600 rounded-full flex items-center justify-center mb-8">
            <span className="text-white font-bold text-4xl">ق</span>
          </div>
          
          {/* Main Heading */}
          <h1 className="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
            AlFawz Qur'an Institute
          </h1>
          
          {/* Subtitle */}
          <p className="text-xl md:text-2xl text-gray-600 mb-8 max-w-3xl mx-auto">
            Learn, memorize, and perfect your Qur'an recitation with expert guidance 
            and modern technology
          </p>
          
          {/* Arabic Quote */}
          <div className="mb-12">
            <p className="text-2xl md:text-3xl text-green-700 font-arabic mb-2" dir="rtl">
              وَلَقَدْ يَسَّرْنَا الْقُرْآنَ لِلذِّكْرِ فَهَلْ مِن مُّدَّكِرٍ
            </p>
            <p className="text-lg text-gray-600 italic">
              "And We have certainly made the Qur'an easy for remembrance, 
              so is there any who will remember?" - Surah Al-Qamar (54:17)
            </p>
          </div>
          
          {/* CTA Buttons */}
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/register"
              className="bg-green-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-green-700 transition-colors shadow-lg"
            >
              Start Your Journey
            </Link>
            <Link
              href="/login"
              className="bg-white text-green-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-gray-50 transition-colors border-2 border-green-600"
            >
              Sign In
            </Link>
          </div>
        </div>
      </div>

      {/* Features Section */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="text-center mb-16">
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            Why Choose AlFawz?
          </h2>
          <p className="text-xl text-gray-600 max-w-2xl mx-auto">
            Experience the perfect blend of traditional Islamic education 
            and cutting-edge technology
          </p>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {/* Feature 1 */}
          <div className="bg-white rounded-xl shadow-lg p-8 text-center">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <span className="text-3xl">🎯</span>
            </div>
            <h3 className="text-xl font-semibold text-gray-900 mb-4">
              Personalized Learning
            </h3>
            <p className="text-gray-600">
              Tailored assignments and progress tracking to match your learning pace and goals
            </p>
          </div>
          
          {/* Feature 2 */}
          <div className="bg-white rounded-xl shadow-lg p-8 text-center">
            <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <span className="text-3xl">👨‍🏫</span>
            </div>
            <h3 className="text-xl font-semibold text-gray-900 mb-4">
              Expert Teachers
            </h3>
            <p className="text-gray-600">
              Learn from qualified instructors with years of experience in Qur'an education
            </p>
          </div>
          
          {/* Feature 3 */}
          <div className="bg-white rounded-xl shadow-lg p-8 text-center">
            <div className="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <span className="text-3xl">🏆</span>
            </div>
            <h3 className="text-xl font-semibold text-gray-900 mb-4">
              Gamified Progress
            </h3>
            <p className="text-gray-600">
              Earn hasanat (rewards) and compete with fellow students in a motivating environment
            </p>
          </div>
          
          {/* Feature 4 */}
          <div className="bg-white rounded-xl shadow-lg p-8 text-center">
            <div className="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <span className="text-3xl">🎤</span>
            </div>
            <h3 className="text-xl font-semibold text-gray-900 mb-4">
              Audio Feedback
            </h3>
            <p className="text-gray-600">
              Submit audio recordings and receive detailed feedback on your recitation
            </p>
          </div>
          
          {/* Feature 5 */}
          <div className="bg-white rounded-xl shadow-lg p-8 text-center">
            <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <span className="text-3xl">📱</span>
            </div>
            <h3 className="text-xl font-semibold text-gray-900 mb-4">
              Mobile Friendly
            </h3>
            <p className="text-gray-600">
              Access your lessons anywhere, anytime with our responsive web application
            </p>
          </div>
          
          {/* Feature 6 */}
          <div className="bg-white rounded-xl shadow-lg p-8 text-center">
            <div className="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <span className="text-3xl">📊</span>
            </div>
            <h3 className="text-xl font-semibold text-gray-900 mb-4">
              Progress Tracking
            </h3>
            <p className="text-gray-600">
              Monitor your memorization progress and see detailed analytics of your journey
            </p>
          </div>
        </div>
      </div>

      {/* CTA Section */}
      <div className="bg-green-600 py-16">
        <div className="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
          <h2 className="text-3xl md:text-4xl font-bold text-white mb-6">
            Ready to Begin Your Qur'an Journey?
          </h2>
          <p className="text-xl text-green-100 mb-8">
            Join thousands of students who have transformed their relationship with the Qur'an
          </p>
          <Link
            href="/register"
            className="bg-white text-green-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-gray-100 transition-colors shadow-lg inline-block"
          >
            Get Started Today
          </Link>
        </div>
      </div>
    </div>
  );
}
