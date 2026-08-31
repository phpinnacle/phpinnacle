<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\Authorization\AuthenticateRequest;
use PHPinnacle\Langcat\Request\Authorization\RefreshTokenRequest;
use PHPinnacle\Langcat\Response\Authorization\MeResponse;
use PHPinnacle\Langcat\Response\Authorization\TokenResponse;
use PHPinnacle\Langcat\Support\Transport;

final readonly class AuthorizationApi
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function authenticate(AuthenticateRequest $request): TokenResponse
    {
        return TokenResponse::fromArray($this->transport->send(
            'POST',
            '/token',
            body: $request->toArray(),
            form: true,
            authenticated: false,
        ));
    }

    public function me(): MeResponse
    {
        return MeResponse::fromArray($this->transport->send('GET', '/me'));
    }

    public function refreshToken(RefreshTokenRequest $request): TokenResponse
    {
        return TokenResponse::fromArray($this->transport->send(
            'POST',
            '/token/refresh',
            body: $request->toArray(),
            form: true,
            authenticated: false,
        ));
    }
}
