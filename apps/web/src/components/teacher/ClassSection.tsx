/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useTranslations } from 'next-intl';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { ScrollArea } from '@/components/ui/scroll-area';
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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { 
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { 
  Users, 
  Plus, 
  Edit, 
  Trash2, 
  BookOpen,
  Calendar,
  UserPlus,
  UserMinus,
  GraduationCap,
  Clock,
  Target
} from 'lucide-react';

// Types for class data
interface ClassData {
  id: string;
  title: string;
  description: string;
  level: 1 | 2 | 3;
  studentCount: number;
  createdAt: string;
  students: Student[];
}

interface Student {
  id: string;
  name: string;
  email: string;
  joinedAt: string;
  progress: number;
}

interface CreateClassData {
  title: string;
  description: string;
  level: 1 | 2 | 3;
  studentIds: string[];
}

interface AvailableStudent {
  id: string;
  name: string;
  email: string;
}

/**
 * Fetch teacher's classes from API
 * @returns Promise with classes data
 */
const fetchClasses = async (): Promise<ClassData[]> => {
  const response = await fetch('/api/teacher/classes', {
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json',
    },
  });
  
  if (!response.ok) {
    throw new Error('Failed to fetch classes');
  }
  
  const data = await response.json();
  return data.classes;
};

/**
 * Fetch available students for class assignment
 * @returns Promise with available students
 */
const fetchAvailableStudents = async (): Promise<AvailableStudent[]> => {
  const response = await fetch('/api/teacher/students/available', {
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json',
    },
  });
  
  if (!response.ok) {
    throw new Error('Failed to fetch available students');
  }
  
  const data = await response.json();
  return data.students;
};

/**
 * Create new class
 * @param classData - Class creation data
 */
const createClass = async (classData: CreateClassData): Promise<ClassData> => {
  const response = await fetch('/api/teacher/classes', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(classData),
  });
  
  if (!response.ok) {
    throw new Error('Failed to create class');
  }
  
  return response.json();
};

/**
 * Update existing class
 * @param classId - Class ID
 * @param classData - Updated class data
 */
const updateClass = async (classId: string, classData: Partial<CreateClassData>): Promise<ClassData> => {
  const response = await fetch(`/api/teacher/classes/${classId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(classData),
  });
  
  if (!response.ok) {
    throw new Error('Failed to update class');
  }
  
  return response.json();
};

/**
 * Delete class
 * @param classId - Class ID to delete
 */
const deleteClass = async (classId: string): Promise<void> => {
  const response = await fetch(`/api/teacher/classes/${classId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json',
    },
  });
  
  if (!response.ok) {
    throw new Error('Failed to delete class');
  }
};

/**
 * Get level badge color
 * @param level - Class level (1, 2, or 3)
 * @returns CSS classes for badge styling
 */
function getLevelBadgeColor(level: 1 | 2 | 3) {
  switch (level) {
    case 1:
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    case 2:
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    case 3:
      return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
  }
}

/**
 * Class creation/editing form component
 * @param isOpen - Dialog open state
 * @param onClose - Close dialog callback
 * @param editingClass - Class being edited (null for creation)
 */
interface ClassFormProps {
  isOpen: boolean;
  onClose: () => void;
  editingClass?: ClassData | null;
}

