<?php

namespace PHPinnacle\Langcat\Request\Groups;

use InvalidArgumentException;
use PHPinnacle\Langcat\Support\RequestValue;

final class SearchGroupsRequest
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

    public function expand(string ...$expansions): self
    {
        if (count($expansions) > 2 || array_diff($expansions, ['group.basic', 'teachers.basic']) !== []) {
            throw new InvalidArgumentException('Group search accepts at most two supported expansions.');
        }

        foreach (array_values(array_unique($expansions)) as $index => $expansion) {
            $this->query["expand[{$index}]"] = $expansion;
        }

        return $this;
    }

    public function name(string $value): self
    {
        return $this->set('name', RequestValue::nonEmpty($value, 'Group name'));
    }

    public function nameLike(string $value): self
    {
        return $this->set('name_like', RequestValue::nonEmpty($value, 'Group name'));
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
        if (!in_array($value, ['+id', '-id'], true)) {
            throw new InvalidArgumentException('Group sort must be +id or -id.');
        }

        return $this->set('sortBy', $value);
    }

    public function teacherId(int $id): self
    {
        return $this->set('teacherId', RequestValue::positive($id, 'Teacher ID'));
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
