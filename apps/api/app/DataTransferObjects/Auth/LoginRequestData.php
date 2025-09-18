<?php

namespace App\DataTransferObjects\Auth;

class LoginRequestData
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function fromArray(array $validated): self
    {
        return new self(
            email: $validated['email'],
            password: $validated['password'],
        );
    }
}
