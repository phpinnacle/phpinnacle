<?php

namespace PHPinnacle\Cerber\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\User;

class LoginUserProvider extends EloquentUserProvider
{
    public function retrieveById(mixed $identifier): ?Authenticatable
    {
        /** @var User|null $user */
        $user = parent::retrieveById($identifier);

        return $user?->status->canAccess() ? $user : null;
    }

    public function retrieveByToken(mixed $identifier, #[\SensitiveParameter] mixed $token): ?Authenticatable
    {
        /** @var User|null $user */
        $user = parent::retrieveByToken($identifier, $token);

        return $user?->status->canAccess() ? $user : null;
    }

    /** @param array<string, mixed> $credentials */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        return $this->loginForCredentials($credentials)?->user;
    }

    /** @param array<string, mixed> $credentials */
    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        $login = $this->loginForCredentials($credentials);

        return (
            $login !== null
            && $login->user_id === $user->getAuthIdentifier()
            && $login->isAvailable()
            && parent::validateCredentials($user, $credentials)
        );
    }

    /** @param array<string, mixed> $credentials */
    private function loginForCredentials(#[\SensitiveParameter] array $credentials): ?Login
    {
        if (($credentials['username'] ?? null) !== null) {
            return Login::lookup($credentials['username']);
        }

        if (($credentials['email'] ?? null) !== null) {
            return Login::lookup($credentials['email'], LoginType::Email);
        }

        return null;
    }
}
