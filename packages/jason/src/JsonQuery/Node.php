<?php

namespace PHPinnacle\Jason\JsonQuery;

use Closure;

/** @internal */
final readonly class Node
{
    /** @param list<array-key>|null $path Null identifies a computed value. */
    public function __construct(
        public mixed $value,
        public ?array $path,
    ) {}

    /** @return list<self> */
    public function children(): array
    {
        if (!is_array($this->value)) {
            return [];
        }

        $children = [];

        foreach ($this->value as $key => $value) {
            $children[] = new self($value, $this->path === null ? null : [...$this->path, $key]);
        }

        return $children;
    }

    /** @return list<self> */
    public function child(string|int $key, bool $create): array
    {
        if (!is_array($this->value)) {
            return [];
        }

        if (is_int($key) && $key < 0) {
            $key += count($this->value);
        }

        if (!array_key_exists($key, $this->value) && !$create) {
            return [];
        }

        $key = array_key_first([$key => true]);

        return [new self(
            array_key_exists($key, $this->value) ? $this->value[$key] : [],
            $this->path === null ? null : [...$this->path, $key],
        )];
    }

    /** @return list<self> */
    public function member(string $name, bool $create): array
    {
        $children = $this->child($name, $create);

        if ($children !== [] || $name !== 'length') {
            return $children;
        }

        $length = match (true) {
            is_array($this->value) => count($this->value),
            is_scalar($this->value) => strlen((string) $this->value),
            default => 0,
        };

        return [new self($length, null)];
    }

    public function length(): int|false
    {
        return match (true) {
            is_array($this->value) => count($this->value),
            is_string($this->value) => strlen($this->value),
            default => false,
        };
    }

    /** @return list<self> */
    public function slice(?int $start, ?int $end, int $step): array
    {
        if (!is_array($this->value)) {
            return [];
        }

        $length = count($this->value);
        $lower = $step > 0 ? 0 : -1;
        $upper = $step > 0 ? $length : $length - 1;
        $from = match (true) {
            $start === null => $step > 0 ? 0 : $length - 1,
            $start < 0 => max($lower, min($upper, $length + $start)),
            default => max($lower, min($upper, $start)),
        };
        $to = match (true) {
            $end === null => $step > 0 ? $length : -1,
            $end < 0 => max($lower, min($upper, $length + $end)),
            default => max($lower, min($upper, $end)),
        };
        $nodes = [];

        for ($index = $from; $step > 0 ? $index < $to : $index > $to; $index += $step) {
            array_push($nodes, ...$this->child($index, false));
        }

        return $nodes;
    }

    /** @return list<self> */
    public function descendants(): array
    {
        $nodes = [$this];

        foreach ($this->children() as $child) {
            array_push($nodes, ...$child->descendants());
        }

        return $nodes;
    }

    /**
     * @param list<Closure(mixed, bool, self ...): list<self>> $steps
     * @return list<self>
     */
    public function follow(array $steps, mixed $root): array
    {
        $nodes = [$this];

        foreach ($steps as $step) {
            $nodes = $step($root, false, ...$nodes);
        }

        return $nodes;
    }
}
