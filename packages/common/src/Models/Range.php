<?php

namespace PHPinnacle\Common\Models;

use Countable;

readonly class Range
{
    public function __construct(
        private float|int|null $left,
        private float|int|null $right,
    ) {}

    /**
     * @param array{from?: int|float|null, to?: int|float|null} $data
     */
    public static function create(array $data): self
    {
        $from = $data['from'] ?? PHP_FLOAT_MIN;
        $to = $data['to'] ?? PHP_FLOAT_MAX;

        return self::make($from, $to);
    }

    public static function make(float|int|null $left, float|int|null $right): self
    {
        return new self($left, $right);
    }

    /**
     * @param Countable|array<array-key, mixed>|float|int $value
     */
    public function match(Countable|array|float|int $value, bool $strict = false): bool
    {
        $source = match (true) {
            $value instanceof Countable, is_array($value) => count($value),
            default => $value,
        };

        return (
            ($this->left === null || ($strict ? $source > $this->left : $source >= $this->left))
            && ($this->right === null || ($strict ? $source < $this->right : $source <= $this->right))
        );
    }
}
