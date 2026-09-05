<?php

namespace PHPinnacle\Rosetta;

use RuntimeException;

final class StorageMap
{
    /** @var array<string, array<string, int>> */
    private array $entries = [];

    /** @var array<string, array<int, string>> */
    private array $uuids = [];

    /** @param array<string, array<string, int>> $entries */
    public static function fromArray(array $entries): self
    {
        $instance = new self;

        foreach ($entries as $uuid => $tokens) {
            foreach ($tokens as $token => $code) {
                $instance->add($uuid, $token, $code);
            }
        }

        return $instance;
    }

    public static function fromSerialized(string $contents, SerializedDataParser $parser): self
    {
        $list = $parser->parse($contents)[1] ?? null;

        if (!is_array($list)) {
            throw new RuntimeException('Invalid DBNames root structure');
        }

        $count = $list[0] ?? null;
        $entries = array_slice($list, 1);

        if (!is_int($count) || count($entries) !== $count) {
            throw new RuntimeException('DBNames entry count does not match declared count');
        }

        $instance = new self;

        foreach ($entries as $position => $entry) {
            if (
                !is_array($entry)
                || count($entry) !== 3
                || !is_string($entry[0] ?? null)
                || !is_string($entry[1] ?? null)
                || !is_int($entry[2] ?? null)
            ) {
                throw new RuntimeException(sprintf(
                    'Invalid DBNames entry at position %d',
                    $position + 1,
                ));
            }

            $instance->add($entry[0], $entry[1], $entry[2]);
        }

        return $instance;
    }

    public function code(string $uuid, #[\SensitiveParameter] string $token): ?int
    {
        return $this->entries[strtolower($uuid)][$token] ?? null;
    }

    public function name(string $uuid, #[\SensitiveParameter] string $token): ?string
    {
        $code = $this->code($uuid, $token);

        return $code === null ? null : sprintf('_%s%d', $token, $code);
    }

    public function uuid(#[\SensitiveParameter] string $token, int $code): ?string
    {
        return $this->uuids[$token][$code] ?? null;
    }

    /** @return array<int, string> */
    public function uuids(#[\SensitiveParameter] string $token): array
    {
        $uuids = $this->uuids[$token] ?? [];
        ksort($uuids, SORT_NUMERIC);

        return $uuids;
    }

    /** @return array<string, int> */
    public function entries(string $uuid): array
    {
        return $this->entries[strtolower($uuid)] ?? [];
    }

    /** @return array<string, array<string, int>> */
    public function toArray(): array
    {
        return $this->entries;
    }

    private function add(string $uuid, #[\SensitiveParameter] string $token, int $code): void
    {
        $uuid = strtolower($uuid);

        $this->entries[$uuid][$token] = $code;
        $this->uuids[$token][$code] = $uuid;
    }
}
