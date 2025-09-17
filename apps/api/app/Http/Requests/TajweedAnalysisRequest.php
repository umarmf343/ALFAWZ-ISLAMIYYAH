<?php
/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Requests;

use App\Models\OrgSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TajweedAnalysisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('student');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxDuration = OrgSetting::getMaxAudioDuration();
        $maxFileSize = min(50 * 1024, $maxDuration * 32); // Rough estimate: 32KB per second

        return [
            'audio' => [
                'required',
                'file',
                'mimes:mp3,wav,m4a,ogg,webm',
                "max:{$maxFileSize}", // Size in KB
            ],
            'surah_id' => [
                'required',
                'integer',
                'min:1',
                'max:114', // Total number of surahs in Quran
            ],
            'ayah_from' => [
                'required',
                'integer',
                'min:1',
            ],
            'ayah_to' => [
                'required',
                'integer',
                'gte:ayah_from', // ayah_to must be >= ayah_from
            ],
            'expected_tokens' => [
                'required',
                'array',
                'min:1',
            ],
            'expected_tokens.*' => [
                'required',
                'string',
                'max:100',
            ],
            'duration_seconds' => [
                'required',
                'numeric',
                'min:1',
                "max:{$maxDuration}",
            ],
            'assignment_id' => [
                'nullable',
                'integer',
                'exists:assignments,id',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        $maxDuration = OrgSetting::getMaxAudioDuration();
        
        return [
            'audio.required' => 'Audio file is required for Tajweed analysis.',
            'audio.file' => 'The audio must be a valid file.',
            'audio.mimes' => 'Audio must be in MP3, WAV, M4A, OGG, or WebM format.',
            'audio.max' => 'Audio file size is too large.',
            
            'surah_id.required' => 'Surah selection is required.',
            'surah_id.min' => 'Invalid surah selection.',
            'surah_id.max' => 'Invalid surah selection. There are only 114 surahs in the Quran.',
            
            'ayah_from.required' => 'Starting ayah number is required.',
            'ayah_from.min' => 'Starting ayah must be at least 1.',
            
            'ayah_to.required' => 'Ending ayah number is required.',
            'ayah_to.gte' => 'Ending ayah must be greater than or equal to starting ayah.',
            
            'expected_tokens.required' => 'Expected Arabic text tokens are required for analysis.',
            'expected_tokens.array' => 'Expected tokens must be provided as an array.',
            'expected_tokens.min' => 'At least one token is required.',
            'expected_tokens.*.required' => 'Each token must have a value.',
            'expected_tokens.*.string' => 'Each token must be a string.',
            'expected_tokens.*.max' => 'Each token must not exceed 100 characters.',
            
            'duration_seconds.required' => 'Audio duration is required.',
            'duration_seconds.numeric' => 'Duration must be a number.',
            'duration_seconds.min' => 'Audio must be at least 1 second long.',
            'duration_seconds.max' => "Audio duration cannot exceed {$maxDuration} seconds.",
            
            'assignment_id.integer' => 'Assignment ID must be a valid number.',
            'assignment_id.exists' => 'The selected assignment does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'audio' => 'audio file',
            'surah_id' => 'surah',
            'ayah_from' => 'starting ayah',
            'ayah_to' => 'ending ayah',
            'expected_tokens' => 'expected Arabic text',
            'duration_seconds' => 'audio duration',
            'assignment_id' => 'assignment',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate ayah range for the selected surah
            if ($this->surah_id && $this->ayah_from && $this->ayah_to) {
                $maxAyahsInSurah = $this->getMaxAyahsForSurah($this->surah_id);
                
                if ($this->ayah_from > $maxAyahsInSurah) {
                    $validator->errors()->add('ayah_from', "Starting ayah cannot exceed {$maxAyahsInSurah} for this surah.");
                }
                
                if ($this->ayah_to > $maxAyahsInSurah) {
                    $validator->errors()->add('ayah_to', "Ending ayah cannot exceed {$maxAyahsInSurah} for this surah.");
                }
            }
            
            // Validate expected tokens count vs ayah range
            if ($this->expected_tokens && $this->ayah_from && $this->ayah_to) {
                $ayahCount = $this->ayah_to - $this->ayah_from + 1;
                $tokenCount = count($this->expected_tokens);
                
                // Rough validation: should have reasonable number of tokens per ayah
                $minTokens = $ayahCount * 3; // At least 3 tokens per ayah
                $maxTokens = $ayahCount * 50; // At most 50 tokens per ayah
                
                if ($tokenCount < $minTokens) {
                    $validator->errors()->add('expected_tokens', "Too few tokens provided. Expected at least {$minTokens} tokens for {$ayahCount} ayah(s).");
                }
                
                if ($tokenCount > $maxTokens) {
                    $validator->errors()->add('expected_tokens', "Too many tokens provided. Expected at most {$maxTokens} tokens for {$ayahCount} ayah(s).");
                }
            }
        });
    }

    /**
     * Get the maximum number of ayahs for a given surah.
     * This is a simplified version - in production, you'd fetch from Quran API or database.
     */
    private function getMaxAyahsForSurah(int $surahId): int
    {
        // Simplified ayah counts for each surah (1-114)
        $ayahCounts = [
            1 => 7, 2 => 286, 3 => 200, 4 => 176, 5 => 120, 6 => 165, 7 => 206, 8 => 75, 9 => 129, 10 => 109,
            11 => 123, 12 => 111, 13 => 43, 14 => 52, 15 => 99, 16 => 128, 17 => 111, 18 => 110, 19 => 98, 20 => 135,
            21 => 112, 22 => 78, 23 => 118, 24 => 64, 25 => 77, 26 => 227, 27 => 93, 28 => 88, 29 => 69, 30 => 60,
            31 => 34, 32 => 30, 33 => 73, 34 => 54, 35 => 45, 36 => 83, 37 => 182, 38 => 88, 39 => 75, 40 => 85,
            41 => 54, 42 => 53, 43 => 89, 44 => 59, 45 => 37, 46 => 35, 47 => 38, 48 => 29, 49 => 18, 50 => 45,
            51 => 60, 52 => 49, 53 => 62, 54 => 55, 55 => 78, 56 => 96, 57 => 29, 58 => 22, 59 => 24, 60 => 13,
            61 => 14, 62 => 11, 63 => 11, 64 => 18, 65 => 12, 66 => 12, 67 => 30, 68 => 52, 69 => 52, 70 => 44,
            71 => 28, 72 => 28, 73 => 20, 74 => 56, 75 => 40, 76 => 31, 77 => 50, 78 => 40, 79 => 46, 80 => 42,
            81 => 29, 82 => 19, 83 => 36, 84 => 25, 85 => 22, 86 => 17, 87 => 19, 88 => 26, 89 => 30, 90 => 20,
            91 => 15, 92 => 21, 93 => 11, 94 => 8, 95 => 8, 96 => 19, 97 => 5, 98 => 8, 99 => 8, 100 => 11,
            101 => 11, 102 => 8, 103 => 3, 104 => 9, 105 => 5, 106 => 4, 107 => 7, 108 => 3, 109 => 6, 110 => 3,
            111 => 5, 112 => 4, 113 => 5, 114 => 6
        ];
        
        return $ayahCounts[$surahId] ?? 1;
    }
}