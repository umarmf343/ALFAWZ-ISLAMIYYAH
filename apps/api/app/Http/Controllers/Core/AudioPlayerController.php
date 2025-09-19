<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\AudioProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * Provides Quran audio lesson metadata and tracks student listening progress.
 */
class AudioPlayerController extends Controller
{
    /**
     * Return the curated surah audio list for the player.
     */
    public function surahs(Request $request): JsonResponse
    {
        $surahs = Config::get('quran_audio.surahs', []);

        return response()->json([
            'data' => [
                'surahs' => $surahs,
            ],
        ]);
    }

    /**
     * Return all saved progress entries for the authenticated student.
     */
    public function index(Request $request): JsonResponse
    {
        $progress = AudioProgress::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'data' => [
                'progress' => $progress,
            ],
        ]);
    }

    /**
     * Retrieve the saved progress for a specific surah.
     */
    public function show(Request $request, int $surahId): JsonResponse
    {
        $progress = AudioProgress::query()
            ->where('user_id', $request->user()->id)
            ->where('surah_id', $surahId)
            ->first();

        return response()->json([
            'data' => $progress,
        ]);
    }

    /**
     * Persist or update the student\'s current listening position.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'surah_id' => ['required', 'integer'],
            'position_seconds' => ['required', 'numeric', 'min:0'],
            'duration_seconds' => ['nullable', 'numeric', 'min:0'],
        ]);

        $surahs = collect(Config::get('quran_audio.surahs', []));
        $surah = $surahs->firstWhere('id', $validated['surah_id']);

        $surahName = $surah['name'] ?? $request->input('surah_name');
        if (!$surahName) {
            $request->validate([
                'surah_name' => ['required', 'string', 'max:255'],
            ]);
            $surahName = $request->input('surah_name');
        }

        $progress = AudioProgress::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'surah_id' => $validated['surah_id'],
            ],
            [
                'surah_name' => $surahName,
                'position_seconds' => $validated['position_seconds'],
                'duration_seconds' => $validated['duration_seconds'] ?? null,
            ]
        );

        return response()->json([
            'data' => $progress,
            'message' => 'Progress saved successfully.',
        ]);
    }
}
