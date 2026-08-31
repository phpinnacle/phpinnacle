<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\Shared\ListDirectoryRequest;
use PHPinnacle\Langcat\Response\Classrooms\ClassroomResponse;
use PHPinnacle\Langcat\Response\Classrooms\ClassroomsResponse;
use PHPinnacle\Langcat\Support\ResourcePath;
use PHPinnacle\Langcat\Support\Transport;

final readonly class ClassroomsApi
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function all(?ListDirectoryRequest $request = null): ClassroomsResponse
    {
        return ClassroomsResponse::fromArray($this->transport->send('GET', '/classrooms', $request?->toQuery() ?? []));
    }

    public function get(int $classroomId): ClassroomResponse
    {
        return ClassroomResponse::fromArray($this->transport->send('GET', ResourcePath::id(
            'classrooms',
            $classroomId,
        )));
    }
}
