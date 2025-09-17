/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

/**
 * Assignment-related TypeScript type definitions.
 * Defines interfaces for assignments, hotspots, submissions, and related entities.
 */

export interface Assignment {
  id: number;
  class_id?: number;
  teacher_id: number;
  title: string;
  description?: string;
  image_s3_url?: string;
  due_at?: string;
  status: 'draft' | 'published';
  targets?: number[]; // Array of user IDs if not class-wide
  created_at: string;
  updated_at: string;
  
  // Relationships
  teacher?: User;
  class?: ClassInfo;
  hotspots?: Hotspot[];
  submissions?: Submission[];
  
  // Computed properties
  is_overdue?: boolean;
  submission_count?: number;
  completion_rate?: number;
}

export interface Hotspot {
  id: number;
  assignment_id: number;
  title?: string;
  tooltip?: string;
  audio_s3_url?: string;
  x: number;
  y: number;
  width: number;
  height: number;
  hotspot_type?: 'audio' | 'text' | 'interactive' | 'quiz';
  animation_type?: 'pulse' | 'bounce' | 'glow' | 'shake';
  is_required?: boolean;
  auto_play?: boolean;
  group_id?: string;
  metadata?: Record<string, any>;
  created_at: string;
  updated_at: string;
  
  // Relationships
  assignment?: Assignment;
  interactions?: HotspotInteraction[];
  
  // Computed properties
  interaction_count?: number;
  completion_rate?: number;
}

export interface HotspotInteraction {
  id: number;
  hotspot_id: number;
  user_id: number;
  interaction_type: 'click' | 'hover' | 'audio_play' | 'audio_complete' | 'quiz_attempt';
  duration?: number; // in seconds
  completion_percentage?: number; // 0-100
  metadata?: Record<string, any>;
  created_at: string;
  updated_at: string;
  
  // Relationships
  hotspot?: Hotspot;
  user?: User;
}

export interface Submission {
  id: number;
  assignment_id: number;
  student_id: number;
  status: 'pending' | 'submitted' | 'graded' | 'returned';
  score?: number; // 0-100
  rubric_json?: {
    tajweed?: number;
    fluency?: number;
    memory?: number;
    pronunciation?: number;
    overall_notes?: string;
  };
  audio_s3_url?: string;
  submitted_at?: string;
  graded_at?: string;
  created_at: string;
  updated_at: string;
  
  // Relationships
  assignment?: Assignment;
  student?: User;
  feedback?: Feedback[];
  
  // Computed properties
  is_late?: boolean;
  days_since_submission?: number;
  feedback_count?: number;
}

export interface Feedback {
  id: number;
  submission_id: number;
  teacher_id: number;
  note?: string;
  audio_s3_url?: string;
  feedback_type?: 'general' | 'tajweed' | 'pronunciation' | 'fluency' | 'encouragement';
  is_public?: boolean;
  created_at: string;
  updated_at: string;
  
  // Relationships
  submission?: Submission;
  teacher?: User;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: 'student' | 'teacher' | 'admin';
  avatar?: string;
  phone?: string;
  date_of_birth?: string;
  gender?: 'male' | 'female';
  level?: number; // 1, 2, 3 for students
  bio?: string;
  preferences?: {
    notifications?: {
      email?: boolean;
      push?: boolean;
      assignment_reminders?: boolean;
      feedback_alerts?: boolean;
    };
    ui?: {
      theme?: 'light' | 'dark';
      language?: 'en' | 'ar';
      font_size?: 'small' | 'medium' | 'large';
    };
  };
  created_at: string;
  updated_at: string;
  
  // Computed properties
  full_name?: string;
  initials?: string;
  is_online?: boolean;
  last_seen_at?: string;
}

export interface ClassInfo {
  id: number;
  teacher_id: number;
  title: string;
  description?: string;
  level: number; // 1, 2, 3
  color?: string;
  is_active?: boolean;
  max_students?: number;
  created_at: string;
  updated_at: string;
  
