<?php

namespace PHPinnacle\Rosetta;

final class TypeMap
{
    /** @param array<string, list<array<mixed>>> $entries */
    private function __construct(
        private readonly array $entries,
    ) {}

    /** @param array<string, list<array<mixed>>> $entries */
    public static function fromArray(array $entries): self
    {
        return new self(array_change_key_case($entries, CASE_LOWER));
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /** @return list<array<mixed>>|null */
    public function descriptors(string $uuid): ?array
    {
        return $this->entries[strtolower($uuid)] ?? null;
    }

    /** @return array<string, list<array<mixed>>> */
    public function toArray(): array
    {
        return $this->entries;
    }
}
