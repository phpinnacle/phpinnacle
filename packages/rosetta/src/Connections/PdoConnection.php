<?php

namespace PHPinnacle\Rosetta\Connections;

use InvalidArgumentException;
use PDO;
use PDOStatement;
use PHPinnacle\Rosetta\Contracts\Connection;
use RuntimeException;

final readonly class PdoConnection implements Connection
{
    public function __construct(
        private PDO $pdo,
    ) {}

    public function select(string $query, array $bindings = []): array
    {
        $rows = $this->statement($query, $bindings)->fetchAll(PDO::FETCH_OBJ);

        $objects = [];

        foreach ($rows as $row) {
            if (!is_object($row)) {
                throw new RuntimeException('Cannot fetch an infobase row');
            }

            $objects[] = $row;
        }

        return $objects;
    }

    public function selectOne(string $query, array $bindings = []): ?object
    {
        $row = $this->statement($query, $bindings)->fetch(PDO::FETCH_OBJ);

        if ($row === false) {
            return null;
        }

        return $row;
    }

    public function hasColumn(string $table, string $column): bool
    {
        if (!preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $table)) {
            throw new InvalidArgumentException('Invalid infobase table name');
        }

        $statement = $this->statement(sprintf('SELECT * FROM %s WHERE 1 = 0', $table), []);

        for ($index = 0, $count = $statement->columnCount(); $index < $count; $index++) {
            $metadata = $statement->getColumnMeta($index);

            if ($metadata === false) {
                throw new RuntimeException(sprintf('Cannot read column metadata for %s', $table));
            }

            if (strcasecmp($metadata['name'], $column) === 0) {
                return true;
            }
        }

        return false;
    }

    /** @param list<mixed> $bindings */
    private function statement(string $query, array $bindings): PDOStatement
    {
        $statement = $this->pdo->prepare($query);

        if (!$statement instanceof PDOStatement || !$statement->execute($bindings)) {
            throw new RuntimeException('Cannot execute an infobase query');
        }

        return $statement;
    }
}