function ClassForm({ isOpen, onClose, editingClass }: ClassFormProps) {
  const t = useTranslations('teacher.classes');
  const queryClient = useQueryClient();
  
  const [formData, setFormData] = useState<CreateClassData>({
    title: '',
    description: '',
    level: 1,
    studentIds: [],
  });

  const { data: availableStudents = [] } = useQuery({
    queryKey: ['available-students'],
    queryFn: fetchAvailableStudents,
    enabled: isOpen,
  });

  // Populate form when editing
  useEffect(() => {
    if (editingClass) {
      setFormData({
        title: editingClass.title,
        description: editingClass.description,
        level: editingClass.level,
        studentIds: editingClass.students.map(s => s.id),
      });
    } else {
      setFormData({
        title: '',
        description: '',
        level: 1,
        studentIds: [],
      });
    }
  }, [editingClass, isOpen]);

  const createMutation = useMutation({
    mutationFn: createClass,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teacher-classes'] });
      onClose();
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ classId, data }: { classId: string; data: Partial<CreateClassData> }) => 
      updateClass(classId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teacher-classes'] });
      onClose();
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (editingClass) {
      updateMutation.mutate({ classId: editingClass.id, data: formData });
    } else {
      createMutation.mutate(formData);
    }
  };

  const handleStudentToggle = (studentId: string) => {
    setFormData(prev => ({
      ...prev,
      studentIds: prev.studentIds.includes(studentId)
        ? prev.studentIds.filter(id => id !== studentId)
        : [...prev.studentIds, studentId],
    }));
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {editingClass 
              ? t('form.editTitle', { defaultValue: 'Edit Class' })
              : t('form.createTitle', { defaultValue: 'Create New Class' })
            }
          </DialogTitle>
          <DialogDescription>
            {editingClass 
              ? t('form.editDescription', { defaultValue: 'Update class details and manage students' })
              : t('form.createDescription', { defaultValue: 'Create a new class and assign students' })
            }
          </DialogDescription>
        </DialogHeader>
        
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Basic Info */}
          <div className="space-y-4">
            <div>
              <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                {t('form.title', { defaultValue: 'Class Title' })}
              </label>
              <Input
                value={formData.title}
                onChange={(e) => setFormData(prev => ({ ...prev, title: e.target.value }))}
                placeholder={t('form.titlePlaceholder', { defaultValue: 'Enter class title' })}
                required
              />
            </div>
            
            <div>
              <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                {t('form.description', { defaultValue: 'Description' })}
              </label>
              <Textarea
                value={formData.description}
                onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
                placeholder={t('form.descriptionPlaceholder', { defaultValue: 'Enter class description' })}
                rows={3}
              />
            </div>
            
            <div>
              <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                {t('form.level', { defaultValue: 'Level' })}
              </label>
              <Select 
                value={formData.level.toString()} 
                onValueChange={(value) => setFormData(prev => ({ ...prev, level: parseInt(value) as 1 | 2 | 3 }))}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="1">{t('levels.beginner', { defaultValue: 'Level 1 - Beginner' })}</SelectItem>
                  <SelectItem value="2">{t('levels.intermediate', { defaultValue: 'Level 2 - Intermediate' })}</SelectItem>
                  <SelectItem value="3">{t('levels.advanced', { defaultValue: 'Level 3 - Advanced' })}</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          {/* Student Selection */}
          <div>
            <label className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 block">
              {t('form.students', { defaultValue: 'Select Students' })} ({formData.studentIds.length})
            </label>
            <ScrollArea className="h-48 border rounded-lg p-3">
              <div className="space-y-2">
                {availableStudents.map((student) => (
                  <div 
                    key={student.id}
                    className={`flex items-center justify-between p-2 rounded-lg cursor-pointer transition-colors ${
                      formData.studentIds.includes(student.id)
                        ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800'
                        : 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700'
                    }`}
                    onClick={() => handleStudentToggle(student.id)}
                  >
                    <div>
                      <p className="text-sm font-medium text-gray-900 dark:text-white">
                        {student.name}
                      </p>
                      <p className="text-xs text-gray-500 dark:text-gray-400">
                        {student.email}
                      </p>
                    </div>
                    {formData.studentIds.includes(student.id) && (
                      <Badge className="bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                        Selected
                      </Badge>
                    )}
                  </div>
                ))}
              </div>
            </ScrollArea>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={onClose}>
              {t('form.cancel', { defaultValue: 'Cancel' })}
            </Button>
            <Button 
              type="submit" 
              disabled={createMutation.isPending || updateMutation.isPending}
            >
              {editingClass 
                ? t('form.update', { defaultValue: 'Update Class' })
                : t('form.create', { defaultValue: 'Create Class' })
              }
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

/**
 * Individual class card component
 * @param classData - Class data
 * @param onEdit - Edit callback
 * @param onDelete - Delete callback
 */
interface ClassCardProps {
  classData: ClassData;
  onEdit: (classData: ClassData) => void;
  onDelete: (classId: string) => void;
}

function ClassCard({ classData, onEdit, onDelete }: ClassCardProps) {
  const t = useTranslations('teacher.classes');
  
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
              <CardTitle className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                {classData.title}
              </CardTitle>
              <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                {classData.description}
              </p>
            </div>
            
            <div className="flex items-center space-x-2 ml-4">
              <Badge className={getLevelBadgeColor(classData.level)}>
                {t(`levels.${classData.level}`, { defaultValue: `Level ${classData.level}` })}
              </Badge>
            </div>
          </div>
        </CardHeader>
        
        <CardContent>
          {/* Stats */}
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
              <div className="flex items-center space-x-1">
                <Users className="h-4 w-4" />
                <span>{classData.studentCount} {t('students', { defaultValue: 'students' })}</span>
              </div>
              <div className="flex items-center space-x-1">
                <Calendar className="h-4 w-4" />
                <span>
                  {new Date(classData.createdAt).toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric' 
                  })}
                </span>
              </div>
            </div>
          </div>

          {/* Actions */}
          <div className="flex items-center justify-between">
            <Button variant="ghost" size="sm" className="text-blue-600 hover:text-blue-700">
              <BookOpen className="h-4 w-4 mr-2" />
              {t('viewDetails', { defaultValue: 'View Details' })}
            </Button>
            
            <div className="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <Button 
                variant="ghost" 
                size="sm"
                onClick={() => onEdit(classData)}
                className="text-gray-600 hover:text-blue-600"
              >
                <Edit className="h-4 w-4" />
              </Button>
              
              <AlertDialog>
                <AlertDialogTrigger asChild>
                  <Button 
                    variant="ghost" 
                    size="sm"
                    className="text-gray-600 hover:text-red-600"
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                  <AlertDialogHeader>
                    <AlertDialogTitle>
                      {t('deleteDialog.title', { defaultValue: 'Delete Class' })}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                      {t('deleteDialog.description', { 
                        defaultValue: 'Are you sure you want to delete this class? This action cannot be undone.' 
                      })}
                    </AlertDialogDescription>
                  </AlertDialogHeader>
                  <AlertDialogFooter>
                    <AlertDialogCancel>
                      {t('deleteDialog.cancel', { defaultValue: 'Cancel' })}
                    </AlertDialogCancel>
                    <AlertDialogAction 
                      onClick={() => onDelete(classData.id)}
                      className="bg-red-600 hover:bg-red-700"
                    >
                      {t('deleteDialog.confirm', { defaultValue: 'Delete' })}
                    </AlertDialogAction>
                  </AlertDialogFooter>
                </AlertDialogContent>
              </AlertDialog>
            </div>
          </div>
        </CardContent>
      </Card>
    </motion.div>
  );
}

