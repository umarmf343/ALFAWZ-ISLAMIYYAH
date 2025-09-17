/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { createContext, useContext, useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { spiritualTheme, spiritualAnimations, spiritualStyles } from '@/styles/theme';

interface SpiritualThemeContextType {
  theme: typeof spiritualTheme;
  animations: typeof spiritualAnimations;
  styles: typeof spiritualStyles;
  isDarkMode: boolean;
  toggleDarkMode: () => void;
  currentThemeMode: 'light' | 'dark';
  setThemeMode: (mode: 'light' | 'dark') => void;
}

const SpiritualThemeContext = createContext<SpiritualThemeContextType | undefined>(undefined);

interface SpiritualThemeProviderProps {
  children: React.ReactNode;
}

/**
 * Spiritual theme provider that manages theme state and provides Islamic design context.
 * Includes dark/light mode support and spiritual animations.
 */
export const SpiritualThemeProvider: React.FC<SpiritualThemeProviderProps> = ({ children }) => {
  const [isDarkMode, setIsDarkMode] = useState(false);
  const [currentThemeMode, setCurrentThemeMode] = useState<'light' | 'dark'>('light');

  // Load theme preference from localStorage on mount
  useEffect(() => {
    const savedTheme = localStorage.getItem('spiritual-theme-mode');
    if (savedTheme === 'dark') {
      setIsDarkMode(true);
      setCurrentThemeMode('dark');
      document.documentElement.classList.add('dark');
    } else {
      setIsDarkMode(false);
      setCurrentThemeMode('light');
      document.documentElement.classList.remove('dark');
    }
  }, []);

  /**
   * Toggle between light and dark theme modes.
   */
  const toggleDarkMode = () => {
    const newMode = !isDarkMode;
    setIsDarkMode(newMode);
    setCurrentThemeMode(newMode ? 'dark' : 'light');
    
    // Update localStorage
    localStorage.setItem('spiritual-theme-mode', newMode ? 'dark' : 'light');
    
    // Update document class
    if (newMode) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  };

  /**
   * Set specific theme mode.
   */
  const setThemeMode = (mode: 'light' | 'dark') => {
    setIsDarkMode(mode === 'dark');
    setCurrentThemeMode(mode);
    localStorage.setItem('spiritual-theme-mode', mode);
    
    if (mode === 'dark') {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  };

  const contextValue: SpiritualThemeContextType = {
    theme: spiritualTheme,
    animations: spiritualAnimations,
    styles: spiritualStyles,
    isDarkMode,
    toggleDarkMode,
    currentThemeMode,
    setThemeMode
  };

  return (
    <SpiritualThemeContext.Provider value={contextValue}>
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ duration: 0.5 }}
        className="min-h-screen transition-colors duration-300"
        style={{
          background: isDarkMode 
            ? 'linear-gradient(135deg, #1a1a1a 0%, #2d1b1b 100%)'
            : spiritualTheme.gradients.subtle
        }}
      >
        <AnimatePresence mode="wait">
          {children}
        </AnimatePresence>
      </motion.div>
    </SpiritualThemeContext.Provider>
  );
};

/**
 * Hook to use spiritual theme context.
 * Provides access to theme colors, animations, and utilities.
 */
export const useSpiritualTheme = (): SpiritualThemeContextType => {
  const context = useContext(SpiritualThemeContext);
  if (context === undefined) {
    throw new Error('useSpiritualTheme must be used within a SpiritualThemeProvider');
  }
  return context;
};

/**
 * Higher-order component to wrap components with spiritual theme.
 */
export const withSpiritualTheme = <P extends object>(
  Component: React.ComponentType<P>
) => {
  const WrappedComponent = (props: P) => {
    const theme = useSpiritualTheme();
    return <Component {...props} theme={theme} />;
  };
  
  WrappedComponent.displayName = `withSpiritualTheme(${Component.displayName || Component.name})`;
  return WrappedComponent;
};

/**
 * Spiritual loading component with Islamic-inspired animations.
 */
export const SpiritualLoader: React.FC<{ size?: 'sm' | 'md' | 'lg' }> = ({ size = 'md' }) => {
  const { theme, animations } = useSpiritualTheme();
  
  const sizeClasses = {
    sm: 'w-6 h-6',
    md: 'w-8 h-8',
    lg: 'w-12 h-12'
  };

  return (
    <motion.div
      className="flex items-center justify-center"
      {...animations.fadeInUp}
    >
      <motion.div
        className={`${sizeClasses[size]} rounded-full border-2 border-transparent`}
        style={{
          background: `conic-gradient(from 0deg, ${theme.colors.gold[400]}, ${theme.colors.maroon[600]}, ${theme.colors.gold[400]})`,
          padding: '2px'
        }}
        {...animations.spinner}
      >
        <div 
          className="w-full h-full rounded-full"
          style={{ background: theme.colors.milk[50] }}
        />
      </motion.div>
    </motion.div>
  );
};

/**
 * Spiritual notification component with theme-aware styling.
 */
