<?php

namespace PHPinnacle\Cerber\Services;

use Laravel\Socialite\Contracts\User as SocialiteUser;
use PHPinnacle\Cerber\Contracts\SocialAccountContext;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\Provider;

class OAuthLogin
{
    public function findOrCreate(
        SocialAccountContext $context,
        Provider $provider,
        SocialiteUser $socialiteUser,
    ): Login {
        return new Login()
            ->getConnection()
            ->transaction(function () use ($context, $provider, $socialiteUser) {
                $account = Login::lookup(
                    \PHPinnacle\Cerber\Enums\LoginType::OAuth,
                    $socialiteUser->getId(),
                    $provider->id,
                );

                $user = $account !== null ? $account->user : $context->resolve($socialiteUser);

                return Login::linkOAuth($provider, $user, $socialiteUser);
            });
    }
}
