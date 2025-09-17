/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

'use client';

import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useTranslations } from 'next-intl';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { ScrollArea } from '@/components/ui/scroll-area';
import { 
  Bell, 
  Check, 
  X, 
  AlertCircle, 
  Info, 
  CheckCircle, 
  Clock,
  MoreHorizontal,
  Trash2,
  Eye
} from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';

// Types for notification data
interface Notification {
  id: string;
  type: 'info' | 'success' | 'warning' | 'error';
  title: string;
  message: string;
  isRead: boolean;
  createdAt: string;
  actionUrl?: string;
  metadata?: {
    studentName?: string;
    className?: string;
    submissionId?: string;
    assignmentTitle?: string;
  };
}

interface NotificationsResponse {
  notifications: Notification[];
  unreadCount: number;
}

/**
 * Fetch notifications from API
 * @returns Promise with notifications data
 */
const fetchNotifications = async (): Promise<NotificationsResponse> => {
  const response = await fetch('/api/teacher/notifications', {
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json',
    },
  });
  
  if (!response.ok) {
    throw new Error('Failed to fetch notifications');
  }
  
  return response.json();
};

/**
 * Mark notification as read
 * @param notificationId - ID of notification to mark as read
 */
const markAsRead = async (notificationId: string): Promise<void> => {
  const response = await fetch(`/api/teacher/notifications/${notificationId}/read`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json',
    },
  });
  
  if (!response.ok) {
    throw new Error('Failed to mark notification as read');
  }
};

/**
 * Delete notification
 * @param notificationId - ID of notification to delete
 */
