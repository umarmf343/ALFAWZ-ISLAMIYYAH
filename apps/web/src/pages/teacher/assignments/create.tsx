/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useRef, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useRouter } from 'next/router';
import {
  ArrowLeft,
  Upload,
  Plus,
  Trash2,
  Save,
  Eye,
  Calendar,
  Users,
  Image as ImageIcon,
  Volume2,
  Info,
  Settings,
  Play,
  Square
} from 'lucide-react';
import Layout from '../../../components/Layout';
import { useAuth } from '../../../hooks/useAuth';
import { CreateAssignmentData, CreateHotspotData, ClassInfo } from '../../../types/assignment';

interface HotspotDraft extends Omit<CreateHotspotData, 'audio'> {
  id: string;
  audioFile?: File;
  audioUrl?: string;
}

/**
 * Teacher assignment creation page with hotspot editor.
 * Allows teachers to create assignments with interactive hotspots on images.
 */
const CreateAssignmentPage: React.FC = () => {
  const router = useRouter();
  const { user } = useAuth();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  // Form state
  const [formData, setFormData] = useState<CreateAssignmentData>({
    title: '',
    description: '',
    class_id: undefined,
    due_at: '',
    targets: []
  });
  
  // Image and hotspot state
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [imageUrl, setImageUrl] = useState<string | null>(null);
  const [hotspots, setHotspots] = useState<HotspotDraft[]>([]);
  const [selectedHotspot, setSelectedHotspot] = useState<string | null>(null);
  const [isCreatingHotspot, setIsCreatingHotspot] = useState(false);
  const [classes, setClasses] = useState<ClassInfo[]>([]);
  
  // Refs
  const imageRef = useRef<HTMLImageElement>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const audioInputRef = useRef<HTMLInputElement>(null);

  /**
   * Handle form input changes.
   * @param field Form field name
   * @param value New value
   */
  const handleInputChange = (field: keyof CreateAssignmentData, value: any) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));
  };

  /**
   * Handle image upload and preview.
   * @param event File input change event
   */
  const handleImageUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) {
      if (!file.type.startsWith('image/')) {
        setError('Please select a valid image file');
        return;
      }
      
      setImageFile(file);
      const url = URL.createObjectURL(file);
      setImageUrl(url);
      setError(null);
    }
  };

  /**
   * Handle image click to create new hotspot.
   * @param event Mouse click event on image
   */
  const handleImageClick = useCallback((event: React.MouseEvent<HTMLImageElement>) => {
    if (!isCreatingHotspot || !imageRef.current) return;
    
    const rect = imageRef.current.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    
    const newHotspot: HotspotDraft = {
      id: `hotspot-${Date.now()}`,
      title: `Hotspot ${hotspots.length + 1}`,
      tooltip: '',
      x: Math.round(x),
      y: Math.round(y),
      width: 40,
      height: 40,
      hotspot_type: 'text',
      animation_type: 'pulse',
      is_required: false,
      auto_play: false
    };
    
    setHotspots(prev => [...prev, newHotspot]);
    setSelectedHotspot(newHotspot.id);
    setIsCreatingHotspot(false);
  }, [isCreatingHotspot, hotspots.length]);

  /**
   * Update hotspot properties.
   * @param hotspotId Hotspot ID to update
   * @param updates Partial hotspot data to update
   */
  const updateHotspot = (hotspotId: string, updates: Partial<HotspotDraft>) => {
    setHotspots(prev => prev.map(h => 
      h.id === hotspotId ? { ...h, ...updates } : h
    ));
  };

  /**
   * Delete hotspot.
   * @param hotspotId Hotspot ID to delete
   */
  const deleteHotspot = (hotspotId: string) => {
    setHotspots(prev => prev.filter(h => h.id !== hotspotId));
    if (selectedHotspot === hotspotId) {
      setSelectedHotspot(null);
    }
  };

  /**
   * Handle audio upload for hotspot.
   * @param hotspotId Hotspot ID
   * @param file Audio file
   */
  const handleHotspotAudioUpload = (hotspotId: string, file: File) => {
    const audioUrl = URL.createObjectURL(file);
    updateHotspot(hotspotId, {
      audioFile: file,
      audioUrl: audioUrl,
      hotspot_type: 'audio'
    });
  };

  /**
   * Submit assignment creation.
   */
  const handleSubmit = async () => {
    try {
      setLoading(true);
      setError(null);
      
      if (!formData.title.trim()) {
        throw new Error('Assignment title is required');
      }
      
      if (!imageFile) {
        throw new Error('Assignment image is required');
      }
      
      const token = localStorage.getItem('token');
      if (!token) {
        throw new Error('Authentication token not found');
      }
      
      // Create FormData for file upload
      const submitData = new FormData();
      submitData.append('title', formData.title);
      if (formData.description) submitData.append('description', formData.description);
      if (formData.class_id) submitData.append('class_id', formData.class_id.toString());
      if (formData.due_at) submitData.append('due_at', formData.due_at);
      if (formData.targets?.length) {
        submitData.append('targets', JSON.stringify(formData.targets));
      }
      
      submitData.append('image', imageFile);
      
      // Add hotspots data
      const hotspotsData = hotspots.map(h => ({
        title: h.title,
        tooltip: h.tooltip,
        x: h.x,
        y: h.y,
        width: h.width,
        height: h.height,
        hotspot_type: h.hotspot_type,
        animation_type: h.animation_type,
        is_required: h.is_required,
        auto_play: h.auto_play,
        group_id: h.group_id,
        metadata: h.metadata
      }));
      
      submitData.append('hotspots', JSON.stringify(hotspotsData));
      
      // Add hotspot audio files
      hotspots.forEach((hotspot, index) => {
        if (hotspot.audioFile) {
          submitData.append(`hotspot_audio_${index}`, hotspot.audioFile);
        }
      });
      
      const response = await fetch('/api/assignments', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: submitData
      });
      
      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Failed to create assignment');
      }
      
      const data = await response.json();
      router.push(`/teacher/assignments/${data.data?.id || data.id}`);
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create assignment');
    } finally {
      setLoading(false);
    }
  };

  const selectedHotspotData = hotspots.find(h => h.id === selectedHotspot);

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
                    Create Assignment
                  </h1>
                  <p className="text-sm text-gray-600">
                    Design interactive assignments with hotspots
                  </p>
                </div>
              </div>
              
              <div className="flex items-center space-x-3">
                <button
                  onClick={() => router.push('/teacher/assignments')}
                  className="flex items-center space-x-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                >
                  <Eye className="h-4 w-4" />
                  <span>Preview</span>
                </button>
                
                <button
                  onClick={handleSubmit}
                  disabled={loading}
                  className="flex items-center space-x-2 px-6 py-2 bg-maroon-600 text-white rounded-lg hover:bg-maroon-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <Save className="h-4 w-4" />
                  <span>{loading ? 'Creating...' : 'Create Assignment'}</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div className="max-w-7xl mx-auto px-6 py-8">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Assignment Form */}
            <div className="lg:col-span-1 space-y-6">
              {/* Basic Information */}
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h3 className="text-lg font-semibold text-maroon-800 mb-4">
                  Assignment Details
                </h3>
                
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Title *
                    </label>
                    <input
                      type="text"
                      value={formData.title}
                      onChange={(e) => handleInputChange('title', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500 focus:border-transparent"
                      placeholder="Enter assignment title"
                    />
                  </div>
                  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Description
                    </label>
                    <textarea
                      value={formData.description}
                      onChange={(e) => handleInputChange('description', e.target.value)}
                      rows={3}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500 focus:border-transparent"
                      placeholder="Describe the assignment objectives"
                    />
                  </div>
                  
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Due Date
                    </label>
                    <input
                      type="datetime-local"
                      value={formData.due_at}
                      onChange={(e) => handleInputChange('due_at', e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500 focus:border-transparent"
                    />
                  </div>
                </div>
              </div>

              {/* Image Upload */}
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h3 className="text-lg font-semibold text-maroon-800 mb-4">
                  Assignment Image *
                </h3>
                
                <div className="space-y-4">
                  <button
                    onClick={() => fileInputRef.current?.click()}
                    className="w-full flex items-center justify-center space-x-2 px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg hover:border-maroon-400 hover:bg-maroon-50 transition-colors"
                  >
                    <Upload className="h-5 w-5 text-gray-600" />
                    <span className="text-gray-600">
                      {imageFile ? 'Change Image' : 'Upload Image'}
                    </span>
                  </button>
                  
                  <input
                    ref={fileInputRef}
                    type="file"
                    accept="image/*"
                    onChange={handleImageUpload}
                    className="hidden"
                  />
                  
                  {imageFile && (
                    <div className="text-sm text-gray-600">
                      <p>Selected: {imageFile.name}</p>
                      <p>Size: {(imageFile.size / 1024 / 1024).toFixed(2)} MB</p>
                    </div>
                  )}
                </div>
              </div>

              {/* Hotspot Tools */}
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h3 className="text-lg font-semibold text-maroon-800 mb-4">
                  Hotspot Tools
                </h3>
                
                <div className="space-y-3">
                  <button
                    onClick={() => setIsCreatingHotspot(!isCreatingHotspot)}
                    className={`w-full flex items-center justify-center space-x-2 px-4 py-3 rounded-lg transition-colors ${
                      isCreatingHotspot
                        ? 'bg-maroon-600 text-white'
                        : 'bg-maroon-50 text-maroon-700 hover:bg-maroon-100'
                    }`}
                  >
                    <Plus className="h-4 w-4" />
                    <span>
                      {isCreatingHotspot ? 'Click on image to add hotspot' : 'Add Hotspot'}
                    </span>
                  </button>
                  
                  <div className="text-sm text-gray-600">
                    <p>Total hotspots: {hotspots.length}</p>
                    {selectedHotspot && (
                      <p>Selected: {selectedHotspotData?.title}</p>
                    )}
                  </div>
                </div>
              </div>

              {/* Hotspot Properties */}
              <AnimatePresence>
                {selectedHotspotData && (
                  <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -20 }}
                    className="bg-white rounded-lg shadow-sm p-6"
                  >
                    <div className="flex items-center justify-between mb-4">
                      <h3 className="text-lg font-semibold text-maroon-800">
                        Hotspot Properties
                      </h3>
                      <button
                        onClick={() => deleteHotspot(selectedHotspotData.id)}
                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </div>
                    
                    <div className="space-y-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Title
                        </label>
                        <input
                          type="text"
                          value={selectedHotspotData.title || ''}
                          onChange={(e) => updateHotspot(selectedHotspotData.id, { title: e.target.value })}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500"
                        />
                      </div>
                      
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Tooltip
                        </label>
                        <textarea
                          value={selectedHotspotData.tooltip || ''}
                          onChange={(e) => updateHotspot(selectedHotspotData.id, { tooltip: e.target.value })}
                          rows={2}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500"
                        />
                      </div>
                      
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Type
                        </label>
                        <select
                          value={selectedHotspotData.hotspot_type}
                          onChange={(e) => updateHotspot(selectedHotspotData.id, { hotspot_type: e.target.value as any })}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500"
                        >
                          <option value="text">Text</option>
                          <option value="audio">Audio</option>
                          <option value="interactive">Interactive</option>
                          <option value="quiz">Quiz</option>
                        </select>
                      </div>
                      
                      {selectedHotspotData.hotspot_type === 'audio' && (
                        <div>
                          <label className="block text-sm font-medium text-gray-700 mb-2">
                            Audio File
                          </label>
                          <button
                            onClick={() => audioInputRef.current?.click()}
                            className="w-full flex items-center justify-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                          >
                            <Volume2 className="h-4 w-4" />
                            <span>
                              {selectedHotspotData.audioFile ? 'Change Audio' : 'Upload Audio'}
                            </span>
                          </button>
                          
                          <input
                            ref={audioInputRef}
                            type="file"
                            accept="audio/*"
                            onChange={(e) => {
                              const file = e.target.files?.[0];
                              if (file) {
                                handleHotspotAudioUpload(selectedHotspotData.id, file);
                              }
                            }}
                            className="hidden"
                          />
                          
                          {selectedHotspotData.audioFile && (
                            <div className="mt-2 text-sm text-gray-600">
                              <p>{selectedHotspotData.audioFile.name}</p>
                              {selectedHotspotData.audioUrl && (
                                <audio controls className="w-full mt-2">
                                  <source src={selectedHotspotData.audioUrl} />
                                </audio>
                              )}
                            </div>
                          )}
                        </div>
                      )}
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>
            </div>

            {/* Image Editor */}
            <div className="lg:col-span-2">
              <div className="bg-white rounded-lg shadow-sm overflow-hidden">
                <div className="p-4 border-b border-gray-200">
                  <div className="flex items-center justify-between">
                    <h3 className="text-lg font-semibold text-maroon-800">
                      Assignment Preview
                    </h3>
                    {isCreatingHotspot && (
                      <div className="flex items-center space-x-2 text-maroon-600">
                        <Plus className="h-4 w-4" />
                        <span className="text-sm font-medium">Click on image to add hotspot</span>
                      </div>
                    )}
                  </div>
                </div>
                
                <div className="relative">
                  {imageUrl ? (
                    <div className="relative">
                      <img
                        ref={imageRef}
                        src={imageUrl}
                        alt="Assignment"
                        className="w-full h-auto cursor-crosshair"
                        onClick={handleImageClick}
                      />
                      
                      {/* Render Hotspots */}
                      {hotspots.map((hotspot) => (
                        <motion.div
                          key={hotspot.id}
                          className={`absolute cursor-pointer rounded-lg border-2 flex items-center justify-center ${
                            selectedHotspot === hotspot.id
                              ? 'bg-maroon-600 border-maroon-700'
                              : 'bg-maroon-500 border-maroon-600 hover:bg-maroon-600'
                          }`}
                          style={{
                            left: hotspot.x,
                            top: hotspot.y,
                            width: hotspot.width,
                            height: hotspot.height
                          }}
                          onClick={(e) => {
                            e.stopPropagation();
                            setSelectedHotspot(hotspot.id);
                          }}
                          whileHover={{ scale: 1.1 }}
                          whileTap={{ scale: 0.95 }}
                        >
                          {hotspot.hotspot_type === 'audio' ? (
                            <Volume2 className="h-3 w-3 text-white" />
                          ) : (
                            <Info className="h-3 w-3 text-white" />
                          )}
                        </motion.div>
                      ))}
                    </div>
                  ) : (
                    <div className="flex items-center justify-center h-96 bg-gray-50">
                      <div className="text-center text-gray-500">
                        <ImageIcon className="h-12 w-12 mx-auto mb-4" />
                        <p>Upload an image to start creating hotspots</p>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Error Display */}
        <AnimatePresence>
          {error && (
            <motion.div
              initial={{ opacity: 0, y: 50 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: 50 }}
              className="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg"
            >
              <p>{error}</p>
              <button
                onClick={() => setError(null)}
                className="ml-4 text-red-200 hover:text-white"
              >
                ×
              </button>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </Layout>
  );
};

export default CreateAssignmentPage;