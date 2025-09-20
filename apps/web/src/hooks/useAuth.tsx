/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import { useState, useEffect, createContext, useContext, useCallback } from 'react';
import type { ComponentType, ReactNode } from 'react';

interface User {
  id: number;
  name: string;
  email: string;
  role: 'student' | 'teacher' | 'admin';
  avatar?: string;
  created_at: string;
  updated_at: string;
}

interface AuthContextType {
  user: User | null;
  token: string | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  register: (userData: RegisterData) => Promise<void>;
  refreshUser: () => Promise<void>;
}

interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  role?: 'student' | 'teacher';
}

// Create Auth Context
const AuthContext = createContext<AuthContextType | undefined>(undefined);

/**
 * Custom hook for authentication management.
 * Provides user state, login, logout, and registration functionality.
 */
export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

/**
 * Authentication hook implementation.
 * Manages user authentication state and API calls.
 */
export const useAuthImplementation = (): AuthContextType => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  /**
   * Login user with email and password.
   * @param email User email
   * @param password User password
   */
  const login = async (email: string, password: string): Promise<void> => {
    try {
      setLoading(true);
      
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ email, password })
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Login failed');
      }

      const data = await response.json();
      
      if (!data.token || !data.user) {
        throw new Error('Invalid response from server');
      }

      // Store authentication data
      setToken(data.token);
      setUser(data.user);
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
      
    } catch (error) {
      throw new Error(error instanceof Error ? error.message : 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  /**
   * Register new user account.
   * @param userData User registration data
   */
  const register = async (userData: RegisterData): Promise<void> => {
    try {
      setLoading(true);
      
      const response = await fetch('/api/auth/register', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(userData)
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Registration failed');
      }

      const data = await response.json();
      
      if (!data.token || !data.user) {
        throw new Error('Invalid response from server');
      }

      // Store authentication data
      setToken(data.token);
      setUser(data.user);
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
      
    } catch (error) {
      throw new Error(error instanceof Error ? error.message : 'Registration failed');
    } finally {
      setLoading(false);
    }
  };

  /**
   * Logout user and clear authentication data.
   */
  const logout = useCallback(async (): Promise<void> => {
    try {
      // Call logout endpoint if token exists
      if (token) {
        await fetch('/api/auth/logout', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });
      }
    } catch (error) {
      console.error('Logout API error:', error);
    } finally {
      // Clear local state regardless of API call result
      setUser(null);
      setToken(null);
      localStorage.removeItem('token');
      localStorage.removeItem('user');
    }
  }, [token]);

  /**
   * Refresh user data from server.
   */
  const refreshUser = useCallback(async (): Promise<void> => {
    try {
      if (!token) {
        throw new Error('No authentication token');
      }

      const response = await fetch('/api/me', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error('Failed to refresh user data');
      }

      const data = await response.json();
      const userData = data.data || data.user || data;
      
      setUser(userData);
      localStorage.setItem('user', JSON.stringify(userData));

    } catch (error) {
      console.error('Refresh user error:', error);
      // If refresh fails, logout user
      logout();
      throw error;
    }
  }, [token, logout]);

  /**
   * Initialize authentication state from localStorage.
   */
  useEffect(() => {
    const initAuth = async () => {
      try {
        const storedToken = localStorage.getItem('token');
        const storedUser = localStorage.getItem('user');

        if (storedToken && storedUser) {
          setToken(storedToken);
          setUser(JSON.parse(storedUser));

          // Verify token is still valid
          await refreshUser();
        }
      } catch (error) {
        console.error('Auth initialization error:', error);
        // Clear invalid data
        localStorage.removeItem('token');
        localStorage.removeItem('user');
      } finally {
        setLoading(false);
      }
    };

    initAuth();
  }, [refreshUser]);

  return {
    user,
    token,
    loading,
    login,
    logout,
    register,
    refreshUser
  };
};

/**
 * Auth Provider component to wrap the app.
 */
export function AuthProvider({ children }: { children: ReactNode }) {
  const auth = useAuthImplementation();

  return (
    <AuthContext.Provider value={auth}>
      {children}
    </AuthContext.Provider>
  );
}

/**
 * Higher-order component for protecting routes.
 * Redirects to login if user is not authenticated.
 */
export const withAuth = <P extends object>(Component: ComponentType<P>) => {
  return function AuthenticatedComponent(props: P) {
    const { user, loading } = useAuth();
    
    if (loading) {
      return (
        <div className="flex items-center justify-center min-h-screen">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-maroon-600"></div>
        </div>
      );
    }
    
    if (!user) {
      // Redirect to login
      if (typeof window !== 'undefined') {
        window.location.href = '/auth/login';
      }
      return null;
    }
    
    return <Component {...props} />;
  };
};

/**
 * Hook for role-based access control.
 * @param allowedRoles Array of roles that can access the resource
 */
export const useRoleAccess = (allowedRoles: string[]) => {
  const { user } = useAuth();
  
  const hasAccess = user && allowedRoles.includes(user.role);
  const isLoading = !user;
  
  return {
    hasAccess,
    isLoading,
    userRole: user?.role
  };
};