<?php

/* AlFawz Qur'an Institute — generated with TRAE */
/* Author: Auto-scaffold (review required) */

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'in:student,teacher'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:50']
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
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Please select your role (student or teacher).',
            'role.in' => 'Role must be either student or teacher.',
            'date_of_birth.date' => 'Please provide a valid date of birth.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'gender.in' => 'Gender must be either male or female.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'country.max' => 'Country name cannot exceed 100 characters.',
            'timezone.max' => 'Timezone cannot exceed 50 characters.'
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
            'name' => 'full name',
            'email' => 'email address',
            'password' => 'password',
            'role' => 'user role',
            'phone' => 'phone number',
            'date_of_birth' => 'date of birth',
            'gender' => 'gender',
            'country' => 'country',
            'timezone' => 'timezone'
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Normalize email to lowercase
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower($this->email)
            ]);
        }

        // Normalize role to lowercase
        if ($this->has('role')) {
            $this->merge([
                'role' => strtolower($this->role)
            ]);
        }

        // Normalize gender to lowercase
        if ($this->has('gender')) {
            $this->merge([
                'gender' => strtolower($this->gender)
            ]);
        }
    }
}