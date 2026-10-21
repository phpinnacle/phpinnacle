<?php

namespace PHPinnacle\Cerber\OAuth;

readonly class SessionAttempt
{
    public function __construct(
        public string $verifier,
        public ?string $linkUserId = null,
        public bool $test = false,
    ) {}
}
