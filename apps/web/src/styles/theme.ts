/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

/**
 * Spiritual theme configuration for AlFawz Qur'an Institute.
 * Features maroon, gold, and milk color palette with Islamic design elements.
 */

export const spiritualTheme = {
  colors: {
    // Primary Colors - Maroon Palette
    maroon: {
      50: '#fdf2f2',
      100: '#fce7e7',
      200: '#f9d5d5',
      300: '#f4b5b5',
      400: '#ec8888',
      500: '#dc5f5f',
      600: '#c53030',
      700: '#9b2c2c',
      800: '#822727',
      900: '#742a2a',
      950: '#4a1414'
    },
    
    // Secondary Colors - Gold Palette
    gold: {
      50: '#fffbeb',
      100: '#fef3c7',
      200: '#fde68a',
      300: '#fcd34d',
      400: '#fbbf24',
      500: '#f59e0b',
      600: '#d97706',
      700: '#b45309',
      800: '#92400e',
      900: '#78350f',
      950: '#451a03'
    },
    
    // Neutral Colors - Milk/Cream Palette
    milk: {
      50: '#fefefe',
      100: '#fdfdfd',
      200: '#fafafa',
      300: '#f7f7f7',
      400: '#f1f1f1',
      500: '#e8e8e8',
      600: '#d1d1d1',
      700: '#b4b4b4',
      800: '#8a8a8a',
      900: '#6f6f6f',
      950: '#525252'
    },
    
    // Accent Colors
    accent: {
      emerald: '#10b981',
      sapphire: '#3b82f6',
      amethyst: '#8b5cf6',
      ruby: '#ef4444'
    },
    
    // Semantic Colors
    success: '#10b981',
    warning: '#f59e0b',
    error: '#ef4444',
    info: '#3b82f6'
  },
  
  gradients: {
    primary: 'linear-gradient(135deg, #9b2c2c 0%, #742a2a 100%)',
    secondary: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
    accent: 'linear-gradient(135deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%)',
    spiritual: 'linear-gradient(135deg, #9b2c2c 0%, #f59e0b 50%, #fef3c7 100%)',
    subtle: 'linear-gradient(135deg, #fefefe 0%, #f7f7f7 100%)'
  },
  
  shadows: {
    sm: '0 1px 2px 0 rgba(155, 44, 44, 0.05)',
    md: '0 4px 6px -1px rgba(155, 44, 44, 0.1), 0 2px 4px -1px rgba(155, 44, 44, 0.06)',
    lg: '0 10px 15px -3px rgba(155, 44, 44, 0.1), 0 4px 6px -2px rgba(155, 44, 44, 0.05)',
    xl: '0 20px 25px -5px rgba(155, 44, 44, 0.1), 0 10px 10px -5px rgba(155, 44, 44, 0.04)',
    gold: '0 4px 14px 0 rgba(245, 158, 11, 0.25)',
    inner: 'inset 0 2px 4px 0 rgba(155, 44, 44, 0.06)'
  },
  
  typography: {
    fontFamily: {
      arabic: ['Amiri', 'Noto Naskh Arabic', 'serif'],
      primary: ['Inter', 'system-ui', 'sans-serif'],
      heading: ['Playfair Display', 'Georgia', 'serif']
    },
    fontSize: {
      xs: '0.75rem',
      sm: '0.875rem',
      base: '1rem',
      lg: '1.125rem',
      xl: '1.25rem',
      '2xl': '1.5rem',
      '3xl': '1.875rem',
      '4xl': '2.25rem',
      '5xl': '3rem'
    }
  },
  
  spacing: {
    xs: '0.25rem',
    sm: '0.5rem',
    md: '1rem',
    lg: '1.5rem',
    xl: '2rem',
    '2xl': '3rem',
    '3xl': '4rem'
  },
  
  borderRadius: {
    sm: '0.25rem',
    md: '0.375rem',
    lg: '0.5rem',
    xl: '0.75rem',
    '2xl': '1rem',
    full: '9999px'
  },
  
  animation: {
    duration: {
      fast: 150,
      normal: 300,
      slow: 500
    },
    easing: {
      easeInOut: 'cubic-bezier(0.4, 0, 0.2, 1)',
      easeOut: 'cubic-bezier(0, 0, 0.2, 1)',
      easeIn: 'cubic-bezier(0.4, 0, 1, 1)',
      bounce: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)'
    }
  },
  
  islamic: {
    patterns: {
      geometric: 'url("/patterns/islamic-geometric.svg")',
      arabesque: 'url("/patterns/arabesque.svg")',
      calligraphy: 'url("/patterns/calligraphy-border.svg")'
    },
    ornaments: {
      corner: 'url("/ornaments/corner.svg")',
      divider: 'url("/ornaments/divider.svg")',
      frame: 'url("/ornaments/frame.svg")'
    }
  }
};

/**
 * Framer Motion animation variants for spiritual theme.
 */
