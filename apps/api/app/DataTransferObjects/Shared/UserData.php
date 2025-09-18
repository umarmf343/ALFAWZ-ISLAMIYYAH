<?php

namespace App\DataTransferObjects\Shared;

use App\Models\User;
use Illuminate\Support\Arr;
use JsonSerializable;

class UserData implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $primaryRole,
        public ?string $phone = null,
        public ?int $level = null,
        public ?string $avatarUrl = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public array $roles = [],
        public array $permissions = [],
        public array $meta = [],
    ) {}

    public static function fromModel(User $user, bool $includeAccess = true): self
    {
        $roles = $includeAccess ? $user->getRoleNames()->toArray() : [];
        $permissions = $includeAccess ? $user->getAllPermissions()->pluck('name')->toArray() : [];

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            primaryRole: $roles[0] ?? $user->role ?? null,
            phone: $user->phone,
            level: Arr::get($user->toArray(), 'level'),
            avatarUrl: $user->profile_picture_url ?? null,
            createdAt: optional($user->created_at)->toISOString(),
            updatedAt: optional($user->updated_at)->toISOString(),
            roles: $roles,
            permissions: $permissions,
            meta: [
                'status' => $user->status ?? null,
                'has_verified_email' => (bool) $user->email_verified_at,
            ],
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->primaryRole,
            'phone' => $this->phone,
            'level' => $this->level,
            'avatar_url' => $this->avatarUrl,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'roles' => $this->roles,
            'permissions' => $this->permissions,
            'meta' => $this->meta,
        ], fn ($value) => $value !== null && $value !== []);
    }
}
