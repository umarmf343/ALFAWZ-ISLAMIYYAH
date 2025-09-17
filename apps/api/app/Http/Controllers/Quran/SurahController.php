<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Controllers\Quran;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SurahController extends Controller
{
    /**
     * Get specific surah details by ID
     *
     * @param Request $request HTTP request
     * @param int $id Surah ID (1-114)
     * @return \Illuminate\Http\JsonResponse Surah details
     */
    public function show(Request $request, $id)
    {
        $language = $request->query('language', 'en');
        $cacheKey = "quran:surah:{$id}:{$language}";

        $surah = Cache::remember($cacheKey, now()->addHours(24), function () use ($id, $language) {
            try {
                $baseUrl = config('services.quran.base_url');
                $response = Http::timeout(30)->get("{$baseUrl}/chapters/{$id}", [
                    'language' => $language
                ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Quran API surah request failed', [
                    'surah_id' => $id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Quran API surah connection failed', [
                    'surah_id' => $id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });

        if (!$surah) {
            return response()->json([
                'error' => 'Unable to fetch surah data',
                'message' => 'Please try again later'
            ], 503);
        }

        return response()->json($surah);
    }

    /**
     * Get list of all surahs from Quran API
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse List of surahs
     */
    public function index(Request $request)
    {
        $language = $request->query('language', 'en');
        $cacheKey = "quran:surahs:list:{$language}";

        $surahs = Cache::remember($cacheKey, now()->addHours(24), function () use ($language) {
            try {
                $baseUrl = config('services.quran.base_url');
                $response = Http::timeout(30)->get("{$baseUrl}/chapters", [
                    'language' => $language
                ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Quran API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Quran API connection failed', [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });

        if (!$surahs) {
            return response()->json([
                'error' => 'Unable to fetch surahs data',
                'message' => 'Please try again later'
            ], 503);
        }

        return response()->json($surahs);
    }

    /**
     * Get ayahs (verses) for a specific surah
     *
     * @param Request $request HTTP request
     * @param int $id Surah ID (1-114)
     * @return \Illuminate\Http\JsonResponse Ayahs data
     */
    public function ayahs(Request $request, $id)
    {
        $language = $request->query('language', 'en');
        $translation = $request->query('translation', '131'); // Default English translation
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 50);
        
        $cacheKey = "quran:surah:{$id}:ayahs:{$language}:{$translation}:page:{$page}";

        $ayahs = Cache::remember($cacheKey, now()->addHours(12), function () use ($id, $language, $translation, $page, $perPage) {
            try {
                $baseUrl = config('services.quran.base_url');
                $response = Http::timeout(30)->get("{$baseUrl}/verses/by_chapter/{$id}", [
                    'language' => $language,
                    'translations' => $translation,
                    'page' => $page,
                    'per_page' => $perPage,
                    'words' => 'true',
                    'word_fields' => 'verse_key,word_key,code_v1,text_uthmani'
                ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Quran API ayahs request failed', [
                    'surah_id' => $id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Quran API ayahs connection failed', [
                    'surah_id' => $id,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });

        if (!$ayahs) {
            return response()->json([
                'error' => 'Unable to fetch ayahs data',
                'message' => 'Please try again later'
            ], 503);
        }

        return response()->json($ayahs);
    }

    /**
     * Get a specific ayah from a surah
     *
     * @param Request $request HTTP request
     * @param int $surahId Surah ID (1-114)
     * @param int $ayahId Ayah ID within the surah
     * @return \Illuminate\Http\JsonResponse Ayah data
     */
    public function getAyah(Request $request, $surahId, $ayahId)
    {
        $language = $request->query('language', 'en');
        $translation = $request->query('translation', '131'); // Default English translation
        $cacheKey = "quran:ayah:{$surahId}:{$ayahId}:{$language}:{$translation}";

        $ayah = Cache::remember($cacheKey, now()->addHours(12), function () use ($surahId, $ayahId, $language, $translation) {
            try {
                $baseUrl = config('services.quran.base_url');
                $verseKey = "{$surahId}:{$ayahId}";
                $response = Http::timeout(30)->get("{$baseUrl}/verses/by_key/{$verseKey}", [
                    'language' => $language,
                    'translations' => $translation,
                    'words' => 'true',
                    'word_fields' => 'verse_key,word_key,code_v1,text_uthmani'
                ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Quran API ayah request failed', [
                    'surah_id' => $surahId,
                    'ayah_id' => $ayahId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Quran API ayah connection failed', [
                    'surah_id' => $surahId,
                    'ayah_id' => $ayahId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });

        if (!$ayah) {
            return response()->json([
                'error' => 'Unable to fetch ayah data',
                'message' => 'Please try again later'
            ], 503);
        }

        return response()->json($ayah);
    }

    /**
     * Get available recitations/reciters
     *
     * @param Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse List of reciters
     */
    public function recitations(Request $request)
    {
        $language = $request->query('language', 'en');
        $cacheKey = "quran:recitations:{$language}";

        $recitations = Cache::remember($cacheKey, now()->addDays(7), function () use ($language) {
            try {
                $baseUrl = config('services.quran.base_url');
                $response = Http::timeout(30)->get("{$baseUrl}/resources/recitations", [
                    'language' => $language
                ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning('Quran API recitations request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Quran API recitations connection failed', [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });

        if (!$recitations) {
            return response()->json([
                'error' => 'Unable to fetch recitations data',
                'message' => 'Please try again later'
            ], 503);
        }

        return response()->json($recitations);
    }
}