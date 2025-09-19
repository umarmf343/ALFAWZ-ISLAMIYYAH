/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect, useCallback } from 'react';
import { useRouter } from 'next/router';
import Link from 'next/link';
import { 
  HomeIcon, 
  UsersIcon, 
  ChartBarIcon, 
  AcademicCapIcon,
  ExclamationTriangleIcon,
  CreditCardIcon,
  CogIcon,
  LogoutIcon,
  MenuIcon,
  XIcon
} from '@heroicons/react/outline';

interface AdminLayoutProps {
  children: React.ReactNode;
  title?: string;
}

interface ApiPermission {
  name?: string;
}

interface RawUser {
  id: number;
  name: string;
  email: string;
  role?: string;
  status?: string;
  permissions?: Array<string | ApiPermission>;
  all_permissions?: Array<string | ApiPermission>;
  roles?: Array<string | ApiPermission>;
}

interface MeResponse {
  user?: RawUser;
}

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  permissions: string[];
}

interface NavigationItem {
  name: string;
  href: string;
  icon: React.ComponentType<React.SVGProps<SVGSVGElement>>;
  requiredPermissions: string[];
}

/**
 * Admin layout component with navigation, role guards, and maroon/gold theme.
 * Provides sidebar navigation and role-based access control for admin pages.
 */
export default function AdminLayout({ children, title = 'Admin Dashboard' }: AdminLayoutProps) {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [loading, setLoading] = useState(true);

  // Navigation items with role-based visibility
  const navigationItems: NavigationItem[] = [
    {
      name: 'Dashboard',
      href: '/admin',
      icon: HomeIcon,
      requiredPermissions: ['admin.dashboard.view']
    },
    {
      name: 'Users',
      href: '/admin/users',
      icon: UsersIcon,
      requiredPermissions: ['admin.users.view']
    },
    {
      name: 'Analytics',
      href: '/admin/analytics',
      icon: ChartBarIcon,
      requiredPermissions: ['admin.analytics.view']
    },
    {
      name: 'Classes',
      href: '/admin/classes',
      icon: AcademicCapIcon,
      requiredPermissions: ['admin.classes.view']
    },
    {
      name: 'Content Moderation',
      href: '/admin/moderation',
      icon: ExclamationTriangleIcon,
      requiredPermissions: ['admin.content.moderate']
    },
    {
      name: 'Payments',
      href: '/admin/payments',
      icon: CreditCardIcon,
      requiredPermissions: ['admin.payments.view']
    },
    {
      name: 'Settings',
      href: '/admin/settings',
      icon: CogIcon,
      requiredPermissions: ['admin.settings.manage']
    }
  ];

  /**
   * Check if user has required permissions for a navigation item.
   * @param requiredPermissions Array of required permissions
   * @returns boolean indicating if user has access
   */
  const hasPermission = useCallback((requiredPermissions: string[]): boolean => {
    if (!user) return false;
    if (user.role === 'admin') return true;
    if (!requiredPermissions.length) return true;

    return requiredPermissions.some(permission => user.permissions.includes(permission));
  }, [user]);

  const normalizeUser = useCallback((payload: RawUser): User => {
    const roleCandidates: string[] = [];

    if (payload.role) {
      roleCandidates.push(payload.role);
    }

    if (Array.isArray(payload.roles)) {
      payload.roles.forEach((roleItem) => {
        if (typeof roleItem === 'string') {
          roleCandidates.push(roleItem);
        } else if (roleItem?.name) {
          roleCandidates.push(roleItem.name);
        }
      });
    }

    const resolvedRole = (roleCandidates.find(Boolean) || 'student').toLowerCase();

    const permissionSet = new Set<string>();

    const pushPermissions = (perms?: Array<string | ApiPermission>) => {
      if (!Array.isArray(perms)) return;
      perms.forEach((perm) => {
        if (typeof perm === 'string') {
          permissionSet.add(perm);
        } else if (perm?.name) {
          permissionSet.add(perm.name);
        }
      });
    };

    pushPermissions(payload.permissions);
    pushPermissions(payload.all_permissions);

    const adminDefaults = [
      'admin.dashboard.view',
      'admin.users.view',
      'admin.analytics.view',
      'admin.classes.view',
      'admin.content.moderate',
      'admin.payments.view',
      'admin.settings.manage'
    ];

    const teacherDefaults = [
      'admin.dashboard.view',
      'admin.analytics.view',
      'admin.classes.view'
    ];

    if (resolvedRole === 'admin') {
      adminDefaults.forEach((perm) => permissionSet.add(perm));
    } else if (resolvedRole === 'teacher') {
      teacherDefaults.forEach((perm) => permissionSet.add(perm));
    }

    return {
      id: payload.id,
      name: payload.name,
      email: payload.email,
      role: resolvedRole,
      permissions: Array.from(permissionSet)
    };
  }, []);

  /**
   * Fetch current user data and verify admin access.
   */
  const fetchUserData = useCallback(async () => {
    try {
      if (typeof window === 'undefined') return;

      const token = window.localStorage.getItem('auth_token');
      if (!token) {
        router.push('/auth/login');
        return;
      }

      const response = await fetch(`${process.env.NEXT_PUBLIC_API_BASE}/me`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error('Failed to fetch user data');
      }

      const raw: MeResponse | RawUser = await response.json();
      const payload = 'user' in raw && raw.user ? raw.user : (raw as RawUser);

      if (!payload) {
        throw new Error('Invalid user payload received');
      }

      const normalizedUser = normalizeUser(payload);

      if (!['admin', 'teacher'].includes(normalizedUser.role)) {
        router.push('/dashboard');
        return;
      }

      setUser(normalizedUser);
    } catch (error) {
      console.error('Error fetching user data:', error);
      router.push('/auth/login');
    } finally {
      setLoading(false);
    }
  }, [normalizeUser, router]);

  /**
   * Handle user logout.
   */
  const handleLogout = async () => {
    try {
      if (typeof window !== 'undefined') {
        const token = window.localStorage.getItem('auth_token');
        if (token) {
          await fetch(`${process.env.NEXT_PUBLIC_API_BASE}/auth/logout`, {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json'
            }
          });
        }
      }
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      if (typeof window !== 'undefined') {
        window.localStorage.removeItem('auth_token');
      }
      router.push('/auth/login');
    }
  };

  useEffect(() => {
    fetchUserData();
  }, [fetchUserData]);

  // Show loading spinner while checking authentication
  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-maroon-600"></div>
      </div>
    );
  }

  // Redirect if user is not authenticated or authorized
  if (!user) {
    return null;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Mobile sidebar overlay */}
      {sidebarOpen && (
        <div className="fixed inset-0 flex z-40 md:hidden">
          <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={() => setSidebarOpen(false)} />
          <div className="relative flex-1 flex flex-col max-w-xs w-full bg-maroon-800">
            <div className="absolute top-0 right-0 -mr-12 pt-2">
              <button
                className="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                onClick={() => setSidebarOpen(false)}
              >
                <XIcon className="h-6 w-6 text-white" />
              </button>
            </div>
            <SidebarContent 
              navigationItems={navigationItems} 
              hasPermission={hasPermission} 
              currentPath={router.pathname}
              user={user}
              onLogout={handleLogout}
            />
          </div>
        </div>
      )}

      {/* Desktop sidebar */}
      <div className="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
        <div className="flex-1 flex flex-col min-h-0 bg-maroon-800">
          <SidebarContent 
            navigationItems={navigationItems} 
            hasPermission={hasPermission} 
            currentPath={router.pathname}
            user={user}
            onLogout={handleLogout}
          />
        </div>
      </div>

      {/* Main content */}
      <div className="md:pl-64 flex flex-col flex-1">
        {/* Top navigation */}
        <div className="sticky top-0 z-10 md:hidden pl-1 pt-1 sm:pl-3 sm:pt-3 bg-white shadow">
          <button
            className="-ml-0.5 -mt-0.5 h-12 w-12 inline-flex items-center justify-center rounded-md text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-maroon-500"
            onClick={() => setSidebarOpen(true)}
          >
            <MenuIcon className="h-6 w-6" />
          </button>
        </div>

        {/* Page header */}
        <div className="bg-white shadow">
          <div className="px-4 sm:px-6 lg:px-8">
            <div className="py-6">
              <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
              <p className="mt-1 text-sm text-gray-500">
                Welcome back, {user.name}
              </p>
            </div>
          </div>
        </div>

        {/* Main content area */}
        <main className="flex-1">
          <div className="py-6">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              {children}
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}

