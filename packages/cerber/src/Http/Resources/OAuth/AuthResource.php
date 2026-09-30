<?php

namespace PHPinnacle\Cerber\Http\Resources\OAuth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPinnacle\Cerber\Models\User;

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
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'status' => $user->status,
            ],
        ];
    }
}
