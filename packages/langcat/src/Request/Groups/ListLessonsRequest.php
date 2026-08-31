<?php

namespace PHPinnacle\Langcat\Request\Groups;

use InvalidArgumentException;
use PHPinnacle\Langcat\Support\RequestValue;

final class ListLessonsRequest
{
    /** @var array<string, int|string> */
    private array $query = [];

    public static function make(): self
    {
        return new self;
    }

    public function createdAfter(string $value): self
    {
        return $this->date('createdAt_gt', $value);
    }

    public function createdAt(string $value): self
    {
        return $this->date('createdAt', $value);
    }

    public function createdBefore(string $value): self
    {
        return $this->date('createdAt_lt', $value);
    }

    public function createdOnOrAfter(string $value): self
    {
        return $this->date('createdAt_ge', $value);
    }

    public function createdOnOrBefore(string $value): self
    {
        return $this->date('createdAt_le', $value);
    }

    public function endsAfter(string $value): self
    {
        return $this->date('endAt_gt', $value);
    }

    public function endsAt(string $value): self
    {
        return $this->date('endAt', $value);
    }

    public function endsBefore(string $value): self
    {
        return $this->date('endAt_lt', $value);
    }

    public function endsOnOrAfter(string $value): self
    {
        return $this->date('endAt_ge', $value);
    }

    public function endsOnOrBefore(string $value): self
    {
        return $this->date('endAt_le', $value);
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
            throw new InvalidArgumentException('Lesson sort must be +id or -id.');
        }

        return $this->set('sortBy', $value);
    }

    public function startsAfter(string $value): self
    {
        return $this->date('startAt_gt', $value);
    }

    public function startsAt(string $value): self
    {
        return $this->date('startAt', $value);
    }

    public function startsBefore(string $value): self
    {
        return $this->date('startAt_lt', $value);
    }

    public function startsOnOrAfter(string $value): self
    {
        return $this->date('startAt_ge', $value);
    }

    public function startsOnOrBefore(string $value): self
    {
        return $this->date('startAt_le', $value);
    }

    /** @return array<string, int|string> */
    public function toQuery(): array
    {
        return $this->query;
    }

    private function date(string $key, string $value): self
    {
        return $this->set($key, RequestValue::date($value, 'Lesson date'));
    }

    private function set(string $key, int|string $value): self
    {
        $this->query[$key] = $value;

        return $this;
    }
}
