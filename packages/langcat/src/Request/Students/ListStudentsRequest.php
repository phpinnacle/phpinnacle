<?php

namespace PHPinnacle\Langcat\Request\Students;

use InvalidArgumentException;
use PHPinnacle\Langcat\Enum\StudentType;

final class ListStudentsRequest
{
    private ?int $page = null;

    private ?int $perPage = null;

    private ?string $sortBy = null;

    private ?bool $archived = null;

    private ?StudentType $type = null;

    private ?int $schoolId = null;

    public static function make(): self
    {
        return new self;
    }

    public function page(int $page): self
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Page must be at least 1.');
        }

        $this->page = $page;

        return $this;
    }

    public function perPage(int $perPage): self
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Items per page must be between 1 and 100.');
        }

        $this->perPage = $perPage;

        return $this;
    }

    public function sortBy(string $sortBy): self
    {
        $allowed = ['+id', '-id', '+schoolId', '-schoolId', '+isArchived', '-isArchived'];

        if (!in_array($sortBy, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported student sort.');
        }

        $this->sortBy = $sortBy;

        return $this;
    }

    public function archived(bool $archived = true): self
    {
        $this->archived = $archived;

        return $this;
    }

    public function type(StudentType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function schoolId(int $schoolId): self
    {
        if ($schoolId < 1) {
            throw new InvalidArgumentException('School ID must be positive.');
        }

        $this->schoolId = $schoolId;

        return $this;
    }

    /** @return array<string, int|string> */
    public function toQuery(): array
    {
        return array_filter(
            [
                'page' => $this->page,
                'perPage' => $this->perPage,
                'sortBy' => $this->sortBy,
                'isArchived' => $this->archived === null ? null : (int) $this->archived,
                'type' => $this->type?->value,
                'schoolId' => $this->schoolId,
            ],
            static fn (mixed $value) => $value !== null,
        );
    }
}
