<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    /**
     * Display a listing of classes for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = ClassModel::with(['teacher', 'members']);

        if ($user->isTeacher()) {
            // Teachers see their own classes
            $query->where('teacher_id', $user->id);
        } elseif ($user->isStudent()) {
            // Students see classes they're members of
            $query->whereHas('members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } else {
            // Admins see all classes
            // No additional filtering needed
        }

        // Apply filters
        if ($request->has('level')) {
            $query->byLevel($request->integer('level'));
        }

        if ($request->has('teacher_id')) {
            $query->byTeacher($request->integer('teacher_id'));
        }

        $classes = $query->latest()->paginate(15);

        return response()->json($classes);
    }

    /**
     * Store a newly created class.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isTeacher() && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Only teachers and admins can create classes'
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'level' => ['required', 'integer', 'min:1', 'max:3'],
        ]);

        $class = ClassModel::create([
            'teacher_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'],
        ]);

        $class->load(['teacher', 'members']);

        return response()->json([
            'message' => 'Class created successfully',
            'class' => $class,
        ], 201);
    }

    /**
     * Display the specified class.
     *
     * @param ClassModel $class
     * @return JsonResponse
     */
    public function show(ClassModel $class): JsonResponse
    {
        $user = request()->user();

        // Check if user has access to this class
        if (!$user->isAdmin() && 
            !$class->isTeacher($user) && 
            !$class->isMember($user)) {
            return response()->json([
                'message' => 'Access denied'
            ], 403);
        }

        $class->load([
            'teacher',
            'members' => function ($query) {
                $query->orderBy('role_in_class')->orderBy('name');
            },
            'assignments' => function ($query) {
                $query->latest()->limit(5);
            }
        ]);

        return response()->json([
            'class' => $class,
            'stats' => [
                'total_members' => $class->getMemberCount(),
                'total_students' => $class->getStudentCount(),
                'total_assignments' => $class->assignments()->count(),
            ]
        ]);
    }

    /**
     * Update the specified class.
     *
     * @param Request $request
     * @param ClassModel $class
     * @return JsonResponse
     */
    public function update(Request $request, ClassModel $class): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin() && !$class->isTeacher($user)) {
            return response()->json([
                'message' => 'Only the class teacher or admin can update this class'
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'level' => ['sometimes', 'integer', 'min:1', 'max:3'],
        ]);

        $class->update($validated);
        $class->load(['teacher', 'members']);

        return response()->json([
            'message' => 'Class updated successfully',
            'class' => $class,
        ]);
    }

    /**
     * Remove the specified class.
     *
     * @param ClassModel $class
     * @return JsonResponse
     */
    public function destroy(ClassModel $class): JsonResponse
    {
        $user = request()->user();

        if (!$user->isAdmin() && !$class->isTeacher($user)) {
            return response()->json([
                'message' => 'Only the class teacher or admin can delete this class'
            ], 403);
        }

        $class->delete();

        return response()->json([
            'message' => 'Class deleted successfully'
        ]);
    }

    /**
     * Add a member to the class.
     *
     * @param Request $request
     * @param ClassModel $class
     * @return JsonResponse
     */
    public function addMember(Request $request, ClassModel $class): JsonResponse
    {
        $user = $request->user();

        if (!$user->isAdmin() && !$class->isTeacher($user)) {
            return response()->json([
                'message' => 'Only the class teacher or admin can add members'
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_in_class' => ['required', Rule::in(['student', 'assistant'])],
        ]);

        $memberUser = User::find($validated['user_id']);

        // Check if user is already a member
        if ($class->isMember($memberUser)) {
            return response()->json([
                'message' => 'User is already a member of this class'
            ], 422);
        }

        // Add the member
        $class->addMember($memberUser, $validated['role_in_class']);

        return response()->json([
            'message' => 'Member added successfully',
            'member' => $memberUser->makeHidden(['password']),
        ]);
    }

    /**
     * Remove a member from the class.
     *
     * @param ClassModel $class
     * @param User $user
     * @return JsonResponse
     */
    public function removeMember(ClassModel $class, User $user): JsonResponse
    {
        $authUser = request()->user();

        if (!$authUser->isAdmin() && !$class->isTeacher($authUser)) {
            return response()->json([
                'message' => 'Only the class teacher or admin can remove members'
            ], 403);
        }

        // Check if user is a member
        if (!$class->isMember($user)) {
            return response()->json([
                'message' => 'User is not a member of this class'
            ], 422);
        }

        // Remove the member
        $class->removeMember($user);

        return response()->json([
            'message' => 'Member removed successfully'
        ]);
    }

    /**
     * Get class members with their roles.
     *
     * @param ClassModel $class
     * @return JsonResponse
     */
    public function getMembers(ClassModel $class): JsonResponse
    {
        $user = request()->user();

        if (!$user->isAdmin() && 
            !$class->isTeacher($user) && 
            !$class->isMember($user)) {
            return response()->json([
                'message' => 'Access denied'
            ], 403);
        }

        $members = $class->members()
            ->withPivot('role_in_class', 'created_at')
            ->orderBy('pivot_role_in_class')
            ->orderBy('name')
            ->get()
            ->makeHidden(['password']);

        return response()->json([
            'members' => $members,
            'stats' => [
                'total_members' => $members->count(),
                'students' => $members->where('pivot.role_in_class', 'student')->count(),
                'assistants' => $members->where('pivot.role_in_class', 'assistant')->count(),
            ]
        ]);
    }
}