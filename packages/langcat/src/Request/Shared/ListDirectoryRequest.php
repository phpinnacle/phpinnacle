<?php

namespace PHPinnacle\Langcat\Request\Shared;

use InvalidArgumentException;

final class ListDirectoryRequest
{
    private ?int $page = null;

    private ?int $perPage = null;

    private ?string $sortBy = null;

    private ?bool $archived = null;

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
        if (!in_array($sortBy, ['+id', '-id'], true)) {
            throw new InvalidArgumentException('Directory sort must be +id or -id.');
        }

        $this->sortBy = $sortBy;

        return $this;
    }

    public function archived(bool $archived = true): self
    {
        $this->archived = $archived;

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
                'schoolId' => $this->schoolId,
            ],
            static fn (mixed $value) => $value !== null,
        );
    }
}
