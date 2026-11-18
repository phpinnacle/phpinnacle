<?php

namespace PHPinnacle\Rosetta\Contracts;

interface Connection
{
    /**
     * @param  list<mixed>  $bindings
     * @return list<object>
     */
    public function select(string $query, array $bindings = []): array;

    /** @param list<mixed> $bindings */
    public function selectOne(string $query, array $bindings = []): ?object;

    /** Check an unqualified physical table name using the connection's normal name resolution. */
    public function hasColumn(string $table, string $column): bool;
}
