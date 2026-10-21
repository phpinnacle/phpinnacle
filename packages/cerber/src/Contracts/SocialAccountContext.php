<?php

namespace PHPinnacle\Cerber\Contracts;

use Laravel\Socialite\Contracts\User as SocialiteUser;
use PHPinnacle\Cerber\Models\User;

interface SocialAccountContext
{
    public function name(): string;

    public function guard(): string;

    public function resolve(SocialiteUser $user): User;
}
