<?php

namespace PHPinnacle\Canvio\Request\Courses;

use PHPinnacle\Canvio\Enum\EnrollmentRole;
use PHPinnacle\Canvio\Enum\SortOrder;
use PHPinnacle\Canvio\Support\RequestBody;

final readonly class ListAccountCoursesRequest
{
    /**
     * @param  list<EnrollmentRole>|null  $enrollmentTypes
     * @param  list<string>|null  $enrollmentWorkflowStates
     */
    public function __construct(
        public ?array $enrollmentTypes = null,
        public ?array $enrollmentWorkflowStates = null,
        public ?bool $published = null,
        public ?bool $completed = null,
        public int|string|null $termId = null,
        public ?string $searchTerm = null,
        public ?string $sort = null,
        public ?SortOrder $order = null,
        public ?int $perPage = null,
    ) {}

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        return RequestBody::withoutNulls([
            'enrollment_type' => $this->enrollmentTypes,
            'enrollment_workflow_state' => $this->enrollmentWorkflowStates,
            'published' => $this->published,
            'completed' => $this->completed,
            'enrollment_term_id' => $this->termId,
            'search_term' => $this->searchTerm,
            'sort' => $this->sort,
            'order' => $this->order,
            'per_page' => $this->perPage,
        ]);
    }
}
