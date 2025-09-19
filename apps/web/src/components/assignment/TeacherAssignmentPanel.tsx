/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useRef, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Plus, Edit3, Trash2, Clock, Send, BookOpen, Target } from 'lucide-react';
import { useSpiritualTheme, SpiritualCard, SpiritualButton } from '@/components/providers/SpiritualThemeProvider';

/**
 * Assignment interface for teacher panel.
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
  targets?: string[]; // user IDs if not class-wide
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

/**
 * Class interface for assignment targeting.
 */
interface Class {
  id: string;
  title: string;
  description?: string;
  level: number;
  teacherId: string;
}

/**
 * Props for TeacherAssignmentPanel component.
 */
interface TeacherAssignmentPanelProps {
  assignments?: Assignment[];
  classes?: Class[];
  onAssignmentCreate?: (assignment: Omit<Assignment, 'id'>) => void;
  onAssignmentUpdate?: (id: string, assignment: Partial<Assignment>) => void;
  onAssignmentDelete?: (id: string) => void;
  className?: string;
}

/**
 * Teacher assignment management panel with spiritual theme integration.
 * Provides assignment creation, editing, hotspot management, and submission review.
 */
export const TeacherAssignmentPanel: React.FC<TeacherAssignmentPanelProps> = ({
  assignments = [],
  classes = [],
  onAssignmentCreate,
  onAssignmentUpdate,
  onAssignmentDelete,
  className = ''
}) => {
  const { theme, animations } = useSpiritualTheme();
  const [selectedAssignment, setSelectedAssignment] = useState<Assignment | null>(null);
  const [isCreating, setIsCreating] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [imageScale] = useState(1);
  const [imageOffset] = useState({ x: 0, y: 0 });
  
  const imageRef = useRef<HTMLImageElement>(null);
  const containerRef = useRef<HTMLDivElement>(null);

  // Form state for assignment creation/editing
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    classId: '',
    dueAt: '',
    imageUrl: '',
    targets: [] as string[]
  });

  /**
   * Handle assignment form submission.
   */
  const handleSubmit = useCallback((e: React.FormEvent) => {
    e.preventDefault();
    
    const assignmentData = {
      ...formData,
      teacherId: 'current-teacher-id', // Would come from auth context
      status: 'draft' as const,
      hotspots: selectedAssignment?.hotspots || []
    };
    
    if (isCreating) {
      onAssignmentCreate?.(assignmentData);
    } else if (selectedAssignment) {
      onAssignmentUpdate?.(selectedAssignment.id, assignmentData);
    }
    
    // Reset form
    setFormData({
      title: '',
      description: '',
      classId: '',
      dueAt: '',
      imageUrl: '',
      targets: []
    });
    setSelectedAssignment(null);
    setIsCreating(false);
    setIsEditing(false);
  }, [formData, isCreating, selectedAssignment, onAssignmentCreate, onAssignmentUpdate]);

  /**
   * Handle hotspot creation on image click.
   */
  const handleImageClick = useCallback((e: React.MouseEvent<HTMLImageElement>) => {
    if (!isEditing || !imageRef.current) return;
    
    const rect = imageRef.current.getBoundingClientRect();
    const x = Math.round((e.clientX - rect.left) / imageScale - imageOffset.x);
    const y = Math.round((e.clientY - rect.top) / imageScale - imageOffset.y);
    
    const newHotspot: Hotspot = {
      id: `hotspot-${Date.now()}`,
      title: 'New Hotspot',
      tooltip: 'Click to edit this hotspot',
      x,
      y,
      width: 50,
      height: 50
    };
    
    if (selectedAssignment) {
      const updatedAssignment = {
        ...selectedAssignment,
        hotspots: [...(selectedAssignment.hotspots || []), newHotspot]
      };
      setSelectedAssignment(updatedAssignment);
    }
  }, [isEditing, imageScale, imageOffset, selectedAssignment]);

  /**
   * Handle hotspot deletion.
   */
  const handleHotspotDelete = useCallback((hotspotId: string) => {
    if (!selectedAssignment) return;
    
    const updatedHotspots = selectedAssignment.hotspots?.filter(h => h.id !== hotspotId) || [];
    setSelectedAssignment({
      ...selectedAssignment,
      hotspots: updatedHotspots
    });
  }, [selectedAssignment]);

  /**
   * Handle assignment publishing.
   */
  const handlePublish = useCallback((assignment: Assignment) => {
    onAssignmentUpdate?.(assignment.id, { status: 'published' });
  }, [onAssignmentUpdate]);

  /**
   * Initialize form data when editing an assignment.
   */
  React.useEffect(() => {
    if (isEditing && selectedAssignment) {
      setFormData({
        title: selectedAssignment.title,
        description: selectedAssignment.description,
        classId: selectedAssignment.classId || '',
        dueAt: selectedAssignment.dueAt || '',
        imageUrl: selectedAssignment.imageUrl || '',
        targets: selectedAssignment.targets || []
      });
    }
  }, [isEditing, selectedAssignment]);

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
              Assignment Management
            </h1>
            <p style={{ color: theme.colors.maroon[600] }}>
              Create and manage Qur&apos;an recitation assignments for your classes
            </p>
          </motion.div>
          <motion.div {...animations.slideInRight}>
            <SpiritualButton
              variant="primary"
              onClick={() => setIsCreating(true)}
              className="flex items-center space-x-2"
            >
              <Plus className="w-5 h-5" />
              <span>New Assignment</span>
            </SpiritualButton>
          </motion.div>
        </div>
      </SpiritualCard>

      {/* Assignment Grid */}
      <motion.div 
        className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        {...animations.staggerChildren}
      >
        {assignments.map((assignment, index) => {
          const classInfo = classes.find(c => c.id === assignment.classId);
          
          return (
            <motion.div
              key={assignment.id}
              {...animations.fadeInUp}
              transition={{ delay: index * 0.1 }}
            >
              <SpiritualCard
                className="border transition-all duration-200"
                style={{ borderColor: theme.colors.gold[200] }}
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
                        {classInfo && (
                          <p 
                            className="text-xs mt-1"
                            style={{ color: theme.colors.maroon[500] }}
                          >
                            Class: {classInfo.title}
                          </p>
                        )}
                      </div>
                    </div>
                    
                    {/* Status Badge */}
                    <div 
                      className="px-2 py-1 rounded-full text-xs font-medium"
                      style={{
                        backgroundColor: assignment.status === 'published'
                          ? `${theme.colors.accent.emerald}20`
                          : `${theme.colors.gold[400]}20`,
                        color: assignment.status === 'published'
                          ? theme.colors.accent.emerald
                          : theme.colors.gold[700]
                      }}
                    >
                      {assignment.status === 'published' ? 'Published' : 'Draft'}
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

                  {/* Assignment Stats */}
                  <div className="flex items-center justify-between text-sm mb-4">
                    <div className="flex items-center space-x-4">
                      <div className="flex items-center space-x-1" style={{ color: theme.colors.maroon[600] }}>
                        <Target className="w-4 h-4" />
                        <span>{assignment.hotspots?.length || 0} hotspots</span>
                      </div>
                      {assignment.dueAt && (
                        <div className="flex items-center space-x-1" style={{ color: theme.colors.maroon[600] }}>
                          <Clock className="w-4 h-4" />
                          <span>Due: {new Date(assignment.dueAt).toLocaleDateString()}</span>
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Action Buttons */}
                  <div 
                    className="flex items-center justify-between pt-4 border-t"
                    style={{ borderColor: theme.colors.gold[200] }}
                  >
                    <div className="flex items-center space-x-2">
                      <button
                        onClick={() => {
                          setSelectedAssignment(assignment);
                          setIsEditing(true);
                        }}
                        className="p-2 rounded-lg transition-colors"
                        style={{
                          color: theme.colors.maroon[600],
                          ':hover': {
                            color: theme.colors.maroon[700],
                            backgroundColor: `${theme.colors.maroon[600]}10`
                          }
                        }}
                      >
                        <Edit3 className="w-4 h-4" />
                      </button>
                      <button
                        onClick={() => onAssignmentDelete?.(assignment.id)}
                        className="p-2 rounded-lg transition-colors"
                        style={{
                          color: theme.colors.accent.crimson,
                          ':hover': {
                            color: theme.colors.accent.crimson,
                            backgroundColor: `${theme.colors.accent.crimson}10`
                          }
                        }}
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                      {assignment.status === 'draft' && (
                        <button
                          onClick={() => handlePublish(assignment)}
                          className="p-2 rounded-lg transition-colors"
                          style={{
                            color: theme.colors.accent.emerald,
                            ':hover': {
                              color: theme.colors.accent.emerald,
                              backgroundColor: `${theme.colors.accent.emerald}10`
                            }
                          }}
                        >
                          <Send className="w-4 h-4" />
                        </button>
                      )}
                    </div>
                    
                    <button
                      onClick={() => setSelectedAssignment(assignment)}
                      className="text-sm font-medium transition-colors"
                      style={{
                        color: theme.colors.maroon[600],
                        ':hover': { color: theme.colors.maroon[700] }
                      }}
                    >
                      View Details
                    </button>
                  </div>
                </div>
              </SpiritualCard>
            </motion.div>
          );
        })}
      </motion.div>

      {/* Assignment Details/Edit Modal */}
      <AnimatePresence>
        {(selectedAssignment || isCreating) && (
          <motion.div
            className="fixed inset-0 flex items-center justify-center p-4 z-50"
            style={{ backgroundColor: 'rgba(0, 0, 0, 0.5)' }}
            {...animations.modal.overlay}
            onClick={() => {
              setSelectedAssignment(null);
              setIsCreating(false);
              setIsEditing(false);
            }}
          >
            <motion.div
              className="rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-y-auto"
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
                  <motion.h2 
                    className="text-xl font-bold"
                    style={{ color: theme.colors.maroon[900] }}
                    {...animations.fadeInUp}
                  >
                    {isCreating ? 'Create New Assignment' : 
                     isEditing ? 'Edit Assignment' : 'Assignment Details'}
                  </motion.h2>
                  <button
                    onClick={() => {
                      setSelectedAssignment(null);
                      setIsCreating(false);
                      setIsEditing(false);
                    }}
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

              {/* Modal Content */}
              <div className="p-6">
                {(isCreating || isEditing) ? (
                  /* Assignment Form */
                  <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      {/* Title */}
                      <div>
                        <label 
                          className="block text-sm font-medium mb-2"
                          style={{ color: theme.colors.maroon[700] }}
                        >
                          Assignment Title
                        </label>
                        <input
                          type="text"
                          value={formData.title}
                          onChange={(e) => setFormData(prev => ({ ...prev, title: e.target.value }))}
                          className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2"
                          style={{
                            borderColor: theme.colors.gold[300],
                            focusRingColor: theme.colors.gold[400]
                          }}
                          required
                        />
                      </div>

                      {/* Class Selection */}
                      <div>
                        <label 
                          className="block text-sm font-medium mb-2"
                          style={{ color: theme.colors.maroon[700] }}
                        >
                          Target Class
                        </label>
                        <select
                          value={formData.classId}
                          onChange={(e) => setFormData(prev => ({ ...prev, classId: e.target.value }))}
                          className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2"
                          style={{
                            borderColor: theme.colors.gold[300],
                            focusRingColor: theme.colors.gold[400]
                          }}
                        >
                          <option value="">Select a class...</option>
                          {classes.map(cls => (
                            <option key={cls.id} value={cls.id}>
                              {cls.title} (Level {cls.level})
                            </option>
                          ))}
                        </select>
                      </div>

                      {/* Due Date */}
                      <div>
                        <label 
                          className="block text-sm font-medium mb-2"
                          style={{ color: theme.colors.maroon[700] }}
                        >
                          Due Date
                        </label>
                        <input
                          type="datetime-local"
                          value={formData.dueAt}
                          onChange={(e) => setFormData(prev => ({ ...prev, dueAt: e.target.value }))}
                          className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2"
                          style={{
                            borderColor: theme.colors.gold[300],
                            focusRingColor: theme.colors.gold[400]
                          }}
                        />
                      </div>

                      {/* Image URL */}
                      <div>
                        <label 
                          className="block text-sm font-medium mb-2"
                          style={{ color: theme.colors.maroon[700] }}
                        >
                          Assignment Image URL
                        </label>
                        <input
                          type="url"
                          value={formData.imageUrl}
                          onChange={(e) => setFormData(prev => ({ ...prev, imageUrl: e.target.value }))}
                          className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2"
                          style={{
                            borderColor: theme.colors.gold[300],
                            focusRingColor: theme.colors.gold[400]
                          }}
                          placeholder="https://example.com/image.jpg"
                        />
                      </div>
                    </div>

                    {/* Description */}
                    <div>
                      <label 
                        className="block text-sm font-medium mb-2"
                        style={{ color: theme.colors.maroon[700] }}
                      >
                        Description
                      </label>
                      <textarea
                        value={formData.description}
                        onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
                        rows={3}
                        className="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2"
                        style={{
                          borderColor: theme.colors.gold[300],
                          focusRingColor: theme.colors.gold[400]
                        }}
                        placeholder="Describe the assignment objectives and requirements..."
                      />
                    </div>

                    {/* Image Preview and Hotspot Editor */}
                    {formData.imageUrl && (
                      <div>
                        <label 
                          className="block text-sm font-medium mb-2"
                          style={{ color: theme.colors.maroon[700] }}
                        >
                          Assignment Image & Hotspots
                        </label>
                        <div
                          ref={containerRef}
                          className="relative border-2 border-dashed rounded-lg overflow-hidden"
                          style={{ borderColor: theme.colors.gold[300] }}
                        >
                          {/* eslint-disable-next-line @next/next/no-img-element */}
                          <img
                            ref={imageRef}
                            src={formData.imageUrl}
                            alt="Assignment"
                            className="w-full cursor-crosshair"
                            onClick={handleImageClick}
                            style={{
                              transform: `scale(${imageScale}) translate(${imageOffset.x}px, ${imageOffset.y}px)`
                            }}
                          />
                          
                          {/* Hotspots Overlay */}
                          {selectedAssignment?.hotspots?.map(hotspot => (
                            <div
                              key={hotspot.id}
                              className="absolute border-2 bg-opacity-20 cursor-move"
                              style={{
                                left: hotspot.x * imageScale + imageOffset.x,
                                top: hotspot.y * imageScale + imageOffset.y,
                                width: hotspot.width * imageScale,
                                height: hotspot.height * imageScale,
                                borderColor: theme.colors.gold[400],
                                backgroundColor: theme.colors.gold[400]
                              }}
                              title={hotspot.title || 'Hotspot'}
                            >
                              <button
                                onClick={() => handleHotspotDelete(hotspot.id)}
                                className="absolute -top-2 -right-2 w-6 h-6 rounded-full text-xs font-bold"
                                style={{
                                  backgroundColor: theme.colors.accent.crimson,
                                  color: 'white'
                                }}
                              >
                                ×
                              </button>
                            </div>
                          ))}
                        </div>
                        <p 
                          className="text-xs mt-2"
                          style={{ color: theme.colors.maroon[500] }}
                        >
                          Click on the image to add hotspots. Click the × button to remove them.
                        </p>
                      </div>
                    )}

                    {/* Form Actions */}
                    <div className="flex items-center justify-end space-x-4">
                      <SpiritualButton
                        type="button"
                        variant="ghost"
                        onClick={() => {
                          setSelectedAssignment(null);
                          setIsCreating(false);
                          setIsEditing(false);
                        }}
                        className="px-6 py-2 font-medium"
                      >
                        Cancel
                      </SpiritualButton>
                      
                      <SpiritualButton
                        type="submit"
                        variant="primary"
                        className="px-6 py-2 font-medium"
                      >
                        {isCreating ? 'Create Assignment' : 'Update Assignment'}
                      </SpiritualButton>
                    </div>
                  </form>
                ) : (
                  /* Assignment Details View */
                  <div className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div>
                        <h3 
                          className="text-lg font-semibold mb-2"
                          style={{ color: theme.colors.maroon[800] }}
                        >
                          Assignment Details
                        </h3>
                        <div className="space-y-2">
                          <p style={{ color: theme.colors.maroon[600] }}>
                            <strong>Status:</strong> {selectedAssignment?.status}
                          </p>
                          <p style={{ color: theme.colors.maroon[600] }}>
                            <strong>Class:</strong> {classes.find(c => c.id === selectedAssignment?.classId)?.title || 'Individual'}
                          </p>
                          {selectedAssignment?.dueAt && (
                            <p style={{ color: theme.colors.maroon[600] }}>
                              <strong>Due:</strong> {new Date(selectedAssignment.dueAt).toLocaleString()}
                            </p>
                          )}
                          <p style={{ color: theme.colors.maroon[600] }}>
                            <strong>Hotspots:</strong> {selectedAssignment?.hotspots?.length || 0}
                          </p>
                        </div>
                      </div>
                      
                      <div>
                        <h3 
                          className="text-lg font-semibold mb-2"
                          style={{ color: theme.colors.maroon[800] }}
                        >
                          Actions
                        </h3>
                        <div className="space-y-2">
                          <SpiritualButton
                            variant="primary"
                            onClick={() => setIsEditing(true)}
                            className="w-full flex items-center justify-center space-x-2"
                          >
                            <Edit3 className="w-4 h-4" />
                            <span>Edit Assignment</span>
                          </SpiritualButton>
                          
                          {selectedAssignment?.status === 'draft' && (
                            <SpiritualButton
                              variant="accent"
                              onClick={() => selectedAssignment && handlePublish(selectedAssignment)}
                              className="w-full flex items-center justify-center space-x-2"
                            >
                              <Send className="w-4 h-4" />
                              <span>Publish Assignment</span>
                            </SpiritualButton>
                          )}
                        </div>
                      </div>
                    </div>

                    {/* Assignment Image */}
                    {selectedAssignment?.imageUrl && (
                      <div>
                        <h3
                          className="text-lg font-semibold mb-2"
                          style={{ color: theme.colors.maroon[800] }}
                        >
                          Assignment Image
                        </h3>
                        <div className="relative">
                          {/* eslint-disable-next-line @next/next/no-img-element */}
                          <img
                            src={selectedAssignment.imageUrl}
                            alt={selectedAssignment.title}
                            className="w-full rounded-lg shadow-lg"
                          />
                          
                          {/* Hotspots Display */}
                          {selectedAssignment.hotspots?.map(hotspot => (
                            <div
                              key={hotspot.id}
                              className="absolute border-2 bg-opacity-20"
                              style={{
                                left: hotspot.x,
                                top: hotspot.y,
                                width: hotspot.width,
                                height: hotspot.height,
                                borderColor: theme.colors.gold[400],
                                backgroundColor: theme.colors.gold[400]
                              }}
                              title={hotspot.title || 'Hotspot'}
                            />
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </motion.div>
  );
};

export default TeacherAssignmentPanel;