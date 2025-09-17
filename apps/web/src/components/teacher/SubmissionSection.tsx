/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useState, useRef, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useTranslations } from 'next-intl';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Textarea } from '@/components/ui/textarea';
import { Slider } from '@/components/ui/slider';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { 
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { 
  Play, 
  Pause, 
  Volume2, 
  VolumeX,
  Clock,
  User,
  Calendar,
  FileAudio,
  CheckCircle,
  XCircle,
  MessageSquare,
  Star,
  BookOpen,
  Filter,
  Search,
  Download,
  Eye,
  RotateCcw,
  Sparkles
} from 'lucide-react';
import { api } from '@/lib/api';

// Types for submission data
interface Submission {
  id: string;
  studentName: string;
  studentEmail: string;
  assignmentTitle: string;
  assignmentId: string;
  audioUrl: string;
  submittedAt: string;
  status: 'pending' | 'graded' | 'reviewed';
  score?: number;
  feedback?: string;
  aiAnalysis?: AIAnalysis;
  rubric?: RubricScores;
}

interface AIAnalysis {
  overallScore: number;
  tajweedScore: number;
  fluencyScore: number;
  pronunciationScore: number;
  suggestions: string[];
  detectedErrors: ErrorDetection[];
  confidence: number;
}

interface ErrorDetection {
  timestamp: number;
  type: 'tajweed' | 'pronunciation' | 'fluency';
  description: string;
  severity: 'low' | 'medium' | 'high';
}

interface RubricScores {
  tajweed: number;
  fluency: number;
  memorization: number;
  pronunciation: number;
}

interface SubmissionFilters {
  status: string;
  assignment: string;
  dateRange: string;
  searchQuery: string;
}

const mapStatus = (status: string | undefined): 'pending' | 'graded' | 'reviewed' => {
  switch ((status ?? '').toLowerCase()) {
    case 'graded':
      return 'graded';
    case 'reviewed':
      return 'reviewed';
    default:
      return 'pending';
  }
};

const mapRubric = (rubric: any): RubricScores | undefined => {
  if (!rubric || typeof rubric !== 'object') {
    return undefined;
  }

  return {
    tajweed: Number(rubric.tajweed ?? rubric.tajweedScore ?? 0),
    fluency: Number(rubric.fluency ?? rubric.fluencyScore ?? 0),
    memorization: Number(rubric.memorization ?? rubric.memorizationScore ?? 0),
    pronunciation: Number(rubric.pronunciation ?? rubric.pronunciationScore ?? 0),
  };
};

const mapAnalysis = (analysis: any): AIAnalysis | undefined => {
  if (!analysis || typeof analysis !== 'object') {
    return undefined;
  }

  const detectedErrorsRaw = analysis.detected_errors ?? analysis.detectedErrors ?? [];
  const detectedErrors: ErrorDetection[] = Array.isArray(detectedErrorsRaw)
    ? detectedErrorsRaw.map((error: any) => ({
        timestamp: Number(error.timestamp ?? error.time ?? 0),
        type: (error.type ?? 'tajweed') as 'tajweed' | 'pronunciation' | 'fluency',
        description: error.description ?? '',
        severity: (error.severity ?? 'low') as 'low' | 'medium' | 'high',
      }))
    : [];

  return {
    overallScore: Number(analysis.overall_score ?? analysis.overallScore ?? 0),
    tajweedScore: Number(analysis.tajweed_score ?? analysis.tajweedScore ?? 0),
    fluencyScore: Number(analysis.fluency_score ?? analysis.fluencyScore ?? 0),
    pronunciationScore: Number(analysis.pronunciation_score ?? analysis.pronunciationScore ?? 0),
    suggestions: Array.isArray(analysis.suggestions) ? analysis.suggestions : [],
    detectedErrors,
    confidence: Number(analysis.confidence ?? analysis.confidence_score ?? 0),
  };
};

const transformSubmission = (submission: any): Submission => {
  const student = submission.student ?? {};
  const assignment = submission.assignment ?? {};
  const feedback = Array.isArray(submission.feedback) && submission.feedback.length > 0 ? submission.feedback[0] : undefined;

  return {
    id: String(submission.id),
    studentName: student.name ?? 'Student',
    studentEmail: student.email ?? '',
    assignmentTitle: assignment.title ?? 'Assignment',
    assignmentId: String(submission.assignment_id ?? assignment.id ?? ''),
    audioUrl: submission.audio_url ?? submission.audio_s3_url ?? '',
    submittedAt: submission.created_at ?? submission.submitted_at ?? new Date().toISOString(),
    status: mapStatus(submission.status),
    score: submission.score ?? submission.overall_score ?? undefined,
    feedback: submission.teacher_notes ?? feedback?.feedback_text ?? feedback?.note ?? undefined,
    aiAnalysis: mapAnalysis(submission.ai_analysis ?? feedback?.ai_analysis),
    rubric: mapRubric(submission.rubric_json ?? feedback?.scores),
  };
};

/**
 * Fetch submissions from API with filters
 * @param filters - Filter criteria
 * @returns Promise with submissions data
 */
const fetchSubmissions = async (filters: SubmissionFilters): Promise<Submission[]> => {
  const params = new URLSearchParams();

  if (filters.status && filters.status !== 'all') params.append('status', filters.status);
  if (filters.assignment && filters.assignment !== 'all') params.append('assignment_id', filters.assignment);
  if (filters.dateRange && filters.dateRange !== 'all') params.append('date_range', filters.dateRange);
  if (filters.searchQuery) params.append('search', filters.searchQuery);

  const query = params.toString();
  const response = await api.get(`/submissions${query ? `?${query}` : ''}`);
  const payload = response as any;
  const rawSubmissions = Array.isArray(payload.data)
    ? payload.data
    : Array.isArray(payload?.data?.data)
    ? payload.data.data
    : Array.isArray(payload?.submissions)
    ? payload.submissions
    : [];

  return rawSubmissions.map(transformSubmission);
};

/**
 * Grade submission with feedback
 * @param submissionId - Submission ID
 * @param gradeData - Grade and feedback data
 */
const gradeSubmission = async (submissionId: string, gradeData: {
  score: number;
  feedback: string;
  rubric: RubricScores;
}): Promise<Submission> => {
  const response = await api.post(`/teacher/submissions/${submissionId}/grade`, {
    score: gradeData.score,
    feedback: gradeData.feedback,
    rubric: gradeData.rubric,
  });

  const payload = (response as any)?.submission ?? response;
  return transformSubmission(payload);
};

/**
 * Get status badge styling
 * @param status - Submission status
 * @returns CSS classes for badge
 */
function getStatusBadge(status: string) {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    case 'graded':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    case 'reviewed':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
  }
}

