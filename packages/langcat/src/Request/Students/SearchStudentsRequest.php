<?php

namespace PHPinnacle\Langcat\Request\Students;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPinnacle\Langcat\Enum\StudentType;

final class SearchStudentsRequest
{
    /**
     * @var array{
     *     page: ?int,
     *     perPage: ?int,
     *     sortBy: ?string,
     *     isArchived: ?int,
     *     type: ?string,
     *     schoolId: ?int,
     *     createdAt: ?string,
     *     createdAt_gt: ?string,
     *     createdAt_ge: ?string,
     *     createdAt_lt: ?string,
     *     createdAt_le: ?string,
     * }
     */
    private array $query = [
        'page' => null,
        'perPage' => null,
        'sortBy' => null,
        'isArchived' => null,
        'type' => null,
        'schoolId' => null,
        'createdAt' => null,
        'createdAt_gt' => null,
        'createdAt_ge' => null,
        'createdAt_lt' => null,
        'createdAt_le' => null,
    ];

    /** @var list<string> */
    private array $expansions = [];

    public static function make(): self
    {
        return new self;
    }

    public function archived(bool $archived = true): self
    {
        $this->query['isArchived'] = (int) $archived;

        return $this;
    }

    public function createdAfter(string $createdAfter): self
    {
        $this->query['createdAt_gt'] = $this->date($createdAfter);

        return $this;
    }

    public function createdAt(string $createdAt): self
    {
        $this->query['createdAt'] = $this->date($createdAt);

        return $this;
    }

    public function createdBefore(string $createdBefore): self
    {
        $this->query['createdAt_lt'] = $this->date($createdBefore);

        return $this;
    }

    public function createdOnOrAfter(string $createdOnOrAfter): self
    {
        $this->query['createdAt_ge'] = $this->date($createdOnOrAfter);

        return $this;
    }

    public function createdOnOrBefore(string $createdOnOrBefore): self
    {
        $this->query['createdAt_le'] = $this->date($createdOnOrBefore);

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

        $this->query['page'] = $page;

        return $this;
    }

    public function perPage(int $perPage): self
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Items per page must be between 1 and 100.');
        }

        $this->query['perPage'] = $perPage;

        return $this;
    }

    public function schoolId(int $schoolId): self
    {
        if ($schoolId < 1) {
            throw new InvalidArgumentException('School ID must be positive.');
        }

        $this->query['schoolId'] = $schoolId;

        return $this;
    }

    public function sortBy(string $sortBy): self
    {
        $allowed = ['+id', '-id', '+schoolId', '-schoolId', '+isArchived', '-isArchived'];

        if (!in_array($sortBy, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported student sort.');
        }

        $this->query['sortBy'] = $sortBy;

        return $this;
    }

    /** @return array<string, int|string> */
    public function toQuery(): array
    {
        $query = array_filter(
            $this->query,
            static fn (mixed $value) => $value !== null,
        );

        foreach ($this->expansions as $index => $expansion) {
            $query["expand[{$index}]"] = $expansion;
        }

        return $query;
    }

    public function type(StudentType $type): self
    {
        $this->query['type'] = $type->value;

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