  // Relationships
  teacher?: User;
  members?: ClassMember[];
  assignments?: Assignment[];
  
  // Computed properties
  student_count?: number;
  assignment_count?: number;
  completion_rate?: number;
}

export interface ClassMember {
  id: number;
  class_id: number;
  user_id: number;
  role_in_class: 'student' | 'assistant';
  joined_at: string;
  is_active?: boolean;
  
  // Relationships
  class?: ClassInfo;
  user?: User;
}

export interface Notification {
  id: number;
  user_id: number;
  type: 'assignment_created' | 'assignment_due_soon' | 'assignment_overdue' | 'feedback_received' | 'grade_posted';
  title: string;
  message: string;
  data?: Record<string, any>;
  read_at?: string;
  created_at: string;
  updated_at: string;
  
  // Relationships
  user?: User;
  
  // Computed properties
  is_read?: boolean;
  time_ago?: string;
}

// API Response Types
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
  links: {
    first: string;
    last: string;
    prev?: string;
    next?: string;
  };
}

// Filter and Search Types
export interface AssignmentFilters {
  status?: 'all' | 'pending' | 'submitted' | 'graded' | 'overdue';
  class_id?: number;
  teacher_id?: number;
  search?: string;
  due_date_from?: string;
  due_date_to?: string;
  sort_by?: 'created_at' | 'due_at' | 'title' | 'status';
  sort_order?: 'asc' | 'desc';
  page?: number;
  limit?: number;
}

export interface SubmissionFilters {
  assignment_id?: number;
  student_id?: number;
  status?: 'pending' | 'submitted' | 'graded' | 'returned';
  score_min?: number;
  score_max?: number;
  submitted_from?: string;
  submitted_to?: string;
  search?: string;
  sort_by?: 'submitted_at' | 'score' | 'student_name';
  sort_order?: 'asc' | 'desc';
  page?: number;
  limit?: number;
}

// Form Data Types
export interface CreateAssignmentData {
  title: string;
  description?: string;
  class_id?: number;
  targets?: number[];
  due_at?: string;
  image?: File;
  hotspots?: Omit<Hotspot, 'id' | 'assignment_id' | 'created_at' | 'updated_at'>[];
}

export interface UpdateAssignmentData extends Partial<CreateAssignmentData> {
  status?: 'draft' | 'published';
}

export interface CreateHotspotData {
  title?: string;
  tooltip?: string;
  x: number;
  y: number;
  width: number;
  height: number;
  hotspot_type?: 'audio' | 'text' | 'interactive' | 'quiz';
  animation_type?: 'pulse' | 'bounce' | 'glow' | 'shake';
  is_required?: boolean;
  auto_play?: boolean;
  group_id?: string;
  audio?: File;
  metadata?: Record<string, any>;
}

export interface CreateSubmissionData {
  assignment_id: number;
  audio?: File;
  notes?: string;
}

export interface CreateFeedbackData {
  submission_id: number;
  note?: string;
  audio?: File;
  feedback_type?: 'general' | 'tajweed' | 'pronunciation' | 'fluency' | 'encouragement';
  is_public?: boolean;
  rubric_scores?: {
    tajweed?: number;
    fluency?: number;
    memory?: number;
    pronunciation?: number;
  };
  overall_score?: number;
}

// Statistics and Analytics Types
export interface AssignmentStats {
  total_assignments: number;
  pending_assignments: number;
  completed_assignments: number;
  overdue_assignments: number;
  average_score: number;
  completion_rate: number;
  submission_rate: number;
}

export interface StudentProgress {
  student_id: number;
  student_name: string;
  total_assignments: number;
  completed_assignments: number;
  average_score: number;
  latest_submission?: string;
  performance_trend: 'improving' | 'declining' | 'stable';
}

export interface HotspotStats {
  hotspot_id: number;
  total_interactions: number;
  unique_users: number;
  average_duration: number;
  completion_rate: number;
  interaction_types: Record<string, number>;
}