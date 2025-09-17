/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

import React, { useState, useEffect, useCallback } from 'react';
import AdminLayout from '../../components/admin/AdminLayout';
import { 
  SearchIcon, 
  FilterIcon, 
  UserAddIcon,
  PencilIcon,
  BanIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/react/outline';

interface User {
  id: number;
  name: string;
  email: string;
  role: 'student' | 'teacher' | 'admin';
  primary_role?: string;
  level?: number | null;
  status: 'active' | 'inactive' | 'pending' | 'suspended' | 'new';
  last_login_at: string | null;
  created_at: string;
  classes_count?: number;
  submissions_count?: number;
}

interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

/**
 * Admin users management page with listing, filtering, and role management.
 * Provides comprehensive user administration functionality.
 */
export default function AdminUsersPage() {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [roleFilter, setRoleFilter] = useState<string>('all');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [actionLoading, setActionLoading] = useState<number | null>(null);

  /**
   * Fetch users from the API with filters and pagination.
   */
  const fetchUsers = useCallback(async (page: number = 1) => {
    try {
      setLoading(true);
      const token = localStorage.getItem('auth_token');

      const params = new URLSearchParams({
        page: page.toString(),
        search: searchTerm,
        role: roleFilter !== 'all' ? roleFilter : '',
        status: statusFilter !== 'all' ? statusFilter : '',
      });

      const response = await fetch(`${process.env.NEXT_PUBLIC_API_BASE}/admin/users?${params}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error('Failed to fetch users');
      }

      const data = await response.json() as any;
      const usersData: User[] = Array.isArray(data.data) ? data.data : [];
      const meta: PaginationMeta = data.meta ?? {
        current_page: data.current_page ?? 1,
        last_page: data.last_page ?? 1,
        per_page: data.per_page ?? usersData.length ?? 0,
        total: data.total ?? usersData.length ?? 0,
      };

      setUsers(usersData.map((item) => ({
        ...item,
        role: item.role ?? item.primary_role ?? 'student',
        status: item.status ?? 'inactive',
      })));
      setCurrentPage(meta.current_page);
      setTotalPages(meta.last_page);
    } catch (error) {
      console.error('Error fetching users:', error);
    } finally {
      setLoading(false);
    }
  }, [roleFilter, searchTerm, statusFilter]);

  /**
   * Update user role.
   * @param userId User ID to update
   * @param newRole New role to assign
   */
  const updateUserRole = async (userId: number, newRole: string) => {
    try {
      setActionLoading(userId);
      const token = localStorage.getItem('auth_token');

      const response = await fetch(`${process.env.NEXT_PUBLIC_API_BASE}/admin/users/${userId}/role`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ role: newRole })
      });

      if (!response.ok) {
        throw new Error('Failed to update user role');
      }

      // Refresh users list
      await fetchUsers(currentPage);
    } catch (error) {
      console.error('Error updating user role:', error);
      alert('Failed to update user role');
    } finally {
      setActionLoading(null);
    }
  };

  /**
   * Toggle user suspension status.
   * @param userId User ID to suspend/unsuspend
   * @param suspend Whether to suspend or unsuspend
   */
  const toggleUserSuspension = async (userId: number, suspend: boolean) => {
    try {
      setActionLoading(userId);
      const token = localStorage.getItem('auth_token');

      const response = await fetch(`${process.env.NEXT_PUBLIC_API_BASE}/admin/users/${userId}/suspend`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ suspend })
      });

      if (!response.ok) {
        throw new Error('Failed to update user status');
      }

      // Refresh users list
      await fetchUsers(currentPage);
    } catch (error) {
      console.error('Error updating user status:', error);
      alert('Failed to update user status');
    } finally {
      setActionLoading(null);
    }
  };

  /**
   * Format date for display.
   * @param dateString ISO date string
   * @returns Formatted date string
   */
  const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  /**
   * Get status badge styling.
   * @param status User status
   * @returns CSS classes for status badge
   */
  const getStatusBadge = (status: string): string => {
    switch (status) {
      case 'active':
        return 'bg-green-100 text-green-800';
      case 'suspended':
        return 'bg-red-100 text-red-800';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      case 'inactive':
        return 'bg-gray-200 text-gray-700';
      case 'new':
        return 'bg-blue-100 text-blue-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  /**
   * Get role badge styling.
   * @param role User role
   * @returns CSS classes for role badge
   */
  const getRoleBadge = (role: string): string => {
    switch (role) {
      case 'admin':
        return 'bg-maroon-100 text-maroon-800';
      case 'teacher':
        return 'bg-blue-100 text-blue-800';
      case 'student':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  // Fetch users on component mount and when filters change
  useEffect(() => {
    fetchUsers(1);
  }, [fetchUsers]);

  return (
    <AdminLayout title="User Management">
      <div className="space-y-6">
        {/* Header with search and filters */}
        <div className="bg-white shadow rounded-lg p-6">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
            <div className="flex-1 max-w-lg">
              <div className="relative">
                <SearchIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search users by name or email..."
                  className="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md focus:ring-maroon-500 focus:border-maroon-500"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
            </div>
            
            <div className="flex space-x-4">
              <select
                className="border border-gray-300 rounded-md px-3 py-2 focus:ring-maroon-500 focus:border-maroon-500"
                value={roleFilter}
                onChange={(e) => setRoleFilter(e.target.value)}
              >
                <option value="all">All Roles</option>
                <option value="student">Students</option>
                <option value="teacher">Teachers</option>
                <option value="admin">Admins</option>
              </select>
              
              <select
                className="border border-gray-300 rounded-md px-3 py-2 focus:ring-maroon-500 focus:border-maroon-500"
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
              >
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
                <option value="pending">Pending</option>
                <option value="new">New</option>
              </select>
            </div>
          </div>
        </div>

        {/* Users table */}
        <div className="bg-white shadow rounded-lg overflow-hidden">
          {loading ? (
            <div className="p-8 text-center">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-maroon-600 mx-auto"></div>
              <p className="mt-2 text-gray-500">Loading users...</p>
            </div>
          ) : (
            <>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        User
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Role
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Last Login
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Stats
                      </th>
                      <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {users.map((user) => (
                      <tr key={user.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="flex items-center">
                            <div className="flex-shrink-0 h-10 w-10">
                              <div className="h-10 w-10 rounded-full bg-maroon-100 flex items-center justify-center">
                                <span className="text-sm font-medium text-maroon-700">
                                  {user.name.charAt(0).toUpperCase()}
                                </span>
                              </div>
                            </div>
                            <div className="ml-4">
                              <div className="text-sm font-medium text-gray-900">{user.name}</div>
                              <div className="text-sm text-gray-500">{user.email}</div>
                            </div>
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getRoleBadge(user.role)}`}>
                            {user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                          </span>
                          {user.level && (
                            <div className="text-xs text-gray-500 mt-1">Level {user.level}</div>
                          )}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusBadge(user.status)}`}>
                            {user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {formatDate(user.last_login_at)}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          <div className="space-y-1">
                            {user.classes_count !== undefined && (
                              <div>Classes: {user.classes_count}</div>
                            )}
                            {user.submissions_count !== undefined && (
                              <div>Submissions: {user.submissions_count}</div>
                            )}
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                          <div className="flex items-center justify-end space-x-2">
                            {/* Role change dropdown */}
                            <select
                              className="text-xs border border-gray-300 rounded px-2 py-1 focus:ring-maroon-500 focus:border-maroon-500"
                              value={user.role ?? 'student'}
                              onChange={(e) => updateUserRole(user.id, e.target.value)}
                              disabled={actionLoading === user.id}
                            >
                              <option value="student">Student</option>
                              <option value="teacher">Teacher</option>
                              <option value="admin">Admin</option>
                            </select>
                            
                            {/* Suspend/Unsuspend button */}
                            <button
                              onClick={() => toggleUserSuspension(user.id, user.status !== 'suspended')}
                              disabled={actionLoading === user.id}
                              className={`inline-flex items-center px-2 py-1 text-xs font-medium rounded ${
                                user.status === 'suspended'
                                  ? 'text-green-700 bg-green-100 hover:bg-green-200'
                                  : 'text-red-700 bg-red-100 hover:bg-red-200'
                              } transition-colors duration-150 disabled:opacity-50`}
                            >
                              {actionLoading === user.id ? (
                                <div className="animate-spin rounded-full h-3 w-3 border-b border-current"></div>
                              ) : user.status === 'suspended' ? (
                                <>
                                  <CheckCircleIcon className="h-3 w-3 mr-1" />
                                  Activate
                                </>
                              ) : (
                                <>
                                  <BanIcon className="h-3 w-3 mr-1" />
                                  Suspend
                                </>
                              )}
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {/* Pagination */}
              {totalPages > 1 && (
                <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                  <div className="flex-1 flex justify-between sm:hidden">
                    <button
                      onClick={() => fetchUsers(currentPage - 1)}
                      disabled={currentPage === 1}
                      className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                    >
                      Previous
                    </button>
                    <button
                      onClick={() => fetchUsers(currentPage + 1)}
                      disabled={currentPage === totalPages}
                      className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                    >
                      Next
                    </button>
                  </div>
                  <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                      <p className="text-sm text-gray-700">
                        Page <span className="font-medium">{currentPage}</span> of{' '}
                        <span className="font-medium">{totalPages}</span>
                      </p>
                    </div>
                    <div>
                      <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <button
                          onClick={() => fetchUsers(currentPage - 1)}
                          disabled={currentPage === 1}
                          className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                        >
                          Previous
                        </button>
                        <button
                          onClick={() => fetchUsers(currentPage + 1)}
                          disabled={currentPage === totalPages}
                          className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                        >
                          Next
                        </button>
                      </nav>
                    </div>
                  </div>
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </AdminLayout>
  );
}