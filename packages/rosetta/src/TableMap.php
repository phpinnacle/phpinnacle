<?php

namespace PHPinnacle\Rosetta;

use RuntimeException;

final readonly class TableMap
{
    /** @param array<string, string> $entries */
    private function __construct(
        private array $entries,
    ) {}

    /** @param array<string, string> $entries */
    public static function fromArray(array $entries): self
    {
        return new self($entries);
    }

    /** @param list<object> $rows */
    public static function fromSchemaStorage(array $rows, SerializedDataParser $parser): self
    {
        $entries = [];

        foreach ($rows as $row) {
            $slot = $row->schema_id ?? null;
            $status = $row->status ?? null;
            $data = $row->data ?? null;
            $contents = is_resource($data) ? stream_get_contents($data) : $data;

            if (!is_int($slot) || $slot <= 0 || !is_string($contents)) {
                throw new RuntimeException('Invalid SchemaStorage row');
            }

            if ($status !== 100) {
                throw new RuntimeException(sprintf('SchemaStorage slot %d is not ready', $slot));
            }

            $tables = $parser->parse($contents)[1] ?? null;
            self::readTables($tables, $slot, '', $entries);
        }

        return new self($entries);
    }

    public function name(string $canonicalName): string
    {
        $canonicalName = strtolower($canonicalName);

        return $this->entries[$canonicalName] ?? $canonicalName;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return $this->entries;
    }

    /** @param array<string, string> $entries */
    private static function readTables(mixed $tables, int $slot, string $owner, array &$entries): void
    {
        if (!is_array($tables) || !is_int($tables[0] ?? null) || $tables[0] !== (count($tables) - 1)) {
            throw new RuntimeException('Invalid SchemaStorage table collection');
        }

        foreach (array_slice($tables, 1) as $table) {
            if (!is_array($table) || !is_string($table[0] ?? null) || !is_int($table[2] ?? null)) {
                throw new RuntimeException('Invalid SchemaStorage table entry');
            }

            $name = $owner . '_' . strtolower($table[0]);
            // The storage slot qualifies the table, never its canonical column names.
            $physicalName = $name . 'x' . $slot;

            if (array_key_exists($name, $entries) && $entries[$name] !== $physicalName) {
                throw new RuntimeException(sprintf('Ambiguous SchemaStorage table %s', $name));
            }

            $entries[$name] = $physicalName;
            self::readTables($table[5] ?? null, $slot, $name, $entries);
        }
    }
}
