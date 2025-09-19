'use client';

import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useTranslations } from 'next-intl';
import { motion, AnimatePresence } from 'framer-motion';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
  Users,
  Plus,
  Edit,
  Trash2,
  Calendar,
  BookOpen,
} from 'lucide-react';
import { api, type ApiResponse } from '@/lib/api';

interface Student {
  id: string;
  name: string;
  email: string;
  joinedAt?: string;
  progress?: number;
}

interface ClassData {
  id: string;
  title: string;
  description: string;
  level: 1 | 2 | 3;
  studentCount: number;
  createdAt: string;
  students: Student[];
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

const LEVEL_OPTIONS: Record<1 | 2 | 3, string> = {
  1: 'teacher.classes.levels.beginner',
  2: 'teacher.classes.levels.intermediate',
  3: 'teacher.classes.levels.advanced',
};

const normalizeLevel = (value: number): 1 | 2 | 3 => {
  if (value <= 1) return 1;
  if (value >= 3) return 3;
  return 2;
};

const isRecord = (value: unknown): value is Record<string, unknown> =>
  typeof value === 'object' && value !== null;

const extractArray = (value: unknown): Record<string, unknown>[] => {
  if (Array.isArray(value)) {
    return value.filter(isRecord);
  }

  if (isRecord(value) && Array.isArray(value.data)) {
    return value.data.filter(isRecord);
  }

  return [];
};

const extractClassPayload = (response: ApiResponse<unknown>): unknown => {
  const data = response.data;
  if (isRecord(data) && 'class' in data) {
    const nested = (data as { class?: unknown }).class;
    return nested ?? data;
  }

  return data;
};

const getNestedRecord = (
  record: Record<string, unknown>,
  key: string,
): Record<string, unknown> | undefined => {
  const value = record[key];
  return isRecord(value) ? value : undefined;
};

const getStringValue = (record: Record<string, unknown>, key: string, fallback = ''): string => {
  const value = record[key];
  if (typeof value === 'string') {
    return value;
  }

  if (typeof value === 'number') {
    return value.toString();
  }

  return fallback;
};

const getOptionalNumber = (record: Record<string, unknown>, key: string): number | undefined => {
  const value = record[key];
  if (typeof value === 'number') {
    return value;
  }

  if (typeof value === 'string') {
    const parsed = Number(value);
    if (!Number.isNaN(parsed)) {
      return parsed;
    }
  }

  return undefined;
};

const transformClass = (classData: unknown): ClassData => {
  const record = isRecord(classData) ? classData : {};
  const memberRecords = extractArray(record.members ?? record.students ?? []);

  const students: Student[] = memberRecords
    .map((memberRecord) => {
      const pivotRecord = getNestedRecord(memberRecord, 'pivot');
      const roleSource = pivotRecord ?? memberRecord;
      const role = getStringValue(roleSource, 'role_in_class', 'student');

      if (role !== 'student') {
        return undefined;
      }

      const joinedAt =
        (pivotRecord && getStringValue(pivotRecord, 'created_at', '')) ||
        getStringValue(memberRecord, 'joined_at', '');
      const progress =
        (pivotRecord && getOptionalNumber(pivotRecord, 'progress')) ??
        getOptionalNumber(memberRecord, 'progress');

      return {
        id: getStringValue(memberRecord, 'id'),
        name: getStringValue(memberRecord, 'name', 'Student'),
        email: getStringValue(memberRecord, 'email'),
        joinedAt: joinedAt || undefined,
        progress,
      };
    })
    .filter((student): student is Student => Boolean(student));

  const levelValue = getOptionalNumber(record, 'level') ?? 1;

  return {
    id: getStringValue(record, 'id'),
    title: getStringValue(record, 'title', getStringValue(record, 'name', 'Untitled Class')),
    description: getStringValue(record, 'description'),
    level: normalizeLevel(levelValue),
    studentCount: students.length,
    createdAt: getStringValue(record, 'created_at', new Date().toISOString()),
    students,
  };
};

const fetchClasses = async (): Promise<ClassData[]> => {
  const response = await api.get<unknown>('/classes');
  const rawClasses = extractArray(response.data);

  return rawClasses.map(transformClass);
};

const fetchAvailableStudents = async (): Promise<AvailableStudent[]> => {
  const response = await api.get<unknown>('/profile/my-students');
  const responseData = response.data;
  const studentSource =
    isRecord(responseData) && responseData.students !== undefined
      ? responseData.students
      : responseData;
  const rawStudents = extractArray(studentSource);

  return rawStudents.map((studentRecord) => ({
    id: getStringValue(studentRecord, 'id'),
    name: getStringValue(studentRecord, 'name', 'Student'),
    email: getStringValue(studentRecord, 'email'),
  }));
};

const createClass = async (data: CreateClassData): Promise<ClassData> => {
  const { studentIds, ...classPayload } = data;
  const response = await api.post<unknown>('/classes', classPayload);
  const createdPayload = isRecord(response.data) && isRecord(response.data.class)
    ? response.data.class
    : response.data;
  const newClass = transformClass(createdPayload);

  if (studentIds.length > 0) {
    await Promise.all(
      studentIds.map((studentId) =>
        api.post(`/classes/${newClass.id}/members`, {
          user_id: Number(studentId),
          role_in_class: 'student',
        }),
      ),
    );
  }

  const refreshed = await api.get<unknown>(`/classes/${newClass.id}`);
  const refreshedClass = isRecord(refreshed.data) && isRecord(refreshed.data.class)
    ? refreshed.data.class
    : refreshed.data;
  return transformClass(refreshedClass ?? newClass);
};

const updateClass = async (classId: string, data: CreateClassData): Promise<ClassData> => {
  const { studentIds, ...classPayload } = data;
  const payloadKeys = Object.keys(classPayload).filter((key) => key !== 'studentIds');

  if (payloadKeys.length > 0) {
    await api.put(`/classes/${classId}`, classPayload);
  }

  const detailsResponse = await api.get<unknown>(`/classes/${classId}`);
  const details = extractClassPayload(detailsResponse);
  const currentClass = transformClass(details);
  const existingIds = currentClass.students.map((student) => student.id);

  const toAdd = studentIds.filter((id) => !existingIds.includes(id));
  const toRemove = existingIds.filter((id) => !studentIds.includes(id));

  if (toAdd.length > 0) {
    await Promise.all(
      toAdd.map((studentId) =>
        api.post(`/classes/${classId}/members`, {
          user_id: Number(studentId),
          role_in_class: 'student',
        }),
      ),
    );
  }

  for (const studentId of toRemove) {
    await api.delete(`/classes/${classId}/members/${studentId}`);
  }

  const refreshed = await api.get<unknown>(`/classes/${classId}`);
  const refreshedClass = extractClassPayload(refreshed);
  return transformClass(refreshedClass ?? details);
};

const deleteClass = async (classId: string) => {
  await api.delete(`/classes/${classId}`);
};

const getLevelBadgeColor = (level: 1 | 2 | 3) => {
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
};

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
    queryKey: ['teacher-available-students'],
    queryFn: fetchAvailableStudents,
    enabled: isOpen,
    staleTime: 5 * 60 * 1000,
  });

  useEffect(() => {
    if (editingClass) {
      setFormData({
        title: editingClass.title,
        description: editingClass.description,
        level: editingClass.level,
        studentIds: editingClass.students.map((student) => student.id),
      });
    } else {
      setFormData({ title: '', description: '', level: 1, studentIds: [] });
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
    mutationFn: ({ classId, data }: { classId: string; data: CreateClassData }) => updateClass(classId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teacher-classes'] });
      onClose();
    },
  });

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    if (editingClass) {
      updateMutation.mutate({ classId: editingClass.id, data: formData });
    } else {
      createMutation.mutate(formData);
    }
  };

  const toggleStudent = (studentId: string) => {
    setFormData((prev) => ({
      ...prev,
      studentIds: prev.studentIds.includes(studentId)
        ? prev.studentIds.filter((id) => id !== studentId)
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
              : t('form.createTitle', { defaultValue: 'Create New Class' })}
          </DialogTitle>
          <DialogDescription>
            {editingClass
              ? t('form.editDescription', { defaultValue: 'Update class details and manage students.' })
              : t('form.createDescription', { defaultValue: 'Create a new class and assign students.' })}
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="space-y-4">
            <div>
              <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                {t('form.title', { defaultValue: 'Class Title' })}
              </label>
              <Input
                value={formData.title}
                onChange={(event) => setFormData((prev) => ({ ...prev, title: event.target.value }))}
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
                onChange={(event) => setFormData((prev) => ({ ...prev, description: event.target.value }))}
                placeholder={t('form.descriptionPlaceholder', { defaultValue: 'Enter class description' })}
                rows={3}
              />
            </div>
            <div>
              <label className="text-sm font-medium text-gray-700 dark:text-gray-300" htmlFor="class-level">
                {t('form.level', { defaultValue: 'Level' })}
              </label>
              <select
                id="class-level"
                value={formData.level.toString()}
                onChange={(event) =>
                  setFormData((prev) => ({ ...prev, level: parseInt(event.target.value, 10) as 1 | 2 | 3 }))
                }
                className="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
              >
                {([1, 2, 3] as const).map((level) => (
                  <option key={level} value={level.toString()}>
                    {t(LEVEL_OPTIONS[level], { defaultValue: `Level ${level}` })}
                  </option>
                ))}
              </select>
            </div>
          </div>
          <div>
            <label className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 block">
              {t('form.students', { defaultValue: 'Select Students' })} ({formData.studentIds.length})
            </label>
            <ScrollArea className="h-48 border rounded-lg p-3">
              <div className="space-y-2">
                {availableStudents.map((student) => {
                  const selected = formData.studentIds.includes(student.id);
                  return (
                    <div
                      key={student.id}
                      className={`flex items-center justify-between p-2 rounded-lg cursor-pointer transition-colors ${
                        selected
                          ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800'
                          : 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700'
                      }`}
                      onClick={() => toggleStudent(student.id)}
                    >
                      <div>
                        <p className="text-sm font-medium text-gray-900 dark:text-white">{student.name}</p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">{student.email}</p>
                      </div>
                      {selected && (
                        <Badge className="bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                          {t('form.selected', { defaultValue: 'Selected' })}
                        </Badge>
                      )}
                    </div>
                  );
                })}
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
              className="bg-emerald-600 hover:bg-emerald-700"
            >
              {editingClass
                ? t('form.update', { defaultValue: 'Update Class' })
                : t('form.create', { defaultValue: 'Create Class' })}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

interface ClassCardProps {
  classData: ClassData;
  onEdit: (classData: ClassData) => void;
  onDelete: (classId: string) => void;
}

function ClassCard({ classData, onEdit, onDelete }: ClassCardProps) {
  const t = useTranslations('teacher.classes');
  const formattedDate = useMemo(() => {
    try {
      return new Date(classData.createdAt).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
      });
    } catch (error) {
      return classData.createdAt;
    }
  }, [classData.createdAt]);

  const handleDelete = useCallback(() => {
    const confirmed = window.confirm(
      t('deleteDialog.description', {
        defaultValue: 'Are you sure you want to delete this class? This action cannot be undone.',
      }),
    );

    if (confirmed) {
      onDelete(classData.id);
    }
  }, [classData.id, onDelete, t]);

  return (
    <motion.div
      layout
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, scale: 0.95 }}
      whileHover={{ y: -2 }}
      className="group"
    >
      <Card className="bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300 h-full">
        <CardHeader className="pb-3">
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <CardTitle className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                {classData.title}
              </CardTitle>
              <p className="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{classData.description}</p>
            </div>
            <Badge className={getLevelBadgeColor(classData.level)}>
              {t(LEVEL_OPTIONS[classData.level], { defaultValue: `Level ${classData.level}` })}
            </Badge>
          </div>
        </CardHeader>
        <CardContent className="flex flex-col justify-between h-full">
          <div className="flex items-center justify-between mb-4 text-sm text-gray-600 dark:text-gray-400">
            <div className="flex items-center space-x-1">
              <Users className="h-4 w-4" />
              <span>
                {classData.studentCount} {t('students', { defaultValue: 'students' })}
              </span>
            </div>
            <div className="flex items-center space-x-1">
              <Calendar className="h-4 w-4" />
              <span>{formattedDate}</span>
            </div>
          </div>
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
              <Button
                variant="ghost"
                size="sm"
                onClick={handleDelete}
                className="text-gray-600 hover:text-red-600"
              >
                <Trash2 className="h-4 w-4" />
                <span className="sr-only">{t('deleteDialog.title', { defaultValue: 'Delete Class' })}</span>
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </motion.div>
  );
}

