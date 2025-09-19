'use client';

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect, useRef } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Eye, EyeOff, Shuffle, Brain, Play, Pause, RotateCcw, Mic, MicOff } from 'lucide-react';
import { FaMicrophone, FaMicrophoneSlash, FaBrain, FaStar, FaTrophy, FaFire } from 'react-icons/fa';
import { CircularProgressbar, buildStyles } from 'react-circular-progressbar';
import 'react-circular-progressbar/dist/styles.css';
import Confetti from 'react-confetti';
import { useMemorization } from '@/hooks/useMemorization';
import { useAudio } from '@/hooks/useAudio';
import { api } from '@/lib/api';

interface MemorizationPlan {
  id: number;
  title: string;
  surahs: number[];
  daily_target: number;
  start_date: string;
  end_date?: string;
  status: 'active' | 'completed' | 'paused';
  completion_percentage: number;
}

interface DueReview {
  id: number;
  plan_title: string;
  surah_id: number;
  ayah_id: number;
  confidence_score: number;
  repetitions: number;
  due_at: string;
  overdue_hours: number;
}

interface AyahData {
  text_arabic: string;
  text_english: string;
  surah_name: string;
  ayah_number: number;
}

interface TajweedAnalysis {
  tajweed_score: number;
  pronunciation_accuracy: number;
  feedback?: string[];
  suggestions?: string[];
}

const isTajweedAnalysis = (value: unknown): value is TajweedAnalysis => {
  if (typeof value !== 'object' || value === null) {
    return false;
  }

  const analysis = value as Record<string, unknown>;
  const feedbackValid =
    analysis.feedback === undefined ||
    (Array.isArray(analysis.feedback) &&
      analysis.feedback.every((item) => typeof item === 'string'));

  const suggestionsValid =
    analysis.suggestions === undefined ||
    (Array.isArray(analysis.suggestions) &&
      analysis.suggestions.every((item) => typeof item === 'string'));

  return (
    typeof analysis.tajweed_score === 'number' &&
    typeof analysis.pronunciation_accuracy === 'number' &&
    feedbackValid &&
    suggestionsValid
  );
};

/**
 * MemorizationSection component with interactive memorization tools.
 * Provides hide words, jumble, quiz modes and progress tracking.
 */
