<?php

namespace PHPinnacle\Rosetta;

use Closure;
use Generator;
use PHPinnacle\Rosetta\Contracts\Connection;
use PHPinnacle\Rosetta\Data\MetadataDefinition;
use PHPinnacle\Rosetta\Enums\MetadataKind;
use RuntimeException;

final class MetadataLoader
{
    private const CONFIG_BATCH_SIZE = 500;

    private const DEFINED_TYPE_COLLECTION = 'c045099e-13b9-4fb6-9d50-fca00202971e';

    public function __construct(
        private readonly SerializedDataParser $parser,
    ) {}

    /**
     * @param  list<string>  $only
     * @param  (Closure(int, int): void)|null  $progress
     * @return Generator<int, MetadataDefinition>
     */
    public function load(Connection $connection, array $only = [], ?Closure $progress = null): Generator
    {
        $filter = $this->filter($only);
        $storageMap = $this->storageMap($connection);
        $typeMap = $this->typeMap($connection);
        $tables = $this->tables($storageMap, $filter);

        $total = count($tables);
        $processed = 0;
        $progress?->__invoke($processed, $total);

        foreach ($this->metadata($connection, $storageMap, $typeMap, $tables) as $object) {
            $progress?->__invoke(++$processed, $total);

            yield $object;
        }
    }

    public function storageMap(Connection $connection): StorageMap
    {
        return StorageMap::fromSerialized(
            $this->compressedData(
                $connection,
                'SELECT binarydata AS data FROM Params WHERE filename = ?',
                'DBNames',
                'Params/DBNames',
            ),
            $this->parser,
        );
    }

    public function typeMap(Connection $connection): TypeMap
    {
        $root = $this->parser->parse($this->compressedData(
            $connection,
            'SELECT binarydata AS data FROM Config WHERE filename = ?',
            'root',
            'Config/root',
        ));
        $rootFile = $root[1] ?? null;

        if (!is_string($rootFile)) {
            throw new RuntimeException('Invalid Config/root structure');
        }

        $configuration = $this->parser->parse($this->compressedData(
            $connection,
            'SELECT binarydata AS data FROM Config WHERE filename = ?',
            $rootFile,
            sprintf('Config/%s', $rootFile),
        ));
        $metadata = $configuration[3] ?? null;

        if (!is_array($metadata)) {
            throw new RuntimeException('Invalid configuration metadata');
        }

        $groups = $metadata[1] ?? null;

        if (!is_array($groups)) {
            throw new RuntimeException('Invalid configuration metadata collections');
        }

        $group = null;

        foreach ($groups as $candidate) {
            if (is_array($candidate) && ($candidate[0] ?? null) === self::DEFINED_TYPE_COLLECTION) {
                $group = $candidate;

                break;
            }
        }

        if ($group === null) {
            throw new RuntimeException('Defined type collection was not found in the configuration');
        }

        $count = $group[1] ?? null;
        $fileNames = [];

        foreach (array_slice($group, 2) as $fileName) {
            if (!is_string($fileName)) {
                throw new RuntimeException('Invalid defined type file name');
            }

            $fileNames[] = $fileName;
        }

        if (!is_int($count) || count($fileNames) !== $count) {
            throw new RuntimeException('Defined type count does not match declared count');
        }

        $entries = [];

        foreach ($this->configData($connection, $fileNames) as $contents) {
            $definition = $this->parser->parse($contents);
            $body = $definition[1] ?? null;

            if (!is_array($body)) {
                throw new RuntimeException('Invalid defined type structure');
            }

            $uuid = $body[1] ?? null;
            $pattern = $body[4] ?? null;

            if (!is_string($uuid) || !is_array($pattern) || ($pattern[0] ?? null) !== 'Pattern') {
                throw new RuntimeException('Invalid defined type structure');
            }

            $entries[$uuid] = array_values(array_filter(
                array_slice($pattern, 1),
                fn ($descriptor) => is_array($descriptor),
            ));
        }

        return TypeMap::fromArray($entries);
    }

    /** @param list<string> $only */
    public function count(StorageMap $storageMap, array $only = []): int
    {
        return count($this->tables($storageMap, $this->filter($only)));
    }

