<?php

namespace App\DataTransferObjects\Auth;

class RegisterRequestData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $role = 'student',
        public ?string $phone = null,
        public int $level = 1,
    ) {}

    public static function fromArray(array $validated, string $defaultRole = 'student'): self
    {
        return new self(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            role: $defaultRole,
            phone: $validated['phone'] ?? null,
            level: (int) ($validated['level'] ?? 1),
        );
    }

    public function toUserAttributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
            'level' => $this->level,
        ];
    }
}
