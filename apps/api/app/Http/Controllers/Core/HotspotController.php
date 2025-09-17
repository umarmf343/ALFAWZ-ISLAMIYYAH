<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Hotspot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class HotspotController extends Controller
{
    /**
     * Display a listing of hotspots for a specific assignment.
     *
     * @param Assignment $assignment The assignment to get hotspots for
     * @return JsonResponse JSON response with hotspots data
     */
    public function index(Assignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        $hotspots = $assignment->hotspots()
            ->orderBy('order')
            ->get()
            ->map(function ($hotspot) {
                return $hotspot->toFrontendArray();
            });

        return response()->json([
            'success' => true,
            'data' => $hotspots,
            'meta' => [
                'assignment_id' => $assignment->id,
                'total_hotspots' => $hotspots->count()
            ]
        ]);
    }

    /**
     * Store a newly created hotspot for an assignment.
     *
     * @param Request $request HTTP request with hotspot data
     * @param Assignment $assignment The assignment to add hotspot to
     * @return JsonResponse JSON response with created hotspot
     */
    public function store(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('update', $assignment);

        $validated = $request->validate([
            'x_position' => 'required|numeric|min:0|max:100',
            'y_position' => 'required|numeric|min:0|max:100',
            'content' => 'required|string|max:1000',
            'audio_url' => 'nullable|url|max:500',
            'tooltip_text' => 'nullable|string|max:200',
            'interaction_type' => ['required', Rule::in(['click', 'hover', 'auto'])],
            'style_config' => 'nullable|array',
            'animation_type' => ['nullable', Rule::in(['pulse', 'bounce', 'fade', 'none'])],
            'is_required' => 'boolean',
            'order' => 'nullable|integer|min:0'
        ]);

        // Set order if not provided
        if (!isset($validated['order'])) {
            $validated['order'] = $assignment->hotspots()->max('order') + 1;
        }

        $validated['assignment_id'] = $assignment->id;
        $validated['created_by'] = Auth::id();

        $hotspot = Hotspot::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Hotspot created successfully',
            'data' => $hotspot->toFrontendArray()
        ], 201);
    }

    /**
     * Display the specified hotspot.
     *
     * @param Hotspot $hotspot The hotspot to display
     * @return JsonResponse JSON response with hotspot data
     */
    public function show(Hotspot $hotspot): JsonResponse
    {
        $this->authorize('view', $hotspot->assignment);

        return response()->json([
            'success' => true,
            'data' => $hotspot->toFrontendArray()
        ]);
    }

    /**
     * Update the specified hotspot.
     *
     * @param Request $request HTTP request with updated hotspot data
     * @param Hotspot $hotspot The hotspot to update
     * @return JsonResponse JSON response with updated hotspot
     */
    public function update(Request $request, Hotspot $hotspot): JsonResponse
    {
        $this->authorize('update', $hotspot->assignment);

        $validated = $request->validate([
            'x_position' => 'sometimes|numeric|min:0|max:100',
            'y_position' => 'sometimes|numeric|min:0|max:100',
            'content' => 'sometimes|string|max:1000',
            'audio_url' => 'nullable|url|max:500',
            'tooltip_text' => 'nullable|string|max:200',
            'interaction_type' => ['sometimes', Rule::in(['click', 'hover', 'auto'])],
            'style_config' => 'nullable|array',
            'animation_type' => ['nullable', Rule::in(['pulse', 'bounce', 'fade', 'none'])],
            'is_required' => 'boolean',
            'order' => 'sometimes|integer|min:0'
        ]);

        $hotspot->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Hotspot updated successfully',
            'data' => $hotspot->fresh()->toFrontendArray()
        ]);
    }

    /**
     * Remove the specified hotspot from storage.
     *
     * @param Hotspot $hotspot The hotspot to delete
     * @return JsonResponse JSON response confirming deletion
     */
    public function destroy(Hotspot $hotspot): JsonResponse
    {
        $this->authorize('update', $hotspot->assignment);

        // Delete associated audio file if exists
        if ($hotspot->audio_url && Storage::exists($hotspot->audio_url)) {
            Storage::delete($hotspot->audio_url);
        }

        $hotspot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hotspot deleted successfully'
        ]);
    }

    /**
     * Get hotspot interactions for current user.
     *
     * @param Hotspot $hotspot
     * @return JsonResponse
     */
    public function getInteractions(Hotspot $hotspot): JsonResponse
    {
        $this->authorize('view', $hotspot->assignment);

        $interactions = $hotspot->interactions()
            ->where('user_id', auth()->id())
            ->orderBy('timestamp', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'interactions' => $interactions->map(fn($interaction) => $interaction->toFrontendArray()),
                'pagination' => [
                    'current_page' => $interactions->currentPage(),
                    'last_page' => $interactions->lastPage(),
                    'per_page' => $interactions->perPage(),
                    'total' => $interactions->total()
                ]
            ]
        ]);
    }

    /**
     * Get hotspot interaction statistics.
     *
     * @param Hotspot $hotspot
     * @return JsonResponse
     */
    public function getStats(Hotspot $hotspot): JsonResponse
    {
        $this->authorize('view', $hotspot->assignment);

        $stats = [
            'total_interactions' => $hotspot->interactions()->count(),
            'unique_users' => $hotspot->interactions()->distinct('user_id')->count(),
            'avg_completion' => $hotspot->interactions()
                ->whereNotNull('completion_percentage')
                ->avg('completion_percentage'),
            'total_duration' => $hotspot->interactions()
                ->whereNotNull('duration_seconds')
                ->sum('duration_seconds'),
            'interaction_types' => $hotspot->interactions()
                ->selectRaw('interaction_type, COUNT(*) as count')
                ->groupBy('interaction_type')
                ->pluck('count', 'interaction_type')
                ->toArray(),
            'recent_activity' => $hotspot->interactions()
                ->with('user:id,name')
                ->orderBy('timestamp', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($interaction) => $interaction->toFrontendArray())
        ];

        return response()->json([
            'success' => true,
            'data' => ['stats' => $stats]
        ]);
    }

    /**
     * Record interaction with a hotspot (for analytics).
     *
     * @param Request $request HTTP request with interaction data
     * @param Hotspot $hotspot The hotspot that was interacted with
     * @return JsonResponse JSON response confirming interaction recorded
     */
    public function recordInteraction(Request $request, Hotspot $hotspot): JsonResponse
    {
        $this->authorize('view', $hotspot->assignment);

        $validated = $request->validate([
            'interaction_type' => ['required', Rule::in(['view', 'click', 'audio_play'])],
            'duration_seconds' => 'nullable|integer|min:0'
        ]);

        $hotspot->recordInteraction(
            Auth::id(),
            $validated['interaction_type'],
            $validated['duration_seconds'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Interaction recorded successfully'
        ]);
    }

    /**
     * Reorder hotspots for an assignment.
     *
     * @param Request $request HTTP request with new order data
     * @param Assignment $assignment The assignment to reorder hotspots for
     * @return JsonResponse JSON response confirming reorder
     */
    public function reorder(Request $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('update', $assignment);

        $validated = $request->validate([
            'hotspot_orders' => 'required|array',
            'hotspot_orders.*.id' => 'required|exists:hotspots,id',
            'hotspot_orders.*.order' => 'required|integer|min:0'
        ]);

        foreach ($validated['hotspot_orders'] as $orderData) {
            $hotspot = Hotspot::find($orderData['id']);
            if ($hotspot && $hotspot->assignment_id === $assignment->id) {
                $hotspot->update(['order' => $orderData['order']]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Hotspots reordered successfully'
        ]);
    }

    /**
     * Upload audio file for a hotspot.
     *
     * @param Request $request HTTP request with audio file
     * @param Hotspot $hotspot The hotspot to add audio to
     * @return JsonResponse JSON response with audio URL
     */
    public function uploadAudio(Request $request, Hotspot $hotspot): JsonResponse
    {
        $this->authorize('update', $hotspot->assignment);

        $validated = $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a,ogg|max:10240' // 10MB max
        ]);

        // Delete old audio file if exists
        if ($hotspot->audio_url && Storage::exists($hotspot->audio_url)) {
            Storage::delete($hotspot->audio_url);
        }

        $audioPath = $validated['audio']->store('hotspots/audio', 'public');
        $audioUrl = Storage::url($audioPath);

        $hotspot->update(['audio_url' => $audioUrl]);

        return response()->json([
            'success' => true,
            'message' => 'Audio uploaded successfully',
            'data' => [
                'audio_url' => $audioUrl,
                'hotspot' => $hotspot->fresh()->toFrontendArray()
            ]
        ]);
    }
}