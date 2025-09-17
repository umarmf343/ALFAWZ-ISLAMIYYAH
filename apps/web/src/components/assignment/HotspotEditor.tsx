/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useRef, useCallback } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  Plus,
  Trash2,
  Volume2,
  Info,
  Settings,
  Play,
  Square,
  Move,
  RotateCcw,
  Eye,
  EyeOff
} from 'lucide-react';
import { CreateHotspotData, Hotspot } from '../../types/assignment';

interface HotspotDraft extends Omit<CreateHotspotData, 'audio'> {
  id: string | number;
  audioFile?: File;
  audioUrl?: string;
  isNew?: boolean;
}

interface HotspotEditorProps {
  imageUrl: string | null;
  hotspots: HotspotDraft[];
  selectedHotspot: string | number | null;
  onHotspotsChange: (hotspots: HotspotDraft[]) => void;
  onSelectedHotspotChange: (id: string | number | null) => void;
  onDeletedHotspotsChange?: (deletedIds: number[]) => void;
  className?: string;
  readOnly?: boolean;
}

/**
 * Interactive hotspot editor component for assignment images.
 * Allows creating, editing, and managing hotspots with drag-and-drop functionality.
 */
const HotspotEditor: React.FC<HotspotEditorProps> = ({
  imageUrl,
  hotspots,
  selectedHotspot,
  onHotspotsChange,
  onSelectedHotspotChange,
  onDeletedHotspotsChange,
  className = '',
  readOnly = false
}) => {
  const [isCreatingHotspot, setIsCreatingHotspot] = useState(false);
  const [isDragging, setIsDragging] = useState(false);
  const [dragOffset, setDragOffset] = useState({ x: 0, y: 0 });
  const [showHotspots, setShowHotspots] = useState(true);
  const [deletedHotspots, setDeletedHotspots] = useState<number[]>([]);
  
  const imageRef = useRef<HTMLImageElement>(null);
  const audioInputRef = useRef<HTMLInputElement>(null);

  /**
   * Handle image click to create new hotspot.
   * @param event Mouse click event on image
   */
  const handleImageClick = useCallback((event: React.MouseEvent<HTMLImageElement>) => {
    if (!isCreatingHotspot || !imageRef.current || readOnly) return;
    
    const rect = imageRef.current.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    
    const newHotspot: HotspotDraft = {
      id: `new-${Date.now()}`,
      title: `Hotspot ${hotspots.length + 1}`,
      tooltip: '',
      x: Math.round(x),
      y: Math.round(y),
      width: 40,
      height: 40,
      hotspot_type: 'text',
      animation_type: 'pulse',
      is_required: false,
      auto_play: false,
      isNew: true
    };
    
    const updatedHotspots = [...hotspots, newHotspot];
    onHotspotsChange(updatedHotspots);
    onSelectedHotspotChange(newHotspot.id);
    setIsCreatingHotspot(false);
  }, [isCreatingHotspot, hotspots, onHotspotsChange, onSelectedHotspotChange, readOnly]);

  /**
   * Handle hotspot drag start.
   * @param event Mouse down event
   * @param hotspotId Hotspot ID being dragged
   */
  const handleHotspotMouseDown = (event: React.MouseEvent, hotspotId: string | number) => {
    if (readOnly) return;
    
    event.preventDefault();
    event.stopPropagation();
    
    const hotspot = hotspots.find(h => h.id === hotspotId);
    if (!hotspot) return;
    
    const rect = event.currentTarget.getBoundingClientRect();
    const offsetX = event.clientX - rect.left;
    const offsetY = event.clientY - rect.top;
    
    setDragOffset({ x: offsetX, y: offsetY });
    setIsDragging(true);
    onSelectedHotspotChange(hotspotId);
    
    const handleMouseMove = (e: MouseEvent) => {
      if (!imageRef.current) return;
      
      const imageRect = imageRef.current.getBoundingClientRect();
      const newX = Math.max(0, Math.min(
        imageRect.width - hotspot.width,
        e.clientX - imageRect.left - offsetX
      ));
      const newY = Math.max(0, Math.min(
        imageRect.height - hotspot.height,
        e.clientY - imageRect.top - offsetY
      ));
      
      updateHotspot(hotspotId, { x: Math.round(newX), y: Math.round(newY) });
    };
    
    const handleMouseUp = () => {
      setIsDragging(false);
      document.removeEventListener('mousemove', handleMouseMove);
      document.removeEventListener('mouseup', handleMouseUp);
    };
    
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);
  };

  /**
   * Update hotspot properties.
   * @param hotspotId Hotspot ID to update
   * @param updates Partial hotspot data to update
   */
  const updateHotspot = (hotspotId: string | number, updates: Partial<HotspotDraft>) => {
    const updatedHotspots = hotspots.map(h => 
      h.id === hotspotId ? { ...h, ...updates } : h
    );
    onHotspotsChange(updatedHotspots);
  };

  /**
   * Delete hotspot.
   * @param hotspotId Hotspot ID to delete
   */
  const deleteHotspot = (hotspotId: string | number) => {
    const hotspot = hotspots.find(h => h.id === hotspotId);
    
    // If it's an existing hotspot (not new), add to deleted list
    if (hotspot && !hotspot.isNew && typeof hotspotId === 'number') {
      const newDeletedHotspots = [...deletedHotspots, hotspotId];
      setDeletedHotspots(newDeletedHotspots);
      onDeletedHotspotsChange?.(newDeletedHotspots);
    }
    
    const updatedHotspots = hotspots.filter(h => h.id !== hotspotId);
    onHotspotsChange(updatedHotspots);
    
    if (selectedHotspot === hotspotId) {
      onSelectedHotspotChange(null);
    }
  };

  /**
   * Handle audio upload for hotspot.
   * @param hotspotId Hotspot ID
   * @param file Audio file
   */
  const handleHotspotAudioUpload = (hotspotId: string | number, file: File) => {
    const audioUrl = URL.createObjectURL(file);
    updateHotspot(hotspotId, {
      audioFile: file,
      audioUrl: audioUrl,
      hotspot_type: 'audio'
    });
  };

  /**
   * Reset hotspot positions to default grid.
   */
  const resetHotspotPositions = () => {
    if (!imageRef.current) return;
    
    const imageRect = imageRef.current.getBoundingClientRect();
    const cols = Math.floor(imageRect.width / 80);
    
    const updatedHotspots = hotspots.map((hotspot, index) => {
      const row = Math.floor(index / cols);
      const col = index % cols;
      return {
        ...hotspot,
        x: col * 80 + 20,
        y: row * 80 + 20
      };
    });
    
    onHotspotsChange(updatedHotspots);
  };

  const selectedHotspotData = hotspots.find(h => h.id === selectedHotspot);

  return (
    <div className={`relative ${className}`}>
      {/* Toolbar */}
      {!readOnly && (
        <div className="absolute top-4 left-4 z-10 bg-white rounded-lg shadow-lg p-2 flex items-center space-x-2">
          <button
            onClick={() => setIsCreatingHotspot(!isCreatingHotspot)}
            className={`p-2 rounded-lg transition-colors ${
              isCreatingHotspot
                ? 'bg-maroon-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
            title="Add Hotspot"
          >
            <Plus className="h-4 w-4" />
          </button>
          
          <button
            onClick={() => setShowHotspots(!showHotspots)}
            className="p-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors"
            title={showHotspots ? 'Hide Hotspots' : 'Show Hotspots'}
          >
            {showHotspots ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
          </button>
          
          {hotspots.length > 0 && (
            <button
              onClick={resetHotspotPositions}
              className="p-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors"
              title="Reset Positions"
            >
              <RotateCcw className="h-4 w-4" />
            </button>
          )}
          
          <div className="text-xs text-gray-500 px-2">
            {hotspots.length} hotspot{hotspots.length !== 1 ? 's' : ''}
          </div>
        </div>
      )}
      
      {/* Creation Mode Indicator */}
      {isCreatingHotspot && !readOnly && (
        <div className="absolute top-4 right-4 z-10 bg-maroon-600 text-white px-3 py-2 rounded-lg shadow-lg">
          <div className="flex items-center space-x-2">
            <Plus className="h-4 w-4" />
            <span className="text-sm font-medium">Click on image to add hotspot</span>
          </div>
        </div>
      )}

      {/* Image Container */}
      <div className="relative bg-gray-50 rounded-lg overflow-hidden">
        {imageUrl ? (
          <div className="relative">
            <img
              ref={imageRef}
              src={imageUrl}
              alt="Assignment"
              className={`w-full h-auto ${!readOnly ? 'cursor-crosshair' : ''}`}
              onClick={handleImageClick}
              draggable={false}
            />
            
            {/* Render Hotspots */}
            {showHotspots && hotspots.map((hotspot) => (
              <motion.div
                key={hotspot.id}
                className={`absolute rounded-lg border-2 flex items-center justify-center transition-all ${
                  readOnly
                    ? 'cursor-pointer'
                    : isDragging
                    ? 'cursor-grabbing'
                    : 'cursor-grab'
                } ${
                  selectedHotspot === hotspot.id
                    ? 'bg-maroon-600 border-maroon-700 shadow-lg'
                    : hotspot.isNew
                    ? 'bg-green-500 border-green-600 hover:bg-green-600'
                    : 'bg-maroon-500 border-maroon-600 hover:bg-maroon-600'
                }`}
                style={{
                  left: hotspot.x,
                  top: hotspot.y,
                  width: hotspot.width,
                  height: hotspot.height,
                  zIndex: selectedHotspot === hotspot.id ? 20 : 10
                }}
                onClick={(e) => {
                  if (!readOnly) {
                    e.stopPropagation();
                    onSelectedHotspotChange(hotspot.id);
                  }
                }}
                onMouseDown={(e) => handleHotspotMouseDown(e, hotspot.id)}
                whileHover={{ scale: readOnly ? 1.05 : 1.1 }}
                whileTap={{ scale: 0.95 }}
                animate={{
                  scale: hotspot.animation_type === 'pulse' ? [1, 1.1, 1] : 1,
                  opacity: hotspot.animation_type === 'fade' ? [0.7, 1, 0.7] : 1
                }}
                transition={{
                  duration: 2,
                  repeat: hotspot.animation_type !== 'none' ? Infinity : 0,
                  ease: 'easeInOut'
                }}
              >
                {hotspot.hotspot_type === 'audio' ? (
                  <Volume2 className="h-3 w-3 text-white" />
                ) : hotspot.hotspot_type === 'quiz' ? (
                  <Settings className="h-3 w-3 text-white" />
                ) : (
                  <Info className="h-3 w-3 text-white" />
                )}
                
                {/* Hotspot Label */}
                {hotspot.title && selectedHotspot === hotspot.id && (
                  <div className="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs px-2 py-1 rounded whitespace-nowrap">
                    {hotspot.title}
                  </div>
                )}
              </motion.div>
            ))}
            
            {/* Selection Handles */}
            {selectedHotspotData && !readOnly && (
              <div
                className="absolute border-2 border-blue-500 pointer-events-none"
                style={{
                  left: selectedHotspotData.x - 2,
                  top: selectedHotspotData.y - 2,
                  width: selectedHotspotData.width + 4,
                  height: selectedHotspotData.height + 4
                }}
              >
                {/* Resize Handles */}
                <div className="absolute -bottom-1 -right-1 w-3 h-3 bg-blue-500 rounded-full pointer-events-auto cursor-se-resize" />
              </div>
            )}
          </div>
        ) : (
          <div className="flex items-center justify-center h-96 bg-gray-100">
            <div className="text-center text-gray-500">
              <Info className="h-12 w-12 mx-auto mb-4" />
              <p>No image available</p>
            </div>
          </div>
        )}
      </div>

      {/* Hotspot Properties Panel */}
      <AnimatePresence>
        {selectedHotspotData && !readOnly && (
          <motion.div
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: 20 }}
            className="absolute top-0 right-0 w-80 bg-white rounded-lg shadow-xl p-4 z-30 max-h-96 overflow-y-auto"
          >
            <div className="flex items-center justify-between mb-4">
              <h4 className="text-lg font-semibold text-maroon-800">
                Hotspot Properties
              </h4>
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
                  placeholder="Hotspot title"
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
                  placeholder="Tooltip text"
                />
              </div>
              
              <div className="grid grid-cols-2 gap-3">
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
                
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Animation
                  </label>
                  <select
                    value={selectedHotspotData.animation_type}
                    onChange={(e) => updateHotspot(selectedHotspotData.id, { animation_type: e.target.value as any })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon-500"
                  >
                    <option value="none">None</option>
                    <option value="pulse">Pulse</option>
                    <option value="fade">Fade</option>
                    <option value="bounce">Bounce</option>
                  </select>
                </div>
              </div>
              
              <div className="grid grid-cols-4 gap-2">
                <div>
                  <label className="block text-xs font-medium text-gray-700 mb-1">
                    X
                  </label>
                  <input
                    type="number"
                    value={selectedHotspotData.x}
                    onChange={(e) => updateHotspot(selectedHotspotData.id, { x: parseInt(e.target.value) || 0 })}
                    className="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 mb-1">
                    Y
                  </label>
                  <input
                    type="number"
                    value={selectedHotspotData.y}
                    onChange={(e) => updateHotspot(selectedHotspotData.id, { y: parseInt(e.target.value) || 0 })}
                    className="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 mb-1">
                    W
                  </label>
                  <input
                    type="number"
                    value={selectedHotspotData.width}
                    onChange={(e) => updateHotspot(selectedHotspotData.id, { width: parseInt(e.target.value) || 20 })}
                    className="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-700 mb-1">
                    H
                  </label>
                  <input
                    type="number"
                    value={selectedHotspotData.height}
                    onChange={(e) => updateHotspot(selectedHotspotData.id, { height: parseInt(e.target.value) || 20 })}
                    className="w-full px-2 py-1 border border-gray-300 rounded text-sm"
                  />
                </div>
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
                      {selectedHotspotData.audioFile || selectedHotspotData.audioUrl ? 'Change Audio' : 'Upload Audio'}
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
                  
                  {(selectedHotspotData.audioFile || selectedHotspotData.audioUrl) && (
                    <div className="mt-2">
                      {selectedHotspotData.audioFile && (
                        <p className="text-sm text-gray-600 mb-2">
                          New: {selectedHotspotData.audioFile.name}
                        </p>
                      )}
                      {selectedHotspotData.audioUrl && (
                        <audio controls className="w-full">
                          <source src={selectedHotspotData.audioUrl} />
                        </audio>
                      )}
                    </div>
                  )}
                </div>
              )}
              
              <div className="flex items-center space-x-4">
                <label className="flex items-center space-x-2">
                  <input
                    type="checkbox"
                    checked={selectedHotspotData.is_required}
                    onChange={(e) => updateHotspot(selectedHotspotData.id, { is_required: e.target.checked })}
                    className="rounded border-gray-300 text-maroon-600 focus:ring-maroon-500"
                  />
                  <span className="text-sm text-gray-700">Required</span>
                </label>
                
                <label className="flex items-center space-x-2">
                  <input
                    type="checkbox"
                    checked={selectedHotspotData.auto_play}
                    onChange={(e) => updateHotspot(selectedHotspotData.id, { auto_play: e.target.checked })}
                    className="rounded border-gray-300 text-maroon-600 focus:ring-maroon-500"
                  />
                  <span className="text-sm text-gray-700">Auto Play</span>
                </label>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
};

export default HotspotEditor;