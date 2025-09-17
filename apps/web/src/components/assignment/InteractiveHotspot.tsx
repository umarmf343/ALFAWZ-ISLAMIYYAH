/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useRef, useEffect, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  Volume2,
  Play,
  Pause,
  Info,
  CheckCircle,
  XCircle,
  Star,
  Zap,
  Target,
  Award,
  Clock,
  RotateCcw
} from 'lucide-react';
import { Hotspot, HotspotInteraction } from '../../types/assignment';

interface InteractiveHotspotProps {
  hotspot: Hotspot;
  onInteraction: (interaction: Omit<HotspotInteraction, 'id' | 'created_at'>) => void;
  isDisabled?: boolean;
  showTooltip?: boolean;
  className?: string;
  size?: 'sm' | 'md' | 'lg';
  variant?: 'default' | 'success' | 'warning' | 'error';
}

interface QuizData {
  question: string;
  options: string[];
  correctAnswer: number;
  explanation?: string;
}

/**
 * Interactive hotspot component with various interaction types.
 * Supports audio playback, quizzes, tooltips, and interaction tracking.
 */
const InteractiveHotspot: React.FC<InteractiveHotspotProps> = ({
  hotspot,
  onInteraction,
  isDisabled = false,
  showTooltip = true,
  className = '',
  size = 'md',
  variant = 'default'
}) => {
  // Audio state
  const [isPlaying, setIsPlaying] = useState(false);
  const [audioProgress, setAudioProgress] = useState(0);
  const [audioDuration, setAudioDuration] = useState(0);
  
  // Interaction state
  const [isHovered, setIsHovered] = useState(false);
  const [isClicked, setIsClicked] = useState(false);
  const [showQuiz, setShowQuiz] = useState(false);
  const [quizAnswer, setQuizAnswer] = useState<number | null>(null);
  const [quizSubmitted, setQuizSubmitted] = useState(false);
  const [interactionCount, setInteractionCount] = useState(0);
  const [lastInteraction, setLastInteraction] = useState<Date | null>(null);
  
  // Refs
  const audioRef = useRef<HTMLAudioElement>(null);
  const hotspotRef = useRef<HTMLDivElement>(null);
  
  // Parse quiz data from metadata
  const quizData: QuizData | null = hotspot.metadata?.quiz || null;

  /**
   * Get hotspot size classes based on size prop.
   */
  const getSizeClasses = () => {
    switch (size) {
      case 'sm': return 'w-8 h-8';
      case 'lg': return 'w-16 h-16';
      default: return 'w-12 h-12';
    }
  };

  /**
   * Get variant color classes.
   */
  const getVariantClasses = () => {
    switch (variant) {
      case 'success': return 'bg-green-500 border-green-600 hover:bg-green-600';
      case 'warning': return 'bg-yellow-500 border-yellow-600 hover:bg-yellow-600';
      case 'error': return 'bg-red-500 border-red-600 hover:bg-red-600';
      default: return 'bg-maroon-500 border-maroon-600 hover:bg-maroon-600';
    }
  };

  /**
   * Get animation variants based on hotspot animation type.
   */
  const getAnimationVariants = () => {
    switch (hotspot.animation_type) {
      case 'pulse':
        return {
          animate: {
            scale: [1, 1.1, 1],
            transition: { duration: 2, repeat: Infinity, ease: 'easeInOut' }
          }
        };
      case 'bounce':
        return {
          animate: {
            y: [0, -10, 0],
            transition: { duration: 1.5, repeat: Infinity, ease: 'easeInOut' }
          }
        };
      case 'fade':
        return {
          animate: {
            opacity: [0.7, 1, 0.7],
            transition: { duration: 2, repeat: Infinity, ease: 'easeInOut' }
          }
        };
      case 'rotate':
        return {
          animate: {
            rotate: [0, 360],
            transition: { duration: 3, repeat: Infinity, ease: 'linear' }
          }
        };
      default:
        return {};
    }
  };

  /**
   * Handle audio playback.
   */
  const handleAudioPlayback = useCallback(async () => {
    if (!hotspot.audio_url || !audioRef.current) return;
    
    try {
      if (isPlaying) {
        audioRef.current.pause();
        setIsPlaying(false);
      } else {
        await audioRef.current.play();
        setIsPlaying(true);
        
        // Track interaction
        recordInteraction('audio_play');
      }
    } catch (error) {
      console.error('Audio playback error:', error);
    }
  }, [isPlaying, hotspot.audio_url]);

  /**
   * Record interaction with the hotspot.
   * @param type Type of interaction
   * @param data Additional interaction data
   */
  const recordInteraction = (type: string, data?: any) => {
    const interaction = {
      hotspot_id: hotspot.id,
      interaction_type: type,
      interaction_data: data,
      timestamp: new Date().toISOString()
    };
    
    onInteraction(interaction);
    setInteractionCount(prev => prev + 1);
    setLastInteraction(new Date());
  };

  /**
   * Handle hotspot click based on type.
   */
  const handleClick = () => {
    if (isDisabled) return;
    
    setIsClicked(true);
    setTimeout(() => setIsClicked(false), 200);
    
    switch (hotspot.hotspot_type) {
      case 'audio':
        handleAudioPlayback();
        break;
      case 'quiz':
        if (quizData) {
          setShowQuiz(true);
          recordInteraction('quiz_open');
        }
        break;
      case 'interactive':
        recordInteraction('click');
        break;
      default:
        recordInteraction('click');
        break;
    }
  };

  /**
   * Handle quiz submission.
   * @param selectedAnswer Selected answer index
   */
  const handleQuizSubmit = (selectedAnswer: number) => {
    if (!quizData || quizSubmitted) return;
    
    setQuizAnswer(selectedAnswer);
    setQuizSubmitted(true);
    
    const isCorrect = selectedAnswer === quizData.correctAnswer;
    
    recordInteraction('quiz_submit', {
      question: quizData.question,
      selectedAnswer,
      correctAnswer: quizData.correctAnswer,
      isCorrect
    });
    
    // Auto-close quiz after 3 seconds
    setTimeout(() => {
      setShowQuiz(false);
      setQuizSubmitted(false);
      setQuizAnswer(null);
    }, 3000);
  };

  /**
   * Get hotspot icon based on type.
   */
  const getHotspotIcon = () => {
    switch (hotspot.hotspot_type) {
      case 'audio':
        return isPlaying ? <Pause className="h-4 w-4" /> : <Volume2 className="h-4 w-4" />;
      case 'quiz':
        return <Target className="h-4 w-4" />;
      case 'interactive':
        return <Zap className="h-4 w-4" />;
      default:
        return <Info className="h-4 w-4" />;
    }
  };

  /**
   * Setup audio event listeners.
   */
  useEffect(() => {
    const audio = audioRef.current;
    if (!audio || !hotspot.audio_url) return;

    const handleLoadedMetadata = () => {
      setAudioDuration(audio.duration);
    };

    const handleTimeUpdate = () => {
      setAudioProgress((audio.currentTime / audio.duration) * 100);
    };

    const handleEnded = () => {
      setIsPlaying(false);
      setAudioProgress(0);
      recordInteraction('audio_complete');
    };

    const handleError = () => {
      setIsPlaying(false);
      console.error('Audio loading error');
    };

    audio.addEventListener('loadedmetadata', handleLoadedMetadata);
    audio.addEventListener('timeupdate', handleTimeUpdate);
    audio.addEventListener('ended', handleEnded);
    audio.addEventListener('error', handleError);

    return () => {
      audio.removeEventListener('loadedmetadata', handleLoadedMetadata);
      audio.removeEventListener('timeupdate', handleTimeUpdate);
      audio.removeEventListener('ended', handleEnded);
      audio.removeEventListener('error', handleError);
    };
  }, [hotspot.audio_url]);

  /**
   * Auto-play audio if enabled.
   */
  useEffect(() => {
    if (hotspot.auto_play && hotspot.audio_url && audioRef.current) {
      const timer = setTimeout(() => {
        handleAudioPlayback();
      }, 1000);
      
      return () => clearTimeout(timer);
    }
  }, [hotspot.auto_play, hotspot.audio_url, handleAudioPlayback]);

  return (
    <>
      {/* Audio Element */}
      {hotspot.audio_url && (
        <audio ref={audioRef} src={hotspot.audio_url} preload="metadata" />
      )}
      
      {/* Hotspot Button */}
      <motion.div
        ref={hotspotRef}
        className={`
          relative cursor-pointer rounded-full border-2 flex items-center justify-center
          transition-all duration-200 select-none
          ${getSizeClasses()}
          ${getVariantClasses()}
          ${isDisabled ? 'opacity-50 cursor-not-allowed' : ''}
          ${isClicked ? 'scale-90' : ''}
          ${className}
        `}
        style={{
          left: hotspot.x,
          top: hotspot.y,
          zIndex: isHovered ? 20 : 10
        }}
        onClick={handleClick}
        onMouseEnter={() => setIsHovered(true)}
        onMouseLeave={() => setIsHovered(false)}
        whileHover={{ scale: isDisabled ? 1 : 1.1 }}
        whileTap={{ scale: isDisabled ? 1 : 0.95 }}
        {...getAnimationVariants()}
      >
        {/* Main Icon */}
        <div className="text-white">
          {getHotspotIcon()}
        </div>
        
        {/* Audio Progress Ring */}
        {hotspot.hotspot_type === 'audio' && isPlaying && (
          <svg className="absolute inset-0 w-full h-full -rotate-90">
            <circle
              cx="50%"
              cy="50%"
              r="45%"
              fill="none"
              stroke="rgba(255,255,255,0.3)"
              strokeWidth="2"
            />
            <circle
              cx="50%"
              cy="50%"
              r="45%"
              fill="none"
              stroke="white"
              strokeWidth="2"
              strokeDasharray={`${2 * Math.PI * 45} ${2 * Math.PI * 45}`}
              strokeDashoffset={`${2 * Math.PI * 45 * (1 - audioProgress / 100)}`}
              className="transition-all duration-100"
            />
          </svg>
        )}
        
        {/* Interaction Count Badge */}
        {interactionCount > 0 && (
          <div className="absolute -top-2 -right-2 bg-gold-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
            {interactionCount > 9 ? '9+' : interactionCount}
          </div>
        )}
        
        {/* Required Indicator */}
        {hotspot.is_required && (
          <div className="absolute -top-1 -left-1 text-red-500">
            <Star className="h-3 w-3 fill-current" />
          </div>
        )}
      </motion.div>
      
      {/* Tooltip */}
      <AnimatePresence>
        {isHovered && showTooltip && hotspot.tooltip && (
          <motion.div
            initial={{ opacity: 0, y: 10, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 10, scale: 0.9 }}
            className="absolute z-30 bg-black text-white text-sm px-3 py-2 rounded-lg shadow-lg max-w-xs"
            style={{
              left: hotspot.x + hotspot.width / 2,
              top: hotspot.y - 10,
              transform: 'translateX(-50%) translateY(-100%)'
            }}
          >
            <div className="font-medium mb-1">{hotspot.title}</div>
            <div className="text-xs opacity-90">{hotspot.tooltip}</div>
            
            {/* Tooltip Arrow */}
            <div className="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-black" />
          </motion.div>
        )}
      </AnimatePresence>
      
      {/* Quiz Modal */}
      <AnimatePresence>
        {showQuiz && quizData && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            onClick={() => setShowQuiz(false)}
          >
            <motion.div
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              className="bg-white rounded-lg shadow-xl max-w-md w-full p-6"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="text-center mb-6">
                <div className="w-12 h-12 bg-maroon-100 rounded-full flex items-center justify-center mx-auto mb-3">
                  <Target className="h-6 w-6 text-maroon-600" />
                </div>
                <h3 className="text-lg font-semibold text-gray-900">
                  {hotspot.title || 'Quiz Question'}
                </h3>
              </div>
              
              <div className="mb-6">
                <p className="text-gray-800 mb-4">{quizData.question}</p>
                
                <div className="space-y-2">
                  {quizData.options.map((option, index) => {
                    let buttonClass = 'w-full p-3 text-left border rounded-lg transition-colors ';
                    
                    if (quizSubmitted) {
                      if (index === quizData.correctAnswer) {
                        buttonClass += 'bg-green-100 border-green-500 text-green-800';
                      } else if (index === quizAnswer && index !== quizData.correctAnswer) {
                        buttonClass += 'bg-red-100 border-red-500 text-red-800';
                      } else {
                        buttonClass += 'bg-gray-100 border-gray-300 text-gray-600';
                      }
                    } else {
                      buttonClass += 'border-gray-300 hover:bg-gray-50 hover:border-gray-400';
                    }
                    
                    return (
                      <button
                        key={index}
                        onClick={() => !quizSubmitted && handleQuizSubmit(index)}
                        disabled={quizSubmitted}
                        className={buttonClass}
                      >
                        <div className="flex items-center justify-between">
                          <span>{option}</span>
                          {quizSubmitted && (
                            <div>
                              {index === quizData.correctAnswer && (
                                <CheckCircle className="h-5 w-5 text-green-600" />
                              )}
                              {index === quizAnswer && index !== quizData.correctAnswer && (
                                <XCircle className="h-5 w-5 text-red-600" />
                              )}
                            </div>
                          )}
                        </div>
                      </button>
                    );
                  })}
                </div>
                
                {quizSubmitted && quizData.explanation && (
                  <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p className="text-sm text-blue-800">
                      <strong>Explanation:</strong> {quizData.explanation}
                    </p>
                  </div>
                )}
              </div>
              
              {!quizSubmitted && (
                <div className="flex justify-end">
                  <button
                    onClick={() => setShowQuiz(false)}
                    className="px-4 py-2 text-gray-600 hover:text-gray-800"
                  >
                    Cancel
                  </button>
                </div>
              )}
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
};

export default InteractiveHotspot;