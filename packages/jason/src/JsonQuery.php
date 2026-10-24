<?php

namespace PHPinnacle\Jason;

use Closure;
use PHPinnacle\Jason\JsonQuery\Node;
use PHPinnacle\Jason\JsonQuery\Parser;

final readonly class JsonQuery
{
    /** @var list<Closure(mixed, bool, Node ...): list<Node>> */
    private array $steps;

    public function __construct(string $path)
    {
        $this->steps = Parser::parse($path);
    }

    /** @return list<mixed> */
    public function find(mixed $data): array
    {
        return array_map(fn (Node $node) => $node->value, new Node($data, [])->follow($this->steps, $data));
    }

    /** @param Closure(mixed): mixed $transform */
    public function update(mixed $data, Closure $transform): mixed
    {
        $nodes = new Node($data, [])->follow($this->steps, $data);
        $values = [];

        foreach ($nodes as $node) {
            if ($node->path !== null) {
                $values[] = &$this->reference($data, $node->path);
            }
        }

        foreach ($values as &$value) {
            $value = $transform($value);
        }

        return $data;
    }

    public function set(mixed $data, mixed $value): mixed
    {
        $nodes = [new Node($data, [])];

        foreach ($this->steps as $step) {
            $nodes = $step($data, true, ...$nodes);

            foreach ($nodes as $node) {
                if ($node->path !== null) {
                    $this->write($data, $node->path, $node->value);
                }
            }
        }

        foreach ($nodes as $node) {
            if ($node->path !== null) {
                $this->write($data, $node->path, $value);
            }
        }

        return $data;
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    public function remove(array $data): array
    {
        $paths = [];

        foreach (new Node($data, [])->follow($this->steps, $data) as $node) {
            if ($node->path === []) {
                return [];
            }

            if ($node->path !== null) {
                $paths[serialize($node->path)] = true;
            }
        }

        return $this->removePaths($data, [], $paths);
    }

    /** @param list<array-key> $path */
    private function write(mixed &$data, array $path, mixed $value): void
    {
        $target = &$this->reference($data, $path);
        $target = $value;
    }

    /** @param list<array-key> $path */
    private function &reference(mixed &$data, array $path): mixed
    {
        if ($path !== [] && is_array($data)) {
            $key = array_shift($path);

            return $this->reference($data[$key], $path);
        }

        return $data;
    }

    /**
     * @param array<array-key, mixed> $data
     * @param list<array-key> $parent
     * @param array<string, true> $paths
     * @return array<array-key, mixed>
     */
    private function removePaths(array $data, array $parent, array $paths): array
    {
        $list = array_is_list($data);

        foreach ($data as $key => $value) {
            $path = [...$parent, $key];

            if (array_key_exists(serialize($path), $paths)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->removePaths($value, $path, $paths);
            }
        }

        return $list ? array_values($data) : $data;
    }
}
