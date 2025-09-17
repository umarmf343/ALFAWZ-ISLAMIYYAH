<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get paginated notifications for the authenticated user.
     *
     * @param Request $request HTTP request with optional pagination params
     * @return JsonResponse JSON response with notifications and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = min((int) $request->query('per_page', 15), 50);
        $unreadOnly = $request->boolean('unread_only', false);
        
        $query = $user->notifications()->orderBy('created_at', 'desc');
        
        if ($unreadOnly) {
            $query->unread();
        }
        
        $notifications = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'has_more' => $notifications->hasMorePages()
                ],
                'unread_count' => $this->notificationService->getUnreadCount($user)
            ]
        ]);
    }
    
    /**
     * Get unread notification count for the authenticated user.
     *
     * @return JsonResponse JSON response with unread count
     */
    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();
        $count = $this->notificationService->getUnreadCount($user);
        
        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }

    /**
     * Mark specific notifications as read.
     *
     * @param Request $request HTTP request with notification IDs
     * @return JsonResponse JSON response with success status
     * @throws ValidationException
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => 'required|array|min:1',
            'notification_ids.*' => 'required|string|exists:notifications,id'
        ]);
        
        $user = Auth::user();
        $notificationIds = $request->input('notification_ids');
        
        $markedCount = $this->notificationService->markAsRead($user, $notificationIds);
        
        return response()->json([
            'success' => true,
            'data' => [
                'marked_count' => $markedCount,
                'unread_count' => $this->notificationService->getUnreadCount($user)
            ]
        ]);
    }
    
    /**
     * Mark all notifications as read for the authenticated user.
     *
     * @return JsonResponse JSON response with success status
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        $markedCount = $this->notificationService->markAllAsRead($user);
        
        return response()->json([
            'success' => true,
            'data' => [
                'marked_count' => $markedCount,
                'unread_count' => 0
            ]
        ]);
    }

    /**
     * Get a specific notification by ID.
     *
     * @param string $id Notification UUID
     * @return JsonResponse JSON response with notification data
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->findOrFail($id);
        
        // Mark as read if not already read
        if (!$notification->isRead()) {
            $notification->markAsRead();
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'notification' => $notification->toFrontendArray()
            ]
        ]);
    }
    
    /**
     * Delete a specific notification.
     *
     * @param string $id Notification UUID
     * @return JsonResponse JSON response with success status
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();
        
        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Notification deleted successfully',
                'unread_count' => $this->notificationService->getUnreadCount($user)
            ]
        ]);
    }

    /**
     * Delete all read notifications for the authenticated user.
     *
     * @return JsonResponse JSON response with success status
     */
    public function deleteAllRead(): JsonResponse
    {
        $user = Auth::user();
        
        $deletedCount = $user->notifications()
            ->whereNotNull('read_at')
            ->delete();
        
        return response()->json([
            'success' => true,
            'data' => [
                'deleted_count' => $deletedCount,
                'message' => 'All read notifications deleted successfully',
                'unread_count' => $this->notificationService->getUnreadCount($user)
            ]
        ]);
    }
    
    /**
     * Update notification preferences for the authenticated user.
     *
     * @param Request $request HTTP request with preference updates
     * @return JsonResponse JSON response with updated preferences
     * @throws ValidationException
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean'
        ]);
        
        $user = Auth::user();
        
        // Update user's notification preferences
        $user->update([
            'email_notifications' => $request->input('email_notifications', $user->email_notifications),
            'push_notifications' => $request->input('push_notifications', $user->push_notifications)
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Notification preferences updated successfully',
                'preferences' => [
                    'email_notifications' => $user->email_notifications,
                    'push_notifications' => $user->push_notifications
                ]
            ]
        ]);
    }
}