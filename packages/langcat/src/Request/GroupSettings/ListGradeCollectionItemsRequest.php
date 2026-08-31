<?php

namespace PHPinnacle\Langcat\Request\GroupSettings;

use InvalidArgumentException;
use PHPinnacle\Langcat\Support\RequestValue;

final class ListGradeCollectionItemsRequest
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

    public function schoolId(int $id): self
    {
        return $this->set('schoolId', RequestValue::positive($id, 'School ID'));
    }

    public function sortBy(string $value): self
    {
        if (!in_array($value, ['+id', '-id', '+createdAt', '-createdAt'], true)) {
            throw new InvalidArgumentException('Unsupported grade item sort.');
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
