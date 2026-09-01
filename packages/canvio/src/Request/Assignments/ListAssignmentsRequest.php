<?php

namespace PHPinnacle\Canvio\Request\Assignments;

use PHPinnacle\Canvio\Support\RequestBody;

final readonly class ListAssignmentsRequest
{
    /**
     * @param  list<string>|null  $include
     * @param  list<int>|null  $assignmentIds
     */
    public function __construct(
        public ?array $include = null,
        public ?string $searchTerm = null,
        public ?array $assignmentIds = null,
        public ?string $orderBy = null,
        public ?string $bucket = null,
        public ?bool $postToSis = null,
        public ?int $perPage = null,
    ) {}

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        return RequestBody::withoutNulls([
            'include' => $this->include,
            'search_term' => $this->searchTerm,
            'assignment_ids' => $this->assignmentIds,
            'order_by' => $this->orderBy,
            'bucket' => $this->bucket,
            'post_to_sis' => $this->postToSis,
            'per_page' => $this->perPage,
        ]);
    }
}
