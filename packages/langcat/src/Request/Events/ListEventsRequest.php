<?php

namespace PHPinnacle\Langcat\Request\Events;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPinnacle\Langcat\Enum\EventContext;
use PHPinnacle\Langcat\Enum\EventType;

final class ListEventsRequest
{
    /** @var array<string, int|string> */
    private array $query = [];

    public static function make(): self
    {
        return new self;
    }

    public function administratorId(int $id): self
    {
        return $this->positive('administratorId', $id);
    }

    public function companyId(int $id): self
    {
        return $this->positive('companyId', $id);
    }

    public function context(EventContext $context): self
    {
        return $this->set('context', $context->value);
    }

    public function date(string $date): self
    {
        return $this->setDate('date', $date);
    }

    public function dateAfter(string $date): self
    {
        return $this->setDate('date_gt', $date);
    }

    public function dateBefore(string $date): self
    {
        return $this->setDate('date_lt', $date);
    }

    public function dateOnOrAfter(string $date): self
    {
        return $this->setDate('date_ge', $date);
    }

    public function dateOnOrBefore(string $date): self
    {
        return $this->setDate('date_le', $date);
    }

    public function page(int $page): self
    {
        return $this->positive('page', $page);
    }

    public function parentId(int $id): self
    {
        return $this->positive('parentId', $id);
    }

    public function perPage(int $perPage): self
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException('Items per page must be between 1 and 100.');
        }

        return $this->set('perPage', $perPage);
    }

    public function sortBy(string $sortBy): self
    {
        if (!in_array($sortBy, ['+id', '-id', '+date', '-date'], true)) {
            throw new InvalidArgumentException('Unsupported event sort.');
        }

        return $this->set('sortBy', $sortBy);
    }

    public function studentId(int $id): self
    {
        return $this->positive('studentId', $id);
    }

    public function teacherId(int $id): self
    {
        return $this->positive('teacherId', $id);
    }

    /** @return array<string, int|string> */
    public function toQuery(): array
    {
        return $this->query;
    }

    public function type(EventType $type): self
    {
        return $this->set('type', $type->value);
    }

    private function positive(string $key, int $value): self
    {
        if ($value < 1) {
            throw new InvalidArgumentException('Event filter IDs and page must be positive.');
        }

        return $this->set($key, $value);
    }

    private function set(string $key, int|string $value): self
    {
        $this->query[$key] = $value;

        return $this;
    }

    private function setDate(string $key, string $value): self
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Event date must use Y-m-d format.');
        }

        return $this->set($key, $value);
    }
}
