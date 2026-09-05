<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\Shared\ListDirectoryRequest;
use PHPinnacle\Langcat\Response\Companies\CompaniesResponse;
use PHPinnacle\Langcat\Response\Companies\CompanyResponse;
use PHPinnacle\Langcat\Response\Shared\AccessResponse;
use PHPinnacle\Langcat\Response\Shared\DetailsResponse;
use PHPinnacle\Langcat\Support\ResourcePath;
use PHPinnacle\Langcat\Support\Transport;

final readonly class CompaniesApi
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function all(?ListDirectoryRequest $request = null): CompaniesResponse
    {
        return CompaniesResponse::fromArray($this->transport->send('GET', '/companies', $request?->toQuery() ?? []));
    }

    public function get(int $companyId): CompanyResponse
    {
        return CompanyResponse::fromArray($this->transport->send('GET', ResourcePath::id('companies', $companyId)));
    }

    public function details(int $companyId): DetailsResponse
    {
        return DetailsResponse::fromArray($this->transport->send(
            'GET',
            ResourcePath::id('companies', $companyId) . '/details',
        ));
    }

    public function access(int $companyId): AccessResponse
    {
        return AccessResponse::fromArray($this->transport->send(
            'GET',
            ResourcePath::id('companies', $companyId) . '/access',
        ));
    }
}