    /**
     * @return list<MetadataDefinition>
     */
    public function chunk(
        Connection $connection,
        StorageMap $storageMap,
        TypeMap $typeMap,
        int $offset,
        int $limit,
    ): array {
        $tables = array_slice($this->tables($storageMap), $offset, $limit, preserve_keys: true);

        return iterator_to_array($this->metadata($connection, $storageMap, $typeMap, $tables), preserve_keys: false);
    }

    /**
     * @param  list<string>  $only
     * @return array<string, true>
     */
    private function filter(array $only): array
    {
        $filter = [];

        foreach ($only as $table) {
            $table = strtolower(pathinfo($table, PATHINFO_FILENAME));
            $filter[$table] = true;
        }

        return $filter;
    }

    /**
     * @param  array<string, true>  $filter
     * @return array<string, array{MetadataKind, string}>
     */
    private function tables(StorageMap $storageMap, array $filter = []): array
    {
        $tables = [];

        foreach (MetadataKind::roots() as $kind) {
            foreach ($storageMap->uuids($kind->storageToken()) as $code => $uuid) {
                $table = strtolower(sprintf('_%s%d', $kind->storageToken(), $code));
                $tables[$table] = [$kind, $uuid];
            }
        }

        $missing = array_diff_key($filter, $tables);

        if ($missing !== []) {
            throw new RuntimeException(sprintf(
                'DBNames has no entries for tables: %s',
                implode(', ', array_keys($missing)),
            ));
        }

        return $filter === [] ? $tables : array_intersect_key($tables, $filter);
    }

    /**
     * @param  array<string, array{MetadataKind, string}>  $tables
     * @return Generator<int, MetadataDefinition>
     */
    private function metadata(
        Connection $connection,
        StorageMap $storageMap,
        TypeMap $typeMap,
        array $tables,
    ): Generator {
        $mapper = new MetadataMapper($storageMap, $typeMap);
        $contents = $this->configData(
            $connection,
            array_values(array_map(fn (array $table) => $table[1], $tables)),
        );

        foreach ($tables as [$kind, $uuid]) {
            yield $mapper->map(
                $this->parser->parse($contents[$uuid]),
                $kind,
            );
        }
    }

    /**
     * @param  list<string>  $fileNames
     * @return array<string, string>
     */
    private function configData(Connection $connection, array $fileNames): array
    {
        if ($fileNames === []) {
            return [];
        }

        $contents = [];

        foreach (array_chunk($fileNames, self::CONFIG_BATCH_SIZE) as $batch) {
            $placeholders = implode(', ', array_fill(0, count($batch), '?'));
            $rows = $connection->select(
                sprintf(
                    'SELECT filename, binarydata AS data FROM Config WHERE filename IN (%s)',
                    $placeholders,
                ),
                $batch,
            );

            foreach ($rows as $row) {
                if (!is_object($row)) {
                    throw new RuntimeException('Invalid Config row');
                }

                $fileName = property_exists($row, 'filename') ? $row->filename : null;
                $data = property_exists($row, 'data') ? $row->data : null;

                if (!is_string($fileName)) {
                    throw new RuntimeException('Invalid Config row');
                }

                $contents[$fileName] = $this->inflate($data, sprintf('Config/%s', $fileName));
            }
        }

        foreach ($fileNames as $fileName) {
            if (!array_key_exists($fileName, $contents)) {
                throw new RuntimeException(sprintf('Config/%s was not found in the infobase', $fileName));
            }
        }

        return $contents;
    }

    private function compressedData(
        Connection $connection,
        string $query,
        string $fileName,
        string $context,
    ): string {
        $row = $connection->selectOne($query, [$fileName]);

        if (!is_object($row) || !property_exists($row, 'data')) {
            throw new RuntimeException(sprintf('%s was not found in the infobase', $context));
        }

        return $this->inflate($row->data, $context);
    }

    private function inflate(mixed $data, string $context): string
    {
        $encoded = is_resource($data) ? stream_get_contents($data) : $data;
        // @mago-expect lint:no-error-control-operator
        $decoded = is_string($encoded) ? @gzinflate($encoded) : false;

        if (!is_string($decoded)) {
            throw new RuntimeException(sprintf('Cannot inflate %s', $context));
        }

        return $decoded;
    }
}
