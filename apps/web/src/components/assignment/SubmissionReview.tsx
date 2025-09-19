/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useRef, useEffect } from 'react';
import {
  Play,
  Pause,
  Volume2,
  VolumeX,
  FastForward,
  Rewind,
  Send,
  Download,
  Clock,
  User,
  CheckCircle,
  XCircle,
  AlertCircle,
  Mic,
  Save
} from 'lucide-react';
import { Submission } from '../../types/assignment';

interface SubmissionReviewProps {
  submission: Submission;
  onFeedbackSubmit: (feedback: {
    note?: string;
    audio?: File;
    score?: number;
    rubric?: RubricScores;
  }) => Promise<void>;
  onScoreUpdate: (score: number, rubric?: RubricScores) => Promise<void>;
  className?: string;
  showRubric?: boolean;
}

interface RubricScores {
  tajweed: number;
  fluency: number;
  memorization: number;
  pronunciation: number;
}

/**
 * Component for reviewing and grading student submissions.
 * Includes audio playback, rubric scoring, and feedback submission.
 */
const SubmissionReview: React.FC<SubmissionReviewProps> = ({
  submission,
  onFeedbackSubmit,
  onScoreUpdate,
  className = '',
  showRubric = true
}) => {
  // Audio playback state
  const [isPlaying, setIsPlaying] = useState(false);
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const [volume, setVolume] = useState(1);
  const [playbackRate, setPlaybackRate] = useState(1);
  const [isMuted, setIsMuted] = useState(false);
  
  // Feedback state
  const [feedbackNote, setFeedbackNote] = useState('');
  const [isRecordingFeedback, setIsRecordingFeedback] = useState(false);
  const [feedbackAudio, setFeedbackAudio] = useState<File | null>(null);
  const [feedbackAudioUrl, setFeedbackAudioUrl] = useState<string | null>(null);
  const [submittingFeedback, setSubmittingFeedback] = useState(false);
  
  // Scoring state
  const [overallScore, setOverallScore] = useState(submission.score || 0);
  const [rubricScores, setRubricScores] = useState<RubricScores>({
    tajweed: submission.rubric_json?.tajweed || 0,
    fluency: submission.rubric_json?.fluency || 0,
    memorization: submission.rubric_json?.memorization || 0,
    pronunciation: submission.rubric_json?.pronunciation || 0
  });
  const [savingScore, setSavingScore] = useState(false);
  
  // Refs
  const audioRef = useRef<HTMLAudioElement>(null);
  const mediaRecorderRef = useRef<MediaRecorder | null>(null);
  const audioChunksRef = useRef<Blob[]>([]);

  /**
   * Initialize audio element and event listeners.
   */
  useEffect(() => {
    const audio = audioRef.current;
    if (!audio) return;

    const handleLoadedMetadata = () => {
      setDuration(audio.duration);
    };

    const handleTimeUpdate = () => {
      setCurrentTime(audio.currentTime);
    };

    const handleEnded = () => {
      setIsPlaying(false);
      setCurrentTime(0);
    };

    audio.addEventListener('loadedmetadata', handleLoadedMetadata);
    audio.addEventListener('timeupdate', handleTimeUpdate);
    audio.addEventListener('ended', handleEnded);

    return () => {
      audio.removeEventListener('loadedmetadata', handleLoadedMetadata);
      audio.removeEventListener('timeupdate', handleTimeUpdate);
      audio.removeEventListener('ended', handleEnded);
    };
  }, [submission.audio_url]);

  /**
   * Toggle audio playback.
   */
  const togglePlayback = () => {
    const audio = audioRef.current;
    if (!audio) return;

    if (isPlaying) {
      audio.pause();
    } else {
      audio.play();
    }
    setIsPlaying(!isPlaying);
  };

  /**
   * Seek to specific time in audio.
   * @param time Time in seconds
   */
  const seekTo = (time: number) => {
    const audio = audioRef.current;
    if (!audio) return;

    audio.currentTime = Math.max(0, Math.min(duration, time));
    setCurrentTime(audio.currentTime);
  };

  /**
   * Skip forward/backward in audio.
   * @param seconds Seconds to skip (positive for forward, negative for backward)
   */
  const skip = (seconds: number) => {
    seekTo(currentTime + seconds);
  };

  /**
   * Update audio volume.
   * @param newVolume Volume level (0-1)
   */
  const updateVolume = (newVolume: number) => {
    const audio = audioRef.current;
    if (!audio) return;

    const volume = Math.max(0, Math.min(1, newVolume));
    audio.volume = volume;
    setVolume(volume);
    setIsMuted(volume === 0);
  };

  /**
   * Toggle mute.
   */
  const toggleMute = () => {
    const audio = audioRef.current;
    if (!audio) return;

    if (isMuted) {
      audio.volume = volume;
      setIsMuted(false);
    } else {
      audio.volume = 0;
      setIsMuted(true);
    }
  };

  /**
   * Update playback rate.
   * @param rate Playback rate (0.5-2.0)
   */
  const updatePlaybackRate = (rate: number) => {
    const audio = audioRef.current;
    if (!audio) return;

    const newRate = Math.max(0.5, Math.min(2, rate));
    audio.playbackRate = newRate;
    setPlaybackRate(newRate);
  };

  /**
   * Start recording feedback audio.
   */
  const startRecordingFeedback = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const mediaRecorder = new MediaRecorder(stream);
      
      audioChunksRef.current = [];
      
      mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
          audioChunksRef.current.push(event.data);
        }
      };
      
      mediaRecorder.onstop = () => {
        const audioBlob = new Blob(audioChunksRef.current, { type: 'audio/wav' });
        const audioFile = new File([audioBlob], 'feedback.wav', { type: 'audio/wav' });
        const audioUrl = URL.createObjectURL(audioBlob);
        
        setFeedbackAudio(audioFile);
        setFeedbackAudioUrl(audioUrl);
        
        // Stop all tracks
        stream.getTracks().forEach(track => track.stop());
      };
      
      mediaRecorderRef.current = mediaRecorder;
      mediaRecorder.start();
      setIsRecordingFeedback(true);
      
    } catch (error) {
      console.error('Error starting audio recording:', error);
    }
  };

  /**
   * Stop recording feedback audio.
   */
  const stopRecordingFeedback = () => {
    if (mediaRecorderRef.current && isRecordingFeedback) {
      mediaRecorderRef.current.stop();
      setIsRecordingFeedback(false);
    }
  };

  /**
   * Update rubric score and calculate overall score.
   * @param category Rubric category
   * @param score Score value (0-100)
   */
  const updateRubricScore = (category: keyof RubricScores, score: number) => {
    const newRubricScores = {
      ...rubricScores,
      [category]: Math.max(0, Math.min(100, score))
    };
    
    setRubricScores(newRubricScores);
    
    // Calculate overall score as average of rubric scores
    const average = Object.values(newRubricScores).reduce((sum, val) => sum + val, 0) / 4;
    setOverallScore(Math.round(average));
  };

  /**
   * Save score and rubric data.
   */
  const saveScore = async () => {
    try {
      setSavingScore(true);
      await onScoreUpdate(overallScore, rubricScores);
    } catch (error) {
      console.error('Error saving score:', error);
    } finally {
      setSavingScore(false);
    }
  };

  /**
   * Submit feedback.
   */
  const submitFeedback = async () => {
    try {
      setSubmittingFeedback(true);
      
      await onFeedbackSubmit({
        note: feedbackNote.trim() || undefined,
        audio: feedbackAudio || undefined,
        score: overallScore,
        rubric: rubricScores
      });
      
      // Reset feedback form
      setFeedbackNote('');
      setFeedbackAudio(null);
      setFeedbackAudioUrl(null);
      
    } catch (error) {
      console.error('Error submitting feedback:', error);
    } finally {
      setSubmittingFeedback(false);
    }
  };

  /**
   * Format time for display.
   * @param seconds Time in seconds
   * @returns Formatted time string (mm:ss)
   */
  const formatTime = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  /**
   * Get status color based on submission status and score.
   * @returns Tailwind color class
   */
  const getStatusColor = (): string => {
    if (submission.status === 'pending') return 'text-yellow-600';
    if (overallScore >= 80) return 'text-green-600';
    if (overallScore >= 60) return 'text-yellow-600';
    return 'text-red-600';
  };

  /**
   * Get status icon based on submission status and score.
   */
  const getStatusIcon = () => {
    if (submission.status === 'pending') return <Clock className="h-5 w-5" />;
    if (overallScore >= 80) return <CheckCircle className="h-5 w-5" />;
    if (overallScore >= 60) return <AlertCircle className="h-5 w-5" />;
    return <XCircle className="h-5 w-5" />;
  };

  return (
    <div className={`bg-white rounded-lg shadow-sm border ${className}`}>
      {/* Header */}
      <div className="p-6 border-b border-gray-200">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-4">
            <div className="flex items-center space-x-2">
              <User className="h-5 w-5 text-gray-600" />
              <span className="font-medium text-gray-900">
                {submission.student?.name || 'Unknown Student'}
              </span>
            </div>
            
            <div className={`flex items-center space-x-2 ${getStatusColor()}`}>
              {getStatusIcon()}
              <span className="text-sm font-medium capitalize">
                {submission.status}
              </span>
            </div>
          </div>
          
          <div className="flex items-center space-x-4">
            <div className="text-right">
              <div className="text-2xl font-bold text-maroon-600">
                {overallScore}/100
              </div>
              <div className="text-sm text-gray-600">Overall Score</div>
            </div>
            
            {submission.audio_url && (
              <button
                onClick={() => {
                  const link = document.createElement('a');
                  link.href = submission.audio_url!;
                  link.download = `submission-${submission.id}.mp3`;
                  link.click();
                }}
                className="p-2 text-gray-600 hover:bg-gray-100 rounded-lg"
                title="Download Audio"
              >
                <Download className="h-4 w-4" />
              </button>
            )}
          </div>
        </div>
      </div>

      <div className="p-6 space-y-6">
        {/* Audio Player */}
        {submission.audio_url && (
          <div className="bg-gray-50 rounded-lg p-4">
            <h4 className="text-lg font-semibold text-maroon-800 mb-4">
              Student Submission
            </h4>
            
            <audio ref={audioRef} src={submission.audio_url} preload="metadata" />
            
            {/* Playback Controls */}
            <div className="flex items-center space-x-4 mb-4">
              <button
                onClick={() => skip(-10)}
                className="p-2 text-gray-600 hover:bg-gray-200 rounded-lg"
                title="Rewind 10s"
              >
                <Rewind className="h-4 w-4" />
              </button>
              
              <button
                onClick={togglePlayback}
                className="p-3 bg-maroon-600 text-white rounded-full hover:bg-maroon-700"
              >
                {isPlaying ? <Pause className="h-5 w-5" /> : <Play className="h-5 w-5" />}
              </button>
              
              <button
                onClick={() => skip(10)}
                className="p-2 text-gray-600 hover:bg-gray-200 rounded-lg"
                title="Forward 10s"
              >
                <FastForward className="h-4 w-4" />
              </button>
              
              <div className="flex items-center space-x-2">
                <button onClick={toggleMute} className="p-1 text-gray-600 hover:bg-gray-200 rounded">
                  {isMuted ? <VolumeX className="h-4 w-4" /> : <Volume2 className="h-4 w-4" />}
                </button>
                <input
                  type="range"
                  min="0"
                  max="1"
                  step="0.1"
                  value={isMuted ? 0 : volume}
                  onChange={(e) => updateVolume(parseFloat(e.target.value))}
                  className="w-20"
                />
              </div>
              
              <select
                value={playbackRate}
                onChange={(e) => updatePlaybackRate(parseFloat(e.target.value))}
                className="px-2 py-1 border border-gray-300 rounded text-sm"
              >
                <option value={0.5}>0.5x</option>
                <option value={0.75}>0.75x</option>
                <option value={1}>1x</option>
                <option value={1.25}>1.25x</option>
                <option value={1.5}>1.5x</option>
                <option value={2}>2x</option>
              </select>
            </div>
            
            {/* Progress Bar */}
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm text-gray-600">
                <span>{formatTime(currentTime)}</span>
                <span>{formatTime(duration)}</span>
              </div>
              
              <div className="relative">
                <div className="w-full h-2 bg-gray-200 rounded-full">
                  <div
                    className="h-2 bg-maroon-600 rounded-full transition-all"
                    style={{ width: `${duration > 0 ? (currentTime / duration) * 100 : 0}%` }}
                  />
                </div>
                <input
                  type="range"
                  min="0"
                  max={duration || 0}
                  value={currentTime}
                  onChange={(e) => seekTo(parseFloat(e.target.value))}
                  className="absolute inset-0 w-full h-2 opacity-0 cursor-pointer"
                />
              </div>
            </div>
          </div>
        )}

        {/* Rubric Scoring */}
        {showRubric && (
          <div className="bg-gray-50 rounded-lg p-4">
            <div className="flex items-center justify-between mb-4">
              <h4 className="text-lg font-semibold text-maroon-800">
                Rubric Assessment
              </h4>
              <button
                onClick={saveScore}
                disabled={savingScore}
                className="flex items-center space-x-2 px-4 py-2 bg-maroon-600 text-white rounded-lg hover:bg-maroon-700 disabled:opacity-50"
              >
                <Save className="h-4 w-4" />
                <span>{savingScore ? 'Saving...' : 'Save Score'}</span>
              </button>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {Object.entries(rubricScores).map(([category, score]) => (
                <div key={category} className="space-y-2">
                  <div className="flex items-center justify-between">
                    <label className="text-sm font-medium text-gray-700 capitalize">
                      {category}
                    </label>
                    <span className="text-sm font-semibold text-maroon-600">
                      {score}/100
                    </span>
                  </div>
                  
                  <input
                    type="range"
                    min="0"
                    max="100"
                    value={score}
                    onChange={(e) => updateRubricScore(category as keyof RubricScores, parseInt(e.target.value))}
                    className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
                  />
                  
                  <div className="flex justify-between text-xs text-gray-500">
                    <span>Poor</span>
                    <span>Good</span>
                    <span>Excellent</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Feedback Section */}
        <div className="bg-gray-50 rounded-lg p-4">
          <h4 className="text-lg font-semibold text-maroon-800 mb-4">
            Provide Feedback
          </h4>
          
          <div className="space-y-4">
            {/* Text Feedback */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Written Feedback
              </label>
              <textarea
                value={feedbackNote}
                onChange={(e) => setFeedbackNote(e.target.value)}
                rows={4}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500 focus:border-transparent"
                placeholder="Provide detailed feedback on the student's performance..."
              />
            </div>
            
            {/* Audio Feedback */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Audio Feedback
              </label>
              
              <div className="flex items-center space-x-3">
                <button
                  onClick={isRecordingFeedback ? stopRecordingFeedback : startRecordingFeedback}
                  className={`flex items-center space-x-2 px-4 py-2 rounded-lg ${
                    isRecordingFeedback
                      ? 'bg-red-600 text-white'
                      : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                  }`}
                >
                  <Mic className="h-4 w-4" />
                  <span>
                    {isRecordingFeedback ? 'Stop Recording' : 'Record Audio'}
                  </span>
                </button>
                
                {feedbackAudioUrl && (
                  <div className="flex-1">
                    <audio controls className="w-full">
                      <source src={feedbackAudioUrl} />
                    </audio>
                  </div>
                )}
              </div>
            </div>
            
            {/* Submit Button */}
            <div className="flex justify-end">
              <button
                onClick={submitFeedback}
                disabled={submittingFeedback || (!feedbackNote.trim() && !feedbackAudio)}
                className="flex items-center space-x-2 px-6 py-2 bg-maroon-600 text-white rounded-lg hover:bg-maroon-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Send className="h-4 w-4" />
                <span>
                  {submittingFeedback ? 'Submitting...' : 'Submit Feedback'}
                </span>
              </button>
            </div>
          </div>
        </div>

        {/* Existing Feedback */}
        {submission.feedback && submission.feedback.length > 0 && (
          <div className="bg-blue-50 rounded-lg p-4">
            <h4 className="text-lg font-semibold text-maroon-800 mb-4">
              Previous Feedback
            </h4>
            
            <div className="space-y-4">
              {submission.feedback.map((feedback, index) => (
                <div key={index} className="bg-white rounded-lg p-4 border">
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm font-medium text-gray-700">
                      {feedback.teacher?.name || 'Teacher'}
                    </span>
                    <span className="text-sm text-gray-500">
                      {new Date(feedback.created_at).toLocaleDateString()}
                    </span>
                  </div>
                  
                  {feedback.note && (
                    <p className="text-gray-800 mb-2">{feedback.note}</p>
                  )}
                  
                  {feedback.audio_url && (
                    <audio controls className="w-full">
                      <source src={feedback.audio_url} />
                    </audio>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default SubmissionReview;