interface SpiritualNotificationProps {
  type: 'success' | 'error' | 'warning' | 'info';
  title: string;
  message: string;
  onClose: () => void;
}

export const SpiritualNotification: React.FC<SpiritualNotificationProps> = ({
  type,
  title,
  message,
  onClose
}) => {
  const { theme, animations } = useSpiritualTheme();
  
  const typeStyles = {
    success: {
      background: `linear-gradient(135deg, ${theme.colors.accent.emerald}15, ${theme.colors.accent.emerald}25)`,
      borderColor: theme.colors.accent.emerald,
      iconColor: theme.colors.accent.emerald
    },
    error: {
      background: `linear-gradient(135deg, ${theme.colors.accent.ruby}15, ${theme.colors.accent.ruby}25)`,
      borderColor: theme.colors.accent.ruby,
      iconColor: theme.colors.accent.ruby
    },
    warning: {
      background: `linear-gradient(135deg, ${theme.colors.gold[400]}15, ${theme.colors.gold[400]}25)`,
      borderColor: theme.colors.gold[400],
      iconColor: theme.colors.gold[600]
    },
    info: {
      background: `linear-gradient(135deg, ${theme.colors.accent.sapphire}15, ${theme.colors.accent.sapphire}25)`,
      borderColor: theme.colors.accent.sapphire,
      iconColor: theme.colors.accent.sapphire
    }
  };

  return (
    <motion.div
      className="fixed top-4 right-4 z-50 max-w-sm w-full"
      {...animations.notification}
    >
      <div
        className="p-4 rounded-lg border backdrop-blur-sm"
        style={{
          background: typeStyles[type].background,
          borderColor: typeStyles[type].borderColor,
          boxShadow: theme.shadows.lg
        }}
      >
        <div className="flex items-start space-x-3">
          <div 
            className="w-5 h-5 rounded-full flex-shrink-0 mt-0.5"
            style={{ backgroundColor: typeStyles[type].iconColor }}
          />
          <div className="flex-1">
            <h4 className="font-semibold text-gray-900 mb-1">{title}</h4>
            <p className="text-sm text-gray-700">{message}</p>
          </div>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            ×
          </button>
        </div>
      </div>
    </motion.div>
  );
};

/**
 * Spiritual card component with theme-aware styling and animations.
 */
interface SpiritualCardProps {
  children: React.ReactNode;
  className?: string;
  hover?: boolean;
  glow?: boolean;
  pattern?: boolean;
}

export const SpiritualCard: React.FC<SpiritualCardProps> = ({
  children,
  className = '',
  hover = true,
  glow = false,
  pattern = false
}) => {
  const { theme, animations, styles } = useSpiritualTheme();

  return (
    <motion.div
      className={`relative ${className}`}
      style={{
        ...styles.spiritualCard,
        ...(pattern ? styles.patternOverlay : {})
      }}
      {...(hover ? animations.cardHover : {})}
      {...(glow ? animations.spiritualGlow : {})}
    >
      {children}
    </motion.div>
  );
};

/**
 * Spiritual button component with theme-aware styling.
 */
interface SpiritualButtonProps {
  children: React.ReactNode;
  variant?: 'primary' | 'secondary' | 'accent' | 'ghost';
  size?: 'sm' | 'md' | 'lg';
  onClick?: () => void;
  disabled?: boolean;
  className?: string;
}

export const SpiritualButton: React.FC<SpiritualButtonProps> = ({
  children,
  variant = 'primary',
  size = 'md',
  onClick,
  disabled = false,
  className = ''
}) => {
  const { theme, animations } = useSpiritualTheme();
  
  const variantStyles = {
    primary: {
      background: theme.gradients.primary,
      color: theme.colors.milk[50],
      border: 'none'
    },
    secondary: {
      background: theme.gradients.secondary,
      color: theme.colors.maroon[900],
      border: 'none'
    },
    accent: {
      background: theme.gradients.accent,
      color: theme.colors.maroon[900],
      border: 'none'
    },
    ghost: {
      background: 'transparent',
      color: theme.colors.maroon[700],
      border: `1px solid ${theme.colors.maroon[300]}`
    }
  };
  
  const sizeStyles = {
    sm: { padding: '0.5rem 1rem', fontSize: '0.875rem' },
    md: { padding: '0.75rem 1.5rem', fontSize: '1rem' },
    lg: { padding: '1rem 2rem', fontSize: '1.125rem' }
  };

  return (
    <motion.button
      className={`rounded-lg font-medium transition-all duration-200 ${className}`}
      style={{
        ...variantStyles[variant],
        ...sizeStyles[size],
        opacity: disabled ? 0.6 : 1,
        cursor: disabled ? 'not-allowed' : 'pointer',
        boxShadow: theme.shadows.md
      }}
      onClick={disabled ? undefined : onClick}
      disabled={disabled}
      {...animations.buttonPress}
    >
      {children}
    </motion.button>
  );
};

export default SpiritualThemeProvider;