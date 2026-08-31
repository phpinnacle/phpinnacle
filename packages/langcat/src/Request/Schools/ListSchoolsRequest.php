<?php

namespace PHPinnacle\Langcat\Request\Schools;

use InvalidArgumentException;

final class ListSchoolsRequest
{
    private ?int $page = null;

    private ?int $perPage = null;

    private ?string $sortBy = null;

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
        if ($perPage < 1 || $perPage > 400) {
            throw new InvalidArgumentException('Items per page must be between 1 and 400.');
        }

        $this->perPage = $perPage;

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

    public function sortBy(string $sortBy): self
    {
        if (!in_array($sortBy, ['+id', '-id', '+name', '-name'], true)) {
            throw new InvalidArgumentException('Unsupported school sort.');
        }

        $this->sortBy = $sortBy;

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
                'schoolId' => $this->schoolId,
            ],
            static fn (mixed $value) => $value !== null,
        );
    }
}