export const spiritualAnimations = {
  // Page transitions
  pageTransition: {
    initial: { opacity: 0, y: 20 },
    animate: { opacity: 1, y: 0 },
    exit: { opacity: 0, y: -20 },
    transition: { duration: 0.3, ease: 'easeInOut' }
  },
  
  // Card animations
  cardHover: {
    rest: { scale: 1, boxShadow: spiritualTheme.shadows.md },
    hover: { 
      scale: 1.02, 
      boxShadow: spiritualTheme.shadows.xl,
      transition: { duration: 0.2, ease: 'easeOut' }
    }
  },
  
  // Button animations
  buttonPress: {
    whileHover: { scale: 1.05 },
    whileTap: { scale: 0.95 },
    transition: { type: 'spring', stiffness: 400, damping: 17 }
  },
  
  // Spiritual glow effect
  spiritualGlow: {
    animate: {
      boxShadow: [
        '0 0 20px rgba(245, 158, 11, 0.3)',
        '0 0 30px rgba(245, 158, 11, 0.5)',
        '0 0 20px rgba(245, 158, 11, 0.3)'
      ]
    },
    transition: {
      duration: 2,
      repeat: Infinity,
      ease: 'easeInOut'
    }
  },
  
  // Fade in from bottom
  fadeInUp: {
    initial: { opacity: 0, y: 30 },
    animate: { opacity: 1, y: 0 },
    transition: { duration: 0.5, ease: 'easeOut' }
  },
  
  // Stagger children animation
  staggerChildren: {
    animate: {
      transition: {
        staggerChildren: 0.1,
        delayChildren: 0.2
      }
    }
  },
  
  // Pulse animation for important elements
  pulse: {
    animate: {
      scale: [1, 1.05, 1],
      opacity: [1, 0.8, 1]
    },
    transition: {
      duration: 1.5,
      repeat: Infinity,
      ease: 'easeInOut'
    }
  },
  
  // Slide in from right (for Arabic content)
  slideInRight: {
    initial: { opacity: 0, x: 50 },
    animate: { opacity: 1, x: 0 },
    transition: { duration: 0.4, ease: 'easeOut' }
  },
  
  // Rotate in animation
  rotateIn: {
    initial: { opacity: 0, rotate: -10, scale: 0.9 },
    animate: { opacity: 1, rotate: 0, scale: 1 },
    transition: { duration: 0.5, ease: 'easeOut' }
  },
  
  // Loading spinner
  spinner: {
    animate: { rotate: 360 },
    transition: {
      duration: 1,
      repeat: Infinity,
      ease: 'linear'
    }
  },
  
  // Success checkmark
  checkmark: {
    initial: { pathLength: 0, opacity: 0 },
    animate: { pathLength: 1, opacity: 1 },
    transition: { duration: 0.5, ease: 'easeOut' }
  },
  
  // Modal animations
  modal: {
    overlay: {
      initial: { opacity: 0 },
      animate: { opacity: 1 },
      exit: { opacity: 0 }
    },
    content: {
      initial: { opacity: 0, scale: 0.9, y: 20 },
      animate: { opacity: 1, scale: 1, y: 0 },
      exit: { opacity: 0, scale: 0.9, y: 20 },
      transition: { type: 'spring', damping: 25, stiffness: 300 }
    }
  },
  
  // Notification animations
  notification: {
    initial: { opacity: 0, x: 300, scale: 0.3 },
    animate: { opacity: 1, x: 0, scale: 1 },
    exit: { opacity: 0, x: 300, scale: 0.5, transition: { duration: 0.2 } },
    transition: { type: 'spring', damping: 25, stiffness: 300 }
  }
};

/**
 * CSS-in-JS styles for spiritual theme components.
 */
export const spiritualStyles = {
  // Gradient backgrounds
  gradientBg: {
    primary: {
      background: spiritualTheme.gradients.primary,
      color: spiritualTheme.colors.milk[50]
    },
    secondary: {
      background: spiritualTheme.gradients.secondary,
      color: spiritualTheme.colors.maroon[900]
    },
    accent: {
      background: spiritualTheme.gradients.accent,
      color: spiritualTheme.colors.maroon[900]
    }
  },
  
  // Glass morphism effect
  glassMorphism: {
    background: 'rgba(254, 254, 254, 0.25)',
    backdropFilter: 'blur(10px)',
    border: '1px solid rgba(255, 255, 255, 0.18)',
    boxShadow: '0 8px 32px 0 rgba(31, 38, 135, 0.37)'
  },
  
  // Islamic pattern overlay
  patternOverlay: {
    position: 'relative',
    '&::before': {
      content: '""',
      position: 'absolute',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      backgroundImage: spiritualTheme.islamic.patterns.geometric,
      opacity: 0.05,
      pointerEvents: 'none'
    }
  },
  
  // Spiritual card style
  spiritualCard: {
    background: spiritualTheme.colors.milk[50],
    border: `1px solid ${spiritualTheme.colors.gold[200]}`,
    borderRadius: spiritualTheme.borderRadius.lg,
    boxShadow: spiritualTheme.shadows.md,
    transition: 'all 0.3s ease',
    '&:hover': {
      boxShadow: spiritualTheme.shadows.xl,
      borderColor: spiritualTheme.colors.gold[300]
    }
  },
  
  // Arabic text styling
  arabicText: {
    fontFamily: spiritualTheme.typography.fontFamily.arabic.join(', '),
    direction: 'rtl',
    lineHeight: 1.8,
    fontSize: '1.25rem'
  },
  
  // Hasanat counter styling
  hasanatCounter: {
    background: spiritualTheme.gradients.secondary,
    color: spiritualTheme.colors.maroon[900],
    padding: '0.5rem 1rem',
    borderRadius: spiritualTheme.borderRadius.full,
    fontWeight: 'bold',
    boxShadow: spiritualTheme.shadows.gold
  }
};

export default spiritualTheme;