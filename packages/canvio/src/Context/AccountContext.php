<?php

namespace PHPinnacle\Canvio\Context;

use Generator;
use PHPinnacle\Canvio\Enum\EnrollmentRole;
use PHPinnacle\Canvio\Request\Courses\ListAccountCoursesRequest;
use PHPinnacle\Canvio\Request\Users\ListAccountUsersRequest;
use PHPinnacle\Canvio\Response\AccountResponse;
use PHPinnacle\Canvio\Response\CourseResponse;
use PHPinnacle\Canvio\Response\UserResponse;
use PHPinnacle\Canvio\Support\ResourcePath;
use PHPinnacle\Canvio\Support\Transport;

final readonly class AccountContext
{
    private string $path;

    public function __construct(
        private Transport $transport,
        int|string $accountId,
    ) {
        $this->path = '/accounts/' . ResourcePath::segment($accountId, 'Account ID');
    }

    /** @return Generator<int, CourseResponse> */
    public function courses(?ListAccountCoursesRequest $request = null): Generator
    {
        foreach ($this->transport->paginate($this->path . '/courses', $request?->toQuery() ?? []) as $payload) {
            yield CourseResponse::fromArray($payload);
        }
    }

    public function details(): AccountResponse
    {
        return AccountResponse::fromArray($this->transport->send('GET', $this->path));
    }

    /** @return Generator<int, UserResponse> */
    public function students(?ListAccountUsersRequest $request = null): Generator
    {
        $query = ['enrollment_type' => EnrollmentRole::Student] + ($request?->toQuery() ?? []);

        foreach ($this->transport->paginate($this->path . '/users', $query) as $payload) {
            yield UserResponse::fromArray($payload);
        }
    }

    /** @return Generator<int, AccountResponse> */
    public function subAccounts(bool $recursive = false): Generator
    {
        $query = $recursive ? ['recursive' => true] : [];

        foreach ($this->transport->paginate($this->path . '/sub_accounts', $query) as $payload) {
            yield AccountResponse::fromArray($payload);
        }
    }

    /** @return Generator<int, UserResponse> */
    public function users(?ListAccountUsersRequest $request = null): Generator
    {
        foreach ($this->transport->paginate($this->path . '/users', $request?->toQuery() ?? []) as $payload) {
            yield UserResponse::fromArray($payload);
        }
    }
}
