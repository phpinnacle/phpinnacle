<?php

namespace PHPinnacle\Canvio\Request\Users;

use PHPinnacle\Canvio\Enum\AccountUserSort;
use PHPinnacle\Canvio\Enum\EnrollmentRole;
use PHPinnacle\Canvio\Enum\SortOrder;
use PHPinnacle\Canvio\Support\RequestBody;

final readonly class ListAccountUsersRequest
{
    /** @param list<string>|null $uuids */
    public function __construct(
        public ?EnrollmentRole $enrollmentType = null,
        public ?string $searchTerm = null,
        public ?AccountUserSort $sort = null,
        public ?SortOrder $order = null,
        public ?bool $includeDeletedUsers = null,
        public ?array $uuids = null,
        public ?int $perPage = null,
    ) {}

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        return RequestBody::withoutNulls([
            'enrollment_type' => $this->enrollmentType,
            'search_term' => $this->searchTerm,
            'sort' => $this->sort,
            'order' => $this->order,
            'include_deleted_users' => $this->includeDeletedUsers,
            'uuids' => $this->uuids,
            'per_page' => $this->perPage,
        ]);
    }
}
