<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('teacher');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'class_id' => [
                'nullable',
                'integer',
                Rule::exists('classes', 'id')->where(function ($query) {
                    return $query->where('teacher_id', $this->user()->id);
                })
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'], // 10MB max
            'due_at' => ['nullable', 'date', 'after:now'],
            'targets' => ['nullable', 'array'],
            'targets.*' => ['integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'in:draft,published'],
            
            // New fields for enhanced assignment wizard
            'pages' => ['nullable', 'array'],
            'pages.*' => ['required', 'array'],
            'pages.*.id' => ['required', 'string'],
            'pages.*.image_url' => ['required', 'string', 'url'],
            'pages.*.hotspots' => ['nullable', 'array'],
            
            'hotspots' => ['nullable', 'array'],
            'hotspots.*' => ['required', 'array'],
            'hotspots.*.id' => ['required', 'string'],
            'hotspots.*.x' => ['required', 'numeric', 'min:0'],
            'hotspots.*.y' => ['required', 'numeric', 'min:0'],
            'hotspots.*.width' => ['required', 'numeric', 'min:1'],
            'hotspots.*.height' => ['required', 'numeric', 'min:1'],
            'hotspots.*.title' => ['nullable', 'string', 'max:255'],
            'hotspots.*.tooltip' => ['nullable', 'string', 'max:1000'],
            'hotspots.*.audio_url' => ['nullable', 'string', 'url'],
            'hotspots.*.page_id' => ['nullable', 'string'],
            
            'rubric' => ['nullable', 'array'],
            'rubric.tajweed' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rubric.fluency' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rubric.memory' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rubric.pronunciation' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rubric.overall_notes' => ['nullable', 'string', 'max:2000'],
            
            'current_page_index' => ['nullable', 'integer', 'min:0']
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
            'class_id.exists' => 'You can only create assignments for classes you teach.',
            'title.required' => 'Assignment title is required.',
            'title.max' => 'Assignment title cannot exceed 255 characters.',
            'description.max' => 'Assignment description cannot exceed 2000 characters.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'Image must be a JPEG, PNG, JPG, or GIF file.',
            'image.max' => 'Image size cannot exceed 10MB.',
            'due_at.date' => 'Due date must be a valid date.',
            'due_at.after' => 'Due date must be in the future.',
            'targets.array' => 'Targets must be an array of user IDs.',
            'targets.*.integer' => 'Each target must be a valid user ID.',
            'targets.*.exists' => 'One or more target users do not exist.',
            'status.in' => 'Status must be either draft or published.',
            
            // New field messages
            'pages.array' => 'Pages must be an array.',
            'pages.*.image_url.required' => 'Each page must have an image URL.',
            'pages.*.image_url.url' => 'Page image URL must be a valid URL.',
            
            'hotspots.array' => 'Hotspots must be an array.',
            'hotspots.*.x.required' => 'Hotspot X coordinate is required.',
            'hotspots.*.y.required' => 'Hotspot Y coordinate is required.',
            'hotspots.*.width.required' => 'Hotspot width is required.',
            'hotspots.*.height.required' => 'Hotspot height is required.',
            'hotspots.*.x.numeric' => 'Hotspot X coordinate must be a number.',
            'hotspots.*.y.numeric' => 'Hotspot Y coordinate must be a number.',
            'hotspots.*.width.numeric' => 'Hotspot width must be a number.',
            'hotspots.*.height.numeric' => 'Hotspot height must be a number.',
            'hotspots.*.title.max' => 'Hotspot title cannot exceed 255 characters.',
            'hotspots.*.tooltip.max' => 'Hotspot tooltip cannot exceed 1000 characters.',
            'hotspots.*.audio_url.url' => 'Hotspot audio URL must be a valid URL.',
            
            'rubric.tajweed.numeric' => 'Tajweed score must be a number.',
            'rubric.fluency.numeric' => 'Fluency score must be a number.',
            'rubric.memory.numeric' => 'Memory score must be a number.',
            'rubric.pronunciation.numeric' => 'Pronunciation score must be a number.',
            'rubric.tajweed.max' => 'Tajweed score cannot exceed 100.',
            'rubric.fluency.max' => 'Fluency score cannot exceed 100.',
            'rubric.memory.max' => 'Memory score cannot exceed 100.',
            'rubric.pronunciation.max' => 'Pronunciation score cannot exceed 100.',
            'rubric.overall_notes.max' => 'Overall notes cannot exceed 2000 characters.',
            
            'current_page_index.integer' => 'Current page index must be an integer.',
            'current_page_index.min' => 'Current page index cannot be negative.'
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
            'class_id' => 'class',
            'title' => 'assignment title',
            'description' => 'assignment description',
            'image' => 'assignment image',
            'due_at' => 'due date',
            'targets' => 'target students',
            'status' => 'assignment status'
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
            // If class_id is provided, targets should be empty (class-wide assignment)
            // If targets are provided, class_id should be empty (individual assignment)
            if ($this->filled('class_id') && $this->filled('targets')) {
                $validator->errors()->add('targets', 'Cannot specify both class and individual targets.');
            }

            // At least one of class_id or targets must be provided
            if (!$this->filled('class_id') && !$this->filled('targets')) {
                $validator->errors()->add('class_id', 'Must specify either a class or individual targets.');
            }

            // If targets are provided, validate they are students
            if ($this->filled('targets')) {
                $targets = $this->input('targets', []);
                $students = \App\Models\User::whereIn('id', $targets)
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'student');
                    })
                    ->pluck('id')
                    ->toArray();

                $nonStudents = array_diff($targets, $students);
                if (!empty($nonStudents)) {
                    $validator->errors()->add('targets', 'All targets must be students.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Set default status to draft if not provided
        if (!$this->has('status')) {
            $this->merge([
                'status' => 'draft'
            ]);
        }

        // Convert empty strings to null for optional fields
        $this->merge([
            'class_id' => $this->input('class_id') ?: null,
            'description' => $this->input('description') ?: null,
            'due_at' => $this->input('due_at') ?: null,
            'targets' => $this->input('targets') ?: null
        ]);
    }
}