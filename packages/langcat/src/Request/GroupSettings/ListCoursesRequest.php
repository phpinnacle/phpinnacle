<?php

namespace PHPinnacle\Langcat\Request\GroupSettings;

use InvalidArgumentException;
use PHPinnacle\Langcat\Support\RequestValue;

final class ListCoursesRequest
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
        $allowed = [
            '+id',
            '-id',
            '+name',
            '-name',
            '+languageId',
            '-languageId',
            '+levelId',
            '-levelId',
            '+ageGroupId',
            '-ageGroupId',
            '+courseBookId',
            '-courseBookId',
            '+createdAt',
            '-createdAt',
        ];

        if (!in_array($value, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported course sort.');
        }

        return $this->set('sortBy', $value);
    }

    public function schoolId(int $id): self
    {
        return $this->id('schoolId', $id);
    }

    public function courseBookId(int $id): self
    {
        return $this->id('courseBookId', $id);
    }

    public function levelId(int $id): self
    {
        return $this->id('levelId', $id);
    }

    public function languageId(int $id): self
    {
        return $this->id('languageId', $id);
    }

    public function ageGroupId(int $id): self
    {
        return $this->id('ageGroupId', $id);
    }

    public function archived(bool $value = true): self
    {
        return $this->set('isArchived', (int) $value);
    }

    public function name(string $value): self
    {
        return $this->set('name', RequestValue::nonEmpty($value, 'Course name'));
    }

    public function nameLike(string $value): self
    {
        return $this->set('name_like', RequestValue::nonEmpty($value, 'Course name'));
    }

    /** @return array<string, int|string> */
    public function toQuery(): array
    {
        return $this->query;
    }

    private function id(string $key, int $id): self
    {
        return $this->set($key, RequestValue::positive($id, 'Resource ID'));
    }

    private function set(string $key, int|string $value): self
    {
        $this->query[$key] = $value;

        return $this;
    }
}
