/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Volume2, Info, Play } from 'lucide-react';
import { Hotspot } from '../../types/assignment';

interface HotspotComponentProps {
  hotspot: Hotspot;
  onClick: (hotspot: Hotspot, event: React.MouseEvent) => void;
  isSelected?: boolean;
  imageWidth?: number;
  imageHeight?: number;
}

/**
 * Interactive hotspot component that renders clickable areas on assignment images.
 * Displays tooltips, handles audio playback, and tracks user interactions.
 */
const HotspotComponent: React.FC<HotspotComponentProps> = ({
  hotspot,
  onClick,
  isSelected = false,
  imageWidth = 1000,
  imageHeight = 800
}) => {
  const [isHovered, setIsHovered] = useState(false);
  const [showTooltip, setShowTooltip] = useState(false);

  /**
   * Handle hotspot click with interaction tracking.
   * @param event Mouse click event
   */
  const handleClick = (event: React.MouseEvent) => {
    event.preventDefault();
    event.stopPropagation();
    onClick(hotspot, event);
    setShowTooltip(!showTooltip);
  };

  /**
   * Calculate responsive position based on image dimensions.
   */
  const getResponsiveStyle = () => {
    const xPercent = (hotspot.x / imageWidth) * 100;
    const yPercent = (hotspot.y / imageHeight) * 100;
    const widthPercent = (hotspot.width / imageWidth) * 100;
    const heightPercent = (hotspot.height / imageHeight) * 100;

    return {
      left: `${xPercent}%`,
      top: `${yPercent}%`,
      width: `${widthPercent}%`,
      height: `${heightPercent}%`
    };
  };

  /**
   * Get hotspot animation variants for different states.
   */
  const getHotspotVariants = () => {
    return {
      idle: {
        scale: 1,
        opacity: 0.7,
        boxShadow: '0 0 0 2px rgba(139, 69, 19, 0.3)'
      },
      hover: {
        scale: 1.05,
        opacity: 0.9,
        boxShadow: '0 0 0 3px rgba(139, 69, 19, 0.5)'
      },
      selected: {
        scale: 1.1,
        opacity: 1,
        boxShadow: '0 0 0 4px rgba(139, 69, 19, 0.8)'
      }
    };
  };

  /**
   * Get hotspot type indicator icon.
   */
  const getHotspotIcon = () => {
    if (hotspot.audio_s3_url) {
      return <Volume2 className="h-3 w-3 text-white" />;
    }
    if (hotspot.tooltip) {
      return <Info className="h-3 w-3 text-white" />;
    }
    return <Play className="h-3 w-3 text-white" />;
  };

  return (
    <>
      {/* Main Hotspot Area */}
      <motion.div
        className="absolute cursor-pointer bg-maroon-600 rounded-lg border-2 border-maroon-700 flex items-center justify-center"
        style={getResponsiveStyle()}
        variants={getHotspotVariants()}
        initial="idle"
        animate={isSelected ? 'selected' : isHovered ? 'hover' : 'idle'}
        whileHover="hover"
        whileTap={{ scale: 0.95 }}
        onClick={handleClick}
        onMouseEnter={() => setIsHovered(true)}
        onMouseLeave={() => setIsHovered(false)}
        transition={{
          type: "spring",
          stiffness: 300,
          damping: 20
        }}
      >
        {/* Hotspot Icon */}
        <motion.div
          animate={{
            rotate: isSelected ? 360 : 0,
            scale: isHovered ? 1.2 : 1
          }}
          transition={{ duration: 0.3 }}
        >
          {getHotspotIcon()}
        </motion.div>

        {/* Pulse Animation for Active Hotspots */}
        <motion.div
          className="absolute inset-0 bg-maroon-400 rounded-lg"
          animate={{
            scale: [1, 1.2, 1],
            opacity: [0.3, 0.1, 0.3]
          }}
          transition={{
            duration: 2,
            repeat: Infinity,
            ease: "easeInOut"
          }}
        />
      </motion.div>

      {/* Tooltip */}
      <AnimatePresence>
        {(showTooltip || isHovered) && hotspot.tooltip && (
          <motion.div
            initial={{ opacity: 0, y: 10, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 10, scale: 0.9 }}
            transition={{ duration: 0.2 }}
            className="absolute z-10 bg-white rounded-lg shadow-lg border border-gray-200 p-3 max-w-xs"
            style={{
              left: `${(hotspot.x / imageWidth) * 100}%`,
              top: `${((hotspot.y + hotspot.height + 10) / imageHeight) * 100}%`,
              transform: 'translateX(-50%)'
            }}
          >
            {/* Tooltip Arrow */}
            <div className="absolute -top-2 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-white border-l border-t border-gray-200 rotate-45"></div>
            
            {/* Tooltip Content */}
            <div className="relative">
              {hotspot.title && (
                <h4 className="font-semibold text-maroon-800 mb-1 text-sm">
                  {hotspot.title}
                </h4>
              )}
              <p className="text-gray-700 text-xs leading-relaxed">
                {hotspot.tooltip}
              </p>
              
              {hotspot.audio_s3_url && (
                <div className="mt-2 pt-2 border-t border-gray-100">
                  <div className="flex items-center space-x-1 text-xs text-maroon-600">
                    <Volume2 className="h-3 w-3" />
                    <span>Click to play audio</span>
                  </div>
                </div>
              )}
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Selection Indicator */}
      <AnimatePresence>
        {isSelected && (
          <motion.div
            initial={{ opacity: 0, scale: 0.8 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.8 }}
            className="absolute bg-gold-400 text-maroon-800 px-2 py-1 rounded-full text-xs font-semibold shadow-lg"
            style={{
              left: `${((hotspot.x + hotspot.width / 2) / imageWidth) * 100}%`,
              top: `${((hotspot.y - 30) / imageHeight) * 100}%`,
              transform: 'translateX(-50%)'
            }}
          >
            Selected
          </motion.div>
        )}
      </AnimatePresence>

      {/* Interaction Ripple Effect */}
      <AnimatePresence>
        {isSelected && (
          <motion.div
            className="absolute pointer-events-none"
            style={{
              left: `${((hotspot.x + hotspot.width / 2) / imageWidth) * 100}%`,
              top: `${((hotspot.y + hotspot.height / 2) / imageHeight) * 100}%`,
              transform: 'translate(-50%, -50%)'
            }}
            initial={{ scale: 0, opacity: 0.8 }}
            animate={{ scale: 3, opacity: 0 }}
            exit={{ scale: 0, opacity: 0 }}
            transition={{ duration: 0.6, ease: "easeOut" }}
          >
            <div className="w-4 h-4 bg-maroon-400 rounded-full" />
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
};

export default HotspotComponent;