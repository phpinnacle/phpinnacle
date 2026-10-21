<?php

namespace PHPinnacle\Cerber\OAuth;

readonly class OAuthCallback
{
    public function __construct(
        public string $state,
        public string $verifier,
        public string $code,
    ) {}
}