function ClassesLoading() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {Array.from({ length: 3 }).map((_, index) => (
        <Card key={index} className="bg-white/70 dark:bg-gray-800/70 border-gray-200 dark:border-gray-700">
          <CardContent className="p-6 space-y-4">
            <Skeleton className="h-6 w-3/4" />
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-4 w-2/3" />
            <div className="flex items-center space-x-4">
              <Skeleton className="h-4 w-1/4" />
              <Skeleton className="h-4 w-1/4" />
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}

function EmptyClasses({ onCreateClass }: { onCreateClass: () => void }) {
  const t = useTranslations('teacher.classes');
  return (
    <Card className="bg-white/70 dark:bg-gray-800/70 border-gray-200 dark:border-gray-700">
      <CardContent className="p-10 text-center space-y-4">
        <div className="mx-auto h-12 w-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
          <Users className="h-6 w-6 text-emerald-600" />
        </div>
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
          {t('empty.title', { defaultValue: 'No classes yet' })}
        </h3>
        <p className="text-sm text-gray-600 dark:text-gray-400">
          {t('empty.subtitle', { defaultValue: 'Create your first class to start organizing your students.' })}
        </p>
        <Button onClick={onCreateClass} className="bg-emerald-600 hover:bg-emerald-700">
          <Plus className="h-4 w-4 mr-2" />
          {t('create', { defaultValue: 'Create Class' })}
        </Button>
      </CardContent>
    </Card>
  );
}

export default function ClassSection() {
  const t = useTranslations('teacher.classes');
  const queryClient = useQueryClient();
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingClass, setEditingClass] = useState<ClassData | null>(null);

  const {
    data: classes = [],
    isLoading,
    error,
  } = useQuery({
    queryKey: ['teacher-classes'],
    queryFn: fetchClasses,
    refetchInterval: 2 * 60 * 1000,
  });

  const deleteMutation = useMutation({
    mutationFn: deleteClass,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['teacher-classes'] }),
  });

  const openCreateForm = () => {
    setEditingClass(null);
    setIsFormOpen(true);
  };

  const openEditForm = (classData: ClassData) => {
    setEditingClass(classData);
    setIsFormOpen(true);
  };

  const closeForm = () => {
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
          <p className="text-red-600 dark:text-red-400 text-sm">{(error as Error).message}</p>
        </CardContent>
      </Card>
    );
  }

  if (classes.length === 0) {
    return (
      <>
        <EmptyClasses onCreateClass={openCreateForm} />
        <ClassForm isOpen={isFormOpen} onClose={closeForm} editingClass={editingClass} />
      </>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('title', { defaultValue: 'My Classes' })}
          </h3>
          <p className="text-sm text-gray-600 dark:text-gray-400">
            {t('subtitle', { defaultValue: `Managing ${classes.length} classes`, count: classes.length })}
          </p>
        </div>
        <Button onClick={openCreateForm} className="bg-emerald-600 hover:bg-emerald-700">
          <Plus className="h-4 w-4 mr-2" />
          {t('create', { defaultValue: 'Create Class' })}
        </Button>
      </div>

      <motion.div
        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ staggerChildren: 0.1 }}
      >
        <AnimatePresence>
          {classes.map((classData) => (
            <ClassCard key={classData.id} classData={classData} onEdit={openEditForm} onDelete={(id) => deleteMutation.mutate(id)} />
          ))}
        </AnimatePresence>
      </motion.div>

      <ClassForm isOpen={isFormOpen} onClose={closeForm} editingClass={editingClass} />
    </div>
  );
}
