<?php

namespace PHPinnacle\Langcat\Request\Groups;

use InvalidArgumentException;
use PHPinnacle\Langcat\Support\RequestValue;

final class ListGroupTeachersRequest
{
    /** @var array<string, int|string> */
    private array $query = [];

    public static function make(): self
    {
        return new self;
    }

    public function page(int $value): self
    {
        return $this->set('page', RequestValue::positive($value, 'Page'));
    }

    public function perPage(int $value): self
    {
        if ($value > 100) {
            throw new InvalidArgumentException('Items per page cannot exceed 100.');
        }

        return $this->set('perPage', RequestValue::positive($value, 'Items per page'));
    }

    public function sortBy(string $value): self
    {
        if (!in_array($value, ['+teacherId', '-teacherId'], true)) {
            throw new InvalidArgumentException('Group teacher sort must be +teacherId or -teacherId.');
        }

        return $this->set('sortBy', $value);
    }

    public function archived(bool $value = true): self
    {
        return $this->set('isArchived', (int) $value);
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
