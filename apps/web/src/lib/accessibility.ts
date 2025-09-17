/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

/**
 * Accessibility utilities for screen readers and keyboard navigation
 */

/**
 * Announce text to screen readers using aria-live regions
 * @param message Text to announce
 * @param priority 'polite' (default) or 'assertive'
 */
export function announceToScreenReader(message: string, priority: 'polite' | 'assertive' = 'polite') {
  const announcement = document.createElement('div');
  announcement.setAttribute('aria-live', priority);
  announcement.setAttribute('aria-atomic', 'true');
  announcement.className = 'sr-only';
  announcement.textContent = message;
  
  document.body.appendChild(announcement);
  
  // Remove after announcement
  setTimeout(() => {
    document.body.removeChild(announcement);
  }, 1000);
}

/**
 * Handle keyboard navigation for interactive elements
 * @param event Keyboard event
 * @param callback Function to execute on Enter/Space
 */
export function handleKeyboardActivation(
  event: React.KeyboardEvent,
  callback: () => void
) {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault();
    callback();
  }
}

/**
 * Generate unique IDs for ARIA relationships
 * @param prefix Optional prefix for the ID
 * @returns Unique ID string
 */
export function generateAriaId(prefix: string = 'aria'): string {
  return `${prefix}-${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * Focus management utilities
 */
export const focusManagement = {
  /**
   * Focus the first focusable element within a container
   * @param container Container element
   */
  focusFirst(container: HTMLElement) {
    const focusableElements = container.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0] as HTMLElement;
    if (firstElement) {
      firstElement.focus();
    }
  },

  /**
   * Trap focus within a container (useful for modals)
   * @param container Container element
   * @param event Keyboard event
   */
  trapFocus(container: HTMLElement, event: KeyboardEvent) {
    if (event.key !== 'Tab') return;

    const focusableElements = container.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    ) as NodeListOf<HTMLElement>;

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    if (event.shiftKey) {
      if (document.activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();
      }
    } else {
      if (document.activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
      }
    }
  },

  /**
   * Save and restore focus for dynamic content changes
   */
  saveFocus(): () => void {
    const activeElement = document.activeElement as HTMLElement;
    return () => {
      if (activeElement && activeElement.focus) {
        activeElement.focus();
      }
    };
  }
};

/**
 * Screen reader only text utility
 * @param text Text for screen readers only
 * @returns JSX element with sr-only class
 */
export function ScreenReaderOnly({ children }: { children: React.ReactNode }) {
  return (
    <span className="sr-only">
      {children}
    </span>
  );
}

/**
 * ARIA live region component for dynamic announcements
 */
export function LiveRegion({ 
  message, 
  priority = 'polite' 
}: { 
  message: string; 
  priority?: 'polite' | 'assertive' 
}) {
  return (
    <div
      aria-live={priority}
      aria-atomic="true"
      className="sr-only"
    >
      {message}
    </div>
  );
}

/**
 * Skip link component for keyboard navigation
 */
export function SkipLink({ href, children }: { href: string; children: React.ReactNode }) {
  return (
    <a
      href={href}
      className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-maroon-600 text-white px-4 py-2 rounded-md z-50 focus:outline-none focus:ring-2 focus:ring-maroon-500 focus:ring-offset-2"
    >
      {children}
    </a>
  );
}

/**
 * Progress announcer for dynamic progress updates
 * @param current Current progress value
 * @param total Total progress value
 * @param label Label for the progress
 */
export function announceProgress(current: number, total: number, label: string) {
  const percentage = Math.round((current / total) * 100);
  announceToScreenReader(`${label}: ${percentage}% complete`, 'polite');
}

/**
 * Validation for ARIA attributes
 */
export const ariaValidation = {
  /**
   * Validate that required ARIA attributes are present
   * @param element Element to validate
   * @param requiredAttrs Array of required ARIA attributes
   */
  validateRequiredAttrs(element: HTMLElement, requiredAttrs: string[]): boolean {
    return requiredAttrs.every(attr => element.hasAttribute(attr));
  },

  /**
   * Check if element has proper labeling
   * @param element Element to check
   */
  hasProperLabeling(element: HTMLElement): boolean {
    return (
      element.hasAttribute('aria-label') ||
      element.hasAttribute('aria-labelledby') ||
      element.hasAttribute('title') ||
      (element.tagName === 'INPUT' && element.hasAttribute('placeholder'))
    );
  }
};