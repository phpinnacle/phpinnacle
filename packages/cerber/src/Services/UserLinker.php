<?php

namespace PHPinnacle\Cerber\Services;

use Laravel\Socialite\Contracts\User as SocialiteUser;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Models\SocialAccount;
use PHPinnacle\Cerber\Models\User;

class UserLinker
{
    public function findOrCreateUser(
        SocialAccountContext $context,
        Provider $provider,
        SocialiteUser $socialiteUser,
    ): User {
        return new SocialAccount()
            ->getConnection()
            ->transaction(function () use ($context, $provider, $socialiteUser) {
                $account = SocialAccount::find($provider, $socialiteUser->getId());

                if ($account !== null) {
                    return $account->user;
                }

                $user = $context->resolve($socialiteUser);
                SocialAccount::link($provider, $user, $socialiteUser);

                return $user;
            });
    }
}
