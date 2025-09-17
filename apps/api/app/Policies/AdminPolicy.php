<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AdminPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can access admin dashboard.
     *
     * @param User $user
     * @return Response|bool
     */
    public function viewDashboard(User $user): Response|bool
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('You must be an admin to access the dashboard.');
    }

    /**
     * Determine if the user can manage other users.
     *
     * @param User $user
     * @return Response|bool
     */
    public function manageUsers(User $user): Response|bool
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('You do not have permission to manage users.');
    }

    /**
     * Determine if the user can assign roles to other users.
     *
     * @param User $user
     * @param User $targetUser
     * @return Response|bool
     */
    public function assignRoles(User $user, User $targetUser): Response|bool
    {
        if (!$user->hasRole('admin')) {
            return Response::deny('You do not have permission to assign roles.');
        }

        // Prevent admins from modifying their own roles
        if ($user->id === $targetUser->id) {
            return Response::deny('You cannot modify your own roles.');
        }

        return Response::allow();
    }

    /**
     * Determine if the user can view analytics data.
     *
     * @param User $user
     * @return Response|bool
     */
    public function viewAnalytics(User $user): Response|bool
    {
        return $user->hasRole(['admin', 'teacher'])
            ? Response::allow()
            : Response::deny('You do not have permission to view analytics.');
    }

    /**
     * Determine if the user can manage classes.
     *
     * @param User $user
     * @return Response|bool
     */
    public function manageClasses(User $user): Response|bool
    {
        return $user->hasRole(['admin', 'teacher'])
            ? Response::allow()
            : Response::deny('You do not have permission to manage classes.');
    }

    /**
     * Determine if the user can moderate content.
     *
     * @param User $user
     * @return Response|bool
     */
    public function moderateContent(User $user): Response|bool
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('You do not have permission to moderate content.');
    }

    /**
     * Determine if the user can manage payments and billing.
     *
     * @param User $user
     * @return Response|bool
     */
    public function managePayments(User $user): Response|bool
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('You do not have permission to manage payments.');
    }

    /**
     * Determine if the user can access system settings.
     *
     * @param User $user
     * @return Response|bool
     */
    public function manageSettings(User $user): Response|bool
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('You do not have permission to manage system settings.');
    }

    /**
     * Determine if the user can impersonate other users.
     *
     * @param User $user
     * @param User $targetUser
     * @return Response|bool
     */
    public function impersonateUser(User $user, User $targetUser): Response|bool
    {
        if (!$user->hasRole('admin')) {
            return Response::deny('You do not have permission to impersonate users.');
        }

        // Prevent impersonating other admins
        if ($targetUser->hasRole('admin')) {
            return Response::deny('You cannot impersonate other administrators.');
        }

        // Prevent self-impersonation
        if ($user->id === $targetUser->id) {
            return Response::deny('You cannot impersonate yourself.');
        }

        return Response::allow();
    }

    /**
     * Determine if the user can perform bulk operations.
     *
     * @param User $user
     * @return Response|bool
     */
    public function performBulkOperations(User $user): Response|bool
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('You do not have permission to perform bulk operations.');
    }

    /**
     * Determine if the user can access audit logs.
     *
     * @param User $user
     * @return Response|bool
     */
    public function viewAuditLogs(User $user): Response|bool
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('You do not have permission to view audit logs.');
    }

    /**
     * Determine if the user can manage system tools and utilities.
     *
     * @param User $user
     * @return Response|bool
     */
    public function useSystemTools(User $user): Response|bool
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('You do not have permission to use system tools.');
    }

    /**
     * Determine if the user can export data.
     *
     * @param User $user
     * @return Response|bool
     */
    public function exportData(User $user): Response|bool
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('You do not have permission to export data.');
    }

    /**
     * Determine if the user can manage organization settings.
     *
     * @param User $user
     * @return Response|bool
     */
    public function manageOrganization(User $user): Response|bool
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('You do not have permission to manage organization settings.');
    }
}