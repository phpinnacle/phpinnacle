<?php

namespace PHPinnacle\Canvio\Request\Users;

use PHPinnacle\Canvio\Enum\CourseUserSort;
use PHPinnacle\Canvio\Enum\EnrollmentRole;
use PHPinnacle\Canvio\Support\RequestBody;

final readonly class ListCourseUsersRequest
{
    /**
     * @param  list<EnrollmentRole>|null  $enrollmentTypes
     * @param  list<string>|null  $include
     */
    public function __construct(
        public ?array $enrollmentTypes = null,
        public ?string $searchTerm = null,
        public ?CourseUserSort $sort = null,
        public ?int $enrollmentRoleId = null,
        public int|string|null $userId = null,
        public ?array $include = null,
        public ?int $perPage = null,
    ) {}

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        return RequestBody::withoutNulls([
            'enrollment_type' => $this->enrollmentTypes,
            'search_term' => $this->searchTerm,
            'sort' => $this->sort,
            'enrollment_role_id' => $this->enrollmentRoleId,
            'user_id' => $this->userId,
            'include' => $this->include,
            'per_page' => $this->perPage,
        ]);
    }
}
