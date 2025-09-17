/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { FaStar, FaTrophy, FaGem, FaCrown, FaFire } from 'react-icons/fa';
import confetti from 'canvas-confetti';

interface HasanatCounterProps {
  currentHasanat: number;
  previousHasanat?: number;
  className?: string;
}

interface Milestone {
  threshold: number;
  title: string;
  icon: React.ReactNode;
  color: string;
  description: string;
}

const MILESTONES: Milestone[] = [
  {
    threshold: 1000,
    title: 'First Steps',
    icon: <FaStar className="text-2xl" />,
    color: 'from-blue-400 to-blue-600',
    description: 'You\'ve earned your first 1,000 Hasanat!'
  },
  {
    threshold: 10000,
    title: 'Dedicated Reader',
    icon: <FaFire className="text-2xl" />,
    color: 'from-orange-400 to-red-500',
    description: '10,000 Hasanat! Your dedication is inspiring!'
  },
  {
    threshold: 50000,
    title: 'Bronze Reciter',
    icon: <FaTrophy className="text-2xl" />,
    color: 'from-amber-600 to-orange-700',
    description: '50,000 Hasanat achieved! You\'re a Bronze Reciter!'
  },
  {
    threshold: 100000,
    title: 'Silver Reciter',
    icon: <FaGem className="text-2xl" />,
    color: 'from-gray-400 to-gray-600',
    description: '100,000 Hasanat! Welcome to Silver status!'
  },
  {
    threshold: 500000,
    title: 'Gold Reciter',
    icon: <FaCrown className="text-2xl" />,
    color: 'from-yellow-400 to-yellow-600',
    description: '500,000 Hasanat! You\'ve reached Gold level!'
  },
  {
    threshold: 1000000,
    title: 'Diamond Master',
    icon: <FaGem className="text-2xl" />,
    color: 'from-purple-400 to-pink-500',
    description: '1 Million Hasanat! You are a Diamond Master!'
  }
];

/**
 * Real-time Hasanat counter with animations and milestone celebrations.
 */
