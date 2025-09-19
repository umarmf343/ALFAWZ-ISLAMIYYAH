<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Teacher private channel authentication.
 * Only the teacher themselves can listen to their private channel.
 */
Broadcast::channel('teacher.{teacherId}', function ($user, $teacherId) {
    return (int) $user->id === (int) $teacherId && $user->role === 'teacher';
});

/**
 * Student private channel authentication.
 * Only the student themselves can listen to their private channel.
 */
Broadcast::channel('student.{studentId}', function ($user, $studentId) {
    return (int) $user->id === (int) $studentId && $user->role === 'student';
});

/**
 * Admin private channel authentication.
 * Only the admin themselves can listen to their private channel.
 */
Broadcast::channel('admin.{adminId}', function ($user, $adminId) {
    return (int) $user->id === (int) $adminId && $user->role === 'admin';
});

/**
 * Class-specific channel authentication.
 * Only teachers and students enrolled in the class can listen.
 */
Broadcast::channel('class.{classId}', function ($user, $classId) {
    // Check if user is teacher of the class or enrolled student
    if ($user->role === 'teacher') {
        return $user->teachingClasses()->where('id', $classId)->exists();
    }

    if ($user->role === 'student') {
        return $user->studentClasses()->where('id', $classId)->exists();
    }

    return false;
});

/**
 * Assignment-specific channel authentication.
 * Only teachers who created the assignment and enrolled students can listen.
 */
Broadcast::channel('assignment.{assignmentId}', function ($user, $assignmentId) {
    if ($user->role === 'teacher') {
        return $user->createdAssignments()->where('id', $assignmentId)->exists();
    }

    if ($user->role === 'student') {
        // Check if student is enrolled in a class that has this assignment
        return $user->studentClasses()
            ->whereHas('assignments', function ($query) use ($assignmentId) {
                $query->where('id', $assignmentId);
            })->exists();
    }
    
    return false;
});