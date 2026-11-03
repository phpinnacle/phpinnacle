<?php

namespace PHPinnacle\Cerber\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Models\User;

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