export default function MemorizationSection() {
  // Use custom hooks for memorization and audio functionality
  const { plans, dueReviews, isLoading, createPlan, submitReview, refreshData } = useMemorization();
  const { isRecording, audioBlob, startRecording, stopRecording, clearRecording } = useAudio();
  
  // Component state
  const [currentAyah, setCurrentAyah] = useState<AyahData | null>(null);
  const [activeMode, setActiveMode] = useState<'normal' | 'hide' | 'jumble' | 'quiz'>('normal');
  const [hiddenWords, setHiddenWords] = useState<Set<number>>(new Set());
  const [jumbledWords, setJumbledWords] = useState<string[]>([]);
  const [quizAnswers, setQuizAnswers] = useState<string[]>([]);
  const [selectedAnswer, setSelectedAnswer] = useState<string>('');
  const [showResults, setShowResults] = useState(false);
  const [confidenceScore, setConfidenceScore] = useState(0.5);
  const [tajweedAnalysis, setTajweedAnalysis] = useState<TajweedAnalysis | null>(null);
  const [showConfetti, setShowConfetti] = useState(false);
  const [windowSize, setWindowSize] = useState({ width: 0, height: 0 });

  // Handle window resize for confetti
  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    const handleResize = () => {
      setWindowSize({ width: window.innerWidth, height: window.innerHeight });
    };

    handleResize();
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);
  // Load initial data on component mount
  useEffect(() => {
    refreshData();
  }, [refreshData]);

  /**
   * Load ayah data for practice.
   */
  const loadAyah = async (surahId: number, ayahId: number) => {
    try {
      const response = await api.get<AyahData>(`/quran/surahs/${surahId}/ayahs/${ayahId}`);
      if (response.data) {
        setCurrentAyah(response.data);
        resetModes();
      }
    } catch (error) {
      console.error('Failed to load ayah:', error);
    }
  };

  /**
   * Reset all interactive modes.
   */
  const resetModes = () => {
    setActiveMode('normal');
    setHiddenWords(new Set());
    setJumbledWords([]);
    setQuizAnswers([]);
    setSelectedAnswer('');
    setShowResults(false);
  };

  /**
   * Toggle hide words mode.
   */
  const toggleHideMode = () => {
    if (activeMode === 'hide') {
      setActiveMode('normal');
      setHiddenWords(new Set());
    } else {
      setActiveMode('hide');
      // Hide random 30% of words
      if (currentAyah) {
        const words = currentAyah.text_arabic.split(' ');
        const hideCount = Math.floor(words.length * 0.3);
        const indices = new Set<number>();
        while (indices.size < hideCount) {
          indices.add(Math.floor(Math.random() * words.length));
        }
        setHiddenWords(indices);
      }
    }
  };

  /**
   * Activate jumble mode.
   */
  const activateJumbleMode = () => {
    if (currentAyah) {
      const words = currentAyah.text_arabic.split(' ');
      const shuffled = [...words].sort(() => Math.random() - 0.5);
      setJumbledWords(shuffled);
      setActiveMode('jumble');
    }
  };

  /**
   * Activate quiz mode.
   */
  const activateQuizMode = () => {
    if (currentAyah) {
      // Generate multiple choice answers (simplified)
      const correctAnswer = currentAyah.text_english;
      const wrongAnswers = [
        'This is a sample wrong translation 1',
        'This is a sample wrong translation 2',
        'This is a sample wrong translation 3'
      ];
      const allAnswers = [correctAnswer, ...wrongAnswers].sort(() => Math.random() - 0.5);
      setQuizAnswers(allAnswers);
      setActiveMode('quiz');
    }
  };

  /**
   * Clear tajweed analysis when recording is cleared.
   */
  const handleClearRecording = () => {
    clearRecording();
    setTajweedAnalysis(null);
  };

  /**
   * Handle creating a new memorization plan.
   */
  const handleCreatePlan = async () => {
    try {
      const planData = {
        title: 'New Memorization Plan',
        surahs: [1], // Default to Al-Fatiha
        daily_target: 5
      };
      await createPlan(planData);
      refreshData();
    } catch (error) {
      console.error('Failed to create plan:', error);
    }
  };

  /**
   * Handle review submission with confidence score and audio.
   */
  const handleSubmitReview = async (reviewId: number) => {
    try {
      const review = dueReviews.find(r => r.id === reviewId);
      if (!review) return;

      const reviewData = {
        plan_id: review.id,
        surah_id: review.surah_id,
        ayah_id: review.ayah_id,
        confidence_score: confidenceScore,
        time_spent: 120,
        audio_file: audioBlob
      };

      const result = await submitReview(reviewData);

      if (
        result &&
        typeof result === 'object' &&
        'tajweed_analysis' in result &&
        isTajweedAnalysis((result as Record<string, unknown>).tajweed_analysis)
      ) {
        setTajweedAnalysis((result as { tajweed_analysis: TajweedAnalysis }).tajweed_analysis);
      } else {
        setTajweedAnalysis(null);
      }
      
      setShowResults(true);
      handleClearRecording();
      
      // Trigger celebration
      setShowConfetti(true);
      setTimeout(() => setShowConfetti(false), 3000);
    } catch (error) {
      console.error('Failed to submit review:', error);
    }
  };

  /**
   * Render Arabic text with interactive features.
   */
  const renderArabicText = () => {
    if (!currentAyah) return null;

    const words = currentAyah.text_arabic.split(' ');

    if (activeMode === 'hide') {
      return (
        <div className="text-2xl font-arabic leading-loose text-right">
          {words.map((word, index) => (
            <span key={index} className="inline-block mx-1">
              {hiddenWords.has(index) ? (
                <span className="bg-gray-300 text-transparent select-none px-2 py-1 rounded">
                  {word.replace(/./g, '●')}
                </span>
              ) : (
                word
              )}
            </span>
          ))}
        </div>
      );
    }

    if (activeMode === 'jumble') {
      return (
        <div className="space-y-4">
          <div className="text-lg text-gray-600">Arrange the words in correct order:</div>
          <div className="flex flex-wrap gap-2 justify-center">
            {jumbledWords.map((word, index) => (
              <Button
                key={index}
                variant="outline"
                className="font-arabic text-lg"
                onClick={() => {
                  // Simple reordering logic
                  const newOrder = [...jumbledWords];
                  const temp = newOrder[0];
                  newOrder[0] = newOrder[index];
                  newOrder[index] = temp;
                  setJumbledWords(newOrder);
                }}
              >
                {word}
              </Button>
            ))}
          </div>
        </div>
      );
    }

    return (
      <div className="text-2xl font-arabic leading-loose text-right">
        {currentAyah.text_arabic}
      </div>
    );
  };



  return (
    <div className="bg-gradient-to-br from-blue-50 via-white to-purple-50 rounded-xl shadow-xl p-6 border border-blue-100 space-y-6 relative overflow-hidden">
      {showConfetti && (
        <Confetti
          width={windowSize.width}
          height={windowSize.height}
          recycle={false}
          numberOfPieces={200}
          gravity={0.3}
        />
      )}
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            Memorization Practice
          </h2>
          <p className="text-gray-600 mt-1">Practice and review your Quranic memorization with AI-powered tools</p>
        </div>
        <div className="flex items-center space-x-3">
          <div className="p-3 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full shadow-lg">
             <FaBrain className="text-white text-lg" />
           </div>
          <div className="text-right">
            <Badge variant="secondary" className="bg-gradient-to-r from-blue-100 to-purple-100 text-blue-700">
              {dueReviews.length} Due Reviews
            </Badge>
            <div className="text-xs text-gray-500 mt-1">AI-Enhanced Learning</div>
          </div>
        </div>
      </div>

      {/* Due Reviews */}
      {dueReviews.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Brain className="h-5 w-5" />
              Due for Review
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-3">
              {dueReviews.slice(0, 3).map((review) => (
                <div key={review.id} className="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <div className="font-medium">{review.plan_title}</div>
                    <div className="text-sm text-gray-600">
                      Surah {review.surah_id}, Ayah {review.ayah_id}
                    </div>
                    <div className="text-xs text-gray-500">
                      Confidence: {Math.round(review.confidence_score * 100)}%
                    </div>
                  </div>
                  <Button
                    size="sm"
                    onClick={() => loadAyah(review.surah_id, review.ayah_id)}
                  >
                    Practice
                  </Button>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Interactive Practice Area */}
      {currentAyah && (
        <Card className="bg-gradient-to-br from-white to-blue-50 border-2 border-blue-200 shadow-xl hover:shadow-2xl transition-all duration-500 transform hover:scale-[1.02]">
          <CardHeader>
            <CardTitle className="flex items-center justify-between">
              <span className="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent font-bold">
                {currentAyah.surah_name} - Ayah {currentAyah.ayah_number}
              </span>
              <div className="flex gap-2">
                <Button
                  size="sm"
                  variant={activeMode === 'hide' ? 'default' : 'outline'}
                  onClick={toggleHideMode}
                  className={`transition-all duration-300 transform hover:scale-110 ${activeMode === 'hide' ? 'bg-gradient-to-r from-blue-500 to-purple-500 shadow-lg animate-pulse' : 'hover:bg-blue-50'}`}
                >
                  {activeMode === 'hide' ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
                </Button>
                <Button
                  size="sm"
                  variant={activeMode === 'jumble' ? 'default' : 'outline'}
                  onClick={activateJumbleMode}
                  className={`transition-all duration-300 transform hover:scale-110 ${activeMode === 'jumble' ? 'bg-gradient-to-r from-green-500 to-teal-500 shadow-lg animate-pulse' : 'hover:bg-green-50'}`}
                >
                  <Shuffle className="h-4 w-4" />
                </Button>
                <Button
                  size="sm"
                  variant={activeMode === 'quiz' ? 'default' : 'outline'}
                  onClick={activateQuizMode}
                  className={`transition-all duration-300 transform hover:scale-110 ${activeMode === 'quiz' ? 'bg-gradient-to-r from-purple-500 to-pink-500 shadow-lg animate-pulse' : 'hover:bg-purple-50'}`}
                >
                  <Brain className="h-4 w-4" />
                </Button>
                <Button 
                  size="sm" 
                  variant="ghost" 
                  onClick={resetModes}
                  className="transition-all duration-300 transform hover:scale-110 hover:bg-red-50 hover:text-red-600"
                >
                  <RotateCcw className="h-4 w-4" />
                </Button>
              </div>
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Arabic Text */}
            <div className="p-6 bg-gradient-to-br from-gray-50 to-blue-50 rounded-xl border-2 border-blue-100 shadow-inner transition-all duration-500 hover:shadow-lg">
              <div className="animate-fade-in">
                {renderArabicText()}
              </div>
            </div>

            {/* Quiz Mode */}
            {activeMode === 'quiz' && (
              <div className="space-y-4 animate-slide-in-up">
                <div className="text-lg font-medium bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent flex items-center gap-2">
                  🧠 Select the correct translation:
                </div>
                <div className="grid gap-3">
                  {quizAnswers.map((answer, index) => (
                    <Button
                      key={index}
                      variant={selectedAnswer === answer ? 'default' : 'outline'}
                      className={`text-left justify-start h-auto p-4 transition-all duration-300 transform hover:scale-[1.02] ${
                        selectedAnswer === answer 
                          ? 'bg-gradient-to-r from-purple-500 to-pink-500 shadow-lg animate-pulse' 
                          : 'hover:bg-purple-50 hover:border-purple-300 hover:shadow-md'
                      }`}
                      onClick={() => setSelectedAnswer(answer)}
                      style={{ animationDelay: `${index * 0.1}s` }}
                    >
                      <span className="mr-2 font-bold text-purple-600">{String.fromCharCode(65 + index)}.</span>
                      {answer}
                    </Button>
                  ))}
                </div>
              </div>
            )}

            {/* English Translation */}
            {activeMode !== 'quiz' && (
              <div className="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 shadow-sm hover:shadow-md transition-all duration-300">
                <div className="text-gray-700 leading-relaxed animate-fade-in">
                  <span className="text-blue-600 font-semibold mr-2">📖</span>
                  {currentAyah.text_english}
                </div>
              </div>
            )}

            {/* Audio Recording Section */}
            <div className="border-2 border-green-200 rounded-xl p-4 bg-gradient-to-br from-green-50 to-teal-50 shadow-lg hover:shadow-xl transition-all duration-300">
              <h4 className="font-medium mb-3 flex items-center gap-2 text-green-800">
                <Mic className="h-4 w-4 animate-pulse" />
                🎤 Record Your Recitation
              </h4>
              <div className="flex gap-2 mb-3">
                {!isRecording && !audioBlob && (
                  <Button 
                    onClick={startRecording} 
                    variant="outline" 
                    size="sm"
                    className="bg-gradient-to-r from-green-500 to-teal-500 text-white border-0 hover:from-green-600 hover:to-teal-600 transform hover:scale-105 transition-all duration-300 shadow-lg"
                  >
                    <Mic className="h-4 w-4 mr-2 animate-pulse" />
                    🎙️ Start Recording
                  </Button>
                )}
                {isRecording && (
                  <Button 
                    onClick={stopRecording} 
                    variant="destructive" 
                    size="sm"
                    className="bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 transform hover:scale-105 transition-all duration-300 shadow-lg animate-pulse"
                  >
                    <MicOff className="h-4 w-4 mr-2" />
                    ⏹️ Stop Recording
                  </Button>
                )}
                {audioBlob && (
                  <>
                    <Button 
                      onClick={handleClearRecording} 
                      variant="outline" 
                      size="sm"
                      className="hover:bg-red-50 hover:border-red-300 hover:text-red-600 transform hover:scale-105 transition-all duration-300"
                    >
                      🗑️ Clear Recording
                    </Button>
                    <Badge variant="secondary" className="bg-gradient-to-r from-green-100 to-teal-100 text-green-700 animate-bounce">
                      ✅ Recording Ready
                    </Badge>
                  </>
                )}
              </div>
              {isRecording && (
                <div className="flex items-center gap-2 text-red-600 bg-red-50 p-3 rounded-lg border border-red-200 animate-pulse">
                  <div className="w-3 h-3 bg-red-600 rounded-full animate-ping"></div>
                  <span className="text-sm font-medium">🔴 Recording in progress...</span>
                  <div className="ml-auto flex gap-1">
                    <div className="w-1 h-4 bg-red-500 rounded animate-bounce" style={{animationDelay: '0s'}}></div>
                    <div className="w-1 h-4 bg-red-500 rounded animate-bounce" style={{animationDelay: '0.1s'}}></div>
                    <div className="w-1 h-4 bg-red-500 rounded animate-bounce" style={{animationDelay: '0.2s'}}></div>
                  </div>
                </div>
              )}
              {audioBlob && (
                <audio controls className="w-full mt-2">
                  <source src={URL.createObjectURL(audioBlob)} type="audio/wav" />
                </audio>
              )}
            </div>
            
            {/* Tajweed Analysis Results */}
            {tajweedAnalysis && (
              <div className="border-2 border-blue-200 rounded-xl p-4 bg-gradient-to-br from-blue-50 to-indigo-50 shadow-lg animate-slide-in-up">
                <h4 className="font-medium mb-3 text-blue-800 flex items-center gap-2">
                  📊 AI Tajweed Analysis
                  <Badge className="bg-gradient-to-r from-blue-500 to-purple-500 text-white animate-pulse">AI Powered</Badge>
                </h4>
                <div className="grid grid-cols-2 gap-4 mb-3">
                  <div>
                    <span className="text-sm text-gray-600">Overall Score</span>
                    <div className="font-bold text-lg">{tajweedAnalysis.tajweed_score}%</div>
                  </div>
                  <div>
                    <span className="text-sm text-gray-600">Pronunciation</span>
                    <div className="font-bold text-lg">{tajweedAnalysis.pronunciation_accuracy}%</div>
                  </div>
                </div>
                {tajweedAnalysis.feedback && tajweedAnalysis.feedback.length > 0 && (
                  <div className="mb-2">
                    <span className="text-sm font-medium text-green-700">Feedback:</span>
                    <ul className="text-sm text-green-600 ml-4">
                      {tajweedAnalysis.feedback.map((item: string, idx: number) => (
                        <li key={idx}>• {item}</li>
                      ))}
                    </ul>
                  </div>
                )}
                {tajweedAnalysis.suggestions && tajweedAnalysis.suggestions.length > 0 && (
                  <div>
                    <span className="text-sm font-medium text-blue-700">Suggestions:</span>
                    <ul className="text-sm text-blue-600 ml-4">
                      {tajweedAnalysis.suggestions.map((item: string, idx: number) => (
                        <li key={idx}>• {item}</li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>
            )}

            {/* Confidence Slider */}
            <div className="space-y-3">
              <label className="text-sm font-medium">How confident are you? ({Math.round(confidenceScore * 100)}%)</label>
              <input
                type="range"
                min="0"
                max="1"
                step="0.1"
                value={confidenceScore}
                onChange={(e) => setConfidenceScore(parseFloat(e.target.value))}
                className="w-full"
              />
              <div className="flex justify-between text-xs text-gray-500">
                <span>Need more practice</span>
                <span>Fully memorized</span>
              </div>
            </div>

            {/* Submit Button */}
            <Button
              className="w-full bg-gradient-to-r from-blue-500 to-purple-500 hover:from-blue-600 hover:to-purple-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300 border-0"
              onClick={() => {
                const review = dueReviews.find(r => 
                  r.surah_id === currentAyah?.surah_name && 
                  r.ayah_id === currentAyah?.ayah_number
                );
                if (review) handleSubmitReview(review.id);
              }}
              disabled={isLoading}
            >
              {isLoading ? (
                <>
                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2 inline-block"></div>
                  Submitting...
                </>
              ) : (
                <>
                  ✨ Complete Review
                  <span className="ml-2">🚀</span>
                </>
              )}
            </Button>
          </CardContent>
        </Card>
      )}

      {/* Progress Overview */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div className="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl shadow-lg border border-blue-200 transform hover:scale-105 transition-all duration-300">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="font-semibold text-blue-900 mb-2">Total Plans</h3>
              <p className="text-3xl font-bold text-blue-600">{plans.length}</p>
              <div className="flex items-center mt-2">
                <FaStar className="text-yellow-500 mr-1" />
                <span className="text-sm text-blue-700">Active Learning</span>
              </div>
            </div>
            <div className="w-16 h-16">
              <CircularProgressbar
                value={plans.length > 0 ? 100 : 0}
                text={`${plans.length}`}
                styles={buildStyles({
                  textColor: '#2563eb',
                  pathColor: '#3b82f6',
                  trailColor: '#dbeafe'
                })}
              />
            </div>
          </div>
        </div>
        
        <div className="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl shadow-lg border border-green-200 transform hover:scale-105 transition-all duration-300">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="font-semibold text-green-900 mb-2">Due Reviews</h3>
              <p className="text-3xl font-bold text-green-600">{dueReviews.length}</p>
              <div className="flex items-center mt-2">
                <FaFire className="text-orange-500 mr-1" />
                <span className="text-sm text-green-700">Ready to Practice</span>
              </div>
            </div>
            <div className="w-16 h-16">
              <CircularProgressbar
                value={dueReviews.length > 0 ? Math.min((dueReviews.length / 10) * 100, 100) : 0}
                text={`${dueReviews.length}`}
                styles={buildStyles({
                  textColor: '#16a34a',
                  pathColor: '#22c55e',
                  trailColor: '#dcfce7'
                })}
              />
            </div>
          </div>
        </div>
        
        <div className="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl shadow-lg border border-purple-200 transform hover:scale-105 transition-all duration-300">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="font-semibold text-purple-900 mb-2">Completed Today</h3>
              <p className="text-3xl font-bold text-purple-600">0</p>
              <div className="flex items-center mt-2">
                <FaTrophy className="text-yellow-500 mr-1" />
                <span className="text-sm text-purple-700">Great Progress!</span>
              </div>
            </div>
            <div className="w-16 h-16">
              <CircularProgressbar
                value={0}
                text={`0`}
                styles={buildStyles({
                  textColor: '#9333ea',
                  pathColor: '#a855f7',
                  trailColor: '#f3e8ff'
                })}
              />
            </div>
          </div>
        </div>
      </div>

      {/* Memorization Plans */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-xl font-bold text-gray-800">📚 Your Memorization Plans</h3>
          <Button
            onClick={handleCreatePlan}
            className="bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-600 hover:to-teal-600 text-white font-semibold px-4 py-2 rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300"
          >
            ➕ Create New Plan
          </Button>
        </div>
        <div className="grid md:grid-cols-2 gap-6">
          {plans.map((plan, index) => (
          <Card 
            key={plan.id}
            className="bg-gradient-to-br from-white to-gray-50 border-2 border-gray-200 shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 hover:border-blue-300"
            style={{ animationDelay: `${index * 0.1}s` }}
          >
            <CardHeader>
              <div className="flex items-center mb-2">
                <div className="w-3 h-3 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full mr-3 animate-pulse"></div>
                <CardTitle className="text-lg font-bold text-gray-800">{plan.title}</CardTitle>
              </div>
              <Badge 
                variant={plan.status === 'active' ? 'default' : 'secondary'}
                className={`${plan.status === 'active' ? 'bg-gradient-to-r from-green-500 to-green-600 text-white' : ''} font-semibold`}
              >
                {plan.status === 'active' ? '🟢 Active' : plan.status}
              </Badge>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div className="flex justify-between text-sm">
                  <span>Progress</span>
                  <span>{Math.round(plan.completion_percentage)}%</span>
                </div>
                <Progress value={plan.completion_percentage} className="h-2" />
                <div className="text-xs text-gray-600">
                  📖 Daily target: {plan.daily_target} ayahs
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
        </div>
      </div>
    </div>
  );
}