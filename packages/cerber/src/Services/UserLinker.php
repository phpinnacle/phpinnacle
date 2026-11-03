<?php

namespace PHPinnacle\Cerber\Services;

use Laravel\Socialite\Contracts\User as SocialiteUser;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;
use PHPinnacle\Cerber\Exceptions\OAuthException;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Models\User;

class UserLinker
{
    public function findOrCreateUser(
        SocialAccountContext $context,
        Provider $provider,
        SocialiteUser $socialiteUser,
    ): User {
        $login = new Login()
            ->getConnection()
            ->transaction(function () use ($context, $provider, $socialiteUser) {
                $account = Login::lookup(
                    \PHPinnacle\Cerber\Enums\LoginType::OAuth,
                    $socialiteUser->getId(),
                    $provider->id,
                );

                $user = $account !== null ? $account->user : $context->resolve($socialiteUser);

                return $provider->link($user, $socialiteUser);
            });

        if (!$login->isAvailable()) {
            throw new OAuthException('access_denied', 'The user cannot sign in.');
        }

        return $login->user;
    }
}
