<?php

namespace PHPinnacle\Cerber\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Spec as OA;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Enums\UserStatus;
use PHPinnacle\Cerber\Models\User;

#[OA\Schema(
    component: 'CerberUserResource',
    properties: [
        'id' => new OA\Schema(
            description: 'User UUID',
            type: 'string',
            format: 'uuid',
            example: '550e8400-e29b-41d4-a716-446655440000',
        ),
        'name' => new OA\Schema(
            description: 'Display name',
            type: 'string',
            example: 'johndoe',
        ),
        'email' => new OA\Schema(
            description: 'Default email login address',
            nullable: true,
            type: 'string',
            format: 'email',
            example: 'user@example.com',
        ),
        'status' => new OA\Schema(
            type: 'string',
            enum: [UserStatus::class],
        ),
        'email_verified_at' => new OA\Schema(
            description: 'Default email login verification time',
            type: 'string',
            format: 'date-time',
            example: null,
            nullable: true,
        ),
        'created_at' => new OA\Schema(
            description: 'Registration time',
            type: 'string',
            format: 'date-time',
            example: '2024-01-01T00:00:00.000000Z',
        ),
    ],
    type: 'object',
)]
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $email = $user->defaultLogin(LoginType::Email);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'status' => $user->status,
            'email' => $email?->value,
            'email_verified_at' => $email?->verified_at,
            'created_at' => $user->created_at,
        ];
    }
}
