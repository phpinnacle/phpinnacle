<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\Shared\ListConsentsRequest;
use PHPinnacle\Langcat\Request\Shared\ListDirectoryRequest;
use PHPinnacle\Langcat\Request\Shared\UpdateDetailsRequest;
use PHPinnacle\Langcat\Response\Shared\ConsentsResponse;
use PHPinnacle\Langcat\Response\Shared\DetailsResponse;
use PHPinnacle\Langcat\Response\Shared\EmptyResponse;
use PHPinnacle\Langcat\Response\Teachers\TeacherResponse;
use PHPinnacle\Langcat\Response\Teachers\TeachersResponse;
use PHPinnacle\Langcat\Support\ResourcePath;
use PHPinnacle\Langcat\Support\Transport;

final readonly class TeachersApi
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function all(?ListDirectoryRequest $request = null): TeachersResponse
    {
        return TeachersResponse::fromArray($this->transport->send('GET', '/teachers', $request?->toQuery() ?? []));
    }

    public function consents(int $teacherId, ?ListConsentsRequest $request = null): ConsentsResponse
    {
        return ConsentsResponse::fromArray($this->transport->send(
            'GET',
            ResourcePath::id('teachers', $teacherId) . '/consents',
            $request?->toQuery() ?? [],
        ));
    }

    public function details(int $teacherId): DetailsResponse
    {
        return DetailsResponse::fromArray($this->transport->send(
            'GET',
            ResourcePath::id('teachers', $teacherId) . '/details',
        ));
    }

    public function get(int $teacherId): TeacherResponse
    {
        return TeacherResponse::fromArray($this->transport->send('GET', ResourcePath::id('teachers', $teacherId)));
    }

    public function updateDetails(int $teacherId, UpdateDetailsRequest $request): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send(
            'PATCH',
            ResourcePath::id('teachers', $teacherId) . '/details',
            body: $request->toArray(),
            allowEmptyResponse: true,
        ));
    }
}
