<?php

namespace PHPinnacle\Canvio\Request\Enrollments;

use PHPinnacle\Canvio\Enum\EnrollmentType;
use PHPinnacle\Canvio\Support\RequestBody;

final readonly class ListEnrollmentsRequest
{
    /**
     * @param  list<EnrollmentType>|null  $types
     * @param  list<string>|null  $states
     * @param  list<int>|null  $roleIds
     * @param  list<string>|null  $include
     */
    public function __construct(
        public ?array $types = null,
        public ?array $states = null,
        public int|string|null $userId = null,
        public ?array $roleIds = null,
        public ?array $include = null,
        public ?int $perPage = null,
    ) {}

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        return RequestBody::withoutNulls([
            'type' => $this->types,
            'state' => $this->states,
            'user_id' => $this->userId,
            'role_id' => $this->roleIds,
            'include' => $this->include,
            'per_page' => $this->perPage,
        ]);
    }
}
