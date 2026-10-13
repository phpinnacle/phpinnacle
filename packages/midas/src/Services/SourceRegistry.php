<?php

namespace PHPinnacle\Midas\Services;

use Illuminate\Container\Attributes\Singleton;
use PHPinnacle\Midas\Contracts\TransactionSource;

#[Singleton]
class SourceRegistry
{
    /** @var array<string, TransactionSource> */
    private array $sources = [];

    public function register(TransactionSource ...$sources): void
    {
        foreach ($sources as $source) {
            $this->sources[$source->type()] = $source;
        }
    }

    public function get(string $type): ?TransactionSource
    {
        return $this->sources[$type] ?? null;
    }
}
