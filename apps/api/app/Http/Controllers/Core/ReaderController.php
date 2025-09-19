<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\ReaderState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ReaderController extends Controller
{
    public function getState(Request $request): JsonResponse
    {
        $user = $request->user();

        $state = ReaderState::firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_surah' => 1,
                'current_ayah' => 1,
                'font_size' => 'medium',
                'translation_enabled' => false,
                'audio_enabled' => false,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $state,
        ]);
    }

    public function saveState(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_surah' => 'required|integer|min:1|max:114',
            'current_ayah' => 'required|integer|min:1',
            'font_size' => 'sometimes|in:small,medium,large',
            'translation_enabled' => 'sometimes|boolean',
            'audio_enabled' => 'sometimes|boolean',
            'reciter_id' => 'nullable|integer|min:1',
        ]);

        $user = $request->user();

        $state = ReaderState::updateOrCreate(
            ['user_id' => $user->id],
            array_merge([
                'font_size' => 'medium',
                'translation_enabled' => false,
                'audio_enabled' => false,
            ], $validated)
        );

        return response()->json([
            'success' => true,
            'message' => 'Reader state saved successfully',
            'data' => $state,
        ]);
    }

    public function getReciters(Request $request): JsonResponse
    {
        $language = $request->query('language', 'en');
        $cacheKey = "reader:reciters:{$language}";

        $reciters = Cache::remember($cacheKey, now()->addDays(7), function () use ($language) {
            $baseUrl = rtrim(config('services.quran.base', ''), '/');

            if (!$baseUrl) {
                return $this->defaultReciters();
            }

            try {
                $timeout = (int) config('services.quran.timeout', 30);
                $response = Http::timeout($timeout)->get("{$baseUrl}/resources/recitations", [
                    'language' => $language,
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return $data['data'] ?? $data;
                }
            } catch (\Throwable $exception) {
                // fall through to default list
            }

            return $this->defaultReciters();
        });

        return response()->json([
            'success' => true,
            'data' => $reciters,
        ]);
    }

    private function defaultReciters(): array
    {
        return [
            [
                'id' => 7,
                'name' => 'Mishary Rashid Alafasy',
                'style' => 'Murattal',
                'translated_name' => 'Mishary Alafasy',
            ],
            [
                'id' => 3,
                'name' => 'Abdul Basit Abdul Samad',
                'style' => 'Mujawwad',
                'translated_name' => 'Abdul Basit',
            ],
            [
                'id' => 1,
                'name' => 'Maher Al Mueaqly',
                'style' => 'Murattal',
                'translated_name' => 'Maher Al Mueaqly',
            ],
        ];
    }
}