/**
 * Loading skeleton for classes
 */
function ClassesLoading() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {Array.from({ length: 6 }).map((_, index) => (
        <Card key={index} className="bg-white/70 dark:bg-gray-800/70">
          <CardHeader>
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <Skeleton className="h-6 w-3/4 mb-2" />
                <Skeleton className="h-4 w-full" />
              </div>
              <Skeleton className="h-6 w-16 ml-4" />
            </div>
          </CardHeader>
          <CardContent>
            <div className="flex items-center justify-between mb-4">
              <Skeleton className="h-4 w-24" />
              <Skeleton className="h-4 w-20" />
            </div>
            <div className="flex items-center justify-between">
              <Skeleton className="h-8 w-24" />
              <div className="flex space-x-1">
                <Skeleton className="h-8 w-8" />
                <Skeleton className="h-8 w-8" />
              </div>
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
function EmptyClasses({ onCreateClass }: { onCreateClass: () => void }) {
  const t = useTranslations('teacher.classes');
  
  return (
    <div className="text-center py-12">
      <div className="text-gray-400 dark:text-gray-600 mb-4">
        <GraduationCap className="h-16 w-16 mx-auto" />
      </div>
      <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
        {t('empty.title', { defaultValue: 'No Classes Yet' })}
      </h3>
      <p className="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
        {t('empty.description', { 
          defaultValue: 'Create your first class to start managing students and assignments.' 
        })}
      </p>
      <Button onClick={onCreateClass} className="bg-emerald-600 hover:bg-emerald-700">
        <Plus className="h-4 w-4 mr-2" />
        {t('createFirst', { defaultValue: 'Create Your First Class' })}
      </Button>
    </div>
  );
}

/**
 * Main Class Section Component
 * Displays and manages teacher's classes with CRUD operations
 */
export default function ClassSection() {
  const t = useTranslations('teacher.classes');
  const queryClient = useQueryClient();
  
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingClass, setEditingClass] = useState<ClassData | null>(null);

  const { data: classes = [], isLoading, error } = useQuery({
    queryKey: ['teacher-classes'],
    queryFn: fetchClasses,
    refetchInterval: 2 * 60 * 1000, // Refetch every 2 minutes
  });

  const deleteMutation = useMutation({
    mutationFn: deleteClass,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teacher-classes'] });
    },
  });

  const handleCreateClass = () => {
    setEditingClass(null);
    setIsFormOpen(true);
  };

  const handleEditClass = (classData: ClassData) => {
    setEditingClass(classData);
    setIsFormOpen(true);
  };

  const handleDeleteClass = (classId: string) => {
    deleteMutation.mutate(classId);
  };

  const handleCloseForm = () => {
    setIsFormOpen(false);
    setEditingClass(null);
  };

  if (isLoading) {
    return <ClassesLoading />;
  }

  if (error) {
    return (
      <Card className="bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
        <CardContent className="p-6 text-center">
          <div className="text-red-600 dark:text-red-400 mb-2">
            <Users className="h-8 w-8 mx-auto" />
          </div>
          <h3 className="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
            {t('error.title', { defaultValue: 'Failed to Load Classes' })}
          </h3>
          <p className="text-red-600 dark:text-red-400 text-sm">
            {(error as Error).message}
          </p>
        </CardContent>
      </Card>
    );
  }

  if (classes.length === 0) {
    return (
      <>
        <EmptyClasses onCreateClass={handleCreateClass} />
        <ClassForm 
          isOpen={isFormOpen} 
          onClose={handleCloseForm} 
          editingClass={editingClass}
        />
      </>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('title', { defaultValue: 'My Classes' })}
          </h3>
          <p className="text-sm text-gray-600 dark:text-gray-400">
            {t('subtitle', { defaultValue: `Managing ${classes.length} classes` })}
          </p>
        </div>
        
        <Button onClick={handleCreateClass} className="bg-emerald-600 hover:bg-emerald-700">
          <Plus className="h-4 w-4 mr-2" />
          {t('create', { defaultValue: 'Create Class' })}
        </Button>
      </div>

      {/* Classes Grid */}
      <motion.div 
        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ staggerChildren: 0.1 }}
      >
        <AnimatePresence>
          {classes.map((classData) => (
            <ClassCard
              key={classData.id}
              classData={classData}
              onEdit={handleEditClass}
              onDelete={handleDeleteClass}
            />
          ))}
        </AnimatePresence>
      </motion.div>

      {/* Form Dialog */}
      <ClassForm 
        isOpen={isFormOpen} 
        onClose={handleCloseForm} 
        editingClass={editingClass}
      />
    </div>
  );
}