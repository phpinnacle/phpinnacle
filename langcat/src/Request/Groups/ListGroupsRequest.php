<?php

namespace PHPinnacle\Langcat\Request\Groups;

use InvalidArgumentException;
use PHPinnacle\Langcat\Support\RequestValue;

final class ListGroupsRequest
{
    /** @var array<string, int|string> */
    private array $query = [];

    public static function make(): self
    {
        return new self;
    }

    public function page(int $page): self
    {
        return $this->set('page', RequestValue::positive($page, 'Page'));
    }

    public function perPage(int $perPage): self
    {
        if ($perPage > 100) {
            throw new InvalidArgumentException('Items per page cannot exceed 100.');
        }

        return $this->set('perPage', RequestValue::positive($perPage, 'Items per page'));
    }

    public function sortBy(string $sortBy): self
    {
        if (!in_array($sortBy, ['+id', '-id'], true)) {
            throw new InvalidArgumentException('Group sort must be +id or -id.');
        }

        return $this->set('sortBy', $sortBy);
    }

    public function archived(bool $archived = true): self
    {
        return $this->set('isArchived', (int) $archived);
    }

    public function schoolId(int $id): self
    {
        return $this->set('schoolId', RequestValue::positive($id, 'School ID'));
    }

    /** @return array<string, int|string> */
    public function toQuery(): array
    {
        return $this->query;
    }

    private function set(string $key, int|string $value): self
    {
        $this->query[$key] = $value;

        return $this;
    }
}
