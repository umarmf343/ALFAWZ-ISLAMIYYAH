/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import { useState, useRef, useCallback } from 'react';

interface AudioRecordingState {
  isRecording: boolean;
  audioBlob: Blob | null;
  duration: number;
  error: string | null;
}

/**
 * Custom hook for audio recording functionality.
 * Provides methods to start, stop, and manage audio recordings for memorization reviews.
 */
export function useAudio() {
  const [state, setState] = useState<AudioRecordingState>({
    isRecording: false,
    audioBlob: null,
    duration: 0,
    error: null,
  });

  const mediaRecorderRef = useRef<MediaRecorder | null>(null);
  const audioChunksRef = useRef<Blob[]>([]);
  const streamRef = useRef<MediaStream | null>(null);
  const startTimeRef = useRef<number>(0);
  const durationIntervalRef = useRef<NodeJS.Timeout | null>(null);

  /**
   * Check if browser supports audio recording.
   */
  const isSupported = useCallback(() => {
    return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);
  }, []);

  /**
   * Start audio recording.
   */
  const startRecording = useCallback(async () => {
    if (!isSupported()) {
      setState(prev => ({
        ...prev,
        error: 'Audio recording is not supported in this browser'
      }));
      return;
    }

    try {
      setState(prev => ({ ...prev, error: null }));
      
      // Request microphone access
      const stream = await navigator.mediaDevices.getUserMedia({
        audio: {
          echoCancellation: true,
          noiseSuppression: true,
          autoGainControl: true,
          sampleRate: 44100,
        }
      });

      streamRef.current = stream;
      audioChunksRef.current = [];

      // Create MediaRecorder instance
      const mediaRecorder = new MediaRecorder(stream, {
        mimeType: MediaRecorder.isTypeSupported('audio/webm;codecs=opus') 
          ? 'audio/webm;codecs=opus'
          : MediaRecorder.isTypeSupported('audio/mp4')
          ? 'audio/mp4'
          : 'audio/webm'
      });

      mediaRecorderRef.current = mediaRecorder;

      // Handle data available event
      mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
          audioChunksRef.current.push(event.data);
        }
      };

      // Handle recording stop event
      mediaRecorder.onstop = () => {
        const audioBlob = new Blob(audioChunksRef.current, { 
          type: mediaRecorder.mimeType || 'audio/webm' 
        });
        
        setState(prev => ({
          ...prev,
          isRecording: false,
          audioBlob,
        }));

        // Clean up stream
        if (streamRef.current) {
          streamRef.current.getTracks().forEach(track => track.stop());
          streamRef.current = null;
        }

        // Clear duration interval
        if (durationIntervalRef.current) {
          clearInterval(durationIntervalRef.current);
          durationIntervalRef.current = null;
        }
      };

      // Handle errors
      mediaRecorder.onerror = (event) => {
        console.error('MediaRecorder error:', event);
        setState(prev => ({
          ...prev,
          isRecording: false,
          error: 'Recording failed. Please try again.'
        }));
      };

      // Start recording
      mediaRecorder.start(100); // Collect data every 100ms
      startTimeRef.current = Date.now();

      setState(prev => ({
        ...prev,
        isRecording: true,
        audioBlob: null,
        duration: 0,
      }));

      // Start duration tracking
      durationIntervalRef.current = setInterval(() => {
        const elapsed = Math.floor((Date.now() - startTimeRef.current) / 1000);
        setState(prev => ({ ...prev, duration: elapsed }));
      }, 1000);

    } catch (error) {
      console.error('Failed to start recording:', error);
      setState(prev => ({
        ...prev,
        isRecording: false,
        error: error instanceof Error 
          ? error.message 
          : 'Failed to access microphone. Please check permissions.'
      }));
    }
  }, [isSupported]);

  /**
   * Stop audio recording.
   */
  const stopRecording = useCallback(() => {
    if (mediaRecorderRef.current && state.isRecording) {
      mediaRecorderRef.current.stop();
    }
  }, [state.isRecording]);

  /**
   * Clear recorded audio.
   */
  const clearAudio = useCallback(() => {
    setState(prev => ({
      ...prev,
      audioBlob: null,
      duration: 0,
      error: null,
    }));
    audioChunksRef.current = [];
  }, []);

  /**
   * Get audio URL for playback.
   */
  const getAudioUrl = useCallback(() => {
    if (state.audioBlob) {
      return URL.createObjectURL(state.audioBlob);
    }
    return null;
  }, [state.audioBlob]);

  /**
   * Convert audio blob to base64 string.
   */
  const getAudioBase64 = useCallback((): Promise<string | null> => {
    return new Promise((resolve) => {
      if (!state.audioBlob) {
        resolve(null);
        return;
      }

      const reader = new FileReader();
      reader.onloadend = () => {
        const base64 = reader.result as string;
        resolve(base64.split(',')[1]); // Remove data:audio/webm;base64, prefix
      };
      reader.onerror = () => resolve(null);
      reader.readAsDataURL(state.audioBlob);
    });
  }, [state.audioBlob]);

  /**
   * Get audio duration in seconds.
   */
  const getAudioDuration = useCallback((): Promise<number> => {
    return new Promise((resolve) => {
      if (!state.audioBlob) {
        resolve(0);
        return;
      }

      const audio = new Audio();
      const url = URL.createObjectURL(state.audioBlob);
      
      audio.onloadedmetadata = () => {
        resolve(audio.duration);
        URL.revokeObjectURL(url);
      };
      
      audio.onerror = () => {
        resolve(0);
        URL.revokeObjectURL(url);
      };
      
      audio.src = url;
    });
  }, [state.audioBlob]);

  /**
   * Format duration in MM:SS format.
   */
  const formatDuration = useCallback((seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  }, []);

  return {
    // State
    isRecording: state.isRecording,
    audioBlob: state.audioBlob,
    duration: state.duration,
    error: state.error,
    
    // Methods
    startRecording,
    stopRecording,
    clearAudio,
    getAudioUrl,
    getAudioBase64,
    getAudioDuration,
    formatDuration,
    isSupported,
    
    // Computed properties
    hasAudio: !!state.audioBlob,
    formattedDuration: formatDuration(state.duration),
  };
}

export type { AudioRecordingState };