<?php

namespace PHPinnacle\Langcat\Request\Documents;

use InvalidArgumentException;
use PHPinnacle\Langcat\Enum\DocumentTemplateType;

final class ListDocumentTemplatesRequest
{
    /** @var array<string, int|string> */
    private array $query = [];

    public static function make(): self
    {
        return new self;
    }

    public function sortBy(string $sortBy): self
    {
        if (!in_array($sortBy, ['+id', '-id'], true)) {
            throw new InvalidArgumentException('Document template sort must be +id or -id.');
        }

        return $this->set('sortBy', $sortBy);
    }

    public function schoolId(int $schoolId): self
    {
        if ($schoolId < 1) {
            throw new InvalidArgumentException('School ID must be positive.');
        }

        return $this->set('schoolId', $schoolId);
    }

    public function title(string $title): self
    {
        return $this->nonEmpty('title', $title);
    }

    public function titleLike(string $title): self
    {
        return $this->nonEmpty('title_like', $title);
    }

    public function type(DocumentTemplateType $type): self
    {
        return $this->set('type', $type->value);
    }

    /** @return array<string, int|string> */
    public function toQuery(): array
    {
        return $this->query;
    }

    private function nonEmpty(string $key, string $value): self
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Document template title cannot be empty.');
        }

        return $this->set($key, $value);
    }

    private function set(string $key, int|string $value): self
    {
        $this->query[$key] = $value;

        return $this;
    }
}
