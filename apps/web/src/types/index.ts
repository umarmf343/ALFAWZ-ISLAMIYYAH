/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

/**
 * Core application type definitions for AlFawz Qur'an Institute
 */

export interface Class {
  id: number;
  name: string;
  description?: string;
  teacher_id: number;
  teacher?: {
    id: number;
    name: string;
    email: string;
  };
  level?: number;
  students_count?: number;
  assignments_count?: number;
  created_at: string;
  updated_at: string;
}

export interface Assignment {
  id: number;
  title: string;
  description?: string;
  class_id: number;
  class?: Class;
  class_title?: string;
  teacher_id: number;
  surah_id: number;
  ayah_start: number;
  ayah_end: number;
  due_date?: string;
  is_published: boolean;
  status: 'draft' | 'published';
  submission_status?: string;
  hotspots_count?: number;
  submissions_count?: number;
  created_at: string;
  updated_at: string;
}

export interface Hotspot {
  id: number;
  assignment_id: number;
  assignment?: Assignment;
  ayah_number: number;
  word_position: number;
  audio_url?: string;
  feedback_text?: string;
  created_at: string;
  updated_at: string;
}

export interface Submission {
  id: number;
  assignment_id: number;
  assignment?: Assignment;
  student_id: number;
  student?: {
    id: number;
    name: string;
    email: string;
  };
  audio_url?: string;
  status: 'pending' | 'graded' | 'reviewed';
  score?: number;
  hasanat?: number;
  feedback_count?: number;
  created_at: string;
  updated_at: string;
}

export interface Feedback {
  id: number;
  submission_id: number;
  submission?: Submission;
  teacher_id: number;
  teacher?: {
    id: number;
    name: string;
  };
  ayah_number: number;
  word_position: number;
  feedback_text: string;
  audio_url?: string;
  created_at: string;
  updated_at: string;
}

export interface LeaderboardEntry {
  id: number;
  user_id: number;
  user_name: string;
  name: string;
  email: string;
  total_hasanat: number;
  total_submissions: number;
  average_score: number;
  rank: number;
  badge?: string;
}

export interface Surah {
  id: number;
  name_simple: string;
  name_arabic: string;
  name_complex: string;
  revelation_place: string;
  revelation_order: number;
  bismillah_pre: boolean;
  verses_count: number;
}

export interface Ayah {
  id: number;
  verse_number: number;
  verse_key: string;
  text_uthmani: string;
  text_simple: string;
  juz_number: number;
  hizb_number: number;
  rub_number: number;
  sajdah_number?: number;
  page_number: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}