<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\Users\SearchUsersRequest;
use PHPinnacle\Langcat\Response\Users\UsersResponse;
use PHPinnacle\Langcat\Support\Transport;

final readonly class UsersApi
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function search(?SearchUsersRequest $request = null): UsersResponse
    {
        return UsersResponse::fromArray($this->transport->send('GET', '/users/search', $request?->toQuery() ?? []));
    }
}
