/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  BookOpen,
  Mic,
  Play,
  Pause,
  Square,
  Clock,
  RotateCcw,
  Send,
  Award
} from 'lucide-react';
import { useSpiritualTheme, SpiritualCard, SpiritualButton } from '@/components/providers/SpiritualThemeProvider';

/**
 * Assignment interface for student dashboard.
 */
interface Assignment {
  id: string;
  title: string;
  description: string;
  dueAt?: string;
  imageUrl?: string;
  hotspots?: Hotspot[];
  status: 'draft' | 'published';
  classId?: string;
  teacherId: string;
}

/**
 * Hotspot interface for interactive assignment areas.
 */
interface Hotspot {
  id: string;
  title?: string;
  tooltip?: string;
  audioUrl?: string;
  x: number;
  y: number;
  width: number;
  height: number;
}

type RubricScores = Partial<
  Record<'tajweed' | 'fluency' | 'memorization' | 'pronunciation', number>
>;

/**
 * Submission interface for student work.
 */
interface Submission {
  id: string;
  assignmentId: string;
  studentId: string;
  status: 'pending' | 'graded';
  score?: number;
  audioUrl?: string;
  rubricJson?: RubricScores;
  createdAt: string;
}

/**
 * Props for StudentAssignmentDashboard component.
 */
interface StudentAssignmentDashboardProps {
  assignments?: Assignment[];
  onSubmissionComplete?: (submission: Submission) => void;
  className?: string;
}

/**
 * Student assignment dashboard with spiritual theme integration.
 * Provides assignment viewing, audio recording, and submission functionality.
 */