export default function HasanatCounter({ currentHasanat, previousHasanat = 0, className = '' }: HasanatCounterProps) {
  const [displayHasanat, setDisplayHasanat] = useState(previousHasanat);
  const [showMilestone, setShowMilestone] = useState<Milestone | null>(null);
  const [isAnimating, setIsAnimating] = useState(false);

  // Check for milestone achievements
  const checkMilestone = (newHasanat: number, oldHasanat: number): Milestone | null => {
    for (const milestone of MILESTONES) {
      if (newHasanat >= milestone.threshold && oldHasanat < milestone.threshold) {
        return milestone;
      }
    }
    return null;
  };

  // Trigger confetti animation
  const triggerConfetti = () => {
    const colors = ['#FFD700', '#FFA500', '#FF6347', '#32CD32', '#1E90FF', '#9370DB'];
    
    confetti({
      particleCount: 100,
      spread: 70,
      origin: { y: 0.6 },
      colors: colors
    });
    
    // Second burst
    setTimeout(() => {
      confetti({
        particleCount: 50,
        spread: 60,
        origin: { y: 0.7 },
        colors: colors
      });
    }, 250);
  };

  // Animate counter from previous to current value
  useEffect(() => {
    if (currentHasanat !== previousHasanat) {
      setIsAnimating(true);
      
      // Check for milestone
      const milestone = checkMilestone(currentHasanat, previousHasanat);
      
      // Animate the number counting up
      const duration = Math.min(2000, Math.max(500, (currentHasanat - previousHasanat) * 2));
      const steps = 60;
      const increment = (currentHasanat - previousHasanat) / steps;
      let currentStep = 0;
      
      const timer = setInterval(() => {
        currentStep++;
        const newValue = previousHasanat + (increment * currentStep);
        
        if (currentStep >= steps) {
          setDisplayHasanat(currentHasanat);
          setIsAnimating(false);
          clearInterval(timer);
          
          // Show milestone if achieved
          if (milestone) {
            setTimeout(() => {
              setShowMilestone(milestone);
              triggerConfetti();
            }, 300);
          }
        } else {
          setDisplayHasanat(Math.floor(newValue));
        }
      }, duration / steps);
      
      return () => clearInterval(timer);
    }
  }, [currentHasanat, previousHasanat]);

  // Get current milestone progress
  const getCurrentMilestoneProgress = () => {
    const nextMilestone = MILESTONES.find(m => m.threshold > currentHasanat);
    if (!nextMilestone) return null;
    
    const previousMilestone = MILESTONES.filter(m => m.threshold <= currentHasanat).pop();
    const previousThreshold = previousMilestone?.threshold || 0;
    
    const progress = ((currentHasanat - previousThreshold) / (nextMilestone.threshold - previousThreshold)) * 100;
    
    return {
      nextMilestone,
      progress: Math.min(100, Math.max(0, progress)),
      remaining: nextMilestone.threshold - currentHasanat
    };
  };

  const milestoneProgress = getCurrentMilestoneProgress();

  return (
    <>
      <motion.div 
        initial={{ opacity: 0, scale: 0.9 }}
        animate={{ opacity: 1, scale: 1 }}
        className={`bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl p-6 text-white relative overflow-hidden ${className}`}
      >
        {/* Background animation */}
        <motion.div
          className="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent"
          animate={{
            x: isAnimating ? ['-100%', '100%'] : '-100%'
          }}
          transition={{
            duration: 1.5,
            ease: 'easeInOut',
            repeat: isAnimating ? Infinity : 0
          }}
        />
        
        <div className="relative z-10">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold">Total Hasanat</h3>
            <motion.div
              animate={{
                rotate: isAnimating ? [0, 360] : 0,
                scale: isAnimating ? [1, 1.2, 1] : 1
              }}
              transition={{ duration: 0.8 }}
            >
              <FaStar className="text-2xl text-amber-200" />
            </motion.div>
          </div>
          
          <motion.div 
            className="text-4xl font-bold mb-2"
            animate={{
              scale: isAnimating ? [1, 1.1, 1] : 1
            }}
            transition={{ duration: 0.5 }}
          >
            {displayHasanat.toLocaleString()}
          </motion.div>
          
          {/* Milestone Progress */}
          {milestoneProgress && (
            <div className="mt-4">
              <div className="flex justify-between text-sm text-amber-100 mb-2">
                <span>Next: {milestoneProgress.nextMilestone.title}</span>
                <span>{milestoneProgress.remaining.toLocaleString()} to go</span>
              </div>
              <div className="w-full bg-amber-600/30 rounded-full h-2">
                <motion.div
                  className="bg-amber-200 h-2 rounded-full"
                  initial={{ width: 0 }}
                  animate={{ width: `${milestoneProgress.progress}%` }}
                  transition={{ duration: 1, ease: 'easeOut' }}
                />
              </div>
            </div>
          )}
          
          <p className="text-amber-100 mt-2">
            {isAnimating ? 'Calculating rewards...' : 'Keep up the great work!'}
          </p>
        </div>
      </motion.div>

      {/* Milestone Celebration Modal */}
      <AnimatePresence>
        {showMilestone && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            onClick={() => setShowMilestone(null)}
          >
            <motion.div
              initial={{ scale: 0.5, opacity: 0, y: 50 }}
              animate={{ scale: 1, opacity: 1, y: 0 }}
              exit={{ scale: 0.5, opacity: 0, y: 50 }}
              transition={{ type: 'spring', damping: 15, stiffness: 300 }}
              className={`bg-gradient-to-br ${showMilestone.color} rounded-3xl p-8 text-white text-center max-w-md mx-auto shadow-2xl`}
              onClick={(e) => e.stopPropagation()}
            >
              <motion.div
                initial={{ scale: 0 }}
                animate={{ scale: 1 }}
                transition={{ delay: 0.2, type: 'spring', damping: 10 }}
                className="mb-6"
              >
                {showMilestone.icon}
              </motion.div>
              
              <motion.h2
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.3 }}
                className="text-3xl font-bold mb-4"
              >
                🎉 Milestone Achieved! 🎉
              </motion.h2>
              
              <motion.h3
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.4 }}
                className="text-xl font-semibold mb-2"
              >
                {showMilestone.title}
              </motion.h3>
              
              <motion.p
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.5 }}
                className="text-lg mb-6 opacity-90"
              >
                {showMilestone.description}
              </motion.p>
              
              <motion.button
                initial={{ opacity: 0, scale: 0.8 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ delay: 0.6 }}
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
                onClick={() => setShowMilestone(null)}
                className="bg-white/20 hover:bg-white/30 px-8 py-3 rounded-full font-semibold transition-colors"
              >
                Continue Journey
              </motion.button>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
}