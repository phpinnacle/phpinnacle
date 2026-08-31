<?php

namespace PHPinnacle\Langcat\Request\Shared;

use InvalidArgumentException;

final class ListConsentsRequest
{
    private ?string $sortBy = null;

    public static function make(): self
    {
        return new self;
    }

    public function sortBy(string $sortBy): self
    {
        if (!in_array($sortBy, ['+id', '-id'], true)) {
            throw new InvalidArgumentException('Consent sort must be +id or -id.');
        }

        $this->sortBy = $sortBy;

        return $this;
    }

    /** @return array<string, string> */
    public function toQuery(): array
    {
        return $this->sortBy === null ? [] : ['sortBy' => $this->sortBy];
    }
}