/**
 * Audio player component for submissions
 * @param audioUrl - URL of the audio file
 * @param aiAnalysis - AI analysis data for error highlighting
 */
interface AudioPlayerProps {
  audioUrl: string;
  aiAnalysis?: AIAnalysis;
}

function AudioPlayer({ audioUrl, aiAnalysis }: AudioPlayerProps) {
  const t = useTranslations('teacher.submissions');
  const audioRef = useRef<HTMLAudioElement>(null);
  const [isPlaying, setIsPlaying] = useState(false);
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const [volume, setVolume] = useState(1);
  const [isMuted, setIsMuted] = useState(false);

  useEffect(() => {
    const audio = audioRef.current;
    if (!audio) return;

    const updateTime = () => setCurrentTime(audio.currentTime);
    const updateDuration = () => setDuration(audio.duration);
    const handleEnded = () => setIsPlaying(false);

    audio.addEventListener('timeupdate', updateTime);
    audio.addEventListener('loadedmetadata', updateDuration);
    audio.addEventListener('ended', handleEnded);

    return () => {
      audio.removeEventListener('timeupdate', updateTime);
      audio.removeEventListener('loadedmetadata', updateDuration);
      audio.removeEventListener('ended', handleEnded);
    };
  }, []);

  const togglePlay = () => {
    const audio = audioRef.current;
    if (!audio) return;

    if (isPlaying) {
      audio.pause();
    } else {
      audio.play();
    }
    setIsPlaying(!isPlaying);
  };

  const handleSeek = (value: number[]) => {
    const audio = audioRef.current;
    if (!audio) return;
    
    const newTime = (value[0] / 100) * duration;
    audio.currentTime = newTime;
    setCurrentTime(newTime);
  };

  const handleVolumeChange = (value: number[]) => {
    const audio = audioRef.current;
    if (!audio) return;
    
    const newVolume = value[0] / 100;
    audio.volume = newVolume;
    setVolume(newVolume);
    setIsMuted(newVolume === 0);
  };

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

  const formatTime = (time: number) => {
    const minutes = Math.floor(time / 60);
    const seconds = Math.floor(time % 60);
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
  };

  return (
    <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-4">
      <audio ref={audioRef} src={audioUrl} preload="metadata" />
      
      {/* Controls */}
      <div className="flex items-center space-x-4">
        <Button
          variant="ghost"
          size="sm"
          onClick={togglePlay}
          className="text-emerald-600 hover:text-emerald-700"
        >
          {isPlaying ? <Pause className="h-5 w-5" /> : <Play className="h-5 w-5" />}
        </Button>
        
        <div className="flex-1">
          <Slider
            value={[duration ? (currentTime / duration) * 100 : 0]}
            onValueChange={handleSeek}
            max={100}
            step={0.1}
            className="w-full"
          />
        </div>
        
        <div className="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
          <span>{formatTime(currentTime)}</span>
          <span>/</span>
          <span>{formatTime(duration)}</span>
        </div>
        
        <div className="flex items-center space-x-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={toggleMute}
            className="text-gray-600 hover:text-gray-700"
          >
            {isMuted ? <VolumeX className="h-4 w-4" /> : <Volume2 className="h-4 w-4" />}
          </Button>
          
          <div className="w-20">
            <Slider
              value={[isMuted ? 0 : volume * 100]}
              onValueChange={handleVolumeChange}
              max={100}
              step={1}
            />
          </div>
        </div>
      </div>

      {/* AI Analysis Errors */}
      {aiAnalysis && aiAnalysis.detectedErrors.length > 0 && (
        <div className="space-y-2">
          <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
            <Sparkles className="h-4 w-4 mr-2 text-purple-500" />
            {t('aiErrors', { defaultValue: 'AI Detected Issues' })}
          </h4>
          <div className="space-y-1">
            {aiAnalysis.detectedErrors.map((error, index) => (
              <div 
                key={index}
                className={`text-xs p-2 rounded flex items-center justify-between cursor-pointer hover:bg-opacity-80 ${
                  error.severity === 'high' 
                    ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                    : error.severity === 'medium'
                    ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                    : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                }`}
                onClick={() => {
                  const audio = audioRef.current;
                  if (audio) {
                    audio.currentTime = error.timestamp;
                  }
                }}
              >
                <span>{error.description}</span>
                <span className="text-xs opacity-70">{formatTime(error.timestamp)}</span>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

/**
 * Grading dialog component
 * @param submission - Submission to grade
 * @param isOpen - Dialog open state
 * @param onClose - Close callback
 */
interface GradingDialogProps {
  submission: Submission;
  isOpen: boolean;
  onClose: () => void;
}

function GradingDialog({ submission, isOpen, onClose }: GradingDialogProps) {
  const t = useTranslations('teacher.submissions');
  const queryClient = useQueryClient();
  
  const [score, setScore] = useState(submission.score || 0);
  const [feedback, setFeedback] = useState(submission.feedback || '');
  const [rubric, setRubric] = useState<RubricScores>(submission.rubric || {
    tajweed: 0,
    fluency: 0,
    memorization: 0,
    pronunciation: 0,
  });

  const gradeMutation = useMutation({
    mutationFn: ({ submissionId, gradeData }: { 
      submissionId: string; 
      gradeData: { score: number; feedback: string; rubric: RubricScores; }
    }) => gradeSubmission(submissionId, gradeData),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teacher-submissions'] });
      onClose();
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    gradeMutation.mutate({
      submissionId: submission.id,
      gradeData: { score, feedback, rubric },
    });
  };

  const calculateOverallScore = () => {
    return Math.round((rubric.tajweed + rubric.fluency + rubric.memorization + rubric.pronunciation) / 4);
  };

  useEffect(() => {
    setScore(calculateOverallScore());
  }, [rubric]);

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {t('grading.title', { defaultValue: 'Grade Submission' })}
          </DialogTitle>
          <DialogDescription>
            {t('grading.description', { 
              defaultValue: 'Evaluate the student\'s recitation and provide feedback' 
            })}
          </DialogDescription>
        </DialogHeader>
        
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Audio Player */}
          <div className="space-y-4">
            <h3 className="font-medium text-gray-900 dark:text-white">
              {t('grading.audio', { defaultValue: 'Student Recitation' })}
            </h3>
            <AudioPlayer audioUrl={submission.audioUrl} aiAnalysis={submission.aiAnalysis} />
            
            {/* AI Analysis */}
            {submission.aiAnalysis && (
              <div className="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                <h4 className="font-medium text-purple-900 dark:text-purple-200 mb-3 flex items-center">
                  <Sparkles className="h-4 w-4 mr-2" />
                  {t('grading.aiAnalysis', { defaultValue: 'AI Analysis' })}
                </h4>
                <div className="grid grid-cols-2 gap-4 mb-4">
                  <div className="text-center">
                    <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">
                      {submission.aiAnalysis.overallScore}%
                    </div>
                    <div className="text-xs text-purple-700 dark:text-purple-300">
                      {t('grading.overall', { defaultValue: 'Overall' })}
                    </div>
                  </div>
                  <div className="text-center">
                    <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">
                      {Math.round(submission.aiAnalysis.confidence * 100)}%
                    </div>
                    <div className="text-xs text-purple-700 dark:text-purple-300">
                      {t('grading.confidence', { defaultValue: 'Confidence' })}
                    </div>
                  </div>
                </div>
                
                {submission.aiAnalysis.suggestions.length > 0 && (
                  <div>
                    <h5 className="text-sm font-medium text-purple-800 dark:text-purple-200 mb-2">
                      {t('grading.suggestions', { defaultValue: 'AI Suggestions' })}
                    </h5>
                    <ul className="text-sm text-purple-700 dark:text-purple-300 space-y-1">
                      {submission.aiAnalysis.suggestions.map((suggestion, index) => (
                        <li key={index} className="flex items-start">
                          <span className="mr-2">•</span>
                          <span>{suggestion}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>
            )}
          </div>
          
          {/* Grading Form */}
          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Rubric Scores */}
            <div className="space-y-4">
              <h3 className="font-medium text-gray-900 dark:text-white">
                {t('grading.rubric', { defaultValue: 'Rubric Scores' })}
              </h3>
              
              {Object.entries(rubric).map(([category, value]) => (
                <div key={category} className="space-y-2">
                  <div className="flex items-center justify-between">
                    <label className="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">
                      {t(`grading.${category}`, { defaultValue: category })}
                    </label>
                    <span className="text-sm text-gray-600 dark:text-gray-400">
                      {value}/100
                    </span>
                  </div>
                  <Slider
                    value={[value]}
                    onValueChange={(newValue) => 
                      setRubric(prev => ({ ...prev, [category]: newValue[0] }))
                    }
                    max={100}
                    step={1}
                    className="w-full"
                  />
                </div>
              ))}
              
              {/* Overall Score */}
              <div className="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4">
                <div className="flex items-center justify-between">
                  <span className="font-medium text-emerald-800 dark:text-emerald-200">
                    {t('grading.overallScore', { defaultValue: 'Overall Score' })}
                  </span>
                  <span className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                    {calculateOverallScore()}%
                  </span>
                </div>
              </div>
            </div>
            
            {/* Feedback */}
            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                {t('grading.feedback', { defaultValue: 'Feedback' })}
              </label>
              <Textarea
                value={feedback}
                onChange={(e) => setFeedback(e.target.value)}
                placeholder={t('grading.feedbackPlaceholder', { 
                  defaultValue: 'Provide constructive feedback for the student...' 
                })}
                rows={4}
              />
            </div>
            
            <DialogFooter>
              <Button type="button" variant="outline" onClick={onClose}>
                {t('grading.cancel', { defaultValue: 'Cancel' })}
              </Button>
              <Button 
                type="submit" 
                disabled={gradeMutation.isPending}
                className="bg-emerald-600 hover:bg-emerald-700"
              >
                {gradeMutation.isPending 
                  ? t('grading.submitting', { defaultValue: 'Submitting...' })
                  : t('grading.submit', { defaultValue: 'Submit Grade' })
                }
              </Button>
            </DialogFooter>
          </form>
        </div>
      </DialogContent>
    </Dialog>
  );
}

/**
 * Individual submission card component
 * @param submission - Submission data
 * @param onGrade - Grade callback
 */
interface SubmissionCardProps {
  submission: Submission;
  onGrade: (submission: Submission) => void;
}

function SubmissionCard({ submission, onGrade }: SubmissionCardProps) {
  const t = useTranslations('teacher.submissions');
  
  return (
    <motion.div
      layout
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, scale: 0.95 }}
      whileHover={{ y: -2 }}
      className="group"
    >
      <Card className="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
        <CardHeader className="pb-3">
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <CardTitle className="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                {submission.studentName}
              </CardTitle>
              <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                {submission.assignmentTitle}
              </p>
              <div className="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                <div className="flex items-center space-x-1">
                  <Calendar className="h-3 w-3" />
                  <span>
                    {new Date(submission.submittedAt).toLocaleDateString('en-US', {
                      month: 'short',
                      day: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </span>
                </div>
                <div className="flex items-center space-x-1">
                  <FileAudio className="h-3 w-3" />
                  <span>{t('audioSubmission', { defaultValue: 'Audio' })}</span>
                </div>
              </div>
            </div>
            
            <div className="flex flex-col items-end space-y-2">
              <Badge className={getStatusBadge(submission.status)}>
                {t(`status.${submission.status}`, { defaultValue: submission.status })}
              </Badge>
              {submission.score !== undefined && (
                <div className="flex items-center space-x-1">
                  <Star className="h-4 w-4 text-yellow-500" />
                  <span className="text-sm font-medium text-gray-900 dark:text-white">
                    {submission.score}%
                  </span>
                </div>
              )}
            </div>
          </div>
        </CardHeader>
        
        <CardContent>
          {/* AI Analysis Preview */}
          {submission.aiAnalysis && (
            <div className="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 mb-4">
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm font-medium text-purple-800 dark:text-purple-200 flex items-center">
                  <Sparkles className="h-4 w-4 mr-1" />
                  {t('aiAnalysis', { defaultValue: 'AI Analysis' })}
                </span>
                <span className="text-sm text-purple-600 dark:text-purple-400">
                  {submission.aiAnalysis.overallScore}%
                </span>
              </div>
              <div className="grid grid-cols-3 gap-2 text-xs">
                <div className="text-center">
                  <div className="text-purple-600 dark:text-purple-400 font-medium">
                    {submission.aiAnalysis.tajweedScore}%
                  </div>
                  <div className="text-purple-700 dark:text-purple-300">
                    {t('tajweed', { defaultValue: 'Tajweed' })}
                  </div>
                </div>
                <div className="text-center">
                  <div className="text-purple-600 dark:text-purple-400 font-medium">
                    {submission.aiAnalysis.fluencyScore}%
                  </div>
                  <div className="text-purple-700 dark:text-purple-300">
                    {t('fluency', { defaultValue: 'Fluency' })}
                  </div>
                </div>
                <div className="text-center">
                  <div className="text-purple-600 dark:text-purple-400 font-medium">
                    {submission.aiAnalysis.pronunciationScore}%
                  </div>
                  <div className="text-purple-700 dark:text-purple-300">
                    {t('pronunciation', { defaultValue: 'Pronunciation' })}
                  </div>
                </div>
              </div>
            </div>
          )}
          
          {/* Feedback Preview */}
          {submission.feedback && (
            <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 mb-4">
              <div className="flex items-center mb-2">
                <MessageSquare className="h-4 w-4 text-gray-600 dark:text-gray-400 mr-2" />
                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                  {t('feedback', { defaultValue: 'Feedback' })}
                </span>
              </div>
              <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                {submission.feedback}
              </p>
            </div>
          )}
          
          {/* Actions */}
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-2">
              <Button variant="ghost" size="sm" className="text-blue-600 hover:text-blue-700">
                <Eye className="h-4 w-4 mr-2" />
                {t('view', { defaultValue: 'View' })}
              </Button>
              <Button variant="ghost" size="sm" className="text-gray-600 hover:text-gray-700">
                <Download className="h-4 w-4 mr-2" />
                {t('download', { defaultValue: 'Download' })}
              </Button>
            </div>
            
            <div className="flex items-center space-x-2">
              {submission.status === 'pending' && (
                <Button 
                  size="sm" 
                  onClick={() => onGrade(submission)}
                  className="bg-emerald-600 hover:bg-emerald-700"
                >
                  <CheckCircle className="h-4 w-4 mr-2" />
                  {t('grade', { defaultValue: 'Grade' })}
                </Button>
              )}
              {submission.status === 'graded' && (
                <Button 
                  size="sm" 
                  variant="outline"
                  onClick={() => onGrade(submission)}
                >
                  <RotateCcw className="h-4 w-4 mr-2" />
                  {t('regrade', { defaultValue: 'Re-grade' })}
                </Button>
              )}
            </div>
          </div>
        </CardContent>
      </Card>
    </motion.div>
  );
}

/**
 * Loading skeleton for submissions
 */
function SubmissionsLoading() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {Array.from({ length: 6 }).map((_, index) => (
        <Card key={index} className="bg-white/70 dark:bg-gray-800/70">
          <CardHeader>
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <Skeleton className="h-6 w-3/4 mb-2" />
                <Skeleton className="h-4 w-full mb-2" />
                <Skeleton className="h-3 w-1/2" />
              </div>
              <Skeleton className="h-6 w-16" />
            </div>
          </CardHeader>
          <CardContent>
            <Skeleton className="h-20 w-full mb-4" />
            <div className="flex items-center justify-between">
              <div className="flex space-x-2">
                <Skeleton className="h-8 w-16" />
                <Skeleton className="h-8 w-20" />
              </div>
              <Skeleton className="h-8 w-16" />
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

/**
 * Empty state component
 */
function EmptySubmissions() {
  const t = useTranslations('teacher.submissions');
  
  return (
    <div className="text-center py-12">
      <div className="text-gray-400 dark:text-gray-600 mb-4">
        <FileAudio className="h-16 w-16 mx-auto" />
      </div>
      <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
        {t('empty.title', { defaultValue: 'No Submissions Yet' })}
      </h3>
      <p className="text-gray-600 dark:text-gray-400 max-w-md mx-auto">
        {t('empty.description', { 
          defaultValue: 'Student submissions will appear here once they start submitting their assignments.' 
        })}
      </p>
    </div>
  );
}

/**
 * Main Submission Section Component
 * Displays and manages student submissions with grading functionality
 */
export default function SubmissionSection() {
  const t = useTranslations('teacher.submissions');
  
  const [filters, setFilters] = useState<SubmissionFilters>({
    status: 'all',
    assignment: 'all',
    dateRange: 'all',
    searchQuery: '',
  });
  const [gradingSubmission, setGradingSubmission] = useState<Submission | null>(null);
  const [isGradingOpen, setIsGradingOpen] = useState(false);

  const { data: submissions = [], isLoading, error } = useQuery({
    queryKey: ['teacher-submissions', filters],
    queryFn: () => fetchSubmissions(filters),
    refetchInterval: 30 * 1000, // Refetch every 30 seconds
  });

  const handleGradeSubmission = (submission: Submission) => {
    setGradingSubmission(submission);
    setIsGradingOpen(true);
  };

  const handleCloseGrading = () => {
    setIsGradingOpen(false);
    setGradingSubmission(null);
  };

  const updateFilter = (key: keyof SubmissionFilters, value: string) => {
    setFilters(prev => ({ ...prev, [key]: value }));
  };

  if (isLoading) {
    return <SubmissionsLoading />;
  }

  if (error) {
    return (
      <Card className="bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
        <CardContent className="p-6 text-center">
          <div className="text-red-600 dark:text-red-400 mb-2">
            <FileAudio className="h-8 w-8 mx-auto" />
          </div>
          <h3 className="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
            {t('error.title', { defaultValue: 'Failed to Load Submissions' })}
          </h3>
          <p className="text-red-600 dark:text-red-400 text-sm">
            {(error as Error).message}
          </p>
        </CardContent>
      </Card>
    );
  }

  if (submissions.length === 0) {
    return <EmptySubmissions />;
  }

  return (
    <div className="space-y-6">
      {/* Header & Filters */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
              {t('title', { defaultValue: 'Student Submissions' })}
            </h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              {t('subtitle', { defaultValue: `${submissions.length} submissions to review` })}
            </p>
          </div>
          
          <Button variant="outline" size="sm">
            <Filter className="h-4 w-4 mr-2" />
            {t('filters', { defaultValue: 'Filters' })}
          </Button>
        </div>
        
        {/* Filter Controls */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input
              type="text"
              placeholder={t('searchPlaceholder', { defaultValue: 'Search students...' })}
              value={filters.searchQuery}
              onChange={(e) => updateFilter('searchQuery', e.target.value)}
              className="pl-10 pr-4 py-2 w-full border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            />
          </div>
          
          <Select value={filters.status} onValueChange={(value) => updateFilter('status', value)}>
            <SelectTrigger>
              <SelectValue placeholder={t('filterStatus', { defaultValue: 'All Status' })} />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">{t('status.all', { defaultValue: 'All Status' })}</SelectItem>
              <SelectItem value="pending">{t('status.pending', { defaultValue: 'Pending' })}</SelectItem>
              <SelectItem value="graded">{t('status.graded', { defaultValue: 'Graded' })}</SelectItem>
              <SelectItem value="reviewed">{t('status.reviewed', { defaultValue: 'Reviewed' })}</SelectItem>
            </SelectContent>
          </Select>
          
          <Select value={filters.assignment} onValueChange={(value) => updateFilter('assignment', value)}>
            <SelectTrigger>
              <SelectValue placeholder={t('filterAssignment', { defaultValue: 'All Assignments' })} />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">{t('assignments.all', { defaultValue: 'All Assignments' })}</SelectItem>
              {/* Add dynamic assignment options here */}
            </SelectContent>
          </Select>
          
          <Select value={filters.dateRange} onValueChange={(value) => updateFilter('dateRange', value)}>
            <SelectTrigger>
              <SelectValue placeholder={t('filterDate', { defaultValue: 'All Time' })} />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">{t('dateRange.all', { defaultValue: 'All Time' })}</SelectItem>
              <SelectItem value="today">{t('dateRange.today', { defaultValue: 'Today' })}</SelectItem>
              <SelectItem value="week">{t('dateRange.week', { defaultValue: 'This Week' })}</SelectItem>
              <SelectItem value="month">{t('dateRange.month', { defaultValue: 'This Month' })}</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      {/* Submissions Grid */}
      <motion.div 
        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ staggerChildren: 0.1 }}
      >
        <AnimatePresence>
          {submissions.map((submission) => (
            <SubmissionCard
              key={submission.id}
              submission={submission}
              onGrade={handleGradeSubmission}
            />
          ))}
        </AnimatePresence>
      </motion.div>

      {/* Grading Dialog */}
      {gradingSubmission && (
        <GradingDialog
          submission={gradingSubmission}
          isOpen={isGradingOpen}
          onClose={handleCloseGrading}
        />
      )}
    </div>
  );
}