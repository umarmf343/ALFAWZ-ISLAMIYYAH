/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useRouter } from 'next/router';
import { 
  ArrowLeft, 
  Play, 
  Pause, 
  Square, 
  Mic, 
  MicOff, 
  Upload, 
  Download,
  Volume2,
  VolumeX,
  Clock,
  CheckCircle,
  AlertCircle,
  BookOpen,
  Send
} from 'lucide-react';
import Layout from '../../../components/Layout';
import HotspotComponent from '../../../components/assignment/HotspotComponent';
import AudioRecorder from '../../../components/assignment/AudioRecorder';
import { useAuth } from '../../../hooks/useAuth';
import { useAssignment } from '../../../hooks/useAssignment';
import { Assignment, Hotspot, Submission } from '../../../types/assignment';

/**
 * Assignment Detail Page - displays assignment with interactive hotspots and audio recording.
 * Allows students to interact with hotspots, record audio, and submit their work.
 */
const AssignmentDetailPage: React.FC = () => {
  const router = useRouter();
  const { id } = router.query;
  const { user } = useAuth();
  const { assignment, submission, loading, error, submitAssignment, uploadAudio } = useAssignment(Number(id));
  
  const [selectedHotspot, setSelectedHotspot] = useState<Hotspot | null>(null);
  const [isRecording, setIsRecording] = useState(false);
  const [audioBlob, setAudioBlob] = useState<Blob | null>(null);
  const [isPlaying, setIsPlaying] = useState(false);
  const [currentAudio, setCurrentAudio] = useState<HTMLAudioElement | null>(null);
  const [showSubmitModal, setShowSubmitModal] = useState(false);
  const imageRef = useRef<HTMLImageElement>(null);
  const audioRef = useRef<HTMLAudioElement>(null);

  /**
   * Handle hotspot click interaction.
   * @param hotspot Hotspot object that was clicked
   * @param event Mouse event for positioning
   */
  const handleHotspotClick = async (hotspot: Hotspot, event: React.MouseEvent) => {
    setSelectedHotspot(hotspot);
    
    // Record interaction
    try {
      await fetch(`/api/hotspots/${hotspot.id}/interact`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({
          interaction_type: 'click',
          metadata: {
            x: event.clientX,
            y: event.clientY
          }
        })
      });
    } catch (error) {
      console.error('Failed to record interaction:', error);
    }
  };

  /**
   * Play hotspot audio.
   * @param audioUrl URL of the audio file to play
   */
  const playHotspotAudio = async (audioUrl: string) => {
    if (currentAudio) {
      currentAudio.pause();
      setCurrentAudio(null);
      setIsPlaying(false);
    }

    const audio = new Audio(audioUrl);
    audio.onplay = () => setIsPlaying(true);
    audio.onpause = () => setIsPlaying(false);
    audio.onended = () => {
      setIsPlaying(false);
      setCurrentAudio(null);
    };
    
    setCurrentAudio(audio);
    await audio.play();
  };

  /**
   * Handle audio recording completion.
   * @param blob Recorded audio blob
   */
  const handleRecordingComplete = (blob: Blob) => {
    setAudioBlob(blob);
    setIsRecording(false);
  };

  /**
   * Upload recorded audio to server.
   */
  const handleAudioUpload = async () => {
    if (!audioBlob || !assignment) return;

    try {
      await uploadAudio(audioBlob);
      alert('Audio uploaded successfully!');
    } catch (error) {
      console.error('Failed to upload audio:', error);
      alert('Failed to upload audio. Please try again.');
    }
  };

  /**
   * Submit assignment for grading.
   */
  const handleSubmitAssignment = async () => {
    if (!assignment) return;

    try {
      await submitAssignment();
      setShowSubmitModal(false);
      alert('Assignment submitted successfully!');
    } catch (error) {
      console.error('Failed to submit assignment:', error);
      alert('Failed to submit assignment. Please try again.');
    }
  };

  /**
   * Get assignment status with styling.
   */
  const getAssignmentStatus = () => {
    if (!assignment) return null;
    
    const now = new Date();
    const dueDate = assignment.due_at ? new Date(assignment.due_at) : null;
    
    if (submission?.status === 'graded') {
      return {
        label: 'Completed',
        color: 'text-green-600 bg-green-50 border-green-200',
        icon: CheckCircle
      };
    }
    
    if (dueDate && now > dueDate) {
      return {
        label: 'Overdue',
        color: 'text-red-600 bg-red-50 border-red-200',
        icon: AlertCircle
      };
    }
    
    return {
      label: 'In Progress',
      color: 'text-yellow-600 bg-yellow-50 border-yellow-200',
      icon: Clock
    };
  };

  if (loading) {
    return (
      <Layout>
        <div className="flex items-center justify-center min-h-screen">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-maroon-600"></div>
        </div>
      </Layout>
    );
  }

  if (error || !assignment) {
    return (
      <Layout>
        <div className="flex items-center justify-center min-h-screen">
          <div className="text-red-600 text-center">
            <AlertCircle className="h-12 w-12 mx-auto mb-4" />
            <p>Error loading assignment: {error || 'Assignment not found'}</p>
            <button
              onClick={() => router.back()}
              className="mt-4 px-4 py-2 bg-maroon-600 text-white rounded-lg hover:bg-maroon-700"
            >
              Go Back
            </button>
          </div>
        </div>
      </Layout>
    );
  }

  const status = getAssignmentStatus();
  const StatusIcon = status?.icon;

  return (
    <Layout>
      <div className="min-h-screen bg-gradient-to-br from-milk-50 to-gold-50">
        {/* Header */}
        <div className="bg-white shadow-sm border-b">
          <div className="max-w-7xl mx-auto px-6 py-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-4">
                <button
                  onClick={() => router.back()}
                  className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  <ArrowLeft className="h-5 w-5 text-gray-600" />
                </button>
                <div>
                  <h1 className="text-2xl font-bold text-maroon-800">
                    {assignment.title}
                  </h1>
                  {assignment.due_at && (
                    <p className="text-sm text-gray-600">
                      Due: {new Date(assignment.due_at).toLocaleDateString()}
                    </p>
                  )}
                </div>
              </div>
              
              {status && StatusIcon && (
                <div className={`flex items-center px-3 py-2 rounded-lg border ${status.color}`}>
                  <StatusIcon className="h-4 w-4 mr-2" />
                  <span className="font-medium">{status.label}</span>
                </div>
              )}
            </div>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-6 py-8">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Main Assignment Area */}
            <div className="lg:col-span-2">
              <div className="bg-white rounded-lg shadow-sm overflow-hidden">
                {/* Assignment Image with Hotspots */}
                {assignment.image_s3_url && (
                  <div className="relative">
                    <img
                      ref={imageRef}
                      src={assignment.image_s3_url}
                      alt={assignment.title}
                      className="w-full h-auto"
                    />
                    
                    {/* Render Hotspots */}
                    {assignment.hotspots?.map((hotspot: Hotspot) => (
                      <HotspotComponent
                        key={hotspot.id}
                        hotspot={hotspot}
                        onClick={handleHotspotClick}
                        isSelected={selectedHotspot?.id === hotspot.id}
                      />
                    ))}
                  </div>
                )}

                {/* Assignment Description */}
                {assignment.description && (
                  <div className="p-6">
                    <h3 className="text-lg font-semibold text-maroon-800 mb-3">
                      Instructions
                    </h3>
                    <p className="text-gray-700 leading-relaxed">
                      {assignment.description}
                    </p>
                  </div>
                )}
              </div>
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              {/* Hotspot Details */}
              <AnimatePresence>
                {selectedHotspot && (
                  <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -20 }}
                    className="bg-white rounded-lg shadow-sm p-6"
                  >
                    <h3 className="text-lg font-semibold text-maroon-800 mb-3">
                      {selectedHotspot.title || 'Hotspot Details'}
                    </h3>
                    
                    {selectedHotspot.tooltip && (
                      <p className="text-gray-700 mb-4">
                        {selectedHotspot.tooltip}
                      </p>
                    )}

                    {selectedHotspot.audio_s3_url && (
                      <button
                        onClick={() => playHotspotAudio(selectedHotspot.audio_s3_url!)}
                        className="flex items-center space-x-2 px-4 py-2 bg-maroon-600 text-white rounded-lg hover:bg-maroon-700 transition-colors"
                      >
                        {isPlaying ? (
                          <>
                            <Pause className="h-4 w-4" />
                            <span>Pause Audio</span>
                          </>
                        ) : (
                          <>
                            <Play className="h-4 w-4" />
                            <span>Play Audio</span>
                          </>
                        )}
                      </button>
                    )}
                  </motion.div>
                )}
              </AnimatePresence>

              {/* Audio Recording */}
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h3 className="text-lg font-semibold text-maroon-800 mb-4">
                  Record Your Recitation
                </h3>
                
                <AudioRecorder
                  onRecordingComplete={handleRecordingComplete}
                  isRecording={isRecording}
                  onRecordingStart={() => setIsRecording(true)}
                  onRecordingStop={() => setIsRecording(false)}
                />

                {audioBlob && (
                  <div className="mt-4 space-y-3">
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600">Recording ready</span>
                      <button
                        onClick={handleAudioUpload}
                        className="flex items-center space-x-2 px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700"
                      >
                        <Upload className="h-3 w-3" />
                        <span>Upload</span>
                      </button>
                    </div>
                  </div>
                )}

                {submission?.audio_s3_url && (
                  <div className="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <div className="flex items-center space-x-2 text-green-700">
                      <CheckCircle className="h-4 w-4" />
                      <span className="text-sm font-medium">Audio submitted</span>
                    </div>
                  </div>
                )}
              </div>

              {/* Assignment Stats */}
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h3 className="text-lg font-semibold text-maroon-800 mb-4">
                  Assignment Details
                </h3>
                
                <div className="space-y-3 text-sm">
                  <div className="flex items-center justify-between">
                    <span className="text-gray-600">Hotspots</span>
                    <span className="font-medium">{assignment.hotspots?.length || 0}</span>
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <span className="text-gray-600">Status</span>
                    <span className="font-medium">{submission?.status || 'Not started'}</span>
                  </div>
                  
                  {submission?.score && (
                    <div className="flex items-center justify-between">
                      <span className="text-gray-600">Score</span>
                      <span className="font-medium">{submission.score}/100</span>
                    </div>
                  )}
                </div>
              </div>

              {/* Submit Assignment */}
              {submission?.audio_s3_url && submission.status === 'pending' && (
                <button
                  onClick={() => setShowSubmitModal(true)}
                  className="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-maroon-600 text-white rounded-lg hover:bg-maroon-700 transition-colors font-medium"
                >
                  <Send className="h-4 w-4" />
                  <span>Submit for Grading</span>
                </button>
              )}
            </div>
          </div>
        </div>

        {/* Submit Confirmation Modal */}
        <AnimatePresence>
          {showSubmitModal && (
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
              onClick={() => setShowSubmitModal(false)}
            >
              <motion.div
                initial={{ scale: 0.95, opacity: 0 }}
                animate={{ scale: 1, opacity: 1 }}
                exit={{ scale: 0.95, opacity: 0 }}
                className="bg-white rounded-lg p-6 max-w-md mx-4"
                onClick={(e) => e.stopPropagation()}
              >
                <h3 className="text-lg font-semibold text-maroon-800 mb-4">
                  Submit Assignment
                </h3>
                <p className="text-gray-600 mb-6">
                  Are you sure you want to submit this assignment for grading? You won&apos;t be able to make changes after submission.
                </p>
                <div className="flex space-x-3">
                  <button
                    onClick={() => setShowSubmitModal(false)}
                    className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    onClick={handleSubmitAssignment}
                    className="flex-1 px-4 py-2 bg-maroon-600 text-white rounded-lg hover:bg-maroon-700"
                  >
                    Submit
                  </button>
                </div>
              </motion.div>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </Layout>
  );
};

export default AssignmentDetailPage;