export const StudentAssignmentDashboard: React.FC<StudentAssignmentDashboardProps> = ({
  assignments = [],
  onSubmissionComplete,
  className = ''
}) => {
  const { theme, animations } = useSpiritualTheme();
  const [selectedAssignment, setSelectedAssignment] = useState<Assignment | null>(null);
  const [isRecording, setIsRecording] = useState(false);
  const [recordedBlob, setRecordedBlob] = useState<Blob | null>(null);
  const [isPlaying, setIsPlaying] = useState(false);
  const [recordingTime, setRecordingTime] = useState(0);
  const [submissions, setSubmissions] = useState<{ [key: string]: Submission }>({});
  const [hasanatEarned, setHasanatEarned] = useState(0);
  
  const mediaRecorderRef = useRef<MediaRecorder | null>(null);
  const audioChunksRef = useRef<Blob[]>([]);
  const audioRef = useRef<HTMLAudioElement | null>(null);
  const recordingIntervalRef = useRef<NodeJS.Timeout | null>(null);

  /**
   * Initialize media recorder for audio recording.
   */
  const initializeMediaRecorder = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const mediaRecorder = new MediaRecorder(stream);
      
      mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
          audioChunksRef.current.push(event.data);
        }
      };
      
      mediaRecorder.onstop = () => {
        const audioBlob = new Blob(audioChunksRef.current, { type: 'audio/wav' });
        setRecordedBlob(audioBlob);
        audioChunksRef.current = [];
      };
      
      mediaRecorderRef.current = mediaRecorder;
    } catch (error) {
      console.error('Error accessing microphone:', error);
    }
  };

  /**
   * Start audio recording with timer.
   */
  const startRecording = async () => {
    if (!mediaRecorderRef.current) {
      await initializeMediaRecorder();
    }
    
    if (mediaRecorderRef.current && mediaRecorderRef.current.state === 'inactive') {
      setRecordedBlob(null);
      setRecordingTime(0);
      mediaRecorderRef.current.start();
      setIsRecording(true);
      
      recordingIntervalRef.current = setInterval(() => {
        setRecordingTime(prev => prev + 1);
      }, 1000);
    }
  };

  /**
   * Stop audio recording and clear timer.
   */
  const stopRecording = () => {
    if (mediaRecorderRef.current && mediaRecorderRef.current.state === 'recording') {
      mediaRecorderRef.current.stop();
      setIsRecording(false);
      
      if (recordingIntervalRef.current) {
        clearInterval(recordingIntervalRef.current);
        recordingIntervalRef.current = null;
      }
    }
  };

  /**
   * Toggle audio playback for recorded audio.
   */
  const togglePlayback = () => {
    if (!recordedBlob) return;
    
    if (!audioRef.current) {
      audioRef.current = new Audio(URL.createObjectURL(recordedBlob));
      audioRef.current.onended = () => setIsPlaying(false);
    }
    
    if (isPlaying) {
      audioRef.current.pause();
      setIsPlaying(false);
    } else {
      audioRef.current.play();
      setIsPlaying(true);
    }
  };

  /**
   * Reset recording state and clear audio.
   */
  const resetRecording = () => {
    setRecordedBlob(null);
    setRecordingTime(0);
    setIsPlaying(false);
    
    if (audioRef.current) {
      audioRef.current.pause();
      audioRef.current = null;
    }
  };

  /**
   * Submit recorded audio as assignment submission.
   */
  const submitRecording = async () => {
    if (!recordedBlob || !selectedAssignment) return;
    
    try {
      // Create form data for audio upload
      const formData = new FormData();
      formData.append('audio', recordedBlob, 'recording.wav');
      formData.append('assignment_id', selectedAssignment.id);
      
      // Submit to API (placeholder)
      const response = await fetch('/api/submissions', {
        method: 'POST',
        body: formData,
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      
      if (response.ok) {
        const submission = await response.json();
        setSubmissions(prev => ({
          ...prev,
          [selectedAssignment.id]: submission
        }));
        
        // Calculate hasanat (placeholder calculation)
        const hasanat = Math.floor(recordingTime * 10);
        setHasanatEarned(prev => prev + hasanat);
        
        onSubmissionComplete?.(submission);
        setSelectedAssignment(null);
        resetRecording();
      }
    } catch (error) {
      console.error('Error submitting recording:', error);
    }
  };

  /**
   * Format recording time as MM:SS.
   */
  const formatTime = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  };

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      if (recordingIntervalRef.current) {
        clearInterval(recordingIntervalRef.current);
      }
      if (audioRef.current) {
        audioRef.current.pause();
      }
    };
  }, []);

  return (
    <motion.div 
      className={`max-w-7xl mx-auto p-6 space-y-6 ${className}`}
      {...animations.pageTransition}
    >
      {/* Header */}
      <SpiritualCard className="p-6" glow>
        <div className="flex items-center justify-between">
          <motion.div {...animations.fadeInUp}>
            <h1 className="text-2xl font-bold mb-2" style={{ color: theme.colors.maroon[800] }}>
              My Assignments
            </h1>
            <p style={{ color: theme.colors.maroon[600] }}>
              Complete your Qur&apos;an recitation assignments and earn hasanat
            </p>
          </motion.div>
          <motion.div 
            className="text-right"
            {...animations.slideInRight}
          >
            <div 
              className="px-4 py-2 rounded-lg"
              style={{
                background: theme.gradients.secondary,
                color: theme.colors.maroon[900],
                boxShadow: theme.shadows.gold
              }}
            >
              <div className="flex items-center space-x-2">
                <Award className="w-5 h-5" />
                <span className="font-bold">{hasanatEarned} Hasanat</span>
              </div>
            </div>
          </motion.div>
        </div>
      </SpiritualCard>

      {/* Assignment Grid */}
      <motion.div 
        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        {...animations.staggerChildren}
      >
        {assignments.map((assignment, index) => {
          const submission = submissions[assignment.id];
          const isCompleted = submission?.status === 'graded';
          const isPending = submission?.status === 'pending';
          
          return (
            <motion.div
              key={assignment.id}
              {...animations.fadeInUp}
              transition={{ delay: index * 0.1 }}
            >
              <SpiritualCard
                className={`border-2 transition-all duration-200 cursor-pointer ${
                  selectedAssignment?.id === assignment.id
                    ? 'shadow-xl'
                    : 'hover:shadow-xl'
                }`}
                style={{
                  borderColor: selectedAssignment?.id === assignment.id 
                    ? theme.colors.gold[400] 
                    : theme.colors.gold[200]
                }}
                onClick={() => setSelectedAssignment(assignment)}
                hover
              >
                <div className="p-6">
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex items-center space-x-3">
                      <div 
                        className="p-2 rounded-lg"
                        style={{ 
                          backgroundColor: `${theme.colors.maroon[600]}20`,
                          color: theme.colors.maroon[600]
                        }}
                      >
                        <BookOpen className="w-6 h-6" />
                      </div>
                      <div>
                        <h3 
                          className="font-semibold"
                          style={{ color: theme.colors.maroon[900] }}
                        >
                          {assignment.title}
                        </h3>
                        <p 
                          className="text-sm"
                          style={{ color: theme.colors.maroon[600] }}
                        >
                          {assignment.description}
                        </p>
                      </div>
                    </div>
                    
                    {/* Status Badge */}
                    <div 
                      className="px-2 py-1 rounded-full text-xs font-medium"
                      style={{
                        backgroundColor: isCompleted
                          ? `${theme.colors.accent.emerald}20`
                          : isPending
                          ? `${theme.colors.gold[400]}20`
                          : `${theme.colors.milk[400]}20`,
                        color: isCompleted
                          ? theme.colors.accent.emerald
                          : isPending
                          ? theme.colors.gold[700]
                          : theme.colors.milk[800]
                      }}
                    >
                      {isCompleted ? 'Completed' : isPending ? 'Pending' : 'Not Started'}
                    </div>
                  </div>

                  {/* Assignment Preview */}
                  {assignment.imageUrl && (
                    <div className="mb-4">
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img
                        src={assignment.imageUrl}
                        alt={assignment.title}
                        className="w-full h-32 object-cover rounded-lg"
                      />
                    </div>
                  )}

                  {/* Due Date */}
                  {assignment.dueAt && (
                    <div className="flex items-center space-x-2 text-sm" style={{ color: theme.colors.maroon[600] }}>
                      <Clock className="w-4 h-4" />
                      <span>Due: {new Date(assignment.dueAt).toLocaleDateString()}</span>
                    </div>
                  )}

                  {/* Progress Indicator */}
                  {submission && (
                    <div className="mt-4">
                      <div className="flex items-center justify-between text-sm mb-2">
                        <span style={{ color: theme.colors.maroon[600] }}>Progress</span>
                        <span style={{ color: theme.colors.maroon[800] }}>
                          {submission.score ? `${submission.score}%` : 'Submitted'}
                        </span>
                      </div>
                      <div 
                        className="w-full h-2 rounded-full"
                        style={{ backgroundColor: theme.colors.milk[300] }}
                      >
                        <div 
                          className="h-2 rounded-full transition-all duration-300"
                          style={{ 
                            width: `${submission.score || 50}%`,
                            background: theme.gradients.primary
                          }}
                        />
                      </div>
                    </div>
                  )}
                </div>
              </SpiritualCard>
            </motion.div>
          );
        })}
      </motion.div>

      {/* Assignment Details Modal */}
      <AnimatePresence>
        {selectedAssignment && (
          <motion.div
            className="fixed inset-0 flex items-center justify-center p-4 z-50"
            style={{ backgroundColor: 'rgba(0, 0, 0, 0.5)' }}
            {...animations.modal.overlay}
            onClick={() => setSelectedAssignment(null)}
          >
            <motion.div
              className="rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
              style={{ 
                background: theme.colors.milk[50],
                boxShadow: theme.shadows.xl
              }}
              {...animations.modal.content}
              onClick={(e) => e.stopPropagation()}
            >
              {/* Modal Header */}
              <div 
                className="p-6 border-b"
                style={{ borderColor: theme.colors.gold[200] }}
              >
                <div className="flex items-center justify-between">
                  <motion.div {...animations.fadeInUp}>
                    <h2 
                      className="text-xl font-bold mb-2"
                      style={{ color: theme.colors.maroon[900] }}
                    >
                      {selectedAssignment.title}
                    </h2>
                    <p style={{ color: theme.colors.maroon[600] }}>
                      {selectedAssignment.description}
                    </p>
                  </motion.div>
                  <button
                    onClick={() => setSelectedAssignment(null)}
                    className="text-2xl transition-colors"
                    style={{ 
                      color: theme.colors.maroon[400],
                      ':hover': { color: theme.colors.maroon[600] }
                    }}
                  >
                    ×
                  </button>
                </div>
              </div>

              {/* Assignment Content */}
              <div className="p-6 space-y-6">
                {/* Assignment Image with Hotspots */}
                {selectedAssignment.imageUrl && (
                  <div className="relative">
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img
                      src={selectedAssignment.imageUrl}
                      alt={selectedAssignment.title}
                      className="w-full rounded-lg shadow-lg"
                    />
                    {/* Hotspots overlay would go here */}
                  </div>
                )}

                {/* Recording Section */}
                <SpiritualCard className="p-6">
                  <h3 
                    className="text-lg font-semibold mb-4"
                    style={{ color: theme.colors.maroon[800] }}
                  >
                    Record Your Recitation
                  </h3>
                  
                  {/* Recording Controls */}
                  <div className="flex items-center space-x-4 mb-4">
                    <SpiritualButton
                      variant="accent"
                      onClick={startRecording}
                      disabled={isRecording}
                      className="flex items-center space-x-2"
                    >
                      <Mic className="w-5 h-5" />
                      <span>{isRecording ? 'Recording...' : 'Start Recording'}</span>
                    </SpiritualButton>
                    
                    <SpiritualButton
                      variant="ghost"
                      onClick={stopRecording}
                      disabled={!isRecording}
                      className="flex items-center space-x-2"
                    >
                      <Square className="w-5 h-5" />
                      <span>Stop</span>
                    </SpiritualButton>
                    
                    {/* Recording Timer */}
                    {(isRecording || recordingTime > 0) && (
                      <div 
                        className="flex items-center space-x-2 px-3 py-2 rounded-lg"
                        style={{ 
                          backgroundColor: theme.colors.maroon[100],
                          color: theme.colors.maroon[800]
                        }}
                      >
                        <Clock className="w-4 h-4" />
                        <span className="font-mono">{formatTime(recordingTime)}</span>
                      </div>
                    )}
                  </div>
                  
                  {/* Playback Controls */}
                  {recordedBlob && (
                    <div className="flex items-center space-x-4 mb-4">
                      <SpiritualButton
                        variant="primary"
                        onClick={togglePlayback}
                        className="flex items-center space-x-2"
                      >
                        {isPlaying ? <Pause className="w-5 h-5" /> : <Play className="w-5 h-5" />}
                        <span>{isPlaying ? 'Pause' : 'Play'}</span>
                      </SpiritualButton>
                      
                      <SpiritualButton
                        variant="ghost"
                        onClick={resetRecording}
                        className="flex items-center space-x-2"
                      >
                        <RotateCcw className="w-5 h-5" />
                        <span>Reset</span>
                      </SpiritualButton>
                    </div>
                  )}
                  
                  {/* Submit Button */}
                  <SpiritualButton
                    variant="primary"
                    onClick={submitRecording}
                    disabled={!recordedBlob}
                    className="w-full py-3 px-4 font-medium flex items-center justify-center space-x-2"
                  >
                    <Send className="w-5 h-5" />
                    <span>Submit Recording</span>
                  </SpiritualButton>
                </SpiritualCard>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </motion.div>
  );
};

export default StudentAssignmentDashboard;