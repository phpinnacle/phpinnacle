<?php

namespace PHPinnacle\Cerber\Services;

use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Two\User as OAuthTwoUser;
use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Exceptions;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\SocialAccount;
use PHPinnacle\Cerber\Models\User;

class UserLinker
{
    public function __construct(
        private ProviderRegistry $providers,
        #[Config('phpinnacle-cerber.allowed_domains', [])]
        public array $domains,
        #[Config('phpinnacle-cerber.default_role')]
        public ?string $defaultRole,
    ) {}

    public function findOrCreateUser(Provider $provider): User
    {
        $socialiteUser = $this->providers->build($provider->type, $provider->config)->user();
        $socialAccount = SocialAccount::find($provider, $socialiteUser->getId());

        if ($socialAccount !== null) {
            return $socialAccount->user;
        }

        $email = $socialiteUser->getEmail();

        if ($email === null || $email === '') {
            throw new Exceptions\EmailNotProvided($provider);
        }

        $user = User::find($email);

        if ($user !== null) {
            return $user;
        }

        if (!$this->isAllowedDomain($email)) {
            throw new Exceptions\DomainNotAllowed($email);
        }

        return $this->createUser($socialiteUser);
    }

    public function linkAccount(Provider $provider, Authenticatable $user): SocialAccount
    {
        $socialiteUser = $this->providers->build($provider->type, $provider->config)->user();

        $values = [
            'external_id' => $socialiteUser->getId(),
            'email' => $socialiteUser->getEmail(),
            'profile' => [
                'name' => $socialiteUser->getName(),
                'nickname' => $socialiteUser->getNickname(),
                'avatar' => $socialiteUser->getAvatar(),
            ],
        ];

        if ($socialiteUser instanceof OAuthTwoUser) {
            $values = array_merge($values, [
                'access_token' => $socialiteUser->token,
                'refresh_token' => $socialiteUser->refreshToken,
                'token_expires_at' => $socialiteUser->expiresIn
                    ? now()->addSeconds($socialiteUser->expiresIn)
                    : null,
            ]);
        }

        return SocialAccount::query()->updateOrCreate([
            'user_id' => $user->getAuthIdentifier(),
            'provider_id' => $provider->getKey(),
        ], $values);
    }

    private function createUser(SocialiteUser $socialiteUser): User
    {
        $user = new User([
            'email' => $socialiteUser->getEmail(),
            'name' => $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? 'User',
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(32)),
            'password_changed_at' => now(),
        ]);
        $user->save();

        if ($this->defaultRole !== null) {
            $role = Role::query()
                ->where('tenant_id', Cerberus::tenant())
                ->where('name', $this->defaultRole)
                ->where('is_active', true)
                ->first();

            if ($role) {
                $user->roles()->attach($role);
            }
        }

        return $user;
    }

    private function isAllowedDomain(string $email): bool
    {
        return in_array(Str::after($email, '@'), $this->domains, true);
    }
}
