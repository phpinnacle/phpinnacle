<?php

namespace PHPinnacle\Canvio\Request\Courses;

use PHPinnacle\Canvio\Enum\EnrollmentRole;
use PHPinnacle\Canvio\Support\RequestBody;

final readonly class ListCoursesRequest
{
    /**
     * @param  list<string>|null  $include
     * @param  list<string>|null  $states
     */
    public function __construct(
        public ?EnrollmentRole $enrollmentType = null,
        public ?string $enrollmentState = null,
        public ?array $include = null,
        public ?array $states = null,
        public ?int $perPage = null,
    ) {}

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        return RequestBody::withoutNulls([
            'enrollment_type' => $this->enrollmentType,
            'enrollment_state' => $this->enrollmentState,
            'include' => $this->include,
            'state' => $this->states,
            'per_page' => $this->perPage,
        ]);
    }
}
