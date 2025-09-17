<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $assignment = $this->route('assignment');

        // Only students can create submissions
        if (!$user->hasRole('student')) {
            return false;
        }

        // Check if assignment is published
        if ($assignment->status !== 'published') {
            return false;
        }

        // Check if assignment is targeted to this user
        if (!$assignment->isTargetedTo($user)) {
            return false;
        }

        // Check if user already has a submission for this assignment
        $existingSubmission = $assignment->submissions()
            ->where('student_id', $user->id)
            ->first();

        return !$existingSubmission;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'mimes:mp3,wav,m4a,aac', 'max:51200'], // 50MB max
            'notes' => ['nullable', 'string', 'max:1000']
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'audio.required' => 'Audio recording is required for submission.',
            'audio.file' => 'Audio must be a valid file.',
            'audio.mimes' => 'Audio must be in MP3, WAV, M4A, or AAC format.',
            'audio.max' => 'Audio file size cannot exceed 50MB.',
            'notes.max' => 'Notes cannot exceed 1000 characters.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'audio' => 'audio recording',
            'notes' => 'submission notes'
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $assignment = $this->route('assignment');

            // Check if assignment is overdue
            if ($assignment->due_at && $assignment->due_at->isPast()) {
                $validator->errors()->add('assignment', 'This assignment is overdue and no longer accepts submissions.');
            }

            // Validate audio file duration if needed (optional)
            if ($this->hasFile('audio')) {
                $audioFile = $this->file('audio');
                
                // Basic file validation - you might want to add duration checks here
                if ($audioFile->getSize() < 1024) { // Less than 1KB
                    $validator->errors()->add('audio', 'Audio file appears to be too small or corrupted.');
                }
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function failedAuthorization(): void
    {
        $user = $this->user();
        $assignment = $this->route('assignment');

        if (!$user->hasRole('student')) {
            abort(403, 'Only students can submit assignments.');
        }

        if ($assignment->status !== 'published') {
            abort(403, 'This assignment is not yet published.');
        }

        if (!$assignment->isTargetedTo($user)) {
            abort(403, 'This assignment is not assigned to you.');
        }

        $existingSubmission = $assignment->submissions()
            ->where('student_id', $user->id)
            ->first();

        if ($existingSubmission) {
            abort(403, 'You have already submitted this assignment.');
        }

        abort(403, 'You are not authorized to submit this assignment.');
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Convert empty notes to null
        if ($this->has('notes') && empty(trim($this->input('notes')))) {
            $this->merge([
                'notes' => null
            ]);
        }
    }
}