<?php

namespace PHPinnacle\Langcat\Request\Students;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPinnacle\Langcat\Enum\StudentType;

final class SearchStudentsRequest
{
    private ?int $page = null;

    private ?int $perPage = null;

    private ?string $sortBy = null;

    private ?bool $archived = null;

    private ?StudentType $type = null;

    /** @var list<string> */
    private array $expansions = [];

    private ?int $schoolId = null;

    private ?string $createdAt = null;

    private ?string $createdAfter = null;

    private ?string $createdOnOrAfter = null;

    private ?string $createdBefore = null;

    private ?string $createdOnOrBefore = null;

    public static function make(): self
    {
        return new self;
    }

    public function archived(bool $archived = true): self
    {
        $this->archived = $archived;

        return $this;
    }

    public function createdAfter(string $createdAfter): self
    {
        $this->createdAfter = $this->date($createdAfter);

        return $this;
    }

    public function createdAt(string $createdAt): self
    {
        $this->createdAt = $this->date($createdAt);

        return $this;
    }

    public function createdBefore(string $createdBefore): self
    {
        $this->createdBefore = $this->date($createdBefore);

        return $this;
    }

    public function createdOnOrAfter(string $createdOnOrAfter): self
    {
        $this->createdOnOrAfter = $this->date($createdOnOrAfter);

        return $this;
    }

    public function createdOnOrBefore(string $createdOnOrBefore): self
    {
        $this->createdOnOrBefore = $this->date($createdOnOrBefore);

        return $this;
    }

    public function expand(string ...$expansions): self
    {
        $allowed = ['student.basic', 'student.details', 'student.access.login', 'parents.basic', 'parents.details'];

        if (count($expansions) > 2 || array_diff($expansions, $allowed) !== []) {
            throw new InvalidArgumentException('Student search accepts at most two supported expansions.');
        }

        $this->expansions = array_values(array_unique($expansions));

        return $this;
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
        $allowed = ['+id', '-id', '+schoolId', '-schoolId', '+isArchived', '-isArchived'];

        if (!in_array($sortBy, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported student sort.');
        }

        $this->sortBy = $sortBy;

        return $this;
    }

    /** @return array<string, int|string> */
    public function toQuery(): array
    {
        $query = array_filter(
            [
                'page' => $this->page,
                'perPage' => $this->perPage,
                'sortBy' => $this->sortBy,
                'isArchived' => $this->archived === null ? null : (int) $this->archived,
                'type' => $this->type?->value,
                'schoolId' => $this->schoolId,
                'createdAt' => $this->createdAt,
                'createdAt_gt' => $this->createdAfter,
                'createdAt_ge' => $this->createdOnOrAfter,
                'createdAt_lt' => $this->createdBefore,
                'createdAt_le' => $this->createdOnOrBefore,
            ],
            static fn (mixed $value) => $value !== null,
        );

        foreach ($this->expansions as $index => $expansion) {
            $query["expand[{$index}]"] = $expansion;
        }

        return $query;
    }

    public function type(StudentType $type): self
    {
        $this->type = $type;

        return $this;
    }

    private function date(string $value): string
    {
        foreach (['Y-m-d', 'Y-m-d H:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);

            if ($date !== false && $date->format($format) === $value) {
                return $value;
            }
        }

        throw new InvalidArgumentException('Date must use Y-m-d or Y-m-d H:i:s format.');
    }
}
