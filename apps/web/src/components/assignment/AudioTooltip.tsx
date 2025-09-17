/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useRef, useEffect, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  Volume2,
  VolumeX,
  Play,
  Pause,
  SkipBack,
  SkipForward,
  RotateCcw,
  Download,
  Headphones,
  Waveform,
  Clock,
  Star,
  Heart,
  BookOpen
} from 'lucide-react';

interface AudioTooltipProps {
  audioUrl: string;
  title?: string;
  description?: string;
  position: { x: number; y: number };
  isVisible: boolean;
  onClose?: () => void;
  onPlayStateChange?: (isPlaying: boolean) => void;
  onProgress?: (progress: number) => void;
  autoPlay?: boolean;
  showWaveform?: boolean;
  showDownload?: boolean;
  className?: string;
}

interface AudioState {
  isPlaying: boolean;
  currentTime: number;
  duration: number;
  volume: number;
  isMuted: boolean;
  isLoading: boolean;
  error: string | null;
}

/**
 * Enhanced audio tooltip component with waveform visualization and controls.
 * Provides rich audio feedback for Quranic recitation and tajweed guidance.
 */
const AudioTooltip: React.FC<AudioTooltipProps> = ({
  audioUrl,
  title = 'Audio Guidance',
  description,
  position,
  isVisible,
  onClose,
  onPlayStateChange,
  onProgress,
  autoPlay = false,
  showWaveform = true,
  showDownload = true,
  className = ''
}) => {
  // Audio ref and state
  const audioRef = useRef<HTMLAudioElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const animationRef = useRef<number>();
  
  const [audioState, setAudioState] = useState<AudioState>({
    isPlaying: false,
    currentTime: 0,
    duration: 0,
    volume: 1,
    isMuted: false,
    isLoading: true,
    error: null
  });
  
  const [waveformData, setWaveformData] = useState<number[]>([]);
  const [playbackRate, setPlaybackRate] = useState(1);
  const [isBookmarked, setIsBookmarked] = useState(false);

  /**
   * Format time in MM:SS format.
   * @param seconds Time in seconds
   * @returns Formatted time string
   */
  const formatTime = useCallback((seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  }, []);

  /**
   * Generate mock waveform data for visualization.
   * In a real implementation, this would analyze the actual audio.
   */
  const generateWaveformData = useCallback(() => {
    const points = 50;
    const data = [];
    
    for (let i = 0; i < points; i++) {
      // Create a more natural waveform pattern
      const base = Math.sin(i * 0.1) * 0.5 + 0.5;
      const variation = Math.random() * 0.3;
      data.push(Math.max(0.1, Math.min(1, base + variation)));
    }
    
    setWaveformData(data);
  }, []);

  /**
   * Draw waveform visualization on canvas.
   */
  const drawWaveform = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas || waveformData.length === 0) return;
    
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    
    const { width, height } = canvas;
    const progress = audioState.duration > 0 ? audioState.currentTime / audioState.duration : 0;
    
    // Clear canvas
    ctx.clearRect(0, 0, width, height);
    
    // Draw waveform bars
    const barWidth = width / waveformData.length;
    const centerY = height / 2;
    
    waveformData.forEach((amplitude, index) => {
      const x = index * barWidth;
      const barHeight = amplitude * (height * 0.8);
      const y = centerY - barHeight / 2;
      
      // Determine bar color based on progress
      const barProgress = index / waveformData.length;
      const isPlayed = barProgress <= progress;
      
      ctx.fillStyle = isPlayed 
        ? 'rgba(139, 69, 19, 0.8)' // Maroon for played portion
        : 'rgba(156, 163, 175, 0.4)'; // Gray for unplayed portion
      
      ctx.fillRect(x, y, barWidth - 1, barHeight);
    });
    
    // Draw progress line
    if (progress > 0) {
      const progressX = progress * width;
      ctx.strokeStyle = 'rgba(139, 69, 19, 1)';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(progressX, 0);
      ctx.lineTo(progressX, height);
      ctx.stroke();
    }
  }, [waveformData, audioState.currentTime, audioState.duration]);

  /**
   * Handle audio play/pause toggle.
   */
  const togglePlayPause = useCallback(async () => {
    if (!audioRef.current) return;
    
    try {
      if (audioState.isPlaying) {
        audioRef.current.pause();
      } else {
        await audioRef.current.play();
      }
    } catch (error) {
      console.error('Audio playback error:', error);
      setAudioState(prev => ({ ...prev, error: 'Playback failed' }));
    }
  }, [audioState.isPlaying]);

  /**
   * Handle seeking to specific position.
   * @param seekTime Target time in seconds
   */
  const seekTo = useCallback((seekTime: number) => {
    if (!audioRef.current) return;
    
    audioRef.current.currentTime = Math.max(0, Math.min(seekTime, audioState.duration));
  }, [audioState.duration]);

  /**
   * Handle volume change.
   * @param newVolume Volume level (0-1)
   */
  const changeVolume = useCallback((newVolume: number) => {
    if (!audioRef.current) return;
    
    const volume = Math.max(0, Math.min(1, newVolume));
    audioRef.current.volume = volume;
    setAudioState(prev => ({ ...prev, volume, isMuted: volume === 0 }));
  }, []);

  /**
   * Toggle mute state.
   */
  const toggleMute = useCallback(() => {
    if (!audioRef.current) return;
    
    if (audioState.isMuted) {
      audioRef.current.volume = audioState.volume;
      setAudioState(prev => ({ ...prev, isMuted: false }));
    } else {
      audioRef.current.volume = 0;
      setAudioState(prev => ({ ...prev, isMuted: true }));
    }
  }, [audioState.isMuted, audioState.volume]);

  /**
   * Change playback rate.
   * @param rate New playback rate
   */
  const changePlaybackRate = useCallback((rate: number) => {
    if (!audioRef.current) return;
    
    audioRef.current.playbackRate = rate;
    setPlaybackRate(rate);
  }, []);

  /**
   * Download audio file.
   */
  const downloadAudio = useCallback(() => {
    const link = document.createElement('a');
    link.href = audioUrl;
    link.download = `${title.replace(/\s+/g, '_')}.mp3`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }, [audioUrl, title]);

  /**
   * Handle waveform click for seeking.
   * @param event Mouse event
   */
  const handleWaveformClick = useCallback((event: React.MouseEvent<HTMLCanvasElement>) => {
    if (!canvasRef.current || audioState.duration === 0) return;
    
    const canvas = canvasRef.current;
    const rect = canvas.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const progress = x / rect.width;
    const seekTime = progress * audioState.duration;
    
    seekTo(seekTime);
  }, [audioState.duration, seekTo]);

  // Setup audio event listeners
  useEffect(() => {
    const audio = audioRef.current;
    if (!audio) return;

    const handleLoadStart = () => {
      setAudioState(prev => ({ ...prev, isLoading: true, error: null }));
    };

    const handleLoadedMetadata = () => {
      setAudioState(prev => ({ 
        ...prev, 
        duration: audio.duration,
        isLoading: false 
      }));
      generateWaveformData();
    };

    const handleTimeUpdate = () => {
      setAudioState(prev => ({ ...prev, currentTime: audio.currentTime }));
      onProgress?.(audio.currentTime);
    };

    const handlePlay = () => {
      setAudioState(prev => ({ ...prev, isPlaying: true }));
      onPlayStateChange?.(true);
    };

    const handlePause = () => {
      setAudioState(prev => ({ ...prev, isPlaying: false }));
      onPlayStateChange?.(false);
    };

    const handleEnded = () => {
      setAudioState(prev => ({ ...prev, isPlaying: false, currentTime: 0 }));
      onPlayStateChange?.(false);
    };

    const handleError = () => {
      setAudioState(prev => ({ 
        ...prev, 
        isLoading: false, 
        error: 'Failed to load audio',
        isPlaying: false 
      }));
    };

    audio.addEventListener('loadstart', handleLoadStart);
    audio.addEventListener('loadedmetadata', handleLoadedMetadata);
    audio.addEventListener('timeupdate', handleTimeUpdate);
    audio.addEventListener('play', handlePlay);
    audio.addEventListener('pause', handlePause);
    audio.addEventListener('ended', handleEnded);
    audio.addEventListener('error', handleError);

    return () => {
      audio.removeEventListener('loadstart', handleLoadStart);
      audio.removeEventListener('loadedmetadata', handleLoadedMetadata);
      audio.removeEventListener('timeupdate', handleTimeUpdate);
      audio.removeEventListener('play', handlePlay);
      audio.removeEventListener('pause', handlePause);
      audio.removeEventListener('ended', handleEnded);
      audio.removeEventListener('error', handleError);
    };
  }, [audioUrl, onPlayStateChange, onProgress, generateWaveformData]);

  // Auto-play functionality
  useEffect(() => {
    if (autoPlay && isVisible && audioRef.current && !audioState.isPlaying) {
      const timer = setTimeout(() => {
        togglePlayPause();
      }, 500);
      
      return () => clearTimeout(timer);
    }
  }, [autoPlay, isVisible, audioState.isPlaying, togglePlayPause]);

  // Waveform animation
  useEffect(() => {
    if (showWaveform) {
      const animate = () => {
        drawWaveform();
        animationRef.current = requestAnimationFrame(animate);
      };
      
      animate();
      
      return () => {
        if (animationRef.current) {
          cancelAnimationFrame(animationRef.current);
        }
      };
    }
  }, [showWaveform, drawWaveform]);

  // Calculate tooltip position
  const tooltipStyle = {
    left: position.x,
    top: position.y - 10,
    transform: 'translateX(-50%) translateY(-100%)'
  };

  return (
    <>
      {/* Audio Element */}
      <audio ref={audioRef} src={audioUrl} preload="metadata" />
      
      {/* Tooltip */}
      <AnimatePresence>
        {isVisible && (
          <motion.div
            initial={{ opacity: 0, scale: 0.9, y: 10 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.9, y: 10 }}
            className={`
              absolute z-50 bg-white rounded-lg shadow-xl border border-gray-200
              min-w-[320px] max-w-[400px] p-4 ${className}
            `}
            style={tooltipStyle}
          >
            {/* Header */}
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center space-x-2">
                <div className="w-8 h-8 bg-maroon-100 rounded-full flex items-center justify-center">
                  <Headphones className="h-4 w-4 text-maroon-600" />
                </div>
                <div>
                  <h3 className="font-medium text-gray-900 text-sm">{title}</h3>
                  {description && (
                    <p className="text-xs text-gray-600">{description}</p>
                  )}
                </div>
              </div>
              
              <div className="flex items-center space-x-1">
                <button
                  onClick={() => setIsBookmarked(!isBookmarked)}
                  className={`p-1 rounded transition-colors ${
                    isBookmarked ? 'text-gold-500' : 'text-gray-400 hover:text-gray-600'
                  }`}
                  title="Bookmark"
                >
                  <Star className={`h-4 w-4 ${isBookmarked ? 'fill-current' : ''}`} />
                </button>
                
                {onClose && (
                  <button
                    onClick={onClose}
                    className="p-1 rounded text-gray-400 hover:text-gray-600"
                    title="Close"
                  >
                    ×
                  </button>
                )}
              </div>
            </div>
            
            {/* Error State */}
            {audioState.error && (
              <div className="bg-red-50 border border-red-200 rounded-lg p-3 mb-3">
                <p className="text-sm text-red-800">{audioState.error}</p>
              </div>
            )}
            
            {/* Loading State */}
            {audioState.isLoading && (
              <div className="flex items-center justify-center py-8">
                <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-maroon-500" />
                <span className="ml-2 text-sm text-gray-600">Loading audio...</span>
              </div>
            )}
            
            {/* Audio Controls */}
            {!audioState.isLoading && !audioState.error && (
              <>
                {/* Waveform */}
                {showWaveform && (
                  <div className="mb-4">
                    <canvas
                      ref={canvasRef}
                      width={280}
                      height={60}
                      className="w-full h-15 bg-gray-50 rounded cursor-pointer"
                      onClick={handleWaveformClick}
                    />
                  </div>
                )}
                
                {/* Time Display */}
                <div className="flex items-center justify-between text-xs text-gray-600 mb-3">
                  <span>{formatTime(audioState.currentTime)}</span>
                  <span>{formatTime(audioState.duration)}</span>
                </div>
                
                {/* Main Controls */}
                <div className="flex items-center justify-center space-x-4 mb-3">
                  <button
                    onClick={() => seekTo(audioState.currentTime - 10)}
                    className="p-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700"
                    title="Rewind 10s"
                  >
                    <SkipBack className="h-4 w-4" />
                  </button>
                  
                  <button
                    onClick={togglePlayPause}
                    className="p-3 rounded-full bg-maroon-500 hover:bg-maroon-600 text-white"
                    title={audioState.isPlaying ? 'Pause' : 'Play'}
                  >
                    {audioState.isPlaying ? (
                      <Pause className="h-5 w-5" />
                    ) : (
                      <Play className="h-5 w-5" />
                    )}
                  </button>
                  
                  <button
                    onClick={() => seekTo(audioState.currentTime + 10)}
                    className="p-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700"
                    title="Forward 10s"
                  >
                    <SkipForward className="h-4 w-4" />
                  </button>
                </div>
                
                {/* Secondary Controls */}
                <div className="flex items-center justify-between">
                  {/* Volume Control */}
                  <div className="flex items-center space-x-2">
                    <button
                      onClick={toggleMute}
                      className="p-1 rounded text-gray-600 hover:text-gray-800"
                    >
                      {audioState.isMuted ? (
                        <VolumeX className="h-4 w-4" />
                      ) : (
                        <Volume2 className="h-4 w-4" />
                      )}
                    </button>
                    
                    <input
                      type="range"
                      min="0"
                      max="1"
                      step="0.1"
                      value={audioState.isMuted ? 0 : audioState.volume}
                      onChange={(e) => changeVolume(parseFloat(e.target.value))}
                      className="w-16 h-1 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                    />
                  </div>
                  
                  {/* Playback Rate */}
                  <div className="flex items-center space-x-1">
                    <Clock className="h-3 w-3 text-gray-500" />
                    <select
                      value={playbackRate}
                      onChange={(e) => changePlaybackRate(parseFloat(e.target.value))}
                      className="text-xs border border-gray-300 rounded px-1 py-0.5"
                    >
                      <option value={0.5}>0.5×</option>
                      <option value={0.75}>0.75×</option>
                      <option value={1}>1×</option>
                      <option value={1.25}>1.25×</option>
                      <option value={1.5}>1.5×</option>
                    </select>
                  </div>
                  
                  {/* Download Button */}
                  {showDownload && (
                    <button
                      onClick={downloadAudio}
                      className="p-1 rounded text-gray-600 hover:text-gray-800"
                      title="Download Audio"
                    >
                      <Download className="h-4 w-4" />
                    </button>
                  )}
                </div>
              </>
            )}
            
            {/* Tooltip Arrow */}
            <div className="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-white" />
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
};

export default AudioTooltip;