/**
 * Sidebar content component with navigation items and user info.
 */
interface SidebarContentProps {
  navigationItems: NavigationItem[];
  hasPermission: (permissions: string[]) => boolean;
  currentPath: string;
  user: User;
  onLogout: () => void;
}

function SidebarContent({ navigationItems, hasPermission, currentPath, user, onLogout }: SidebarContentProps) {
  return (
    <>
      {/* Logo and title */}
      <div className="flex items-center h-16 flex-shrink-0 px-4 bg-maroon-900">
        <div className="flex items-center">
          <div className="flex-shrink-0">
            <div className="h-8 w-8 bg-gold-400 rounded-full flex items-center justify-center">
              <span className="text-maroon-900 font-bold text-sm">AF</span>
            </div>
          </div>
          <div className="ml-3">
            <p className="text-white text-sm font-medium">AlFawz Admin</p>
          </div>
        </div>
      </div>

      {/* Navigation */}
      <div className="flex-1 flex flex-col overflow-y-auto">
        <nav className="flex-1 px-2 py-4 space-y-1">
          {navigationItems
            .filter(item => hasPermission(item.requiredPermissions))
            .map((item) => {
              const isActive = currentPath === item.href;
              return (
                <Link key={item.name} href={item.href}>
                  <a
                    className={`${
                      isActive
                        ? 'bg-maroon-900 text-white'
                        : 'text-maroon-100 hover:bg-maroon-700 hover:text-white'
                    } group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-150`}
                  >
                    <item.icon
                      className={`${
                        isActive ? 'text-white' : 'text-maroon-300 group-hover:text-white'
                      } mr-3 flex-shrink-0 h-5 w-5 transition-colors duration-150`}
                    />
                    {item.name}
                  </a>
                </Link>
              );
            })
          }
        </nav>
      </div>

      {/* User info and logout */}
      <div className="flex-shrink-0 flex border-t border-maroon-700 p-4">
        <div className="flex items-center w-full">
          <div className="flex-shrink-0">
            <div className="h-8 w-8 bg-gold-400 rounded-full flex items-center justify-center">
              <span className="text-maroon-900 font-medium text-xs">
                {user.name.charAt(0).toUpperCase()}
              </span>
            </div>
          </div>
          <div className="ml-3 flex-1">
            <p className="text-sm font-medium text-white truncate">{user.name}</p>
            <p className="text-xs text-maroon-300 capitalize">{user.role}</p>
          </div>
          <button
            onClick={onLogout}
            className="ml-3 flex-shrink-0 p-1 text-maroon-300 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-maroon-800 focus:ring-white transition-colors duration-150"
            title="Logout"
          >
            <LogoutIcon className="h-5 w-5" />
          </button>
        </div>
      </div>
    </>
  );
}