/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  Brain, 
  Volume2, 
  VolumeX, 
  Play, 
  Pause, 
  RotateCcw, 
  CheckCircle, 
  AlertCircle, 
  TrendingUp,
  Clock,
  Target,
  Award,
  Loader2
} from 'lucide-react';

interface AIFeedbackData {
  accuracy_score: number;
  similarity_score: number;
  transcribed_text: string;
  expected_text: string;
  feedback_sections: {
    specific_feedback: string;
    positive_points: string;
    improvements: string;
  };
  tajweed_score?: number;
  fluency_score?: number;
  pace_analysis?: string;
  tajweed_rules?: string[];
  pronunciation_errors?: string[];
  audio_duration: number;
  analysis_area: string;
  assignment_feedback?: {
    focus: string;
    criteria: string[];
  };
}

interface AIFeedbackPanelProps {
  submissionId: string;
  audioUrl?: string;
  expectedText?: string;
  assignmentType?: string;
  area?: string;
  onFeedbackGenerated?: (feedback: AIFeedbackData) => void;
  className?: string;
}

/**
 * AI-powered feedback panel with Whisper integration for audio analysis.
 * Provides comprehensive tajweed, fluency, and accuracy feedback.
 */
export const AIFeedbackPanel: React.FC<AIFeedbackPanelProps> = ({
  submissionId,
  audioUrl,
  expectedText,
  assignmentType = 'recitation',
  area = 'general',
  onFeedbackGenerated,
  className = ''
}) => {
  const [feedback, setFeedback] = useState<AIFeedbackData | null>(null);
  const [isAnalyzing, setIsAnalyzing] = useState(false);
  const [isPlaying, setIsPlaying] = useState(false);
  const [isMuted, setIsMuted] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [audioElement, setAudioElement] = useState<HTMLAudioElement | null>(null);

  useEffect(() => {
    if (audioUrl) {
      const audio = new Audio(audioUrl);
      audio.addEventListener('ended', () => setIsPlaying(false));
      setAudioElement(audio);
      
      return () => {
        audio.pause();
        audio.removeEventListener('ended', () => setIsPlaying(false));
      };
    }
  }, [audioUrl]);

  /**
   * Generate AI feedback for the submission audio.
   */
  const generateFeedback = async () => {
    if (!audioUrl) {
      setError('No audio file available for analysis');
      return;
    }

    setIsAnalyzing(true);
    setError(null);

    try {
      const response = await fetch('/api/submissions/ai-feedback', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({
          submission_id: submissionId,
          audio_url: audioUrl,
          expected_text: expectedText,
          assignment_type: assignmentType,
          area: area
        })
      });

      if (!response.ok) {
        throw new Error('Failed to generate AI feedback');
      }

      const data = await response.json();
      setFeedback(data.feedback);
      onFeedbackGenerated?.(data.feedback);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Analysis failed');
    } finally {
      setIsAnalyzing(false);
    }
  };

  /**
   * Toggle audio playback.
   */
  const togglePlayback = () => {
    if (!audioElement) return;

    if (isPlaying) {
      audioElement.pause();
    } else {
      audioElement.play();
    }
    setIsPlaying(!isPlaying);
  };

  /**
   * Toggle audio mute.
   */
  const toggleMute = () => {
    if (!audioElement) return;
    
    audioElement.muted = !isMuted;
    setIsMuted(!isMuted);
  };

  /**
   * Get score color based on value.
   */
  const getScoreColor = (score: number): string => {
    if (score >= 90) return 'text-green-600';
    if (score >= 75) return 'text-yellow-600';
    if (score >= 60) return 'text-orange-600';
    return 'text-red-600';
  };

  /**
   * Get score background color based on value.
   */
  const getScoreBgColor = (score: number): string => {
    if (score >= 90) return 'bg-green-100';
    if (score >= 75) return 'bg-yellow-100';
    if (score >= 60) return 'bg-orange-100';
    return 'bg-red-100';
  };

  return (
    <div className={`bg-white rounded-lg shadow-lg border border-gray-200 ${className}`}>
      {/* Header */}
      <div className="p-6 border-b border-gray-200">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="p-2 bg-purple-100 rounded-lg">
              <Brain className="w-6 h-6 text-purple-600" />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-gray-900">
                AI Feedback Analysis
              </h3>
              <p className="text-sm text-gray-600">
                Powered by Whisper AI for comprehensive recitation analysis
              </p>
            </div>
          </div>
          
          {/* Audio Controls */}
          {audioUrl && (
            <div className="flex items-center space-x-2">
              <button
                onClick={togglePlayback}
                className="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                disabled={isAnalyzing}
              >
                {isPlaying ? (
                  <Pause className="w-5 h-5 text-gray-700" />
                ) : (
                  <Play className="w-5 h-5 text-gray-700" />
                )}
              </button>
              <button
                onClick={toggleMute}
                className="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                disabled={isAnalyzing}
              >
                {isMuted ? (
                  <VolumeX className="w-5 h-5 text-gray-700" />
                ) : (
                  <Volume2 className="w-5 h-5 text-gray-700" />
                )}
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Content */}
      <div className="p-6">
        {/* Generate Feedback Button */}
        {!feedback && !isAnalyzing && (
          <motion.button
            onClick={generateFeedback}
            className="w-full py-3 px-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg font-medium hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 flex items-center justify-center space-x-2"
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
          >
            <Brain className="w-5 h-5" />
            <span>Generate AI Feedback</span>
          </motion.button>
        )}

        {/* Loading State */}
        {isAnalyzing && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            className="text-center py-8"
          >
            <Loader2 className="w-8 h-8 text-purple-600 animate-spin mx-auto mb-4" />
            <p className="text-gray-600">Analyzing your recitation...</p>
            <p className="text-sm text-gray-500 mt-2">
              This may take a few moments
            </p>
          </motion.div>
        )}

        {/* Error State */}
        {error && (
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start space-x-3"
          >
            <AlertCircle className="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" />
            <div>
              <p className="text-red-800 font-medium">Analysis Failed</p>
              <p className="text-red-700 text-sm mt-1">{error}</p>
              <button
                onClick={generateFeedback}
                className="mt-2 text-red-600 hover:text-red-700 text-sm font-medium flex items-center space-x-1"
              >
                <RotateCcw className="w-4 h-4" />
                <span>Try Again</span>
              </button>
            </div>
          </motion.div>
        )}

        {/* Feedback Results */}
        <AnimatePresence>
          {feedback && (
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
              className="space-y-6"
            >
              {/* Score Overview */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className={`p-4 rounded-lg ${getScoreBgColor(feedback.accuracy_score)}`}>
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-gray-700">Accuracy</p>
                      <p className={`text-2xl font-bold ${getScoreColor(feedback.accuracy_score)}`}>
                        {feedback.accuracy_score}%
                      </p>
                    </div>
                    <Target className={`w-8 h-8 ${getScoreColor(feedback.accuracy_score)}`} />
                  </div>
                </div>

                {feedback.tajweed_score && (
                  <div className={`p-4 rounded-lg ${getScoreBgColor(feedback.tajweed_score)}`}>
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-sm font-medium text-gray-700">Tajweed</p>
                        <p className={`text-2xl font-bold ${getScoreColor(feedback.tajweed_score)}`}>
                          {feedback.tajweed_score}%
                        </p>
                      </div>
                      <Award className={`w-8 h-8 ${getScoreColor(feedback.tajweed_score)}`} />
                    </div>
                  </div>
                )}

                {feedback.fluency_score && (
                  <div className={`p-4 rounded-lg ${getScoreBgColor(feedback.fluency_score)}`}>
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="text-sm font-medium text-gray-700">Fluency</p>
                        <p className={`text-2xl font-bold ${getScoreColor(feedback.fluency_score)}`}>
                          {feedback.fluency_score}%
                        </p>
                      </div>
                      <TrendingUp className={`w-8 h-8 ${getScoreColor(feedback.fluency_score)}`} />
                    </div>
                  </div>
                )}
              </div>

              {/* Detailed Feedback */}
              <div className="space-y-4">
                {/* Positive Points */}
                <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                  <div className="flex items-start space-x-3">
                    <CheckCircle className="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" />
                    <div>
                      <h4 className="font-medium text-green-800">Positive Points</h4>
                      <p className="text-green-700 text-sm mt-1">
                        {feedback.feedback_sections.positive_points}
                      </p>
                    </div>
                  </div>
                </div>

                {/* Improvements */}
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <div className="flex items-start space-x-3">
                    <TrendingUp className="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
                    <div>
                      <h4 className="font-medium text-blue-800">Areas for Improvement</h4>
                      <p className="text-blue-700 text-sm mt-1">
                        {feedback.feedback_sections.improvements}
                      </p>
                    </div>
                  </div>
                </div>

                {/* Specific Feedback */}
                <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                  <h4 className="font-medium text-gray-800 mb-2">Detailed Analysis</h4>
                  <p className="text-gray-700 text-sm">
                    {feedback.feedback_sections.specific_feedback}
                  </p>
                </div>
              </div>

              {/* Additional Analysis */}
              {(feedback.tajweed_rules || feedback.pronunciation_errors || feedback.pace_analysis) && (
                <div className="space-y-4">
                  <h4 className="font-medium text-gray-800">Additional Analysis</h4>
                  
                  {feedback.tajweed_rules && feedback.tajweed_rules.length > 0 && (
                    <div className="bg-purple-50 border border-purple-200 rounded-lg p-4">
                      <h5 className="font-medium text-purple-800 mb-2">Tajweed Rules Identified</h5>
                      <div className="flex flex-wrap gap-2">
                        {feedback.tajweed_rules.map((rule, index) => (
                          <span
                            key={index}
                            className="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full"
                          >
                            {rule}
                          </span>
                        ))}
                      </div>
                    </div>
                  )}

                  {feedback.pace_analysis && (
                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                      <div className="flex items-start space-x-3">
                        <Clock className="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" />
                        <div>
                          <h5 className="font-medium text-yellow-800">Pace Analysis</h5>
                          <p className="text-yellow-700 text-sm mt-1">{feedback.pace_analysis}</p>
                        </div>
                      </div>
                    </div>
                  )}

                  {feedback.pronunciation_errors && feedback.pronunciation_errors.length > 0 && (
                    <div className="bg-orange-50 border border-orange-200 rounded-lg p-4">
                      <h5 className="font-medium text-orange-800 mb-2">Pronunciation Notes</h5>
                      <ul className="space-y-1">
                        {feedback.pronunciation_errors.map((error, index) => (
                          <li key={index} className="text-orange-700 text-sm flex items-start space-x-2">
                            <span className="w-1 h-1 bg-orange-400 rounded-full mt-2 flex-shrink-0"></span>
                            <span>{error}</span>
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>
              )}

              {/* Regenerate Button */}
              <div className="pt-4 border-t border-gray-200">
                <button
                  onClick={generateFeedback}
                  className="text-purple-600 hover:text-purple-700 text-sm font-medium flex items-center space-x-1"
                  disabled={isAnalyzing}
                >
                  <RotateCcw className="w-4 h-4" />
                  <span>Regenerate Analysis</span>
                </button>
              </div>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </div>
  );
};

export default AIFeedbackPanel;