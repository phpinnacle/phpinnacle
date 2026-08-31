<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\Shared\ListDirectoryRequest;
use PHPinnacle\Langcat\Response\Administrators\AdministratorResponse;
use PHPinnacle\Langcat\Response\Administrators\AdministratorsResponse;
use PHPinnacle\Langcat\Response\Shared\DetailsResponse;
use PHPinnacle\Langcat\Support\ResourcePath;
use PHPinnacle\Langcat\Support\Transport;

final readonly class AdministratorsApi
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function all(?ListDirectoryRequest $request = null): AdministratorsResponse
    {
        return AdministratorsResponse::fromArray($this->transport->send(
            'GET',
            '/administrators',
            $request?->toQuery() ?? [],
        ));
    }

    public function details(int $administratorId): DetailsResponse
    {
        return DetailsResponse::fromArray($this->transport->send(
            'GET',
            ResourcePath::id('administrators', $administratorId) . '/details',
        ));
    }

    public function get(int $administratorId): AdministratorResponse
    {
        return AdministratorResponse::fromArray($this->transport->send('GET', ResourcePath::id(
            'administrators',
            $administratorId,
        )));
    }
}
