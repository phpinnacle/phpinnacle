<?php

namespace PHPinnacle\Canvio\Request\Submissions;

use PHPinnacle\Canvio\Support\RequestBody;

final readonly class ListSubmissionsRequest
{
    /** @param list<string>|null $include */
    public function __construct(
        public ?array $include = null,
        public ?bool $grouped = null,
        public ?int $perPage = null,
    ) {}

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        return RequestBody::withoutNulls([
            'include' => $this->include,
            'grouped' => $this->grouped,
            'per_page' => $this->perPage,
        ]);
    }
}
