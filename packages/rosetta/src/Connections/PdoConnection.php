<?php

namespace PHPinnacle\Rosetta\Connections;

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

        if (!is_array($rows)) {
            throw new RuntimeException('Cannot fetch infobase rows');
        }

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

        if (!is_object($row)) {
            throw new RuntimeException('Cannot fetch an infobase row');
        }

        return $row;
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
