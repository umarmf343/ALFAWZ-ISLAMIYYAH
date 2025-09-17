/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { createContext, useContext, useEffect, useState } from 'react';
import { api } from '@/lib/api';
import { User, AuthContextType, LoginCredentials, RegisterData } from '@/types/auth';

const AuthContext = createContext<AuthContextType | undefined>(undefined);

/**
 * Authentication context provider component.
 * Manages user authentication state and provides auth methods.
 */
export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  /**
   * Initialize authentication state from localStorage
   */
  useEffect(() => {
    const initAuth = async () => {
      const storedToken = localStorage.getItem('auth_token');
      if (storedToken) {
        api.setToken(storedToken);
        setToken(storedToken);
        
        try {
          const response = await api.get('/me');
          setUser(response.data);
        } catch (error) {
          console.error('Failed to fetch user:', error);
          // Clear invalid token
          api.clearToken();
          setToken(null);
        }
      }
      setIsLoading(false);
    };

    initAuth();
  }, []);

  /**
   * Login user with email and password
   */
  const login = async (credentials: LoginCredentials) => {
    try {
      const response = await api.post('/auth/login', credentials);
      const { user: userData, token: authToken } = response.data;
      
      setUser(userData);
      setToken(authToken);
      api.setToken(authToken);
    } catch (error) {
      console.error('Login failed:', error);
      throw error;
    }
  };

  /**
   * Register new user account
   */
  const register = async (data: RegisterData) => {
    try {
      const response = await api.post('/auth/register', data);
      const { user: userData, token: authToken } = response.data;
      
      setUser(userData);
      setToken(authToken);
      api.setToken(authToken);
    } catch (error) {
      console.error('Registration failed:', error);
      throw error;
    }
  };

  /**
   * Logout user and clear authentication state
   */
  const logout = async () => {
    try {
      await api.post('/auth/logout');
    } catch (error) {
      console.error('Logout request failed:', error);
    } finally {
      setUser(null);
      setToken(null);
      api.clearToken();
    }
  };

  const value: AuthContextType = {
    user,
    token,
    isLoading,
    login,
    register,
    logout,
    isAuthenticated: !!user,
    isTeacher: user?.role === 'teacher',
    isStudent: user?.role === 'student',
    isAdmin: user?.role === 'admin',
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

/**
 * Hook to access authentication context
 */
export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}