<?php

namespace App\DataTransferObjects\Shared;

use JsonSerializable;

class ApiResponse implements JsonSerializable
{
    public function __construct(
        public bool $success = true,
        public ?string $message = null,
        public mixed $data = null,
        public ?array $errors = null,
    ) {}

    public static function message(string $message, bool $success = true, ?array $data = null): self
    {
        return new self($success, $message, $data);
    }

    public static function data(array $data, ?string $message = null, bool $success = true): self
    {
        return new self($success, $message, $data);
    }

    public static function error(string $message, array $errors = null): self
    {
        return new self(false, $message, null, $errors);
    }

    public function jsonSerialize(): array
    {
        $payload = [
            'success' => $this->success,
        ];

        if ($this->message !== null) {
            $payload['message'] = $this->message;
        }

        if ($this->data !== null) {
            $payload['data'] = $this->data;
        }

        if ($this->errors !== null) {
            $payload['errors'] = $this->errors;
        }

        return $payload;
    }
}
