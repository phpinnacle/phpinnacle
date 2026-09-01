<?php

namespace PHPinnacle\Canvio\Context;

use Generator;
use PHPinnacle\Canvio\Request\Enrollments\ListEnrollmentsRequest;
use PHPinnacle\Canvio\Response\EnrollmentResponse;
use PHPinnacle\Canvio\Response\UserResponse;
use PHPinnacle\Canvio\Support\ResourcePath;
use PHPinnacle\Canvio\Support\Transport;

final readonly class UserContext
{
    private string $path;

    public function __construct(
        private Transport $transport,
        int|string $userId,
    ) {
        $this->path = '/users/' . ResourcePath::segment($userId, 'User ID');
    }

    public function details(): UserResponse
    {
        return UserResponse::fromArray($this->transport->send('GET', $this->path));
    }

    /** @return Generator<int, EnrollmentResponse> */
    public function enrollments(?ListEnrollmentsRequest $request = null): Generator
    {
        foreach ($this->transport->paginate($this->path . '/enrollments', $request?->toQuery() ?? []) as $payload) {
            yield EnrollmentResponse::fromArray($payload);
        }
    }

    public function profile(): UserResponse
    {
        return UserResponse::fromArray($this->transport->send('GET', $this->path . '/profile'));
    }
}
