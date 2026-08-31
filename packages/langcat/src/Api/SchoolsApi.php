<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\Schools\ListSchoolsRequest;
use PHPinnacle\Langcat\Response\Schools\SchoolResponse;
use PHPinnacle\Langcat\Response\Schools\SchoolsResponse;
use PHPinnacle\Langcat\Support\ResourcePath;
use PHPinnacle\Langcat\Support\Transport;

final readonly class SchoolsApi
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function all(?ListSchoolsRequest $request = null): SchoolsResponse
    {
        return SchoolsResponse::fromArray($this->transport->send('GET', '/schools', $request?->toQuery() ?? []));
    }

    public function get(int $schoolId): SchoolResponse
    {
        return SchoolResponse::fromArray($this->transport->send('GET', ResourcePath::id('schools', $schoolId)));
    }
}