const deleteNotification = async (notificationId: string): Promise<void> => {
  const response = await fetch(`/api/teacher/notifications/${notificationId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json',
    },
  });
  
  if (!response.ok) {
    throw new Error('Failed to delete notification');
  }
};

/**
 * Get notification icon based on type
 * @param type - Notification type
 * @returns Icon component
 */
function getNotificationIcon(type: Notification['type']) {
  switch (type) {
    case 'success':
      return CheckCircle;
    case 'warning':
      return AlertCircle;
    case 'error':
      return X;
    default:
      return Info;
  }
}

/**
 * Get notification color classes based on type
 * @param type - Notification type
 * @returns CSS classes for styling
 */
function getNotificationColors(type: Notification['type']) {
  switch (type) {
    case 'success':
      return {
        bg: 'bg-green-50 dark:bg-green-900/20',
        border: 'border-green-200 dark:border-green-800',
        icon: 'text-green-600 dark:text-green-400',
        badge: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
      };
    case 'warning':
      return {
        bg: 'bg-amber-50 dark:bg-amber-900/20',
        border: 'border-amber-200 dark:border-amber-800',
        icon: 'text-amber-600 dark:text-amber-400',
        badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
      };
    case 'error':
      return {
        bg: 'bg-red-50 dark:bg-red-900/20',
        border: 'border-red-200 dark:border-red-800',
        icon: 'text-red-600 dark:text-red-400',
        badge: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
      };
    default:
      return {
        bg: 'bg-blue-50 dark:bg-blue-900/20',
        border: 'border-blue-200 dark:border-blue-800',
        icon: 'text-blue-600 dark:text-blue-400',
        badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
      };
  }
}

/**
 * Individual notification item component
 * @param notification - Notification data
 * @param onMarkAsRead - Callback for marking as read
 * @param onDelete - Callback for deletion
 */
interface NotificationItemProps {
  notification: Notification;
  onMarkAsRead: (id: string) => void;
  onDelete: (id: string) => void;
}

function NotificationItem({ notification, onMarkAsRead, onDelete }: NotificationItemProps) {
  const [isHovered, setIsHovered] = useState(false);
  const colors = getNotificationColors(notification.type);
  const Icon = getNotificationIcon(notification.type);

  return (
    <motion.div
      layout
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, x: -100 }}
      whileHover={{ scale: 1.01 }}
      onHoverStart={() => setIsHovered(true)}
      onHoverEnd={() => setIsHovered(false)}
      className={`p-4 rounded-lg border transition-all duration-200 ${
        notification.isRead 
          ? 'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700' 
          : `${colors.bg} ${colors.border}`
      }`}
    >
      <div className="flex items-start space-x-3">
        {/* Icon */}
        <div className={`p-1 rounded-full ${colors.icon}`}>
          <Icon className="h-4 w-4" />
        </div>
        
        {/* Content */}
        <div className="flex-1 min-w-0">
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <h4 className={`text-sm font-medium ${
                notification.isRead 
                  ? 'text-gray-600 dark:text-gray-400' 
                  : 'text-gray-900 dark:text-white'
              }`}>
                {notification.title}
              </h4>
              <p className={`text-xs mt-1 ${
                notification.isRead 
                  ? 'text-gray-500 dark:text-gray-500' 
                  : 'text-gray-700 dark:text-gray-300'
              }`}>
                {notification.message}
              </p>
              
              {/* Metadata */}
              {notification.metadata && (
                <div className="flex flex-wrap gap-1 mt-2">
                  {notification.metadata.studentName && (
                    <Badge variant="outline" className="text-xs">
                      {notification.metadata.studentName}
                    </Badge>
                  )}
                  {notification.metadata.className && (
                    <Badge variant="outline" className="text-xs">
                      {notification.metadata.className}
                    </Badge>
                  )}
                </div>
              )}
            </div>
            
            {/* Unread indicator */}
            {!notification.isRead && (
              <div className="w-2 h-2 bg-blue-500 rounded-full ml-2 mt-1" />
            )}
          </div>
          
          {/* Footer */}
          <div className="flex items-center justify-between mt-3">
            <div className="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
              <Clock className="h-3 w-3" />
              <span>
                {formatDistanceToNow(new Date(notification.createdAt), { addSuffix: true })}
              </span>
            </div>
            
            {/* Actions */}
            <AnimatePresence>
              {isHovered && (
                <motion.div
                  initial={{ opacity: 0, scale: 0.8 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 0, scale: 0.8 }}
                  className="flex items-center space-x-1"
                >
                  {!notification.isRead && (
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => onMarkAsRead(notification.id)}
                      className="h-6 w-6 p-0 hover:bg-blue-100 dark:hover:bg-blue-900/20"
                    >
                      <Eye className="h-3 w-3" />
                    </Button>
                  )}
                  
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => onDelete(notification.id)}
                    className="h-6 w-6 p-0 hover:bg-red-100 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400"
                  >
                    <Trash2 className="h-3 w-3" />
                  </Button>
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        </div>
      </div>
    </motion.div>
  );
}

/**
 * Loading skeleton for notifications
 */
function NotificationsLoading() {
  return (
    <div className="space-y-4">
      {Array.from({ length: 3 }).map((_, index) => (
        <div key={index} className="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
          <div className="flex items-start space-x-3">
            <Skeleton className="h-6 w-6 rounded-full" />
            <div className="flex-1">
              <Skeleton className="h-4 w-3/4 mb-2" />
              <Skeleton className="h-3 w-full mb-2" />
              <Skeleton className="h-3 w-1/2" />
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}

/**
 * Empty state component
 */
function EmptyNotifications() {
  const t = useTranslations('teacher.notifications');
  
  return (
    <div className="text-center py-8">
      <div className="text-gray-400 dark:text-gray-600 mb-4">
        <Bell className="h-12 w-12 mx-auto" />
      </div>
      <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
        {t('empty.title', { defaultValue: 'No Notifications' })}
      </h3>
      <p className="text-gray-600 dark:text-gray-400 text-sm">
        {t('empty.message', { defaultValue: 'You\'re all caught up! New notifications will appear here.' })}
      </p>
    </div>
  );
}

/**
 * Main Notification Section Component
 * Displays and manages teacher notifications with real-time updates
 */
export default function NotificationSection() {
  const t = useTranslations('teacher.notifications');
  const queryClient = useQueryClient();
  
  const { data, isLoading, error } = useQuery({
    queryKey: ['teacher-notifications'],
    queryFn: fetchNotifications,
    refetchInterval: 30 * 1000, // Refetch every 30 seconds
    staleTime: 10 * 1000, // Consider data stale after 10 seconds
  });

  // Mark as read mutation
  const markAsReadMutation = useMutation({
    mutationFn: markAsRead,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teacher-notifications'] });
    },
  });

  // Delete notification mutation
  const deleteMutation = useMutation({
    mutationFn: deleteNotification,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teacher-notifications'] });
    },
  });

  const handleMarkAsRead = (notificationId: string) => {
    markAsReadMutation.mutate(notificationId);
  };

  const handleDelete = (notificationId: string) => {
    deleteMutation.mutate(notificationId);
  };

  const handleMarkAllAsRead = () => {
    if (data?.notifications) {
      const unreadNotifications = data.notifications.filter(n => !n.isRead);
      unreadNotifications.forEach(notification => {
        markAsReadMutation.mutate(notification.id);
      });
    }
  };

  if (isLoading) {
    return <NotificationsLoading />;
  }

  if (error) {
    return (
      <Card className="bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800">
        <CardContent className="p-6 text-center">
          <div className="text-red-600 dark:text-red-400 mb-2">
            <AlertCircle className="h-8 w-8 mx-auto" />
          </div>
          <h3 className="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
            {t('error.title', { defaultValue: 'Failed to Load Notifications' })}
          </h3>
          <p className="text-red-600 dark:text-red-400 text-sm">
            {(error as Error).message}
          </p>
        </CardContent>
      </Card>
    );
  }

  const notifications = data?.notifications || [];
  const unreadCount = data?.unreadCount || 0;

  if (notifications.length === 0) {
    return <EmptyNotifications />;
  }

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-2">
          <h3 className="text-sm font-medium text-gray-900 dark:text-white">
            {t('title', { defaultValue: 'Notifications' })}
          </h3>
          {unreadCount > 0 && (
            <Badge className="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
              {unreadCount} {t('unread', { defaultValue: 'unread' })}
            </Badge>
          )}
        </div>
        
        {unreadCount > 0 && (
          <Button
            variant="ghost"
            size="sm"
            onClick={handleMarkAllAsRead}
            className="text-xs"
            disabled={markAsReadMutation.isPending}
          >
            <Check className="h-3 w-3 mr-1" />
            {t('markAllRead', { defaultValue: 'Mark all read' })}
          </Button>
        )}
      </div>

      {/* Notifications List */}
      <ScrollArea className="h-96">
        <AnimatePresence>
          <div className="space-y-3">
            {notifications.map((notification) => (
              <NotificationItem
                key={notification.id}
                notification={notification}
                onMarkAsRead={handleMarkAsRead}
                onDelete={handleDelete}
              />
            ))}
          </div>
        </AnimatePresence>
      </ScrollArea>
    </div>
  );
}