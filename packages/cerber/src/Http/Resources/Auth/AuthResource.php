<?php

namespace PHPinnacle\Cerber\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Spec as OA;
use PHPinnacle\Cerber\Enums\Prerequisite;
use PHPinnacle\Cerber\Models\User;

#[OA\Schema(
    component: 'CerberAuthResource',
    properties: [
        'access_token' => new OA\Schema(
            description: 'JWT bearer token',
            type: 'string',
            example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...',
        ),
        'token_type' => new OA\Schema(
            description: 'Token type',
            type: 'string',
            example: 'bearer',
        ),
        'expires_in' => new OA\Schema(
            description: 'Token lifetime in seconds',
            type: 'integer',
            example: 3600,
        ),
        'prerequisites' => new OA\Schema(
            description: 'Contains password when is_password_temporary is set. This marker does not block API access or clear automatically.',
            type: 'array',
            items: new OA\Schema(type: 'string', enum: [Prerequisite::class]),
        ),
        'user' => new OA\Schema(
            description: 'User profile',
            allOf: [new OA\Schema(ref: '#/components/schemas/CerberUserResource')],
        ),
    ],
    type: 'object',
)]
class AuthResource extends JsonResource
{
    public function __construct(
        #[\SensitiveParameter]
        private readonly string $token,
        User $user,
    ) {
        parent::__construct($user);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'access_token' => $this->token,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl') * 60,
            'prerequisites' => array_map(
                fn (Prerequisite $prerequisite) => $prerequisite->value,
                $user->prerequisites(),
            ),
            'user' => new UserResource($user),
        ];
    }
}
