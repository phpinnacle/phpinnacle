<?php

namespace PHPinnacle\Langcat\Request\Groups;

use InvalidArgumentException;
use PHPinnacle\Langcat\Enum\AssignType;
use PHPinnacle\Langcat\Support\RequestValue;

final class ListGroupStudentsRequest
{
    /** @var array<string, int|string> */
    private array $query = [];

    public static function make(): self
    {
        return new self;
    }

    public function archived(bool $value = true): self
    {
        return $this->set('isArchived', (int) $value);
    }

    public function assignType(AssignType $type): self
    {
        return $this->set('assignType', $type->value);
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

    public function schoolId(int $id): self
    {
        return $this->set('schoolId', RequestValue::positive($id, 'School ID'));
    }

    public function sortBy(string $value): self
    {
        $allowed = ['+groupId', '-groupId', '+isArchived', '-isArchived', '+studentId', '-studentId'];

        if (!in_array($value, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported group student sort.');
        }

        return $this->set('sortBy', $value);
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
