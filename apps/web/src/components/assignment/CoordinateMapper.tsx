/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useRef, useCallback, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  MousePointer,
  Move,
  RotateCcw,
  ZoomIn,
  ZoomOut,
  Grid,
  Crosshair,
  Save,
  Undo,
  Redo,
  Trash2
} from 'lucide-react';
import { Hotspot } from '../../types/assignment';

interface CoordinateMapperProps {
  imageUrl: string;
  hotspots: Hotspot[];
  onHotspotsChange: (hotspots: Hotspot[]) => void;
  isEditable?: boolean;
  showGrid?: boolean;
  snapToGrid?: boolean;
  gridSize?: number;
  className?: string;
}

interface DragState {
  isDragging: boolean;
  draggedHotspot: Hotspot | null;
  startPosition: { x: number; y: number };
  offset: { x: number; y: number };
}

interface ViewState {
  zoom: number;
  pan: { x: number; y: number };
}

/**
 * Coordinate mapping component for interactive hotspot positioning.
 * Supports drag-and-drop, zoom, pan, grid snapping, and undo/redo functionality.
 */
const CoordinateMapper: React.FC<CoordinateMapperProps> = ({
  imageUrl,
  hotspots,
  onHotspotsChange,
  isEditable = true,
  showGrid = false,
  snapToGrid = false,
  gridSize = 20,
  className = ''
}) => {
  // Container and image refs
  const containerRef = useRef<HTMLDivElement>(null);
  const imageRef = useRef<HTMLImageElement>(null);
  
  // State management
  const [dragState, setDragState] = useState<DragState>({
    isDragging: false,
    draggedHotspot: null,
    startPosition: { x: 0, y: 0 },
    offset: { x: 0, y: 0 }
  });
  
  const [viewState, setViewState] = useState<ViewState>({
    zoom: 1,
    pan: { x: 0, y: 0 }
  });
  
  const [imageLoaded, setImageLoaded] = useState(false);
  const [imageDimensions, setImageDimensions] = useState({ width: 0, height: 0 });
  const [history, setHistory] = useState<Hotspot[][]>([hotspots]);
  const [historyIndex, setHistoryIndex] = useState(0);
  const [selectedHotspot, setSelectedHotspot] = useState<string | null>(null);
  const [isCreatingHotspot, setIsCreatingHotspot] = useState(false);

  /**
   * Snap coordinates to grid if enabled.
   * @param x X coordinate
   * @param y Y coordinate
   * @returns Snapped coordinates
   */
  const snapToGridIfEnabled = useCallback((x: number, y: number) => {
    if (!snapToGrid) return { x, y };
    
    return {
      x: Math.round(x / gridSize) * gridSize,
      y: Math.round(y / gridSize) * gridSize
    };
  }, [snapToGrid, gridSize]);

  /**
   * Convert screen coordinates to image coordinates.
   * @param screenX Screen X coordinate
   * @param screenY Screen Y coordinate
   * @returns Image coordinates
   */
  const screenToImageCoordinates = useCallback((screenX: number, screenY: number) => {
    if (!containerRef.current || !imageRef.current) return { x: 0, y: 0 };
    
    const containerRect = containerRef.current.getBoundingClientRect();
    const imageRect = imageRef.current.getBoundingClientRect();
    
    // Calculate relative position within the image
    const relativeX = (screenX - imageRect.left) / imageRect.width;
    const relativeY = (screenY - imageRect.top) / imageRect.height;
    
    // Convert to actual image coordinates
    const imageX = relativeX * imageDimensions.width;
    const imageY = relativeY * imageDimensions.height;
    
    return snapToGridIfEnabled(imageX, imageY);
  }, [imageDimensions, snapToGridIfEnabled]);

  /**
   * Convert image coordinates to screen coordinates.
   * @param imageX Image X coordinate
   * @param imageY Image Y coordinate
   * @returns Screen coordinates
   */
  const imageToScreenCoordinates = useCallback((imageX: number, imageY: number) => {
    if (!imageRef.current) return { x: 0, y: 0 };
    
    const imageRect = imageRef.current.getBoundingClientRect();
    
    const relativeX = imageX / imageDimensions.width;
    const relativeY = imageY / imageDimensions.height;
    
    return {
      x: imageRect.left + (relativeX * imageRect.width),
      y: imageRect.top + (relativeY * imageRect.height)
    };
  }, [imageDimensions]);

  /**
   * Add hotspot to history for undo/redo functionality.
   * @param newHotspots Updated hotspots array
   */
  const addToHistory = useCallback((newHotspots: Hotspot[]) => {
    const newHistory = history.slice(0, historyIndex + 1);
    newHistory.push([...newHotspots]);
    
    // Limit history to 50 entries
    if (newHistory.length > 50) {
      newHistory.shift();
    } else {
      setHistoryIndex(prev => prev + 1);
    }
    
    setHistory(newHistory);
  }, [history, historyIndex]);

  /**
   * Handle mouse down on hotspot for dragging.
   * @param hotspot Hotspot being dragged
   * @param event Mouse event
   */
  const handleHotspotMouseDown = useCallback((hotspot: Hotspot, event: React.MouseEvent) => {
    if (!isEditable) return;
    
    event.preventDefault();
    event.stopPropagation();
    
    setSelectedHotspot(hotspot.id);
    
    const screenCoords = imageToScreenCoordinates(hotspot.x, hotspot.y);
    
    setDragState({
      isDragging: true,
      draggedHotspot: hotspot,
      startPosition: { x: event.clientX, y: event.clientY },
      offset: {
        x: event.clientX - screenCoords.x,
        y: event.clientY - screenCoords.y
      }
    });
  }, [isEditable, imageToScreenCoordinates]);

  /**
   * Handle mouse move for dragging hotspots.
   * @param event Mouse event
   */
  const handleMouseMove = useCallback((event: MouseEvent) => {
    if (!dragState.isDragging || !dragState.draggedHotspot) return;
    
    const newCoords = screenToImageCoordinates(
      event.clientX - dragState.offset.x,
      event.clientY - dragState.offset.y
    );
    
    // Constrain to image boundaries
    const constrainedX = Math.max(0, Math.min(newCoords.x, imageDimensions.width - dragState.draggedHotspot.width));
    const constrainedY = Math.max(0, Math.min(newCoords.y, imageDimensions.height - dragState.draggedHotspot.height));
    
    const updatedHotspots = hotspots.map(h => 
      h.id === dragState.draggedHotspot!.id 
        ? { ...h, x: constrainedX, y: constrainedY }
        : h
    );
    
    onHotspotsChange(updatedHotspots);
  }, [dragState, screenToImageCoordinates, imageDimensions, hotspots, onHotspotsChange]);

  /**
   * Handle mouse up to end dragging.
   */
  const handleMouseUp = useCallback(() => {
    if (dragState.isDragging) {
      addToHistory(hotspots);
      setDragState({
        isDragging: false,
        draggedHotspot: null,
        startPosition: { x: 0, y: 0 },
        offset: { x: 0, y: 0 }
      });
    }
  }, [dragState.isDragging, hotspots, addToHistory]);

  /**
   * Handle image click to create new hotspot.
   * @param event Mouse event
   */
  const handleImageClick = useCallback((event: React.MouseEvent) => {
    if (!isEditable || !isCreatingHotspot || dragState.isDragging) return;
    
    const coords = screenToImageCoordinates(event.clientX, event.clientY);
    
    const newHotspot: Hotspot = {
      id: `hotspot_${Date.now()}`,
      assignment_id: 0, // Will be set by parent
      title: `Hotspot ${hotspots.length + 1}`,
      tooltip: 'Click to interact',
      audio_url: null,
      x: coords.x,
      y: coords.y,
      width: 40,
      height: 40,
      hotspot_type: 'interactive',
      animation_type: 'pulse',
      is_required: false,
      auto_play: false,
      metadata: null,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    };
    
    const updatedHotspots = [...hotspots, newHotspot];
    onHotspotsChange(updatedHotspots);
    addToHistory(updatedHotspots);
    setSelectedHotspot(newHotspot.id);
    setIsCreatingHotspot(false);
  }, [isEditable, isCreatingHotspot, dragState.isDragging, screenToImageCoordinates, hotspots, onHotspotsChange, addToHistory]);

  /**
   * Handle zoom controls.
   * @param delta Zoom delta (-1 for zoom out, 1 for zoom in)
   */
  const handleZoom = useCallback((delta: number) => {
    setViewState(prev => ({
      ...prev,
      zoom: Math.max(0.1, Math.min(3, prev.zoom + (delta * 0.1)))
    }));
  }, []);

  /**
   * Reset view to default state.
   */
  const resetView = useCallback(() => {
    setViewState({ zoom: 1, pan: { x: 0, y: 0 } });
  }, []);

  /**
   * Undo last action.
   */
  const undo = useCallback(() => {
    if (historyIndex > 0) {
      const prevHotspots = history[historyIndex - 1];
      onHotspotsChange(prevHotspots);
      setHistoryIndex(prev => prev - 1);
    }
  }, [history, historyIndex, onHotspotsChange]);

  /**
   * Redo last undone action.
   */
  const redo = useCallback(() => {
    if (historyIndex < history.length - 1) {
      const nextHotspots = history[historyIndex + 1];
      onHotspotsChange(nextHotspots);
      setHistoryIndex(prev => prev + 1);
    }
  }, [history, historyIndex, onHotspotsChange]);

  /**
   * Delete selected hotspot.
   */
  const deleteSelectedHotspot = useCallback(() => {
    if (!selectedHotspot) return;
    
    const updatedHotspots = hotspots.filter(h => h.id !== selectedHotspot);
    onHotspotsChange(updatedHotspots);
    addToHistory(updatedHotspots);
    setSelectedHotspot(null);
  }, [selectedHotspot, hotspots, onHotspotsChange, addToHistory]);

  /**
   * Handle image load to get dimensions.
   */
  const handleImageLoad = useCallback(() => {
    if (imageRef.current) {
      setImageDimensions({
        width: imageRef.current.naturalWidth,
        height: imageRef.current.naturalHeight
      });
      setImageLoaded(true);
    }
  }, []);

  // Setup mouse event listeners
  useEffect(() => {
    if (dragState.isDragging) {
      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
      
      return () => {
        document.removeEventListener('mousemove', handleMouseMove);
        document.removeEventListener('mouseup', handleMouseUp);
      };
    }
  }, [dragState.isDragging, handleMouseMove, handleMouseUp]);

  // Keyboard shortcuts
  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      if (!isEditable) return;
      
      if (event.ctrlKey || event.metaKey) {
        switch (event.key) {
          case 'z':
            event.preventDefault();
            if (event.shiftKey) {
              redo();
            } else {
              undo();
            }
            break;
          case 'y':
            event.preventDefault();
            redo();
            break;
        }
      } else {
        switch (event.key) {
          case 'Delete':
          case 'Backspace':
            if (selectedHotspot) {
              event.preventDefault();
              deleteSelectedHotspot();
            }
            break;
          case 'Escape':
            setSelectedHotspot(null);
            setIsCreatingHotspot(false);
            break;
        }
      }
    };
    
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [isEditable, selectedHotspot, undo, redo, deleteSelectedHotspot]);

  return (
    <div className={`relative bg-gray-100 rounded-lg overflow-hidden ${className}`}>
      {/* Toolbar */}
      {isEditable && (
        <div className="absolute top-4 left-4 z-20 bg-white rounded-lg shadow-lg p-2 flex items-center space-x-2">
          <button
            onClick={() => setIsCreatingHotspot(!isCreatingHotspot)}
            className={`p-2 rounded transition-colors ${
              isCreatingHotspot 
                ? 'bg-maroon-500 text-white' 
                : 'bg-gray-100 hover:bg-gray-200 text-gray-700'
            }`}
            title="Create Hotspot"
          >
            <Crosshair className="h-4 w-4" />
          </button>
          
          <div className="w-px h-6 bg-gray-300" />
          
          <button
            onClick={undo}
            disabled={historyIndex <= 0}
            className="p-2 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            title="Undo (Ctrl+Z)"
          >
            <Undo className="h-4 w-4" />
          </button>
          
          <button
            onClick={redo}
            disabled={historyIndex >= history.length - 1}
            className="p-2 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            title="Redo (Ctrl+Y)"
          >
            <Redo className="h-4 w-4" />
          </button>
          
          <div className="w-px h-6 bg-gray-300" />
          
          <button
            onClick={() => handleZoom(-1)}
            className="p-2 rounded bg-gray-100 hover:bg-gray-200 text-gray-700"
            title="Zoom Out"
          >
            <ZoomOut className="h-4 w-4" />
          </button>
          
          <span className="text-sm text-gray-600 min-w-[3rem] text-center">
            {Math.round(viewState.zoom * 100)}%
          </span>
          
          <button
            onClick={() => handleZoom(1)}
            className="p-2 rounded bg-gray-100 hover:bg-gray-200 text-gray-700"
            title="Zoom In"
          >
            <ZoomIn className="h-4 w-4" />
          </button>
          
          <button
            onClick={resetView}
            className="p-2 rounded bg-gray-100 hover:bg-gray-200 text-gray-700"
            title="Reset View"
          >
            <RotateCcw className="h-4 w-4" />
          </button>
          
          <div className="w-px h-6 bg-gray-300" />
          
          <button
            onClick={deleteSelectedHotspot}
            disabled={!selectedHotspot}
            className="p-2 rounded bg-red-100 hover:bg-red-200 text-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
            title="Delete Selected (Delete)"
          >
            <Trash2 className="h-4 w-4" />
          </button>
        </div>
      )}
      
      {/* Main container */}
      <div 
        ref={containerRef}
        className="relative w-full h-full overflow-hidden cursor-crosshair"
        style={{
          transform: `scale(${viewState.zoom}) translate(${viewState.pan.x}px, ${viewState.pan.y}px)`
        }}
        onClick={handleImageClick}
      >
        {/* Grid overlay */}
        {showGrid && imageLoaded && (
          <div 
            className="absolute inset-0 pointer-events-none opacity-30"
            style={{
              backgroundImage: `
                linear-gradient(to right, #ccc 1px, transparent 1px),
                linear-gradient(to bottom, #ccc 1px, transparent 1px)
              `,
              backgroundSize: `${gridSize}px ${gridSize}px`
            }}
          />
        )}
        
        {/* Image */}
        <img
          ref={imageRef}
          src={imageUrl}
          alt="Assignment base image"
          className="w-full h-auto block"
          onLoad={handleImageLoad}
          draggable={false}
        />
        
        {/* Hotspots */}
        {imageLoaded && hotspots.map((hotspot) => {
          const screenCoords = imageToScreenCoordinates(hotspot.x, hotspot.y);
          const isSelected = selectedHotspot === hotspot.id;
          const isDragging = dragState.draggedHotspot?.id === hotspot.id;
          
          return (
            <motion.div
              key={hotspot.id}
              className={`
                absolute cursor-move rounded-full border-2 flex items-center justify-center
                transition-all duration-200 select-none z-10
                ${
                  isSelected 
                    ? 'border-maroon-500 bg-maroon-500 shadow-lg' 
                    : 'border-blue-500 bg-blue-500 hover:border-blue-600 hover:bg-blue-600'
                }
                ${isDragging ? 'scale-110 shadow-xl' : ''}
              `}
              style={{
                left: hotspot.x,
                top: hotspot.y,
                width: hotspot.width,
                height: hotspot.height,
                transform: `translate(-50%, -50%)`
              }}
              onMouseDown={(e) => handleHotspotMouseDown(hotspot, e)}
              onClick={(e) => {
                e.stopPropagation();
                setSelectedHotspot(hotspot.id);
              }}
              whileHover={{ scale: 1.05 }}
              animate={{
                scale: isSelected ? 1.1 : 1,
                boxShadow: isSelected 
                  ? '0 0 0 2px rgba(139, 69, 19, 0.3)' 
                  : '0 0 0 0px transparent'
              }}
            >
              <MousePointer className="h-4 w-4 text-white" />
              
              {/* Hotspot label */}
              {isSelected && (
                <div className="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs px-2 py-1 rounded whitespace-nowrap">
                  {hotspot.title}
                </div>
              )}
            </motion.div>
          );
        })}
        
        {/* Creation cursor */}
        {isCreatingHotspot && (
          <div className="absolute inset-0 cursor-crosshair" />
        )}
      </div>
      
      {/* Status bar */}
      <div className="absolute bottom-4 right-4 bg-black bg-opacity-75 text-white text-xs px-3 py-2 rounded">
        {hotspots.length} hotspot{hotspots.length !== 1 ? 's' : ''}
        {selectedHotspot && ` • Selected: ${hotspots.find(h => h.id === selectedHotspot)?.title}`}
        {isCreatingHotspot && ' • Click to create hotspot'}
      </div>
    </div>
  );
};

export default CoordinateMapper;