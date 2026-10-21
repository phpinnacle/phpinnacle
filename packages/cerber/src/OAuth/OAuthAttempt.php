<?php

namespace PHPinnacle\Cerber\OAuth;

readonly class OAuthAttempt
{
    public function __construct(
        public string $context,
        public string $provider,
        public string $callbackUrl,
        public string $challenge,
    ) {}

    public function matches(string $context, string $provider, string $callbackUrl, string $verifier): bool
    {
        return (
            $this->context === $context
            && $this->provider === $provider
            && $this->callbackUrl === $callbackUrl
            && hash_equals($this->challenge, self::challenge($verifier))
        );
    }

    public static function challenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), from: '+/', to: '-_'), '=');
    }
}
